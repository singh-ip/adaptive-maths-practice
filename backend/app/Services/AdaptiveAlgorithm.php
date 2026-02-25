<?php

namespace App\Services;

final class AdaptiveAlgorithm
{
    public const MIN_DIFFICULTY = 1;

    public const MAX_DIFFICULTY = 10;

    public const TOTAL_QUESTIONS = 5;

    public const START_DIFFICULTY = 5;

    public function nextDifficulty(bool $isCorrect, int $currentDifficulty): int
    {
        if ($isCorrect) {
            return min($currentDifficulty + 1, self::MAX_DIFFICULTY);
        }

        return max($currentDifficulty - 1, self::MIN_DIFFICULTY);
    }

    public function getStartingDifficulty(): int
    {
        return self::START_DIFFICULTY;
    }

    public function isSessionComplete(int $questionsCount): bool
    {
        return $questionsCount >= self::TOTAL_QUESTIONS;
    }

    public function getTotalQuestions(): int
    {
        return self::TOTAL_QUESTIONS;
    }
}
