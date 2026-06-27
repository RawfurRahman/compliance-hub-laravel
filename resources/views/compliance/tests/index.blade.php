<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compliance Tests - Compliance Hub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Compliance Tests</h1>
                <p class="text-gray-600 mt-1">Monitor and manage compliance testing for {{ $project->name }}</p>
            </div>
            <a href="/projects/{{ $project->id }}/compliance/tests/create"
               class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors shadow-sm">
                + Create Test
            </a>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <div class="text-sm text-gray-500 font-medium">Total Tests</div>
                <div class="text-2xl font-bold text-gray-900 mt-1">{{ $summary['total_tests'] }}</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <div class="text-sm text-gray-500 font-medium">Pass Rate</div>
                <div class="text-2xl font-bold text-green-600 mt-1">{{ $summary['passing_percentage'] }}%</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <div class="text-sm text-gray-500 font-medium">Automated / Manual</div>
                <div class="text-2xl font-bold text-gray-900 mt-1">{{ $summary['automated_tests'] }} / {{ $summary['manual_tests'] }}</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <div class="text-sm text-gray-500 font-medium">Overdue</div>
                <div class="text-2xl font-bold text-red-600 mt-1">{{ $summary['overdue_tests'] }}</div>
            </div>
        </div>

        <!-- Filters -->
        <form method="GET" action="/projects/{{ $project->id }}/compliance/tests" class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
            <input type="hidden" name="view" value="{{ $viewMode }}">
            <div class="flex flex-wrap items-end gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Framework</label>
                    <select name="framework_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Frameworks</option>
                        @foreach($frameworks as $fw)
                            <option value="{{ $fw->id }}" {{ ($filterData['framework_id'] ?? '') == $fw->id ? 'selected' : '' }}>{{ $fw->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Owner</label>
                    <select name="owner_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Owners</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ ($filterData['owner_id'] ?? '') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Status</label>
                    <select name="status" class="border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Statuses</option>
                        @foreach(['Passing', 'Overdue', 'Needs Remediation', 'Due Soon', 'Not Yet Run'] as $st)
                            <option value="{{ $st }}" {{ ($filterData['status'] ?? '') == $st ? 'selected' : '' }}>{{ $st }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Test Type</label>
                    <select name="test_type" class="border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Types</option>
                        @foreach(['Automated', 'Manual'] as $tt)
                            <option value="{{ $tt }}" {{ ($filterData['test_type'] ?? '') == $tt ? 'selected' : '' }}>{{ $tt }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="bg-gray-800 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">Apply</button>
                    <a href="/projects/{{ $project->id }}/compliance/tests?view={{ $viewMode }}" class="border border-gray-300 text-gray-600 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors">Clear</a>
                </div>
            </div>
        </form>

        <!-- Segmented View Toggle -->
        @php
            $filterParams = http_build_query(array_filter($filterData, fn($v) => $v !== null && $v !== ''));
        @endphp
        <div class="flex bg-slate-200 p-1 rounded-xl self-start mb-6">
            <a href="?view=all{{ $filterParams ? '&' . $filterParams : '' }}"
               class="px-4 py-2 text-sm font-semibold rounded-lg transition-all {{ $viewMode === 'all' ? 'bg-white text-gray-900 shadow-sm' : 'text-slate-600 hover:text-slate-800' }}">
                All resource monitoring
            </a>
            <a href="?view=by_framework{{ $filterParams ? '&' . $filterParams : '' }}"
               class="px-4 py-2 text-sm font-semibold rounded-lg transition-all {{ $viewMode === 'by_framework' ? 'bg-white text-gray-900 shadow-sm' : 'text-slate-600 hover:text-slate-800' }}">
                By framework
            </a>
        </div>

        <!-- Content -->
        @if($viewMode === 'all')
            {{-- All resource monitoring view --}}
            @if($tests->count() > 0)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Test Name</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Owner</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Frameworks</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Last Run</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($tests as $test)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4">
                                        <a href="/projects/{{ $project->id }}/compliance/tests/{{ $test->id }}" class="text-blue-600 hover:text-blue-900 font-medium">
                                            {{ $test->name }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">{{ $test->ownerUser?->name ?? '—' }}</td>
                                    <td class="px-6 py-4">
                                        @php
                                            $statusColors = [
                                                'Passing' => 'bg-green-100 text-green-800',
                                                'Overdue' => 'bg-red-100 text-red-800',
                                                'Needs Remediation' => 'bg-orange-100 text-orange-800',
                                                'Due Soon' => 'bg-yellow-100 text-yellow-800',
                                                'Not Yet Run' => 'bg-gray-100 text-gray-600',
                                            ];
                                        @endphp
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $statusColors[$test->status] ?? 'bg-gray-100 text-gray-800' }}">
                                            {{ $test->status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $test->test_type === 'Automated' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }}">
                                            {{ $test->test_type }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($test->frameworkLinks as $link)
                                                <span class="inline-flex px-2 py-0.5 text-xs bg-blue-50 text-blue-700 rounded">{{ $link->framework->name }}</span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        {{ $test->last_run_at ? $test->last_run_at->format('M d, Y') : 'Never' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if(method_exists($tests, 'links'))
                    <div class="mt-6">
                        {{ $tests->appends(request()->query())->links() }}
                    </div>
                @endif
            @else
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No compliance tests found</h3>
                    <p class="mt-1 text-sm text-gray-500">Get started by creating a new compliance test.</p>
                    <a href="/projects/{{ $project->id }}/compliance/tests/create" class="mt-4 inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                        Create Test
                    </a>
                </div>
            @endif
        @else
            {{-- By framework view --}}
            @if($frameworkGroups->count() > 0)
                <div class="space-y-6">
                    @foreach($frameworkGroups as $group)
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                            <div class="p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center gap-3">
                                        <h2 class="text-lg font-bold text-gray-900">{{ $group->framework->name }}</h2>
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-50 text-blue-700">
                                            {{ $group->total }} test{{ $group->total !== 1 ? 's' : '' }}
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm text-gray-500">Pass rate:</span>
                                        <span class="text-lg font-bold {{ $group->pass_rate >= 80 ? 'text-green-600' : ($group->pass_rate >= 50 ? 'text-yellow-600' : 'text-red-600') }}">
                                            {{ $group->pass_rate }}%
                                        </span>
                                    </div>
                                </div>

                                <!-- Pass Rate Bar -->
                                <div class="w-full bg-gray-200 rounded-full h-2 mb-4">
                                    <div class="h-2 rounded-full transition-all duration-500 {{ $group->pass_rate >= 80 ? 'bg-green-500' : ($group->pass_rate >= 50 ? 'bg-yellow-500' : 'bg-red-500') }}"
                                         style="width: {{ $group->pass_rate }}%">
                                    </div>
                                </div>

                                <!-- Status Breakdown -->
                                <div class="flex flex-wrap gap-4 text-sm mb-4">
                                    <span class="text-green-700 font-medium">Passing: {{ $group->passing }}</span>
                                    <span class="text-red-700 font-medium">Overdue: {{ $group->overdue }}</span>
                                    <span class="text-orange-700 font-medium">Needs Remediation: {{ $group->failing }}</span>
                                    <span class="text-yellow-700 font-medium">Due Soon: {{ $group->due_soon }}</span>
                                    <span class="text-gray-500 font-medium">Not Yet Run: {{ $group->not_yet_run }}</span>
                                </div>

                                <!-- Expandable Test List -->
                                @if($group->tests->count() > 0)
                                    <div x-data="{ open: false }" class="border-t border-gray-100 pt-4">
                                        <button @click="open = !open" class="flex items-center gap-2 text-sm font-semibold text-gray-600 hover:text-gray-900 transition-colors">
                                            <svg :class="open ? 'rotate-90' : ''" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                            </svg>
                                            View Tests ({{ $group->tests->count() }})
                                        </button>
                                        <div x-show="open" x-collapse class="mt-3">
                                            <table class="min-w-full divide-y divide-gray-200">
                                                <thead class="bg-gray-50">
                                                    <tr>
                                                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Name</th>
                                                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Owner</th>
                                                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                                                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Type</th>
                                                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Last Run</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-200">
                                                    @foreach($group->tests as $test)
                                                        <tr class="hover:bg-gray-50 transition-colors">
                                                            <td class="px-4 py-3">
                                                                <a href="/projects/{{ $project->id }}/compliance/tests/{{ $test->id }}" class="text-blue-600 hover:text-blue-900 font-medium text-sm">
                                                                    {{ $test->name }}
                                                                </a>
                                                            </td>
                                                            <td class="px-4 py-3 text-sm text-gray-600">{{ $test->ownerUser?->name ?? '—' }}</td>
                                                            <td class="px-4 py-3">
                                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $statusColors[$test->status] ?? 'bg-gray-100 text-gray-800' }}">
                                                                    {{ $test->status }}
                                                                </span>
                                                            </td>
                                                            <td class="px-4 py-3">
                                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $test->test_type === 'Automated' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }}">
                                                                    {{ $test->test_type }}
                                                                </span>
                                                            </td>
                                                            <td class="px-4 py-3 text-sm text-gray-500">
                                                                {{ $test->last_run_at ? $test->last_run_at->format('M d, Y') : 'Never' }}
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No framework groups found</h3>
                    <p class="mt-1 text-sm text-gray-500">Tests must be mapped to frameworks to appear in this view.</p>
                </div>
            @endif
        @endif
    </div>

    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</body>
</html>
