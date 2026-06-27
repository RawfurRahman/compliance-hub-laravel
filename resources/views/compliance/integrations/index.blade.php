<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Integrations - Compliance Hub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Integrations</h1>
                <p class="text-gray-600 mt-1">Connect external services to unlock automated compliance tests for {{ $project->name }}</p>
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

        @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 text-sm font-medium">
                {{ session('error') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-5 gap-8">
            {{-- Available Integrations --}}
            <div class="lg:col-span-3">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Available Integrations</h2>
                <p class="text-sm text-gray-500 mb-6">Connect a service to automatically create compliance tests based on its capabilities.</p>

                <div class="space-y-4">
                    @forelse($availableTypes as $type)
                        @php
                            $isConnected = in_array($type->integration_type, $connectedTypes);
                            $displayName = ucwords(str_replace('_', ' ', $type->integration_type));
                            $category = $categories[$type->integration_type] ?? 'General';
                        @endphp
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 flex items-center justify-between {{ $isConnected ? 'opacity-60' : '' }}">
                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-6 h-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900">{{ $displayName }}</h3>
                                    <p class="text-xs text-gray-500 mt-0.5">{{ $category }}</p>
                                    <div class="flex items-center gap-2 mt-2">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700">
                                            Unlocks {{ $type->template_count }} test{{ $type->template_count !== 1 ? 's' : '' }}
                                        </span>
                                        @if($isConnected)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700">
                                                Connected
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @if(!$isConnected)
                                <form method="POST" action="/projects/{{ $project->id }}/compliance/integrations">
                                    @csrf
                                    <input type="hidden" name="name" value="{{ $displayName }}">
                                    <input type="hidden" name="type" value="{{ $type->integration_type }}">
                                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors shadow-sm whitespace-nowrap">
                                        Connect
                                    </button>
                                </form>
                            @else
                                <span class="text-sm text-gray-400 font-medium">Connected</span>
                            @endif
                        </div>
                    @empty
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 text-center">
                            <p class="text-gray-400 text-sm">No integration types available yet. New integrations appear here as test templates are added.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Connected Integrations --}}
            <div class="lg:col-span-2">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Connected</h2>
                <p class="text-sm text-gray-500 mb-6">Integrations currently active for this project.</p>

                <div class="space-y-3">
                    @forelse($connected as $integration)
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="font-medium text-gray-900 text-sm">{{ $integration->name }}</h3>
                                    <p class="text-xs text-gray-500 mt-0.5">{{ ucwords(str_replace('_', ' ', $integration->type)) }}</p>
                                </div>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700">
                                    Active
                                </span>
                            </div>
                            <div class="mt-3 flex items-center justify-between text-xs text-gray-500">
                                <span>{{ $integration->compliance_tests_count ?? 0 }} test{{ ($integration->compliance_tests_count ?? 0) !== 1 ? 's' : '' }} created</span>
                                <a href="/projects/{{ $project->id }}/compliance/tests" class="text-blue-600 hover:text-blue-700 font-medium">
                                    View Tests &rarr;
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 text-center">
                            <svg class="w-10 h-10 text-gray-300 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                            </svg>
                            <p class="text-gray-400 text-sm">No integrations connected yet. Connect one from the available list.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</body>
</html>
