<?php

namespace TDSoft\AiTutor\Core\Models;

use Illuminate\Database\Eloquent\Model;

final class CreditTransaction extends AppendOnlyModel
{
    protected $table = 'tutor_ai_credit_transactions';
    protected $guarded = ['*'];
    public $timestamps = false;
}
