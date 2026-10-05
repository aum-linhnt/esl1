<?php

namespace TDSoft\AiTutor\Core\Models;

final class CreditTransaction extends AppendOnlyModel
{
    protected $table = 'tutor_ai_credit_transactions';

    protected $guarded = ['*'];

    public $timestamps = false;
}
