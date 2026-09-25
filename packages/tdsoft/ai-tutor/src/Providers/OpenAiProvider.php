<?php

namespace TDSoft\AiTutor\Providers;

use Illuminate\Http\Client\Factory;
use Psr\Http\Message\StreamInterface;
use TDSoft\AiTutor\Contracts\ChatProviderInterface;
use TDSoft\AiTutor\Contracts\EmbeddingProviderInterface;
use TDSoft\AiTutor\Core\AiException;
use TDSoft\AiTutor\Core\AiRequest;
use TDSoft\AiTutor\Core\AiResponse;
use TDSoft\AiTutor\Core\StreamOutput;
use TDSoft\AiTutor\Knowledge\Vector;
use Throwable;

final class OpenAiProvider implements ChatProviderInterface, EmbeddingProviderInterface
{
    public function __construct(private Factory $http, private CredentialResolver $credentials, private StreamOutput $stream) {}

    public function execute(AiRequest $request): AiResponse
    {
        $embedding = $request->feature === 'knowledge_embedding';
        $model = $embedding ? ($request->payload['embedding_model'] ?? config('ai-tutor.embedding_model')) : config('ai-tutor.model');
        if (! is_string($model) || trim($model) === '') {
            throw new AiException('AI_PROVIDER_NOT_CONFIGURED');
        }
        if (! $embedding && $request->feature !== 'tutor_message') {
            throw new AiException('AI_PROVIDER_NOT_CONFIGURED');
        }
        $streaming = ! $embedding && $this->stream->active();
        $body = $embedding
            ? ['model' => $model, 'input' => $request->payload['input'], 'encoding_format' => 'float']
            : [
                'model' => $model, 'store' => false, 'stream' => $streaming,
                'max_output_tokens' => (int) config('ai-tutor.tutor.max_output_tokens', 1200),
                'instructions' => $request->payload['instructions'],
                'input' => $request->payload['input'],
            ];
        $effort = config('ai-tutor.tutor.reasoning_effort');
        if (! $embedding && str_starts_with($model, 'gpt-5') && is_string($effort) && $effort !== '') {
            $body['reasoning'] = ['effort' => $effort];
        }
        $key = $this->credentials->resolve('openai');
        try {
            // Fixed origin, no redirects, no automatic retry: uncertain failures need reconciliation.
            $response = $this->http->withToken($key)->acceptJson()->connectTimeout(10)->timeout($embedding ? 40 : 90)
                ->withOptions(['allow_redirects' => false, 'stream' => $streaming])
                ->post('https://api.openai.com/v1/'.($embedding ? 'embeddings' : 'responses'), $body);
        } catch (Throwable) {
            throw new AiException('AI_REQUEST_RECONCILIATION_REQUIRED');
        }
        if (in_array($response->status(), [401, 403], true)) {
            throw new AiException('AI_PROVIDER_AUTH_FAILED');
        }
        if ($response->status() === 429) {
            throw new AiException('AI_PROVIDER_RATE_LIMITED');
        }
        if (! $response->successful()) {
            throw new AiException('AI_REQUEST_RECONCILIATION_REQUIRED');
        }
        try {
            if ($streaming) {
                return $this->readStream($response->toPsrResponse()->getBody(), $model);
            }
            $json = $response->json();
            if ($embedding) {
                return new AiResponse('', 'openai', $json['model'] ?? $model,
                    ['input_tokens' => $json['usage']['prompt_tokens']],
                    providerRequestId: $response->header('x-request-id') ?: null,
                    data: ['embedding' => Vector::validate($json['data'][0]['embedding'])]);
            }

            return $this->chatResponse($json, $model);
        } catch (Throwable) {
            throw new AiException('AI_REQUEST_RECONCILIATION_REQUIRED');
        }
    }

    private function chatResponse(array $json, string $model): AiResponse
    {
        if (($json['status'] ?? null) !== 'completed' || ! isset($json['usage']['input_tokens'], $json['usage']['output_tokens'])) {
            throw new AiException('AI_REQUEST_RECONCILIATION_REQUIRED');
        }
        $text = '';
        foreach ($json['output'] ?? [] as $item) {
            if (($item['type'] ?? '') !== 'message') {
                continue;
            }
            foreach ($item['content'] ?? [] as $part) {
                if (($part['type'] ?? '') === 'output_text') {
                    $text .= $part['text'];
                } elseif (($part['type'] ?? '') === 'refusal') {
                    $text .= $part['refusal'];
                }
            }
        }
        if ($text === '') {
            throw new AiException('AI_REQUEST_RECONCILIATION_REQUIRED');
        }

        return new AiResponse($text, 'openai', $json['model'] ?? $model, [
            'input_tokens' => $json['usage']['input_tokens'],
            'output_tokens' => $json['usage']['output_tokens'],
            'cached_tokens' => $json['usage']['input_tokens_details']['cached_tokens'] ?? 0,
        ], $json['id'] ?? null);
    }

    private function readStream(StreamInterface $body, string $model): AiResponse
    {
        $buffer = '';
        while (! $body->eof()) {
            $buffer .= $body->read(4096);
            $buffer = str_replace("\r\n", "\n", $buffer);
            if (strlen($buffer) > 2097152) {
                throw new AiException('AI_REQUEST_RECONCILIATION_REQUIRED');
            }
            while (($end = strpos($buffer, "\n\n")) !== false) {
                $frame = substr($buffer, 0, $end);
                $buffer = substr($buffer, $end + 2);
                $data = [];
                foreach (explode("\n", $frame) as $line) {
                    if (str_starts_with($line, 'data:')) {
                        $data[] = ltrim(substr($line, 5));
                    }
                }
                if (! $data || implode('', $data) === '[DONE]') {
                    continue;
                }
                $event = json_decode(implode("\n", $data), true, flags: JSON_THROW_ON_ERROR);
                if (($event['type'] ?? '') === 'response.output_text.delta') {
                    $this->stream->emit($event['delta']);
                } elseif (($event['type'] ?? '') === 'response.completed') {
                    return $this->chatResponse($event['response'], $model);
                } elseif (in_array($event['type'] ?? '', ['error', 'response.failed', 'response.incomplete'], true)) {
                    throw new AiException('AI_REQUEST_RECONCILIATION_REQUIRED');
                }
            }
        }
        throw new AiException('AI_REQUEST_RECONCILIATION_REQUIRED');
    }
}
