<?php

namespace App\Services\AI;

use App\Models\QuestionBank;
use App\Models\ExamSet;
use Illuminate\Support\Str;

class AiExerciseGeneratorService
{
    protected GeminiApiService $gemini;

    public function __construct(GeminiApiService $gemini)
    {
        $this->gemini = $gemini;
    }

    /**
     * Normalize text for strict anti-duplication comparison.
     */
    public static function normalizeText(string $text): string
    {
        $clean = mb_strtolower(trim($text), 'UTF-8');
        // Remove common punctuation and extraneous whitespaces
        $clean = preg_replace('/[^\p{L}\p{N}\s]/u', '', $clean);
        return preg_replace('/\s+/', ' ', $clean);
    }

    /**
     * Generate structured, standardized English test questions according to real exam formats.
     * Supports: THPT Quốc Gia, IELTS, TOEIC, VSTEP (B1-B2-C1), and CEFR.
     *
     * @param string $topic Topic / context (e.g., "Môi trường & Biến đổi khí hậu", "Workplace Communication", "Technology & AI")
     * @param string $difficulty "A1", "A2", "B1", "B2", "C1", "Mixed"
     * @param int $count Number of questions to generate (1-50)
     * @param string $skill "all", "reading", "listening", "writing", "speaking", "grammar", "vocabulary"
     * @param array $questionTypes E.g. ['mcq']
     * @param string $examStandard "thpt_qg", "ielts", "toeic", "vstep", "cefr"
     * @param string $mode "full_exam" (ma trận đề thi thật) or "custom"
     * @param string|null $section Optional specific section
     * @return array
     */
    public function generateQuestions(
        string $topic,
        string $difficulty = 'B1',
        int $count = 10,
        string $skill = 'all',
        array $questionTypes = ['mcq'],
        string $examStandard = 'general',
        string $mode = 'standard',
        ?string $section = null
    ): array {
        $targetCount = max(1, min(50, (int) $count));
        $validSkills = ['reading', 'listening', 'writing', 'speaking', 'grammar', 'vocabulary'];
        $cleanTopic = trim($topic) ?: $this->getDefaultTopicForStandard($examStandard);
        $standard = in_array($examStandard, ['thpt_qg', 'ielts', 'toeic', 'vstep', 'cefr']) ? $examStandard : 'general';

        // Preload existing QuestionBank questions to check against database
        $existingDbQuestions = QuestionBank::query()
            ->select('question_text')
            ->limit(1000)
            ->pluck('question_text')
            ->map(fn($t) => self::normalizeText($t))
            ->flip()
            ->toArray();

        $seenInBatch = [];
        $finalQuestions = [];

        // 1. Try Gemini API if configured
        if ($this->gemini->isConfigured()) {
            $batchSize = min(15, $targetCount);
            $remaining = $targetCount;
            $maxAttempts = (int) ceil($targetCount / $batchSize) + 2;
            $attempts = 0;

            while ($remaining > 0 && $attempts < $maxAttempts) {
                $attempts++;
                $currentBatchSize = min($batchSize, $remaining);

                $avoidList = array_slice(array_keys($seenInBatch), -20);
                $avoidPrompt = !empty($avoidList)
                    ? "\nCRITICAL: DO NOT REPEAT OR GENERATE QUESTIONS SIMILAR TO THESE ALREADY CREATED:\n- " . implode("\n- ", $avoidList)
                    : "";

                $systemPrompt = $this->buildSystemPromptForStandard($standard, $difficulty, $skill, $cleanTopic, $currentBatchSize, $avoidPrompt, $mode, $section);

                $jsonSchema = [
                    'type' => 'OBJECT',
                    'properties' => [
                        'questions' => [
                            'type' => 'ARRAY',
                            'items' => [
                                'type' => 'OBJECT',
                                'properties' => [
                                    'skill' => ['type' => 'STRING', 'enum' => ['reading', 'listening', 'writing', 'speaking', 'grammar', 'vocabulary']],
                                    'difficulty' => ['type' => 'STRING', 'enum' => ['A1', 'A2', 'B1', 'B2', 'C1', 'Mixed']],
                                    'question_type' => ['type' => 'STRING', 'enum' => ['mcq', 'multiple_select', 'fill_blank', 'word_ordering', 'matching', 'true_false', 'audio_listening', 'pronunciation_speech', 'essay_writing']],
                                    'part_name' => ['type' => 'STRING'],
                                    'question_text' => ['type' => 'STRING'],
                                    'options' => [
                                        'type' => 'ARRAY',
                                        'items' => ['type' => 'STRING']
                                    ],
                                    'correct_answer' => ['type' => 'STRING'],
                                    'explanation' => ['type' => 'STRING'],
                                    'passage_title' => ['type' => 'STRING'],
                                    'passage_content' => ['type' => 'STRING'],
                                    'audio_script' => ['type' => 'STRING'],
                                    'task_type' => ['type' => 'STRING'],
                                ],
                                'required' => ['skill', 'difficulty', 'question_type', 'question_text', 'options', 'correct_answer', 'explanation']
                            ]
                        ]
                    ],
                    'required' => ['questions']
                ];

                $userPrompt = "Generate {$currentBatchSize} authentic, realistic exam questions according to {$standard} standard for topic '{$cleanTopic}' at level {$difficulty}. Output structured JSON.";

                try {
                    $response = $this->gemini->generateContent(
                        model: 'flash',
                        systemPrompt: $systemPrompt,
                        userPrompt: $userPrompt,
                        jsonSchema: $jsonSchema,
                        temperature: 0.7
                    );

                    $batchQuestions = $response['questions'] ?? [];

                    foreach ($batchQuestions as $q) {
                        $this->enrichQuestionMetadata($q, $standard);
                        if ($this->validateAndDeduplicateQuestion($q, $seenInBatch, $existingDbQuestions, $validSkills, $difficulty, $skill, false)) {
                            $finalQuestions[] = $q;
                            $remaining--;
                            if ($remaining <= 0) break;
                        }
                    }
                } catch (\Throwable $e) {
                    break;
                }
            }
        }

        // 2. If not enough questions from Gemini (or Gemini offline), synthesize authentic standard procedural questions
        if (count($finalQuestions) < $targetCount) {
            $needed = $targetCount - count($finalQuestions);
            $procedural = $this->synthesizeStandardQuestions($standard, $cleanTopic, $difficulty, $needed, $skill, $seenInBatch, $existingDbQuestions, $mode);
            foreach ($procedural as $pq) {
                $finalQuestions[] = $pq;
            }
        }

        return array_values(array_slice($finalQuestions, 0, $targetCount));
    }

