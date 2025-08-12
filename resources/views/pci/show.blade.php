@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="pciProjectManager({{ json_encode($project) }}, {{ json_encode($project->pciDssDetails) }}, {{ json_encode($findings) }})">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-semibold text-gray-800" x-text="`PCI DSS Assessment for: ${project.name}`"></h1>
        <div>
            <button x-show="!isEditing" @click="isEditing = true" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Edit Project
            </button>
            <div x-show="isEditing">
                <button @click="isEditing = false; resetForm();" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    Cancel
                </button>
                <button @click="document.getElementById('pci-form').submit()" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded ml-2">
                    Save Changes
                </button>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif

    <form id="pci-form" x-show="isEditing" action="{{ route('pci.update', $project) }}" method="POST" class="space-y-8">
        @csrf
        @method('PUT')

        <x-pci.project-info :project="$project" />
        <x-pci.business-overview />
        <x-pci.assessment-timeframe />
        <x-pci.scope-of-work />
        <x-pci.reviewed-environments />
        <x-pci.quarterly-scans />
        <x-pci.environment-details />
        <x-pci.assessment-activities />
        <x-pci.overall-findings />
        <x-pci.requirements-list :requirements="$requirements" />
    </form>

    <div x-show="!isEditing" class="space-y-8">
        <x-pci.project-info :project="$project" />
        <x-pci.business-overview :details="$project->pciDssDetails" />
        <x-pci.assessment-timeframe :details="$project->pciDssDetails" />
        <x-pci.scope-of-work :details="$project->pciDssDetails" :paymentChannels="$paymentChannels" />
        <x-pci.reviewed-environments :details="$project->pciDssDetails" />
        <x-pci.quarterly-scans :details="$project->pciDssDetails" />
        <x-pci.environment-details :details="$project->pciDssDetails" />
        <x-pci.assessment-activities :details="$project->pciDssDetails" />
        <x-pci.overall-findings :details="$project->pciDssDetails" />
        <x-pci.requirements-list :requirements="$requirements" :findings="$findings" />
    </div>
</div>
@endsection

@push('scripts')
<script>
    function pciProjectManager(project, details, findings) {
        return {
            isEditing: false,
            project: project,
            details: details || {},
            findings: findings || {},
            
            init() {
                this.initializeFindings();
            },

            // This function ensures that all findings have a valid 'assessor_responses' array.
            initializeFindings() {
                if (typeof this.findings === 'object' && this.findings !== null) {
                    Object.values(this.findings).forEach(finding => {
                        if (!finding.assessor_responses || !Array.isArray(finding.assessor_responses)) {
                            // Initialize with an empty string to ensure the textarea has a value to bind to.
                            finding.assessor_responses = [''];
                        }
                    });
                }
            },

            resetForm() {
                window.location.reload();
            }
        }
    }
</script>
@endpush
