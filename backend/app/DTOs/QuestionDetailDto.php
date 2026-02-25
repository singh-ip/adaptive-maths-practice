<?php

namespace App\DTOs;

final readonly class QuestionDetailDto
{
    public function __construct(
        public int $questionNumber,
        public string $question,
        public int $correctAnswer,
        public ?int $yourAnswer,
        public bool $correct,
        public int $difficulty,
        public ?string $feedback,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'question_number' => $this->questionNumber,
            'question' => $this->question,
            'correct_answer' => $this->correctAnswer,
            'your_answer' => $this->yourAnswer,
            'correct' => $this->correct,
            'difficulty' => $this->difficulty,
            'feedback' => $this->feedback,
        ];
    }
}
