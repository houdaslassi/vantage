@extends('vantage::layout')

@section('title', 'All Jobs')

@section('content')
<!-- Header -->
<div class="mb-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">All Jobs</h1>
            <p class="mt-1 text-sm text-gray-500">Strategic monitoring and filtering of queue jobs</p>
        </div>
        <div class="flex items-center space-x-3">
            <span class="text-sm text-gray-500">{{ $jobs->total() }} total jobs</span>
            <button onclick="location.reload()" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Refresh
            </button>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="bg-white shadow-sm border border-gray-200 rounded-lg mb-6">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">Filters</h3>
        <p class="mt-1 text-sm text-gray-500">Narrow down your job list</p>
    </div>
    <div class="p-6">
        <form id="jobs-filter-form" method="GET" action="{{ route('vantage.jobs') }}" class="space-y-6">
            <!-- First row of filters -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
                    <select name="status" class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                        <option value="">All Statuses</option>
                        <option value="processed" {{ request('status') === 'processed' ? 'selected' : '' }}>✅ Processed</option>
                        <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>❌ Failed</option>
                        <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>⏳ Processing</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Queue</label>
                    <select name="queue" class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                        <option value="">All Queues</option>
                        @foreach($queues as $queue)
                            <option value="{{ $queue }}" {{ request('queue') === $queue ? 'selected' : '' }}>{{ $queue }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Job Class</label>
                    <input type="text" name="job_class" value="{{ request('job_class') }}" placeholder="Search by job class..."
                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                </div>
            </div>

            <!-- Tags section -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Tags</label>
                <div class="space-y-3">
                    <input type="text" name="tags" value="{{ request('tags') }}" placeholder="Enter tags separated by commas (e.g., email, notification, urgent)"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-gray-600">Filter mode:</span>
                        <div class="flex items-center space-x-3">
                            <label class="flex items-center">
                                <input type="radio" name="tag_mode" value="all" {{ request('tag_mode', 'all') === 'all' ? 'checked' : '' }} class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300">
                                <span class="ml-2 text-sm text-gray-700">Must have all tags</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="tag_mode" value="any" {{ request('tag_mode') === 'any' ? 'checked' : '' }} class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300">
                                <span class="ml-2 text-sm text-gray-700">Must have any tag</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action buttons -->
            <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                <div class="flex items-center space-x-4">
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z"></path>
                        </svg>
                        Apply Filters
                    </button>
                    <a href="{{ route('vantage.jobs') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        Clear All
                    </a>
                </div>
                <div class="text-sm text-gray-500">
                    Press <kbd class="px-2 py-1 bg-gray-100 rounded text-xs">Ctrl+K</kbd> to focus tags
                </div>
            </div>
    </form>
</div>

<!-- Tag Cloud -->
@if($allTags->isNotEmpty())
<script>
// Define function globally so it's accessible from onclick
window.addTagToFilter = function(tag) {
    if (!tag) {
        return false;
    }
    
    // Find the tags input in the main filters form
    const form = document.getElementById('jobs-filter-form');
    if (!form) {
        return false;
    }
    
    const tagsInput = form.querySelector('input[name="tags"]');
    if (!tagsInput) {
        return false;
    }
    
    const currentTags = tagsInput.value.trim();
    
    // Normalize tag (already lowercased from PHP, but ensure it's trimmed)
    const normalizedTag = String(tag).toLowerCase().trim();
    
    let newTagsValue;
    if (currentTags === '') {
        newTagsValue = normalizedTag;
    } else {
        const tags = currentTags.split(',').map(t => t.trim().toLowerCase()).filter(t => t);
        if (!tags.includes(normalizedTag)) {
            // Add the tag if it doesn't exist
            newTagsValue = currentTags + ', ' + normalizedTag;
        } else {
            // Tag already exists - remove it (toggle behavior)
            const newTags = tags.filter(t => t !== normalizedTag);
            newTagsValue = newTags.join(', ');
        }
    }
    
    // Set the value
    tagsInput.value = newTagsValue;
    
    // Submit the main form
    form.submit();
    
    return false; // Prevent default
};
</script>
<div class="bg-white shadow-sm border border-gray-200 rounded-lg mb-6">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">Popular Tags</h3>
        <p class="mt-1 text-sm text-gray-500">Click any tag to add it to your filter</p>
    </div>
    <div class="p-6">
        <div class="flex flex-wrap gap-2">
            @foreach($allTags as $tagData)
                @php
                    $tagValue = strtolower($tagData['tag']);
                    $currentTags = request('tags', '');
                    $tagsArray = !empty($currentTags) ? array_map('trim', explode(',', $currentTags)) : [];
                    $isActive = in_array($tagValue, array_map('strtolower', $tagsArray));
                @endphp
                <button type="button" 
                        data-tag="{{ $tagValue }}"
                        onclick="addTagToFilter('{{ addslashes($tagValue) }}')"
                        class="tag-filter-button group inline-flex items-center px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500
                               {{ $isActive ? 'ring-2 ring-indigo-500 ' : '' }}
                               {{ $tagData['failed'] > 0 ? 'bg-red-50 text-red-700 border border-red-200 hover:bg-red-100' : 
                                  ($tagData['processed'] > 0 ? 'bg-green-50 text-green-700 border border-green-200 hover:bg-green-100' : 
                                  'bg-blue-50 text-blue-700 border border-blue-200 hover:bg-blue-100') }}">
                    <span class="mr-2">
                        @if($tagData['failed'] > 0)
                            ❌
                        @elseif($tagData['processed'] > 0)
                            ✅
                        @else
                            🏷️
                        @endif
                    </span>
                    <span>{{ $tagData['tag'] }}</span>
                    <span class="ml-2 px-2 py-0.5 rounded-full text-xs font-semibold {{ $tagData['failed'] > 0 ? 'bg-red-100 text-red-800' : ($tagData['processed'] > 0 ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800') }}">
                        {{ $tagData['total'] }}
                    </span>
                </button>
            @endforeach
        </div>
    </div>
</div>
@endif

<!-- Jobs Table -->
<div class="bg-white shadow rounded-lg">
    <div class="overflow-x-auto">
    <table class="min-w-full w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Job</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Queue</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tags</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Memory</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CPU</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($jobs as $job)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">#{{ $job->id }}</td>
                    <td class="px-6 py-4 text-sm font-medium text-gray-900" title="{{ $job->job_class }}">
                        {{ str(class_basename($job->job_class))->limit(40) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $job->queue ?? 'default' }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        @if($job->job_tags)
                            <div class="flex flex-wrap gap-1">
                                @foreach(array_slice($job->job_tags, 0, 3) as $tag)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                        {{ $tag }}
                                    </span>
                                @endforeach
                                @if(count($job->job_tags) > 3)
                                    <span class="text-xs text-gray-500">+{{ count($job->job_tags) - 3 }}</span>
                                @endif
                            </div>
                        @else
                            <span class="text-gray-400">-</span>
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
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" title="@if($job->memory_peak_end_bytes)Peak: {{ $job->formatted_memory_peak_end }}@endif">
                        @if($job->memory_peak_end_bytes)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-50 text-purple-700">
                                {{ $job->formatted_memory_peak_end }}
                            </span>
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" title="@if($job->cpu_total_ms)User: {{ $job->formatted_cpu_user }}, Sys: {{ $job->formatted_cpu_sys }}@endif">
                        @if($job->cpu_total_ms)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-cyan-50 text-cyan-700">
                                {{ $job->formatted_cpu_total }}
                            </span>
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
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
                    <td colspan="10" class="px-6 py-8 text-center text-gray-500">
                        No jobs found
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>

<!-- Pagination -->
@if($jobs->hasPages())
    <div class="mt-6">
        {{ $jobs->links() }}
    </div>
@endif
@endsection

@section('scripts')
<script>
// Function is defined inline before the Popular Tags section
// This section is kept for any additional scripts

// Add keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + K to focus on tags input
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        const tagsInput = document.querySelector('input[name="tags"]');
        if (tagsInput) {
            tagsInput.focus();
        }
    }
});
</script>
@endsection


