<?php

namespace App\AI\Prompts;

/**
 * Builds the Ollama prompt for answer feedback generation.
 *
 * Prompt strategy:
 * - We do NOT use format:'json' — feedback is free-form text, not JSON.
 *   Forcing JSON format for prose produces awkward escaped strings.
 * - We keep the prompt short and directive. Small models (3B) tend to ramble
 *   with longer prompts; a tight prompt with a clear length constraint produces
 *   cleaner output.
 */
final class FeedbackPrompt
{
    public function build(string $question, int $correctAnswer, int $studentAnswer): string
    {
        return <<<EOT
You are a friendly maths teacher for Grade 5-6 students (age 10-11).

A student got this question wrong:
Question: {$question}
Their answer: {$studentAnswer}
Correct answer: {$correctAnswer}

Reply with ONLY the feedback itself — no preamble, no labels, no intro phrase like "Here are..." or "Sure!".
Write 2-3 short encouraging sentences that:
1. Acknowledge they got it wrong kindly
2. Show the correct calculation step by step
3. Encourage them to try again

Keep it simple, warm, and under 60 words. Do not repeat the question.
EOT;
    }
}
