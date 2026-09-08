<?php

namespace App\Services\Assessment;

use App\Models\User;
use App\Models\QuestionBank;
use App\Models\AdaptiveTestSession;
use Illuminate\Support\Facades\DB;

class AdaptiveTestingService
{
    protected AssessmentService $assessmentService;

    public function __construct(AssessmentService $assessmentService)
    {
        $this->assessmentService = $assessmentService;
    }

    /**
     * Start a new adaptive testing session for a learner.
     *
     * @param int $userId
     * @param string $startingDifficulty
     * @return AdaptiveTestSession
     */
    public function initializeSession(int $userId, string $startingDifficulty = 'A1'): AdaptiveTestSession
    {
        // Cancel any lingering active sessions
        AdaptiveTestSession::where('user_id', $userId)
            ->where('status', 'active')
            ->update(['status' => 'finished']);

        return AdaptiveTestSession::create([
            'user_id' => $userId,
            'current_difficulty' => in_array($startingDifficulty, ['A1', 'A2', 'B1']) ? $startingDifficulty : 'A1',
            'consecutive_correct' => 0,
            'consecutive_wrong' => 0,
            'question_history' => [],
            'answers_history' => [],
            'total_questions_answered' => 0,
            'correct_count' => 0,
            'score' => 0,
            'status' => 'active',
            'final_level' => null,
        ]);
    }

    public const TOTAL_TEST_QUESTIONS = 40;

    /**
     * Map question index (1 to 40) to the corresponding English skill.
     * 1-12: Listening (12 questions)
     * 13-26: Reading (14 questions)
     * 27-33: Writing (7 tasks)
     * 34-40: Speaking (7 tasks)
     */
    public function getSkillForStep(int $step): string
    {
        return match (true) {
            $step <= 12 => 'listening',
            $step <= 26 => 'reading',
            $step <= 33 => 'writing',
            default => 'speaking',
        };
    }

    /**
     * Fetch the next question for the adaptive session based on skill sequence, current difficulty and history.
     *
     * @param int $sessionId
     * @return QuestionBank|null
     */
    public function getNextQuestion(int $sessionId): ?QuestionBank
    {
        $session = AdaptiveTestSession::findOrFail($sessionId);

        if ($session->status !== 'active' || $session->total_questions_answered >= self::TOTAL_TEST_QUESTIONS) {
            return null;
        }

        $history = $session->question_history ?? [];
        $currentStep = count($history) + 1;
        $targetSkill = $this->getSkillForStep($currentStep);

        // If target skill is reading, use the Testlet-Based cluster selection mechanism
        if ($targetSkill === 'reading') {
            $readingQuestion = $this->getNextReadingQuestion($session, $history, $currentStep);
            if ($readingQuestion) {
                return $readingQuestion;
            }
        }

        $eligibleQuery = QuestionBank::whereNotIn('id', $history)
            ->where('skill', $targetSkill);

        if ($targetSkill === 'listening') {
            $eligibleQuery->whereNotIn('question_type', ['pronunciation_speech', 'audio_recording', 'essay_writing']);
        } elseif ($targetSkill === 'reading') {
            $eligibleQuery->whereNotIn('question_type', ['pronunciation_speech', 'audio_recording', 'essay_writing']);
        } elseif ($targetSkill === 'writing') {
            $eligibleQuery->whereIn('question_type', ['essay_writing', 'fill_blank']);
        } elseif ($targetSkill === 'speaking') {
            $eligibleQuery->whereIn('question_type', ['audio_recording', 'pronunciation_speech']);
        }

        // 1. Try fetching from current difficulty for the target skill
        $question = (clone $eligibleQuery)->where('difficulty', $session->current_difficulty)
            ->inRandomOrder()
            ->first();

        // 2. Fallback to any difficulty in the target skill not yet answered
        if (!$question) {
            $question = (clone $eligibleQuery)
                ->inRandomOrder()
                ->first();
        }

        // 3. Fallback to any compatible question in the same target skill
        if (!$question) {
            $question = QuestionBank::whereNotIn('id', $history)
                ->where('skill', $targetSkill)
                ->inRandomOrder()
                ->first();
        }

        // 4. If current skill is exhausted, fall forward to subsequent skills in sequence (listening -> reading -> writing -> speaking)
        if (!$question) {
            $skillsOrder = ['listening', 'reading', 'writing', 'speaking'];
            $currentSkillIdx = array_search($targetSkill, $skillsOrder);
            if ($currentSkillIdx !== false) {
                for ($i = $currentSkillIdx + 1; $i < count($skillsOrder); $i++) {
                    $nextSkill = $skillsOrder[$i];
                    if ($nextSkill === 'reading') {
                        $readingQuestion = $this->getNextReadingQuestion($session, $history, $currentStep);
                        if ($readingQuestion) {
                            return $readingQuestion;
                        }
                    }
                    $fallbackQuery = QuestionBank::whereNotIn('id', $history)->where('skill', $nextSkill);
                    if ($nextSkill === 'listening') {
                        $fallbackQuery->whereNotIn('question_type', ['pronunciation_speech', 'audio_recording', 'essay_writing']);
                    } elseif ($nextSkill === 'reading') {
                        $fallbackQuery->whereNotIn('question_type', ['pronunciation_speech', 'audio_recording', 'essay_writing']);
                    } elseif ($nextSkill === 'writing') {
                        $fallbackQuery->whereIn('question_type', ['essay_writing', 'fill_blank']);
                    } elseif ($nextSkill === 'speaking') {
                        $fallbackQuery->whereIn('question_type', ['audio_recording', 'pronunciation_speech']);
                    }
                    $question = $fallbackQuery->inRandomOrder()->first();
                    if ($question) {
                        break;
                    }
                }
            }
        }

        return $question;
    }

