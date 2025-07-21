@props(['details'])

<div class="bg-white shadow rounded-lg p-6 mb-8">
    <h2 class="text-xl font-semibold text-gray-800 mb-4">Overall Findings</h2>
    <div x-data="{ findings: {{ json_encode(optional($details)->overall_findings ?? []) }} }">
        <table class="min-w-full divide-y divide-gray-300 assessment-table">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900">Title</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Description</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Severity</th>
                    <th scope="col" class="relative py-3.5 pl-3 pr-4" x-show="isEditing"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                <template x-for="(finding, index) in findings" :key="index">
                    <tr>
                        <td x-text="finding.title" x-show="!isEditing"></td>
                        <td x-show="isEditing"><input type="text" x-model="finding.title" :name="'overall_findings[' + index + '][title]'" class="block w-full border-gray-300 rounded-md shadow-sm"></td>
                        <td x-text="finding.description" x-show="!isEditing"></td>
                        <td x-show="isEditing"><textarea x-model="finding.description" :name="'overall_findings[' + index + '][description]'" rows="2" class="block w-full border-gray-300 rounded-md shadow-sm"></textarea></td>
                        <td x-text="finding.severity" x-show="!isEditing"></td>
                        <td x-show="isEditing">
                            <select x-model="finding.severity" :name="'overall_findings[' + index + '][severity]'" class="block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">Select Severity</option>
                                <option value="Low">Low</option>
                                <option value="Medium">Medium</option>
                                <option value="High">High</option>
                                <option value="Critical">Critical</option>
                            </select>
                        </td>
                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium" x-show="isEditing">
                            <button type="button" @click="findings.splice(index, 1)" class="text-red-600 hover:text-red-900">Remove</button>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
        <button type="button" @click="findings.push({ title: '', description: '', severity: '' })" class="mt-4 px-3 py-1 bg-blue-500 text-white rounded-md text-sm hover:bg-blue-600" x-show="isEditing">
            Add Finding
        </button>
    </div>
</div>