    /**
     * Build rich, specialized system prompt depending on the chosen exam standard.
     */
    protected function buildSystemPromptForStandard(
        string $standard,
        string $difficulty,
        string $skill,
        string $topic,
        int $batchSize,
        string $avoidPrompt,
        string $mode,
        ?string $section
    ): string {
        switch ($standard) {
            case 'thpt_qg':
                return <<<PROMPT
# Senior Author for Vietnamese National High School Graduation Exam (Đề thi Tốt nghiệp THPT môn Tiếng Anh)
You are an expert test creator for the Vietnamese Ministry of Education & Training (Bộ GD&ĐT).
Generate exactly {$batchSize} authentic questions strictly following the official THPT Quốc Gia exam format on topic: '{$topic}' (Difficulty: {$difficulty}).

## Official Question Types & Stems to distribute across:
1. **Phát âm (Pronunciation)**: "Mark the letter A, B, C, or D on your answer sheet to indicate the word whose underlined part differs from the other three in pronunciation in each of the following questions." (Focus: -ed endings, -s/es, vowels).
2. **Trọng âm (Stress)**: "Mark the letter A, B, C, or D on your answer sheet to indicate the word that differs from the other three in the position of primary stress in each of the following questions." (2-syllable & 3-syllable words).
3. **Ngữ pháp & Từ vựng (Grammar & Vocabulary)**: Question stem with blank (...). Focus on: Tag questions, Articles, Collocations, Phrasal verbs, Conditionals, Inversion, Reduced relative clauses, Prepositions, Tenses, Idioms.
4. **Từ đồng nghĩa & Trái nghĩa**: "Mark the letter A, B, C, or D on your answer sheet to indicate the word(s) CLOSEST in meaning to the underlined word(s)..." and "OPPOSITE in meaning to the underlined word(s)...".
5. **Giao tiếp (Social Communication)**: Realistic dialogue exchange with a blank for polite response.
6. **Đọc điền từ (Cloze Reading)**: Include a reading passage of 120-180 words in 'passage_content' and title in 'passage_title'. Provide fill-in questions for blanks.
7. **Đọc hiểu (Reading Comprehension)**: Provide an authentic, informative passage of 250-400 words in 'passage_content' and 'passage_title'. Generate questions targeting main idea, specific details, pronoun reference, and inference.
8. **Tìm lỗi sai (Error Identification)**: Identify grammatical or confusing word errors.
9. **Viết lại câu & Kết hợp câu (Sentence Transformation & Combination)**: Modal verbs, reported speech, comparative structures, inversion/cleft sentences.

## Mandatory Rules:
- MCQ: Exactly 4 options (A, B, C, D) formatted cleanly.
- Explanation: Detailed explanation in Vietnamese (Tiếng Việt) explaining the exact grammatical rule, vocabulary definition, or quote from the passage.{$avoidPrompt}
PROMPT;

            case 'ielts':
                return <<<PROMPT
# Cambridge IELTS Senior Assessment Specialist
You are an official Cambridge IELTS senior item designer.
Generate exactly {$batchSize} authentic IELTS test items on topic: '{$topic}' at target CEFR/Band: {$difficulty}.

## Authentic IELTS Modules:
- **Reading**: Academic Reading Passages with 'passage_title' and 300-500 word 'passage_content'. Question types: Multiple Choice (A, B, C, D), True/False/Not Given, Yes/No/Not Given, Summary Completion.
- **Listening**: Conversational or academic lecture script in 'audio_script' and 'question_text'.
- **Writing**: Task 1 (Data description / report / formal letter) or Task 2 (Discursive essay min 250 words with prompt scenario, viewpoints to discuss, and grading rubric).
- **Speaking**: Part 1 (Introduction questions), Part 2 (Cue Card with 1-minute prep bullet points: You should say...), Part 3 (Two-way abstract discussion questions).

## Mandatory Rules:
- Set 'skill' accurately ('reading', 'listening', 'writing', 'speaking').
- Every item must have a comprehensive Vietnamese explanation clarifying the reasoning and band descriptors.{$avoidPrompt}
PROMPT;

            case 'toeic':
                return <<<PROMPT
# Official ETS TOEIC Test Item Developer
You are an expert test creator for the TOEIC Listening & Reading exam (Business English).
Generate exactly {$batchSize} authentic TOEIC test questions on topic: '{$topic}' at level {$difficulty}.

## Authentic TOEIC Parts:
- **Part 5 (Incomplete Sentences)**: High-frequency workplace sentences with blanks testing parts of speech (nouns, adjectives, adverbs), prepositions, conjunctions, verb tenses, and business collocations (contracts, marketing, human resources, shipping, finance).
- **Part 6 (Text Completion)**: Business memo, email, or announcement with blanks.
- **Part 7 (Reading Comprehension)**: Business document (email, press release, schedule, order invoice) in 'passage_content' and 'passage_title' with 2-4 comprehension questions.
- **Listening (Part 2-4)**: Business conversation or short talk script in 'audio_script'.

## Mandatory Rules:
- 4 clear options for MCQ.
- Complete Vietnamese explanation with business context translation and grammar analysis.{$avoidPrompt}
PROMPT;

            case 'vstep':
                return <<<PROMPT
# Senior Examination Author for VSTEP (Khung Năng Lực Ngoại Ngữ 6 Bậc Việt Nam)
You are an author for the Vietnamese Standardized Test of English Proficiency (VSTEP B1 - B2 - C1).
Generate exactly {$batchSize} authentic VSTEP exam items on topic: '{$topic}' at level {$difficulty}.

## Authentic VSTEP Format:
- **Reading**: Comprehension passages (300-450 words) with title in 'passage_title' and text in 'passage_content'. Questions testing main idea, stated details, vocabulary in context, author purpose, and inference.
- **Listening**: Public announcements, workplace conversations, or academic lectures with audio scripts.
- **Writing**: Task 1 (Letter/Email 120 words with 3 bulleted requirements) or Task 2 (Essay 250 words discussing social issues).
- **Speaking**: Part 1 (Social interaction), Part 2 (Solution discussion choosing 1 of 3 options), Part 3 (Topic development with mindmap arguments).

## Mandatory Rules:
- Comprehensive Vietnamese explanation for each item.{$avoidPrompt}
PROMPT;

            default:
                return <<<PROMPT
# International CEFR English Examination Author
You are a senior test author designing standardized English test items at CEFR Level: '{$difficulty}' on topic: '{$topic}'.
Generate exactly {$batchSize} unique, high-quality test items across skills: {$skill}.

## Requirements:
1. Strict CEFR vocabulary frequency and grammar alignment.
2. For reading: Provide passage title and passage content when appropriate.
3. For listening: Provide conversational dialogue scripts.
4. Vietnamese Explanation: Thorough grammatical and lexical breakdown.{$avoidPrompt}
PROMPT;
        }
    }

    /**
     * Default topics tailored to each standard.
     */
    protected function getDefaultTopicForStandard(string $standard): string
    {
        return match ($standard) {
            'thpt_qg' => 'Bảo vệ Môi trường, Giáo dục Hiện đại & Chuyển đổi Số',
            'ielts' => 'Environmental Sustainability & Scientific Innovations',
            'toeic' => 'Corporate Management, Product Launch & Customer Relations',
            'vstep' => 'Higher Education, Digital Lifestyle & Community Tourism',
            default => 'Workplace Communication & English in Daily Life',
        };
    }

