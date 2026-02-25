<?php

namespace App\DTOs;

final readonly class SubmitAnswerDto
{
    public function __construct(
        public bool $sessionComplete,
        public bool $answerCorrect,
        public int $correctAnswer,
        public ?int $yourAnswer,
        public ?string $feedback,
        /** @var array<string, int>|null */
        public ?array $progress,
        public ?int $nextQuestionId,
        public ?int $nextQuestionNumber,
        public ?string $nextQuestion,
        public ?int $nextDifficulty,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'session_complete' => $this->sessionComplete,
            'answer_correct' => $this->answerCorrect,
            'correct_answer' => $this->correctAnswer,
            'your_answer' => $this->yourAnswer,
            'feedback' => $this->feedback,
            'progress' => $this->progress,
            'next_question_id' => $this->nextQuestionId,
            'next_question_number' => $this->nextQuestionNumber,
            'next_question' => $this->nextQuestion,
            'next_difficulty' => $this->nextDifficulty,
        ];
    }
}
