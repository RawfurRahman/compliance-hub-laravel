<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compliance Dashboard - Compliance Hub</title>
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
                <h1 class="text-2xl font-bold text-gray-900">Compliance Dashboard</h1>
                <p class="text-gray-600 mt-1">Overview of compliance status for {{ $project->name }}</p>
            </div>
            <a href="/projects/{{ $project->id }}/compliance/tests"
               class="text-gray-600 hover:text-gray-900 px-4 py-2 rounded-lg font-medium transition-colors border border-gray-300">
                &larr; Back to Tests
            </a>
        </div>

        @if(session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-lg mb-6 text-sm font-medium">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <div class="text-sm text-gray-500 font-medium">Compliant</div>
                <div class="text-2xl font-bold text-emerald-600 mt-1">{{ $counts['compliant'] ?? 0 }}</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <div class="text-sm text-gray-500 font-medium">Partially Compliant</div>
                <div class="text-2xl font-bold text-amber-600 mt-1">{{ $counts['partially_compliant'] ?? 0 }}</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <div class="text-sm text-gray-500 font-medium">Non-Compliant</div>
                <div class="text-2xl font-bold text-red-600 mt-1">{{ $counts['non_compliant'] ?? 0 }}</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <div class="text-sm text-gray-500 font-medium">Overdue</div>
                <div class="text-2xl font-bold text-red-600 mt-1">{{ $counts['overdue'] ?? 0 }}</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <div class="text-sm text-gray-500 font-medium">Waived</div>
                <div class="text-2xl font-bold text-purple-600 mt-1">{{ $counts['waived'] ?? 0 }}</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <div class="text-sm text-gray-500 font-medium">Under Review</div>
                <div class="text-2xl font-bold text-blue-600 mt-1">{{ $counts['under_review'] ?? 0 }}</div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Compliance by Framework</h2>
                @if($byFramework->count() > 0)
                    <div class="space-y-4">
                        @foreach($byFramework as $fw)
                            <div>
                                <div class="flex items-center justify-between text-sm mb-1">
                                    <span class="font-medium text-gray-700">{{ $fw['framework'] }}</span>
                                    <span class="text-gray-500">{{ $fw['compliance_pct'] }}%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="h-2 rounded-full {{ $fw['compliance_pct'] >= 80 ? 'bg-emerald-500' : ($fw['compliance_pct'] >= 50 ? 'bg-amber-500' : 'bg-red-500') }}"
                                         style="width: {{ $fw['compliance_pct'] }}%"></div>
                                </div>
                                <div class="flex gap-3 mt-1 text-xs text-gray-500">
                                    <span>{{ $fw['pass'] }} compliant</span>
                                    <span>{{ $fw['fail'] }} non-compliant</span>
                                    <span>{{ $fw['total'] }} total</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-400 text-center py-8">No framework compliance data available.</p>
                @endif
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Overdue Plans</h2>
                @if($overduePlans->count() > 0)
                    <div class="space-y-3">
                        @foreach($overduePlans as $plan)
                            <div class="flex items-center justify-between border-b border-gray-100 pb-2">
                                <div>
                                    <span class="text-sm font-medium text-gray-900">{{ $plan->title }}</span>
                                    <p class="text-xs text-gray-500">Due: {{ $plan->target_date?->format('M d, Y') ?? 'N/A' }}</p>
                                </div>
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                    Overdue
                                </span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-400 text-center py-8">No overdue items.</p>
                @endif
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Quick Links</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <a href="/projects/{{ $project->id }}/compliance/tests"
                   class="p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors text-center">
                    <div class="font-medium text-gray-900">Compliance Tests</div>
                    <div class="text-sm text-gray-500">Manage test cases</div>
                </a>
                <a href="/projects/{{ $project->id }}/compliance/findings"
                   class="p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors text-center">
                    <div class="font-medium text-gray-900">Findings</div>
                    <div class="text-sm text-gray-500">Review compliance findings</div>
                </a>
                <a href="/projects/{{ $project->id }}/compliance/snapshots"
                   class="p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors text-center">
                    <div class="font-medium text-gray-900">Snapshots</div>
                    <div class="text-sm text-gray-500">Point-in-time reports</div>
                </a>
            </div>
        </div>
    </div>
</body>
</html>