    /**
     * Enrich question metadata with standard identifiers and passage bindings.
     */
    protected function enrichQuestionMetadata(array &$q, string $standard): void
    {
        $meta = is_array($q['meta_data'] ?? null) ? $q['meta_data'] : [];

        $meta['exam_standard'] = $standard;
        if (!empty($q['part_name'])) {
            $meta['part_name'] = $q['part_name'];
        }
        if (!empty($q['passage_title'])) {
            $meta['passage_title'] = $q['passage_title'];
        }
        if (!empty($q['passage_content'])) {
            $meta['passage_content'] = $q['passage_content'];
            $meta['passage'] = $q['passage_content']; // Compatibility alias
        }
        if (!empty($q['audio_script'])) {
            $meta['audio_script'] = $q['audio_script'];
        }
        if (!empty($q['task_type'])) {
            $meta['task_type'] = $q['task_type'];
        }

        $q['meta_data'] = $meta;
    }

    /**
     * Validate question integrity and enforce strict deduplication.
     */
    protected function validateAndDeduplicateQuestion(
        array &$q,
        array &$seenInBatch,
        array &$existingDbQuestions,
        array $validSkills,
        string $defaultDiff,
        string $targetSkill,
        bool $allowDbVariant = true
    ): bool {
        if (empty($q['question_text']) || (!isset($q['correct_answer']) && empty($q['options']))) {
            return false;
        }

        $norm = self::normalizeText($q['question_text']);
        if (strlen($norm) < 8) {
            return false;
        }

        // Check duplicate in current batch
        if (isset($seenInBatch[$norm])) {
            return false;
        }

        // Check duplicate in database
        if (isset($existingDbQuestions[$norm])) {
            if (!$allowDbVariant) {
                return false;
            }
            $variantTag = " (Biến thể đề #" . (count($seenInBatch) + 1) . ")";
            $q['question_text'] .= $variantTag;
            $norm = self::normalizeText($q['question_text']);
            if (isset($seenInBatch[$norm]) || isset($existingDbQuestions[$norm])) {
                return false;
            }
        }

        // Enforce skill
        if (!in_array($q['skill'] ?? '', $validSkills)) {
            $q['skill'] = ($targetSkill !== 'all' && in_array($targetSkill, $validSkills)) ? $targetSkill : 'reading';
        }

        if ($targetSkill !== 'all' && in_array($targetSkill, $validSkills)) {
            $q['skill'] = $targetSkill;
        }

        // Standardize options for MCQ
        $qType = $q['question_type'] ?? 'mcq';
        if ($qType === 'mcq' || $qType === 'true_false') {
            $options = is_array($q['options']) ? array_values(array_unique(array_filter(array_map('trim', $q['options'])))) : [];
            if (count($options) < 2) {
                return false;
            }

            $correct = trim($q['correct_answer'] ?? '');
            $matched = false;
            foreach ($options as $opt) {
                if (mb_strtolower($opt) === mb_strtolower($correct)) {
                    $q['correct_answer'] = $opt;
                    $matched = true;
                    break;
                }
            }
            if (!$matched && !empty($correct)) {
                $options[] = $correct;
                $options = array_values(array_unique($options));
            }
            $q['options'] = $options;
        }

        if (empty($q['difficulty'])) {
            $q['difficulty'] = in_array($defaultDiff, ['A1', 'A2', 'B1', 'B2', 'C1']) ? $defaultDiff : 'B1';
        }

        $seenInBatch[$norm] = true;
        return true;
    }

    /**
     * Procedural synthesis engine for authentic test items when Gemini is unavailable.
     */
    protected function synthesizeStandardQuestions(
        string $standard,
        string $topic,
        string $difficulty,
        int $count,
        string $skill,
        array &$seenInBatch,
        array &$existingDbQuestions,
        string $mode
    ): array {
        $level = in_array($difficulty, ['A1', 'A2', 'B1', 'B2', 'C1']) ? $difficulty : 'B1';
        $validSkills = ['reading', 'listening', 'writing', 'speaking', 'grammar', 'vocabulary'];
        $results = [];

        // 1. Get authentic bank for standard
        $templates = match ($standard) {
            'thpt_qg' => $this->getThptProceduralBank($topic, $level),
            'ielts' => $this->getIeltsProceduralBank($topic, $level),
            'toeic' => $this->getToeicProceduralBank($topic, $level),
            'vstep' => $this->getVstepProceduralBank($topic, $level),
            default => $this->getGeneralProceduralBank($topic, $level),
        };

        foreach ($templates as $item) {
            if (count($results) >= $count) break;

            $this->enrichQuestionMetadata($item, $standard);
            if ($this->validateAndDeduplicateQuestion($item, $seenInBatch, $existingDbQuestions, $validSkills, $level, $skill, true)) {
                $results[] = $item;
            }
        }

        // 2. If more are needed, dynamically generate combinatorial authentic items
        $iteration = 1;
        while (count($results) < $count && $iteration <= 100) {
            $dyn = $this->createDynamicStandardItem($standard, $topic, $level, $iteration++);
            $this->enrichQuestionMetadata($dyn, $standard);
            if ($this->validateAndDeduplicateQuestion($dyn, $seenInBatch, $existingDbQuestions, $validSkills, $level, $skill, true)) {
                $results[] = $dyn;
            }
        }

        return $results;
    }

