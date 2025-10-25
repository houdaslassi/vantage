<?php

namespace houdaslassi\QueueMonitor\Support;

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
     * Gets public properties from job and stores them safely.
     */
    public static function getPayload($event): ?string
    {
        if (!config('queue-monitor.store_payload', true)) {
            return null;
        }

        try {
            $command = self::getCommand($event);
            
            if (!$command) {
                return null;
            }

            $data = self::extractData($command);
            $data = self::redactSensitive($data);

            // Debug: Log what we're extracting (remove in production)
            if (config('app.debug', false)) {
                \Log::info('PayloadExtractor: Extracted data', [
                    'command_class' => get_class($command),
                    'data_keys' => array_keys($data),
                    'sample_data' => array_slice($data, 0, 3, true)
                ]);
            }

            return json_encode($data, JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            \Log::error('PayloadExtractor: Failed to extract payload', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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

            $command = @unserialize($serialized, ['allowed_classes' => true]);

            return is_object($command) ? $command : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Extract data from command object
     * 
     * Gets public properties and converts to JSON-safe format.
     */
    protected static function extractData(object $command): array
    {
        $data = [];

        // Get all public properties
        foreach (get_object_vars($command) as $key => $value) {
            $data[$key] = self::convertValue($value);
        }

        // Also try to get protected/private properties using reflection
        try {
            $reflection = new \ReflectionClass($command);
            foreach ($reflection->getProperties(\ReflectionProperty::IS_PROTECTED | \ReflectionProperty::IS_PRIVATE) as $property) {
                $property->setAccessible(true);
                $key = $property->getName();
                $value = $property->getValue($command);
                
                // Only add if not already set (public takes precedence)
                if (!isset($data[$key])) {
                    $data[$key] = self::convertValue($value);
                }
            }
        } catch (\Throwable $e) {
            // Ignore reflection errors
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
            return array_map(fn($item) => self::convertValue($item), $value);
        }

        // Eloquent models
        if (is_object($value) && method_exists($value, 'getKey') && method_exists($value, 'getTable')) {
            $modelData = [
                'model' => get_class($value),
                'id' => $value->getKey(),
            ];
            
            // Try to get some attributes for context
            try {
                $attributes = $value->getAttributes();
                // Only include a few key attributes to avoid huge payloads
                $keyAttributes = ['name', 'email', 'title', 'slug'];
                foreach ($keyAttributes as $attr) {
                    if (isset($attributes[$attr])) {
                        $modelData[$attr] = $attributes[$attr];
                    }
                }
            } catch (\Throwable $e) {
                // Ignore attribute access errors
            }
            
            return $modelData;
        }

        // Collections
        if (is_object($value) && method_exists($value, 'toArray')) {
            try {
                return self::convertValue($value->toArray());
            } catch (\Throwable $e) {
                return ['class' => get_class($value), 'type' => 'collection'];
            }
        }

        // DateTime objects
        if ($value instanceof \DateTimeInterface) {
            return [
                'class' => get_class($value),
                'date' => $value->format('Y-m-d H:i:s'),
                'timezone' => $value->getTimezone()->getName(),
            ];
        }

        // Other objects - try to get some properties
        if (is_object($value)) {
            $objectData = ['class' => get_class($value)];
            
            try {
                $reflection = new \ReflectionClass($value);
                $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);
                
                foreach ($properties as $property) {
                    $propName = $property->getName();
                    $propValue = $property->getValue($value);
                    
                    // Only include simple properties to avoid recursion
                    if (is_null($propValue) || is_scalar($propValue)) {
                        $objectData[$propName] = $propValue;
                    }
                }
            } catch (\Throwable $e) {
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
        $sensitiveKeys = config('queue-monitor.redact_keys', [
            'password', 'token', 'secret', 'api_key', 'access_token'
        ]);

        foreach ($data as $key => &$value) {
            // Check if key is sensitive
            if (in_array(strtolower($key), array_map('strtolower', $sensitiveKeys))) {
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
