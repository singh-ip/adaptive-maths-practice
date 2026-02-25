<?php

namespace App\Mappers;

use App\DTOs\SubmitAnswerDto;
use App\Models\Question;
use App\Services\AdaptiveAlgorithm;

final class SubmitAnswerDtoBuilder
{
    public function __construct(
        private readonly AdaptiveAlgorithm $adaptiveAlgorithm,
    ) {}

    public function sessionComplete(
        bool $isCorrect,
        Question $question,
        int $studentAnswer,
        ?string $feedback,
    ): SubmitAnswerDto {
        return new SubmitAnswerDto(
            sessionComplete: true,
            answerCorrect: $isCorrect,
            correctAnswer: $question->correct_answer,
            yourAnswer: $studentAnswer,
            feedback: $feedback,
            progress: null,
            nextQuestionId: null,
            nextQuestionNumber: null,
            nextQuestion: null,
            nextDifficulty: null,
        );
    }

    public function nextQuestion(
        bool $isCorrect,
        Question $question,
        int $studentAnswer,
        ?string $feedback,
        int $lockedQuestionCount,
        int $correctSoFar,
        Question $nextQuestion,
    ): SubmitAnswerDto {
        return new SubmitAnswerDto(
            sessionComplete: false,
            answerCorrect: $isCorrect,
            correctAnswer: $question->correct_answer,
            yourAnswer: $studentAnswer,
            feedback: $feedback,
            progress: [
                'current_question' => $lockedQuestionCount + 1,
                'total_questions' => $this->adaptiveAlgorithm->getTotalQuestions(),
                'correct_so_far' => $correctSoFar,
            ],
            nextQuestionId: $nextQuestion->id,
            nextQuestionNumber: $nextQuestion->question_number,
            nextQuestion: $nextQuestion->question_text,
            nextDifficulty: $nextQuestion->difficulty,
        );
    }
}
