<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Compliance Test - Compliance Hub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Create Compliance Test</h1>
                <p class="text-gray-600 mt-1">Add a new compliance test that maps to multiple frameworks</p>
            </div>
            <a href="/projects/{{ $project->id }}/compliance/tests" 
               class="text-blue-600 hover:text-blue-900 font-medium">← Back to tests</a>
        </div>

        <!-- Form Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <form method="POST" action="/projects/{{ $project->id }}/compliance/tests" class="space-y-6">
                @csrf

                <!-- Test Information -->
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Test Information</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Test Name *</label>
                            <input type="text" name="name" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="e.g. Public SSH Access Control">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Team *</label>
                            <input type="text" name="team" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="e.g. Security Team">
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea name="description" rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                  placeholder="Describe what this test validates..."></textarea>
                    </div>
                </div>

                <!-- Test Configuration -->
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Test Configuration</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Type *</label>
                            <select name="test_type" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select type...</option>
                                <option value="Automated">Automated</option>
                                <option value="Manual">Manual</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                            <select name="status" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select status...</option>
                                <option value="Passing">Passing</option>
                                <option value="Overdue">Overdue</option>
                                <option value="Due Soon">Due Soon</option>
                                <option value="Needs Remediation">Needs Remediation</option>
                                <option value="Not Yet Run">Not Yet Run</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">SLA Days (nullable)</label>
                            <input type="number" name="sla_days" min="0"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="e.g. 30">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Last Run Date</label>
                            <input type="date" name="last_run_at"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Next Due Date</label>
                            <input type="date" name="next_due_at"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                </div>

                <!-- Framework Mapping -->
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Framework Mapping *</h2>
                    <p class="text-sm text-gray-600 mb-4">Select the frameworks this test applies to (multiple selection allowed)</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 max-h-64 overflow-y-auto border border-gray-200 rounded-lg p-4">
                        @foreach($frameworks as $framework)
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input type="checkbox" name="framework_ids[]" value="{{ $framework->id }}"
                                       class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                <span class="text-sm text-gray-700">{{ $framework->name }}</span>
                            </label>
                        @endforeach
                    </div>
                    <p class="text-xs text-gray-500 mt-2">* Required: At least one framework must be selected</p>
                </div>

                <!-- Failing Entities -->
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Failing Entities (Optional)</h2>
                    <p class="text-sm text-gray-600 mb-4">Add specific resources/items that this test found failing (e.g., AWS EC2 instances, Database servers)</p>
                    <div id="failing-entities-container">
                        <div class="failing-entity-item border border-gray-200 rounded-lg p-4 mb-3">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Entity Description *</label>
                                    <input type="text" name="failing_entities[0][description]" required
                                          class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                                          placeholder="e.g. AWS EC2 Instance i-0fea5e182f5ef814d">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Detected Date *</label>
                                    <input type="date" name="failing_entities[0][detected_at]" required
                                          class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Resolved Date (nullable)</label>
                                    <input type="date" name="failing_entities[0][resolved_at]"
                                          class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                            </div>
                        </div>
                    </div>
                    <button type="button" onclick="addFailingEntity()"
                            class="text-sm bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1 rounded transition-colors">
                        + Add Failing Entity
                    </button>
                </div>

                <!-- Form Actions -->
                <div class="flex justify-end gap-4 pt-6 border-t border-gray-200">
                    <a href="/projects/{{ $project->id }}/compliance/tests"
                       class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium">\n                        Cancel\n                    </a>\n                    <button type="submit"
                            class=\"px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors font-medium shadow-sm\">
                        Create Test
                    </button>\n                </div>\n            </form>\n        </div>\n    </div>\n\n    <script>\n        let failingEntityCount = 1;\n\n        function addFailingEntity() {\n            const container = document.getElementById('failing-entities-container');\n            const newItem = document.createElement('div');\n            newItem.className = 'failing-entity-item border border-gray-200 rounded-lg p-4 mb-3';\n            newItem.innerHTML = `\n                <div class=\"grid grid-cols-1 md:grid-cols-3 gap-3\">\n                    <div>\n                        <label class=\"block text-xs font-medium text-gray-700 mb-1\">Entity Description *</label>\n                        <input type=\"text\" name=\"failing_entities[${failingEntityCount}][description]\" required\n                              class=\"w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500\"\n                              placeholder=\"e.g. AWS EC2 Instance i-0fea5e182f5ef814d\">\n                    </div>\n                    <div>\n                        <label class=\"block text-xs font-medium text-gray-700 mb-1\">Detected Date *</label>\n                        <input type=\"date\" name=\"failing_entities[${failingEntityCount}][detected_at]\" required\n                              class=\"w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500\">\n                    </div>\n                    <div class=\"flex items-end\">\n                        <button type=\"button\" onclick=\"removeFailingEntity(this)\"\n                                class=\"px-3 py-1 text-red-600 hover:text-red-800 text-sm font-medium\">\n                            Remove\n                        </button>\n                    </div>\n                </div>\n            `;\n            container.appendChild(newItem);\n            failingEntityCount++;\n        }\n\n        function removeFailingEntity(button) {\n            button.closest('.failing-entity-item').remove();\n        }\n    </script>\n</body>\n</html>