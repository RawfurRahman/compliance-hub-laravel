{{-- resources/views/evidence/show.blade.php --}}
@extends('layouts.app')

@push('styles')
    <link href="{{ asset('fonts/outfit.css') }}" rel="stylesheet">
    <style nonce="{{ $cspNonce }}">
        body { font-family: 'Outfit', sans-serif; background-color: #f8fafc; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; height: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        [x-cloak] { display: none !important; }
        .glass-panel { 
            background: rgba(255, 255, 255, 0.7); 
            backdrop-filter: blur(20px); 
            -webkit-backdrop-filter: blur(20px); 
            border: 1px solid rgba(255, 255, 255, 0.9); 
            box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.05); 
        }
        
        .upload-zone {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .upload-zone.drag-active {
            background-color: #eef2ff !important;
            border-color: #6366f1 !important;
            transform: scale(1.02);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }
    </style>
@endpush

@section('content')
<div class="min-h-screen bg-slate-50/50" x-data="premiumEvidenceWorkspace" x-init="onBoot()">
    
    {{-- Main Top Header --}}
    <div class="bg-white border-b border-slate-200 px-8 py-6 sticky top-0 z-40 shadow-sm backdrop-blur-md bg-white/90">
        <div class="max-w-[1600px] mx-auto flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white shadow-lg shadow-indigo-500/30">
                    <i class="fas fa-microchip text-xl"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Compliance Project</span>
                        <i class="fas fa-chevron-right text-[8px] text-slate-300"></i>
                        <span class="text-[10px] font-black text-indigo-600 uppercase tracking-[0.2em]">Integrity Hub</span>
                    </div>
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">{{ $project->name }}</h1>
                </div>
            </div>

            <div class="flex items-center gap-3">
                 @can('is-auditor')
                    <a href="{{ route('evidence.export-zip', $project) }}" class="px-5 py-2.5 rounded-xl text-[11px] font-black uppercase tracking-wider text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 transition-all shadow-sm flex items-center group">
                        <i class="fas fa-file-export mr-2 text-indigo-500 group-hover:scale-110 transition-transform"></i> Export Compliance ZIP
                    </a>
                @endcan
            </div>
        </div>
    </div>

    <div class="max-w-[1600px] mx-auto px-8 py-8">
        <div class="flex flex-col lg:flex-row gap-8 items-start">
            
            {{-- Left Sidebar Navigation (Domains) --}}
            <div class="w-full lg:w-72 flex-shrink-0 sticky top-32 z-30">
                <div class="glass-panel rounded-3xl p-5 border border-white">
                    <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-4 px-2">Framework Domains</h3>
                    <nav class="space-y-1">
                        <template x-for="dom in domains" :key="dom">
                            <a :href="'#domain-' + slugify(dom)" 
                               @click="activeDomain = dom"
                               :class="activeDomain === dom ? 'bg-indigo-50 text-indigo-700 border-indigo-100 shadow-sm' : 'text-slate-600 hover:bg-slate-100/60 border-transparent'"
                               class="flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-bold transition-all border">
                                <span class="truncate pr-2" x-text="dom"></span>
                                <span class="px-2 py-0.5 rounded-md text-[9px] font-black"
                                      :class="activeDomain === dom ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-200 text-slate-500'"
                                      x-text="getReqsByDomain(dom).length"></span>
                            </a>
                        </template>
                    </nav>

                    <div class="mt-8 pt-6 border-t border-slate-100">
                        <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-4 px-2">Project Pulse</h3>
                        <div class="space-y-4 max-h-60 overflow-y-auto custom-scrollbar px-2 pr-4">
                            <template x-for="act in activities" :key="act.time + act.user">
                                <div class="flex gap-3 group">
                                    <div class="w-6 h-6 rounded-lg bg-white border border-slate-100 flex items-center justify-center shadow-sm group-hover:scale-110 transition-all flex-shrink-0">
                                        <i :class="'fas ' + act.icon" class="text-[9px] text-slate-400"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-[10px] font-bold text-slate-800 leading-tight">
                                            <span class="text-indigo-600" x-text="act.user"></span>
                                            <span x-show="act.type==='upload'"> provisioned asset for <span x-text="act.req"></span></span>
                                            <span x-show="act.type==='comment'"> updated review</span>
                                        </p>
                                        <span class="text-[8px] font-black text-slate-400 uppercase mt-0.5 block" x-text="act.time"></span>
                                    </div>
                                </div>
                            </template>
                            <template x-if="!activities.length">
                                <p class="text-[10px] font-medium text-slate-400 italic">No recent activity.</p>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Main Content Column --}}
            <div class="flex-1 min-w-0 pb-32">
                <template x-for="dom in domains" :key="dom">
                    <div :id="'domain-' + slugify(dom)" class="scroll-mt-32 mb-12">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-8 h-8 rounded-xl bg-slate-900 flex items-center justify-center text-white shadow-md">
                                <i class="fas fa-layer-group text-sm"></i>
                            </div>
                            <h2 class="text-xl font-black text-slate-900 tracking-tight" x-text="dom"></h2>
                        </div>
                        
                        <div class="space-y-6">
                            <template x-for="req in getReqsByDomain(dom)" :key="req.id">
                                <div class="glass-panel rounded-3xl p-6 md:p-8 bg-white/60 hover:bg-white/90 transition-colors shadow-sm hover:shadow-xl group/card relative overflow-hidden">
                                    
                                    {{-- N/A Overlay (if applicable) --}}
                                    <div x-show="req.is_applicable == 0" class="absolute inset-0 bg-slate-100/50 backdrop-blur-[2px] z-10 flex items-center justify-center">
                                        <div class="bg-white px-6 py-3 rounded-2xl shadow-lg border border-slate-200 flex items-center gap-3">
                                            <i class="fas fa-ban text-rose-500 text-xl"></i>
                                            <div>
                                                <div class="text-sm font-black text-slate-800">Control Marked N/A</div>
                                                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">Out of scope</div>
                                            </div>
                                            @if($isPci)
                                                @can('is-auditor')
                                                <button @click="toggleScope(req.id, true)" class="ml-4 px-4 py-1.5 bg-indigo-50 text-indigo-600 rounded-lg text-[10px] font-black uppercase tracking-widest hover:bg-indigo-100 transition-colors">
                                                    Include
                                                </button>
                                                @endcan
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Header --}}
                                    <div class="flex flex-col xl:flex-row xl:items-start justify-between gap-6 mb-6">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-3 mb-2">
                                                <span class="px-3 py-1 bg-slate-100 text-slate-800 rounded-lg text-sm font-black tracking-widest border border-slate-200 shadow-sm" x-text="req.req_num"></span>
                                                <span x-show="req.name" class="text-lg font-bold text-slate-800" x-text="req.name"></span>
                                            </div>
                                            <p class="text-sm text-slate-600 font-medium leading-relaxed max-w-4xl" x-text="req.description"></p>
                                        </div>
                                        
                                        <div class="flex items-center gap-2 xl:shrink-0">
                                            @if($isPci)
                                                @can('is-auditor')
                                                <button @click="toggleScope(req.id, false)" class="px-3 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-widest text-slate-400 bg-white border border-slate-200 hover:text-rose-500 hover:border-rose-200 transition-all shadow-sm">
                                                    Mark N/A
                                                </button>
                                                @endcan
                                            @endif
                                            
                                            <div class="px-4 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-widest bg-slate-50 border border-slate-100 shadow-inner flex items-center gap-2">
                                                <span class="text-indigo-600" x-text="(evidence[req.id] || []).length"></span>
                                                <span class="text-slate-400">Assets</span>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Inline Upload Zone --}}
                                    <div class="mb-6">
                                        <label class="upload-zone relative rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50/50 hover:bg-indigo-50/50 hover:border-indigo-300 transition-all overflow-hidden flex flex-col items-center justify-center p-6 text-center group cursor-pointer"
                                               @dragover.prevent="$el.classList.add('drag-active')" 
                                               @dragleave.prevent="$el.classList.remove('drag-active')" 
                                               @drop.prevent="$el.classList.remove('drag-active'); handleDrop($event, req.id)">
                                            <input type="file" name="file" class="hidden" @change="handleFileSelect($event, req.id)">
                                            
                                            <div class="w-12 h-12 rounded-full bg-white shadow-sm border border-slate-100 flex items-center justify-center text-slate-400 group-hover:text-indigo-500 group-hover:scale-110 transition-all mb-3">
                                                <i class="fas fa-cloud-upload-alt text-xl"></i>
                                            </div>
                                            <p class="text-[12px] font-bold text-slate-700">Drag & drop evidence here, or <span class="text-indigo-600">browse</span></p>
                                            <p class="text-[10px] font-medium text-slate-400 mt-1">Supports PDF, DOCX, XLSX, PNG, JPG (Max 50MB)</p>
                                        </label>
                                    </div>

                                    {{-- Evidence Grid/Table --}}
                                    <div x-show="(evidence[req.id] || []).length > 0" class="rounded-2xl border border-slate-100 bg-white overflow-hidden shadow-sm">
                                        <table class="min-w-full text-left">
                                            <thead class="bg-slate-50 border-b border-slate-100">
                                                <tr>
                                                    <th class="px-5 py-3 text-[9px] font-black text-slate-500 uppercase tracking-widest w-[30%]">Evidence Asset</th>
                                                    <th class="px-5 py-3 text-[9px] font-black text-slate-500 uppercase tracking-widest w-[30%]">AI Synthesis</th>
                                                    <th class="px-5 py-3 text-[9px] font-black text-slate-500 uppercase tracking-widest w-[25%]">Status / HITL</th>
                                                    <th class="px-5 py-3 text-[9px] font-black text-slate-500 uppercase tracking-widest text-right w-[15%]">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-50">
                                                <template x-for="file in (evidence[req.id] || [])" :key="file.id">
                                                    <tr class="hover:bg-slate-50/50 transition-colors">
                                                        <td class="px-5 py-4 align-top">
                                                            <div class="flex items-start gap-3">
                                                                <div class="w-8 h-8 rounded-lg bg-indigo-50 border border-indigo-100 flex flex-shrink-0 items-center justify-center text-indigo-500">
                                                                    <i class="fas fa-file-alt text-sm"></i>
                                                                </div>
                                                                <div class="min-w-0">
                                                                    <div class="text-[12px] font-bold text-slate-800 truncate" x-text="file.original_filename"></div>
                                                                    <div class="flex items-center gap-2 mt-1">
                                                                        <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest" x-text="new Date(file.created_at).toLocaleDateString()"></span>
                                                                        <span x-show="file.gap_category" class="px-1.5 py-0.5 rounded text-[8px] font-black uppercase bg-rose-50 text-rose-600 border border-rose-100">Gap Detected</span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td class="px-5 py-4 align-top">
                                                            <div class="flex items-center gap-2 mb-1.5">
                                                                <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-widest flex items-center gap-1 shadow-sm"
                                                                      :class="file.scan_status === 'clean' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : (file.scan_status === 'infected' ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-amber-50 text-amber-700 border border-amber-200')">
                                                                    <i class="fas" :class="file.scan_status === 'clean' ? 'fa-check-circle' : (file.scan_status === 'infected' ? 'fa-shield-virus' : 'fa-virus-slash')"></i>
                                                                    <span x-text="file.scan_status === 'infected' ? 'Blocked' : (file.scan_status || 'Scanning')"></span>
                                                                </span>
                                                                <span x-show="file.ai_analysis_status === 'awaiting_review'" class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-widest bg-indigo-50 text-indigo-700 border border-indigo-200 shadow-sm">
                                                                    AI Ready
                                                                </span>
                                                            </div>
                                                            <p class="text-[11px] text-slate-600 font-medium line-clamp-2 mt-2 italic" x-text="file.ai_observations || 'Waiting for AI processing...'"></p>
                                                        </td>
                                                        <td class="px-5 py-4 align-top">
                                                            <div class="flex flex-col gap-2">
                                                                <span :class="getBadgeClass(file.hitl_status)" class="inline-flex items-center px-2.5 py-1 rounded-lg text-[9px] font-black tracking-widest shadow-sm w-max">
                                                                    <span class="w-1.5 h-1.5 rounded-full mr-1.5" :class="file.hitl_status === 'accepted' ? 'bg-emerald-500' : (file.hitl_status === 'action_required' ? 'bg-rose-500' : 'bg-amber-500')"></span>
                                                                    <span x-text="file.hitl_status || 'Waiting'"></span>
                                                                </span>
                                                                
                                                                <div x-show="file.feedbacks && file.feedbacks.length > 0" class="mt-1">
                                                                    <button @click="openFeedback(file)" class="text-[10px] font-bold text-sky-600 hover:text-sky-800 flex items-center gap-1 transition-colors">
                                                                        <i class="fas fa-comment-dots"></i> <span x-text="file.feedbacks.length + ' comments'"></span>
                                                                    </button>
                                                                </div>
                                                                <div x-show="!file.feedbacks || file.feedbacks.length === 0" class="mt-1">
                                                                     <button @click="openFeedback(file)" class="text-[9px] font-bold text-slate-400 hover:text-indigo-600 transition-colors uppercase tracking-widest">
                                                                        + Add Note
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td class="px-5 py-4 align-top text-right">
                                                             @can('is-auditor')
                                                                <div class="flex flex-col items-end gap-1.5">
                                                                    <div class="flex items-center gap-1 mb-1">
                                                                        <button @click="directAudit(file, 'accept')" class="w-7 h-7 rounded-lg text-emerald-600 bg-emerald-50 hover:bg-emerald-600 hover:text-white transition-all shadow-sm flex items-center justify-center" title="Accept Evidence">
                                                                            <i class="fas fa-check text-xs"></i>
                                                                        </button>
                                                                        <button @click="openReview(file)" class="w-7 h-7 rounded-lg text-rose-600 bg-rose-50 hover:bg-rose-600 hover:text-white transition-all shadow-sm flex items-center justify-center" title="Return for Correction">
                                                                            <i class="fas fa-undo-alt text-xs"></i>
                                                                        </button>
                                                                    </div>
                                                                    <button @click="openReview(file)" class="text-[10px] font-black text-indigo-600 uppercase tracking-widest hover:underline px-2 py-1 bg-indigo-50/50 rounded-lg">
                                                                        Deep Audit
                                                                    </button>
                                                                </div>
                                                            @endcan
                                                            @cannot('is-auditor')
                                                                <button @click="openReview(file)" class="text-[10px] font-black text-indigo-600 uppercase tracking-widest border border-indigo-200 px-3 py-1.5 rounded-lg bg-white hover:bg-indigo-50 transition-all shadow-sm">
                                                                    View Analysis
                                                                </button>
                                                            @endcannot
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
                </template>
            </div>
        </div>
    </div>
    
    {{-- JSON DATA STORE --}}
    <script id="integrity-hub-data" type="application/json" nonce="{{ $cspNonce }}">
        {
            "requirements": {!! json_encode($requirements) !!},
            "evidence": {!! $evidenceByRequirement->toJson() !!},
            "domains": {!! json_encode($domains) !!}
        }
    </script>
