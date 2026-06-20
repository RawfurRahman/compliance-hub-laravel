@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto" x-data="pciGapTracker()">
    {{-- Breadcrumb & Title Header --}}
    <div class="mb-8 flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <div class="mb-2 flex items-center gap-2 text-xs font-bold uppercase tracking-widest text-slate-400">
                <a href="{{ route('projects.show', $project) }}" class="hover:text-sky-600 transition-colors">{{ $project->name }}</a>
                <i class="fas fa-chevron-right text-[9px]"></i>
                <span>PCI DSS v4.0.1</span>
            </div>
            <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">PCI DSS v4.0.1 Gap Assessment Report</h1>
            <p class="mt-2 text-sm font-medium text-slate-500 font-sans">Assess, document, and manage the compliance gaps for the project.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            @can('update', $project)
                <button @click="importModalOpen = true" class="rounded-xl bg-gradient-to-r from-sky-500 to-indigo-500 px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-sky-500/25 transition hover:shadow-sky-500/40 inline-flex items-center">
                    <i class="fas fa-file-excel mr-2 text-xs"></i> Import Assessment Data
                </button>
            @endcan
        </div>
    </div>

    {{-- Alert Messages --}}
    @if(session('success'))
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800 flex items-center">
            <i class="fas fa-check-circle mr-2.5 text-emerald-500"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800 flex items-center">
            <i class="fas fa-exclamation-circle mr-2.5 text-rose-500"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- Gap Summary Cards / Progress Section --}}
    <section class="mb-10">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-xs font-bold uppercase tracking-widest text-slate-500">Assessment Metrics</h2>
            <span class="text-sm font-semibold text-slate-500">{{ $stats['progress'] }}% Compliant</span>
        </div>
        
        {{-- Progress Bar --}}
        <div class="mb-6 w-full bg-slate-200 rounded-full h-2.5 overflow-hidden shadow-inner">
            <div class="bg-gradient-to-r from-sky-500 to-indigo-500 h-2.5 rounded-full transition-all duration-500" style="width: {{ $stats['progress'] }}%"></div>
        </div>

        <div class="grid gap-5 md:grid-cols-5">
            {{-- Total Controls --}}
            <div class="rounded-2xl border border-white/70 bg-white p-5 shadow-sm">
                <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Controls</p>
                <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ $stats['total'] }}</p>
            </div>
            {{-- YES (Compliant) --}}
            <div class="rounded-2xl border border-white/70 bg-white p-5 shadow-sm border-l-4 border-l-emerald-500">
                <p class="text-xs font-bold text-emerald-600 uppercase tracking-wider">Yes (Compliant)</p>
                <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ $stats['yes'] }}</p>
            </div>
            {{-- NO (Gaps) --}}
            <div class="rounded-2xl border border-white/70 bg-white p-5 shadow-sm border-l-4 border-l-rose-500">
                <p class="text-xs font-bold text-rose-600 uppercase tracking-wider">No (Gap)</p>
                <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ $stats['no'] }}</p>
            </div>
            {{-- N/A --}}
            <div class="rounded-2xl border border-white/70 bg-white p-5 shadow-sm border-l-4 border-l-slate-400">
                <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">N/A (Not Applicable)</p>
                <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ $stats['na'] }}</p>
            </div>
            {{-- Pending --}}
            <div class="rounded-2xl border border-white/70 bg-white p-5 shadow-sm border-l-4 border-l-amber-500">
                <p class="text-xs font-bold text-amber-600 uppercase tracking-wider">Pending Review</p>
                <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ $stats['pending'] }}</p>
            </div>
        </div>
    </section>

    {{-- Main Data Table Card --}}
    <div class="glass-card rounded-2xl border border-white/60 bg-white shadow-lg overflow-hidden mb-12">
        <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between flex-wrap gap-4">
            <div>
                <h2 class="font-extrabold text-slate-900 text-lg">PCI DSS Control Checklist</h2>
                <p class="mt-1 text-xs text-slate-500">Configure status, milestone target, and comments. Changes are automatically saved.</p>
            </div>
            {{-- Inline status tracker helper --}}
            <div class="flex items-center gap-2 text-xs font-semibold text-slate-400 bg-slate-50 border border-slate-100 rounded-lg px-3 py-1.5" x-show="saving">
                <i class="fas fa-spinner fa-spin text-sky-500"></i> Saving changes...
            </div>
            <div class="flex items-center gap-2 text-xs font-semibold text-emerald-600 bg-emerald-50 border border-emerald-100 rounded-lg px-3 py-1.5" x-show="saved" x-cloak>
                <i class="fas fa-check-circle"></i> Changes saved successfully!
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-slate-50/50 border-b border-slate-100 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">
                        <th class="px-6 py-4" style="width: 40%;">PCI DSS Requirements v4.0.1</th>
                        <th class="px-4 py-4" style="width: 15%;">Status</th>
                        <th class="px-4 py-4" style="width: 15%;">N/A Explanation</th>
                        <th class="px-4 py-4" style="width: 15%;">Target Milestone</th>
                        <th class="px-6 py-4" style="width: 15%;">Comments</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($assessments as $item)
                        @if($item->is_section_header)
                            {{-- Section Header Row --}}
                            <tr class="bg-slate-800 border-y border-slate-900 text-white font-bold">
                                <td colspan="5" class="px-6 py-4 text-sm font-extrabold select-none">
                                    <i class="fas fa-folder-open mr-2 text-sky-400 text-xs"></i>{{ $item->requirement_text }}
                                </td>
                            </tr>
                        @else
                            {{-- Assessable Control Row --}}
                            <tr class="hover:bg-sky-50/20 transition-colors" x-data="rowEditor({{ $item->id }}, '{{ $item->status }}', '{{ addslashes($item->na_explanation) }}', '{{ $item->milestone_date ? $item->milestone_date->format('Y-m-d') : '' }}', '{{ addslashes($item->comments) }}')">
                                <td class="px-6 py-4 text-xs font-medium text-slate-800 leading-relaxed whitespace-pre-wrap">
                                    {{ $item->requirement_text }}
                                </td>
                                <td class="px-4 py-4">
                                    @can('update', $project)
                                        <select x-model="status" @change="save()"
                                                :class="statusClass(status)"
                                                class="w-full text-xs font-bold rounded-lg border-slate-200 py-1.5 focus:border-sky-500 focus:ring-sky-500 transition shadow-sm">
                                            <option value="Pending">Pending</option>
                                            <option value="Yes">Yes</option>
                                            <option value="No">No</option>
                                            <option value="N/A">N/A</option>
                                        </select>
                                    @else
                                        <span :class="statusClass(status)" class="px-2.5 py-1.5 rounded-lg text-xs font-bold block text-center">
                                            <span x-text="status"></span>
                                        </span>
                                    @endcan
                                </td>
                                <td class="px-4 py-4">
                                    @can('update', $project)
                                        <input type="text" x-model="na_explanation" @blur="save()"
                                               placeholder="If N/A, explain here..."
                                               :disabled="status !== 'N/A'"
                                               class="w-full text-xs rounded-lg border-slate-200 py-1.5 focus:border-sky-500 focus:ring-sky-500 disabled:bg-slate-50 disabled:text-slate-400 disabled:border-slate-100 transition shadow-sm">
                                    @else
                                        <p class="text-xs text-slate-600 whitespace-pre-wrap" x-text="na_explanation || '-'"></p>
                                    @endcan
                                </td>
                                <td class="px-4 py-4">
                                    @can('update', $project)
                                        <input type="date" x-model="milestone_date" @change="save()"
                                               class="w-full text-xs rounded-lg border-slate-200 py-1.5 focus:border-sky-500 focus:ring-sky-500 transition shadow-sm text-slate-700">
                                    @else
                                        <p class="text-xs text-slate-600" x-text="milestone_date || '-'"></p>
                                    @endcan
                                </td>
                                <td class="px-6 py-4">
                                    @can('update', $project)
                                        <textarea x-model="comments" @blur="save()"
                                                  rows="1"
                                                  placeholder="Add comments..."
                                                  class="w-full text-xs rounded-lg border-slate-200 py-1.5 focus:border-sky-500 focus:ring-sky-500 transition-all duration-200 shadow-sm focus:h-20 min-h-[34px] resize-y"></textarea>
                                    @else
                                        <p class="text-xs text-slate-600 whitespace-pre-wrap" x-text="comments || '-'"></p>
                                    @endcan
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-16 text-center text-slate-500 bg-slate-50/50">
                                <div class="max-w-md mx-auto">
                                    <div class="w-16 h-16 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-400 mx-auto mb-4">
                                        <i class="fas fa-clipboard-list text-2xl"></i>
                                    </div>
                                    <h3 class="font-extrabold text-slate-800 text-lg">No Gap Assessment Data</h3>
                                    <p class="text-sm text-slate-400 mt-1 mb-6">Import the official PCI DSS v4.0.1 Gap Assessment spreadsheet to get started.</p>
                                    @can('update', $project)
                                        <button @click="importModalOpen = true" class="px-5 py-2.5 rounded-xl bg-slate-900 text-white font-bold text-xs hover:bg-slate-800 transition shadow-md">
                                            <i class="fas fa-file-excel mr-2 text-xs"></i> Import Excel
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Import Modal --}}
    @can('update', $project)
        <div x-show="importModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60" @keydown.escape.window="importModalOpen = false" x-cloak
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
             
            <div class="bg-white rounded-2xl w-full max-w-lg shadow-2xl border border-slate-100 overflow-hidden" @click.away="importModalOpen = false"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95">
                 
                <div class="px-6 py-5 border-b border-slate-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-sky-50 flex items-center justify-center text-sky-500">
                            <i class="fas fa-file-excel text-lg"></i>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">Import Assessment Spreadsheet</h2>
                            <p class="text-xs text-slate-400 mt-0.5">Upload a PCI DSS gap assessment file (.xlsx, .xls, .csv)</p>
                        </div>
                    </div>
                </div>

                <form action="{{ route('pci-gap.import', $project) }}" method="POST" enctype="multipart/form-data" class="px-6 py-6 space-y-6">
                    @csrf
                    
                    {{-- Drag and Drop Area --}}
                    <div class="border-2 border-dashed border-slate-300 rounded-2xl p-8 text-center hover:border-sky-500 hover:bg-sky-50/20 transition cursor-pointer relative"
                         x-data="{ active: false }"
                         @dragover.prevent="active = true"
                         @dragleave.prevent="active = false"
                         @drop.prevent="active = false; $refs.fileInput.files = $event.dataTransfer.files; fileName = $refs.fileInput.files[0].name"
                         :class="{ 'border-sky-500 bg-sky-50/30': active }"
                         @click="$refs.fileInput.click()">
                         
                        <input type="file" name="assessment_file" x-ref="fileInput" accept=".xlsx,.xls,.csv" class="hidden" required
                               @change="fileName = $refs.fileInput.files[0] ? $refs.fileInput.files[0].name : ''">
                               
                        <div class="w-12 h-12 rounded-full bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-400 mx-auto mb-3">
                            <i class="fas fa-cloud-upload-alt text-xl"></i>
                        </div>
                        <p class="text-sm font-semibold text-slate-700">Drag & Drop your file here or click to browse</p>
                        <p class="text-xs text-slate-400 mt-1">Supports Excel or CSV files up to 10MB</p>
                        
                        <div class="mt-4 p-2 bg-sky-50/50 border border-sky-100 rounded-lg text-xs font-bold text-sky-700 inline-flex items-center gap-1.5" x-show="fileName">
                            <i class="fas fa-file-excel text-emerald-600"></i>
                            <span x-text="fileName"></span>
                        </div>
                    </div>

                    {{-- Warning Note --}}
                    <div class="p-4 bg-amber-50 border border-amber-200 rounded-xl flex items-start gap-3">
                        <i class="fas fa-info-circle text-amber-600 mt-0.5 text-base"></i>
                        <div class="text-xs text-amber-800 leading-normal">
                            <strong>Note:</strong> Importing a new gap assessment file will replace all existing gap assessment records for this project.
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-3 border-t border-slate-100">
                        <button type="button" @click="importModalOpen = false"
                                class="px-4 py-2.5 text-sm font-semibold text-slate-600 bg-slate-100 rounded-xl hover:bg-slate-200 transition-all">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-6 py-2.5 text-sm font-bold text-white bg-slate-900 rounded-xl hover:bg-slate-800 transition-all shadow-md">
                            Import Data
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endcan
</div>
@endsection

