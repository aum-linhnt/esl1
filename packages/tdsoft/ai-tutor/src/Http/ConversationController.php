<?php

namespace TDSoft\AiTutor\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use TDSoft\AiTutor\Conversations\ConversationService;
use TDSoft\AiTutor\Core\AiException;
use TDSoft\AiTutor\Core\StreamOutput;
use TDSoft\AiTutor\Knowledge\Access;
use Throwable;

final class ConversationController
{
    public function __construct(private ConversationService $tutor, private Access $access, private StreamOutput $stream) {}

    public function index(): JsonResponse
    {
        $this->access->module('ai_tutor_core');
        $items = DB::table('tutor_ai_conversations')->where('user_id', $this->access->actors->resolve()->id)
            ->orderByDesc('created_at')->limit(100)->get()->filter(function ($item) {
                try {
                    $this->tutor->conversation($item->id);

                    return true;
                } catch (AiException) {
                    return false;
                }
            })->values();

        return new JsonResponse($items);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lesson_id' => 'required|string|max:191', 'question_id' => 'nullable|string|max:191',
            'teaching_mode' => 'sometimes|in:socratic,hints_first,explain,practice,review,exam',
        ]);

        return new JsonResponse($this->tutor->create($data['lesson_id'], $data['teaching_mode'] ?? 'hints_first', $data['question_id'] ?? null), 201);
    }

    public function show(string $id): JsonResponse
    {
        $conversation = $this->tutor->conversation($id);
        $messages = DB::table('tutor_ai_conversation_messages')->where('conversation_id', $id)
            ->orderByDesc('created_at')->limit(100)->pluck('id')->reverse()->map(fn ($id) => $this->tutor->message($id))->values();

        return new JsonResponse(['conversation' => $conversation, 'messages' => $messages]);
    }

    public function send(Request $request, string $id): JsonResponse|StreamedResponse
    {
        $data = $request->validate([
            'message' => 'required|string|max:4000', 'request_id' => 'required|uuid', 'idempotency_key' => 'required|string|max:191',
        ]);
        $this->tutor->conversation($id);
        $work = fn () => $this->tutor->send($id, $data['message'], $data['request_id'], $data['idempotency_key']);
        if (! str_contains($request->header('Accept', ''), 'text/event-stream')) {
            return new JsonResponse($work());
        }

        return new StreamedResponse(function () use ($work, $data) {
            // Finish settlement even if the browser disconnects; reconnect must not create another AI request.
            ignore_user_abort(true);
            self::event('start', ['request_id' => $data['request_id']]);
            try {
                $message = $this->stream->during(fn ($text) => self::event('delta', ['text' => $text]), $work);
                self::event('completed', $message);
            } catch (Throwable $error) {
                self::event('error', ['code' => $error instanceof AiException ? $error->errorCode : 'AI_TUTOR_FAILED']);
            }
        }, 200, self::headers());
    }

    public function stream(string $id): StreamedResponse
    {
        // Read-only reconnect. No provider execution on GET.
        $message = $this->tutor->message($id);

        return new StreamedResponse(fn () => self::event($message['status'], $message), 200, self::headers());
    }

    public function source(string $id, string $chunk): JsonResponse
    {
        return new JsonResponse($this->tutor->source($id, $chunk));
    }

    public function feedback(Request $request, string $id): JsonResponse
    {
        $this->tutor->message($id);
        $data = $request->validate(['rating' => 'required|in:helpful,unhelpful']);
        DB::table('tutor_ai_message_feedback')->updateOrInsert(
            ['message_id' => $id, 'user_id' => $this->access->actors->resolve()->id],
            ['rating' => $data['rating'], 'created_at' => now(), 'updated_at' => now()],
        );

        return new JsonResponse(['saved' => true]);
    }

    private static function headers(): array
    {
        return ['Content-Type' => 'text/event-stream', 'Cache-Control' => 'no-cache, no-store', 'X-Accel-Buffering' => 'no'];
    }

    private static function event(string $event, array $data): void
    {
        echo 'event: '.$event."\n".'data: '.json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)."\n\n";
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }
}
