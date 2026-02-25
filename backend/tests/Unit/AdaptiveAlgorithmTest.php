<?php

namespace Tests\Unit;

use App\Services\AdaptiveAlgorithm;
use PHPUnit\Framework\TestCase;

class AdaptiveAlgorithmTest extends TestCase
{
    private AdaptiveAlgorithm $algorithm;

    protected function setUp(): void
    {
        parent::setUp();
        $this->algorithm = new AdaptiveAlgorithm();
    }

    // ── Starting difficulty ──────────────────────────────────────────────────

    public function test_starting_difficulty_is_five(): void
    {
        $this->assertEquals(AdaptiveAlgorithm::START_DIFFICULTY, $this->algorithm->getStartingDifficulty());
    }

    // ── Difficulty progression ───────────────────────────────────────────────

    public function test_correct_answer_increases_difficulty(): void
    {
        $this->assertEquals(AdaptiveAlgorithm::START_DIFFICULTY + 1, $this->algorithm->nextDifficulty(true, AdaptiveAlgorithm::START_DIFFICULTY));
    }

    public function test_incorrect_answer_decreases_difficulty(): void
    {
        $this->assertEquals(AdaptiveAlgorithm::START_DIFFICULTY - 1, $this->algorithm->nextDifficulty(false, AdaptiveAlgorithm::START_DIFFICULTY));
    }

    public function test_difficulty_cannot_exceed_ten(): void
    {
        $this->assertEquals(AdaptiveAlgorithm::MAX_DIFFICULTY, $this->algorithm->nextDifficulty(true, AdaptiveAlgorithm::MAX_DIFFICULTY));
    }

    public function test_difficulty_cannot_go_below_one(): void
    {
        $this->assertEquals(AdaptiveAlgorithm::MIN_DIFFICULTY, $this->algorithm->nextDifficulty(false, AdaptiveAlgorithm::MIN_DIFFICULTY));
    }

    // ── Session completion ───────────────────────────────────────────────────

    public function test_session_not_complete_before_five_questions(): void
    {
        $this->assertFalse($this->algorithm->isSessionComplete(AdaptiveAlgorithm::TOTAL_QUESTIONS - 1));
    }

    public function test_session_complete_at_five_questions(): void
    {
        $this->assertTrue($this->algorithm->isSessionComplete(AdaptiveAlgorithm::TOTAL_QUESTIONS));
    }

    public function test_session_complete_beyond_five_questions(): void
    {
        $this->assertTrue($this->algorithm->isSessionComplete(AdaptiveAlgorithm::TOTAL_QUESTIONS + 1));
    }

    // ── Total questions ──────────────────────────────────────────────────────

    public function test_total_questions_is_five(): void
    {
        $this->assertEquals(AdaptiveAlgorithm::TOTAL_QUESTIONS, $this->algorithm->getTotalQuestions());
    }
}