@push('scripts')
<script>
function pciGapTracker() {
    return {
        importModalOpen: false,
        fileName: '',
        saving: false,
        saved: false,
        triggerGlobalFeedback() {
            this.saved = true;
            setTimeout(() => { this.saved = false; }, 3000);
        }
    };
}

function rowEditor(id, initialStatus, initialNaExpl, initialMilestone, initialComments) {
    return {
        id: id,
        status: initialStatus,
        na_explanation: initialNaExpl,
        milestone_date: initialMilestone,
        comments: initialComments,
        
        statusClass(val) {
            return {
                'Pending': 'bg-amber-50 border border-amber-200 text-amber-800 focus:border-amber-500 focus:ring-amber-500',
                'Yes': 'bg-emerald-50 border border-emerald-200 text-emerald-800 focus:border-emerald-500 focus:ring-emerald-500',
                'No': 'bg-rose-50 border border-rose-200 text-rose-800 focus:border-rose-500 focus:ring-rose-500',
                'N/A': 'bg-slate-50 border border-slate-200 text-slate-600 focus:border-slate-500 focus:ring-slate-500'
            }[val] || 'bg-slate-50 border border-slate-200 text-slate-600';
        },
        
        async save() {
            // Check status change logic (clear explanation if not N/A)
            if (this.status !== 'N/A') {
                this.na_explanation = '';
            }
            
            this.$parent.saving = true;
            
            try {
                const response = await fetch(`{{ url('/pci-gap-assessments') }}/${this.id}`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        status: this.status,
                        na_explanation: this.na_explanation,
                        milestone_date: this.milestone_date || null,
                        comments: this.comments
                    })
                });
                
                if (!response.ok) {
                    throw new Error('Failed to update row');
                }
                
                const data = await response.json();
                this.status = data.data.status;
                this.na_explanation = data.data.na_explanation || '';
                this.milestone_date = data.data.milestone_date || '';
                this.comments = data.data.comments || '';
                
                this.$parent.saving = false;
                this.$parent.triggerGlobalFeedback();
            } catch (error) {
                this.$parent.saving = false;
                alert('Failed to save changes. Please try again.');
            }
        }
    };
}
</script>
@endpush
