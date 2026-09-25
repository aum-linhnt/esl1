<?php

namespace TDSoft\AiTutor\Core\Models;

final class UsageRecord extends AppendOnlyModel
{
    protected $table = 'tutor_ai_usage_records';

    protected $guarded = ['*'];
}
