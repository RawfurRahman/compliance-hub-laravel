<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Remediation Plans - Compliance Hub</title>
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
                <h1 class="text-2xl font-bold text-gray-900">Remediation Plans</h1>
                <p class="text-gray-600 mt-1">Overdue and active remediation plans for {{ $project->name }}</p>
            </div>
            <a href="/projects/{{ $project->id }}/compliance"
               class="text-gray-600 hover:text-gray-900 px-4 py-2 rounded-lg font-medium transition-colors border border-gray-300">
                &larr; Back to Dashboard
            </a>
        </div>

        @if($plans->count() > 0)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Title</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Target Date</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($plans as $plan)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4">
                                    <a href="/projects/{{ $project->id }}/compliance/remediations/{{ $plan->id }}"
                                       class="text-blue-600 hover:text-blue-900 font-medium text-sm">
                                        {{ $plan->title }}
                                    </a>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                        {{ ucfirst($plan->treatment_type ?? 'N/A') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    @php
                                        $stColors = [
                                            'planned' => 'bg-yellow-100 text-yellow-800',
                                            'in_progress' => 'bg-blue-100 text-blue-800',
                                            'completed' => 'bg-emerald-100 text-emerald-800',
                                            'overdue' => 'bg-red-100 text-red-800',
                                        ];
                                    @endphp
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $stColors[$plan->status] ?? 'bg-gray-100 text-gray-800' }}">
                                        {{ str_replace('_', ' ', ucfirst($plan->status)) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    {{ $plan->target_date?->format('M d, Y') ?? '—' }}
                                </td>
                                <td class="px-6 py-4">
                                    <a href="/projects/{{ $project->id }}/compliance/remediations/{{ $plan->id }}"
                                       class="text-blue-600 hover:text-blue-900 text-sm font-medium">View</a>
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
                <h3 class="mt-2 text-sm font-medium text-gray-900">No remediation plans</h3>
                <p class="mt-1 text-sm text-gray-500">All remediation plans are up to date.</p>
            </div>
        @endif
    </div>
</body>
</html>