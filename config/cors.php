<?php

$origins = collect(explode(',', (string) env('FRONTEND_URL', 'http://localhost:5173,http://127.0.0.1:5173,https://inventory-lilac-alpha.vercel.app')))
    ->map(fn ($origin) => rtrim(trim($origin), '/'))
    ->filter(fn ($origin) => filter_var($origin, FILTER_VALIDATE_URL))
    ->values()
    ->all();

return [
    'paths' => ['api/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => $origins,
    'allowed_origins_patterns' => ['#^https://[a-z0-9-]+\.vercel\.app$#i'],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];
