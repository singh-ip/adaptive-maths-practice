<?php

namespace App\DTOs;

use App\Enums\SessionStatus;

final readonly class SessionSummaryDto
{
    public function __construct(
        public int $sessionId,
        public SessionStatus $status,
        public int $totalQuestions,
        public int $correctAnswers,
        public float $scorePercentage,
        /** @var list<int> */
        public array $difficultyProgression,
        /** @var list<array<string, mixed>> */
        public array $details,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'session_id' => $this->sessionId,
            'status' => $this->status->value,
            'total_questions' => $this->totalQuestions,
            'correct_answers' => $this->correctAnswers,
            'score_percentage' => $this->scorePercentage,
            'difficulty_progression' => $this->difficultyProgression,
            'details' => $this->details,
        ];
    }
}
