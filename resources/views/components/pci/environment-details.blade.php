<div class="bg-white shadow rounded-lg p-6 mb-8">
    <h2 class="text-xl font-semibold text-gray-800 mb-4">Environment Details</h2>
    <div class="space-y-6">
        <div>
            <label class="block text-sm font-medium text-gray-700">Is segmentation used to isolate the CDE from other networks?</label>
            <div class="flex items-center mt-2" x-show="isEditing">
                <input type="radio" name="segmentation_used" id="segmentation_used_yes" :value="true" x-model="details.segmentation_used" class="h-4 w-4 text-blue-600 border-gray-300">
                <label for="segmentation_used_yes" class="ml-2 text-sm">Yes</label>
                <input type="radio" name="segmentation_used" id="segmentation_used_no" :value="false" x-model="details.segmentation_used" class="ml-4 h-4 w-4 text-blue-600 border-gray-300">
                <label for="segmentation_used_no" class="ml-2 text-sm">No</label>
            </div>
            <div x-show="!isEditing" class="mt-1 font-semibold" x-text="details.segmentation_used ? 'Yes' : 'No'"></div>
            <div x-show="details.segmentation_used" x-transition class="mt-2">
                <label for="segmentation_desc" class="block text-sm font-medium text-gray-700">Description of Segmentation</label>
                <div x-show="!isEditing" class="mt-1 p-2 bg-gray-50 rounded-md border" x-text="details.segmentation_desc || 'No description provided.'"></div>
                <div x-show="isEditing">
                    <textarea name="segmentation_desc" id="segmentation_desc" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" x-model="details.segmentation_desc"></textarea>
                </div>
            </div>
        </div>
        <hr class="my-6">
        <div x-init="if (!Array.isArray(details.pci_ssc_products)) { details.pci_ssc_products = [] }">
            <label class="block text-sm font-medium text-gray-700">Are PCI SSC-validated products or solutions used?</label>
             <div class="flex items-center mt-2" x-show="isEditing">
                <input type="radio" name="pci_ssc_products_used" id="pci_ssc_products_used_yes" :value="true" x-model="details.pci_ssc_products_used" class="h-4 w-4 text-blue-600 border-gray-300">
                <label for="pci_ssc_products_used_yes" class="ml-2 text-sm">Yes</label>
                <input type="radio" name="pci_ssc_products_used" id="pci_ssc_products_used_no" :value="false" x-model="details.pci_ssc_products_used" class="ml-4 h-4 w-4 text-blue-600 border-gray-300">
                <label for="pci_ssc_products_used_no" class="ml-2 text-sm">No</label>
            </div>
            <div x-show="!isEditing" class="mt-1 font-semibold" x-text="details.pci_ssc_products_used ? 'Yes' : 'No'"></div>
            <div x-show="details.pci_ssc_products_used" x-transition class="mt-4">
                <h3 class="text-lg font-medium text-gray-900 mb-2">Product Details</h3>
                <table class="min-w-full divide-y divide-gray-300 assessment-table">
                    <thead class="bg-gray-50">
                        <tr>
                            <th>Product Name</th><th>Version</th><th>Vendor</th><th>Description</th><th x-show="isEditing"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <template x-for="(product, index) in details.pci_ssc_products" :key="index">
                            <tr>
                                <td x-text="product.product_name" x-show="!isEditing"></td>
                                <td x-show="isEditing"><input type="text" x-model="product.product_name" :name="'products[' + index + '][product_name]'" class="w-full"></td>
                                <td x-text="product.version" x-show="!isEditing"></td>
                                <td x-show="isEditing"><input type="text" x-model="product.version" :name="'products[' + index + '][version]'" class="w-full"></td>
                                <td x-text="product.vendor" x-show="!isEditing"></td>
                                <td x-show="isEditing"><input type="text" x-model="product.vendor" :name="'products[' + index + '][vendor]'" class="w-full"></td>
                                <td x-text="product.description" x-show="!isEditing"></td>
                                <td x-show="isEditing"><input type="text" x-model="product.description" :name="'products[' + index + '][description]'" class="w-full"></td>
                                <td x-show="isEditing"><button type="button" @click="details.pci_ssc_products.splice(index, 1)" class="text-red-600">Remove</button></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
                <button type="button" @click="details.pci_ssc_products.push({ product_name: '', version: '', vendor: '', description: '' })" class="mt-2 text-blue-600" x-show="isEditing">Add Product</button>
            </div>
        </div>
    </div>
</div>
