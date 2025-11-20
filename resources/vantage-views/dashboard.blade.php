@extends('vantage::layout')

@section('title', 'Dashboard')

@section('content')
<!-- Header with Period Selector -->
<div class="mb-6 flex justify-between items-center">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Dashboard</h2>
        <p class="text-sm text-gray-500 mt-1">Strategic queue monitoring and observability</p>
    </div>
    <div class="flex gap-3 items-center">
        <button onclick="location.reload()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 inline-flex items-center gap-2">
            <i data-lucide="refresh-cw" class="w-4 h-4" aria-hidden="true"></i>
            Refresh
        </button>
        <form method="GET" class="inline-block">
            <select name="period" onchange="this.form.submit()" class="px-4 py-2 text-sm font-medium border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                <option value="1h" {{ $period === '1h' ? 'selected' : '' }}>Last Hour</option>
                <option value="6h" {{ $period === '6h' ? 'selected' : '' }}>Last 6 Hours</option>
                <option value="24h" {{ $period === '24h' ? 'selected' : '' }}>Last 24 Hours</option>
                <option value="7d" {{ $period === '7d' ? 'selected' : '' }}>Last 7 Days</option>
                <option value="30d" {{ $period === '30d' ? 'selected' : '' }}>Last 30 Days</option>
                <option value="all" {{ $period === 'all' ? 'selected' : '' }}>All Time</option>
            </select>
        </form>
    </div>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
    <!-- Total Jobs -->
    <div class="bg-gradient-to-br from-blue-50 to-blue-100 overflow-hidden shadow-lg rounded-xl border border-blue-200 hover:shadow-xl transition-shadow">
        <div class="p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-blue-600 uppercase tracking-wide">Total Jobs</p>
                    <p class="text-3xl font-bold text-blue-900 mt-2">{{ number_format($stats['total']) }}</p>
                </div>
                <div class="text-4xl opacity-80">
                    <i data-lucide="package" class="w-10 h-10 text-blue-500" aria-hidden="true"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Processed -->
    <div class="bg-gradient-to-br from-green-50 to-green-100 overflow-hidden shadow-lg rounded-xl border border-green-200 hover:shadow-xl transition-shadow">
        <div class="p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-green-600 uppercase tracking-wide">Processed</p>
                    <p class="text-3xl font-bold text-green-900 mt-2">{{ number_format($stats['processed']) }}</p>
                    @if($stats['total'] > 0)
                        <p class="text-xs text-green-600 mt-1">{{ round(($stats['processed'] / $stats['total']) * 100, 1) }}% of total</p>
                    @endif
                </div>
                <div class="text-4xl opacity-80">
                    <i data-lucide="check-circle" class="w-10 h-10 text-green-500" aria-hidden="true"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Failed -->
    <div class="bg-gradient-to-br from-red-50 to-red-100 overflow-hidden shadow-lg rounded-xl border border-red-200 hover:shadow-xl transition-shadow">
        <div class="p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-red-600 uppercase tracking-wide">Failed</p>
                    <p class="text-3xl font-bold text-red-900 mt-2">{{ number_format($stats['failed']) }}</p>
                    @if($stats['total'] > 0)
                        <p class="text-xs text-red-600 mt-1">{{ round(($stats['failed'] / $stats['total']) * 100, 1) }}% of total</p>
                    @endif
                </div>
                <div class="text-4xl opacity-80">
                    <i data-lucide="x-circle" class="w-10 h-10 text-red-500" aria-hidden="true"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Processing -->
    <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 overflow-hidden shadow-lg rounded-xl border border-yellow-200 hover:shadow-xl transition-shadow">
        <div class="p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-yellow-600 uppercase tracking-wide">Processing</p>
                    <p class="text-3xl font-bold text-yellow-900 mt-2">{{ number_format($stats['processing']) }}</p>
                    <p class="text-xs text-yellow-600 mt-1">Currently running</p>
                </div>
                <div class="text-4xl opacity-80 animate-pulse">
                    <i data-lucide="clock" class="w-10 h-10 text-yellow-500" aria-hidden="true"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Rate -->
    @php
        $successIcon = $stats['success_rate'] >= 95 ? 'target' : ($stats['success_rate'] >= 80 ? 'bar-chart-2' : 'alert-triangle');
        $successIconColor = $stats['success_rate'] >= 95 ? 'text-emerald-500' : ($stats['success_rate'] >= 80 ? 'text-amber-500' : 'text-rose-500');
    @endphp
    <div class="bg-gradient-to-br {{ $stats['success_rate'] >= 95 ? 'from-emerald-50 to-emerald-100 border-emerald-200' : ($stats['success_rate'] >= 80 ? 'from-amber-50 to-amber-100 border-amber-200' : 'from-rose-50 to-rose-100 border-rose-200') }} overflow-hidden shadow-lg rounded-xl border hover:shadow-xl transition-shadow">
        <div class="p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium {{ $stats['success_rate'] >= 95 ? 'text-emerald-600' : ($stats['success_rate'] >= 80 ? 'text-amber-600' : 'text-rose-600') }} uppercase tracking-wide">Success Rate</p>
                    <p class="text-3xl font-bold {{ $stats['success_rate'] >= 95 ? 'text-emerald-900' : ($stats['success_rate'] >= 80 ? 'text-amber-900' : 'text-rose-900') }} mt-2">{{ $stats['success_rate'] }}%</p>
                    <p class="text-xs {{ $stats['success_rate'] >= 95 ? 'text-emerald-600' : ($stats['success_rate'] >= 80 ? 'text-amber-600' : 'text-rose-600') }} mt-1">
                        @if($stats['success_rate'] >= 95)
                            Excellent!
                        @elseif($stats['success_rate'] >= 80)
                            Good
                        @else
                            Needs attention
                        @endif
                    </p>
                </div>
                <div class="text-4xl opacity-80">
                    <i data-lucide="{{ $successIcon }}" class="w-10 h-10 {{ $successIconColor }}" aria-hidden="true"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Performance Stats Cards -->
