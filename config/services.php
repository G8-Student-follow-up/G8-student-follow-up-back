<?php

return [

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    // 'github' => [
    //     'client_id' => env('GITHUB_CLIENT_ID'),
    //     'client_secret' => env('GITHUB_CLIENT_SECRET'),
    //     'redirect' => env('GITHUB_REDIRECT_URI'),
    // ],

    /*
    |--------------------------------------------------------------------------
    | Firebase / Firestore (PNC Timetable)
    |--------------------------------------------------------------------------
    |
    | project_id   – Your Firebase project ID (e.g. "timetable-ab826")
    | api_key      – Web API key from Firebase project settings
    | service_account – JSON string of a Firebase service-account private key.
    |                   Required when Firestore security rules require auth;
    |                   without it the service degrades to [] gracefully.
    |
    */
    'pnc_timetable' => [
        'project_id' => env('PNC_TIMETABLE_PROJECT_ID'),
        'api_key' => env('PNC_TIMETABLE_API_KEY'),
        'collection' => env('PNC_TIMETABLE_COLLECTION', 'sessions'),

        // Google Calendar API key — used to fetch actual event data from the
        // PNC public calendars. Separate from the Firebase API key above.
        'calendar_api_key' => env('PNC_CALENDAR_API_KEY'),
    ],

];
