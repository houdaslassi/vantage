<?php

namespace HoudaSlassi\Vantage\Http\Controllers;

use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use HoudaSlassi\Vantage\Enums\JobStatus;
use HoudaSlassi\Vantage\Models\VantageJob;
use HoudaSlassi\Vantage\Support\QueueDepthChecker;
use HoudaSlassi\Vantage\Support\VantageLogger;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class QueueMonitorController extends Controller
{
    /**
     * Dashboard - Overview of all jobs
     */
    public function index(Request $request): Factory|View
    {
        $period = $request->get('period', '30d');
        $since = $this->getSinceDate($period);

        $stats = [
            'total' => VantageJob::where('created_at', '>', $since)->count(),
            'processed' => VantageJob::where('created_at', '>', $since)->where('status', JobStatus::Processed)->count(),
            'failed' => VantageJob::where('created_at', '>', $since)->where('status', JobStatus::Failed)->count(),
            'processing' => VantageJob::where('status', JobStatus::Processing)
                ->where('created_at', '>', now()->subHour())
                ->count(),
            'avg_duration' => VantageJob::where('created_at', '>', $since)
                ->whereNotNull('duration_ms')
                ->avg('duration_ms'),
        ];

        $completedJobs = $stats['processed'] + $stats['failed'];
        $stats['success_rate'] = $completedJobs > 0
            ? round(($stats['processed'] / $completedJobs) * 100, 1)
            : 0;

        $recentJobs = VantageJob::select([
                'id', 'uuid', 'job_class', 'queue', 'connection', 'attempt',
                'status', 'started_at', 'finished_at', 'duration_ms',
                'exception_class', 'exception_message', 'job_tags', 'retried_from_id',
                'created_at', 'updated_at'
            ])
            ->latest('id')
            ->limit(20)
            ->get();

        $jobsByStatus = VantageJob::select('status', DB::raw('count(*) as count'))
            ->where('created_at', '>', $since)
            ->groupBy('status')
            ->get()
            ->mapWithKeys(fn($item): array => [$item->status->value => $item->count]);

        $connectionName = (new VantageJob)->getConnectionName();
        $connection = DB::connection($connectionName);
        $driver = $connection->getDriverName();

        if ($driver === 'mysql') {
            $dateFormat = DB::raw('DATE_FORMAT(created_at, "%Y-%m-%d %H:00:00") as hour');
        } elseif ($driver === 'sqlite') {
            $dateFormat = DB::raw('strftime("%Y-%m-%d %H:00:00", created_at) as hour');
        } elseif ($driver === 'pgsql') {
            $dateFormat = DB::raw("to_char(created_at, 'YYYY-MM-DD HH24:00:00') as hour");
        } else {
            $dateFormat = DB::raw('DATE_FORMAT(created_at, "%Y-%m-%d %H:00:00") as hour');
        }

        $failedValue = JobStatus::Failed->value;
        $jobsByHour = VantageJob::select(
                $dateFormat,
                DB::raw('count(*) as count'),
                DB::raw("sum(case when status = '{$failedValue}' then 1 else 0 end) as failed_count")
            )
            ->where('created_at', '>', $since)
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        $topFailingJobs = VantageJob::select('job_class', DB::raw('count(*) as failure_count'))
            ->where('created_at', '>', $since)
            ->where('status', JobStatus::Failed)
            ->groupBy('job_class')
            ->orderByDesc('failure_count')
            ->limit(5)
            ->get();

        $topExceptions = VantageJob::select('exception_class', DB::raw('count(*) as count'))
            ->where('created_at', '>', $since)
            ->whereNotNull('exception_class')
            ->groupBy('exception_class')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        $slowestJobs = VantageJob::select('job_class', DB::raw('AVG(duration_ms) as avg_duration'), DB::raw('MAX(duration_ms) as max_duration'), DB::raw('count(*) as count'))
            ->where('created_at', '>', $since)
            ->whereNotNull('duration_ms')
            ->groupBy('job_class')
            ->orderByDesc('avg_duration')
            ->limit(5)
            ->get();

        $topTags = VantageJob::select(['job_tags', 'status', 'job_class'])
            ->where('created_at', '>', $since)
            ->whereNotNull('job_tags')
            ->get()
            ->flatMap(fn($job) => collect($job->job_tags)->map(fn($tag): array => [
                'tag' => $tag,
                'status' => $job->status,
                'job_class' => $job->job_class,
            ]))
            ->groupBy('tag')
            ->map(fn($jobs, $tag): array => [
                'tag' => $tag,
                'total' => $jobs->count(),
                'failed' => $jobs->where('status', JobStatus::Failed)->count(),
                'processed' => $jobs->where('status', JobStatus::Processed)->count(),
                'processing' => $jobs->where('status', JobStatus::Processing)->count(),
            ])
            ->sortByDesc('total')
            ->take(10)
            ->values();

        // Recent batches (if batch table exists)
        $recentBatches = collect();
        if (Schema::hasTable('job_batches')) {
            $recentBatches = DB::table('job_batches')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
        }

        // Queue depths (real-time)
        try {
            $queueDepths = QueueDepthChecker::getQueueDepthWithMetadataAlways();
        } catch (\Throwable $throwable) {
            VantageLogger::warning('Failed to get queue depths', ['error' => $throwable->getMessage()]);
            // Always show at least one queue entry even on error
            $queueDepths = [
                'default' => [
                    'depth' => 0,
                    'driver' => config('queue.default', 'unknown'),
                    'connection' => config('queue.default', 'unknown'),
                    'status' => 'healthy',
                ]
            ];
        }

        // Performance statistics
        $performanceStats = [
            'avg_memory_start_bytes' => VantageJob::where('created_at', '>', $since)
                ->whereNotNull('memory_start_bytes')
                ->avg('memory_start_bytes'),
            'avg_memory_end_bytes' => VantageJob::where('created_at', '>', $since)
                ->whereNotNull('memory_end_bytes')
                ->avg('memory_end_bytes'),
            'avg_memory_peak_end_bytes' => VantageJob::where('created_at', '>', $since)
                ->whereNotNull('memory_peak_end_bytes')
                ->avg('memory_peak_end_bytes'),
            'max_memory_peak_end_bytes' => VantageJob::where('created_at', '>', $since)
                ->whereNotNull('memory_peak_end_bytes')
                ->max('memory_peak_end_bytes'),
            'avg_cpu_user_ms' => VantageJob::where('created_at', '>', $since)
                ->whereNotNull('cpu_user_ms')
                ->avg('cpu_user_ms'),
            'avg_cpu_sys_ms' => VantageJob::where('created_at', '>', $since)
                ->whereNotNull('cpu_sys_ms')
                ->avg('cpu_sys_ms'),
        ];

        // Calculate average total CPU (user + sys)
        $avgCpuTotal = null;
        if ($performanceStats['avg_cpu_user_ms'] !== null || $performanceStats['avg_cpu_sys_ms'] !== null) {
            $avgCpuTotal = ($performanceStats['avg_cpu_user_ms'] ?? 0) + ($performanceStats['avg_cpu_sys_ms'] ?? 0);
        }

        $performanceStats['avg_cpu_total_ms'] = $avgCpuTotal;

        // Top memory-consuming jobs
        $topMemoryJobs = VantageJob::select('job_class', DB::raw('AVG(memory_peak_end_bytes) as avg_memory_peak'), DB::raw('MAX(memory_peak_end_bytes) as max_memory_peak'), DB::raw('count(*) as count'))
            ->where('created_at', '>', $since)
            ->whereNotNull('memory_peak_end_bytes')
            ->groupBy('job_class')
            ->orderByDesc('avg_memory_peak')
            ->limit(5)
            ->get();

        // Top CPU-consuming jobs
        $topCpuJobs = VantageJob::select(
                'job_class',
                DB::raw('AVG(cpu_user_ms) as avg_cpu_user'),
                DB::raw('AVG(cpu_sys_ms) as avg_cpu_sys'),
                DB::raw('AVG(COALESCE(cpu_user_ms, 0) + COALESCE(cpu_sys_ms, 0)) as avg_cpu_total'),
                DB::raw('count(*) as count')
            )
            ->where('created_at', '>', $since)
            ->where(function($query): void {
                $query->whereNotNull('cpu_user_ms')
                      ->orWhereNotNull('cpu_sys_ms');
            })
            ->groupBy('job_class')
            ->orderByDesc('avg_cpu_total')
            ->limit(5)
            ->get();

        return view('vantage::dashboard', ['stats' => $stats, 'recentJobs' => $recentJobs, 'jobsByStatus' => $jobsByStatus, 'jobsByHour' => $jobsByHour, 'topFailingJobs' => $topFailingJobs, 'topExceptions' => $topExceptions, 'slowestJobs' => $slowestJobs, 'topTags' => $topTags, 'recentBatches' => $recentBatches, 'queueDepths' => $queueDepths, 'period' => $period, 'performanceStats' => $performanceStats, 'topMemoryJobs' => $topMemoryJobs, 'topCpuJobs' => $topCpuJobs]);
    }

    /**
     * Jobs list with filtering
     */
    public function jobs(Request $request): Factory|View
    {
        // Exclude large columns (payload, stack) from jobs list to improve performance
        $query = VantageJob::select([
            'id', 'uuid', 'job_class', 'queue', 'connection', 'attempt', 
            'status', 'started_at', 'finished_at', 'duration_ms',
            'exception_class', 'exception_message', 'job_tags', 'retried_from_id',
            'created_at', 'updated_at',
            // Performance telemetry fields
            'memory_start_bytes', 'memory_end_bytes', 'memory_peak_start_bytes',
            'memory_peak_end_bytes', 'memory_peak_delta_bytes',
            'cpu_user_ms', 'cpu_sys_ms'
            // Exclude: payload, stack (large text fields not needed for list view)
        ]);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('job_class')) {
            $query->where('job_class', 'like', '%' . $request->job_class . '%');
        }

        if ($request->filled('queue')) {
            $query->where('queue', $request->queue);
        }

        // Advanced tag filtering
        $tagsParam = $request->get('tags');
        
        // Check if tags parameter exists and is not empty
        if (!empty($tagsParam) && trim((string) $tagsParam) !== '') {
            $tags = is_array($tagsParam) ? $tagsParam : explode(',', (string) $tagsParam);
            $tags = array_map(trim(...), $tags);
            $tags = array_map(strtolower(...), $tags);
            $tags = array_filter($tags); // Remove empty tags

            if ($tags !== []) {
                // Get database driver for database-specific JSON queries
                $connectionName = (new VantageJob)->getConnectionName();
                $connection = DB::connection($connectionName);
                $driver = $connection->getDriverName();

                if ($request->filled('tag_mode') && $request->tag_mode === 'any') {
                    // Jobs that have ANY of the specified tags
                    $query->where(function($q) use ($tags, $driver): void {
                        foreach ($tags as $tag) {
                            if ($driver === 'sqlite') {
                                // SQLite: json_each().value returns the actual string, not JSON-encoded
                                $q->orWhereRaw("EXISTS (
                                    SELECT 1 FROM json_each(vantage_jobs.job_tags) 
                                    WHERE json_each.value = ?
                                )", [$tag]);
                            } else {
                                // MySQL and PostgreSQL support whereJsonContains
                                $q->orWhereJsonContains('job_tags', $tag);
                            }
                        }
                    });
                } else {
                    // Jobs that have ALL of the specified tags (default)
                    foreach ($tags as $tag) {
                        if ($driver === 'sqlite') {
                            // SQLite: json_each().value returns the actual string, not JSON-encoded
                            // So we compare directly to the tag value
                            $query->whereRaw("EXISTS (
                                SELECT 1 FROM json_each(vantage_jobs.job_tags) 
                                WHERE json_each.value = ?
                            )", [$tag]);
                        } else {
                            // MySQL and PostgreSQL
                            $query->whereJsonContains('job_tags', $tag);
                        }
                    }
                }
            }
        } elseif ($request->filled('tag')) {
            // Single tag filter (backward compatibility)
            $tag = strtolower(trim($request->tag));
            $connectionName = (new VantageJob)->getConnectionName();
            $connection = DB::connection($connectionName);
            $driver = $connection->getDriverName();

            if ($driver === 'sqlite') {
                // SQLite: json_each().value returns the actual string, not JSON-encoded
                $query->whereRaw("EXISTS (
                    SELECT 1 FROM json_each(vantage_jobs.job_tags) 
                    WHERE json_each.value = ?
                )", [$tag]);
            } else {
                $query->whereJsonContains('job_tags', $tag);
            }
        }

        if ($request->filled('since')) {
            $query->where('created_at', '>', $request->since);
        }

        // Get jobs
        $jobs = $query->latest('id')
            ->paginate(50)
            ->withQueryString();

        // Get filter options
        // Only show queues that actually have jobs in vantage_jobs table
        // This ensures filtering by a queue will return results
        $queues = VantageJob::distinct()
            ->whereNotNull('queue')
            ->where('queue', '!=', '')
            ->pluck('queue')
            ->filter()
            ->sort()
            ->values();
        
        $jobClasses = VantageJob::distinct()->pluck('job_class')->map(fn($c): string => class_basename($c))->filter();

        // Get all available tags with counts - only select needed columns
        $allTags = VantageJob::select(['job_tags', 'status'])
            ->whereNotNull('job_tags')
            ->get()
            ->flatMap(fn($job) => collect($job->job_tags)->map(fn($tag): array => [
                'tag' => $tag,
                'status' => $job->status,
            ]))
            ->groupBy('tag')
            ->map(fn($jobs, $tag): array => [
                'tag' => $tag,
                'total' => $jobs->count(),
                'processed' => $jobs->where('status', JobStatus::Processed)->count(),
                'failed' => $jobs->where('status', JobStatus::Failed)->count(),
                'processing' => $jobs->where('status', JobStatus::Processing)->count(),
            ])
            ->sortByDesc('total')
            ->take(50); // Limit to top 50 tags

        return view('vantage::jobs', ['jobs' => $jobs, 'queues' => $queues, 'jobClasses' => $jobClasses, 'allTags' => $allTags]);
    }

    /**
     * Job details
     */
    public function show($id): Factory|View
    {
        $job = VantageJob::findOrFail($id);

        // Get retry chain
        $retryChain = [];
        if ($job->retried_from_id) {
            $retryChain = $this->getRetryChain($job);
        }

        return view('vantage::show', ['job' => $job, 'retryChain' => $retryChain]);
    }

    /**
     * Tags statistics
     */
    public function tags(Request $request): Factory|View
    {
        $period = $request->get('period', '7d');
        $since = $this->getSinceDate($period);

        // Get all jobs with tags - only select needed columns
        $jobs = VantageJob::select(['job_tags', 'status', 'duration_ms'])
            ->whereNotNull('job_tags')
            ->where('created_at', '>', $since)
            ->get();

        // Calculate tag statistics
        $tagStats = [];
        foreach ($jobs as $job) {
            foreach ($job->job_tags ?? [] as $tag) {
                if (!isset($tagStats[$tag])) {
                    $tagStats[$tag] = [
                        'total' => 0,
                        'processed' => 0,
                        'failed' => 0,
                        'processing' => 0,
                        'durations' => [],
                    ];
                }

                $tagStats[$tag]['total']++;
                $tagStats[$tag][$job->status->value]++;

                if ($job->duration_ms) {
                    $tagStats[$tag]['durations'][] = $job->duration_ms;
                }
            }
        }

        // Calculate averages and success rates
        foreach ($tagStats as &$tagStat) {
            $tagStat['avg_duration'] = empty($tagStat['durations'])
                ? 0
                : round(array_sum($tagStat['durations']) / count($tagStat['durations']), 2);

            $tagStat['success_rate'] = $tagStat['total'] > 0
                ? round(($tagStat['processed'] / $tagStat['total']) * 100, 1)
                : 0;

            unset($tagStat['durations']);
        }

        // Sort by total count
        uasort($tagStats, fn($a, $b): int => $b['total'] <=> $a['total']);

        return view('vantage::tags', ['tagStats' => $tagStats, 'period' => $period]);
    }

    /**
     * Failed jobs
     */
    public function failed(Request $request): Factory|View
    {
        // Exclude large columns (payload) from failed jobs list
        // Keep stack for debugging, but exclude payload
        $jobs = VantageJob::select([
                'id', 'uuid', 'job_class', 'queue', 'connection', 'attempt',
                'status', 'started_at', 'finished_at', 'duration_ms',
                'exception_class', 'exception_message', 'stack', 'job_tags',
                'retried_from_id', 'created_at', 'updated_at'
                // Exclude: payload (very large, not needed for failed list)
            ])
            ->where('status', JobStatus::Failed)
            ->latest('id')
            ->paginate(50);

        return view('vantage::failed', ['jobs' => $jobs]);
    }

    /**
     * Retry a job - simple and works for all cases
     */
    public function retry($id)
    {
        $run = VantageJob::findOrFail($id);

        if ($run->status !== JobStatus::Failed) {
            return back()->with('error', 'Only failed jobs can be retried.');
        }

        $jobClass = $run->job_class;

        if (!class_exists($jobClass)) {
            return back()->with('error', "Job class {$jobClass} not found.");
        }

        try {
            // Simple: Just unserialize the original job from Laravel's payload
            $job = $this->restoreJobFromPayload($run);

            if ($job === null) {
                return back()->with('error', 'Unable to restore job. Payload might be missing or corrupted.');
            }

            // Mark as retry
            $job->queueMonitorRetryOf = $run->id;

            // Dispatch
            dispatch($job)
                ->onQueue($run->queue ?? 'default')
                ->onConnection($run->connection ?? config('queue.default'));

        return back()->with('success', 'Job queued for retry!');

        } catch (\Throwable $throwable) {
                VantageLogger::error('Vantage: Retry failed', [
                'job_id' => $id,
                'error' => $throwable->getMessage()
            ]);

            return back()->with('error', "Retry failed: " . $throwable->getMessage());
        }
    }

    /**
     * Restore job from the original Laravel serialized payload
     * This is the simplest and most accurate method
     */
    protected function restoreJobFromPayload(VantageJob $vantageJob): ?object
    {
        if (!$vantageJob->payload) {
            return null;
        }

        try {
            $payload = json_decode($vantageJob->payload, true);

            // Get the serialized command from Laravel's raw payload
            $serialized = $payload['raw_payload']['data']['command'] ?? null;

            if (!$serialized) {
                VantageLogger::warning('Vantage: No serialized command in payload', ['run_id' => $vantageJob->id]);
                return null;
            }

            // Unserialize it - Laravel stored it this way originally
            $job = unserialize($serialized, ['allowed_classes' => true]);

            if (!is_object($job)) {
                VantageLogger::warning('Vantage: Unserialize did not return object', [
                    'run_id' => $vantageJob->id,
                    'result_type' => gettype($job)
                ]);
                return null;
            }

            VantageLogger::info('Vantage: Successfully restored job', [
                'run_id' => $vantageJob->id,
                'job_class' => $job::class
            ]);

            return $job;

        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Get retry chain
     */
    protected function getRetryChain($job): array
    {
        $chain = [];
        $current = $job->retriedFrom;

        while ($current) {
            $chain[] = $current;
            $current = $current->retriedFrom;
        }

        return array_reverse($chain);
    }

    /**
     * Get since date from period string
     */
    protected function getSinceDate($period)
    {
        return match($period) {
            '1h' => now()->subHour(),
            '6h' => now()->subHours(6),
            '24h' => now()->subDay(),
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            'all' => now()->subYears(100), // All time
            default => now()->subDays(30),
        };
    }
}

