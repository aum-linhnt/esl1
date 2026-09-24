<?php

namespace TDSoft\AiTutor\Core\Models;

use Illuminate\Database\Eloquent\Model;

final class CostSnapshot extends AppendOnlyModel
{
    protected $table = 'tutor_ai_cost_snapshots';
    protected $guarded = ['*'];
    public $timestamps = false;
}
