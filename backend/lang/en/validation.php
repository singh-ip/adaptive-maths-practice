<?php

return [
    'custom' => [
        'session_id' => [
            'required' => 'Session ID is required.',
            'exists' => 'Session not found or already completed.',
        ],
        'question_id' => [
            'required' => 'Question ID is required.',
            'exists' => 'Question not found in session.',
            'unique' => 'This question has already been answered.',
        ],
        'answer' => [
            'required' => 'Answer is required.',
            'regex' => 'Answer must be an integer.',
        ],
    ],

];
