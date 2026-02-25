<?php

namespace App\AI\Parsers;

use Illuminate\Support\Facades\Log;

final class QuestionResponseParser
{
    /**
     * @param  array<string, mixed>  $data  Raw decoded Ollama response body
     * @param  string  $model  Model name — included in warning logs only
     * @return array{question: string, correct_answer: int}|null Null signals the caller to use fallback
     */
    public function parse(array $data, string $model): ?array
    {
        $raw = $data['response'] ?? '';

        // Decode directly first (format:'json' should give us clean JSON)
        $json = json_decode($raw, true);

        // Secondary fallback: extract the first {...} block in case of surrounding whitespace
        if (! is_array($json)) {
            if (preg_match('/\{[^}]+\}/s', $raw, $matches)) {
                $json = json_decode($matches[0], true);
            }
        }

        if (
            is_array($json)
            && isset($json['question'], $json['correct_answer'])
            && is_string($json['question'])
            && is_numeric($json['correct_answer'])
        ) {
            return [
                'question' => trim($json['question']),
                'correct_answer' => (int) $json['correct_answer'],
            ];
        }

        Log::warning('Failed to parse Ollama question response', ['raw' => $raw, 'model' => $model]);

        return null;
    }
}
