<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Findings - Compliance Hub</title>
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
                <h1 class="text-2xl font-bold text-gray-900">Audit Findings</h1>
                <p class="text-gray-600 mt-1">Audit findings for {{ $project->name }}</p>
            </div>
            <a href="/projects/{{ $project->id }}/compliance"
               class="text-gray-600 hover:text-gray-900 px-4 py-2 rounded-lg font-medium transition-colors border border-gray-300">
                &larr; Back to Dashboard
            </a>
        </div>

        @if($findings->count() > 0)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Reference</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Title</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Severity</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Audit Date</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Due Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($findings as $finding)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 text-sm font-mono text-gray-500">{{ $finding->finding_reference }}</td>
                                <td class="px-6 py-4">
                                    <a href="/projects/{{ $project->id }}/compliance/audit-findings/{{ $finding->id }}"
                                       class="text-blue-600 hover:text-blue-900 font-medium text-sm">{{ $finding->title }}</a>
                                </td>
                                <td class="px-6 py-4">
                                    @php
                                        $sevColors = ['critical' => 'bg-red-100 text-red-800', 'high' => 'bg-orange-100 text-orange-800', 'medium' => 'bg-yellow-100 text-yellow-800', 'low' => 'bg-gray-100 text-gray-800'];
                                    @endphp
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $sevColors[$finding->severity] ?? 'bg-gray-100 text-gray-800' }}">
                                        {{ ucfirst($finding->severity) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    @php
                                        $stColors = ['open' => 'bg-red-100 text-red-800', 'in_review' => 'bg-blue-100 text-blue-800', 'resolved' => 'bg-emerald-100 text-emerald-800', 'closed' => 'bg-gray-100 text-gray-800'];
                                    @endphp
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $stColors[$finding->status] ?? 'bg-gray-100 text-gray-800' }}">
                                        {{ str_replace('_', ' ', ucfirst($finding->status)) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $finding->audit_date?->format('M d, Y') ?? '—' }}</td>
                                <td class="px-6 py-4 text-sm {{ $finding->is_overdue ? 'text-red-600 font-medium' : 'text-gray-500' }}">
                                    {{ $finding->due_date?->format('M d, Y') ?? '—' }}
                                    @if($finding->is_overdue)
                                        <span class="ml-1 text-xs font-bold text-red-600">OVERDUE</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No audit findings</h3>
                <p class="mt-1 text-sm text-gray-500">No audit findings have been recorded for this project.</p>
            </div>
        @endif
    </div>
</body>
</html>