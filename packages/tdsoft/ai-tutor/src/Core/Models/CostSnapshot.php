<?php

namespace TDSoft\AiTutor\Core\Models;

final class CostSnapshot extends AppendOnlyModel
{
    protected $table = 'tutor_ai_cost_snapshots';

    protected $guarded = ['*'];

    public $timestamps = false;
}
