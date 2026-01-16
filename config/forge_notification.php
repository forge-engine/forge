<?php

declare(strict_types=1);

/**
 * ForgeNotification Configuration
 *
 * This file allows you to customize notification settings.
 * Most settings can be overridden via environment variables.
 *
 * To use environment variables, set them in your .env file:
 * - SMTP_HOST, SMTP_PORT, SMTP_USERNAME, SMTP_PASSWORD, etc.
 * - TWILIO_ACCOUNT_SID, TWILIO_AUTH_TOKEN, TWILIO_FROM
 * - SENDGRID_API_KEY, MAILGUN_API_KEY, etc.
 */

return [
  /*
  |--------------------------------------------------------------------------
  | Default Channel
  |--------------------------------------------------------------------------
  |
  | The default notification channel to use when no channel is specified.
  | Options: 'email', 'sms', 'push'
  |
  */
  'default_channel' => env('NOTIFICATION_DEFAULT_CHANNEL', 'email'),

  /*
  |--------------------------------------------------------------------------
  | Queue Configuration
  |--------------------------------------------------------------------------
  |
  | Configuration for asynchronous notification sending via ForgeEvents.
  |
  */
  'queue' => [
    // Enable queue for notifications (set to false for immediate sending)
    'enabled' => env('NOTIFICATION_QUEUE_ENABLED', true),

    // Queue name for notifications
    'queue_name' => env('NOTIFICATION_QUEUE_NAME', 'notifications'),

    // Priority: 'high', 'normal', 'low'
    'priority' => env('NOTIFICATION_QUEUE_PRIORITY', 'normal'),

    // Maximum number of retries for failed notifications
    'max_retries' => env('NOTIFICATION_MAX_RETRIES', 3),

    // Delay before processing (e.g., '5m', '30s', '2h')
    'delay' => env('NOTIFICATION_QUEUE_DELAY', '0s'),
  ],

  /*
  |--------------------------------------------------------------------------
  | Email Channel Configuration
  |--------------------------------------------------------------------------
  |
  | Configuration for email notifications.
  |
  */
  'channels' => [
    'email' => [
      // Default email provider
      'default_provider' => env('NOTIFICATION_EMAIL_PROVIDER', 'smtp'),

      'providers' => [
        'smtp' => [
          'host' => env('SMTP_HOST', 'localhost'),
          'port' => env('SMTP_PORT', 1025), // Mailpit default: 1025
          'username' => env('SMTP_USERNAME', ''),
          'password' => env('SMTP_PASSWORD', ''),
          'encryption' => env('SMTP_ENCRYPTION', 'none'), // Mailpit: none, tls, ssl
          'from_address' => env('SMTP_FROM_ADDRESS', 'noreply@forge.test'),
          'from_name' => env('SMTP_FROM_NAME', 'Forge Application'),
        ],

        'sendgrid' => [
          'api_key' => env('SENDGRID_API_KEY', ''),
          'from_address' => env('SENDGRID_FROM_ADDRESS', 'noreply@example.com'),
          'from_name' => env('SENDGRID_FROM_NAME', 'Forge Application'),
        ],

        'mailgun' => [
          'domain' => env('MAILGUN_DOMAIN', ''),
          'api_key' => env('MAILGUN_API_KEY', ''),
          'from_address' => env('MAILGUN_FROM_ADDRESS', 'noreply@example.com'),
          'from_name' => env('MAILGUN_FROM_NAME', 'Forge Application'),
        ],
      ],
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS Channel Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for SMS notifications.
    |
    */
    'sms' => [
      // Default SMS provider
      'default_provider' => env('NOTIFICATION_SMS_PROVIDER', 'twilio'),

      'providers' => [
        'twilio' => [
          'account_sid' => env('TWILIO_ACCOUNT_SID', ''),
          'auth_token' => env('TWILIO_AUTH_TOKEN', ''),
          'from' => env('TWILIO_FROM', ''),
        ],

        'vonage' => [
          'api_key' => env('VONAGE_API_KEY', ''),
          'api_secret' => env('VONAGE_API_SECRET', ''),
          'from' => env('VONAGE_FROM', ''),
        ],
      ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Push Notification Channel Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for push notifications.
    |
    */
    'push' => [
      // Default push provider
      'default_provider' => env('NOTIFICATION_PUSH_PROVIDER', 'firebase'),

      'providers' => [
        'firebase' => [
          'server_key' => env('FIREBASE_SERVER_KEY', ''),
          'project_id' => env('FIREBASE_PROJECT_ID', ''),
        ],

        'onesignal' => [
          'app_id' => env('ONESIGNAL_APP_ID', ''),
          'rest_api_key' => env('ONESIGNAL_REST_API_KEY', ''),
        ],
      ],
    ],
  ],
];