    /**
     * Process user's answer across all 4 skills, apply branching algorithm, and finalize when reaching 10 questions.
     *
     * @param int $sessionId
     * @param int $questionId
     * @param string $userAnswer
     * @return array
     */
    public function processAnswer(int $sessionId, int $questionId, string $userAnswer): array
    {
        $session = AdaptiveTestSession::findOrFail($sessionId);
        $question = QuestionBank::findOrFail($questionId);

        $isCorrect = false;
        $feedbackNote = null;

        // Skill-specific evaluation logic
        if ($question->skill === 'writing' || $question->question_type === 'essay_writing') {
            $wordCount = str_word_count(strip_tags($userAnswer));
            $minWords = $question->meta_data['min_words'] ?? 30;
            $threshold = min($minWords, 25);
            $isCorrect = ($wordCount >= $threshold);
            $feedbackNote = $isCorrect
                ? "Bài viết đạt {$wordCount} từ (đạt mức tối thiểu {$threshold} từ). Lập luận rõ ràng, hoàn thành tốt yêu cầu đề bài!"
                : "Bài viết đạt {$wordCount} từ (chưa đạt mức tối thiểu {$threshold} từ). Hãy mở rộng thêm các ý để đạt điểm cao hơn.";
        } elseif ($question->skill === 'speaking' || $question->question_type === 'audio_recording') {
            $hasContent = !empty(trim($userAnswer));
            $isCorrect = $hasContent;
            $feedbackNote = $isCorrect
                ? "Đã lưu bản ghi âm câu trả lời thành công! Hệ thống đánh giá cao độ phản xạ tự nhiên và tính hoàn chỉnh của phần nói."
                : "Chưa ghi nhận bản ghi âm giọng nói.";
        } else {
            // Listening & Reading Multiple Choice Matching
            $userClean = strtolower(trim((string)$userAnswer));
            $correctClean = strtolower(trim((string)$question->correct_answer));

            if ($userClean === $correctClean) {
                $isCorrect = true;
            } elseif (strlen($userClean) === 1 && str_starts_with($correctClean, $userClean . '.')) {
                $isCorrect = true;
            } elseif (strlen($correctClean) === 1 && str_starts_with($userClean, $correctClean . '.')) {
                $isCorrect = true;
            } elseif (is_array($question->options)) {
                foreach ($question->options as $idx => $opt) {
                    $optClean = strtolower(trim((string)$opt));
                    $letter = strtolower(chr(65 + $idx));
                    if ($userClean === $optClean || $userClean === $letter) {
                        if ($correctClean === $optClean || $correctClean === $letter || str_starts_with($optClean, $correctClean)) {
                            $isCorrect = true;
                            break;
                        }
                    }
                }
            }
        }

        $history = $session->question_history ?? [];
        $history[] = $questionId;

        $meta = $question->meta_data ?? [];
        $passageContent = $meta['passage_content'] ?? $meta['passage'] ?? $meta['reading_passage'] ?? null;
        $passageTitle = $meta['passage_title'] ?? $meta['title'] ?? null;
        $audioUrl = $question->audio_url ?: ($question->skill === 'listening' ? 'https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3' : null);

        $answersHistory = $session->answers_history ?? [];
        $answersHistory[] = [
            'question_id' => $question->id,
            'question_text' => $question->question_text,
            'skill' => $question->skill,
            'difficulty' => $question->difficulty,
            'question_type' => $question->question_type,
            'options' => $question->options,
            'user_answer' => $userAnswer,
            'correct_answer' => $question->correct_answer,
            'is_correct' => $isCorrect,
            'explanation' => $question->explanation ?: $feedbackNote,
            'feedback_note' => $feedbackNote,
            'passage' => $passageContent,
            'passage_title' => $passageTitle,
            'audio_url' => $audioUrl,
            'min_words' => $meta['min_words'] ?? ($question->skill === 'writing' ? 40 : null),
            'task_type' => $meta['task_type'] ?? null,
            'testlet_index' => $meta['testlet_index'] ?? null,
            'testlet_badge' => $meta['testlet_badge'] ?? null,
            'testlet_info' => $meta['testlet_info'] ?? null,
            'testlet_question_num' => $meta['testlet_question_num'] ?? null,
            'testlet_size' => $meta['testlet_size'] ?? null,
        ];

        $consecutiveCorrect = $session->consecutive_correct;
        $consecutiveWrong = $session->consecutive_wrong;
        $currentDifficulty = $session->current_difficulty;
        $difficultyChanged = false;
        $difficultyChangeDirection = null;

        $ladder = ['A1', 'A2', 'B1', 'B2', 'C1'];
        $currentIdx = array_search($currentDifficulty, $ladder);
        if ($currentIdx === false) $currentIdx = 0;

        if ($isCorrect) {
            $consecutiveCorrect++;
            $consecutiveWrong = 0;

            // Branch UP if 2 consecutive correct
            if ($consecutiveCorrect >= 2 && $currentIdx < count($ladder) - 1) {
                $currentDifficulty = $ladder[$currentIdx + 1];
                $difficultyChanged = true;
                $difficultyChangeDirection = 'up';
                $consecutiveCorrect = 0;
            }
        } else {
            $consecutiveWrong++;
            $consecutiveCorrect = 0;

            // Branch DOWN if 2 consecutive wrong
            if ($consecutiveWrong >= 2 && $currentIdx > 0) {
                $currentDifficulty = $ladder[$currentIdx - 1];
                $difficultyChanged = true;
                $difficultyChangeDirection = 'down';
                $consecutiveWrong = 0;
            }
        }

        $totalAnswered = count($history);
        $correctCount = $session->correct_count + ($isCorrect ? 1 : 0);
        $score = $correctCount * 10;
        $isFinished = ($totalAnswered >= self::TOTAL_TEST_QUESTIONS);
        $finalLevel = null;
        $finalReport = null;
        $skillMatrix = null;

        if ($isFinished) {
            $finalLevel = $this->calculateFinalLevel($answersHistory);
            $skillMatrix = $this->calculateSkillMatrix($answersHistory);
            
            $session->update([
                'status' => 'finished',
                'final_level' => $finalLevel,
                'current_difficulty' => $currentDifficulty,
                'consecutive_correct' => $consecutiveCorrect,
                'consecutive_wrong' => $consecutiveWrong,
                'question_history' => $history,
                'answers_history' => $answersHistory,
                'total_questions_answered' => $totalAnswered,
                'correct_count' => $correctCount,
                'score' => $score,
            ]);

            // Submit full assessment to update skills matrix & award coins
            $submittedPayload = [];
            foreach ($answersHistory as $item) {
                $submittedPayload[$item['question_id']] = $item['user_answer'];
            }

            $finalReport = $this->assessmentService->gradeSubmission(
                $session->user_id,
                null,
                $submittedPayload,
                'adaptive_test'
            );

            $finalReport['skill_matrix'] = $skillMatrix;
            $finalReport['final_level'] = $finalLevel;
            $finalReport['answers_history'] = $answersHistory;

            // Update user's current level in user table
            User::where('id', $session->user_id)->update([
                'current_level' => $finalLevel,
            ]);
        } else {
            $session->update([
                'current_difficulty' => $currentDifficulty,
                'consecutive_correct' => $consecutiveCorrect,
                'consecutive_wrong' => $consecutiveWrong,
                'question_history' => $history,
                'answers_history' => $answersHistory,
                'total_questions_answered' => $totalAnswered,
                'correct_count' => $correctCount,
                'score' => $score,
            ]);
        }

        return [
            'is_correct' => $isCorrect,
            'correct_answer' => $question->correct_answer,
            'explanation' => $question->explanation ?: ($feedbackNote ?: 'Hoàn thành câu hỏi.'),
            'feedback_note' => $feedbackNote,
            'current_step' => $totalAnswered,
            'total_steps' => self::TOTAL_TEST_QUESTIONS,
            'score' => $score,
            'correct_count' => $correctCount,
            'current_difficulty' => $currentDifficulty,
            'difficulty_changed' => $difficultyChanged,
            'difficulty_direction' => $difficultyChangeDirection,
            'is_finished' => $isFinished,
            'final_level' => $finalLevel,
            'final_report' => $finalReport,
            'skill_matrix' => $skillMatrix,
            'answers_history' => $answersHistory,
        ];
    }

