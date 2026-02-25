<?php

namespace App\Queries;

use App\Models\Answer;

final class AnswerQueries
{
    public function create(int $questionId, int $studentAnswer, bool $isCorrect, ?string $feedback = null): Answer
    {
        return Answer::create([
            'question_id' => $questionId,
            'student_answer' => $studentAnswer,
            'is_correct' => $isCorrect,
            'feedback' => $feedback,
        ]);
    }
}
