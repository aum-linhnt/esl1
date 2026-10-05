<?php

namespace TDSoft\AiTutor\Contracts;

use TDSoft\AiTutor\Core\LearnerIdentity;

interface ActorResolver
{
    public function resolve(): LearnerIdentity;
}
