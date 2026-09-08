<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class GeminiApiService
{
    protected ?string $apiKey;
    protected string $baseUrl;
    protected string $modelFlash;
    protected string $modelPro;
    protected float $temperatureFlash;
    protected float $temperaturePro;
    protected int $maxRpm;
    protected int $timeoutFlash;
    protected int $timeoutPro;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
        $this->baseUrl = config('services.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta/models');
        $this->modelFlash = config('services.gemini.model_flash', 'gemini-1.5-flash');
        $this->modelPro = config('services.gemini.model_pro', 'gemini-1.5-pro');
        $this->temperatureFlash = (float) config('services.gemini.temperature_flash', 0.7);
        $this->temperaturePro = (float) config('services.gemini.temperature_pro', 0.3);
        $this->maxRpm = (int) config('services.gemini.max_rpm', 15);
        $this->timeoutFlash = (int) config('services.gemini.timeout_flash', 30);
        $this->timeoutPro = (int) config('services.gemini.timeout_pro', 60);
    }

    /**
     * Check if the Gemini API is properly configured with a valid key.
     */
    public function isConfigured(): bool
    {
        if (empty($this->apiKey)) {
            return false;
        }
        if ($this->apiKey === 'demo_key' || str_contains($this->apiKey, 'DemoSampleKey') || str_contains($this->apiKey, 'SampleKey')) {
            return false;
        }
        return strlen($this->apiKey) > 15;
    }

    /**
     * Test API connection by making a lightweight request.
     *
     * @return array{success: bool, message: string, model: string}
     */
    public function testConnection(): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'API Key chưa được cấu hình hoặc không hợp lệ.',
                'model' => $this->modelFlash,
            ];
        }

        try {
            $endpoint = "{$this->baseUrl}/{$this->modelFlash}:generateContent?key={$this->apiKey}";

            $response = Http::timeout(10)
                ->asJson()
                ->post($endpoint, [
                    'contents' => [
                        ['role' => 'user', 'parts' => [['text' => 'Say "ESL AI Connected" in exactly 3 words.']]]
                    ],
                    'generationConfig' => ['maxOutputTokens' => 20],
                ]);

            if ($response->successful()) {
                $text = $response->json('candidates.0.content.parts.0.text', '');
                return [
                    'success' => true,
                    'message' => "Kết nối thành công! Response: \"{$text}\"",
                    'model' => $this->modelFlash,
                ];
            }

            $errorMsg = $response->json('error.message', 'Unknown error');
            return [
                'success' => false,
                'message' => "API trả về lỗi ({$response->status()}): {$errorMsg}",
                'model' => $this->modelFlash,
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Không thể kết nối: ' . $e->getMessage(),
                'model' => $this->modelFlash,
            ];
        }
    }

    /**
     * Generate content with Google Gemini API using structured JSON or text output.
     *
     * @param string $model 'flash' or 'pro' or exact model name
     * @param string $systemPrompt
     * @param string $userPrompt
     * @param array|null $jsonSchema  If provided, forces JSON output with this schema
     * @param float|null $temperature Override default temperature (0.0 - 2.0)
     * @return array|string
     */
    public function generateContent(
        string $model = 'flash',
        string $systemPrompt = '',
        string $userPrompt = '',
        ?array $jsonSchema = null,
        ?float $temperature = null
    ): array|string {
        $modelName = match ($model) {
            'pro' => $this->modelPro,
            'flash' => $this->modelFlash,
            default => $model,
        };

        $isProModel = ($model === 'pro' || str_contains($modelName, 'pro'));

        // Fallback / mock mode if API key is not configured or in unit testing
        if (!$this->isConfigured()) {
            return $this->generateFallbackResponse($systemPrompt, $userPrompt, $jsonSchema);
        }

        // Rate-limit check (free tier: 15 RPM)
        if (!$this->checkRateLimit()) {
            Log::warning('Gemini API rate limit reached, using fallback response.');
            return $this->generateFallbackResponse($systemPrompt, $userPrompt, $jsonSchema);
        }

        $endpoint = "{$this->baseUrl}/{$modelName}:generateContent?key={$this->apiKey}";
        $timeout = $isProModel ? $this->timeoutPro : $this->timeoutFlash;

        // Build payload
        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $userPrompt]
                    ]
                ]
            ],
            'safetySettings' => $this->getSafetySettings(),
        ];

        if (!empty($systemPrompt)) {
            $payload['systemInstruction'] = [
                'parts' => [
                    ['text' => $systemPrompt]
                ]
            ];
        }

        // Generation config
        $generationConfig = [
            'temperature' => $temperature ?? ($isProModel ? $this->temperaturePro : $this->temperatureFlash),
        ];

        if ($jsonSchema !== null) {
            $generationConfig['responseMimeType'] = 'application/json';
            $generationConfig['responseSchema'] = $jsonSchema;
        }

        $payload['generationConfig'] = $generationConfig;

        $startTime = microtime(true);

        try {
            $response = Http::timeout($timeout)
                ->retry(3, 500, function ($exception, $request) {
                    // Retry on connection errors, rate-limits (429), and server errors (500, 503)
                    return $exception instanceof \Illuminate\Http\Client\ConnectionException
                        || (method_exists($exception, 'response') && $exception->response && in_array($exception->response->status(), [429, 500, 503]));
                }, throw: false)
                ->asJson()
                ->post($endpoint, $payload);

            $elapsed = round((microtime(true) - $startTime) * 1000);

            if ($response->successful()) {
                $candidates = $response->json('candidates', []);
                $text = $candidates[0]['content']['parts'][0]['text'] ?? '';
                $tokenUsage = $response->json('usageMetadata', []);

                Log::info('Gemini API success', [
                    'model' => $modelName,
                    'elapsed_ms' => $elapsed,
                    'prompt_tokens' => $tokenUsage['promptTokenCount'] ?? 0,
                    'response_tokens' => $tokenUsage['candidatesTokenCount'] ?? 0,
                ]);

                // Increment rate-limit counter
                $this->incrementRateLimit();

                if ($jsonSchema !== null) {
                    $decoded = json_decode($text, true);
                    return is_array($decoded) ? $decoded : ['raw' => $text];
                }

                return $text;
            }

            $errorBody = $response->json('error', []);
            Log::warning('Gemini API request failed', [
                'model' => $modelName,
                'status' => $response->status(),
                'error' => $errorBody['message'] ?? $response->body(),
                'elapsed_ms' => $elapsed,
            ]);

            return $this->generateFallbackResponse($systemPrompt, $userPrompt, $jsonSchema);

        } catch (Exception $e) {
            $elapsed = round((microtime(true) - $startTime) * 1000);
            Log::error('Gemini API Exception', [
                'model' => $modelName,
                'error' => $e->getMessage(),
                'elapsed_ms' => $elapsed,
            ]);
            return $this->generateFallbackResponse($systemPrompt, $userPrompt, $jsonSchema);
        }
    }

    /**
     * Safety settings to prevent educational content from being blocked.
     */
    protected function getSafetySettings(): array
    {
        return [
            ['category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => 'BLOCK_NONE'],
            ['category' => 'HARM_CATEGORY_HATE_SPEECH', 'threshold' => 'BLOCK_NONE'],
            ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_NONE'],
            ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_NONE'],
        ];
    }

    /**
     * Check if we're within the rate limit (free tier: 15 RPM).
     */
    protected function checkRateLimit(): bool
    {
        $key = 'gemini_rpm_' . now()->format('Y-m-d_H-i');
        $current = (int) Cache::get($key, 0);
        return $current < $this->maxRpm;
    }

    /**
     * Increment the rate limit counter for the current minute.
     */
    protected function incrementRateLimit(): void
    {
        $key = 'gemini_rpm_' . now()->format('Y-m-d_H-i');
        $current = (int) Cache::get($key, 0);
        Cache::put($key, $current + 1, 120); // TTL 2 minutes
    }

    /**
     * Fallback heuristic generator when API key is missing or upstream is unavailable.
     */
    protected function generateFallbackResponse(string $systemPrompt, string $userPrompt, ?array $jsonSchema): array|string
    {
        // 1. AI Writing schema
        if ($jsonSchema && isset($jsonSchema['properties']['grammar_errors'])) {
            $wordCount = str_word_count($userPrompt);
            $hasHeSheError = (bool) preg_match('/\b(he|she|it)\s+(go|like|have|want)\b/i', $userPrompt);
            $hasPastError = (bool) preg_match('/\byesterday\s+.*\b(go|see|buy)\b/i', $userPrompt);

            $grammarErrors = [];
            if ($hasHeSheError) {
                $grammarErrors[] = [
                    'original' => 'he/she + động từ nguyên mẫu',
                    'fix' => 'Thêm đuôi -s/es cho chủ ngữ ngôi thứ ba số ít (he/she/it)',
                    'reason' => 'Quy tắc chia thì Hiện tại đơn trong tiếng Anh.'
                ];
            }
            if ($hasPastError) {
                $grammarErrors[] = [
                    'original' => 'yesterday + verb nguyên mẫu',
                    'fix' => 'Chuyển sang dạng quá khứ (V2/ed)',
                    'reason' => 'Trạng từ yesterday chỉ hành động đã xảy ra trong quá khứ.'
                ];
            }
            if (empty($grammarErrors)) {
                $grammarErrors[] = [
                    'original' => 'Một số cụm câu chưa tự nhiên',
                    'fix' => 'Nên dùng liên từ ghép (Moreover, In addition, However) để kết nối ý mạch lạc hơn.',
                    'reason' => 'Tăng tính liên kết và độ trôi chảy của bài luận.'
                ];
            }

            $score = max(65, min(95, 70 + ($wordCount > 30 ? 15 : 5) - (count($grammarErrors) * 5)));
            $level = $score >= 85 ? 'B2' : ($score >= 70 ? 'B1' : 'A2');

            return [
                'overall_score' => $score,
                'cefr_level' => $level,
                'coherence_score' => max(60, $score - 5),
                'structure_feedback' => 'Bài viết có bố cục khá rõ ràng. Nên chia thành 3 đoạn (Mở bài, Thân bài, Kết luận) để tăng điểm Coherence & Cohesion.',
                'grammar_errors' => $grammarErrors,
                'vocabulary_improvements' => [
                    ['original' => 'good', 'suggestion' => 'exceptional / outstanding', 'explanation' => 'Nâng cấp band từ vựng mô tả chất lượng tích cực.'],
                    ['original' => 'very happy', 'suggestion' => 'delighted / thrilled', 'explanation' => 'Diễn đạt cảm xúc tinh tế và chuyên nghiệp hơn.'],
                    ['original' => 'important', 'suggestion' => 'crucial / paramount', 'explanation' => 'Từ vựng học thuật chỉ tầm quan trọng cao.']
                ],
                'model_essay' => "Bài viết của bạn diễn đạt khá rõ ý. Đoạn văn hoàn thiện hơn: \"" . trim($userPrompt) . " Overall, continuous language practice and vocabulary enhancement will surely elevate your proficiency to the next CEFR level.\""
            ];
        }

        // 2. AI Speaking schema
        if ($jsonSchema && isset($jsonSchema['properties']['mispronounced_words'])) {
            return [
                'pronunciation_score' => 88,
                'fluency_score' => 85,
                'intonation_score' => 82,
                'mispronounced_words' => [
                    [
                        'word' => 'comfortable',
                        'expected_ipa' => '/ˈkʌmftəbl/',
                        'actual_ipa' => '/kəmˈfɔːrtəbl/',
                        'tip' => 'Lưu ý từ này chỉ có 3 âm tiết, nuốt âm "or": /ˈkʌmf-tə-bl/.'
                    ],
                    [
                        'word' => 'schedule',
                        'expected_ipa' => '/ˈskedʒuːl/',
                        'actual_ipa' => '/ˈʃedjuːl/',
                        'tip' => 'Phát âm âm đầu là /sk/ theo chuẩn Anh - Mỹ.'
                    ]
                ],
                'feedback' => 'Bạn phát âm rất rõ ràng, ngữ điệu tự nhiên. Hãy chú ý các âm câm và trọng âm của từ đa âm tiết để nói giống người bản xứ hơn!'
            ];
        }

        // 3. AI Exercise Generator schema
        if ($jsonSchema && isset($jsonSchema['properties']['questions'])) {
            return [
                'questions' => [
                    [
                        'skill' => 'vocabulary',
                        'difficulty' => 'B1',
                        'question_type' => 'mcq',
                        'question_text' => 'Choose the most appropriate word: "The company offers a very ___ salary package with performance bonuses."',
                        'options' => ['competitive', 'competitor', 'compete', 'competition'],
                        'correct_answer' => 'competitive',
                        'explanation' => '"Competitive salary" là cụm từ cố định chỉ mức lương cạnh tranh/hấp dẫn.'
                    ],
                    [
                        'skill' => 'grammar',
                        'difficulty' => 'B1',
                        'question_type' => 'mcq',
                        'question_text' => 'Select the correct conditional structure: "If you ___ any questions, please feel free to ask."',
                        'options' => ['have', 'had', 'will have', 'would have'],
                        'correct_answer' => 'have',
                        'explanation' => 'Mệnh đề điều kiện loại 1 với lời mời lịch sự ở vế chính.'
                    ],
                    [
                        'skill' => 'reading',
                        'difficulty' => 'B1',
                        'question_type' => 'mcq',
                        'question_text' => '"Candidate must possess strong interpersonal skills and teamwork mindset." What is required?',
                        'options' => ['Kỹ năng giao tiếp và làm việc nhóm', 'Khả năng làm việc độc lập 100%', 'Bằng thạc sĩ chuyên ngành', 'Kinh nghiệm 10 năm'],
                        'correct_answer' => 'Kỹ năng giao tiếp và làm việc nhóm',
                        'explanation' => 'Interpersonal skills = kỹ năng giao tiếp/ứng xử; teamwork mindset = tư duy làm việc nhóm.'
                    ]
                ]
            ];
        }

        // 4. Default AI Tutor / General Chat response (Intelligent Fallback Engine)
        return $this->generateTutorFallbackResponse($userPrompt, $systemPrompt);
    }

    /**
     * Generate an intelligent, context-aware fallback response for the ESL AI Tutor.
     */
    protected function generateTutorFallbackResponse(string $userPrompt, string $systemPrompt): string
    {
        $prompt = trim($userPrompt);
        $lower = mb_strtolower($prompt, 'UTF-8');

        $isAskingMeaning = (bool) preg_match('/(nghĩa là gì|có nghĩa là gì|nghĩa của|nghĩa từ|là gì|what does|meaning)/iu', $lower);

        // 1. Greetings (Xin chào, hello, hi...)
        if (!$isAskingMeaning && (
            preg_match('/^(xin chào|chào\s*(bạn|bot|thầy|cô)?|hello\s*(bot)?|hi\s*(bot)?|hey|good morning|good afternoon|good evening)[\s!.,?~]*$/iu', $lower)
            || in_array($lower, ['xin chào', 'chào', 'chào bạn', 'hello', 'hi', 'alo', 'hey'])
        )) {
            return "Chào bạn! 🌟 Tôi là ESL Bot - Trợ lý gia sư tiếng Anh thông minh của bạn.\n\n"
                . "Rất vui được đồng hành cùng bạn học tập hôm nay! Bạn muốn tra cứu từ vựng, giải thích ngữ pháp, phát âm hay hỏi về bài học nào? Hãy nhắn cho tôi nhé! 😊";
        }

        // 2. Questions about bot capabilities (Bạn giúp gì, làm được gì...)
        if (preg_match('/(giúp gì|làm gì|làm được gì|chức năng|hướng dẫn|bạn là ai|who are you|hỗ trợ gì)/iu', $lower)) {
            return "Tôi là **ESL Bot** – Trợ lý gia sư tiếng Anh thông minh của bạn! 🌟\n\n"
                . "Tôi có thể hỗ trợ bạn mọi lúc:\n"
                . "• 📖 **Tra cứu & giải nghĩa từ vựng**: Nghĩa tiếng Việt, từ loại, phiên âm IPA, ví dụ (VD: *'Hello nghĩa là gì'*, *'Từ vựng về Environment'*)\n"
                . "• ✍️ **Giải thích ngữ pháp**: 12 thì, câu điều kiện, câu bị động (VD: *'Giải thích thì hiện tại đơn'*, *'Khi nào dùng have been / has been'*)\n"
                . "• 🔄 **Phân biệt từ dễ nhầm**: (VD: *'How much và How many'*, *'Look, see, watch'*)\n"
                . "• 🗣️ **Luyện phát âm & Chữa bài**: Trong các mục AI Speaking và AI Writing.\n\n"
                . "👉 Hãy thử hỏi tôi một câu hỏi về tiếng Anh ngay nhé!";
        }

        // 3. Vocabulary Dictionary for common queries
        $vocabDict = [
            'hello' => [
                'word' => 'Hello',
                'ipa' => '/həˈləʊ/',
                'type' => 'Thán từ / Danh từ',
                'meaning' => 'Lời chào, xin chào. Dùng khi gặp mặt ai đó, bắt đầu cuộc trò chuyện hoặc khi nhấc máy nghe điện thoại.',
                'examples' => [
                    'Hello! Nice to meet you. (Xin chào! Rất vui được gặp bạn.)',
                    'She said hello with a bright smile. (Cô ấy mỉm cười và nói lời chào.)',
                ],
                'note' => 'Các cách chào tương tự: Hi (thân mật hơn), Good morning/afternoon, Hey.'
            ],
            'apple' => [
                'word' => 'Apple',
                'ipa' => '/ˈæpl/',
                'type' => 'Danh từ',
                'meaning' => 'Quả táo (loại trái cây tròn, vỏ đỏ, xanh hoặc vàng).',
                'examples' => [
                    'An apple a day keeps the doctor away. (Mỗi ngày ăn một quả táo giúp tránh xa bác sĩ - Ngạn ngữ Anh)',
                    'She bought a basket of fresh red apples. (Cô ấy đã mua một giỏ táo đỏ tươi.)',
                ],
                'note' => 'Cụm từ hay: the apple of one\'s eye (người được yêu quý nhất, báu vật).'
            ],
            'student' => [
                'word' => 'Student',
                'ipa' => '/ˈstjuːdnt/ hoặc /ˈstuːdnt/',
                'type' => 'Danh từ',
                'meaning' => 'Học sinh, sinh viên, người đang theo học.',
                'examples' => [
                    'She is an excellent ESL student. (Cô ấy là một học viên ESL xuất sắc.)',
                    'All students must submit their assignments on time. (Tất cả học viên phải nộp bài tập đúng hạn.)',
                ],
                'note' => 'Đồng nghĩa: pupil (học sinh nhỏ tuổi), learner (người học).'
            ],
            'teacher' => [
                'word' => 'Teacher',
                'ipa' => '/ˈtiːtʃər/',
                'type' => 'Danh từ',
                'meaning' => 'Giáo viên, thầy/cô giáo người dạy học.',
                'examples' => [
                    'Our English teacher is very patient and kind. (Giáo viên tiếng Anh của chúng tôi rất kiên nhẫn và tốt bụng.)',
                ],
                'note' => 'Động từ: teach (dạy dỗ).'
            ],
            'grammar' => [
                'word' => 'Grammar',
                'ipa' => '/ˈɡræmər/',
                'type' => 'Danh từ',
                'meaning' => 'Ngữ pháp, quy tắc kết hợp từ ngữ thành câu hoàn chỉnh.',
                'examples' => [
                    'Good grammar makes your writing clear and professional. (Ngữ pháp tốt giúp bài viết rõ ràng và chuyên nghiệp.)',
                ],
                'note' => 'Lưu ý chính tả: kết thúc bằng "-ar" chứ không phải "-er".'
            ],
            'vocabulary' => [
                'word' => 'Vocabulary',
                'ipa' => '/vəˈkæbjələri/',
                'type' => 'Danh từ',
                'meaning' => 'Vốn từ vựng, tập hợp tất cả các từ trong một ngôn ngữ hoặc của một người.',
                'examples' => [
                    'Reading books daily expands your vocabulary rapidly. (Đọc sách hàng ngày giúp mở rộng vốn từ nhanh chóng.)',
                ],
                'note' => 'Viết tắt thường dùng: vocab.'
            ],
            'pronunciation' => [
                'word' => 'Pronunciation',
                'ipa' => '/prəˌnʌnsiˈeɪʃn/',
                'type' => 'Danh từ',
                'meaning' => 'Cách phát âm, sự phát âm từ ngữ theo đúng ngữ âm chuẩn.',
                'examples' => [
                    'Listening to native speakers improves your pronunciation. (Nghe người bản xứ giúp cải thiện phát âm của bạn.)',
                ],
                'note' => 'Động từ tương ứng là "pronounce" /prəˈnaʊns/ (chú ý sự thay đổi âm từ -nounce sang -nun-).'
            ],
            'hi' => [
                'word' => 'Hi',
                'ipa' => '/haɪ/',
                'type' => 'Thán từ',
                'meaning' => 'Lời chào thân mật giữa bạn bè, người quen (tương tự "hello" nhưng ít trang trọng hơn).',
                'examples' => [
                    'Hi everyone! Welcome back. (Chào mọi người! Chào mừng quay lại.)',
                ],
                'note' => 'Không nên dùng trong thư từ trang trọng hoặc phỏng vấn xin việc.'
            ],
            'book' => [
                'word' => 'Book',
                'ipa' => '/bʊk/',
                'type' => 'Danh từ & Động từ',
                'meaning' => '• Danh từ: Cuốn sách, quyển vở.\n• Động từ: Đặt trước vé, phòng khách sạn, chỗ ngồi.',
                'examples' => [
                    'I am reading a great book. (Tôi đang đọc một cuốn sách rất hay.)',
                    'I need to book a hotel room. (Tôi cần đặt trước một phòng khách sạn.)',
                ],
                'note' => 'Cụm từ hay: by the book (làm theo đúng quy tắc).'
            ],
            'learn' => [
                'word' => 'Learn',
                'ipa' => '/lɜːn/',
                'type' => 'Động từ',
                'meaning' => 'Học hỏi, tiếp thu kiến thức hoặc kỹ năng mới thông qua trải nghiệm hoặc rèn luyện.',
                'examples' => [
                    'She is learning English every day. (Cô ấy đang học tiếng Anh mỗi ngày.)',
                ],
                'note' => 'Phân biệt: "Learn" nhấn mạnh việc tiếp thu kỹ năng (learn to swim), còn "Study" nhấn mạnh hành động học tập/đọc sách vở.'
            ],
            'study' => [
                'word' => 'Study',
                'ipa' => '/ˈstʌdi/',
                'type' => 'Động từ & Danh từ',
                'meaning' => 'Học tập, nghiên cứu tài liệu, ôn thi một cách có hệ thống.',
                'examples' => [
                    'He is studying for his final exam. (Cậu ấy đang ôn thi cho kỳ thi cuối kỳ.)',
                ],
                'note' => 'Thường dùng khi ngồi vào bàn học, xem sách vở hoặc nghiên cứu chuyên sâu.'
            ],
            'environment' => [
                'word' => 'Environment',
                'ipa' => '/ɪnˈvaɪrənmənt/',
                'type' => 'Danh từ',
                'meaning' => 'Môi trường sống tự nhiên hoặc môi trường xung quanh (làm việc, học tập).',
                'examples' => [
                    'We must protect our environment. (Chúng ta phải bảo vệ môi trường của chúng ta.)',
                    'A positive working environment increases productivity. (Môi trường làm việc tích cực giúp tăng năng suất.)',
                ],
                'note' => 'Tính từ: environmental (thuộc về môi trường).'
            ],
            'competitive' => [
                'word' => 'Competitive',
                'ipa' => '/kəmˈpetətɪv/',
                'type' => 'Tính từ',
                'meaning' => 'Có tính cạnh tranh cao, cạnh tranh hoặc có tinh thần ganh đua.',
                'examples' => [
                    'The company offers a competitive salary. (Công ty đưa ra mức lương rất cạnh tranh/hấp dẫn.)',
                    'It is a highly competitive market. (Đó là một thị trường có tính cạnh tranh rất cao.)',
                ],
                'note' => 'Danh từ: competition (cuộc thi/sự cạnh tranh), competitor (đối thủ cạnh tranh).'
            ],
            'experience' => [
                'word' => 'Experience',
                'ipa' => '/ɪkˈspɪəriəns/',
                'type' => 'Danh từ & Động từ',
                'meaning' => '• Danh từ (không đếm được): Kinh nghiệm làm việc hoặc kiến thức tích lũy.\n• Danh từ (đếm được): Trải nghiệm, sự việc đã trải qua.\n• Động từ: Trải qua, nếm trải.',
                'examples' => [
                    'He has rich experience in software engineering. (Anh ấy có nhiều kinh nghiệm trong kỹ nghệ phần mềm.)',
                    'It was an unforgettable experience. (Đó là một trải nghiệm không thể nào quên.)',
                ],
                'note' => 'Tính từ: experienced (có kinh nghiệm, lành nghề).'
            ],
            'schedule' => [
                'word' => 'Schedule',
                'ipa' => '/ˈskedʒuːl/ (US) hoặc /ˈʃedjuːl/ (UK)',
                'type' => 'Danh từ & Động từ',
                'meaning' => 'Lịch trình, thời gian biểu, kế hoạch làm việc.',
                'examples' => [
                    'I have a busy schedule today. (Hôm nay tôi có một lịch trình bận rộn.)',
                    'The meeting is scheduled for 2 PM. (Cuộc họp được lên lịch vào lúc 2 giờ chiều.)',
                ],
                'note' => 'Cụm từ: ahead of schedule (sớm hơn tiến độ), behind schedule (chậm tiến độ).'
            ],
            'comfortable' => [
                'word' => 'Comfortable',
                'ipa' => '/ˈkʌmftəbl/',
                'type' => 'Tính từ',
                'meaning' => 'Thoải mái, tiện nghi, dễ chịu (về thể chất hoặc cảm xúc).',
                'examples' => [
                    'This sofa is very comfortable. (Chiếc ghế sofa này rất êm ái/thoải mái.)',
                    'Please make yourself comfortable. (Xin cứ tự nhiên như ở nhà nhé.)',
                ],
                'note' => 'Lưu ý phát âm nuốt âm: chỉ đọc 3 âm tiết /ˈkʌmf-tə-bl/ thay vì 4 âm tiết.'
            ],
        ];

        // Check if user is asking about meaning of a specific known word
        foreach ($vocabDict as $key => $item) {
            if (preg_match('/\b' . preg_quote($key, '/') . '\b/iu', $lower)) {
                $res = "📖 **{$item['word']}** `{$item['ipa']}` ({$item['type']})\n\n"
                    . "🔹 **Nghĩa:** {$item['meaning']}\n\n"
                    . "📝 **Ví dụ minh họa:**\n";
                foreach ($item['examples'] as $ex) {
                    $res .= "• {$ex}\n";
                }
                if (!empty($item['note'])) {
                    $res .= "\n💡 *Ghi chú:* {$item['note']}";
                }
                return $res;
            }
        }

        // Generic word meaning extraction (VD: "XYZ nghĩa là gì")
        if (preg_match('/(?:từ\s+)?["\']?([a-zA-Z\s]{2,25})["\']?\s+(?:nghĩa là gì|có nghĩa là gì|là gì)/iu', $prompt, $matches)
            || preg_match('/(?:nghĩa của từ|nghĩa từ)\s+["\']?([a-zA-Z\s]{2,25})["\']?/iu', $prompt, $matches)) {
            $searchedWord = trim($matches[1]);
            return "📖 **Giải nghĩa từ: \"{$searchedWord}\"**\n\n"
                . "Trong tiếng Anh, **{$searchedWord}** mang ý nghĩa tùy thuộc vào ngữ cảnh câu và từ loại (danh từ, động từ hay tính từ).\n\n"
                . "💡 **Cách học từ này hiệu quả:**\n"
                . "1. Đặt ít nhất 1 câu hoàn chỉnh có chứa từ **{$searchedWord}**.\n"
                . "2. Tra cứu thêm các từ loại liên quan (Word Family) và từ đồng nghĩa.\n\n"
                . "👉 Bạn có thể nhắn cả câu chứa từ này để tôi dịch và phân tích ngữ cảnh chuẩn xác nhất nhé!";
        }

        // 4. Grammar explanation requests
        // 4.1. Present simple (thì hiện tại đơn)
        if (preg_match('/(hiện tại đơn|present simple)/iu', $lower)) {
            return "📚 **Thì Hiện Tại Đơn (Present Simple Tense)**\n\n"
                . "🔹 **1. Công thức:**\n"
                . "• Với To Be: `S + am/is/are + O/Adj`\n"
                . "• Với Động từ thường: `S + V(s/es) + O` (Phủ định: `S + do/does not + V`)\n\n"
                . "🔹 **2. Cách dùng chính:**\n"
                . "• Diễn tả chân lý, sự thật hiển nhiên (VD: *The earth goes around the sun.*)\n"
                . "• Diễn tả thói quen, hành động lặp lại (VD: *I practice English every morning.*)\n\n"
                . "🔹 **3. Dấu hiệu:** *always, usually, often, sometimes, everyday, once a week...*\n\n"
                . "📝 *Ví dụ:* She **studies** hard every night. / He **does not like** spicy food.";
        }

        // 4.2. Past simple (thì quá khứ đơn)
        if (preg_match('/(quá khứ đơn|past simple)/iu', $lower)) {
            return "📚 **Thì Quá Khứ Đơn (Past Simple Tense)**\n\n"
                . "🔹 **1. Công thức:**\n"
                . "• Với To Be: `S + was/were + O/Adj`\n"
                . "• Với Động từ thường: `S + V2/ed + O` (Phủ định: `S + did not + V`)\n\n"
                . "🔹 **2. Cách dùng:**\n"
                . "• Diễn tả hành động đã bắt đầu và kết thúc hoàn toàn trong quá khứ.\n\n"
                . "🔹 **3. Dấu hiệu:** *yesterday, last week/month/year, ago, in 2022...*\n\n"
                . "📝 *Ví dụ:* We **visited** Da Nang last summer. / She **did not go** to school yesterday.";
        }

        // 4.3. Present perfect (thì hiện tại hoàn thành)
        if (preg_match('/(hiện tại hoàn thành|present perfect)/iu', $lower)) {
            return "📚 **Thì Hiện Tại Hoàn Thành (Present Perfect Tense)**\n\n"
                . "🔹 **1. Công thức:** `S + have/has + V3/ed + O`\n"
                . "• `Have`: Dùng cho I / You / We / They\n"
                . "• `Has`: Dùng cho He / She / It / Danh từ số ít\n\n"
                . "🔹 **2. Cách dùng chính:**\n"
                . "• Hành động bắt đầu trong quá khứ và kéo dài đến hiện tại (đi với *since, for*).\n"
                . "• Trải nghiệm, kinh nghiệm sống (đi với *ever, never*).\n"
                . "• Hành động vừa mới xảy ra (đi với *just, already, yet*).\n\n"
                . "📝 *Ví dụ:* I **have lived** in Hanoi for 3 years. / She **has already finished** her homework.";
        }

        // 4.4. Conditional sentences (câu điều kiện)
        if (preg_match('/(câu điều kiện|conditional)/iu', $lower)) {
            return "📚 **Tổng Hợp Câu Điều Kiện (Conditional Sentences)**\n\n"
                . "• **Loại 1 (Có thật ở hiện tại/tương lai):**\n"
                . "  `If + S + V(hiện tại đơn), S + will + V(nguyên mẫu)`\n"
                . "  VD: *If it rains, we will stay home.*\n\n"
                . "• **Loại 2 (Không có thật ở hiện tại):**\n"
                . "  `If + S + V2/ed (were), S + would + V(nguyên mẫu)`\n"
                . "  VD: *If I were you, I would take this course.*\n\n"
                . "• **Loại 3 (Không có thật trong quá khứ):**\n"
                . "  `If + S + had + V3/ed, S + would have + V3/ed`\n"
                . "  VD: *If she had studied harder, she would have passed.*";
        }

        // 4.5. Has been vs Have been
        if (preg_match('/(has been|have been)/iu', $lower)) {
            return "📚 **Phân Biệt 'Have been' và 'Has been'**\n\n"
                . "• **Have been**: Dùng với các chủ ngữ số nhiều và ngôi 1, 2:\n"
                . "  `I / You / We / They / Danh từ số nhiều`\n"
                . "  📝 VD: *They **have been** friends for ten years.* (Họ là bạn được 10 năm rồi.)\n\n"
                . "• **Has been**: Dùng với chủ ngữ ngôi thứ 3 số ít:\n"
                . "  `He / She / It / Danh từ số ít / Danh từ không đếm được`\n"
                . "  📝 VD: *She **has been** working here since 2020.* (Cô ấy làm việc ở đây từ 2020.)\n\n"
                . "💡 *Thường xuất hiện trong thì Hiện tại hoàn thành và Thể bị động!*";
        }

        // 4.6. How much vs How many
        if (preg_match('/(how much|how many)/iu', $lower)) {
            return "📚 **Phân Biệt 'How much' và 'How many'**\n\n"
                . "• **How many** + Danh từ đếm được số nhiều (Countable):\n"
                . "  📝 VD: *How many books did you buy?* (Bạn đã mua bao nhiêu cuốn sách?)\n\n"
                . "• **How much** + Danh từ không đếm được (Uncountable):\n"
                . "  📝 VD: *How much water do you need?* (Bạn cần bao nhiêu nước?)\n\n"
                . "• **How much** dùng để hỏi giá cả tiền nong:\n"
                . "  📝 VD: *How much is this shirt?* (Chiếc áo này bao nhiêu tiền?)";
        }

        // 4.7. General grammar request (giải thích cấu trúc ngữ pháp...)
        if (preg_match('/(ngữ pháp|cấu trúc|grammar|tenses)/iu', $lower)) {
            return "📚 **Hệ Thống Ngữ Pháp Tiếng Anh Trọng Tâm**\n\n"
                . "Tôi có thể giải thích chi tiết các chủ điểm sau:\n"
                . "1. **12 Thì tiếng Anh**: Hiện tại đơn, Quá khứ đơn, Hiện tại hoàn thành...\n"
                . "2. **Câu điều kiện (If sentences)**: Loại 0, 1, 2, 3\n"
                . "3. **Thể bị động (Passive Voice)**: `S + be + V3/ed`\n"
                . "4. **Mệnh đề quan hệ**: Who, Whom, Which, That, Whose\n"
                . "5. **Giới từ & Trợ động từ**: in/on/at, has been/have been...\n\n"
                . "👉 Bạn muốn tìm hiểu cấu trúc cụ thể nào? Hãy gõ tên cấu trúc (VD: *'Thì hiện tại đơn'*, *'Câu điều kiện loại 1'*...) để tôi hướng dẫn chi tiết nhé!";
        }

        // 5. Pronunciation requests
        if (preg_match('/(phát âm|ipa|pronunciation|đọc sao|phát âm thế nào)/iu', $lower)) {
            return "🗣️ **Hướng Dẫn Phát Âm Tiếng Anh Chuẩn IPA**\n\n"
                . "Để nói tiếng Anh tự nhiên và chuẩn xác, bạn hãy chú ý 3 quy tắc:\n"
                . "1. **Ending Sounds (Âm đuôi)**: Luôn phát âm rõ các âm cuối /s/, /t/, /d/, /k/, /z/ (rất quan trọng với người Việt).\n"
                . "2. **Word Stress (Trọng âm)**: Nhấn mạnh đúng âm tiết chính (VD: *'record'* danh từ nhấn âm 1, động từ nhấn âm 2).\n"
                . "3. **Intonation (Ngữ điệu)**: Lên giọng ở cuối câu hỏi Yes/No, xuống giọng ở câu trần thuật.\n\n"
                . "👉 Bạn cần hướng dẫn phát âm từ nào? Hãy nhắn từ đó để tôi cung cấp phiên âm IPA và mẹo phát âm nhé!";
        }

        // 6. Dynamic Context-aware response for other queries
        $safePrompt = htmlspecialchars(strip_tags($prompt), ENT_QUOTES, 'UTF-8');
        return "🤖 **ESL Bot**: Cảm ơn câu hỏi của bạn về: *\"{$safePrompt}\"*\n\n"
            . "Để nắm vững chủ đề này trong tiếng Anh:\n"
            . "• Hãy ghi nhớ cách dùng kèm ngữ cảnh thực tế thay vì học vẹt công thức.\n"
            . "• Đặt ít nhất 1-2 câu ví dụ ngắn gắn liền với giao tiếp hàng ngày.\n"
            . "• Kết hợp luyện phát âm to thành tiếng để tăng phản xạ tự nhiên.\n\n"
            . "💡 *Bạn có thể hỏi tôi:* Tra từ vựng (VD: *'Hello nghĩa là gì'*), giải thích ngữ pháp (VD: *'Thì hiện tại đơn'*), hoặc vào mục **Luyện đề / AI Writing** để thực hành nhé!";
    }
}
