<?php

namespace TDSoft\AiTutor\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use TDSoft\AiTutor\Conversations\ConversationService;
use TDSoft\AiTutor\Knowledge\Access;
use TDSoft\AiTutor\Providers\ProviderReadiness;

final class PageController
{
    public function tutor(Request $request, Access $access): mixed
    {
        $access->module('ai_tutor_core');
        $data = $request->validate(['lesson_id' => 'required|string|max:191']);
        $lesson = $access->lesson($data['lesson_id']);

        return view('ai-tutor::tutor', ['lesson' => $lesson, 'actorId' => $access->actors->resolve()->id]);
    }

    public function context(Request $request, Access $access, ConversationService $tutor): mixed
    {
        $access->module('ai_tutor_core');
        $data = $request->validate([
            'lesson_id' => 'required|string|max:191',
            'question_id' => 'nullable|string|max:191|required_with:attempt_id',
            'attempt_id' => 'nullable|string|max:191|required_with:question_id',
        ]);
        $lesson = $access->lesson($data['lesson_id']);
        if (! empty($data['question_id'])) {
            $tutor->questionContext($access->actors->resolve()->id, $data['question_id'], $lesson->lessonId, $data['attempt_id']);
        }

        return new JsonResponse(['lesson_id' => $lesson->lessonId, 'question_id' => $data['question_id'] ?? null, 'attempt_id' => $data['attempt_id'] ?? null]);
    }

    public function knowledge(Access $access, ProviderReadiness $readiness): mixed
    {
        $access->administrator();

        return view('ai-tutor::knowledge', ['embeddingError' => $readiness->embeddingError()]);
    }
}
