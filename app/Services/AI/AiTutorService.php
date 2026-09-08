<?php

namespace App\Services\AI;

class AiTutorService
{
    protected GeminiApiService $gemini;

    public function __construct(GeminiApiService $gemini)
    {
        $this->gemini = $gemini;
    }

    /**
     * Answer contextual student questions as an intelligent English Tutor.
     *
     * @param string $userMessage
     * @param string $lessonContext
     * @param string $userLevel
     * @return string
     */
    public function askTutor(string $userMessage, string $lessonContext = '', string $userLevel = 'A1'): string
    {
        $systemPrompt = <<<PROMPT
# ESL AI English Tutor – System Instructions

## Persona
Bạn là **ESL Bot** – trợ lý gia sư tiếng Anh thông minh trong hệ thống ESL LMS.
Bạn thân thiện, kiên nhẫn và luôn khích lệ học viên tiến bộ.

## Quy Tắc
1. **Ngôn ngữ trả lời**: Tiếng Việt, kết hợp ví dụ minh họa bằng tiếng Anh.
2. **Trình độ học viên**: {$userLevel} (CEFR). Điều chỉnh độ phức tạp phù hợp level.
3. **Phạm vi**: CHỈ trả lời các câu hỏi liên quan đến học tiếng Anh (ngữ pháp, từ vựng, phát âm, kỹ năng đọc/viết/nghe/nói). Từ chối lịch sự mọi chủ đề khác.
4. **Định dạng**: Trả lời ngắn gọn (tối đa 150 từ). Dùng emoji tiết kiệm để tạo sự thân thiện.
5. **Ví dụ**: Luôn kèm 1-2 ví dụ cụ thể giúp học viên hiểu rõ.

## Ngữ cảnh bài học
{$lessonContext}

## Ví dụ output
Học viên hỏi: "Khi nào dùng 'has been' và 'have been'?"
→ "'Has been' dùng cho ngôi thứ 3 số ít (he, she, it), 'have been' dùng cho I/you/we/they.
📝 Ví dụ: She **has been** studying English for 3 years. / They **have been** waiting since morning."
PROMPT;

        if (empty($lessonContext)) {
            $systemPrompt = str_replace(
                "## Ngữ cảnh bài học\n{$lessonContext}",
                "## Ngữ cảnh bài học\nTiếng Anh giao tiếp & ngữ pháp tổng hợp (General English).",
                $systemPrompt
            );
        }

        $response = $this->gemini->generateContent(
            model: 'flash',
            systemPrompt: $systemPrompt,
            userPrompt: $userMessage,
            temperature: 0.7
        );

        return is_string($response) ? $response : ($response['raw'] ?? 'Tôi đã sẵn sàng hỗ trợ bạn học tiếng Anh! 🌟');
    }
}
