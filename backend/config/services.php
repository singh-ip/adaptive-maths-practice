<?php

return [
    'ollama' => [
        'url' => env('OLLAMA_BASE_URL', 'http://ollama:11434'),
        // Use a larger model (e.g. mistral, llama3.1:8b) if you have a GPU.
        'model' => env('OLLAMA_MODEL', 'llama3.2'),
    ],
];
