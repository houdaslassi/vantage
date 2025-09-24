<?php

return [
    'store_full_payload' => false,
    'redact_keys' => ['password', 'token', 'authorization', 'secret'],
    'retention_days' => 14,
    'notify_on_failure' => true,
    'notification_channels' => ['mail'],
    'routes' => false,
    'notify' => [
        'email' => env('QUEUE_MONITOR_NOTIFY_EMAIL', null),
        'slack_webhook' => env('QUEUE_MONITOR_SLACK_WEBHOOK', null),
    ],
];
