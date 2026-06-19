@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-10" x-data="pciProjectManager({{ json_encode($project) }}, {{ json_encode($project->pciDssDetails) }}, {{ json_encode($findings) }})">
    <div class="flex flex-col md:flex-row md:items-end justify-between mb-10 gap-6">
        <div>
            <div class="flex items-center space-x-2 mb-3">
                <a href="{{ route('projects.index') }}" class="text-slate-400 hover:text-sky-600 transition-colors text-xs font-bold uppercase tracking-widest">Portfolio</a>
                <i class="fas fa-chevron-right text-[10px] text-slate-300"></i>
                <span class="text-sky-600 font-bold text-xs uppercase tracking-widest">PCI DSS Assessment</span>
            </div>
            <h1 class="text-4xl font-extrabold text-slate-900 tracking-tight" x-text="project.name"></h1>
            <p class="mt-2 text-md text-slate-500 font-medium">Full ROC (Report on Compliance) Data Collection Hub</p>
        </div>
        <div class="flex items-center gap-3">
            <button x-show="!isEditing" @click="isEditing = true" class="btn-premium px-6 py-2.5 rounded-xl text-xs font-bold uppercase tracking-widest shadow-lg flex items-center">
                <i class="fas fa-edit mr-2"></i> Edit ROC Data
            </button>
            <div x-show="isEditing" class="flex gap-3">
                <button @click="isEditing = false; resetForm();" class="px-6 py-2.5 text-xs font-bold uppercase tracking-widest text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors">
                    Cancel
                </button>
                <button @click="document.getElementById('pci-form').submit()" class="px-6 py-2.5 text-xs font-bold uppercase tracking-widest text-white bg-emerald-600 rounded-xl hover:bg-emerald-700 shadow-lg shadow-emerald-600/20 transition-all">
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

    @if ($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
            <strong>Validation Errors:</strong>
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
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
        <x-pci.requirements-list :requirements="$requirements" :details="$project->pciDssDetails" />
    </form>

    <div x-show="!isEditing" class="space-y-10">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div class="glass-card rounded-3xl p-8 border border-white/60 shadow-xl overflow-hidden relative">
                <div class="absolute -right-20 -top-20 w-80 h-80 bg-sky-500/5 rounded-full blur-3xl pointer-events-none"></div>
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-6 flex items-center">
                    <i class="fas fa-info-circle mr-2 text-sky-500"></i> Project Metadata
                </h3>
                <x-pci.project-info :project="$project" />
            </div>
            
            <div class="glass-card rounded-3xl p-8 border border-white/60 shadow-xl overflow-hidden relative">
                <div class="absolute -right-20 -top-20 w-80 h-80 bg-emerald-500/5 rounded-full blur-3xl pointer-events-none"></div>
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-6 flex items-center">
                    <i class="fas fa-briefcase mr-2 text-emerald-500"></i> Business Overview
                </h3>
                <x-pci.business-overview :details="$project->pciDssDetails" />
            </div>
        </div>

        <div class="glass-card rounded-3xl p-8 border border-white/60 shadow-xl">
             <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-8 flex items-center">
                <i class="fas fa-list-check mr-2 text-indigo-500"></i> Requirement Verification Matrix
            </h3>
            <x-pci.requirements-list :requirements="$requirements" :details="$project->pciDssDetails" />
        </div>
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
