@extends('vantage::layout')

@section('title', 'Tags Analytics')

@section('content')
<div class="mb-6 flex justify-between items-center">
    <h2 class="text-2xl font-bold text-gray-900">Tags Analytics</h2>
    <div class="flex gap-2">
        @foreach(['24h' => '24 Hours', '7d' => '7 Days', '30d' => '30 Days'] as $key => $label)
            <a href="?period={{ $key }}" 
               class="px-3 py-2 text-sm font-medium rounded-md {{ $period === $key ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>
</div>

<!-- Tags Table -->
<div class="bg-white shadow rounded-lg overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tag</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Jobs</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Processed</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Failed</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Success Rate</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Duration</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($tagStats as $tag => $stats)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium bg-indigo-100 text-indigo-800">
                            🏷️ {{ $tag }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
                        {{ number_format($stats['total']) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600">
                        {{ number_format($stats['processed']) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600">
                        {{ number_format($stats['failed']) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @php
                            $rate = $stats['success_rate'];
                            $color = $rate >= 95 ? 'green' : ($rate >= 80 ? 'yellow' : 'red');
                        @endphp
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $color }}-100 text-{{ $color }}-800">
                            {{ $rate }}%
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        @if($stats['avg_duration'] > 0)
                            @if($stats['avg_duration'] < 1000)
                                {{ round($stats['avg_duration']) }}ms
                            @else
                                {{ round($stats['avg_duration'] / 1000, 2) }}s
                            @endif
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                        No tagged jobs found in this period
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if(!empty($tagStats))
    <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
        <p class="text-sm text-blue-800">
            💡 <strong>Tip:</strong> Use tags in your jobs to categorize them! Add a <code class="bg-blue-100 px-1 rounded">tags()</code> method to your job class.
        </p>
    </div>
@endif
@endsection