@php
    $hasPerformanceData = $performanceStats['avg_memory_peak_end_bytes'] !== null || $performanceStats['avg_cpu_total_ms'] !== null;
@endphp
@if($hasPerformanceData)
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Average Memory Usage -->
    @if($performanceStats['avg_memory_peak_end_bytes'] !== null)
    <div class="bg-gradient-to-br from-purple-50 to-purple-100 overflow-hidden shadow-lg rounded-xl border border-purple-200 hover:shadow-xl transition-shadow">
        <div class="p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-purple-600 uppercase tracking-wide">Avg Memory</p>
                    <p class="text-2xl font-bold text-purple-900 mt-2">
                        @php
                            $bytes = $performanceStats['avg_memory_peak_end_bytes'];
                            if ($bytes < 1024 * 1024) {
                                echo round($bytes / 1024, 2) . ' KB';
                            } elseif ($bytes < 1024 * 1024 * 1024) {
                                echo round($bytes / (1024 * 1024), 2) . ' MB';
                            } else {
                                echo round($bytes / (1024 * 1024 * 1024), 2) . ' GB';
                            }
                        @endphp
                    </p>
                    <p class="text-xs text-purple-600 mt-1">Peak usage</p>
                </div>
                <div class="text-4xl opacity-80">
                    <i data-lucide="hard-drive" class="w-10 h-10 text-purple-500" aria-hidden="true"></i>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Peak Memory Usage -->
    @if($performanceStats['max_memory_peak_end_bytes'] !== null)
    <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 overflow-hidden shadow-lg rounded-xl border border-indigo-200 hover:shadow-xl transition-shadow">
        <div class="p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-indigo-600 uppercase tracking-wide">Peak Memory</p>
                    <p class="text-2xl font-bold text-indigo-900 mt-2">
                        @php
                            $bytes = $performanceStats['max_memory_peak_end_bytes'];
                            if ($bytes < 1024 * 1024) {
                                echo round($bytes / 1024, 2) . ' KB';
                            } elseif ($bytes < 1024 * 1024 * 1024) {
                                echo round($bytes / (1024 * 1024), 2) . ' MB';
                            } else {
                                echo round($bytes / (1024 * 1024 * 1024), 2) . ' GB';
                            }
                        @endphp
                    </p>
                    <p class="text-xs text-indigo-600 mt-1">Maximum observed</p>
                </div>
                <div class="text-4xl opacity-80">
                    <i data-lucide="trending-up" class="w-10 h-10 text-indigo-500" aria-hidden="true"></i>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Average CPU Time -->
    @if($performanceStats['avg_cpu_total_ms'] !== null)
    <div class="bg-gradient-to-br from-cyan-50 to-cyan-100 overflow-hidden shadow-lg rounded-xl border border-cyan-200 hover:shadow-xl transition-shadow">
        <div class="p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-cyan-600 uppercase tracking-wide">Avg CPU Time</p>
                    <p class="text-2xl font-bold text-cyan-900 mt-2">
                        @php
                            $ms = $performanceStats['avg_cpu_total_ms'];
                            if ($ms < 1000) {
                                echo round($ms) . 'ms';
                            } else {
                                echo round($ms / 1000, 2) . 's';
                            }
                        @endphp
                    </p>
                    <p class="text-xs text-cyan-600 mt-1">User + System</p>
                </div>
                <div class="text-4xl opacity-80">
                    <i data-lucide="zap" class="w-10 h-10 text-cyan-500" aria-hidden="true"></i>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Memory Efficiency -->
    @if($performanceStats['avg_memory_start_bytes'] !== null && $performanceStats['avg_memory_end_bytes'] !== null)
    <div class="bg-gradient-to-br from-teal-50 to-teal-100 overflow-hidden shadow-lg rounded-xl border border-teal-200 hover:shadow-xl transition-shadow">
        <div class="p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-teal-600 uppercase tracking-wide">Memory Delta</p>
                    <p class="text-2xl font-bold text-teal-900 mt-2">
                        @php
                            $delta = $performanceStats['avg_memory_end_bytes'] - $performanceStats['avg_memory_start_bytes'];
                            $absDelta = abs($delta);
                            $sign = $delta >= 0 ? '+' : '-';
                            if ($absDelta < 1024 * 1024) {
                                echo $sign . round($absDelta / 1024, 2) . ' KB';
                            } elseif ($absDelta < 1024 * 1024 * 1024) {
                                echo $sign . round($absDelta / (1024 * 1024), 2) . ' MB';
                            } else {
                                echo $sign . round($absDelta / (1024 * 1024 * 1024), 2) . ' GB';
                            }
                        @endphp
                    </p>
                    <p class="text-xs text-teal-600 mt-1">Avg change per job</p>
                </div>
                <div class="text-4xl opacity-80">
                    <i data-lucide="bar-chart-2" class="w-10 h-10 text-teal-500" aria-hidden="true"></i>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endif

