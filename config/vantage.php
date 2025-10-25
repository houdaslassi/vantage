<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Store Payload
    |--------------------------------------------------------------------------
    |
    | Whether to store job payload for debugging and retry purposes.
    | When enabled, job data will be stored with automatic redaction of
    | sensitive keys. Disable to save database space if not needed.
    |
    */
    'store_payload' => env('VANTAGE_STORE_PAYLOAD', true),

    /*
    |--------------------------------------------------------------------------
    | Redact Sensitive Keys
    |--------------------------------------------------------------------------
    |
    | List of keys that should be redacted from stored payloads for security.
    | These keys will be replaced with '[REDACTED]' in the payload.
    |
    */
    'redact_keys' => [
        'password', 'token', 'authorization', 'secret', 'api_key',
        'apikey', 'access_token', 'refresh_token', 'private_key',
        'card_number', 'cvv', 'ssn', 'credit_card'
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Retention
    |--------------------------------------------------------------------------
    |
    | Number of days to keep job run history. Older records will be pruned.
    | Set to null to keep records indefinitely.
    |
    */
    'retention_days' => 14,

    /*
    |--------------------------------------------------------------------------
    | Failure Notifications
    |--------------------------------------------------------------------------
    |
    | Configure how you want to be notified when jobs fail.
    |
    */
    'notify_on_failure' => true,
    'notification_channels' => ['mail'],
    'notify' => [
        'email' => env('VANTAGE_NOTIFY_EMAIL', null),
        'slack_webhook' => env('VANTAGE_SLACK_WEBHOOK', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Job Tagging
    |--------------------------------------------------------------------------
    |
    | Configure job tagging behavior for better organization and filtering.
    | Tags are extracted from the job's tags() method (Laravel built-in feature).
    |
    */
    'tagging' => [
        // Enable/disable tagging feature
        'enabled' => true,

        // Automatically add tags to all jobs
        'auto_tags' => [
            'environment' => false,  // Adds: env:production, env:local, etc.
            'queue_name' => true,    // Adds: queue:default, queue:emails, etc.
            'hour' => false,         // Adds: hour:14, hour:09, etc.
        ],

        // Maximum number of tags per job
        'max_tags_per_job' => 20,

        // Sanitize tag values (lowercase, trim whitespace)
        'sanitize' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Web Routes
    |--------------------------------------------------------------------------
    |
    | Whether to register web routes for viewing job history.
    | When enabled, routes will be available at /queue-monitor
    |
    */
    'routes' => env('VANTAGE_ROUTES', true),
];
