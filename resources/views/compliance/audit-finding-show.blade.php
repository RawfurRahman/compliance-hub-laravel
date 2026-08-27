<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $finding->title }} - Audit Finding</title>
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
                    <a href="/projects/{{ $project->id }}/compliance/audit-findings" class="hover:text-gray-700">Audit Findings</a>
                    <span class="mx-2">/</span>
                    <span class="text-gray-900">{{ $finding->title }}</span>
                </nav>
                <h1 class="text-2xl font-bold text-gray-900">{{ $finding->title }}</h1>
                <p class="text-gray-600 mt-1">Reference: {{ $finding->finding_reference }}</p>
            </div>
            <a href="/projects/{{ $project->id }}/compliance/audit-findings"
               class="text-blue-600 hover:text-blue-900 font-medium">← Back</a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="text-sm text-gray-500 font-medium mb-1">Severity</div>
                @php
                    $sevColors = ['critical' => 'text-red-600', 'high' => 'text-orange-600', 'medium' => 'text-yellow-600', 'low' => 'text-gray-600'];
                @endphp
                <div class="text-2xl font-bold {{ $sevColors[$finding->severity] ?? 'text-gray-900' }}">{{ ucfirst($finding->severity) }}</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="text-sm text-gray-500 font-medium mb-1">Status</div>
                <div class="text-2xl font-bold">
                    <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full {{ $finding->status === 'closed' ? 'bg-emerald-100 text-emerald-800' : ($finding->status === 'resolved' ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800') }}">
                        {{ str_replace('_', ' ', ucfirst($finding->status)) }}
                    </span>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="text-sm text-gray-500 font-medium mb-1">Auditor</div>
                <div class="text-lg font-bold text-gray-900">{{ $finding->auditor?->name ?? '—' }}</div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Description</h3>
            <p class="text-sm text-gray-600">{{ $finding->description ?? 'No description provided.' }}</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Timeline</h3>
                <div class="space-y-3">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Audit Date:</span>
                        <span class="font-medium">{{ $finding->audit_date?->format('M d, Y') ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Due Date:</span>
                        <span class="font-medium {{ $finding->is_overdue ? 'text-red-600' : '' }}">{{ $finding->due_date?->format('M d, Y') ?? '—' }}</span>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Linked Controls</h3>
                @if($finding->control)
                    <p class="text-sm text-gray-600">Control: {{ $finding->control->name ?? $finding->control->control_code }}</p>
                @endif
                @if($finding->frameworkControl)
                    <p class="text-sm text-gray-600">Framework Control: {{ $finding->frameworkControl->name ?? $finding->frameworkControl->control_code }}</p>
                @endif
                @if(!$finding->control && !$finding->frameworkControl)
                    <p class="text-sm text-gray-400">No controls linked.</p>
                @endif
            </div>
        </div>

        @if($finding->remediation_plan)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Remediation Plan</h3>
                <p class="text-sm text-gray-600 whitespace-pre-wrap">{{ $finding->remediation_plan }}</p>
            </div>
        @endif

        @if(in_array($finding->status, ['open', 'in_review']))
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Close Finding</h3>
                <form method="POST" action="/projects/{{ $project->id }}/compliance/audit-findings/{{ $finding->id }}/close">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Resolution Notes</label>
                        <textarea name="resolution" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Describe the resolution..."></textarea>
                    </div>
                    <button type="submit" class="px-6 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium transition-colors shadow-sm">Close Finding</button>
                </form>
            </div>
        @endif
    </div>
</body>
</html>