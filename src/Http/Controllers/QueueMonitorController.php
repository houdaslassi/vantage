<?php

namespace houdaslassi\Vantage\Http\Controllers;

use houdaslassi\Vantage\Models\QueueJobRun;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class QueueMonitorController extends Controller
{
    /**
     * Dashboard - Overview of all jobs
     */
    public function index(Request $request)
    {
        $period = $request->get('period', '30d'); // Changed default to 30 days
        $since = $this->getSinceDate($period);
        
        // Overall statistics
        $stats = [
            'total' => QueueJobRun::where('created_at', '>', $since)->count(),
            'processed' => QueueJobRun::where('created_at', '>', $since)->where('status', 'processed')->count(),
            'failed' => QueueJobRun::where('created_at', '>', $since)->where('status', 'failed')->count(),
            'processing' => QueueJobRun::where('status', 'processing')
                ->where('created_at', '>', now()->subHour()) // Only recent processing jobs
                ->count(),
            'avg_duration' => QueueJobRun::where('created_at', '>', $since)
                ->whereNotNull('duration_ms')
                ->avg('duration_ms'),
        ];
        
        // Calculate success rate based on completed jobs only (processed + failed)
        $completedJobs = $stats['processed'] + $stats['failed'];
        $stats['success_rate'] = $completedJobs > 0 
            ? round(($stats['processed'] / $completedJobs) * 100, 1) 
            : 0;
        
        // Recent jobs
        $recentJobs = QueueJobRun::latest('id')
            ->limit(20)
            ->get();
        
        // Jobs by status (for chart)
        $jobsByStatus = QueueJobRun::select('status', DB::raw('count(*) as count'))
            ->where('created_at', '>', $since)
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');
        
        // Jobs by hour (for trend chart)
        $jobsByHour = QueueJobRun::select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m-%d %H:00:00") as hour'),
                DB::raw('count(*) as count'),
                DB::raw('sum(case when status = "failed" then 1 else 0 end) as failed_count')
            )
            ->where('created_at', '>', $since)
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();
        
        // Top failing jobs
        $topFailingJobs = QueueJobRun::select('job_class', DB::raw('count(*) as failure_count'))
            ->where('created_at', '>', $since)
            ->where('status', 'failed')
            ->groupBy('job_class')
            ->orderByDesc('failure_count')
            ->limit(5)
            ->get();
        
        // Top exceptions
        $topExceptions = QueueJobRun::select('exception_class', DB::raw('count(*) as count'))
            ->where('created_at', '>', $since)
            ->whereNotNull('exception_class')
            ->groupBy('exception_class')
            ->orderByDesc('count')
            ->limit(5)
            ->get();
        
        // Slowest jobs
        $slowestJobs = QueueJobRun::select('job_class', DB::raw('AVG(duration_ms) as avg_duration'), DB::raw('MAX(duration_ms) as max_duration'), DB::raw('count(*) as count'))
            ->where('created_at', '>', $since)
            ->whereNotNull('duration_ms')
            ->groupBy('job_class')
            ->orderByDesc('avg_duration')
            ->limit(5)
            ->get();
        
        // Top tags
        $topTags = QueueJobRun::where('created_at', '>', $since)
            ->whereNotNull('job_tags')
            ->get()
            ->flatMap(function ($job) {
                return collect($job->job_tags)->map(function ($tag) use ($job) {
                    return [
                        'tag' => $tag,
                        'status' => $job->status,
                        'job_class' => $job->job_class,
                    ];
                });
            })
            ->groupBy('tag')
            ->map(function ($jobs, $tag) {
                return [
                    'tag' => $tag,
                    'total' => $jobs->count(),
                    'failed' => $jobs->where('status', 'failed')->count(),
                    'processed' => $jobs->where('status', 'processed')->count(),
                    'processing' => $jobs->where('status', 'processing')->count(),
                ];
            })
            ->sortByDesc('total')
            ->take(10)
            ->values();
        
        return view('vantage::dashboard', compact(
            'stats',
            'recentJobs',
            'jobsByStatus',
            'jobsByHour',
            'topFailingJobs',
            'topExceptions',
            'slowestJobs',
            'topTags',
            'period'
        ));
    }
    
    /**
     * Jobs list with filtering
     */
    public function jobs(Request $request)
    {
        $query = QueueJobRun::query();
        
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
        if ($request->filled('tags')) {
            $tags = is_array($request->tags) ? $request->tags : explode(',', $request->tags);
            $tags = array_map('trim', $tags);
            $tags = array_map('strtolower', $tags);
            
            if ($request->filled('tag_mode') && $request->tag_mode === 'any') {
                // Jobs that have ANY of the specified tags
                $query->where(function($q) use ($tags) {
                    foreach ($tags as $tag) {
                        $q->orWhereJsonContains('job_tags', $tag);
                    }
                });
            } else {
                // Jobs that have ALL of the specified tags (default)
                foreach ($tags as $tag) {
                    $query->whereJsonContains('job_tags', $tag);
                }
            }
        } elseif ($request->filled('tag')) {
            // Single tag filter (backward compatibility)
            $query->whereJsonContains('job_tags', strtolower($request->tag));
        }
        
        if ($request->filled('since')) {
            $query->where('created_at', '>', $request->since);
        }
        
        // Get jobs
        $jobs = $query->latest('id')
            ->paginate(50)
            ->withQueryString();
        
        // Get filter options
        $queues = QueueJobRun::distinct()->pluck('queue')->filter();
        $jobClasses = QueueJobRun::distinct()->pluck('job_class')->map(fn($c) => class_basename($c))->filter();
        
        // Get all available tags with counts
        $allTags = QueueJobRun::whereNotNull('job_tags')
            ->get()
            ->flatMap(function ($job) {
                return collect($job->job_tags)->map(function ($tag) use ($job) {
                    return [
                        'tag' => $tag,
                        'status' => $job->status,
                    ];
                });
            })
            ->groupBy('tag')
            ->map(function ($jobs, $tag) {
                return [
                    'tag' => $tag,
                    'total' => $jobs->count(),
                    'processed' => $jobs->where('status', 'processed')->count(),
                    'failed' => $jobs->where('status', 'failed')->count(),
                    'processing' => $jobs->where('status', 'processing')->count(),
                ];
            })
            ->sortByDesc('total')
            ->take(50); // Limit to top 50 tags
        
        return view('vantage::jobs', compact('jobs', 'queues', 'jobClasses', 'allTags'));
    }
    
    /**
     * Job details
     */
    public function show($id)
    {
        $job = QueueJobRun::findOrFail($id);
        
        // Get retry chain
        $retryChain = [];
        if ($job->retried_from_id) {
            $retryChain = $this->getRetryChain($job);
        }
        
        return view('vantage::show', compact('job', 'retryChain'));
    }
    
    /**
     * Tags statistics
     */
    public function tags(Request $request)
    {
        $period = $request->get('period', '7d');
        $since = $this->getSinceDate($period);
        
        // Get all jobs with tags
        $jobs = QueueJobRun::whereNotNull('job_tags')
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
                        'durations' => [],
                    ];
                }
                
                $tagStats[$tag]['total']++;
                $tagStats[$tag][$job->status]++;
                
                if ($job->duration_ms) {
                    $tagStats[$tag]['durations'][] = $job->duration_ms;
                }
            }
        }
        
        // Calculate averages and success rates
        foreach ($tagStats as $tag => &$stats) {
            $stats['avg_duration'] = !empty($stats['durations']) 
                ? round(array_sum($stats['durations']) / count($stats['durations']), 2)
                : 0;
            
            $stats['success_rate'] = $stats['total'] > 0
                ? round(($stats['processed'] / $stats['total']) * 100, 1)
                : 0;
            
            unset($stats['durations']);
        }
        
        // Sort by total count
        uasort($tagStats, fn($a, $b) => $b['total'] <=> $a['total']);
        
        return view('vantage::tags', compact('tagStats', 'period'));
    }
    
    /**
     * Failed jobs
     */
    public function failed(Request $request)
    {
        $jobs = QueueJobRun::where('status', 'failed')
            ->latest('id')
            ->paginate(50);
        
        return view('vantage::failed', compact('jobs'));
    }
    
    /**
     * Retry a job
     */
    public function retry($id)
    {
        $job = QueueJobRun::findOrFail($id);
        
        if ($job->status !== 'failed') {
            return back()->with('error', 'Only failed jobs can be retried.');
        }
        
        // Call the retry command
        \Artisan::call('vantage:retry', ['run_id' => $id, '--force' => true]);
        
        return back()->with('success', 'Job queued for retry!');
    }
    
    /**
     * Get retry chain
     */
    protected function getRetryChain($job)
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

