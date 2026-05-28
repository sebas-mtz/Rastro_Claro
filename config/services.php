<?php

// ============================================================
// Agrega estas entradas a tu config/services.php existente
// ============================================================

return [

    // ... tus otros servicios (mailgun, ses, etc.) ...

    /*
    |--------------------------------------------------------------------------
    | Stripe
    |--------------------------------------------------------------------------
    */
    'stripe' => [
        'key'             => env('STRIPE_KEY'),
        'secret'          => env('STRIPE_SECRET'),
        'webhook_secret'  => env('STRIPE_WEBHOOK_SECRET'),
        'currency'        => env('STRIPE_CURRENCY', 'mxn'),
        'premium_price'   => env('PREMIUM_PRICE', 19900), // en centavos
    ],

    /*
    |--------------------------------------------------------------------------
    | OpenAI (usado por PrediccionController)
    |--------------------------------------------------------------------------
    */
    'openai' => [
        'key' => env('OPENAI_API_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Google (Socialite)
    |--------------------------------------------------------------------------
    */
    'google' => [
        'client_id'     => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect'      => env('GOOGLE_REDIRECT_URI'),
    ],

];
