{{-- resources/views/evidence/hub.blade.php --}}
@extends('layouts.app')

@push('styles')
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .font-outfit { font-family: 'Outfit', sans-serif; }
        .evidence-table th {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #475569;
            background-color: #f8fafc;
            border-bottom: 2px solid #e2e8f0;
            padding: 12px 16px;
        }
        .evidence-table td {
            font-size: 13px;
            color: #334155;
            padding: 16px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
        }
        .btn-action-green {
            background-color: #10b981;
            color: #ffffff;
        }
        .btn-action-green:hover {
            background-color: #059669;
        }
        .btn-action-orange {
            background-color: #f59e0b;
            color: #ffffff;
        }
        .btn-action-orange:hover {
            background-color: #d97706;
        }
        .btn-action-red {
            background-color: #ef4444;
            color: #ffffff;
        }
        .btn-action-red:hover {
            background-color: #dc2626;
        }
        .btn-action-blue {
            background-color: #0284c7;
            color: #ffffff;
        }
        .btn-action-blue:hover {
            background-color: #0369a1;
        }
        .btn-action-gray {
            background-color: #e2e8f0;
            color: #475569;
        }
        .btn-action-gray:hover {
            background-color: #cbd5e1;
        }
    </style>
@endpush

@section('content')
<div class="p-2 font-outfit max-w-full" x-data="evidenceHub()" x-init="initData()">
    
    {{-- Top Section --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-6 gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Auditor Evidence Tracker</h1>
            <p class="text-sm text-slate-500 mt-1">Enterprise-grade compliance evidence validation dashboard.</p>
        </div>
        
        {{-- Project Switcher Dropdown --}}
        <div class="flex items-center gap-3">
            <label for="project-switcher" class="text-xs font-bold text-slate-500 uppercase tracking-wider">Active Project:</label>
            <select id="project-switcher" 
                    @change="switchProject($event.target.value)" 
                    class="bg-white border border-slate-200 rounded-xl px-4 py-2 text-sm font-semibold text-slate-700 focus:outline-none focus:ring-4 focus:ring-sky-500/10 focus:border-sky-300 transition shadow-sm">
                @foreach($projects as $p)
                    <option value="{{ $p->id }}" {{ $p->id === $project->id ? 'selected' : '' }}>
                        {{ $p->name }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Main Container Card --}}
    <div class="bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden">
        
        {{-- Card Header matching the Mockup --}}
        <div class="px-6 py-5 border-b border-slate-100 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4" style="background: #f8fafc;">
            <div class="flex items-center gap-3">
                <div class="w-2.5 h-6 rounded-full bg-sky-500"></div>
                <h2 class="text-sm font-extrabold text-slate-800 uppercase tracking-widest">
                    Evidence Tracker - PCI-DSS Assessment ({{ $project->name }})
                </h2>
            </div>
            
            {{-- Mockup Buttons --}}
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('evidence.export-zip', $project) }}" class="inline-flex items-center px-4 py-2 text-[11px] font-bold uppercase tracking-wider rounded-xl text-white transition-all shadow-sm" style="background-color: #0f766e; hover:background-color: #0d5f58;">
                    <i class="fas fa-file-export mr-2"></i> Export Report
                </a>
                
                <button class="inline-flex items-center px-4 py-2 text-[11px] font-bold uppercase tracking-wider rounded-xl transition-all shadow-sm" style="background-color: #e2f0d9; color: #385723; border: 1px solid #c5e0b4;">
                    <i class="fas fa-file-excel mr-2"></i> Excel
                </button>
                
                <button class="inline-flex items-center px-4 py-2 text-[11px] font-bold uppercase tracking-wider rounded-xl transition-all shadow-sm" style="background-color: #fce4d6; color: #c65911; border: 1px solid #f8cbad;">
                    <i class="fas fa-file-pdf mr-2"></i> PDF
                </button>
                
                <div class="relative">
                    <button class="inline-flex items-center px-4 py-2 text-[11px] font-bold bg-white text-slate-700 border border-slate-200 rounded-xl hover:bg-slate-50 transition shadow-sm">
                        <i class="fas fa-filter mr-2 text-slate-400"></i> Filter <i class="fas fa-chevron-down ml-2 text-[9px] text-slate-400"></i>
                    </button>
                </div>
            </div>
        </div>

        {{-- Table Element --}}
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse table-fixed min-w-[1200px]">
                <thead>
                    <tr class="evidence-table">
                        <th class="w-[11%]">Framework Requirement</th>
                        <th class="w-[12%]">Evidence ID / File Name</th>
                        <th class="w-[9%]">Upload Date & Time</th>
                        <th class="w-[9%]">Security Status (ClamAV)</th>
                        <th class="w-[10%]">AI Preliminary Assessment</th>
                        <th class="w-[27%]">AI Evidence Observation</th>
                        <th class="w-[12%]">Auditor Determination</th>
                        <th class="w-[10%]">Auditor Feedback / Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    <template x-for="file in files" :key="file.id">
                        <tr class="hover:bg-slate-50/50 transition-colors group">
                            
                            {{-- Col 1: Framework Req --}}
                            <td class="px-4 py-4">
                                <div class="flex flex-col gap-1.5">
                                    <span class="inline-flex items-center w-fit px-2 py-0.5 rounded bg-slate-100 text-slate-800 border border-slate-200 text-[10px] font-bold uppercase tracking-wider" x-text="file.requirement ? file.requirement.req_num : 'General'"></span>
                                    <span class="text-xs font-semibold text-slate-700 leading-normal" x-text="file.requirement ? (file.requirement.description || file.requirement.req_description) : 'General Evidence File'"></span>
                                </div>
                            </td>

                            {{-- Col 2: File Name --}}
                            <td class="px-4 py-4">
                                <div class="flex items-start">
                                    <div class="w-8 h-8 rounded-lg bg-slate-50 border border-slate-200 flex items-center justify-center text-slate-400 mr-2.5 mt-0.5 flex-shrink-0 shadow-sm transition-transform group-hover:scale-105">
                                        <i class="fas" :class="getFileIcon(file.original_filename)"></i>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <a :href="'/api/evidence/file/' + file.id" target="_blank" 
                                           class="text-xs font-bold text-sky-600 hover:text-sky-800 hover:underline block truncate" 
                                           :title="'Download ' + file.original_filename">
                                            <span x-text="file.original_filename"></span>
                                            <i class="fas fa-download text-[9px] ml-1 text-sky-500 opacity-60 group-hover:opacity-100 transition-opacity"></i>
                                        </a>
                                        <span class="inline-block text-[9px] font-bold text-slate-400 mt-1 uppercase" x-text="'ID: #' + file.id"></span>
                                    </div>
                                </div>
                            </td>
                            
                            {{-- Col 3: Upload Date --}}
                            <td class="px-4 py-4 text-slate-500 font-semibold text-xs">
                                <div class="flex items-center gap-1.5">
                                    <i class="far fa-calendar-alt text-slate-400 text-[10px]"></i>
                                    <span x-text="formatDate(file.created_at)"></span>
                                </div>
                            </td>
                            
                            {{-- Col 4: ClamAV --}}
                            <td class="px-4 py-4">
                                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold border"
                                     :class="file.scan_status === 'clean' ? 'bg-emerald-50 text-emerald-700 border-emerald-200/60' : (file.scan_status === 'infected' ? 'bg-rose-50 text-rose-700 border-rose-200/60' : 'bg-amber-50 text-amber-700 border-amber-200/60')">
                                    <span class="w-1.5 h-1.5 rounded-full" 
                                          :class="file.scan_status === 'clean' ? 'bg-emerald-500' : (file.scan_status === 'infected' ? 'bg-rose-500' : 'bg-amber-400')"></span>
                                    <span x-text="file.scan_status === 'clean' ? 'Clean' : (file.scan_status === 'infected' ? 'Infected' : 'Scanning')"></span>
                                </div>
                            </td>
                            
                            {{-- Col 5: AI Assessment --}}
                            <td class="px-4 py-4">
                                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold border"
                                     :class="getAssessmentBadgeClass(file)">
                                    <span class="w-1.5 h-1.5 rounded-full" :class="getAssessmentDot(file)"></span>
                                    <span x-text="getAssessmentLabel(file)"></span>
                                </div>
                            </td>
                            
                            {{-- Col 6: AI Observation --}}
                            <td class="px-4 py-4">
                                <div class="flex flex-col gap-2">
                                    <template x-if="file.scan_status === 'infected'">
                                        <div class="p-3 rounded-xl bg-rose-50/50 border border-rose-100">
                                            <span class="inline-flex items-center gap-1 text-[10px] font-bold text-rose-700 uppercase tracking-wider mb-1">
                                                <i class="fas fa-shield-virus"></i> Blocked by ClamAV
                                            </span>
                                            <p class="text-xs text-rose-600 font-medium leading-relaxed" x-text="file.ai_observations || 'Threat detected: file containing malware was deleted.'"></p>
                                        </div>
                                    </template>
                                    <template x-if="file.scan_status !== 'infected'">
                                        <div class="flex flex-col gap-2.5">
                                            {{-- Observation Container --}}
                                            <div class="p-3.5 rounded-xl bg-indigo-50/30 border border-indigo-100/50 hover:border-indigo-200/60 transition shadow-sm">
                                                <span class="inline-flex items-center gap-1.5 text-[10px] font-extrabold text-indigo-700 uppercase tracking-widest mb-1.5">
                                                    <i class="fas fa-robot text-[10px]"></i> AI Observation
                                                </span>
                                                <p class="text-xs text-slate-700 leading-relaxed font-medium" x-text="file.ai_observations || 'Analysis pending...'"></p>
                                            </div>
                                            
                                            {{-- Recommendation Container (if generated) --}}
                                            <template x-if="file.ai_recommendations && file.ai_recommendations !== 'None' && file.ai_recommendations.trim() !== ''">
                                                <div class="p-3.5 rounded-xl bg-amber-50/30 border border-amber-100/50 hover:border-amber-200/60 transition shadow-sm">
                                                    <span class="inline-flex items-center gap-1.5 text-[10px] font-extrabold text-amber-700 uppercase tracking-widest mb-1.5">
                                                        <i class="fas fa-lightbulb text-[10px]"></i> Actionable Recommendation
                                                    </span>
                                                    <p class="text-xs text-slate-600 leading-relaxed font-medium" x-text="file.ai_recommendations"></p>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </td>
                            
                            {{-- Col 7: Auditor Determination --}}
                            <td class="px-4 py-4">
                                <div class="flex flex-col gap-3">
                                    <div class="flex items-center gap-2">
                                        <span class="w-1.5 h-1.5 rounded-full"
                                              :class="file.hitl_status === 'accepted' ? 'bg-emerald-500' : (file.hitl_status === 'action_required' ? 'bg-rose-500' : 'bg-slate-400')"></span>
                                        <span class="text-xs font-bold uppercase tracking-wider" 
                                              :class="file.hitl_status === 'accepted' ? 'text-emerald-700' : (file.hitl_status === 'action_required' ? 'text-rose-700' : 'text-slate-500')">
                                            <span x-text="file.hitl_status === 'accepted' ? 'Accepted' : (file.hitl_status === 'action_required' ? 'Action Req.' : 'Pending Review')"></span>
                                        </span>
                                    </div>
                                    
                                    {{-- Action Buttons --}}
                                    <div class="flex flex-col gap-1.5 w-full">
                                        <template x-if="file.hitl_status === 'accepted'">
                                            <div class="flex flex-col gap-1 w-full">
                                                <div class="w-full py-1.5 px-2 text-[10px] font-extrabold uppercase tracking-wider rounded-lg bg-emerald-50 text-emerald-800 border border-emerald-200/50 flex items-center justify-center gap-1.5 select-none">
                                                    <i class="fas fa-check-circle text-emerald-600"></i> Validated
                                                </div>
                                                <button @click="openViewDetails(file)" class="w-full py-1.5 px-2 text-[10px] font-black uppercase tracking-wider rounded-lg btn-action-blue transition-transform hover:scale-[1.02] shadow-sm flex items-center justify-center gap-1">
                                                    <i class="fas fa-expand-alt"></i> Details
                                                </button>
                                            </div>
                                        </template>
                                        
                                        <template x-if="file.hitl_status !== 'accepted' && file.scan_status !== 'infected'">
                                            <div class="flex flex-col gap-1.5 w-full">
                                                <button @click="updateStatus(file, 'accept')" class="w-full py-2 px-2 text-[10px] font-black uppercase tracking-wider rounded-lg btn-action-green transition-transform hover:scale-[1.02] shadow-sm flex items-center justify-center gap-1">
                                                    <i class="fas fa-check-circle"></i> Accept & Approve
                                                </button>
                                                <button @click="openRejectionModal(file, 'ai')" class="w-full py-2 px-2 text-[10px] font-black uppercase tracking-wider rounded-lg btn-action-orange transition-transform hover:scale-[1.02] shadow-sm flex items-center justify-center gap-1">
                                                    <i class="fas fa-sync-alt"></i> Re-analyse (AI)
                                                </button>
                                                <button @click="openRejectionModal(file, 'customer')" class="w-full py-2 px-2 text-[10px] font-black uppercase tracking-wider rounded-lg btn-action-red transition-transform hover:scale-[1.02] shadow-sm flex items-center justify-center gap-1">
                                                    <i class="fas fa-times-circle"></i> Reject to Client
                                                </button>
                                            </div>
                                        </template>
                                        
                                        <template x-if="file.scan_status === 'infected'">
                                            <div class="p-2.5 rounded-lg bg-rose-50 text-rose-800 border border-rose-100 flex items-center gap-1.5 text-xs font-bold select-none">
                                                <i class="fas fa-ban text-rose-600"></i> N/A (Threat)
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </td>
                            
                            {{-- Col 8: Feedback Notes --}}
                            <td class="px-4 py-4">
                                <div class="space-y-2">
                                    <template x-if="editingFeedbackId !== file.id">
                                        <div class="group relative flex flex-col gap-1.5">
                                            <p class="text-xs text-slate-700 bg-slate-50 border border-slate-200/60 p-3 rounded-xl leading-relaxed shadow-sm min-h-[50px] font-medium" 
                                               x-text="getFeedbackText(file) || 'No comments added.'"></p>
                                            
                                            <button @click="startEditFeedback(file)" 
                                                    class="text-[10px] font-black text-sky-600 hover:text-sky-800 uppercase tracking-widest flex items-center gap-1.5 transition self-start">
                                                <i class="fas fa-edit text-[9px]"></i> Add / Edit Note
                                            </button>
                                        </div>
                                    </template>
                                    
                                    <template x-if="editingFeedbackId === file.id">
                                        <div class="space-y-2">
                                            <textarea x-model="feedbackInput" 
                                                      rows="3" 
                                                      class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition shadow-inner font-medium"
                                                      placeholder="Write feedback for this evidence..."></textarea>
                                            <div class="flex gap-2">
                                                <button @click="saveFeedback(file)" class="flex-1 py-1.5 px-2 text-[9px] font-black uppercase tracking-widest rounded-lg bg-sky-600 text-white hover:bg-sky-700 transition shadow-sm">
                                                    Save Note
                                                </button>
                                                <button @click="cancelEditFeedback()" class="flex-1 py-1.5 px-2 text-[9px] font-black uppercase tracking-widest rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200 transition">
                                                    Cancel
                                                </button>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </td>
                            
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
    
    {{-- Details Modal --}}
    <div x-show="detailsModalOpen" 
         class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4" 
         @keydown.escape.window="detailsModalOpen = false" 
         x-cloak>
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-xl overflow-hidden border border-slate-100" @click.away="detailsModalOpen = false">
            <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between" style="background: #f8fafc;">
                <div class="flex items-center gap-2">
                    <i class="fas fa-file-invoice text-indigo-500"></i>
                    <h3 class="text-md font-extrabold text-slate-800 uppercase tracking-widest">Evidence Detailed File Audit</h3>
                </div>
                <button @click="detailsModalOpen = false" class="text-slate-400 hover:text-slate-600 transition">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            
            <div class="p-6 space-y-4 text-sm" x-if="selectedFile">
                <div>
                    <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1">File Name</h4>
                    <p class="font-bold text-slate-800" x-text="selectedFile.original_filename"></p>
                </div>
                <div>
                    <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1">Requirement</h4>
                    <p class="font-semibold text-slate-700" x-text="selectedFile.requirement ? selectedFile.requirement.req_num + ' - ' + (selectedFile.requirement.description || selectedFile.requirement.req_description) : 'N/A'"></p>
                </div>
                <div>
                    <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1">AI Audit Observations</h4>
                    <p class="text-slate-600 bg-slate-50 p-3 rounded-xl border border-slate-100 leading-relaxed italic" x-text="selectedFile.ai_observations || 'No observations'"></p>
                </div>
                <div>
                    <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1">AI Actionable Recommendations</h4>
                    <p class="text-slate-600 bg-slate-50 p-3 rounded-xl border border-slate-100 leading-relaxed italic" x-text="selectedFile.ai_recommendations || 'None'"></p>
                </div>
            </div>
            
            <div class="px-6 py-4 border-t border-slate-100 flex justify-end bg-slate-50">
                <button @click="detailsModalOpen = false" class="px-5 py-2 text-xs font-bold uppercase tracking-wider text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition">
                    Close Audit
                </button>
            </div>
        </div>
    </div>

    {{-- Rejection Note Modal --}}
    <div x-show="rejectionModalOpen" 
         class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4" 
         @keydown.escape.window="rejectionModalOpen = false" 
         x-cloak
         x-transition>
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden border border-slate-100" @click.away="rejectionModalOpen = false">
            <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between" style="background: #f8fafc;">
                <div class="flex items-center gap-2">
                    <i class="fas fa-exclamation-triangle" :class="rejectionType === 'ai' ? 'text-amber-500' : 'text-rose-500'"></i>
                    <h3 class="text-sm font-extrabold text-slate-800 uppercase tracking-widest" x-text="rejectionType === 'ai' ? 'Reject to AI Re-Analysis' : 'Reject to Customer'"></h3>
                </div>
                <button @click="rejectionModalOpen = false" class="text-slate-400 hover:text-slate-600 transition">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            
            <div class="p-6 space-y-4">
                <div>
                    <p class="text-xs text-slate-600 mb-1" x-text="'File: ' + (rejectionFile ? rejectionFile.original_filename : '')"></p>
                    <p class="text-xs text-slate-400 mb-3" x-text="rejectionType === 'ai' ? 'This will trigger a new AI analysis. A note explaining the reason is required.' : 'This will flag the evidence for customer remediation. A note explaining the issue is required.'"></p>
                </div>
                <div>
                    <label class="text-xs font-bold text-slate-700 uppercase tracking-wider block mb-2">Rejection Note <span class="text-rose-500">*</span></label>
                    <textarea x-model="rejectionNote" 
                              rows="4" 
                              class="w-full text-sm p-3 border-2 rounded-xl focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition"
                              :class="rejectionNoteError ? 'border-rose-300 bg-rose-50/30' : 'border-slate-200'"
                              placeholder="Explain why this evidence is being rejected..."></textarea>
                    <p x-show="rejectionNoteError" class="text-xs text-rose-500 mt-1 font-semibold">A rejection note is required before proceeding.</p>
                </div>
            </div>
            
            <div class="px-6 py-4 border-t border-slate-100 flex justify-end gap-3 bg-slate-50">
                <button @click="rejectionModalOpen = false" class="px-4 py-2 text-xs font-bold uppercase tracking-wider text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition">
                    Cancel
                </button>
                <button @click="submitRejection()" 
                        class="px-5 py-2 text-xs font-bold uppercase tracking-wider text-white rounded-xl transition shadow-sm"
                        :class="rejectionType === 'ai' ? 'bg-amber-500 hover:bg-amber-600' : 'bg-rose-500 hover:bg-rose-600'">
                    <i class="fas mr-1" :class="rejectionType === 'ai' ? 'fa-robot' : 'fa-paper-plane'"></i>
                    <span x-text="rejectionType === 'ai' ? 'Send to AI' : 'Send to Customer'"></span>
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function evidenceHub() {
    return {
        files: @json($evidenceFiles),
        editingFeedbackId: null,
        feedbackInput: '',
        detailsModalOpen: false,
        selectedFile: null,
        // Rejection modal state
        rejectionModalOpen: false,
        rejectionFile: null,
        rejectionType: 'ai', // 'ai' or 'customer'
        rejectionNote: '',
        rejectionNoteError: false,

        initData() {
            // Start polling status every 5 seconds
            setInterval(() => this.pollLoop(), 5000);
        },

        async pollLoop() {
            for (let f of this.files) {
                if (f.hitl_status === 'accepted' || f.scan_status === 'infected') continue;
                try {
                    const r = await fetch(`/evidence/${f.id}/status`);
                    if (r.ok) {
                        const data = await r.json();
                        f.scan_status = data.scan_status;
                        f.ai_analysis_status = data.ai_analysis_status;
                        f.hitl_status = data.hitl_status;
                        f.ai_observations = data.ai_observations;
                        f.ai_recommendations = data.ai_recommendations;
                    }
                } catch (e) {}
            }
        },

        switchProject(projectId) {
            window.location.href = "{{ url('evidence-hub') }}/" + projectId;
        },

        getFileIcon(filename) {
            if (!filename) return 'fa-file-shield';
            const ext = filename.split('.').pop().toLowerCase();
            switch (ext) {
                case 'pdf': return 'fa-file-pdf text-rose-500';
                case 'xls':
                case 'xlsx':
                case 'csv': return 'fa-file-excel text-emerald-500';
                case 'jpg':
                case 'jpeg':
                case 'png': return 'fa-file-image text-sky-500';
                case 'doc':
                case 'docx': return 'fa-file-word text-blue-500';
                case 'ps1':
                case 'sh':
                case 'bat': return 'fa-file-code text-amber-500';
                default: return 'fa-file-shield text-slate-400';
            }
        },

        formatDate(dateStr) {
            if (!dateStr) return '';
            const d = new Date(dateStr);
            const yr = d.getFullYear();
            const mo = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            let hr = d.getHours();
            const min = String(d.getMinutes()).padStart(2, '0');
            const ampm = hr >= 12 ? 'PM' : 'AM';
            hr = hr % 12;
            hr = hr ? hr : 12;
            const formattedHour = String(hr).padStart(2, '0');
            return `${yr}-${mo}-${day} ${formattedHour}:${min} ${ampm}`;
        },

        getFeedbackText(file) {
            if (file.feedbacks && file.feedbacks.length > 0) {
                return file.feedbacks[file.feedbacks.length - 1].message;
            }
            return '';
        },

        getAssessmentDot(file) {
            if (file.scan_status === 'infected') return 'bg-slate-400';
            if (file.ai_analysis_status === 'failed') return 'bg-rose-500';
            if (file.hitl_status === 'accepted' || file.ai_analysis_status === 'completed' || file.ai_analysis_status === 'approved') return 'bg-emerald-500';
            if (file.hitl_status === 'action_required' || file.ai_analysis_status === 'rejected') return 'bg-rose-500';
            if (file.ai_analysis_status === 'awaiting_review' || file.hitl_status === 'pending_review') return 'bg-amber-400';
            if (file.ai_analysis_status === 'processing') return 'bg-sky-400';
            return 'bg-slate-300';
        },

        getAssessmentLabel(file) {
            if (file.scan_status === 'infected') return 'Blocked';
            if (file.ai_analysis_status === 'failed') return 'Failed Analysis';
            if (file.hitl_status === 'accepted' || file.ai_analysis_status === 'completed' || file.ai_analysis_status === 'approved') return 'Sufficient';
            if (file.hitl_status === 'action_required' || file.ai_analysis_status === 'rejected') return 'Deficient';
            if (file.ai_analysis_status === 'awaiting_review' || file.hitl_status === 'pending_review') return 'Partially Compliant';
            if (file.ai_analysis_status === 'processing') return 'Processing...';
            return 'Analysis Pending';
        },

        getAssessmentTextClass(file) {
            if (file.scan_status === 'infected') return 'text-slate-500';
            const label = this.getAssessmentLabel(file);
            switch (label) {
                case 'Sufficient': return 'text-emerald-700';
                case 'Partially Compliant': return 'text-amber-700';
                case 'Deficient': return 'text-rose-700';
                case 'Processing...': return 'text-sky-700';
                default: return 'text-slate-500';
            }
        },

        getAssessmentBadgeClass(file) {
            if (file.scan_status === 'infected') return 'bg-slate-50 text-slate-500 border-slate-200/60';
            const label = this.getAssessmentLabel(file);
            switch (label) {
                case 'Sufficient': return 'bg-emerald-50 text-emerald-700 border-emerald-200/60';
                case 'Partially Compliant': return 'bg-amber-50 text-amber-700 border-amber-200/60';
                case 'Deficient': return 'bg-rose-50 text-rose-700 border-rose-200/60';
                case 'Processing...': return 'bg-sky-50 text-sky-700 border-sky-200/60 animate-pulse';
                default: return 'bg-slate-50 text-slate-500 border-slate-200/60';
            }
        },

        startEditFeedback(file) {
            this.editingFeedbackId = file.id;
            this.feedbackInput = this.getFeedbackText(file);
        },

        cancelEditFeedback() {
            this.editingFeedbackId = null;
            this.feedbackInput = '';
        },

        saveFeedback(file) {
            fetch(`/evidence/${file.id}/feedback`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    message: this.feedbackInput,
                    action: 'reply'
                })
            })
            .then(res => res.json())
            .then(data => {
                if (!file.feedbacks) file.feedbacks = [];
                file.feedbacks.push({ message: this.feedbackInput });
                this.editingFeedbackId = null;
            })
            .catch(err => alert('Failed to save feedback note.'));
        },

        updateStatus(file, action) {
            // Accept action doesn't need a note
            fetch(`/evidence/${file.id}/feedback`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    message: action === 'accept' ? 'Evidence accepted and validated by auditor' : 'Auditor returned evidence for correction',
                    action: action
                })
            })
            .then(res => {
                if (!res.ok) throw new Error('Failed');
                return res.json();
            })
            .then(data => {
                file.hitl_status = action === 'accept' ? 'accepted' : 'action_required';
            })
            .catch(err => alert('Failed to update status.'));
        },

        // Open the rejection note modal
        openRejectionModal(file, type) {
            this.rejectionFile = file;
            this.rejectionType = type;
            this.rejectionNote = '';
            this.rejectionNoteError = false;
            this.rejectionModalOpen = true;
        },

        // Submit the rejection with a mandatory note
        async submitRejection() {
            if (!this.rejectionNote.trim()) {
                this.rejectionNoteError = true;
                return;
            }
            this.rejectionNoteError = false;
            const file = this.rejectionFile;

            if (this.rejectionType === 'ai') {
                // Reject to AI for re-analysis
                try {
                    const res = await fetch(`/evidence/${file.id}/ai/reject`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({ note: this.rejectionNote })
                    });
                    const data = await res.json();
                    if (data.status === 'success') {
                        file.ai_analysis_status = 'processing';
                        file.ai_observations = 'Re-analysis in progress...';
                        file.hitl_status = 'pending_review';
                        if (!file.feedbacks) file.feedbacks = [];
                        file.feedbacks.push({ message: '[AI Rejection Note] ' + this.rejectionNote });
                        this.rejectionModalOpen = false;
                    } else {
                        alert(data.message || 'Failed to trigger AI re-analysis.');
                    }
                } catch (err) {
                    alert('Network error: Failed to trigger AI re-analysis.');
                }
            } else {
                // Reject to Customer
                try {
                    const res = await fetch(`/evidence/${file.id}/feedback`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            message: this.rejectionNote,
                            action: 'return'
                        })
                    });
                    const data = await res.json();
                    if (data.status === 'success') {
                        file.hitl_status = 'action_required';
                        if (!file.feedbacks) file.feedbacks = [];
                        file.feedbacks.push({ message: this.rejectionNote });
                        this.rejectionModalOpen = false;
                    } else {
                        alert(data.message || 'Failed to reject to customer.');
                    }
                } catch (err) {
                    alert('Network error: Failed to reject to customer.');
                }
            }
        },

        openViewDetails(file) {
            this.selectedFile = file;
            this.detailsModalOpen = true;
        }
    };
}
</script>
@endpush
