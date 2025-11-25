<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Enable/Disable Package
    |--------------------------------------------------------------------------
    |
    | Master switch to enable or disable the entire Vantage package.
    | When disabled, no job tracking, routes, or listeners will be active.
    | Useful for testing in staging without affecting production, or
    | temporarily disabling monitoring.
    |
    */
    'enabled' => env('VANTAGE_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Store Payload
    |--------------------------------------------------------------------------
    |
    | Whether to store job payload for debugging and retry purposes.
    | When enabled, job data will be stored with automatic redaction of
    | sensitive keys. Disable to save database space if not needed.
    |
    | Strategy options:
    | - 'always': Extract payload on every job (higher overhead)
    | - 'on_failure': Only extract payload when job fails (recommended for performance)
    | - 'never': Never store payload (reduces storage and overhead)
    |
    */
    'store_payload' => [
        'enabled' => env('VANTAGE_STORE_PAYLOAD', true),
        'strategy' => env('VANTAGE_PAYLOAD_STRATEGY', 'on_failure'), // 'always' | 'on_failure' | 'never'
    ],

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
    | Allowed Job Classes (Security)
    |--------------------------------------------------------------------------
    |
    | List of fully-qualified class names that are allowed during payload
    | unserialization. By default (null), all classes are allowed, which is
    | Laravel's standard behavior for queue jobs.
    |
    | For tighter security in sensitive environments, you can specify exact
    | class names:
    |
    | 'allowed_job_classes' => [
    |     'App\Jobs\SendEmail',
    |     'App\Jobs\ProcessPayment',
    |     'Illuminate\Queue\SerializesModels',
    |     'Illuminate\Database\Eloquent\Model',
    |     // ... add your job classes
    | ],
    |
    | Note: This only affects payload extraction for debugging. Queue execution
    | is not affected. If unsure, leave as null (default: allow all trusted jobs).
    |
    */
    'allowed_job_classes' => null,

    /*
    |--------------------------------------------------------------------------
    | Data Retention
    |--------------------------------------------------------------------------
    |
    | Number of days to keep job run history. Older records will be pruned.
    | Set to null to keep records indefinitely.
    | This value is used as the default for the `vantage:prune` command.
    |
    */
    'retention_days' => env('VANTAGE_RETENTION_DAYS', 14),

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
    | Configure how the dashboard routes are registered.
    | - routes: master switch to register routes at all
    | - route_prefix: base URI segment for all dashboard routes
    |
    */
    'routes' => env('VANTAGE_ROUTES', true),
    'route_prefix' => env('VANTAGE_ROUTE_PREFIX', 'vantage'),

    /*
    |--------------------------------------------------------------------------
    | Performance Telemetry
    |--------------------------------------------------------------------------
    |
    | Configure automatic performance telemetry collection for queued jobs.
    | If disabled, only basic lifecycle fields are stored. Sample rate lets
    | you downsample metrics collection to reduce overhead.
    */
    'telemetry' => [
        'enabled' => env('VANTAGE_TELEMETRY_ENABLED', true),
        'sample_rate' => (float) env('VANTAGE_TELEMETRY_SAMPLE_RATE', 1.0), // 0.0 - 1.0
        'capture_cpu' => env('VANTAGE_TELEMETRY_CPU', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Connection
    |--------------------------------------------------------------------------
    |
    | Specify which database connection to use for storing queue job runs.
    | This is useful when your application uses multiple databases.
    | Set to null to use the default database connection.
    |
    */
    'database_connection' => env('VANTAGE_DATABASE_CONNECTION', null),

    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    |
    | Configure authentication for the Vantage dashboard (similar to Horizon).
    | When enabled, routes will be protected using a gate authorization check.
    |
    | By default, all authenticated users can access Vantage. You can customize
    | the authorization logic by overriding the 'viewVantage' gate in your
    | AppServiceProvider:
    |
    | Gate::define('viewVantage', function ($user) {
    |     return $user->isAdmin(); // or your custom logic
    | });
    |
    | Set to false to disable authentication (not recommended for production).
    |
    */
    'auth' => [
        'enabled' => env('VANTAGE_AUTH_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Toggle package-originated log messages. When disabled, Vantage will refrain
    | from writing informational/debug/warning logs to your application log.
    |
    */
    'logging' => [
        'enabled' => env('VANTAGE_LOGGING_ENABLED', true),
    ],
];
