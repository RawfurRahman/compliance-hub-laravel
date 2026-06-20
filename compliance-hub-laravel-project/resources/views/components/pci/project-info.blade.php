@props(['project'])

<div class="bg-white rounded-lg shadow-sm">
    <div class="p-6 border-b border-slate-200">
        <h2 class="text-xl font-bold text-slate-800" id="project-info">Project Information</h2>
    </div>
    <div class="p-6">
        <h3 class="text-lg font-semibold text-slate-700 mb-4">Assessed Entity Details</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-slate-600">Company Name</label>
                <div x-show="!isEditing" class="mt-1 text-base text-slate-900 font-semibold">{{ optional($project->pciDssDetails)->ae_company_name ?? 'N/A' }}</div>
                <div x-show="isEditing"><input type="text" name="ae_company_name" class="mt-1 block w-full assessment-table" x-model="details.ae_company_name"></div>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-600">DBA</label>
                <div x-show="!isEditing" class="mt-1 text-base text-slate-900">{{ optional($project->pciDssDetails)->ae_dba ?? 'N/A' }}</div>
                <div x-show="isEditing"><input type="text" name="ae_dba" class="mt-1 block w-full assessment-table" x-model="details.ae_dba"></div>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-600">Mailing Address</label>
                <div x-show="!isEditing" class="mt-1 text-base text-slate-900">{{ optional($project->pciDssDetails)->ae_mailing_address ?? 'N/A' }}</div>
                <div x-show="isEditing"><input type="text" name="ae_mailing_address" class="mt-1 block w-full assessment-table" x-model="details.ae_mailing_address"></div>
            </div>
        </div>
        <h3 class="text-lg font-semibold text-slate-700 mt-8 mb-4">Assessor Details</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
             <div>
                <label class="block text-sm font-medium text-slate-600">QSA Company Name</label>
                <div x-show="!isEditing" class="mt-1 text-base text-slate-900 font-semibold">{{ optional($project->pciDssDetails)->assessor_company_name ?? 'N/A' }}</div>
                <div x-show="isEditing"><input type="text" name="assessor_company_name" class="mt-1 block w-full assessment-table" x-model="details.assessor_company_name"></div>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-600">Lead Assessor Name</label>
                <div x-show="!isEditing" class="mt-1 text-base text-slate-900">{{ optional($project->pciDssDetails)->assessor_lead_name ?? 'N/A' }}</div>
                <div x-show="isEditing"><input type="text" name="assessor_lead_name" class="mt-1 block w-full assessment-table" x-model="details.assessor_lead_name"></div>
            </div>
        </div>
    </div>
</div>
