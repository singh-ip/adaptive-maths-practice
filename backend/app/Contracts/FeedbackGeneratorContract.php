<?php

namespace App\Contracts;

interface FeedbackGeneratorContract
{
    public function explain(string $question, int $correctAnswer, int $studentAnswer): string;
}
