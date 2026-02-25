<?php

namespace App\Queries;

use App\Enums\SessionStatus;
use App\Models\Session;

final class SessionQueries
{
    /**
     * Find a session by ID.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $sessionId): Session
    {
        return Session::findOrFail($sessionId);
    }

    public function create(int $totalQuestions, int $correctCount): Session
    {
        return Session::create([
            'status' => SessionStatus::Active,
            'total_questions' => $totalQuestions,
            'correct_count' => $correctCount,
        ]);
    }

    public function markCompleted(Session $session): bool
    {
        return $session->update(['status' => SessionStatus::Completed]);
    }

    public function incrementCorrectCount(Session $session): void
    {
        $session->increment('correct_count');
    }
}