<!-- Queue Depths -->
<div class="bg-white shadow rounded-lg mb-8 p-6">
    <h3 class="text-lg font-medium text-gray-900 mb-4 inline-flex items-center gap-2">
        <i data-lucide="bar-chart-2" class="w-5 h-5 text-gray-500" aria-hidden="true"></i>
        Queue Depths (Real-time)
    </h3>
    @if(!empty($queueDepths))
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($queueDepths as $queueName => $queueInfo)
            <div class="border rounded-lg p-4 {{ $queueInfo['status'] === 'critical' ? 'border-red-300 bg-red-50' : ($queueInfo['status'] === 'warning' ? 'border-yellow-300 bg-yellow-50' : ($queueInfo['status'] === 'normal' ? 'border-blue-300 bg-blue-50' : 'border-green-300 bg-green-50')) }}">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">{{ $queueName ?: 'default' }}</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($queueInfo['depth']) }}</p>
                        <p class="text-xs text-gray-500 mt-1">
                            {{ ucfirst($queueInfo['status']) }}
                            @if(isset($queueInfo['driver']) && $queueInfo['driver'])
                                &middot; {{ $queueInfo['driver'] }}
                            @endif
                        </p>
                    </div>
                    @php
                        $statusColor = match($queueInfo['status']) {
                            'critical' => 'text-red-500',
                            'warning' => 'text-yellow-500',
                            'normal' => 'text-blue-500',
                            default => 'text-green-500',
                        };
                    @endphp
                    <div class="text-3xl">
                        <i data-lucide="circle" class="w-6 h-6 {{ $statusColor }}" aria-hidden="true"></i>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-8">
            <p class="text-sm text-gray-500 mb-2">No queue depths available</p>
            <p class="text-xs text-gray-400">
                @if(config('queue.default') === 'sync')
                    Queue depth is not available for the 'sync' driver (jobs run immediately)
                @else
                    No pending jobs found or queue driver may not be supported
                @endif
            </p>
        </div>
    @endif
