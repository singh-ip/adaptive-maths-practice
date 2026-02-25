<?php

namespace App\Contracts;

interface QuestionGeneratorContract
{
    public function generate(int $difficulty): array;
}
