<?php

namespace App\Queries;

use App\Models\Question;
use Illuminate\Database\Eloquent\Collection;

final class QuestionQueries
{
    /**
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findInSessionOrFail(int $questionId, int $sessionId): Question
    {
        return Question::where('id', $questionId)
            ->where('session_id', $sessionId)
            ->firstOrFail();
    }

    /**
     * @return Collection<int, Question>
     */
    public function allWithAnswers(int $sessionId): Collection
    {
        return Question::where('session_id', $sessionId)
            ->with('answers')
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Return all question texts for a session in order (oldest first).
     *
     * @return string[]
     */
    public function getQuestionTexts(int $sessionId): array
    {
        return Question::where('session_id', $sessionId)
            ->orderBy('question_number')
            ->pluck('question_text')
            ->all();
    }

    public function create(int $sessionId, int $questionNumber, int $difficulty, string $questionText, int $correctAnswer): Question
    {
        return Question::create([
            'session_id' => $sessionId,
            'question_number' => $questionNumber,
            'difficulty' => $difficulty,
            'question_text' => $questionText,
            'correct_answer' => $correctAnswer,
        ]);
    }
}
