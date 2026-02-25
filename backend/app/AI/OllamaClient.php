<?php

namespace App\AI;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Thin wrapper around the Ollama HTTP API.
 * Owns only transport concerns: base URL, timeout, and the generate endpoint call.
 * All prompt building, response parsing, and fallback logic live elsewhere.
 */
final class OllamaClient
{
    private readonly string $baseUrl;

    private readonly string $model;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.ollama.url'), '/');
        $this->model = config('services.ollama.model');
    }

    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * @param  array<string, mixed>  $options  Ollama model options (temperature, num_predict, etc.)
     */
    public function generate(string $prompt, array $options = [], int $timeout = 30): Response
    {
        return $this->post($prompt, $options, format: null, timeout: $timeout);
    }

    /**
     *
     * @param  array<string, mixed>  $options  Ollama model options (temperature, num_predict, etc.)
     */
    public function generateJson(string $prompt, array $options = [], int $timeout = 30): Response
    {
        return $this->post($prompt, $options, format: 'json', timeout: $timeout);
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private function post(string $prompt, array $options, ?string $format, int $timeout = 30): Response
    {
        $payload = [
            'model' => $this->model,
            'prompt' => $prompt,
            'stream' => false,
            'options' => $options,
        ];

        if ($format !== null) {
            $payload['format'] = $format;
        }

        return Http::timeout($timeout)->post("{$this->baseUrl}/api/generate", $payload);
    }
}
