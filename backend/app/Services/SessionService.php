<?php

namespace App\Services;

use App\Contracts\QuestionGeneratorContract;
use App\DTOs\SessionDto;
use App\Queries\QuestionQueries;
use App\Queries\SessionQueries;

final class SessionService
{
    public function __construct(
        private readonly AdaptiveAlgorithm $adaptiveAlgorithm,
        private readonly QuestionGeneratorContract $questionGenerator,
        private readonly QuestionQueries $questionQueries,
        private readonly SessionQueries $sessionQueries,
    ) {}

    public function create(): SessionDto
    {
        $session = $this->sessionQueries->create($this->adaptiveAlgorithm->getTotalQuestions(), 0);

        $difficulty = $this->adaptiveAlgorithm->getStartingDifficulty();
        $questionData = $this->questionGenerator->generate($difficulty);

        $question = $this->questionQueries->create(
            $session->id,
            1,
            $difficulty,
            $questionData['question'],
            $questionData['correct_answer']
        );

        return new SessionDto(
            sessionId: $session->id,
            questionId: $question->id,
            questionNumber: $question->question_number,
            question: $question->question_text,
            difficulty: $question->difficulty,
        );
    }
}
