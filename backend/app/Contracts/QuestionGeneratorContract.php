<?php

namespace App\Contracts;

interface QuestionGeneratorContract
{
    /**
     * @return array{question: string, correct_answer: int}
     */
    public function generate(int $difficulty): array;
}
