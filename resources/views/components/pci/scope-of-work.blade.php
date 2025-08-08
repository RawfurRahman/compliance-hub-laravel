<div class="bg-white shadow rounded-lg p-6 mb-8">
    <h2 class="text-xl font-semibold text-gray-800 mb-4">Description of Scope of Work and Approach Taken</h2>
    <div class="space-y-6">
        <div>
            <label for="scope_validation_activities" class="block text-sm font-medium text-gray-700">Assessor's Validation Activities</label>
            <div x-show="!isEditing" class="mt-1 p-2 bg-gray-50 rounded-md border" x-text="details.scope_validation_activities || 'Not provided.'"></div>
            <div x-show="isEditing">
                <textarea name="scope_validation_activities" id="scope_validation_activities" rows="4" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" x-model="details.scope_validation_activities"></textarea>
            </div>
        </div>
        <div>
            <label for="scope_excluded_areas" class="block text-sm font-medium text-gray-700">Excluded Areas</label>
            <div x-show="!isEditing" class="mt-1 p-2 bg-gray-50 rounded-md border" x-text="details.scope_excluded_areas || 'Not provided.'"></div>
            <div x-show="isEditing">
                <textarea name="scope_excluded_areas" id="scope_excluded_areas" rows="4" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" x-model="details.scope_excluded_areas"></textarea>
            </div>
        </div>
        <div>
            <label for="scope_reduction_factors" class="block text-sm font-medium text-gray-700">Scope Reduction Factors</label>
            <div x-show="!isEditing" class="mt-1 p-2 bg-gray-50 rounded-md border" x-text="details.scope_reduction_factors || 'Not provided.'"></div>
            <div x-show="isEditing">
                <textarea name="scope_reduction_factors" id="scope_reduction_factors" rows="4" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" x-model="details.scope_reduction_factors"></textarea>
            </div>
        </div>
        <div>
            <label for="saq_eligibility" class="block text-sm font-medium text-gray-700">SAQ Eligibility Considerations</label>
            <div x-show="!isEditing" class="mt-1 p-2 bg-gray-50 rounded-md border" x-text="details.saq_eligibility || 'Not provided.'"></div>
            <div x-show="isEditing">
                <textarea name="saq_eligibility" id="saq_eligibility" rows="4" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" x-model="details.saq_eligibility"></textarea>
            </div>
        </div>
    </div>
</div>
