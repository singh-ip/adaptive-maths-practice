<?php

namespace App\AI\Parsers;

final class FeedbackResponseParser
{
    private const MAX_LENGTH = 400;

    private const PREAMBLE_PATTERN = '/^(here (are|is)\b|sure[!,]?|of course[!,]?|certainly[!,]?)/i';

    /**
     * @param  array<string, mixed>  $data  Raw decoded Ollama response body
     * @return string|null Null signals the caller to use fallback
     */
    public function parse(array $data): ?string
    {
        $feedback = trim($data['response'] ?? '');

        if ($feedback === '') {
            return null;
        }

        // Strip any preamble line the model adds despite being told not to
        // e.g. "Here are three encouraging sentences:" or "Sure! ..."
        $lines = explode("\n", $feedback);
        if (preg_match(self::PREAMBLE_PATTERN, trim($lines[0]))) {
            array_shift($lines);
            $feedback = trim(implode("\n", $lines));
        }

        // If the model ignores the word-limit instruction and produces an
        // oversized response, reject it entirely so the caller falls back to
        // a complete, useful deterministic message rather than truncated text.
        if (strlen($feedback) > self::MAX_LENGTH) {
            return null;
        }

        return $feedback;
    }
}
