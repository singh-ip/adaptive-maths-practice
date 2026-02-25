<?php

namespace App\AI\Prompts;

/**
 * Builds the Ollama prompt for multiplication question generation.
 *
 * Prompt strategy:
 * - PHP computes the operands and correct answer from difficulty-based ranges.
 *   Small local models (3B–8B) frequently miscalculate multi-digit multiplication,
 *   so the model's only job is to write a natural word problem around given numbers.
 * - We use format:'json' on the API call which constrains token sampling to valid
 *   JSON — more reliable than asking politely in the prompt alone.
 * - Temperature is set to 0.7 — enough structure for reliable JSON while allowing varied scenarios.
 */
final class QuestionPrompt
{
    /**
     * @param  string[]  $previousQuestions  Question texts already used in this session.
     * @return array{prompt: string, correct_answer: int}
     */
    public function build(int $difficulty, array $previousQuestions = []): array
    {
        $ranges = $this->getDifficultyRanges($difficulty);

        $first = rand($ranges['first_min'], $ranges['first_max']);
        $second = rand($ranges['second_min'], $ranges['second_max']);
        $answer = $first * $second;

        $avoidClause = '';
        if (!empty($previousQuestions)) {
            $listed = implode("\n", array_map(
                static fn(string $q, int $i): string => '  ' . ($i + 1) . '. ' . preg_replace('/[\x00-\x1F\x7F]/', ' ', strip_tags($q)),
                $previousQuestions,
                array_keys($previousQuestions)
            ));
            $avoidClause = "\n- The scenario and real-world context MUST be different from all previous questions in this session. Previous questions were:\n{$listed}\n  Choose a completely different setting, object, and theme.";
        }

        $prompt = <<<EOT
You are generating a maths question for a Grade 5-6 student (age 10-11).

Write a short multiplication word problem using these exact numbers: {$first} and {$second}.
The correct answer is {$answer}.

Rules:
- Use a realistic everyday scenario. Choose from: boxes, bags, rows of seats, baskets, packets, trays, crates, bottles, plants, eggs, pencils, floors of a car park, tiles, jars, buckets, pages, coins, buttons.
- The question must end with "How many [items] are there in total?"
- Do NOT include the number {$answer} anywhere in the question text.
- Keep it to 1-2 sentences.{$avoidClause}

You MUST respond with ONLY this JSON object, no other text:
{"question": "<your word problem here>", "correct_answer": {$answer}}
EOT;

        return ['prompt' => $prompt, 'correct_answer' => $answer];
    }

    /**
     * @return array{first_min: int, first_max: int, second_min: int, second_max: int}
     */
    public function getDifficultyRanges(int $difficulty): array
    {
        return match ($difficulty) {
            1, 2, 3 => ['first_min' => 2,  'first_max' => 9,  'second_min' => 2,  'second_max' => 9],
            4, 5 => ['first_min' => 2,  'first_max' => 9,  'second_min' => 10, 'second_max' => 20],
            6 => ['first_min' => 2,  'first_max' => 9,  'second_min' => 20, 'second_max' => 50],
            7, 8 => ['first_min' => 10, 'first_max' => 30, 'second_min' => 10, 'second_max' => 30],
            9 => ['first_min' => 20, 'first_max' => 50, 'second_min' => 20, 'second_max' => 50],
            10 => ['first_min' => 50, 'first_max' => 99, 'second_min' => 50, 'second_max' => 99],
            default => ['first_min' => 2,  'first_max' => 9,  'second_min' => 2,  'second_max' => 9],
        };
    }
}