    /**
     * Authentic THPT Quốc Gia Procedural Bank (Pronunciation, Stress, Grammar, Synonyms, Cloze, Reading, Inversion).
     */
    protected function getThptProceduralBank(string $topic, string $level): array
    {
        $clozePassage = "Urbanization and digital transformation are rapidly changing the way people live and work in Vietnam. In recent years, sustainable practices have been widely (1) ___ by corporations and educational institutions alike. Not only (2) ___ these initiatives conserve precious natural resources, but they also foster innovation across diverse sectors. According to environmental experts, the transition towards a green economy requires collective determination from both the government and local communities. (3) ___, without proactive education and public awareness, long-term climate goals will remain difficult to achieve.";

        $readingPassage = "The transition to renewable energy sources has emerged as one of the most critical imperatives of the 21st century. As fossil fuel reserves continue to deplete and greenhouse gas emissions escalate global temperatures, governments across the world are investing heavily in solar, wind, and hydroelectric infrastructure.\n\nIn Southeast Asia, countries like Vietnam have witnessed exponential growth in solar photovoltaic installations over the past decade. Favorable feed-in tariffs combined with abundant solar irradiance have propelled Vietnam to the forefront of clean energy development in the ASEAN region. However, this unprecedented acceleration has revealed significant infrastructural bottlenecks, particularly regarding power grid capacity and energy storage technologies.\n\nEnergy analysts argue that without substantial modernization of transmission lines, surplus energy generated in peak hours cannot be effectively transmitted to industrial hubs. Therefore, smart grid technologies and advanced battery storage systems must be deployed in tandem with renewable generation to ensure long-term stability and economic feasibility.";

        return [
            // 1. Pronunciation -ed
            [
                'skill' => 'grammar',
                'difficulty' => $level,
                'question_type' => 'mcq',
                'part_name' => 'Phần 1: Ngữ âm & Trọng âm',
                'question_text' => "Mark the letter A, B, C, or D to indicate the word whose underlined part differs from the other three in pronunciation:\n(A) maintain<u>ed</u>   (B) develop<u>ed</u>   (C) improv<u>ed</u>   (D) establish<u>ed</u>",
                'options' => ['A. maintained', 'B. developed', 'C. improved', 'D. established'],
                'correct_answer' => 'A. maintained',
                'explanation' => "Đuôi '-ed' trong 'maintained' phát âm là /d/ (âm cuối là nguyên âm /eɪ/). Trong 'developed' và 'established' phát âm là /t/ (âm vô thanh /p/ và /ʃ/). Trong 'improved' phát âm là /d/.",
            ],
            // 2. Stress 2 syllables
            [
                'skill' => 'grammar',
                'difficulty' => $level,
                'question_type' => 'mcq',
                'part_name' => 'Phần 1: Ngữ âm & Trọng âm',
                'question_text' => "Mark the letter A, B, C, or D to indicate the word that differs from the other three in the position of primary stress:",
                'options' => ['A. protect', 'B. balance', 'C. reduce', 'D. provide'],
                'correct_answer' => 'B. balance',
                'explanation' => "'Balance' là danh từ/động từ 2 âm tiết có trọng âm rơi vào âm tiết thứ nhất (/'bæləns/). Các từ còn lại có trọng âm rơi vào âm tiết thứ hai: protect (/prə'tekt/), reduce (/rɪ'dju:s/), provide (/prə'vaɪd/).",
            ],
            // 3. Collocation
            [
                'skill' => 'vocabulary',
                'difficulty' => $level,
                'question_type' => 'mcq',
                'part_name' => 'Phần 2: Ngữ pháp & Từ vựng',
                'question_text' => "The city council decided to ___ strict regulations to reduce plastic waste in the urban area.",
                'options' => ['A. impose', 'B. commit', 'C. create', 'D. handle'],
                'correct_answer' => 'A. impose',
                'explanation' => "Cụm từ cố định (collocation): 'impose strict regulations on sth' = ban hành/áp đặt các quy định nghiêm ngặt.",
            ],
            // 4. Inversion
            [
                'skill' => 'grammar',
                'difficulty' => $level,
                'question_type' => 'mcq',
                'part_name' => 'Phần 2: Ngữ pháp & Từ vựng',
                'question_text' => "Not only ___ renewable energy sources, but they also established strict recycling guidelines.",
                'options' => [
                    'A. did the corporation adopt',
                    'B. the corporation adopted',
                    'C. had the corporation adopt',
                    'D. the corporation did adopt'
                ],
                'correct_answer' => 'A. did the corporation adopt',
                'explanation' => "Cấu trúc đảo ngữ với 'Not only + Trợ động từ + S + V..., but S also...': 'Not only did the corporation adopt...'.",
            ],
            // 5. Closest in meaning
            [
                'skill' => 'vocabulary',
                'difficulty' => $level,
                'question_type' => 'mcq',
                'part_name' => 'Phần 3: Từ đồng nghĩa',
                'question_text' => "Mark the letter A, B, C, or D to indicate the word CLOSEST in meaning to the underlined word:\n'The government launched an ambitious campaign to <u>curtail</u> industrial emissions.'",
                'options' => ['A. restrict', 'B. promote', 'C. ignite', 'D. prolong'],
                'correct_answer' => 'A. restrict',
                'explanation' => "'Curtail' có nghĩa là cắt giảm, hạn chế, đồng nghĩa với 'restrict' (hoặc limit/cut down).",
            ],
            // 6. Opposite in meaning
            [
                'skill' => 'vocabulary',
                'difficulty' => $level,
                'question_type' => 'mcq',
                'part_name' => 'Phần 3: Từ trái nghĩa',
                'question_text' => "Mark the letter A, B, C, or D to indicate the word OPPOSITE in meaning to the underlined word:\n'His explanations during the symposium were remarkably <u>transparent</u> and easy to follow.'",
                'options' => ['A. ambiguous', 'B. obvious', 'C. candid', 'D. lucid'],
                'correct_answer' => 'A. ambiguous',
                'explanation' => "'Transparent' (rõ ràng, minh bạch) trái nghĩa với 'ambiguous' (mơ hồ, khó hiểu). 'Obvious', 'candid', 'lucid' đều mang nghĩa rõ ràng/thẳng thắn.",
            ],
            // 7. Cloze passage item 1
            [
                'skill' => 'reading',
                'difficulty' => $level,
                'question_type' => 'mcq',
                'part_name' => 'Phần 4: Đọc điền từ vào đoạn văn',
                'passage_title' => 'Sustainable Urban Growth in Vietnam',
                'passage_content' => $clozePassage,
                'question_text' => "Read the passage and choose the best word for blank (1):",
                'options' => ['A. embraced', 'B. dismissed', 'C. terminated', 'D. neglected'],
                'correct_answer' => 'A. embraced',
                'explanation' => "'Embraced' (được đón nhận, ủng hộ và áp dụng rộng rãi) phù hợp nhất với ngữ cảnh tích cực của câu.",
            ],
            // 8. Cloze passage item 2
            [
                'skill' => 'reading',
                'difficulty' => $level,
                'question_type' => 'mcq',
                'part_name' => 'Phần 4: Đọc điền từ vào đoạn văn',
                'passage_title' => 'Sustainable Urban Growth in Vietnam',
                'passage_content' => $clozePassage,
                'question_text' => "Choose the best transitional word for blank (3):",
                'options' => ['A. However', 'B. Therefore', 'C. Moreover', 'D. Because'],
                'correct_answer' => 'A. However',
                'explanation' => "'However' (Tuy nhiên) thể hiện sự tương phản với mệnh đề trước: mặc dù cần sự nỗ lực chung, tuy nhiên nếu thiếu giáo dục nhận thức thì mục tiêu khó đạt được.",
            ],
            // 9. Reading comprehension: Main idea
            [
                'skill' => 'reading',
                'difficulty' => $level,
                'question_type' => 'mcq',
                'part_name' => 'Phần 5: Đọc hiểu văn bản',
                'passage_title' => 'The Renewable Energy Transition in Southeast Asia',
                'passage_content' => $readingPassage,
                'question_text' => "What is the primary topic of the passage?",
                'options' => [
                    'A. Vietnam’s solar energy boom and infrastructural challenges',
                    'B. The permanent depletion of all fossil fuel reserves worldwide',
                    'C. Why solar energy is obsolete compared to hydroelectric dams',
                    'D. Economic policies of European renewable energy grids'
                ],
                'correct_answer' => 'A. Vietnam’s solar energy boom and infrastructural challenges',
                'explanation' => "Đoạn văn tập trung phân tích sự phát triển vượt bậc của năng lượng mặt trời tại Việt Nam và các thách thức về hạ tầng lưới điện đi kèm.",
            ],
            // 10. Reading comprehension: Detail
            [
                'skill' => 'reading',
                'difficulty' => $level,
                'question_type' => 'mcq',
                'part_name' => 'Phần 5: Đọc hiểu văn bản',
                'passage_title' => 'The Renewable Energy Transition in Southeast Asia',
                'passage_content' => $readingPassage,
                'question_text' => "According to paragraph 2, what factors contributed to Vietnam's rapid solar expansion?",
                'options' => [
                    'A. Favorable feed-in tariffs and abundant solar irradiance',
                    'B. Severe decline in urban industrial power demands',
                    'C. Exclusive reliance on foreign imported coal',
                    'D. Total absence of transmission grid bottlenecks'
                ],
                'correct_answer' => 'A. Favorable feed-in tariffs and abundant solar irradiance',
                'explanation' => "Đoạn 2 nêu rõ: 'Favorable feed-in tariffs combined with abundant solar irradiance have propelled Vietnam to the forefront...'.",
            ],
            // 11. Error identification
            [
                'skill' => 'grammar',
                'difficulty' => $level,
                'question_type' => 'mcq',
                'part_name' => 'Phần 6: Tìm lỗi sai',
                'question_text' => "Find the underlined part that needs correction:\n'The <u>committee</u> members <u>have</u> submitted <u>its</u> final report to the department yesterday.'",
                'options' => ['A. committee', 'B. have', 'C. its', 'D. yesterday'],
                'correct_answer' => 'C. its',
                'explanation' => "Chủ ngữ là 'The committee members' (danh từ số nhiều chỉ các thành viên) nên đại từ sở hữu phải là 'their' thay vì 'its'. Ngoài ra 'yesterday' cần dùng thì quá khứ đơn 'submitted' thay vì 'have submitted'.",
            ],
            // 12. Sentence transformation
            [
                'skill' => 'grammar',
                'difficulty' => $level,
                'question_type' => 'mcq',
                'part_name' => 'Phần 7: Viết lại câu',
                'question_text' => "Mark the sentence that is closest in meaning to the original:\n'It was not until the manager explained the protocol that the staff understood the procedure.'",
                'options' => [
                    'A. Only after the manager explained the protocol did the staff understand the procedure.',
                    'B. The staff understood the procedure before the manager explained the protocol.',
                    'C. Not until the staff understood the procedure did the manager explain it.',
                    'D. As soon as the staff explained the protocol, the manager understood it.'
                ],
                'correct_answer' => 'A. Only after the manager explained the protocol did the staff understand the procedure.',
                'explanation' => "Cấu trúc tương đương: 'It was not until... that...' = 'Only after + S + V + Trợ động từ + S + V': 'Only after the manager explained the protocol did the staff understand the procedure.'",
            ],
        ];
    }

