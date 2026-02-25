<?php

namespace App\Services;

use App\DTOs\QuestionDetailDto;
use App\DTOs\SessionSummaryDto;
use App\Models\Question;
use App\Models\Session;
use App\Queries\QuestionQueries;
use App\Queries\SessionQueries;
use Illuminate\Database\Eloquent\Collection;

final class SessionSummaryService
{
    public function __construct(
        private readonly QuestionQueries $questionQueries,
        private readonly SessionQueries $sessionQueries,
    ) {}

    public function build(int $sessionId): SessionSummaryDto
    {
        $session = $this->sessionQueries->findOrFail($sessionId);
        $questions = $this->questionQueries->allWithAnswers($sessionId);

        return new SessionSummaryDto(
            sessionId: $session->id,
            status: $session->status,
            totalQuestions: $session->total_questions,
            correctAnswers: $session->correct_count,
            scorePercentage: $this->calculateScore($session),
            difficultyProgression: $questions->pluck('difficulty')->toArray(),
            details: $this->mapQuestionDetails($questions),
        );
    }

    private function calculateScore(Session $session): float
    {
        if ($session->total_questions === 0) {
            return 0;
        }

        return round(($session->correct_count / $session->total_questions) * 100, 2);
    }

    private function mapQuestionDetails(Collection $questions): array
    {
        return $questions->map(function (Question $question) {
            $answer = $question->answers->sortByDesc('created_at')->first();

            return new QuestionDetailDto(
                questionNumber: $question->question_number,
                question: $question->question_text,
                correctAnswer: $question->correct_answer,
                yourAnswer: $answer?->student_answer,
                correct: $answer !== null ? $answer->is_correct : false,
                difficulty: $question->difficulty,
                feedback: $answer?->feedback,
            )->toArray();
        })->toArray();
    }
}
