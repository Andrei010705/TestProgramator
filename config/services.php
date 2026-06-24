<?php

return [
    'youtube' => [
        'api_key' => env('YOUTUBE_API_KEY'),
        'search_url' => env('YOUTUBE_SEARCH_URL', 'https://www.googleapis.com/youtube/v3/search'),
    ],
    'ai' => [
        'api_key' => env('AI_API_KEY'),
        'endpoint' => env('AI_API_ENDPOINT', 'https://api.openai.com/v1/chat/completions'),
        'model' => env('AI_MODEL', 'gpt-4o-mini'),
    ],
];
