<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Compliance Test - Compliance Hub</title>
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
                    <a href="/projects/{{ $project->id }}/compliance/tests" class="hover:text-gray-700">Tests</a>
                    <span class="mx-2">/</span>
                    <span class="text-gray-900">{{ $test->name }}</span>
                </nav>
                <h1 class="text-2xl font-bold text-gray-900">Edit Compliance Test</h1>
                <p class="text-gray-600 mt-1">Update test configuration and mappings</p>
            </div>
            <a href="/projects/{{ $project->id }}/compliance/tests"
               class="text-blue-600 hover:text-blue-900 font-medium">← Back to tests</a>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <form method="POST" action="/projects/{{ $project->id }}/compliance/tests/{{ $test->id }}" class="space-y-6">
                @csrf
                @method('PUT')

                <div>
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Test Information</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Test Name *</label>
                            <input type="text" name="name" value="{{ $test->name }}" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Team *</label>
                            <input type="text" name="team" value="{{ $test->team }}" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Owner *</label>
                            <select name="owner_user_id" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select owner...</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ $test->owner_user_id == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Control Monitor</label>
                            <select name="control_monitor_id"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">None</option>
                                @foreach($controlMonitors as $cm)
                                    <option value="{{ $cm->id }}" {{ $test->control_monitor_id == $cm->id ? 'selected' : '' }}>{{ $cm->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea name="description" rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">{{ $test->description }}</textarea>
                    </div>
                </div>

                <div>
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Test Configuration</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Type *</label>
                            <select name="test_type" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="Automated" {{ $test->test_type === 'Automated' ? 'selected' : '' }}>Automated</option>
                                <option value="Manual" {{ $test->test_type === 'Manual' ? 'selected' : '' }}>Manual</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                            <select name="status" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                @foreach(['Passing', 'Overdue', 'Due Soon', 'Needs Remediation', 'Not Yet Run'] as $st)
                                    <option value="{{ $st }}" {{ $test->status === $st ? 'selected' : '' }}>{{ $st }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Last Run Date</label>
                            <input type="date" name="last_run_at" value="{{ $test->last_run_at?->format('Y-m-d') }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Next Due Date</label>
                            <input type="date" name="next_due_at" value="{{ $test->next_due_at?->format('Y-m-d') }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                </div>

                <div>
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Framework Mapping *</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 max-h-64 overflow-y-auto border border-gray-200 rounded-lg p-4">
                        @php $selectedFrameworks = $test->frameworkLinks->pluck('framework_id')->toArray(); @endphp
                        @foreach($frameworks as $framework)
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input type="checkbox" name="framework_ids[]" value="{{ $framework->id }}"
                                       class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                       {{ in_array($framework->id, $selectedFrameworks) ? 'checked' : '' }}>
                                <span class="text-sm text-gray-700">{{ $framework->name }}</span>
                            </label>
                        @endforeach
                    </div>
                    <p class="text-xs text-gray-500 mt-2">* At least one framework must be selected</p>
                </div>

                <div>
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Failing Entities</h2>
                    <div id="failing-entities-container">
                        @forelse($test->activeFailures() as $i => $failure)
                            <div class="failing-entity-item border border-gray-200 rounded-lg p-4 mb-3">
                                <input type="hidden" name="failing_entities[{{ $i }}][id]" value="{{ $failure->id }}">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Entity Description</label>
                                        <input type="text" name="failing_entities[{{ $i }}][description]" value="{{ $failure->failing_entity_description }}"
                                               class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Detected Date</label>
                                        <input type="date" name="failing_entities[{{ $i }}][detected_at]" value="{{ $failure->detected_at->format('Y-m-d') }}"
                                               class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Resolved Date</label>
                                        <input type="date" name="failing_entities[{{ $i }}][resolved_at]" value="{{ $failure->resolved_at?->format('Y-m-d') }}"
                                               class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-blue-500">
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-6 text-sm text-gray-400">No failing entities recorded.</div>
                        @endforelse
                    </div>
                </div>

                <div class="flex justify-end gap-4 pt-6 border-t border-gray-200">
                    <a href="/projects/{{ $project->id }}/compliance/tests/{{ $test->id }}"
                       class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium">Cancel</a>
                    <button type="submit"
                            class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors font-medium shadow-sm">Update Test</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>