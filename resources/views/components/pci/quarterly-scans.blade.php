<div class="bg-white shadow rounded-lg p-6 mb-8">
    <h2 class="text-xl font-semibold text-gray-800 mb-4">Quarterly Scan Results</h2>
    <div x-init="if (!Array.isArray(details.external_scans)) { details.external_scans = [] }">
        <h3 class="text-lg font-medium text-gray-900 mb-2">External Scan Results</h3>
        <table class="min-w-full divide-y divide-gray-300 assessment-table">
            <thead class="bg-gray-50">
                <tr><th>Date</th><th>Result</th><th>Initial Assessment</th><th x-show="isEditing"></th></tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                <template x-for="(scan, index) in details.external_scans" :key="index">
                    <tr>
                        <td x-text="scan.scan_date" x-show="!isEditing"></td>
                        <td x-show="isEditing"><input type="date" x-model="scan.scan_date" :name="'ext_scans[' + index + '][scan_date]'" class="w-full"></td>
                        <td x-text="scan.result" x-show="!isEditing"></td>
                        <td x-show="isEditing"><input type="text" x-model="scan.result" :name="'ext_scans[' + index + '][result]'" class="w-full"></td>
                        <td class="text-center" x-show="!isEditing" x-text="scan.initial_assessment ? 'Yes' : 'No'"></td>
                        <td class="text-center" x-show="isEditing"><input type="checkbox" x-model="scan.initial_assessment" :name="'ext_scans[' + index + '][initial_assessment]'" value="1"></td>
                        <td x-show="isEditing"><button type="button" @click="details.external_scans.splice(index, 1)" class="text-red-600">Remove</button></td>
                    </tr>
                </template>
            </tbody>
        </table>
        <button type="button" @click="details.external_scans.push({ scan_date: '', result: '', initial_assessment: false })" class="mt-2 text-blue-600" x-show="isEditing">Add External Scan</button>
    </div>
    <hr class="my-6">
    <div x-init="if (!Array.isArray(details.internal_scans)) { details.internal_scans = [] }" class="mt-6">
        <h3 class="text-lg font-medium text-gray-900 mb-2">Internal Scan Results</h3>
        <table class="min-w-full divide-y divide-gray-300 assessment-table">
            <thead class="bg-gray-50">
                <tr><th>Date</th><th>Result</th><th>Initial Assessment</th><th x-show="isEditing"></th></tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                <template x-for="(scan, index) in details.internal_scans" :key="index">
                    <tr>
                        <td x-text="scan.scan_date" x-show="!isEditing"></td>
                        <td x-show="isEditing"><input type="date" x-model="scan.scan_date" :name="'int_scans[' + index + '][scan_date]'" class="w-full"></td>
                        <td x-text="scan.result" x-show="!isEditing"></td>
                        <td x-show="isEditing"><input type="text" x-model="scan.result" :name="'int_scans[' + index + '][result]'" class="w-full"></td>
                        <td class="text-center" x-show="!isEditing" x-text="scan.initial_assessment ? 'Yes' : 'No'"></td>
                        <td class="text-center" x-show="isEditing"><input type="checkbox" x-model="scan.initial_assessment" :name="'int_scans[' + index + '][initial_assessment]'" value="1"></td>
                        <td x-show="isEditing"><button type="button" @click="details.internal_scans.splice(index, 1)" class="text-red-600">Remove</button></td>
                    </tr>
                </template>
            </tbody>
        </table>
        <button type="button" @click="details.internal_scans.push({ scan_date: '', result: '', initial_assessment: false })" class="mt-2 text-blue-600" x-show="isEditing">Add Internal Scan</button>
    </div>
</div>