    /**
     * Authentic IELTS Procedural Bank (Reading with passage, Writing Tasks, Speaking Cues).
     */
    protected function getIeltsProceduralBank(string $topic, string $level): array
    {
        $ieltsPassage = "Biomimicry—the practice of looking to nature for solutions to modern engineering and architectural challenges—is transforming sustainable design. Rather than imposing artificial structures onto the environment, architects are analyzing biological systems that have evolved over billions of years.\n\nA renowned application of biomimicry is the Eastgate Centre in Harare, Zimbabwe. Designed by architect Mick Pearce in collaboration with Arup engineers, the office complex possesses no conventional air-conditioning. Instead, it is cooled by a passive system modeled on the self-cooling mounds of Macrotermes bellicosus termites.\n\nThese termites construct towering nests with a subterranean network of vents. As wind drives ambient air through the bottom flues, heat generated by underground fungus farms rises through chimneys, maintaining an internal temperature within one degree of 30°C day and night. Pearce replicated this mechanism with fans that draw cool night air into hollow floors, reducing the building's energy consumption by 35% compared to similarly sized conventional buildings.";

        return [
            // IELTS Reading 1: True / False / Not Given
            [
                'skill' => 'reading',
                'difficulty' => $level,
                'question_type' => 'true_false',
                'part_name' => 'IELTS Reading Passage 1',
                'passage_title' => 'Biomimicry: Nature-Inspired Architecture',
                'passage_content' => $ieltsPassage,
                'question_text' => "Do the following statement agree with the information in the reading passage?\n'The Eastgate Centre relies on traditional electrical air conditioning during the hottest summer months.'",
                'options' => ['True', 'False', 'Not Given'],
                'correct_answer' => 'False',
                'explanation' => "Đoạn 2 nêu rõ: 'the office complex possesses no conventional air-conditioning. Instead, it is cooled by a passive system...', do đó khẳng định nhà tòa dùng điều hòa truyền thống là FALSE.",
            ],
            // IELTS Reading 2: Multiple Choice
            [
                'skill' => 'reading',
                'difficulty' => $level,
                'question_type' => 'mcq',
                'part_name' => 'IELTS Reading Passage 1',
                'passage_title' => 'Biomimicry: Nature-Inspired Architecture',
                'passage_content' => $ieltsPassage,
                'question_text' => "How did the architect Mick Pearce achieve a 35% energy reduction in the building?",
                'options' => [
                    'A. By replicating the ventilation principles of termite mounds with cool night air',
                    'B. By cultivating subterranean fungus farms inside the office basement',
                    'C. By manufacturing artificial termites to maintain mechanical thermostats',
                    'D. By completely eliminating all windows and doors from the structure'
                ],
                'correct_answer' => 'A. By replicating the ventilation principles of termite mounds with cool night air',
                'explanation' => "Đoạn 3 giải thích Pearce đã mô phỏng cơ chế của tổ mối bằng cách dùng quạt hút không khí mát ban đêm vào các sàn rỗng ('replicated this mechanism with fans that draw cool night air into hollow floors').",
            ],
            // IELTS Writing Task 1
            [
                'skill' => 'writing',
                'difficulty' => $level,
                'question_type' => 'essay_writing',
                'part_name' => 'IELTS Writing Task 1',
                'question_text' => "WRITING TASK 1 (Academic)\nYou should spend about 20 minutes on this task.\n\nThe chart below shows energy consumption by fuel type in an industrialized country between 2000 and 2024.\n\nSummarise the information by selecting and reporting the main features, and make comparisons where relevant.\n\nWrite at least 150 words.",
                'options' => [],
                'correct_answer' => 'Academic Report (Minimum 150 words)',
                'explanation' => "Đề bài IELTS Writing Task 1 chuẩn: Học viên cần có Overview khái quát 2 xu hướng chính, phân tích các số liệu nổi bật và so sánh giữa các loại năng lượng trong khoảng thời gian 2000-2024 mà không đưa ý kiến cá nhân.",
            ],
            // IELTS Writing Task 2
            [
                'skill' => 'writing',
                'difficulty' => $level,
                'question_type' => 'essay_writing',
                'part_name' => 'IELTS Writing Task 2',
                'question_text' => "WRITING TASK 2\nYou should spend about 40 minutes on this task.\n\nSome people believe that university education should focus purely on providing theoretical academic knowledge, while others argue that its primary purpose should be preparing graduates for employment.\n\nDiscuss both views and give your own opinion.\n\nGive reasons for your answer and include any relevant examples from your own knowledge or experience.\nWrite at least 250 words.",
                'options' => [],
                'correct_answer' => 'Academic Discursive Essay (Minimum 250 words)',
                'explanation' => "Đề bài IELTS Writing Task 2 dạng 'Discuss both views and give your opinion': Yêu cầu phân tích cả 2 quan điểm (kiến thức lý thuyết hàn lâm vs đào tạo kỹ năng nghề nghiệp) và khẳng định rõ lập trường cá nhân xuyên suốt bài viết.",
            ],
            // IELTS Speaking Part 2
            [
                'skill' => 'speaking',
                'difficulty' => $level,
                'question_type' => 'pronunciation_speech',
                'part_name' => 'IELTS Speaking Part 2 (Cue Card)',
                'question_text' => "CANDIDATE CUE CARD\nDescribe an eco-friendly invention or practice that you find impressive.\nYou should say:\n- What it is\n- How you first learned about it\n- How it works\nAnd explain why you think it is beneficial for the environment.\n\nYou have 1 minute to prepare and should speak for 1 to 2 minutes.",
                'options' => [],
                'correct_answer' => 'Individual Long Turn (1-2 minutes speech)',
                'explanation' => "Thí sinh có 1 phút chuẩn bị dàn ý và nói liên tục từ 1-2 phút theo các câu hỏi gợi ý trong thẻ (Cue Card), thể hiện vốn từ vựng phong phú về môi trường và độ trôi chảy ngữ pháp.",
            ],
        ];
    }

