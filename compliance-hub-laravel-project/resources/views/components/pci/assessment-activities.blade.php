<div class="bg-white shadow rounded-lg p-6 mb-8">
    <h2 class="text-xl font-semibold text-gray-800 mb-4">Assessment Activities</h2>
    <div x-init="if (!Array.isArray(details.assessment_activities)) { details.assessment_activities = [] }">
        <table class="min-w-full divide-y divide-gray-300 assessment-table">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900">Activity Name</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Date</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Outcome</th>
                    <th scope="col" class="relative py-3.5 pl-3 pr-4" x-show="isEditing"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                <template x-for="(activity, index) in details.assessment_activities" :key="index">
                    <tr>
                        <td x-text="activity.name" x-show="!isEditing"></td>
                        <td x-show="isEditing"><input type="text" x-model="activity.name" :name="'assessment_activities[' + index + '][name]'" class="block w-full border-gray-300 rounded-md shadow-sm"></td>
                        <td x-text="activity.date" x-show="!isEditing"></td>
                        <td x-show="isEditing"><input type="date" x-model="activity.date" :name="'assessment_activities[' + index + '][date]'" class="block w-full border-gray-300 rounded-md shadow-sm"></td>
                        <td x-text="activity.outcome" x-show="!isEditing"></td>
                        <td x-show="isEditing"><textarea x-model="activity.outcome" :name="'assessment_activities[' + index + '][outcome]'" rows="2" class="block w-full border-gray-300 rounded-md shadow-sm"></textarea></td>
                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium" x-show="isEditing">
                            <button type="button" @click="details.assessment_activities.splice(index, 1)" class="text-red-600 hover:text-red-900">Remove</button>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
        <button type="button" @click="details.assessment_activities.push({ name: '', date: '', outcome: '' })" class="mt-4 px-3 py-1 bg-blue-500 text-white rounded-md text-sm hover:bg-blue-600" x-show="isEditing">
            Add Activity
        </button>
    </div>
</div>
