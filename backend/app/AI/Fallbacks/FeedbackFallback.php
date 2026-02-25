<?php

namespace App\AI\Fallbacks;

final class FeedbackFallback
{
    public function generate(int $correctAnswer, int $studentAnswer): string
    {
        $difference = abs($correctAnswer - $studentAnswer);

        return match (true) {
            $difference > 20 => "Not quite! The correct answer is {$correctAnswer}. Try working through the problem step by step — break it into smaller parts.",
            $difference > 5 => "Close! But the correct answer is {$correctAnswer}. Double-check your calculation and try again — you're nearly there!",
            default => "So close! The correct answer is {$correctAnswer}. You were only {$difference} away — great effort, try once more!",
        };
    }
}
