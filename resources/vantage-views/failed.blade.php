@extends('vantage::layout')

@section('title', 'Failed Jobs')

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Failed Jobs</h2>
    <p class="text-gray-500">View and retry failed queue jobs</p>
</div>

<!-- Failed Jobs Table -->
<div class="bg-white shadow rounded-lg overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Job</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Queue</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Exception</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Message</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Failed At</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($jobs as $job)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">#{{ $job->id }}</td>
                    <td class="px-6 py-4 text-sm font-medium text-gray-900" title="{{ $job->job_class }}">
                        {{ Str::limit(class_basename($job->job_class), 30) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $job->queue ?? 'default' }}
                    </td>
                    <td class="px-6 py-4 text-sm text-red-600 font-mono text-xs" title="{{ $job->exception_class }}">
                        {{ Str::limit(class_basename($job->exception_class), 25) }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500" title="{{ $job->exception_message }}">
                        {{ Str::limit($job->exception_message, 50) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $job->finished_at ? $job->finished_at->diffForHumans() : '-' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                        <a href="{{ route('vantage.jobs.show', $job->id) }}" 
                           class="text-indigo-600 hover:text-indigo-900">
                            View
                        </a>
                        <form action="{{ route('vantage.jobs.retry', $job->id) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="text-green-600 hover:text-green-900">
                                Retry
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center">
                        <div class="text-6xl mb-4">🎉</div>
                        <p class="text-gray-500 text-lg">No failed jobs!</p>
                        <p class="text-gray-400 text-sm mt-2">All your queue jobs are running smoothly.</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Pagination -->
@if($jobs->hasPages())
    <div class="mt-6">
        {{ $jobs->links() }}
    </div>
@endif
@endsection