    /**
     * Authentic TOEIC Procedural Bank (Part 5 Incomplete Sentences, Part 7 Reading Comprehension).
     */
    protected function getToeicProceduralBank(string $topic, string $level): array
    {
        $toeicPassage = "MEMORANDUM\nTO: All Regional Sales Representatives\nFROM: Clara Sterling, Chief Marketing Officer\nDATE: October 14, 2026\nSUBJECT: Annual Vendor Conference & Hotel Reservations\n\nPlease be advised that the Global Logistics Expo will take place at the Grand Regency Convention Center in Singapore from November 18 to 21. All representatives attending the event must submit their corporate travel reimbursement requests to HR by October 25.\n\nCompany-approved accommodations have been reserved at the Marina Suites. To secure the corporate discounted rate of $160 per night, bookings must be confirmed through our internal travel portal using promotion code 'GL2026'. Late reservations made after November 1 will not be eligible for corporate discount pricing.";

        return [
            // TOEIC Part 5 - Part of Speech
            [
                'skill' => 'reading',
                'difficulty' => $level,
                'question_type' => 'mcq',
                'part_name' => 'TOEIC Part 5: Incomplete Sentences',
                'question_text' => "Mr. Tanaka submitted the quarterly expenditure report ___ so that the board could review the figures before the audit.",
                'options' => ['A. prompt', 'B. promptly', 'C. promptness', 'D. prompter'],
                'correct_answer' => 'B. promptly',
                'explanation' => "Vị trí này cần một trạng từ (adverb) để bổ nghĩa cho động từ 'submitted': 'submitted the report promptly' (nộp báo cáo một cách nhanh chóng, kịp thời).",
            ],
            // TOEIC Part 5 - Conjunction
            [
                'skill' => 'reading',
                'difficulty' => $level,
                'question_type' => 'mcq',
                'part_name' => 'TOEIC Part 5: Incomplete Sentences',
                'question_text' => "___ the shipment was delayed due to inclement weather, the client received their orders within the revised deadline.",
                'options' => ['A. Although', 'B. Despite', 'C. Because of', 'D. Regardless'],
                'correct_answer' => 'A. Although',
                'explanation' => "Mệnh đề 'the shipment was delayed...' là một clause hoàn chỉnh có chủ ngữ và động từ, nên cần liên từ 'Although' biểu thị sự tương phản. 'Despite' và 'Because of' đi với cụm danh từ/V-ing.",
            ],
            // TOEIC Part 5 - Business Collocation
            [
                'skill' => 'reading',
                'difficulty' => $level,
                'question_type' => 'mcq',
                'part_name' => 'TOEIC Part 5: Incomplete Sentences',
                'question_text' => "Our legal department is currently ___ negotiations with the supplier to finalize the multi-year vendor contract.",
                'options' => ['A. conducting', 'B. manufacturing', 'C. behaving', 'D. performing'],
                'correct_answer' => 'A. conducting',
                'explanation' => "Cụm từ thương mại cố định: 'conduct negotiations' = tiến hành đàm phán/thương lượng hợp đồng.",
            ],
            // TOEIC Part 7 - Comprehension Detail
            [
                'skill' => 'reading',
                'difficulty' => $level,
                'question_type' => 'mcq',
                'part_name' => 'TOEIC Part 7: Reading Comprehension',
                'passage_title' => 'Internal Memorandum: Annual Vendor Conference',
                'passage_content' => $toeicPassage,
                'question_text' => "What is the deadline for sales representatives to submit travel reimbursement requests?",
                'options' => ['A. October 14', 'B. October 25', 'C. November 1', 'D. November 18'],
                'correct_answer' => 'B. October 25',
                'explanation' => "Văn bản nêu rõ: 'All representatives attending the event must submit their corporate travel reimbursement requests to HR by October 25.'",
            ],
            // TOEIC Part 7 - Comprehension Inference
            [
                'skill' => 'reading',
                'difficulty' => $level,
                'question_type' => 'mcq',
                'part_name' => 'TOEIC Part 7: Reading Comprehension',
                'passage_title' => 'Internal Memorandum: Annual Vendor Conference',
                'passage_content' => $toeicPassage,
                'question_text' => "What will happen if an employee reserves a hotel room after November 1?",
                'options' => [
                    'A. They will not receive the discounted corporate room rate',
                    'B. Their conference registration will be automatically canceled',
                    'C. They will be reassigned to a different corporate branch',
                    'D. They will have to pay a penalty directly to Singapore customs'
                ],
                'correct_answer' => 'A. They will not receive the discounted corporate room rate',
                'explanation' => "Đoạn cuối thông báo: 'Late reservations made after November 1 will not be eligible for corporate discount pricing.'",
            ],
        ];
    }

