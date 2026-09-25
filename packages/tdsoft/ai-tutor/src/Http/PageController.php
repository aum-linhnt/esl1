<?php

namespace TDSoft\AiTutor\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

    public function context(Request $request, Access $access): mixed
    {
        $access->module('ai_tutor_core');
        $data = $request->validate(['lesson_id' => 'required|string|max:191']);
        $lesson = $access->lesson($data['lesson_id']);

        return new JsonResponse(['lesson_id' => $lesson->lessonId]);
    }

    public function knowledge(Access $access, ProviderReadiness $readiness): mixed
    {
        $access->administrator();

        return view('ai-tutor::knowledge', ['embeddingError' => $readiness->embeddingError()]);
    }
}