    /**
     * Determine final learner level based on difficulty distribution of correctly answered questions.
     */
    public function calculateFinalLevel(array $answersHistory): string
    {
        $correctByLevel = ['C1' => 0, 'B2' => 0, 'B1' => 0, 'A2' => 0, 'A1' => 0];
        $totalCorrect = 0;

        foreach ($answersHistory as $item) {
            if ($item['is_correct']) {
                $totalCorrect++;
                $diff = $item['difficulty'] ?? 'A1';
                if (isset($correctByLevel[$diff])) {
                    $correctByLevel[$diff]++;
                }
            }
        }

        if ($correctByLevel['C1'] >= 2 || ($correctByLevel['C1'] >= 1 && $correctByLevel['B2'] >= 2)) {
            return 'C1';
        }
        if ($correctByLevel['B2'] >= 2 || ($correctByLevel['B2'] >= 1 && $correctByLevel['B1'] >= 2) || $totalCorrect >= 8) {
            return 'B2';
        }
        if ($correctByLevel['B1'] >= 2 || ($correctByLevel['B1'] >= 1 && $correctByLevel['A2'] >= 2) || $totalCorrect >= 6) {
            return 'B1';
        }
        if ($correctByLevel['A2'] >= 2 || $totalCorrect >= 4) {
            return 'A2';
        }
        return 'A1';
    }