</div>

{{-- Slide Over Review Panel (Restored) --}}
<div x-show="showReviewPanel" class="fixed inset-0 z-50 overflow-hidden" x-cloak>
    <div class="absolute inset-0 bg-slate-900/30 backdrop-blur-sm transition-opacity" @click="showReviewPanel = false"></div>
    <div class="fixed inset-y-0 right-0 max-w-full flex">
        <div class="w-screen max-w-5xl bg-white rounded-l-[40px] shadow-2xl border-l border-slate-100 overflow-y-auto transform transition-all duration-500"
             x-transition:enter="translate-x-full" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
             x-transition:leave="translate-x-full" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full">
            <template x-if="reviewFile">
                <div class="p-12 space-y-12">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-8">
                        <div class="flex items-center gap-6">
                            <div class="w-16 h-16 rounded-[20px] bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 text-2xl shadow-sm">
                                <i class="fas fa-file-contract"></i>
                            </div>
                            <div>
                                <h3 class="text-3xl font-black text-slate-900 tracking-tighter" x-text="reviewFile.original_filename"></h3>
                                <p class="text-[10px] font-black text-indigo-600 uppercase tracking-widest mt-1">Registry ID: #<span x-text="reviewFile.id"></span></p>
                            </div>
                        </div>
                        <button @click="showReviewPanel = false" class="w-12 h-12 rounded-full bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-400 hover:text-rose-500 transition-all shadow-sm"><i class="fas fa-times text-xl"></i></button>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="p-8 bg-indigo-50/50 rounded-3xl border border-indigo-100">
                            <h4 class="text-[10px] font-black text-indigo-700 uppercase tracking-widest mb-4"><i class="fas fa-robot mr-2"></i> AI Perspectives</h4>
                            <div class="text-sm text-slate-800 leading-relaxed font-semibold italic" x-text="reviewFile.ai_observations || 'Mining document observations...'"></div>
                        </div>
                        <div class="p-8 bg-emerald-50/50 rounded-3xl border border-emerald-100">
                            <h4 class="text-[10px] font-black text-emerald-700 uppercase tracking-widest mb-4"><i class="fas fa-lightbulb-on mr-2"></i> Integrity Recommendations</h4>
                            <div class="text-sm text-slate-800 leading-relaxed font-semibold italic" x-text="reviewFile.ai_recommendations || 'No critical recommendations.'"></div>
                        </div>
                    </div>

                    {{-- AI Gap Warnings --}}
                    <div x-show="reviewFile.gaps && reviewFile.gaps.length > 0" class="p-6 bg-amber-50 border border-amber-200 rounded-2xl">
                        <div class="flex items-start gap-3">
                            <i class="fas fa-exclamation-triangle text-amber-500 mt-0.5"></i>
                            <div class="flex-1">
                                <p class="text-sm font-bold text-amber-800">AI found potential gaps in this evidence</p>
                                <div class="mt-3 space-y-2">
                                    <template x-for="g in reviewFile.gaps" :key="g.gap">
                                        <div class="flex items-start gap-2 text-sm">
                                            <span :class="'px-1.5 py-0.5 rounded text-[10px] font-bold uppercase ' + (g.severity === 'high' ? 'bg-red-100 text-red-700' : g.severity === 'medium' ? 'bg-amber-100 text-amber-700' : 'bg-yellow-100 text-yellow-700')" x-text="g.severity"></span>
                                            <span class="text-slate-700" x-text="g.gap"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    @can('is-auditor')
                    {{-- Auditor Controls --}}
                    <div class="p-8 bg-slate-50/50 rounded-[32px] border border-slate-100">
                        <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-6 text-center">Auditor Adjudication</h4>
                        <div class="flex gap-4">
                            <button @click="submitAudit('accept')" class="flex-1 py-4 bg-indigo-600 text-white rounded-2xl font-black text-xs uppercase tracking-widest shadow-lg shadow-indigo-600/30 hover:bg-indigo-700 transition-all flex items-center justify-center">
                                <i class="fas fa-check-circle mr-2"></i> Accept Asset
                            </button>
                            <button @click="submitAudit('return')" class="flex-1 py-4 bg-white text-rose-600 border border-rose-100 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-rose-50 transition-all flex items-center justify-center">
                                <i class="fas fa-undo mr-2"></i> Request Revision
                            </button>
                        </div>
                        <div class="mt-6">
                             <textarea x-model="auditNotes" class="w-full p-6 bg-white border border-slate-200 rounded-2xl text-sm focus:outline-none focus:ring-4 focus:ring-indigo-600/10 min-h-[150px]" placeholder="Explain the rationale..."></textarea>
                        </div>
                    </div>
                    @endcan
                </div>
            </template>
        </div>
    </div>
