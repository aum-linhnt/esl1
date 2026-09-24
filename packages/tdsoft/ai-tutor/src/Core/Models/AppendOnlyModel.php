<?php

namespace TDSoft\AiTutor\Core\Models;

use Illuminate\Database\Eloquent\Model;
use TDSoft\AiTutor\Core\AiException;

abstract class AppendOnlyModel extends Model
{
    protected $guarded = ['*'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new AiException('AI_HISTORY_IMMUTABLE'));
        static::deleting(fn () => throw new AiException('AI_HISTORY_IMMUTABLE'));
    }
}
