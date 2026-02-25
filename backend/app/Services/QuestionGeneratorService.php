<?php

namespace App\Services;

use App\AI\Fallbacks\QuestionFallback;
use App\AI\OllamaClient;
use App\AI\Parsers\QuestionResponseParser;
use App\AI\Prompts\QuestionPrompt;
use App\Contracts\QuestionGeneratorContract;
use Illuminate\Support\Facades\Log;

final class QuestionGeneratorService implements QuestionGeneratorContract
{
    public function __construct(
        private readonly OllamaClient $client,
        private readonly QuestionPrompt $prompt,
        private readonly QuestionResponseParser $parser,
        private readonly QuestionFallback $fallback,
    ) {}

    /**
     * @param  string[]  $previousQuestions  Question texts already used in this session.
     * @return array{question: string, correct_answer: int}
     */
    public function generate(int $difficulty, array $previousQuestions = []): array
    {
        ['prompt' => $promptText] = $this->prompt->build($difficulty, $previousQuestions);

        try {
            $response = $this->client->generateJson(
                prompt: $promptText,
                options: ['temperature' => 0.7, 'num_predict' => 120],
            );

            if ($response->failed()) {
                Log::error('Ollama API error', [
                    'status' => $response->status(),
                    'model' => $this->client->getModel(),
                ]);

                return $this->fallback->generate($difficulty);
            }

            return $this->parser->parse($response->json(), $this->client->getModel())
                ?? $this->fallback->generate($difficulty);
        } catch (\Throwable $e) {
            Log::error('Question generation failed', ['error' => $e->getMessage()]);

            return $this->fallback->generate($difficulty);
        }
    }
}
