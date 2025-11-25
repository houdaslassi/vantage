<?php

namespace HoudaSlassi\Vantage\Support;

/**
 * Simple static context to keep per-job runtime baselines in memory
 * keyed by job UUID. Used for CPU deltas and other metrics we don't
 * persist as separate start columns.
 *
 * Includes TTL-based cleanup to prevent memory leaks in long-running workers.
 */
class JobPerformanceContext
{
    protected static array $baselines = [];

    protected static int $maxAge = 3600;

     // 1 hour TTL
    protected static int $lastCleanup = 0;

    protected static int $cleanupInterval = 300; // Cleanup every 5 minutes

    public static function setBaseline(string $uuid, array $data): void
    {
        // Periodic cleanup to prevent memory leaks
        self::periodicCleanup();

        self::$baselines[$uuid] = [
            'data' => $data,
            'timestamp' => time(),
        ];
    }

    public static function getBaseline(string $uuid): ?array
    {
        $entry = self::$baselines[$uuid] ?? null;

        if ($entry === null) {
            return null;
        }

        // Check if baseline has expired
        if (time() - $entry['timestamp'] > self::$maxAge) {
            unset(self::$baselines[$uuid]);
            return null;
        }

        return $entry['data'];
    }

    public static function clearBaseline(string $uuid): void
    {
        unset(self::$baselines[$uuid]);
    }

    /**
     * Cleanup stale baselines older than maxAge
     */
    protected static function periodicCleanup(): void
    {
        $now = time();

        // Only run cleanup every cleanupInterval seconds
        if ($now - self::$lastCleanup < self::$cleanupInterval) {
            return;
        }

        $cutoff = $now - self::$maxAge;
        $beforeCount = count(self::$baselines);

        self::$baselines = array_filter(
            self::$baselines,
            fn(array $entry): bool => $entry['timestamp'] > $cutoff
        );

        $afterCount = count(self::$baselines);
        $cleaned = $beforeCount - $afterCount;

        if ($cleaned > 0) {
            VantageLogger::debug('JobPerformanceContext: Cleaned stale baselines', [
                'cleaned' => $cleaned,
                'remaining' => $afterCount,
            ]);
        }

        self::$lastCleanup = $now;
    }

    /**
     * Force cleanup of all baselines (useful for testing)
     */
    public static function clearAll(): void
    {
        self::$baselines = [];
        self::$lastCleanup = 0;
    }

    /**
     * Get statistics about baseline storage
     */
    public static function getStats(): array
    {
        return [
            'count' => count(self::$baselines),
            'memory_bytes' => strlen(serialize(self::$baselines)),
            'oldest_timestamp' => self::$baselines === []
                ? null
                : min(array_column(self::$baselines, 'timestamp')),
        ];
    }
}