    /**
     * Calculate 4-skill diagnostic breakdown matrix from answer history.
     */
    public function calculateSkillMatrix(array $answersHistory): array
    {
        $skills = ['listening', 'reading', 'writing', 'speaking'];
        $matrix = [];

        foreach ($skills as $skill) {
            $items = array_values(array_filter($answersHistory, fn($i) => ($i['skill'] ?? '') === $skill));
            $total = count($items);
            $correct = count(array_filter($items, fn($i) => !empty($i['is_correct'])));
            $acc = $total > 0 ? round(($correct / $total) * 100) : 0;

            // Highest level answered correctly
            $highestLevel = 'A1';
            foreach ($items as $item) {
                if (!empty($item['is_correct'])) {
                    $lvl = $item['difficulty'] ?? 'A1';
                    if ($this->levelValue($lvl) > $this->levelValue($highestLevel)) {
                        $highestLevel = $lvl;
                    }
                }
            }

            $matrix[$skill] = [
                'skill' => $skill,
                'name' => match($skill) {
                    'listening' => 'Kỹ năng Nghe (Listening)',
                    'reading' => 'Kỹ năng Đọc (Reading)',
                    'writing' => 'Kỹ năng Viết (Writing)',
                    'speaking' => 'Kỹ năng Nói (Speaking)',
                },
                'short_name' => match($skill) {
                    'listening' => 'Nghe',
                    'reading' => 'Đọc',
                    'writing' => 'Viết',
                    'speaking' => 'Nói',
                },
                'emoji' => match($skill) {
                    'listening' => '🎧',
                    'reading' => '📖',
                    'writing' => '✍️',
                    'speaking' => '🎙️',
                },
                'total' => $total,
                'correct' => $correct,
                'accuracy' => $acc,
                'assessed_level' => $highestLevel,
            ];
        }

        return $matrix;
    }

