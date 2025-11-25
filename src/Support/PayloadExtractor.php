<?php

namespace HoudaSlassi\Vantage\Support;

use HoudaSlassi\Vantage\Support\VantageLogger;

/**
 * Simple Payload Extractor
 *
 * Extracts job data for storage - keeps it simple and safe.
 */
class PayloadExtractor
{
    /**
     * Extract job payload as JSON string for storage
     *
     * Gets COMPLETE payload from Laravel's queue - everything!
     *
     * @param $event The queue event (JobProcessing, JobProcessed, or JobFailed)
     * @param bool $force Force extraction regardless of config strategy
     */
    public static function getPayload($event, bool $force = false): ?string
    {
        $config = config('vantage.store_payload');

        // Support legacy boolean config
        if (is_bool($config)) {
            if (!$config) {
                return null;
            }
        } else {
            // New array config
            $enabled = $config['enabled'] ?? true;
            $strategy = $config['strategy'] ?? 'on_failure';

            if (!$enabled || $strategy === 'never') {
                return $force ? self::extractPayloadData($event) : null;
            }

            // If strategy is 'on_failure' and not forced, skip extraction
            if ($strategy === 'on_failure' && !$force) {
                return null;
            }
        }

        return self::extractPayloadData($event);
    }

    /**
     * Internal method to extract payload data from event
     *
     * Separated from getPayload() to support forced extraction
     */
    protected static function extractPayloadData($event): ?string
    {
        try {
            // Get the COMPLETE raw payload from Laravel's queue
            $rawPayload = $event->job->payload();

            // Convert the command object to readable format
            $command = self::getCommand($event);
            $commandData = [];

            if ($command !== null) {
                $commandData = self::extractData($command);
            }

            // Combine everything
            $fullData = [
                'raw_payload' => $rawPayload, // Complete Laravel queue payload
                'command_data' => $commandData, // Extracted command properties
                'job_info' => [
                    'uuid' => method_exists($event->job, 'uuid') ? $event->job->uuid() : null,
                    'job_id' => method_exists($event->job, 'getJobId') ? $event->job->getJobId() : null,
                    'name' => method_exists($event->job, 'resolveName') ? $event->job->resolveName() : null,
                    'queue' => $event->job->getQueue(),
                    'connection' => $event->connectionName ?? null,
                    'attempts' => $event->job->attempts(),
                ],
            ];

            $fullData = self::redactSensitive($fullData);

            // Debug: Log what we're extracting
            if (config('app.debug', false)) {
                VantageLogger::info('PayloadExtractor: Complete payload extracted', [
                    'command_class' => $command ? $command::class : null,
                    'raw_payload_keys' => array_keys($rawPayload),
                    'command_data_keys' => array_keys($commandData),
                    'payload_size' => strlen(json_encode($fullData)),
                ]);
            }

            return json_encode($fullData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } catch (\Throwable $throwable) {
            VantageLogger::error('PayloadExtractor: Failed to extract payload', [
                'error' => $throwable->getMessage(),
                'trace' => $throwable->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Get job command object from event
     */
    protected static function getCommand($event): ?object
    {
        try {
            $payload = $event->job->payload();
            $serialized = $payload['data']['command'] ?? null;

            if (!is_string($serialized)) {
                return null;
            }

            // Use whitelist of allowed classes for security
            $allowedClasses = self::getAllowedClasses();

            try {
                $command = unserialize($serialized, ['allowed_classes' => $allowedClasses]);
            } catch (\Throwable $e) {
                VantageLogger::warning('PayloadExtractor: Unserialization failed', [
                    'error' => $e->getMessage(),
                    'event' => $event::class,
                ]);
                return null;
            }

            return is_object($command) ? $command : null;
        } catch (\Throwable $throwable) {
            VantageLogger::error('PayloadExtractor: Failed to get command', [
                'error' => $throwable->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get whitelist of allowed classes for unserialization
     *
     * By default, allows all classes (Laravel's standard behavior for queue jobs).
     * Users can configure specific classes via 'vantage.allowed_job_classes' for tighter security.
     *
     * Note: PHP's unserialize() doesn't support wildcards, so patterns are not supported.
     * Either provide specific class names or use default (allow all trusted job classes).
     */
    protected static function getAllowedClasses(): bool|array
    {
        $configClasses = config('vantage.allowed_job_classes');

        // If not configured, allow all classes (Laravel's default for queue jobs)
        // This is safe because only trusted jobs should be in the queue
        if ($configClasses === null) {
            return true;
        }

        // If user explicitly set to empty array, don't allow any classes
        if ($configClasses === []) {
            VantageLogger::warning('PayloadExtractor: No classes allowed for unserialization - payload extraction disabled');
            return false;
        }

        // If user provided specific classes, use them
        if (is_array($configClasses)) {
            return $configClasses;
        }

        // Fallback to allow all
        return true;
    }

    /**
     * Extract data from command object
     *
     * Gets ALL properties (public, protected, private) from the job.
     * Saves EVERYTHING - no filtering!
     */
    protected static function extractData(object $command): array
    {
        $data = [];

        try {
            $reflectionClass = new \ReflectionClass($command);

            // Get ALL properties (public, protected, private) - NO FILTERING!
            foreach ($reflectionClass->getProperties() as $reflectionProperty) {
                $key = $reflectionProperty->getName();
                $value = $reflectionProperty->getValue($command);
                $data[$key] = self::convertValue($value);
            }
        } catch (\Throwable) {
            // If reflection fails, fallback to public properties only
            foreach (get_object_vars($command) as $key => $value) {
                $data[$key] = self::convertValue($value);
            }
        }

        return $data;
    }

    /**
     * Convert value to JSON-safe format
     *
     * Handles scalars, arrays, objects, models safely.
     */
    protected static function convertValue($value)
    {
        // Simple values
        if (is_null($value) || is_scalar($value)) {
            return $value;
        }

        // Arrays
        if (is_array($value)) {
            return array_map(self::convertValue(...), $value);
        }

        // Eloquent models
        if (is_object($value) && method_exists($value, 'getKey') && method_exists($value, 'getTable')) {
            $modelData = [
                'model' => $value::class,
                'id' => $value->getKey(),
            ];

            // Try to get some attributes for context
            try {
                $attributes = $value->getAttributes();
                // Only include a few key attributes to avoid huge payloads
                $keyAttributes = ['name', 'email', 'title', 'slug'];
                foreach ($keyAttributes as $keyAttribute) {
                    if (isset($attributes[$keyAttribute])) {
                        $modelData[$keyAttribute] = $attributes[$keyAttribute];
                    }
                }
            } catch (\Throwable) {
                // Ignore attribute access errors
            }

            return $modelData;
        }

        // Collections
        if (is_object($value) && method_exists($value, 'toArray')) {
            try {
                return self::convertValue($value->toArray());
            } catch (\Throwable) {
                return ['class' => $value::class, 'type' => 'collection'];
            }
        }

        // DateTime objects
        if ($value instanceof \DateTimeInterface) {
            return [
                'class' => $value::class,
                'date' => $value->format('Y-m-d H:i:s'),
                'timezone' => $value->getTimezone()->getName(),
            ];
        }

        // Other objects - try to get some properties
        if (is_object($value)) {
            $objectData = ['class' => $value::class];

            try {
                $reflectionClass = new \ReflectionClass($value);
                $properties = $reflectionClass->getProperties(\ReflectionProperty::IS_PUBLIC);

                foreach ($properties as $property) {
                    $propName = $property->getName();
                    $propValue = $property->getValue($value);

                    // Only include simple properties to avoid recursion
                    if (is_null($propValue) || is_scalar($propValue)) {
                        $objectData[$propName] = $propValue;
                    }
                }
            } catch (\Throwable) {
                // Ignore reflection errors
            }

            return $objectData;
        }

        return null;
    }

    /**
     * Redact sensitive keys from data
     *
     * Removes passwords, tokens, secrets, etc.
     */
    protected static function redactSensitive(array $data): array
    {
        $sensitiveKeys = config('vantage.redact_keys', [
            'password', 'token', 'secret', 'api_key', 'access_token'
        ]);

        foreach ($data as $key => &$value) {
            // Check if key is sensitive
            if (in_array(strtolower((string) $key), array_map(strtolower(...), $sensitiveKeys))) {
                $data[$key] = '[REDACTED]';
            }
            // Recursively check nested arrays
            elseif (is_array($value)) {
                $data[$key] = self::redactSensitive($value);
            }
        }

        return $data;
    }
}