    /**
     * Authentic VSTEP Procedural Bank (Reading with passage, Writing Tasks, Speaking Solution Discussion).
     */
    protected function getVstepProceduralBank(string $topic, string $level): array
    {
        $vstepPassage = "Community-based ecotourism (CBET) has emerged as an effective model for sustainable rural development in northern Vietnam. In provinces such as Lao Cai, Ha Giang, and Hoa Binh, ethnic minority communities manage homestays, trekking tours, and traditional handicraft workshops.\n\nThe primary advantage of CBET is its capacity to generate direct financial benefits for indigenous households without depleting natural ecosystems. Traditional agricultural livelihoods, heavily susceptible to climate variations and price volatility, are supplemented by tourism revenue. Furthermore, young villagers who previously migrated to major metropolitan areas for low-skilled factory employment are now finding viable career opportunities as tour guides, hospitality managers, and cultural performers.\n\nNonetheless, ecotourism development poses acute socio-cultural dilemmas. Unregulated commercialization risks diluting authentic cultural traditions, turning sacred rituals into superficial commercial entertainment. Sociologists stress that local authorities must empower indigenous elders in decision-making processes to preserve cultural heritage alongside economic gains.";

        return [
            // VSTEP Reading 1
            [
                'skill' => 'reading',
                'difficulty' => $level,
                'question_type' => 'mcq',
                'part_name' => 'VSTEP Reading Comprehension Passage 1',
                'passage_title' => 'Community-Based Ecotourism in Northern Vietnam',
                'passage_content' => $vstepPassage,
                'question_text' => "What is cited as a major economic benefit of community-based ecotourism in paragraph 2?",
                'options' => [
                    'A. Providing alternative income that reduces rural-to-urban youth migration',
                    'B. Replacing all traditional agricultural practices with industrial tourism',
                    'C. Guaranteeing free international travel tickets for local villagers',
                    'D. Eliminating the need for government oversight and regulations'
                ],
                'correct_answer' => 'A. Providing alternative income that reduces rural-to-urban youth migration',
                'explanation' => "Đoạn 2 nêu rõ du lịch sinh thái tạo thêm nguồn thu nhập và giúp thanh niên trong làng có việc làm tại chỗ ('young villagers who previously migrated... are now finding viable career opportunities...').",
            ],
            // VSTEP Writing Task 1
            [
                'skill' => 'writing',
                'difficulty' => $level,
                'question_type' => 'essay_writing',
                'part_name' => 'VSTEP Writing Task 1 (Letter/Email)',
                'question_text' => "VSTEP WRITING TASK 1\nYou should spend about 20 minutes on this task.\n\nYou recently attended an English speaking workshop organized by your university, but you encountered several technical difficulties during the online session.\n\nWrite an email to the event coordinator. In your email:\n- Explain which workshop you attended and when\n- Describe the technical problems you encountered\n- Suggest two specific improvements for upcoming sessions.\n\nWrite at least 120 words. You do NOT need to write any address.",
                'options' => [],
                'correct_answer' => 'Formal/Semi-formal Email (Minimum 120 words)',
                'explanation' => "Đề bài VSTEP Writing Task 1 chuẩn: Viết thư phản hồi/góp ý có độ dài tối thiểu 120 từ, đáp ứng đầy đủ 3 yêu cầu trong đề và sử dụng văn phong lịch sự, trang trọng phù hợp với nhà trường.",
            ],
            // VSTEP Speaking Part 2
            [
                'skill' => 'speaking',
                'difficulty' => $level,
                'question_type' => 'pronunciation_speech',
                'part_name' => 'VSTEP Speaking Part 2: Solution Discussion',
                'question_text' => "VSTEP SPEAKING PART 2: SOLUTION DISCUSSION\nSituation: Your university English club wants to organize a summer activity to help members practice speaking skills. Three options are suggested:\n1. An English-speaking camping trip in an eco-resort\n2. A public debate contest on contemporary social issues\n3. An English storytelling workshop for local primary school pupils\n\nWhich option do you think is the best choice? Explain your decision and give reasons why you do not choose the other two options.\n(You have 1 minute to prepare and 3 minutes to present).",
                'options' => [],
                'correct_answer' => 'Oral Presentation (2-3 minutes)',
                'explanation' => "Dạng bài VSTEP Speaking Part 2 kinh điển (Thảo luận giải pháp): Thí sinh phải chọn 1 phương án tối ưu, nêu rõ các lý do thuyết phục và đồng thời phản biện, chỉ ra nhược điểm của 2 phương án còn lại.",
            ],
        ];
    }

    /**
     * General CEFR bank.
     */
    protected function getGeneralProceduralBank(string $topic, string $level): array
    {
        return $this->getThptProceduralBank($topic, $level);
    }

    /**
     * Create dynamically varied items to ensure reaching up to 50 items.
     */
    protected function createDynamicStandardItem(string $standard, string $topic, string $level, int $index): array
    {
        $contexts = [
            1 => [
                'stem' => "In an academic study on '{$topic}', researchers observed that systematic implementation of verified methodologies significantly improves outcome reliability.",
                'q' => "What is the primary conclusion of the study regarding '{$topic}'?",
                'opts' => ['A. Systematic implementation improves outcome reliability', 'B. Methodologies are inherently ineffective and confusing', 'C. Research outcomes are completely independent of preparation', 'D. Empirical verification should be disregarded entirely'],
                'ans' => 'A. Systematic implementation improves outcome reliability',
                'exp' => "Nghiên cứu khẳng định việc áp dụng phương pháp luận có hệ thống sẽ nâng cao đáng kể độ tin cậy của kết quả nghiên cứu.",
            ],
            2 => [
                'stem' => "[AUDIO] Lecturer: 'When addressing challenges in {$topic}, differentiating empirical evidence from anecdotal speculation is essential for sound policy.'",
                'q' => "According to the lecturer, what distinction is vital?",
                'opts' => ['A. Distinguishing empirical evidence from anecdotal speculation', 'B. Relying exclusively on personal unsubstantiated intuition', 'C. Ignoring factual observations during policy formulation', 'D. Assuming all anecdotal claims are scientifically valid'],
                'ans' => 'A. Distinguishing empirical evidence from anecdotal speculation',
                'exp' => "Giảng viên nhấn mạnh tầm quan trọng của việc phân biệt giữa bằng chứng thực nghiệm và suy đoán truyền miệng.",
            ],
            3 => [
                'stem' => "Select the most appropriate preposition: 'Stakeholders are committed ___ achieving long-term sustainability goals in {$topic}.'",
                'q' => "Which preposition correctly follows 'committed'?",
                'opts' => ['A. to', 'B. with', 'C. for', 'D. on'],
                'ans' => 'A. to',
                'exp' => "Cấu trúc cố định: 'be committed to + V-ing/Noun' = cam kết tận tâm thực hiện điều gì.",
            ],
            4 => [
                'stem' => "Identify the error in the following statement: 'Neither the director <u>nor</u> his assistants <u>were</u> aware of the policy change <u>until</u> it was announced <u>by</u> the board.'",
                'q' => "Is there an error in the sentence?",
                'opts' => ['A. No error', 'B. Change nor to or', 'C. Change were to was', 'D. Change until to while'],
                'ans' => 'A. No error',
                'exp' => "Quy tắc hòa hợp chủ vị với 'Neither A nor B': Động từ chia theo danh từ gần nhất 'his assistants' (số nhiều) nên dùng 'were' là hoàn toàn chính xác.",
            ],
        ];

        $varKey = (($index - 1) % count($contexts)) + 1;
        $tpl = $contexts[$varKey];
        $suffix = " (Mã câu #{$index})";

        return [
            'skill' => 'reading',
            'difficulty' => $level,
            'question_type' => 'mcq',
            'part_name' => "Phần kiểm tra tổng hợp {$standard}",
            'question_text' => $tpl['stem'] . "\n" . $tpl['q'] . $suffix,
            'options' => $tpl['opts'],
            'correct_answer' => $tpl['ans'],
            'explanation' => $tpl['exp'],
        ];
    }

