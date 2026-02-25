<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class Answer extends Model
{
    protected $fillable = [
        'question_id',
        'student_answer',
        'is_correct',
        'feedback',
    ];

    protected $casts = [
        'student_answer' => 'integer',
        'is_correct' => 'boolean',
    ];
}
