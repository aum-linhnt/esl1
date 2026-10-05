<?php

namespace TDSoft\AiTutor\Contracts;

use TDSoft\AiTutor\Core\AiRequest;
use TDSoft\AiTutor\Core\AiResponse;

interface AiProvider
{
    // Provider-specific data and credentials must not cross this boundary.
    public function execute(AiRequest $request): AiResponse;
}
