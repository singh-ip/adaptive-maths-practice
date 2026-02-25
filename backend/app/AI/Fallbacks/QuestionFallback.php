<?php

namespace App\AI\Fallbacks;

use App\AI\Prompts\QuestionPrompt;

/**
 * Generates a valid question entirely in PHP — no LLM required.
 * Used when Ollama is unavailable, times out, or returns unparseable output.
 */
final class QuestionFallback
{
    public function __construct(
        private readonly QuestionPrompt $prompt,
    ) {}

    /**
     * Word-problem templates. Placeholders:
     *   {a} = first operand, {b} = second operand, {thing} = singular noun, {things} = plural noun.
     *
     * @var list<array{singular: string, plural: string, template: string}>
     */
    private const TEMPLATES = [
        [
            'singular' => 'box',
            'plural' => 'boxes',
            'template' => 'A warehouse has {a} {things}, each containing {b} books. How many books are there in total?',
        ],
        [
            'singular' => 'bag',
            'plural' => 'bags',
            'template' => 'A baker packs {b} cookies into each of {a} {things}. How many cookies are there in total?',
        ],
        [
            'singular' => 'row',
            'plural' => 'rows',
            'template' => 'A cinema has {a} {things} of seats with {b} seats in each row. How many seats are there in total?',
        ],
        [
            'singular' => 'basket',
            'plural' => 'baskets',
            'template' => 'A market stall has {a} {things}, each holding {b} apples. How many apples are there in total?',
        ],
        [
            'singular' => 'packet',
            'plural' => 'packets',
            'template' => 'A classroom receives {a} {things} of stickers with {b} stickers in each packet. How many stickers are there in total?',
        ],
        [
            'singular' => 'tray',
            'plural' => 'trays',
            'template' => 'A canteen has {a} {things}, each holding {b} cups. How many cups are there in total?',
        ],
        [
            'singular' => 'bottle',
            'plural' => 'bottles',
            'template' => 'A delivery van carries {a} crates, each containing {b} {things}. How many bottles are there in total?',
        ],
        [
            'singular' => 'plant',
            'plural' => 'plants',
            'template' => 'A garden centre has {a} tables, each displaying {b} {things}. How many plants are there in total?',
        ],
        [
            'singular' => 'egg',
            'plural' => 'eggs',
            'template' => 'A farm packs {b} {things} into each of {a} cartons. How many eggs are there in total?',
        ],
        [
            'singular' => 'pencil',
            'plural' => 'pencils',
            'template' => 'A teacher hands out {b} {things} to each of {a} students. How many pencils are given out in total?',
        ],
        [
            'singular' => 'car',
            'plural' => 'cars',
            'template' => 'A car park has {a} floors with {b} {things} on each floor. How many cars can it hold in total?',
        ],
        [
            'singular' => 'tile',
            'plural' => 'tiles',
            'template' => 'A floor is made up of {a} rows with {b} {things} in each row. How many tiles are there in total?',
        ],
    ];

    /**
     * @return array{question: string, correct_answer: int}
     */
    public function generate(int $difficulty): array
    {
        $ranges = $this->prompt->getDifficultyRanges($difficulty);

        $first = rand($ranges['first_min'], $ranges['first_max']);
        $second = rand($ranges['second_min'], $ranges['second_max']);

        $template = self::TEMPLATES[array_rand(self::TEMPLATES)];

        $question = str_replace(
            ['{a}', '{b}', '{thing}', '{things}'],
            [(string) $first, (string) $second, $template['singular'], $template['plural']],
            $template['template'],
        );

        return [
            'question' => $question,
            'correct_answer' => $first * $second,
        ];
    }
}
