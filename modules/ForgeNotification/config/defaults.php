<?php

declare(strict_types=1);

return [
    "forge_notification" => [
        "email" => [],
        "sms" => [
            "account_sid" => env('TWILIO_ACCOUNT_SID', 'your-secret-sid'),
            "auth_token" => env('TWILIO_AUTH_TOKEN', 'your-auth-token'),
            "from" => env('TWILIO_FROM', '+0000000'),
        ],
        "push" => []
    ]
];
