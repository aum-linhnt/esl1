<?php

namespace App\QuestionTypes\Handlers;

use App\Models\QuestionBank;
use App\QuestionTypes\QuestionTypeInterface;
use Illuminate\Http\Request;

class SpeakingHandler implements QuestionTypeInterface
{
    public function getType(): string
    {
        return 'speaking';
    }

    public function getLabel(): string
    {
        return 'Luyện phát âm & Ghi âm Speaking';
    }

    public function rules(): array
    {
        return [
            'correct_answer' => 'nullable|string',
        ];
    }

    public function formatForStorage(Request $request): array
    {
        $qType = $request->input('question_type');
        $answer = trim((string)$request->input('correct_answer', ''));

        if (empty($answer)) {
            $answer = $qType === 'pronunciation_speech' 
                ? 'Target Speech Phrase' 
                : 'Audio Submission / AI Evaluation';
        }

        return [
            'options' => null,
            'correct_answer' => $answer,
            'meta_data' => [],
        ];
    }

    public function evaluate(QuestionBank $question, mixed $userAnswer): bool
    {
        if (empty($userAnswer)) {
            return false;
        }

        // For pronunciation comparison
        if ($question->question_type === 'pronunciation_speech') {
            $userSpeech = trim((string)$userAnswer);
            $targetSpeech = trim((string)$question->correct_answer);

            if (empty($userSpeech) || empty($targetSpeech)) {
                return false;
            }

            // If user successfully recorded audio via microphone in CBT exam
            if (str_starts_with($userSpeech, 'audio_recorded_') || str_starts_with($userSpeech, 'data:audio')) {
                return true;
            }

            // If input is an audio URL or Base64 audio, evaluate using Speech AI API
            if (filter_var($userSpeech, FILTER_VALIDATE_URL)) {
                $service = app(\App\Services\AI\AiSpeakingService::class);
                $res = $service->assessAudioUrl($userSpeech, $targetSpeech);
                return ($res['score'] ?? 0) >= 60.0;
            }

            if (str_starts_with($userSpeech, 'data:audio') || strlen($userSpeech) > 500) {
                $service = app(\App\Services\AI\AiSpeakingService::class);
                $res = $service->assessBase64($userSpeech, $targetSpeech);
                return ($res['score'] ?? 0) >= 60.0;
            }

            // Fallback text matching
            $lowerUser = strtolower($userSpeech);
            $lowerTarget = strtolower($targetSpeech);

            if (str_contains($lowerUser, $lowerTarget) || str_contains($lowerTarget, $lowerUser)) {
                return true;
            }

            similar_text($lowerUser, $lowerTarget, $percent);
            return $percent >= 75.0;
        }

        // For general audio recording: verify presence
        return !empty(trim((string)$userAnswer));
    }
}
