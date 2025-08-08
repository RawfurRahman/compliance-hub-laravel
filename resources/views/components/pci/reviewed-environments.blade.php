<div class="bg-white shadow rounded-lg p-6 mb-8">
    <h2 class="text-xl font-semibold text-gray-800 mb-4">Details About Reviewed Environments</h2>
    <div class="space-y-6">
        <div>
            <label for="network_diagrams_desc" class="block text-sm font-medium text-gray-700">Network Diagrams Description</label>
            <div x-show="!isEditing" class="mt-1 p-2 bg-gray-50 rounded-md border" x-text="details.network_diagrams_desc || 'Not provided.'"></div>
            <div x-show="isEditing"><textarea name="network_diagrams_desc" id="network_diagrams_desc" rows="3" class="mt-1 block w-full" x-model="details.network_diagrams_desc"></textarea></div>
        </div>
        <div>
            <label for="account_dataflow_diagrams_desc" class="block text-sm font-medium text-gray-700">Account Dataflow Diagrams Description</label>
            <div x-show="!isEditing" class="mt-1 p-2 bg-gray-50 rounded-md border" x-text="details.account_dataflow_diagrams_desc || 'Not provided.'"></div>
            <div x-show="isEditing"><textarea name="account_dataflow_diagrams_desc" id="account_dataflow_diagrams_desc" rows="3" class="mt-1 block w-full" x-model="details.account_dataflow_diagrams_desc"></textarea></div>
        </div>
        <div>
            <label for="storage_account_data_desc" class="block text-sm font-medium text-gray-700">Storage of Account Data Description</label>
            <div x-show="!isEditing" class="mt-1 p-2 bg-gray-50 rounded-md border" x-text="details.storage_account_data_desc || 'Not provided.'"></div>
            <div x-show="isEditing"><textarea name="storage_account_data_desc" id="storage_account_data_desc" rows="3" class="mt-1 block w-full" x-model="details.storage_account_data_desc"></textarea></div>
        </div>
        <hr class="my-6">
        <div x-init="if (!Array.isArray(details.tpsps)) { details.tpsps = [] }">
            <h3 class="text-lg font-medium text-gray-900 mb-2">In-scope Third-Party Service Providers</h3>
            <table class="min-w-full divide-y divide-gray-300 assessment-table">
                <thead class="bg-gray-50"><tr><th>Name</th><th>Service Provided</th><th x-show="isEditing"></th></tr></thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    <template x-for="(tpsp, index) in details.tpsps" :key="index">
                        <tr>
                            <td x-text="tpsp.name" x-show="!isEditing"></td>
                            <td x-show="isEditing"><input type="text" x-model="tpsp.name" :name="'tpsps[' + index + '][name]'" class="w-full"></td>
                            <td x-text="tpsp.service_provided" x-show="!isEditing"></td>
                            <td x-show="isEditing"><input type="text" x-model="tpsp.service_provided" :name="'tpsps[' + index + '][service_provided]'" class="w-full"></td>
                            <td x-show="isEditing"><button type="button" @click="details.tpsps.splice(index, 1)" class="text-red-600">Remove</button></td>
                        </tr>
                    </template>
                </tbody>
            </table>
            <button type="button" @click="details.tpsps.push({ name: '', service_provided: '' })" class="mt-2 text-blue-600" x-show="isEditing">Add Provider</button>
        </div>
        <div x-init="if (!Array.isArray(details.networks)) { details.networks = [] }" class="mt-6">
            <h3 class="text-lg font-medium text-gray-900 mb-2">In-scope Networks</h3>
            <table class="min-w-full divide-y divide-gray-300 assessment-table">
                <thead class="bg-gray-50"><tr><th>Name/Description</th><th>IP Range/Subnet</th><th x-show="isEditing"></th></tr></thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    <template x-for="(network, index) in details.networks" :key="index">
                        <tr>
                            <td x-text="network.name" x-show="!isEditing"></td>
                            <td x-show="isEditing"><input type="text" x-model="network.name" :name="'networks[' + index + '][name]'" class="w-full"></td>
                            <td x-text="network.ip_range" x-show="!isEditing"></td>
                            <td x-show="isEditing"><input type="text" x-model="network.ip_range" :name="'networks[' + index + '][ip_range]'" class="w-full"></td>
                            <td x-show="isEditing"><button type="button" @click="details.networks.splice(index, 1)" class="text-red-600">Remove</button></td>
                        </tr>
                    </template>
                </tbody>
            </table>
            <button type="button" @click="details.networks.push({ name: '', ip_range: '' })" class="mt-2 text-blue-600" x-show="isEditing">Add Network</button>
        </div>
        <div x-init="if (!Array.isArray(details.locations)) { details.locations = [] }" class="mt-6">
            <h3 class="text-lg font-medium text-gray-900 mb-2">In-scope Locations</h3>
            <table class="min-w-full divide-y divide-gray-300 assessment-table">
                <thead class="bg-gray-50"><tr><th>Name/Description</th><th>Address</th><th x-show="isEditing"></th></tr></thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    <template x-for="(location, index) in details.locations" :key="index">
                        <tr>
                            <td x-text="location.name" x-show="!isEditing"></td>
                            <td x-show="isEditing"><input type="text" x-model="location.name" :name="'locations[' + index + '][name]'" class="w-full"></td>
                            <td x-text="location.address" x-show="!isEditing"></td>
                            <td x-show="isEditing"><input type="text" x-model="location.address" :name="'locations[' + index + '][address]'" class="w-full"></td>
                            <td x-show="isEditing"><button type="button" @click="details.locations.splice(index, 1)" class="text-red-600">Remove</button></td>
                        </tr>
                    </template>
                </tbody>
            </table>
            <button type="button" @click="details.locations.push({ name: '', address: '' })" class="mt-2 text-blue-600" x-show="isEditing">Add Location</button>
        </div>
        <div x-init="if (!Array.isArray(details.components)) { details.components = [] }" class="mt-6">
            <h3 class="text-lg font-medium text-gray-900 mb-2">In-scope Components</h3>
            <table class="min-w-full divide-y divide-gray-300 assessment-table">
                <thead class="bg-gray-50"><tr><th>Name/Description</th><th>Type</th><th x-show="isEditing"></th></tr></thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    <template x-for="(component, index) in details.components" :key="index">
                        <tr>
                            <td x-text="component.name" x-show="!isEditing"></td>
                            <td x-show="isEditing"><input type="text" x-model="component.name" :name="'components[' + index + '][name]'" class="w-full"></td>
                            <td x-text="component.type" x-show="!isEditing"></td>
                            <td x-show="isEditing">
                                <select x-model="component.type" :name="'components[' + index + '][type]'" class="w-full">
                                    <option value="">Select Type</option>
                                    <option value="Server">Server</option>
                                    <option value="Workstation">Workstation</option>
                                    <option value="Network Device">Network Device</option>
                                    <option value="Other">Other</option>
                                </select>
                            </td>
                            <td x-show="isEditing"><button type="button" @click="details.components.splice(index, 1)" class="text-red-600">Remove</button></td>
                        </tr>
                    </template>
                </tbody>
            </table>
            <button type="button" @click="details.components.push({ name: '', type: '' })" class="mt-2 text-blue-600" x-show="isEditing">Add Component</button>
        </div>
    </div>
</div>
