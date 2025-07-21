@props(['details'])

<div class="bg-white shadow rounded-lg p-6 mb-8">
    <h2 class="text-xl font-semibold text-gray-800 mb-4">Date and Timeframe of Assessment</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label for="date_of_report" class="block text-sm font-medium text-gray-700">Date of Report</label>
            <div x-show="!isEditing" class="mt-1 font-semibold text-gray-800">{{ optional($details)->date_of_report ?? 'Not set' }}</div>
            <div x-show="isEditing">
                <input type="date" name="date_of_report" id="date_of_report" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" x-model="details.date_of_report">
            </div>
        </div>
        <div>
            <label for="date_assessment_ended" class="block text-sm font-medium text-gray-700">Date Assessment Ended</label>
            <div x-show="!isEditing" class="mt-1 font-semibold text-gray-800">{{ optional($details)->date_assessment_ended ?? 'Not set' }}</div>
            <div x-show="isEditing">
                <input type="date" name="date_assessment_ended" id="date_assessment_ended" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" x-model="details.date_assessment_ended">
            </div>
        </div>
    </div>
</div>
