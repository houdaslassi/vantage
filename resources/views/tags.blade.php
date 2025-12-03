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

<!-- Search Bar -->
<div class="mb-4">
    <input type="text" 
           id="tagSearch" 
           placeholder="Search tags..." 
           class="w-full max-w-md px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
</div>

<!-- Tags Table -->
<div class="bg-white shadow rounded-lg">
    <div class="overflow-x-auto">
    <table id="tagsTable" class="min-w-full w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 sortable" data-column="0">
                    Tag <span class="sort-indicator text-gray-400">--</span>
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 sortable" data-column="1">
                    Total Jobs <span class="sort-indicator text-gray-400">--</span>
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 sortable" data-column="2">
                    Processed <span class="sort-indicator text-gray-400">--</span>
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 sortable" data-column="3">
                    Failed <span class="sort-indicator text-gray-400">--</span>
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 sortable" data-column="4">
                    Processing <span class="sort-indicator text-gray-400">--</span>
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 sortable" data-column="5">
                    Success Rate <span class="sort-indicator text-gray-400">--</span>
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 sortable" data-column="6">
                    Avg Duration <span class="sort-indicator text-gray-400">--</span>
                </th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($tagStats as $tag => $stats)
                <tr class="hover:bg-gray-50 tag-row" data-tag="{{ strtolower($tag) }}">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <a href="{{ route('vantage.jobs', ['tag' => $tag]) }}" 
                           class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-sm font-medium bg-indigo-100 text-indigo-800 hover:bg-indigo-200 transition-colors">
                            <i data-lucide="tag" class="w-4 h-4" aria-hidden="true"></i>
                            {{ $tag }}
                        </a>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium" data-sort="{{ $stats['total'] }}">
                        {{ number_format($stats['total']) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600" data-sort="{{ $stats['processed'] }}">
                        {{ number_format($stats['processed']) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600" data-sort="{{ $stats['failed'] }}">
                        {{ number_format($stats['failed']) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-yellow-600" data-sort="{{ $stats['processing'] ?? 0 }}">
                        {{ number_format($stats['processing'] ?? 0) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap" data-sort="{{ $stats['success_rate'] }}">
                        @php
                            $rate = $stats['success_rate'];
                            $color = $rate >= 95 ? 'green' : ($rate >= 80 ? 'yellow' : 'red');
                        @endphp
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $color }}-100 text-{{ $color }}-800">
                            {{ $rate }}%
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" data-sort="{{ $stats['avg_duration'] }}">
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
                    <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                        No tagged jobs found in this period
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>

@if(!empty($tagStats))
    <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
        <p class="text-sm text-blue-800 inline-flex items-center gap-2">
            <i data-lucide="lightbulb" class="w-4 h-4" aria-hidden="true"></i>
            <span>
                <strong>Tip:</strong> Use tags in your jobs to categorize them! Add a <code class="bg-blue-100 px-1 rounded">tags()</code> method to your job class.
            </span>
        </p>
    </div>
@endif

<script>
// Search functionality
document.getElementById('tagSearch')?.addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('.tag-row');
    
    rows.forEach(row => {
        const tag = row.getAttribute('data-tag');
        if (tag.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

// Sortable columns
let sortDirection = {};
document.querySelectorAll('.sortable').forEach(header => {
    header.addEventListener('click', function() {
        const column = parseInt(this.getAttribute('data-column'));
        const table = document.getElementById('tagsTable');
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr.tag-row'));
        
        // Toggle sort direction
        sortDirection[column] = sortDirection[column] === 'asc' ? 'desc' : 'asc';
        const direction = sortDirection[column];
        
        // Sort rows
        rows.sort((a, b) => {
            const aCell = a.cells[column];
            const bCell = b.cells[column];
            const aValue = parseFloat(aCell.getAttribute('data-sort')) || aCell.textContent.trim();
            const bValue = parseFloat(bCell.getAttribute('data-sort')) || bCell.textContent.trim();
            
            if (direction === 'asc') {
                return aValue > bValue ? 1 : -1;
            } else {
                return aValue < bValue ? 1 : -1;
            }
        });
        
        // Re-append sorted rows
        rows.forEach(row => tbody.appendChild(row));
        
        // Update sort indicators
        document.querySelectorAll('.sort-indicator').forEach(ind => {
            ind.textContent = '--';
        });
        this.querySelector('.sort-indicator').textContent = direction === 'asc' ? '^' : 'v';
    });
});
</script>
@endsection

