<?php

namespace TDSoft\AiTutor\Http;

use TDSoft\AiTutor\Writing\WritingAccess;
use TDSoft\AiTutor\Writing\WritingDrafts;

final class WritingPageController
{
    public function index(WritingAccess $access): mixed
    {
        return view('ai-tutor::writing', ['actorId' => $access->check(), 'draftId' => null]);
    }

    public function show(string $id, WritingDrafts $drafts, WritingAccess $access): mixed
    {
        $drafts->get($id);

        return view('ai-tutor::writing', ['actorId' => $access->check(), 'draftId' => $id]);
    }
}
