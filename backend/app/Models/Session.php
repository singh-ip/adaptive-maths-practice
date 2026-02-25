<?php

namespace App\Models;

use App\Enums\SessionStatus;
use Illuminate\Database\Eloquent\Model;

final class Session extends Model
{
    protected $fillable = [
        'status',
        'total_questions',
        'correct_count',
    ];

    protected $casts = [
        'status' => SessionStatus::class,
        'correct_count' => 'integer',
        'total_questions' => 'integer',
    ];

}