</div>

{{-- Feedback / Communication Modal --}}
<div x-show="showFeedbackModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-md z-50 flex items-center justify-center p-4" x-cloak x-transition>
    <div class="bg-white rounded-[32px] shadow-2xl w-full max-w-2xl border border-white flex flex-col h-[600px]" @click.away="showFeedbackModal = false">
        <div class="px-8 py-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50 rounded-t-[32px]">
            <div>
                <h2 class="text-xl font-black text-slate-900 tracking-tight">Audit Communication</h2>
                <p class="text-[9px] font-black text-indigo-600 uppercase tracking-widest mt-1" x-text="reviewFile ? reviewFile.original_filename : ''"></p>
            </div>
            <button @click="showFeedbackModal = false" class="text-slate-400 hover:text-indigo-600 transition-colors"><i class="fas fa-times"></i></button>
        </div>
        
        <div class="flex-1 overflow-y-auto p-8 space-y-6 custom-scrollbar bg-slate-50/30">
            <template x-if="reviewFile && reviewFile.feedbacks">
                <template x-for="msg in reviewFile.feedbacks" :key="msg.id">
                    <div :class="msg.user_id == {{ auth()->id() }} ? 'items-end' : 'items-start'" class="flex flex-col space-y-2">
                         <div :class="msg.user_id == {{ auth()->id() }} ? 'bg-indigo-600 text-white rounded-l-2xl rounded-tr-2xl' : 'bg-white text-slate-800 rounded-r-2xl rounded-tl-2xl border border-slate-100'" class="px-5 py-3 max-w-[85%] shadow-sm">
                            <p class="text-sm font-semibold" x-text="msg.message"></p>
                         </div>
                          <span class="text-[8px] font-black text-slate-400 uppercase px-1" x-text="msg.user ? msg.user.username : 'Unknown'"></span>
                    </div>
                </template>
            </template>
        </div>

        <div class="p-6 bg-white border-t border-slate-100 rounded-b-[32px]">
            <textarea x-model="auditNotes" class="w-full p-4 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-4 focus:ring-indigo-600/10 min-h-[80px]" placeholder="Type your message..."></textarea>
            <div class="mt-4 flex justify-end gap-2">
                <button @click="submitAudit('reply')" class="px-6 py-2.5 bg-indigo-600 text-white rounded-xl font-black text-[10px] uppercase tracking-widest shadow-md shadow-indigo-600/20 hover:bg-indigo-700 transition-all">
                    Send Message
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script nonce="{{ $cspNonce }}">
document.addEventListener('alpine:init', () => {
    Alpine.data('premiumEvidenceWorkspace', () => {
        const _STORE = JSON.parse(document.getElementById('integrity-hub-data').textContent);
        
        return {
            projectId: {{ $project->id }},
            requirements: Object.values(_STORE.requirements || {}),
            evidence: _STORE.evidence || {},
            domains: Object.values(_STORE.domains || {}),
            activeDomain: null,
            activeReqId: null,
            activities: [],
            showReviewPanel: false,
            showFeedbackModal: false,
            reviewFile: null,
            auditNotes: '',

            onBoot() {
                if (this.domains && this.domains.length > 0) {
                    this.activeDomain = this.domains[0];
                }
                this.fetchPulse();
                setInterval(() => this.fetchPulse(), 12000);
                setInterval(() => this.pollLoop(), 5000);
                this.setupObserver();
            },

            setupObserver() {
                setTimeout(() => {
                    const observer = new IntersectionObserver((entries) => {
                        entries.forEach(entry => {
                            if (entry.isIntersecting) {
                                const id = entry.target.getAttribute('id');
                                if (id && id.startsWith('domain-')) {
                                    const rawDom = id.replace('domain-', '');
                                    const match = this.domains.find(d => this.slugify(d) === rawDom);
                                    if (match) this.activeDomain = match;
                                }
                            }
                        });
                    }, { rootMargin: '-30% 0px -70% 0px' });

                    this.domains.forEach(d => {
                        const el = document.getElementById('domain-' + this.slugify(d));
                        if (el) observer.observe(el);
                    });
                }, 500);
            },

            async handleDrop(e, reqId) {
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    this.submitFile(files[0], reqId);
                }
            },
            
            async handleFileSelect(e, reqId) {
                const files = e.target.files;
                if (files.length > 0) {
                    this.submitFile(files[0], reqId);
                }
            },
            
            submitFile(file, reqId) {
                const formData = new FormData();
                formData.append('file', file);
                formData.append('requirement_id', reqId);
                
                fetch('{{ route('evidence.upload', $project) }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: formData
                })
                .then(response => {
                    if (response.ok) {
                        window.location.reload(); 
                    } else {
                        alert('Upload failed.');
                    }
                })
                .catch(error => {
                    alert('Upload failed.');
                });
            },

            async fetchPulse() {
                try {
                    const r = await fetch(`/evidence/${this.projectId}/activities`);
                    if (r.ok) this.activities = await r.json();
                } catch (e) {}
            },

            getBadgeClass(s) {
                if (s === 'accepted') return 'bg-emerald-100 text-emerald-700 border border-emerald-200';
                if (s === 'action_required') return 'bg-rose-100 text-rose-700 border border-rose-200';
                return 'bg-amber-100 text-amber-700 border border-amber-200';
            },

            async toggleScope(id, state) {
                try {
                    const r = await fetch(`/evidence/${this.projectId}/${id}/toggle-scope`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
                        body: JSON.stringify({ is_applicable: state ? 1 : 0 })
                    });
                    if (r.ok) {
                        const target = this.requirements.find(req => req.id === id);
                        if (target) target.is_applicable = state ? 1 : 0;
                    }
                } catch (e) {}
            },

            openReview(f) {
                this.reviewFile = f;
                this.auditNotes = '';
                this.showReviewPanel = true;
            },

            openFeedback(f) {
                this.reviewFile = f;
                this.auditNotes = '';
                this.showFeedbackModal = true;
            },

            async directAudit(f, action) {
                if (!confirm(`Are you sure you want to ${action} this asset?`)) return;
                try {
                    const r = await fetch(`/evidence/${f.id}/feedback`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
                        body: JSON.stringify({ action, message: 'Direct Audit Action' })
                    });
                    if (r.ok) window.location.reload();
                } catch (e) {}
            },

            async submitAudit(action) {
                if ((action === 'return' || action === 'reply') && !this.auditNotes) { 
                    alert('Communication content required.'); 
                    return; 
                }
                try {
                    const r = await fetch(`/evidence/${this.reviewFile.id}/feedback`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
                        body: JSON.stringify({ action, message: this.auditNotes || 'Automated Synthesis Adjudication' })
                    });
                    if (r.ok) {
                        window.location.reload();
                    }
                } catch (e) {}
            },

            async pollLoop() {
                const allFiles = Object.values(this.evidence).flat();
                for (let f of allFiles) {
                    if (f.hitl_status === 'accepted' || f.scan_status === 'infected' || f.scan_status === 'failed') continue;
                    if (f.scan_status === 'pending' || f.ai_analysis_status === 'pending' || f.ai_analysis_status === 'processing') {
                        try {
                            const r = await fetch(`/evidence/${f.id}/status`);
                            if (r.ok) Object.assign(f, await r.json());
                        } catch (e) {}
                    }
                }
            },

            slugify(text) {
                return text.toString().toLowerCase()
                    .replace(/\s+/g, '-')
                    .replace(/[^\w\-]+/g, '')
                    .replace(/\-\-+/g, '-')
                    .replace(/^-+/, '')
                    .replace(/-+$/, '');
            },

            getReqsByDomain(dom) {
                return Object.values(this.requirements).filter(r => r.domain === dom);
            }
        }
    });
});
</script>
@endpush
