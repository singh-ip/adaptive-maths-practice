<?php

namespace App\Contracts;

interface QuestionGeneratorContract
{
    /**
     * @param  string[]  $previousQuestions  Question texts already used in this session.
     * @return array{question: string, correct_answer: int}
     */
    public function generate(int $difficulty, array $previousQuestions = []): array;
}
