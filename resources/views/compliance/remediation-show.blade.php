<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $plan->title }} - Remediation Plan</title>
    <script src="{{ asset('js/tailwind.min.js') }}"></script>
    <link href="{{ asset('fonts/inter.css') }}" rel="stylesheet">
    <style nonce="{{ $cspNonce }}">
        * { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex items-center justify-between mb-8">
            <div>
                <nav class="text-sm text-gray-500 mb-2">
                    <a href="/projects/{{ $project->id }}/compliance/remediations" class="hover:text-gray-700">Remediation Plans</a>
                    <span class="mx-2">/</span>
                    <span class="text-gray-900">{{ $plan->title }}</span>
                </nav>
                <h1 class="text-2xl font-bold text-gray-900">{{ $plan->title }}</h1>
                <p class="text-gray-600 mt-1">Remediation plan details</p>
            </div>
            <a href="/projects/{{ $project->id }}/compliance/remediations"
               class="text-blue-600 hover:text-blue-900 font-medium">← Back</a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Plan Details</h3>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Type:</span>
                        <span class="text-sm font-medium">{{ ucfirst($plan->treatment_type ?? 'N/A') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Status:</span>
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $plan->status === 'completed' ? 'bg-emerald-100 text-emerald-800' : ($plan->status === 'overdue' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800') }}">
                            {{ str_replace('_', ' ', ucfirst($plan->status)) }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Target Date:</span>
                        <span class="text-sm font-medium">{{ $plan->target_date?->format('M d, Y') ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Completion Date:</span>
                        <span class="text-sm font-medium">{{ $plan->completion_date?->format('M d, Y') ?? '—' }}</span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Risk Context</h3>
                @if($plan->risk)
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Risk:</span>
                            <a href="/projects/{{ $project->id }}/risk-register/{{ $plan->risk->id }}/edit" class="text-sm text-blue-600 hover:text-blue-900 font-medium">
                                {{ $plan->risk->risk_title ?? 'View Risk' }}
                            </a>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Risk Score:</span>
                            <span class="text-sm font-medium">{{ $plan->risk->inherent_score ?? '—' }}</span>
                        </div>
                    </div>
                @else
                    <p class="text-sm text-gray-400">No linked risk record.</p>
                @endif
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Notes</h3>
            <p class="text-sm text-gray-600 whitespace-pre-wrap">{{ $plan->notes ?? 'No notes provided.' }}</p>
        </div>

        @if($plan->status !== 'completed')
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Close Plan</h3>
                <form method="POST" action="/projects/{{ $project->id }}/compliance/remediations/{{ $plan->id }}/close">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Resolution Notes</label>
                        <textarea name="notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Describe how this was resolved..."></textarea>
                    </div>
                    <button type="submit" class="px-6 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium transition-colors shadow-sm">
                        Mark as Completed
                    </button>
                </form>
            </div>
        @endif
    </div>
</body>
</html>