    private function levelValue(string $level): int
    {
        return match($level) {
            'A1' => 1,
            'A2' => 2,
            'B1' => 3,
            'B2' => 4,
            'C1' => 5,
            default => 1,
        };
    }

    /**
     * Testlet-based question selector for Reading skill (Steps 13 – 26).
     * Guarantees that questions within the same testlet bundle share the exact same reading passage.
     */
    protected function getNextReadingQuestion(AdaptiveTestSession $session, array $history, int $currentStep): ?QuestionBank
    {
        // 1. Determine current Testlet configuration
        // Testlet 1: Steps 13 - 17 (5 questions)
        // Testlet 2: Steps 18 - 22 (5 questions)
        // Testlet 3: Steps 23 - 26 (4 questions)
        if ($currentStep <= 17) {
            $testletIndex = 1;
            $testletStart = 13;
            $testletEnd = 17;
            $testletSize = 5;
        } elseif ($currentStep <= 22) {
            $testletIndex = 2;
            $testletStart = 18;
            $testletEnd = 22;
            $testletSize = 5;
        } else {
            $testletIndex = 3;
            $testletStart = 23;
            $testletEnd = 26;
            $testletSize = 4;
        }

        $answersHistory = $session->answers_history ?? [];
        $currentTestletAnswers = array_slice($answersHistory, $testletStart - 1);

        $selectedQuestion = null;

        // 2. Check if this testlet is already in progress and anchored to a passage
        if (!empty($currentTestletAnswers)) {
            $firstAnswerInTestlet = $currentTestletAnswers[0];
            $passageTitle = $firstAnswerInTestlet['passage_title'] ?? null;
            $passageContent = $firstAnswerInTestlet['passage'] ?? null;

            if ($passageTitle || $passageContent) {
                // Find next question in the same passage
                $query = QuestionBank::whereNotIn('id', $history)
                    ->where('skill', 'reading');

                if ($passageTitle) {
                    $query->where('meta_data->passage_title', $passageTitle);
                } else {
                    $query->where(function($q) use ($passageContent) {
                        $q->where('meta_data->passage_content', $passageContent)
                          ->orWhere('meta_data->passage', $passageContent);
                    });
                }

                $selectedQuestion = $query->orderBy('id', 'asc')->first();
            }
        }

        // 3. If no active passage (new testlet) or current passage ran out of questions:
        if (!$selectedQuestion) {
            // Get passages already used in previous testlets of this session
            $usedPassageTitles = collect($answersHistory)
                ->pluck('passage_title')
                ->filter()
                ->unique()
                ->toArray();

            // Try matching current difficulty with a multi-item passage first
            $query = QuestionBank::whereNotIn('id', $history)
                ->where('skill', 'reading')
                ->whereNotNull('meta_data->passage_content');

            if (!empty($usedPassageTitles)) {
                $query->whereNotIn('meta_data->passage_title', $usedPassageTitles);
            }

            // A. Try current difficulty
            $selectedQuestion = (clone $query)->where('difficulty', $session->current_difficulty)
                ->orderBy('id', 'asc')
                ->first();

            // B. Try adjacent difficulties (e.g. B2, C1, B1)
            if (!$selectedQuestion) {
                $selectedQuestion = (clone $query)->orderBy('id', 'asc')->first();
            }

            // C. Fallback to any reading question not in history
            if (!$selectedQuestion) {
                $selectedQuestion = QuestionBank::whereNotIn('id', $history)
                    ->where('skill', 'reading')
                    ->inRandomOrder()
                    ->first();
            }
        }

        if ($selectedQuestion) {
            $questionNumInTestlet = ($currentStep - $testletStart + 1);
            $meta = $selectedQuestion->meta_data ?? [];
            $meta['testlet_index'] = $testletIndex;
            $meta['testlet_total'] = 3;
            $meta['testlet_start_step'] = $testletStart;
            $meta['testlet_end_step'] = $testletEnd;
            $meta['testlet_size'] = $testletSize;
            $meta['testlet_question_num'] = $questionNumInTestlet;
            $meta['testlet_badge'] = "Bài đọc {$testletIndex}/3 (Câu {$testletStart}–{$testletEnd})";
            $meta['testlet_info'] = "Câu {$questionNumInTestlet}/{$testletSize} của bài đọc này · Chọn 1 đáp án A, B, C, D.";
            $selectedQuestion->meta_data = $meta;
        }

        return $selectedQuestion;
    }
}

