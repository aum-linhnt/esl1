<?php

namespace TDSoft\AiTutor\Core\Models;

use Illuminate\Database\Eloquent\Model;

final class CreditAccount extends Model
{
    protected $table = 'tutor_ai_credit_accounts';

    protected $guarded = ['*'];
}
