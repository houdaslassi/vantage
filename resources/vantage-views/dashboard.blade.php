@extends('vantage::layout')

@section('title', 'Dashboard')

@section('content')
<!-- Header with Period Selector -->
<div class="mb-6 flex justify-between items-center">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Dashboard</h2>
        <p class="text-sm text-gray-500 mt-1">Real-time queue job monitoring</p>
    </div>
    <div class="flex gap-3 items-center">
        <button onclick="location.reload()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
            🔄 Refresh
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
                <div class="text-4xl opacity-80">📦</div>
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
                <div class="text-4xl opacity-80">✅</div>
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
                <div class="text-4xl opacity-80">❌</div>
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
                <div class="text-4xl opacity-80 animate-pulse">⏳</div>
            </div>
        </div>
    </div>

    <!-- Success Rate -->
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
                <div class="text-4xl opacity-80">{{ $stats['success_rate'] >= 95 ? '🎯' : ($stats['success_rate'] >= 80 ? '📊' : '⚠️') }}</div>
            </div>
        </div>
    </div>
</div>

<!-- Success Rate Trend Chart -->
<div class="bg-white shadow rounded-lg mb-8">
    <div class="px-4 py-5 sm:p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">📈 Success Rate Trend</h3>
        <div class="h-64" id="trendChart">
            <canvas id="successRateChart"></canvas>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-8">
    <!-- Top Tags -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">🏷️ Top Tags</h3>
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
                            <div class="flex gap-2 text-xs">
                                @if($tagData['processed'] > 0)
                                    <span class="text-green-600">✅ {{ $tagData['processed'] }}</span>
                                @endif
                                @if($tagData['failed'] > 0)
                                    <span class="text-red-600">❌ {{ $tagData['failed'] }}</span>
                                @endif
                                @if($tagData['processing'] > 0)
                                    <span class="text-yellow-600">⏳ {{ $tagData['processing'] }}</span>
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
            <h3 class="text-lg font-medium text-gray-900 mb-4">❌ Top Failing Jobs</h3>
            @if($topFailingJobs->isEmpty())
                <p class="text-gray-500 text-center py-4">No failures! 🎉</p>
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
            <h3 class="text-lg font-medium text-gray-900 mb-4">⚠️ Top Exceptions</h3>
            @if($topExceptions->isEmpty())
                <p class="text-gray-500 text-center py-4">No exceptions! 🎉</p>
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
            <h3 class="text-lg font-medium text-gray-900 mb-4">🐌 Slowest Jobs</h3>
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

<!-- Recent Jobs -->
<div class="bg-white shadow rounded-lg">
    <div class="px-4 py-5 sm:p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium text-gray-900">Recent Jobs</h3>
            <a href="{{ route('vantage.jobs') }}" class="text-sm text-indigo-600 hover:text-indigo-800">
                View all →
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

