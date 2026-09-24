<?php

namespace TDSoft\AiTutor\Core\Models;

use Illuminate\Database\Eloquent\Model;

final class UsageRecord extends AppendOnlyModel
{
    protected $table = 'tutor_ai_usage_records';
    protected $guarded = ['*'];
}
