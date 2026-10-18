<?php

return [

    'frontend_url' => env('FRONTEND_URL', 'http://localhost:3000'),

    /*
     * The game top-up provider used for player-name checks and automatic delivery.
     * Register new drivers in App\Providers\AppServiceProvider.
     */
    'provider' => env('GAME_PROVIDER', 'fake'),

    'admin' => [
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
        'username' => env('ADMIN_USERNAME', 'admin'),
    ],

    'rate_limits' => [
        'login' => (int) env('RATE_LIMIT_LOGIN', 5),
        'check_name' => (int) env('RATE_LIMIT_CHECK_NAME', 20),
        'orders' => (int) env('RATE_LIMIT_ORDERS', 10),
    ],

    'currency' => 'USD',

    /* Allow anyone to open /docs/api (otherwise only logged-in admins; always open in local). */
    'api_docs_public' => (bool) env('API_DOCS_PUBLIC', false),

];