    /**
     * Persist generated questions into the QuestionBank table with strict DB deduplication.
     *
     * @param array $questions
     * @return int Number of inserted questions
     */
    public function saveToQuestionBank(array $questions): int
    {
        $validTypes = ['mcq', 'multiple_select', 'fill_blank', 'word_ordering', 'matching', 'true_false', 'audio_listening', 'pronunciation_speech', 'essay_writing'];
        $validSkills = ['reading', 'listening', 'writing', 'speaking', 'grammar', 'vocabulary'];
        $inserted = 0;

        foreach ($questions as $q) {
            if (empty($q['question_text']) || (!isset($q['correct_answer']) && empty($q['options']))) {
                continue;
            }

            $questionText = trim($q['question_text']);

            // Deduplication check against existing question_text
            $alreadyExists = QuestionBank::where('question_text', $questionText)->exists();
            if ($alreadyExists) {
                continue;
            }

            $qType = in_array($q['question_type'] ?? 'mcq', $validTypes) ? $q['question_type'] : 'mcq';
            $skill = in_array($q['skill'] ?? '', $validSkills) ? $q['skill'] : 'reading';

            $meta = is_array($q['meta_data'] ?? null) ? $q['meta_data'] : [];
            if (!empty($q['passage_title']) && empty($meta['passage_title'])) {
                $meta['passage_title'] = $q['passage_title'];
            }
            if (!empty($q['passage_content']) && empty($meta['passage_content'])) {
                $meta['passage_content'] = $q['passage_content'];
                $meta['passage'] = $q['passage_content'];
            }
            if (!empty($q['audio_script']) && empty($meta['audio_script'])) {
                $meta['audio_script'] = $q['audio_script'];
            }
            if (!empty($q['part_name']) && empty($meta['part_name'])) {
                $meta['part_name'] = $q['part_name'];
            }

            QuestionBank::create([
                'skill' => $skill,
                'difficulty' => in_array($q['difficulty'] ?? '', ['A1', 'A2', 'B1', 'B2', 'C1', 'Mixed']) ? $q['difficulty'] : 'B1',
                'question_type' => $qType,
                'question_text' => $questionText,
                'options' => is_array($q['options'] ?? null) ? array_values($q['options']) : [],
                'correct_answer' => trim((string)($q['correct_answer'] ?? '')),
                'explanation' => $q['explanation'] ?? '',
                'meta_data' => $meta,
            ]);
            $inserted++;
        }

        return $inserted;
    }

    /**
     * Persist questions and immediately create an ExamSet (Đề thi chuẩn hóa)
     * so that students can take it directly via /practice/exam/{key}.
     *
     * @param array $questions List of generated question arrays
     * @param array $examData Form metadata (title, key, duration_minutes, reward_coins, etc.)
     * @param int $userId Creator user ID
     * @return ExamSet The newly created ExamSet
     */
    public function saveAsExamSet(array $questions, array $examData, int $userId): ExamSet
    {
        $validTypes = ['mcq', 'multiple_select', 'fill_blank', 'word_ordering', 'matching', 'true_false', 'audio_listening', 'pronunciation_speech', 'essay_writing'];
        $validSkills = ['reading', 'listening', 'writing', 'speaking', 'grammar', 'vocabulary'];

        $savedIds = [];
        $skillCounts = [];

        foreach ($questions as $q) {
            if (empty($q['question_text'])) {
                continue;
            }

            $questionText = trim($q['question_text']);
            $qType = in_array($q['question_type'] ?? 'mcq', $validTypes) ? $q['question_type'] : 'mcq';
            $skill = in_array($q['skill'] ?? '', $validSkills) ? $q['skill'] : 'reading';

            $meta = is_array($q['meta_data'] ?? null) ? $q['meta_data'] : [];
            if (!empty($q['passage_title'])) $meta['passage_title'] = $q['passage_title'];
            if (!empty($q['passage_content'])) {
                $meta['passage_content'] = $q['passage_content'];
                $meta['passage'] = $q['passage_content'];
            }
            if (!empty($q['audio_script'])) $meta['audio_script'] = $q['audio_script'];
            if (!empty($q['part_name'])) $meta['part_name'] = $q['part_name'];

            // Find existing or create fresh
            $existing = QuestionBank::where('question_text', $questionText)->first();
            if ($existing) {
                $savedIds[] = $existing->id;
                $skillCounts[$existing->skill] = ($skillCounts[$existing->skill] ?? 0) + 1;
            } else {
                $created = QuestionBank::create([
                    'skill' => $skill,
                    'difficulty' => in_array($q['difficulty'] ?? '', ['A1', 'A2', 'B1', 'B2', 'C1', 'Mixed']) ? $q['difficulty'] : 'B1',
                    'question_type' => $qType,
                    'question_text' => $questionText,
                    'options' => is_array($q['options'] ?? null) ? array_values($q['options']) : [],
                    'correct_answer' => trim((string)($q['correct_answer'] ?? '')),
                    'explanation' => $q['explanation'] ?? '',
                    'meta_data' => $meta,
                ]);
                $savedIds[] = $created->id;
                $skillCounts[$skill] = ($skillCounts[$skill] ?? 0) + 1;
            }
        }

        // Determine ExamSet skill
        $examSkill = 'full_mock';
        if (count($skillCounts) === 1) {
            $examSkill = array_key_first($skillCounts);
        } elseif (isset($examData['skill']) && in_array($examData['skill'], ['reading', 'listening', 'writing', 'speaking', 'full_mock'])) {
            $examSkill = $examData['skill'];
        }

        // Generate unique key
        $title = trim($examData['title'] ?? 'Đề Thi Chuẩn Hóa AI');
        $rawKey = !empty($examData['key']) ? Str::slug($examData['key'], '_') : Str::slug($title, '_');
        $uniqueKey = $rawKey . '_' . Str::lower(Str::random(5));

        $duration = max(5, min(180, (int)($examData['duration_minutes'] ?? 60)));
        $coins = max(0, min(500, (int)($examData['reward_coins'] ?? 30)));
        $difficulty = in_array($examData['difficulty'] ?? '', ['A1', 'A2', 'B1', 'B2', 'C1', 'Mixed']) ? $examData['difficulty'] : 'B1';

        $description = $examData['description'] ?? "Đề thi trắc nghiệm và đánh giá năng lực tiếng Anh theo chuẩn khảo thí quốc tế được biên soạn tự động bởi ESL AI Engine.";

        return ExamSet::create([
            'title' => $title,
            'key' => $uniqueKey,
            'skill' => $examSkill,
            'difficulty' => $difficulty,
            'question_count' => count($savedIds),
            'duration_minutes' => $duration,
            'reward_coins' => $coins,
            'description' => $description,
            'sections' => !empty($skillCounts) ? $skillCounts : null,
            'question_ids' => $savedIds,
            'is_published' => (bool)($examData['is_published'] ?? true),
            'created_by' => $userId,
        ]);
    }
}
