<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compliance Snapshots - Compliance Hub</title>
    <script src="{{ asset('js/tailwind.min.js') }}"></script>
    <link href="{{ asset('fonts/inter.css') }}" rel="stylesheet">
    <style nonce="{{ $cspNonce }}">
        * { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Compliance Snapshots</h1>
                <p class="text-gray-600 mt-1">Point-in-time compliance reports for {{ $project->name }}</p>
            </div>
            <div class="flex gap-3">
                <a href="/projects/{{ $project->id }}/compliance"
                   class="text-gray-600 hover:text-gray-900 px-4 py-2 rounded-lg font-medium transition-colors border border-gray-300">
                    &larr; Back
                </a>
                <button data-snapshot-action="take"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors shadow-sm">
                    + Take Snapshot
                </button>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-lg mb-6 text-sm font-medium">
                {{ session('success') }}
            </div>
        @endif

        @if($snapshots->count() > 0)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Total Controls</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Compliant</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Partial</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Non-Compliant</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Waived</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Overdue</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Compare</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($snapshots as $snapshot)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $snapshot->snapshot_date?->format('M d, Y') ?? $snapshot->created_at->format('M d, Y') }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $snapshot->snapshot_type === 'ondemand' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }}">
                                        {{ ucfirst($snapshot->snapshot_type) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $snapshot->total_controls }}</td>
                                <td class="px-6 py-4 text-sm text-emerald-600 font-medium">{{ $snapshot->compliant_count }}</td>
                                <td class="px-6 py-4 text-sm text-amber-600 font-medium">{{ $snapshot->partial_count }}</td>
                                <td class="px-6 py-4 text-sm text-red-600 font-medium">{{ $snapshot->non_compliant_count }}</td>
                                <td class="px-6 py-4 text-sm text-purple-600 font-medium">{{ $snapshot->waived_count }}</td>
                                <td class="px-6 py-4 text-sm text-red-600 font-medium">{{ $snapshot->overdue_count }}</td>
                                <td class="px-6 py-4">
                                    <input type="checkbox" class="compare-check w-4 h-4 text-blue-600 border-gray-300 rounded" value="{{ $snapshot->id }}">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4 flex justify-end">
                <button data-snapshot-action="compare" class="bg-gray-800 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors" disabled id="compareBtn">
                    Compare Selected
                </button>
            </div>

            <div id="compareResults" class="mt-6 hidden"></div>
        @else
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No snapshots taken</h3>
                <p class="mt-1 text-sm text-gray-500">Take your first snapshot to capture the current compliance state.</p>
                <button data-snapshot-action="take" class="mt-4 inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">Take Snapshot</button>
            </div>
        @endif
    </div>

    <script nonce="{{ $cspNonce }}">
        let selectedIds = [];
        document.querySelectorAll('.compare-check').forEach(cb => {
            cb.addEventListener('change', function() {
                if (this.checked) selectedIds.push(this.value);
                else selectedIds = selectedIds.filter(id => id !== this.value);
                document.getElementById('compareBtn').disabled = selectedIds.length !== 2;
            });
        });

        function compareSnapshots() {
            if (selectedIds.length !== 2) return;
            const [from, to] = selectedIds;
            fetch(`/projects/{{ $project->id }}/compliance/snapshots/${from}/compare/${to}`)
                .then(r => r.json())
                .then(data => {
                    const el = document.getElementById('compareResults');
                    el.classList.remove('hidden');
                    el.innerHTML = '<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6"><h3 class="text-lg font-semibold mb-4">Comparison Result</h3><pre class="text-sm text-gray-600">' + JSON.stringify(data, null, 2) + '</pre></div>';
                });
        }

        function takeSnapshot() {
            fetch('/projects/{{ $project->id }}/compliance/snapshots', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }, body: JSON.stringify({ type: 'ondemand' }) })
                .then(r => r.json())
                .then(() => { window.location.reload(); });
        }

        document.querySelectorAll('[data-snapshot-action]').forEach(btn => {
            btn.addEventListener('click', function () {
                if (this.getAttribute('data-snapshot-action') === 'compare') compareSnapshots();
                else takeSnapshot();
            });
        });
    </script>
</body>
</html>