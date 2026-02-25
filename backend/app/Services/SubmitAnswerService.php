<?php

namespace App\Services;

use App\Contracts\FeedbackGeneratorContract;
use App\Contracts\QuestionGeneratorContract;
use App\DTOs\SubmitAnswerDto;
use App\Models\Question;
use App\Models\Session;
use App\Queries\AnswerQueries;
use App\Queries\QuestionQueries;
use App\Queries\SessionQueries;

final class SubmitAnswerService
{
    public function __construct(
        private readonly AdaptiveAlgorithm $adaptiveAlgorithm,
        private readonly QuestionGeneratorContract $questionGenerator,
        private readonly FeedbackGeneratorContract $feedbackGenerator,
        private readonly SubmitAnswerDtoBuilder $dtoBuilder,
        private readonly QuestionQueries $questionQueries,
        private readonly SessionQueries $sessionQueries,
        private readonly AnswerQueries $answerQueries,
    ) {}

    public function handle(int $sessionId, int $questionId, int $studentAnswer): SubmitAnswerDto
    {
        $session = $this->sessionQueries->findOrFail($sessionId);

        [$question, $questionCount] = $this->questionQueries->findInSessionWithCount($questionId, $sessionId);

        $isLastQuestion = $this->adaptiveAlgorithm->isSessionComplete($questionCount);
        $isCorrect = $studentAnswer === $question->correct_answer;
        $feedback = $this->generateFeedbackIfWrong($isCorrect, $question, $studentAnswer);
        $nextQuestion = $this->prepareNextQuestion($isLastQuestion, $isCorrect, $question->difficulty);

        $this->persistAnswer($session, $question->id, $studentAnswer, $isCorrect, $feedback, $isLastQuestion);

        if ($isLastQuestion) {
            return $this->dtoBuilder->sessionComplete($isCorrect, $question, $studentAnswer, $feedback);
        }

        return $this->buildNextQuestionResponse(
            $session,
            $question,
            $studentAnswer,
            $isCorrect,
            $feedback,
            $questionCount,
            $nextQuestion
        );
    }

    private function persistAnswer(
        Session $session,
        int $questionId,
        int $studentAnswer,
        bool $isCorrect,
        ?string $feedback,
        bool $isLastQuestion
    ): void {
        $this->answerQueries->create($questionId, $studentAnswer, $isCorrect, $feedback);

        if ($isCorrect) {
            $this->sessionQueries->incrementCorrectCount($session);
        }

        if ($isLastQuestion) {
            $this->sessionQueries->markCompleted($session);
        }
    }

    /**
     * @param  array{difficulty: int, question: string, correct_answer: int}  $nextQuestion
     */
    private function buildNextQuestionResponse(
        Session $session,
        Question $question,
        int $studentAnswer,
        bool $isCorrect,
        ?string $feedback,
        int $questionCount,
        array $nextQuestion
    ): SubmitAnswerDto {
        $savedNextQuestion = $this->questionQueries->create(
            $session->id,
            $questionCount + 1,
            $nextQuestion['difficulty'],
            $nextQuestion['question'],
            $nextQuestion['correct_answer'],
        );

        return $this->dtoBuilder->nextQuestion(
            $isCorrect,
            $question,
            $studentAnswer,
            $feedback,
            $questionCount,
            $session->correct_count,
            $savedNextQuestion,
        );
    }

    private function generateFeedbackIfWrong(bool $isCorrect, Question $question, int $studentAnswer): ?string
    {
        if ($isCorrect) {
            return null;
        }

        return $this->feedbackGenerator->explain(
            $question->question_text,
            $question->correct_answer,
            $studentAnswer,
        );
    }

    private function prepareNextQuestion(bool $isLastQuestion, bool $isCorrect, int $currentDifficulty): ?array
    {
        if ($isLastQuestion) {
            return null;
        }

        $nextDifficulty = $this->adaptiveAlgorithm->nextDifficulty($isCorrect, $currentDifficulty);
        $questionData = $this->questionGenerator->generate($nextDifficulty);

        return [
            'difficulty' => $nextDifficulty,
            'question' => $questionData['question'],
            'correct_answer' => $questionData['correct_answer'],
        ];
    }
}
