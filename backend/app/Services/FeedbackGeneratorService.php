<?php

namespace App\Services;

use App\AI\Fallbacks\FeedbackFallback;
use App\AI\OllamaClient;
use App\AI\Parsers\FeedbackResponseParser;
use App\AI\Prompts\FeedbackPrompt;
use App\Contracts\FeedbackGeneratorContract;
use Illuminate\Support\Facades\Log;

final class FeedbackGeneratorService implements FeedbackGeneratorContract
{
    public function __construct(
        private readonly OllamaClient $client,
        private readonly FeedbackPrompt $prompt,
        private readonly FeedbackResponseParser $parser,
        private readonly FeedbackFallback $fallback,
    ) {}

    #[\Override]
    public function explain(string $question, int $correctAnswer, int $studentAnswer): string
    {
        // Safety net: never call Ollama for a correct answer.
        // generateFeedbackIfWrong() in SubmitAnswerService is the primary guard,
        // but this ensures no AI hit if this method is ever called incorrectly.
        if ($studentAnswer === $correctAnswer) {
            return '';
        }

        $promptText = $this->prompt->build($question, $correctAnswer, $studentAnswer);

        try {
            // 15s timeout — feedback is best-effort; if Ollama is slow or cold,
            // we fall back to a deterministic message rather than blocking the student.
            $response = $this->client->generate(
                prompt: $promptText,
                options: ['temperature' => 0.6, 'num_predict' => 150],
                timeout: 15,
            );

            if ($response->failed()) {
                Log::error('Ollama API error for feedback', [
                    'status' => $response->status(),
                    'model' => $this->client->getModel(),
                ]);

                return $this->fallback->generate($correctAnswer, $studentAnswer);
            }

            return $this->parser->parse($response->json())
                ?? $this->fallback->generate($correctAnswer, $studentAnswer);
        } catch (\Throwable $e) {
            Log::error('Feedback generation failed', ['error' => $e->getMessage()]);

            return $this->fallback->generate($correctAnswer, $studentAnswer);
        }
    }
}