</div>

<!-- Success Rate Trend Chart -->
<div class="bg-white shadow rounded-lg mb-8">
    <div class="px-4 py-5 sm:p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4 inline-flex items-center gap-2">
            <i data-lucide="trending-up" class="w-5 h-5 text-gray-500" aria-hidden="true"></i>
            Success Rate Trend
        </h3>
        <div class="h-64" id="trendChart">
            <canvas id="successRateChart"></canvas>
        </div>
    </div>
</div>

<!-- Performance Metrics Section -->
@if($hasPerformanceData && ($topMemoryJobs->isNotEmpty() || $topCpuJobs->isNotEmpty()))
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Top Memory Consuming Jobs -->
    @if($topMemoryJobs->isNotEmpty())
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4 inline-flex items-center gap-2">
                <i data-lucide="hard-drive" class="w-5 h-5 text-gray-500" aria-hidden="true"></i>
                Top Memory Consumers
            </h3>
            <div class="space-y-3">
                @foreach($topMemoryJobs as $job)
                    <div class="flex items-center justify-between border rounded-lg p-3 hover:bg-gray-50">
                        <div class="flex-1 min-w-0">
                            <span class="text-sm font-medium text-gray-900 truncate block" title="{{ $job->job_class }}">
                                {{ Str::limit(class_basename($job->job_class), 30) }}
                            </span>
                            <span class="text-xs text-gray-500">{{ number_format($job->count) }} jobs</span>
                        </div>
                        <div class="ml-4 text-right">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                @php
                                    $bytes = $job->avg_memory_peak;
                                    if ($bytes < 1024 * 1024) {
                                        echo round($bytes / 1024, 2) . ' KB';
                                    } elseif ($bytes < 1024 * 1024 * 1024) {
                                        echo round($bytes / (1024 * 1024), 2) . ' MB';
                                    } else {
                                        echo round($bytes / (1024 * 1024 * 1024), 2) . ' GB';
                                    }
                                @endphp
                            </span>
                            <p class="text-xs text-gray-500 mt-1">avg peak</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <!-- Top CPU Consuming Jobs -->
    @if($topCpuJobs->isNotEmpty())
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4 inline-flex items-center gap-2">
                <i data-lucide="zap" class="w-5 h-5 text-gray-500" aria-hidden="true"></i>
                Top CPU Consumers
            </h3>
            <div class="space-y-3">
                @foreach($topCpuJobs as $job)
                    <div class="flex items-center justify-between border rounded-lg p-3 hover:bg-gray-50">
                        <div class="flex-1 min-w-0">
                            <span class="text-sm font-medium text-gray-900 truncate block" title="{{ $job->job_class }}">
                                {{ Str::limit(class_basename($job->job_class), 30) }}
                            </span>
                            <span class="text-xs text-gray-500">{{ number_format($job->count) }} jobs</span>
                        </div>
                        <div class="ml-4 text-right">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-cyan-100 text-cyan-800">
                                @php
                                    $ms = $job->avg_cpu_total;
                                    if ($ms < 1000) {
                                        echo round($ms) . 'ms';
                                    } else {
                                        echo round($ms / 1000, 2) . 's';
                                    }
                                @endphp
                            </span>
                            <p class="text-xs text-gray-500 mt-1">avg total</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-8">
    <!-- Top Tags -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4 inline-flex items-center gap-2">
                <i data-lucide="tag" class="w-5 h-5 text-gray-500" aria-hidden="true"></i>
                Top Tags
            </h3>
            @if($topTags->isEmpty())
                <p class="text-gray-500 text-center py-4">No tags found</p>
            @else
                <div class="space-y-3">
                    @foreach($topTags as $tagData)
                        <div class="border rounded-lg p-3 hover:bg-gray-50">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-900 truncate" title="{{ $tagData['tag'] }}">
                                    {{ Str::limit($tagData['tag'], 20) }}
                                </span>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    {{ number_format($tagData['total']) }}
                                </span>
                            </div>
                            <div class="flex gap-3 text-xs">
                                @if($tagData['processed'] > 0)
                                    <span class="text-green-600 inline-flex items-center gap-1">
                                        <i data-lucide="check-circle" class="w-4 h-4" aria-hidden="true"></i>
                                        {{ $tagData['processed'] }}
                                    </span>
                                @endif
                                @if($tagData['failed'] > 0)
                                    <span class="text-red-600 inline-flex items-center gap-1">
                                        <i data-lucide="x-circle" class="w-4 h-4" aria-hidden="true"></i>
                                        {{ $tagData['failed'] }}
                                    </span>
                                @endif
                                @if($tagData['processing'] > 0)
                                    <span class="text-yellow-600 inline-flex items-center gap-1">
                                        <i data-lucide="clock" class="w-4 h-4" aria-hidden="true"></i>
                                        {{ $tagData['processing'] }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Top Failing Jobs -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4 inline-flex items-center gap-2">
                <i data-lucide="x-octagon" class="w-5 h-5 text-gray-500" aria-hidden="true"></i>
                Top Failing Jobs
            </h3>
            @if($topFailingJobs->isEmpty())
                <p class="text-gray-500 text-center py-4 flex items-center justify-center gap-2">
                    <i data-lucide="star" class="w-4 h-4 text-amber-500" aria-hidden="true"></i>
                    No failures!
                </p>
            @else
                <div class="space-y-3">
                    @foreach($topFailingJobs as $job)
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-900 truncate" title="{{ $job->job_class }}">
                                {{ Str::limit(class_basename($job->job_class), 25) }}
                            </span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                {{ number_format($job->failure_count) }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Top Exceptions -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4 inline-flex items-center gap-2">
                <i data-lucide="alert-triangle" class="w-5 h-5 text-gray-500" aria-hidden="true"></i>
                Top Exceptions
            </h3>
            @if($topExceptions->isEmpty())
                <p class="text-gray-500 text-center py-4 flex items-center justify-center gap-2">
                    <i data-lucide="star" class="w-4 h-4 text-amber-500" aria-hidden="true"></i>
                    No exceptions!
                </p>
            @else
                <div class="space-y-3">
                    @foreach($topExceptions as $exception)
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-900 truncate" title="{{ $exception->exception_class }}">
                                {{ Str::limit(class_basename($exception->exception_class), 25) }}
                            </span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                {{ number_format($exception->count) }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Slowest Jobs -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4 inline-flex items-center gap-2">
                <i data-lucide="timer" class="w-5 h-5 text-gray-500" aria-hidden="true"></i>
                Slowest Jobs
            </h3>
            @if($slowestJobs->isEmpty())
                <p class="text-gray-500 text-center py-4">No data yet</p>
            @else
                <div class="space-y-3">
                    @foreach($slowestJobs as $job)
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-900 truncate" title="{{ $job->job_class }}">
                                {{ Str::limit(class_basename($job->job_class), 25) }}
                            </span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                @if($job->avg_duration < 1000)
                                    {{ round($job->avg_duration) }}ms
                                @else
                                    {{ round($job->avg_duration / 1000, 2) }}s
                                @endif
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Recent Batches -->
@if($recentBatches->isNotEmpty())
<div class="bg-white shadow rounded-lg mb-8">
    <div class="px-4 py-5 sm:p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4 inline-flex items-center gap-2">
            <i data-lucide="package" class="w-5 h-5 text-gray-500" aria-hidden="true"></i>
            Recent Batches
        </h3>
        <div class="space-y-3">
            @foreach($recentBatches as $batch)
                <div class="border rounded-lg p-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-900">{{ $batch->name ?? 'Unnamed Batch' }}</span>
                        @if($batch->cancelled_at)
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Cancelled</span>
                        @elseif($batch->finished_at)
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Completed</span>
                        @else
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Running</span>
                        @endif
                    </div>
                    <div class="flex items-center gap-4 text-sm text-gray-600">
                        <span>{{ $batch->total_jobs }} jobs</span>
                        @if($batch->failed_jobs > 0)
                            <span class="text-red-600">{{ $batch->failed_jobs }} failed</span>
                        @endif
                        @php
                            $progress = $batch->total_jobs > 0 
                                ? round((($batch->total_jobs - $batch->pending_jobs) / $batch->total_jobs) * 100) 
                                : 0;
                        @endphp
                        <span class="text-gray-400">&bull;</span>
                        <span>{{ $progress }}% complete</span>
                    </div>
                    <div class="mt-2 bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $progress }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endif

<!-- Recent Jobs -->
<div class="bg-white shadow rounded-lg">
    <div class="px-4 py-5 sm:p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium text-gray-900">Recent Jobs</h3>
            <a href="{{ route('vantage.jobs') }}" class="text-sm text-indigo-600 hover:text-indigo-800 inline-flex items-center gap-1">
                View all
                <i data-lucide="chevron-right" class="w-4 h-4" aria-hidden="true"></i>
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Job</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Queue</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tags</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($recentJobs as $job)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">#{{ $job->id }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900" title="{{ $job->job_class }}">
                                {{ Str::limit(class_basename($job->job_class), 30) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $job->queue ?? 'default' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($job->job_tags && count($job->job_tags) > 0)
                                    <div class="flex flex-wrap gap-1">
                                        @foreach(array_slice($job->job_tags, 0, 3) as $tag)
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                {{ Str::limit($tag, 15) }}
                                            </span>
                                        @endforeach
                                        @if(count($job->job_tags) > 3)
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                                +{{ count($job->job_tags) - 3 }}
                                            </span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-gray-400 text-xs">No tags</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($job->status === 'processed')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        Processed
                                    </span>
                                @elseif($job->status === 'failed')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                        Failed
                                    </span>
                                @else
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                        Processing
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $job->formatted_duration }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $job->created_at->diffForHumans() }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="{{ route('vantage.jobs.show', $job->id) }}" class="text-indigo-600 hover:text-indigo-900">
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                No jobs yet
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('successRateChart');
    
    // Prepare data
    const hours = @json($jobsByHour->pluck('hour'));
    const totals = @json($jobsByHour->pluck('count'));
    const failures = @json($jobsByHour->pluck('failed_count'));
    
    // Calculate success rates
    const successRates = totals.map((total, index) => {
        if (total === 0) return 0;
        return ((total - failures[index]) / total * 100).toFixed(1);
    });
    
    // Format labels
    const labels = hours.map(h => {
        const date = new Date(h);
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', hour: '2-digit' });
    });
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Success Rate (%)',
                    data: successRates,
                    borderColor: 'rgb(34, 197, 94)',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    tension: 0.4,
                    fill: true,
                    yAxisID: 'y'
                },
                {
                    label: 'Total Jobs',
                    data: totals,
                    borderColor: 'rgb(99, 102, 241)',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    tension: 0.4,
                    fill: true,
                    yAxisID: 'y1'
                },
                {
                    label: 'Failed Jobs',
                    data: failures,
                    borderColor: 'rgb(239, 68, 68)',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    tension: 0.4,
                    fill: true,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                if (context.dataset.label === 'Success Rate (%)') {
                                    label += context.parsed.y + '%';
                                } else {
                                    label += context.parsed.y + ' jobs';
                                }
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Success Rate (%)'
                    },
                    min: 0,
                    max: 100
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Job Count'
                    },
                    grid: {
                        drawOnChartArea: false,
                    },
                }
            }
        }
    });
    
    // Auto-refresh every 30 seconds
    setTimeout(function() {
        location.reload();
    }, 30000);
});
</script>
@endsection

