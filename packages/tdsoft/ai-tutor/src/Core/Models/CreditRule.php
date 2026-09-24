<?php

namespace TDSoft\AiTutor\Core\Models;

use Illuminate\Database\Eloquent\Model;

final class CreditRule extends Model
{
    protected $table = 'tutor_ai_credit_rules';
    protected $guarded = ['*'];
}
