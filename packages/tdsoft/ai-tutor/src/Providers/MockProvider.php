<?php

namespace TDSoft\AiTutor\Providers;

use TDSoft\AiTutor\Contracts\ChatProviderInterface;
use TDSoft\AiTutor\Core\AiException;
use TDSoft\AiTutor\Core\AiRequest;
use TDSoft\AiTutor\Core\AiResponse;

final class MockProvider implements ChatProviderInterface
{
    public int $calls = 0;

    public ?AiRequest $lastRequest = null;

    public ?AiException $failure = null;

    public ?AiResponse $response = null;

    public function execute(AiRequest $request): AiResponse
    {
        if (! app()->environment('testing')) {
            throw new AiException('AI_PROVIDER_NOT_CONFIGURED');
        }
        $this->calls++;
        $this->lastRequest = $request;
        if ($this->failure) {
            throw $this->failure;
        }

        return $this->response ?? new AiResponse('Mock response', 'mock', 'mock-v1', ['input_tokens' => 10, 'output_tokens' => 5]);
    }
}
