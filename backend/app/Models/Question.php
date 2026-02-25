<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Question extends Model
{
    protected $fillable = [
        'session_id',
        'question_number',
        'difficulty',
        'question_text',
        'correct_answer',
    ];

    protected $casts = [
        'question_number' => 'integer',
        'difficulty' => 'integer',
        'correct_answer' => 'integer',
    ];

    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class);
    }
}
