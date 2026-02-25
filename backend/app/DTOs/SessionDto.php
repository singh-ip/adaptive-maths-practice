<?php

namespace App\DTOs;

final readonly class SessionDto
{
    public function __construct(
        public int $sessionId,
        public int $questionId,
        public int $questionNumber,
        public string $question,
        public int $difficulty,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'session_id' => $this->sessionId,
            'question_id' => $this->questionId,
            'question_number' => $this->questionNumber,
            'question' => $this->question,
            'difficulty' => $this->difficulty,
        ];
    }
}
