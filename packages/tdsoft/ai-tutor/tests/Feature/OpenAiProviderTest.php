<?php

namespace TDSoft\AiTutor\Tests\Feature;

use Illuminate\Http\Client\Factory;
use TDSoft\AiTutor\Core\AiRequest;
use TDSoft\AiTutor\Core\AiResponse;
use TDSoft\AiTutor\Core\StreamOutput;
use TDSoft\AiTutor\Providers\CredentialResolver;
use TDSoft\AiTutor\Providers\OpenAiProvider;
use TDSoft\AiTutor\Tests\FoundationTestCase;

final class OpenAiProviderTest extends FoundationTestCase
{
    private function driver(Factory $http): OpenAiProvider
    {
        config([
            'ai-tutor.credentials.openai' => 'test-key-not-real',
            'ai-tutor.model' => 'test-chat',
            'ai-tutor.embedding_model' => 'test-embed',
            'ai-tutor.tutor.max_output_tokens' => 4000,
            'ai-tutor.tutor.reasoning_effort' => 'low',
        ]);
        $http->preventStrayRequests();

        return new OpenAiProvider($http, new CredentialResolver, $this->app->make(StreamOutput::class));
    }

    private function chat(): array
    {
        return ['id' => 'response-test', 'status' => 'completed', 'model' => 'test-chat',
            'output' => [['type' => 'message', 'content' => [['type' => 'output_text', 'text' => 'Hello']]]],
            'usage' => ['input_tokens' => 12, 'output_tokens' => 2, 'input_tokens_details' => ['cached_tokens' => 3]]];
    }

    public function test_chat_and_embedding_are_normalized_without_remote_storage(): void
    {
        $http = new Factory;
        $driver = $this->driver($http);
        $http->fake([
            'api.openai.com/v1/responses' => Factory::response($this->chat()),
            'api.openai.com/v1/embeddings' => Factory::response(['model' => 'test-embed', 'data' => [['embedding' => [1, 0.5]]], 'usage' => ['prompt_tokens' => 5]]),
        ]);
        $request = $this->request(payload: ['instructions' => 'Teach', 'input' => 'Hello']);
        $reply = $driver->execute($request);
        $this->assertSame('Hello', $reply->content);
        $this->assertSame(3, $reply->usage['cached_tokens']);
        $http->assertSent(fn ($r) => $r['store'] === false && $r['stream'] === false && $r->url() === 'https://api.openai.com/v1/responses');
        $embedding = $driver->execute(new AiRequest('knowledge_embedding', $request->actor, ['input' => 'Text'], $request->requestId, 'embed'));
        $this->assertSame([1, 0.5], $embedding->data['embedding']);
        $this->assertSame($embedding->toArray(), AiResponse::fromArray($embedding->toArray())->toArray());
    }

    public function test_real_sse_frames_emit_deltas_and_require_completed_usage(): void
    {
        $http = new Factory;
        $driver = $this->driver($http);
        $frames = 'data: '.json_encode(['type' => 'response.output_text.delta', 'delta' => 'Hello'])."\n\n"
            .'data: '.json_encode(['type' => 'response.completed', 'response' => $this->chat()])."\n\n";
        $http->fake(['*' => Factory::response($frames, 200, ['Content-Type' => 'text/event-stream'])]);
        $deltas = [];
        $reply = $this->app->make(StreamOutput::class)->during(function ($text) use (&$deltas) {
            $deltas[] = $text;
        },
            fn () => $driver->execute($this->request(payload: ['instructions' => 'Teach', 'input' => 'Hello'])));
        $this->assertSame(['Hello'], $deltas);
        $this->assertSame(12, $reply->usage['input_tokens']);
        $this->assertFalse($this->app->make(StreamOutput::class)->active());
    }

    public function test_gpt_five_chat_uses_configured_reasoning_effort_and_output_budget(): void
    {
        $http = new Factory;
        $driver = $this->driver($http);
        config(['ai-tutor.model' => 'gpt-5-mini']);
        $http->fake(['*' => Factory::response($this->chat())]);

        $driver->execute($this->request(payload: ['instructions' => 'Teach', 'input' => 'Hello']));

        $http->assertSent(fn ($request) => $request['max_output_tokens'] === 4000
            && $request['reasoning'] === ['effort' => 'low']);
    }

    public function test_auth_failure_is_sanitized_and_unknown_response_requires_reconciliation(): void
    {
        $http = new Factory;
        $driver = $this->driver($http);
        $http->fake(['*' => Factory::response(['error' => 'secret-key-provider-body'], 401)]);
        $this->assertError('AI_PROVIDER_AUTH_FAILED', fn () => $driver->execute($this->request(payload: ['instructions' => 'Teach', 'input' => 'Hi'])));
        $http = new Factory;
        $driver = $this->driver($http);
        $http->fake(['*' => Factory::response(['status' => 'incomplete'], 200)]);
        $this->assertError('AI_REQUEST_RECONCILIATION_REQUIRED', fn () => $driver->execute($this->request(payload: ['instructions' => 'Teach', 'input' => 'Hi'])));
    }
}
