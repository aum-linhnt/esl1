<?php

namespace TDSoft\AiTutor\Core\Models;

use Illuminate\Database\Eloquent\Model;

final class AiRequestRecord extends Model
{
    protected $table = 'tutor_ai_requests';
    protected $guarded = ['*'];
    protected $hidden = ['encrypted_result'];
}
