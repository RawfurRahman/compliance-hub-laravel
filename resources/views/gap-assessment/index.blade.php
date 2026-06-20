@extends('layouts.app')

@push('styles')
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; background: #f8fafc; }
        .glass-premium {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.04);
        }
        [x-cloak] { display: none !important; }
    </style>
@endpush

@section('content')
<div class="p-8 min-h-screen" x-data="gapAssessmentWorkspace()" x-cloak>
    
    {{-- Header Section --}}
    <div class="flex flex-col lg:flex-row lg:items-center justify-between mb-10 gap-6">
        <div class="flex items-center space-x-6">
            <div class="w-16 h-16 rounded-2xl bg-indigo-600 flex items-center justify-center text-white shadow-2xl">
                <i class="fas fa-tasks text-2xl"></i>
            </div>
            <div>
                <div class="flex items-center space-x-2 mb-1">
                    <a href="{{ route('projects.index') }}" class="text-[10px] font-black text-slate-400 uppercase tracking-widest hover:text-indigo-600 transition-colors">Projects</a>
                    <i class="fas fa-chevron-right text-[8px] text-slate-300"></i>
                    <span class="text-[10px] font-black text-indigo-600 uppercase tracking-widest">Gap Assessment</span>
                </div>
                <h1 class="text-4xl font-black text-slate-900 tracking-tight">{{ $project->name }} - Gap Assessment</h1>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('evidence.show', $project) }}" class="px-6 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 transition-all flex items-center shadow-sm">
                <i class="fas fa-arrow-left mr-2"></i> Back to Workspace
            </a>
            <button @click="showImportModal = true" class="px-8 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest bg-indigo-600 text-white shadow-xl hover:bg-indigo-700 transition-all">
                <i class="fas fa-file-import mr-2"></i> Import Assessment Data
            </button>
        </div>
    </div>

    {{-- Navigation Tabs --}}
    <div class="mb-8 border-b border-slate-200">
        <div class="flex space-x-8">
            <a href="{{ route('projects.show', $project) }}" class="px-1 py-4 text-sm font-semibold text-slate-600 hover:text-slate-900 border-b-2 border-transparent hover:border-slate-300 transition-colors">
                Overview
            </a>
            <a href="{{ route('projects.scope', $project) }}" class="px-1 py-4 text-sm font-semibold text-slate-600 hover:text-slate-900 border-b-2 border-transparent hover:border-slate-300 transition-colors">
                Scope
            </a>
            <a href="{{ route('projects.gap-assessment', $project) }}" class="px-1 py-4 text-sm font-semibold text-indigo-600 border-b-2 border-indigo-600">
                Gap Assessment
            </a>
            <a href="{{ route('projects.reporting', $project) }}" class="px-1 py-4 text-sm font-semibold text-slate-600 hover:text-slate-900 border-b-2 border-transparent hover:border-slate-300 transition-colors">
                Reports
            </a>
        </div>
    </div>

    {{-- Messages --}}
    @if(session('success'))
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-sm font-semibold flex items-center shadow-sm">
            <i class="fas fa-check-circle mr-2 text-emerald-500 text-base"></i>
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-6 p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-sm font-semibold flex items-center shadow-sm">
            <i class="fas fa-exclamation-circle mr-2 text-rose-500 text-base"></i>
            {{ session('error') }}
        </div>
    @endif

    {{-- Progress Card --}}
    <div class="glass-premium rounded-2xl p-6 mb-8">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-lg font-bold text-slate-800">Compliance Progress</h2>
                <p class="text-slate-400 text-xs mt-0.5">Overall compliance for project requirements across all departments.</p>
            </div>
            <div class="text-right">
                <span class="text-3xl font-extrabold text-indigo-600 tracking-tight" x-text="`${projectProgress}%`"></span >
                <span class="text-slate-400 text-xs block font-semibold" x-text="`${completedControls} of ${totalControls} Completed`"></span>
            </div>
        </div>
        <div class="w-full bg-slate-100 h-3 rounded-full overflow-hidden">
            <div class="bg-indigo-600 h-full transition-all duration-500 ease-out" :style="`width: ${projectProgress}%`"></div>
        </div>
    </div>

    {{-- Main Workspace Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        {{-- Left Tabs (Departments) --}}
        <div class="lg:col-span-3 space-y-2">
            <h3 class="text-xs font-black uppercase tracking-wider text-slate-400 mb-3 px-2">Assigned Departments</h3>
            
            <template x-for="dept in departments" :key="dept.id">
                <button 
                    @click="activeDeptId = dept.id"
                    class="w-full px-4 py-3 rounded-xl flex items-center justify-between text-left transition-all duration-200"
                    :class="activeDeptId === dept.id ? 'bg-indigo-600 text-white shadow-lg' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-100 shadow-sm'">
                    <div>
                        <span class="font-bold text-sm block" x-text="dept.name"></span>
                        <span class="text-[10px] uppercase font-bold mt-0.5 tracking-wider block"
                              :class="activeDeptId === dept.id ? 'text-indigo-200' : 'text-slate-400'"
                              x-text="`${dept.gap_controls.length} controls`"></span>
                    </div>
                    <i class="fas fa-chevron-right text-xs" :class="activeDeptId === dept.id ? 'text-white' : 'text-slate-300'"></i>
                </button>
            </template>

            @if(count($departments) === 0)
                <div class="p-6 bg-white border border-slate-100 rounded-xl text-center shadow-sm">
                    <p class="text-sm font-bold text-slate-400">No Assessment Data</p>
                    <p class="text-xs text-slate-300 mt-1">Please import an Excel file to initialize departments.</p>
                </div>
            @endif
        </div>

        {{-- Right Controls Table --}}
        <div class="lg:col-span-9">
            <template x-for="dept in departments" :key="dept.id">
                <div x-show="activeDeptId === dept.id" class="glass-premium rounded-2xl overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 bg-white flex justify-between items-center">
                        <h3 class="font-bold text-slate-800 text-lg" x-text="dept.name"></h3>
                        <span class="px-3 py-1 bg-indigo-50 border border-indigo-100 text-indigo-700 rounded-lg text-xs font-black uppercase tracking-widest"
                              x-text="`${dept.gap_controls.filter(c => c.status === 'Done').length} / ${dept.gap_controls.length} Done`"></span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 text-[10px] font-black uppercase tracking-widest text-slate-400 border-b border-slate-100">
                                    <th class="px-6 py-4 w-28">Control ID</th>
                                    <th class="px-6 py-4">Requirement</th>
                                    <th class="px-6 py-4">Required Evidence</th>
                                    <th class="px-6 py-4 w-44">Evidence File</th>
                                    <th class="px-6 py-4 w-32 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="control in dept.gap_controls" :key="control.id">
                                    <tr class="hover:bg-slate-50/50 transition-colors border-b border-slate-100/60 last:border-0 align-top">
                                        <td class="px-6 py-5 font-bold text-sm text-indigo-600" x-text="control.control_id"></td>
                                        <td class="px-6 py-5 text-sm text-slate-700 leading-relaxed font-medium" x-text="control.requirement_description"></td>
                                        <td class="px-6 py-5 text-xs text-slate-500 leading-relaxed" x-text="control.required_evidence || 'N/A'"></td>
                                        <td class="px-6 py-5 space-y-2">
                                            {{-- Attached Evidence list --}}
                                            <div class="space-y-1 max-w-[200px]">
                                                <template x-for="evidence in control.evidence_files" :key="evidence.id">
                                                    <a :href="`{{ url('storage') }}/${evidence.file_path}`" target="_blank"
                                                       class="block text-[11px] font-bold text-indigo-600 hover:underline truncate"
                                                       :title="evidence.original_filename">
                                                        <i class="fas fa-file-alt mr-1 text-slate-400"></i>
                                                        <span x-text="evidence.original_filename"></span>
                                                    </a>
                                                </template>
                                            </div>

                                            {{-- Upload Form --}}
                                            <form :action="`{{ url('projects') }}/{{ $project->id }}/gap-assessment/controls/${control.id}/evidence`"
                                                  method="POST" enctype="multipart/form-data" class="flex items-center gap-1.5 pt-1">
                                                @csrf
                                                <label class="cursor-pointer px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 border border-slate-200 text-slate-600 rounded-lg text-[10px] font-black uppercase tracking-widest flex items-center transition-colors">
                                                    <i class="fas fa-cloud-upload-alt mr-1"></i> Upload
                                                    <input type="file" name="file" class="hidden" onchange="this.form.submit()">
                                                </label>
                                            </form>
                                        </td>
                                        <td class="px-6 py-5 text-center">
                                            <button 
                                                @click="toggleStatus(control)"
                                                class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-widest shadow-sm transition-all duration-200 w-24 text-center border"
                                                :class="control.status === 'Done' ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' : 'bg-amber-50 text-amber-700 border-amber-200 hover:bg-amber-100'"
                                                x-text="control.status">
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </template>
        </div>

    </div>

    {{-- Import Assessment Modal --}}
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm"
         x-show="showImportModal"
         x-transition
         style="display: none;">
        
        <div class="bg-white rounded-2xl w-full max-w-md overflow-hidden shadow-2xl border border-slate-100"
             @click.away="showImportModal = false">
            
            <div class="px-6 py-4 bg-indigo-600 text-white flex items-center justify-between">
                <h3 class="text-lg font-black uppercase tracking-wider flex items-center">
                    <i class="fas fa-file-import mr-2.5"></i> Import Excel
                </h3>
                <button @click="showImportModal = false" class="text-white/80 hover:text-white transition">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <form action="{{ route('gap-assessment.import', $project) }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
                @csrf
                
                <div class="border-2 border-dashed border-slate-200 hover:border-indigo-600 rounded-xl p-8 text-center cursor-pointer transition-colors relative">
                    <input type="file" name="file" required class="absolute inset-0 opacity-0 cursor-pointer" id="excel_file_input"
                           onchange="document.getElementById('file-chosen-text').textContent = this.files[0].name">
                    <div class="space-y-2">
                        <i class="fas fa-cloud-upload-alt text-3xl text-indigo-500 block"></i>
                        <span class="text-xs font-black uppercase tracking-wider text-slate-400 block" id="file-chosen-text">Choose Excel File (.xlsx, .xls, .csv)</span>
                    </div>
                </div>

                <div class="flex justify-end space-x-3 pt-4 border-t border-slate-100">
                    <button type="button" @click="showImportModal = false"
                            class="px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest text-slate-500 bg-slate-100 hover:bg-slate-200 transition">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-7 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest bg-indigo-600 text-white hover:bg-indigo-700 transition shadow-lg">
                        Import
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

@push('scripts')
<script>
function gapAssessmentWorkspace() {
    return {
        departments: @json($departments),
        projectProgress: {{ $projectProgress }},
        totalControls: {{ $totalControls }},
        completedControls: {{ $completedControls }},
        activeDeptId: {{ count($departments) > 0 ? $departments[0]->id : 'null' }},
        showImportModal: false,

        toggleStatus(control) {
            const nextStatus = control.status === 'Done' ? 'Pending' : 'Done';
            
            fetch(`{{ url('projects') }}/{{ $project->id }}/gap-assessment/controls/${control.id}/status`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ status: nextStatus })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    // Update state variables
                    control.status = data.control_status;
                    this.projectProgress = data.project_progress;
                    this.completedControls = data.completed_controls;
                } else {
                    alert('Failed to update control status');
                }
            })
            .catch(err => {
                console.error(err);
                alert('An error occurred. Please try again.');
            });
        }
    };
}
</script>
@endpush
@endsection
