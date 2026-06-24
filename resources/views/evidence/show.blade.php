{{-- resources/views/evidence/show.blade.php --}}
@extends('layouts.app')

@push('styles')
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; background: #fdfdfd; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
        [x-cloak] { display: none !important; }
        .glass-premium { 
            background: rgba(255, 255, 255, 0.85); 
            backdrop-filter: blur(16px); 
            -webkit-backdrop-filter: blur(16px); 
            border: 1px solid rgba(255, 255, 255, 0.5); 
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.05); 
        }
        .active-ring { ring: 2px solid #6366f1; border-color: #6366f1 !important; box-shadow: 0 0 20px rgba(99,102,241,0.15) !important; }
    </style>
@endpush

@section('content')
<div class="p-8 min-h-screen" x-data="premiumEvidenceWorkspace()" x-init="onBoot()">
    
    {{-- Main Header --}}
    <div class="flex flex-col lg:flex-row lg:items-center justify-between mb-10 gap-6">
        <div class="flex items-center space-x-6">
            <div class="w-16 h-16 rounded-2xl bg-indigo-600 flex items-center justify-center text-white shadow-2xl">
                <i class="fas fa-microchip text-2xl"></i>
            </div>
            <div>
                <div class="flex items-center space-x-2 mb-1">
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Workspace</span>
                    <i class="fas fa-chevron-right text-[8px] text-slate-300"></i>
                    <span class="text-[10px] font-black text-indigo-600 uppercase tracking-widest">Integrity Ingest</span>
                </div>
                <h1 class="text-4xl font-black text-slate-900 tracking-tight">{{ $project->name }}</h1>
            </div>
        </div>

        <div class="flex items-center gap-3">
             @can('is-auditor')
                <a href="{{ route('evidence.export-zip', $project) }}" class="px-6 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 transition-all flex items-center shadow-sm">
                    <i class="fas fa-file-export mr-2 text-amber-500"></i> Export Compliance ZIP
                </a>
            @endcan
            <button @click="showUploadModal = true" class="px-8 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest bg-indigo-600 text-white shadow-xl hover:bg-indigo-700 transition-all">
                <i class="fas fa-plus mr-2"></i> Provision Asset
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        {{-- Main Column: Quick Jump menu and Control Cards --}}
        <div class="lg:col-span-9 space-y-8">
            
            {{-- Sticky Domain Quick Jump Menu --}}
            <div class="sticky top-0 z-30 bg-slate-50/90 backdrop-blur-md border border-slate-200/60 rounded-2xl p-4 flex flex-wrap gap-2 shadow-sm">
                <template x-for="dom in domains" :key="dom">
                    <a :href="'#domain-' + slugify(dom)" class="px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest bg-white border border-slate-200 hover:border-indigo-500 hover:text-indigo-600 transition-all shadow-sm flex items-center">
                        <span x-text="dom"></span>
                        <span class="ml-1.5 px-1.5 py-0.5 rounded bg-slate-100 text-slate-500 text-[8px]" x-text="getReqsByDomain(dom).length"></span>
                    </a>
                </template>
            </div>

            {{-- Controls Grouped by Domain --}}
            <template x-for="dom in domains" :key="dom">
                <div :id="'domain-' + slugify(dom)" class="scroll-mt-28">
                    <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-6 border-b border-slate-200 pb-3 flex items-center gap-2">
                        <i class="fas fa-layer-group text-indigo-500"></i>
                        <span x-text="dom"></span>
                    </h3>
                    
                    <template x-for="req in getReqsByDomain(dom)" :key="req.id">
                        <div :id="'req-card-' + req.id" class="glass-premium rounded-[28px] p-8 border border-white shadow-lg relative overflow-hidden bg-white/60 mb-6 hover:shadow-xl transition-all">
                            
                            {{-- Card Header --}}
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4 pb-4 border-b border-slate-100">
                                <div class="flex items-center space-x-3 flex-wrap gap-y-2">
                                    <span class="text-2xl font-black text-slate-900 tracking-tight" x-text="req.req_num"></span>
                                    <span x-show="req.name" class="text-2xl font-bold text-slate-700 tracking-tight" x-text="req.name"></span>
                                    <template x-if="req.is_applicable == 0">
                                        <span class="text-[8px] font-black text-rose-500 bg-rose-50 px-1.5 py-0.5 rounded uppercase">N/A</span>
                                    </template>
                                </div>
                                <div class="flex items-center space-x-2">
                                    @if($isPci)
                                        @can('is-auditor')
                                        <div class="flex items-center space-x-1 px-1.5 py-0.5 bg-white rounded-full border border-slate-100 shadow-sm mr-2">
                                            <button @click="toggleScope(req.id, true)" :class="req.is_applicable == 1 ? 'text-emerald-700 bg-emerald-50' : 'text-slate-300'" class="text-[8px] font-black px-3 py-1 rounded-full transition-all">IN-SCOPE</button>
                                            <button @click="toggleScope(req.id, false)" :class="req.is_applicable == 0 ? 'text-rose-700 bg-rose-50' : 'text-slate-300'" class="text-[8px] font-black px-3 py-1 rounded-full transition-all">N/A</button>
                                        </div>
                                        @endcan
                                    @endif
                                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest mr-2">
                                        <i class="fas fa-database mr-1"></i> <span x-text="(evidence[req.id] || []).length"></span> provisioned
                                    </span>
                                    <button @click="activeReqId = req.id; showUploadModal = true" class="px-4 py-1.5 rounded-lg text-[9px] font-black uppercase tracking-widest text-indigo-600 border border-indigo-100 bg-indigo-50/50 hover:bg-indigo-100 transition-all flex items-center">
                                        <i class="fas fa-plus mr-1"></i> Provision
                                    </button>
                                </div>
                            </div>
                            
                            {{-- Card Description --}}
                            <p class="text-slate-600 font-semibold leading-relaxed mb-6" x-text="req.description"></p>
                            
                            {{-- Evidence Table --}}
                            <div class="rounded-2xl border border-slate-100 bg-white shadow-inner overflow-hidden">
                                <table class="min-w-full divide-y divide-slate-100">
                                    <thead class="bg-slate-50/50 text-[9px] font-black text-slate-400 uppercase tracking-widest">
                                        <tr>
                                            <th class="px-5 py-3 text-left">Identity / Asset</th>
                                            <th class="px-5 py-3 text-left">AI Integrity Ingest (Synthesis)</th>
                                            <th class="px-5 py-3 text-left">HITL Communication</th>
                                            <th class="px-5 py-3 text-left">Control State</th>
                                            <th class="px-5 py-3 text-right">Audit Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-50">
                                        <template x-for="file in (evidence[req.id] || [])" :key="file.id">
                                            <tr class="hover:bg-slate-50/40 transition-colors group">
                                                {{-- Column 1: Asset Info --}}
                                                <td class="px-5 py-4 align-top">
                                                    <div class="flex items-center">
                                                        <div class="w-8 h-8 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-400 mr-3 shadow-sm flex-shrink-0">
                                                            <i class="fas fa-file-shield text-sm"></i>
                                                        </div>
                                                        <div class="min-w-0">
                                                            <div class="text-[12px] font-bold text-slate-800 truncate" x-text="truncateFilename(file.original_filename)"></div>
                                                            <div class="text-[8px] font-black text-slate-400 uppercase mt-0.5">
                                                                <span x-text="new Date(file.created_at).toLocaleDateString()"></span>
                                                                <span class="mx-1">|</span>
                                                                <span x-text="file.mime_type || 'Asset'"></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>

                                                {{-- Column 2: AI Analysis (Synthesis) --}}
                                                <td class="px-5 py-4 align-top max-w-xs">
                                                    <div class="space-y-2">
                                                        <div class="flex items-center gap-1.5">
                                                            <span class="text-[8px] font-black uppercase tracking-widest" :class="file.scan_status === 'clean' ? 'text-emerald-600' : 'text-amber-600'">
                                                                <i class="fas" :class="file.scan_status === 'clean' ? 'fa-shield-check' : 'fa-radar'"></i>
                                                                <span x-text="file.scan_status || 'Scanning...'"></span>
                                                            </span>
                                                            <span x-show="file.ai_analysis_status === 'awaiting_review'" class="text-[8px] font-black text-indigo-600 uppercase tracking-widest bg-indigo-50 px-1.5 py-0.5 rounded">AI READY</span>
                                                        </div>
                                                        
                                                        <div x-show="file.ai_observations" class="relative group/obs">
                                                            <p class="text-[10px] font-semibold text-slate-600 italic line-clamp-2" x-text="file.ai_observations"></p>
                                                            <div class="hidden group-hover/obs:block absolute z-20 top-full left-0 mt-2 p-4 bg-slate-900 text-white rounded-xl text-[10px] w-64 shadow-2xl">
                                                                <div class="font-black mb-2 text-indigo-300 uppercase tracking-widest border-b border-white/10 pb-1">Full Synthesis</div>
                                                                <div x-text="file.ai_observations"></div>
                                                                <div class="mt-3 font-black text-emerald-400 uppercase tracking-widest border-b border-white/10 pb-1">Recommendations</div>
                                                                <div x-text="file.ai_recommendations"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>

                                                {{-- Column 3: HITL Communication --}}
                                                <td class="px-5 py-4 align-top">
                                                    <div class="space-y-1.5">
                                                        <template x-if="file.feedbacks && file.feedbacks.length > 0">
                                                            <div class="space-y-1">
                                                                <p class="text-[9px] font-bold text-indigo-600 bg-indigo-50/50 p-2 rounded-lg border border-indigo-100/50 leading-relaxed max-w-[180px] truncate" x-text="file.feedbacks[file.feedbacks.length-1].message"></p>
                                                                <button @click="openFeedback(file)" class="text-[8px] font-black text-slate-400 hover:text-indigo-600 uppercase tracking-widest flex items-center">
                                                                    <i class="fas fa-comments-alt mr-1"></i> <span x-text="file.feedbacks.length"></span> Messages...
                                                                </button>
                                                            </div>
                                                        </template>
                                                        <template x-if="!file.feedbacks || file.feedbacks.length === 0">
                                                            <button @click="openFeedback(file)" class="text-[8px] font-black text-slate-300 hover:text-indigo-600 uppercase tracking-widest border border-dashed border-slate-200 px-2 py-1 rounded-lg">
                                                                + Add Feedback
                                                            </button>
                                                        </template>
                                                    </div>
                                                </td>

                                                {{-- Column 4: Control State --}}
                                                <td class="px-5 py-4 align-top">
                                                    <div class="flex flex-col">
                                                        <span :class="getBadgeClass(file.hitl_status)" class="inline-flex items-center px-2 py-0.5 rounded-full text-[8px] font-black tracking-widest shadow-sm max-w-max">
                                                            <i class="fas fa-dot-circle mr-1 opacity-50"></i>
                                                            <span x-text="file.hitl_status || 'Waiting'"></span>
                                                        </span>
                                                        <div class="w-24 h-1 bg-slate-100 rounded-full mt-2 overflow-hidden">
                                                            <div class="h-full bg-indigo-600 transition-all duration-1000" :style="{ width: getProgress(file) + '%' }"></div>
                                                        </div>
                                                    </div>
                                                </td>

                                                {{-- Column 5: Audit Action --}}
                                                <td class="px-5 py-4 align-top text-right">
                                                    @can('is-auditor')
                                                        <div class="flex flex-col items-end gap-1.5">
                                                            <div class="flex items-center gap-1">
                                                                <button 
                                                                    @click="directAudit(file, 'accept')" 
                                                                    class="p-1.5 rounded-lg text-emerald-600 bg-emerald-50 hover:bg-emerald-600 hover:text-white transition-all shadow-sm"
                                                                    title="Accept Evidence"
                                                                >
                                                                    <i class="fas fa-check-circle text-xs"></i>
                                                                </button>
                                                                <button 
                                                                    @click="openReview(file)" 
                                                                    class="p-1.5 rounded-lg text-rose-600 bg-rose-50 hover:bg-rose-600 hover:text-white transition-all shadow-sm"
                                                                    title="Return for Correction"
                                                                >
                                                                    <i class="fas fa-undo-alt text-xs"></i>
                                                                </button>
                                                            </div>
                                                            <button @click="openReview(file)" class="text-[8px] font-black text-indigo-600 uppercase tracking-widest hover:underline">
                                                                Deep Audit View
                                                            </button>
                                                        </div>
                                                    @endcan
                                                    @cannot('is-auditor')
                                                        <button @click="openReview(file)" class="text-[9px] font-black text-indigo-600 uppercase tracking-widest border border-indigo-100 px-3 py-1.5 rounded-lg bg-indigo-50/30 hover:bg-white transition-all">
                                                            Review Detail
                                                        </button>
                                                    @endcannot
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                                <template x-if="(evidence[req.id] || []).length === 0">
                                    <div class="p-8 text-center text-slate-300 bg-slate-50/20">
                                        <i class="fas fa-inbox text-xl mb-2"></i>
                                        <p class="text-[9px] font-black uppercase tracking-widest">Vault Empty for this requirement</p>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </template>
        </div>

        {{-- Right Column: Pulse Feed --}}
        <div class="lg:col-span-3 sticky top-28">
            <div class="glass-premium rounded-[32px] p-6 border border-white shadow-xl bg-white/40">
                <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-6 flex items-center">
                    <i class="fas fa-bolt mr-2 text-amber-500"></i> Pulse Feed
                </h3>
                <div class="space-y-6">
                    <template x-for="act in activities" :key="act.time + act.user">
                        <div class="flex gap-4 group">
                            <div class="w-8 h-8 rounded-xl bg-white border border-slate-100 flex items-center justify-center shadow-sm group-hover:scale-110 transition-all flex-shrink-0">
                                <i :class="'fas ' + act.icon" class="text-[10px] text-slate-400"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-[11px] font-bold text-slate-800 leading-tight">
                                    <span class="text-indigo-700" x-text="act.user"></span>
                                    <span x-show="act.type==='upload'"> provisioned asset for <span x-text="act.req"></span></span>
                                    <span x-show="act.type==='comment'"> updated review</span>
                                </p>
                                <span class="text-[9px] font-bold text-slate-400 uppercase mt-1 block" x-text="act.time"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    {{-- Provision Modal --}}
    <div x-show="showUploadModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-50 flex items-center justify-center p-4" x-cloak x-transition>
        <div class="bg-white rounded-[40px] shadow-2xl w-full max-w-xl border border-white" @click.away="showUploadModal = false">
            <div class="px-12 py-10 border-b border-slate-100 flex justify-between items-center">
                <h2 class="text-3xl font-black text-slate-900 tracking-tight">Provision Evidence</h2>
                <button @click="showUploadModal = false" class="text-slate-300 hover:text-indigo-600"><i class="fas fa-times text-xl"></i></button>
            </div>
            <form action="{{ route('evidence.upload', $project) }}" method="POST" enctype="multipart/form-data" class="p-12 text-center">
                @csrf
                <input type="hidden" name="requirement_id" :value="activeReqId">
                <div class="p-20 border-2 border-dashed border-slate-200 rounded-[48px] bg-slate-50/30 group hover:border-indigo-600 transition-all relative">
                    <i class="fas fa-file-arrow-up text-6xl text-slate-200 mb-8 group-hover:text-indigo-600 transition-all"></i>
                    <input type="file" name="file" required class="absolute inset-0 opacity-0 cursor-pointer">
                    <p class="text-[11px] font-black text-slate-400 uppercase tracking-widest">Select compliance record</p>
                </div>
                <div class="mt-12 flex justify-end gap-5">
                    <button type="button" @click="showUploadModal = false" class="text-[11px] font-black uppercase text-slate-400 tracking-widest">Cancel</button>
                    <button type="submit" class="px-12 py-4 bg-indigo-600 text-white rounded-3xl font-black text-[11px] uppercase tracking-widest shadow-xl shadow-indigo-600/30 hover:bg-indigo-700">Synthesize</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Auditor Review Slide-over --}}
    <div x-show="showReviewPanel" class="fixed inset-0 z-50 overflow-hidden" x-cloak>
        <div class="absolute inset-0 bg-slate-900/30 backdrop-blur-sm transition-opacity" @click="showReviewPanel = false"></div>
        <div class="fixed inset-y-0 right-0 max-w-full flex">
            <div class="w-screen max-w-5xl bg-white rounded-l-[64px] shadow-2xl border-l border-slate-100 overflow-y-auto transform transition-all duration-500"
                 x-transition:enter="translate-x-full" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0">
                <template x-if="reviewFile">
                    <div class="p-24 space-y-20">
                        <div class="flex items-center justify-between border-b border-slate-50 pb-16">
                            <div class="flex items-center gap-10">
                                <div class="w-24 h-24 rounded-[32px] bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 text-4xl shadow-sm">
                                    <i class="fas fa-file-contract"></i>
                                </div>
                                <div>
                                    <h3 class="text-6xl font-black text-slate-900 tracking-tighter" x-text="reviewFile.original_filename"></h3>
                                    <p class="text-[12px] font-black text-indigo-600 uppercase tracking-widest mt-3">Registry ID: #<span x-text="reviewFile.id"></span></p>
                                </div>
                            </div>
                            <button @click="showReviewPanel = false" class="w-20 h-20 rounded-full bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-400 hover:text-rose-500 transition-all shadow-sm"><i class="fas fa-times text-3xl"></i></button>
                        </div>
                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-24">
                            <div class="lg:col-span-12 space-y-12">
                                <div class="grid grid-cols-2 gap-12">
                                    <div class="p-12 bg-indigo-50/50 rounded-[56px] border border-indigo-100 relative overflow-hidden">
                                        <h4 class="text-[11px] font-black text-indigo-700 uppercase tracking-widest mb-8"><i class="fas fa-robot mr-2"></i> AI Perspectives</h4>
                                        <div class="text-sm text-slate-800 leading-relaxed font-semibold italic" x-text="reviewFile.ai_observations || 'Mining document observations...'"></div>
                                    </div>
                                    <div class="p-12 bg-emerald-50/50 rounded-[56px] border border-emerald-100 relative overflow-hidden">
                                        <h4 class="text-[11px] font-black text-emerald-700 uppercase tracking-widest mb-8"><i class="fas fa-lightbulb-on mr-2"></i> Integrity Recommendations</h4>
                                        <div class="text-sm text-slate-800 leading-relaxed font-semibold italic" x-text="reviewFile.ai_recommendations || 'No critical gaps documented.'"></div>
                                    </div>
                                </div>
                                
                                {{-- Auditor Controls --}}
                                <div class="p-16 glass-premium rounded-[64px] border border-slate-100 bg-slate-50/30">
                                    <h4 class="text-[11px] font-black text-slate-400 uppercase tracking-widest mb-12 text-center">Auditor Adjudication</h4>
                                    <div class="flex gap-6 max-w-3xl mx-auto">
                                        <button @click="submitAudit('accept')" class="flex-1 py-8 bg-indigo-600 text-white rounded-3xl font-black text-[15px] uppercase tracking-widest shadow-2xl shadow-indigo-600/40 hover:bg-indigo-700 transition-all flex items-center justify-center">
                                            <i class="fas fa-check-circle mr-3"></i> Accept Integrity Asset
                                        </button>
                                        <button @click="submitAudit('return')" class="flex-1 py-8 bg-white text-rose-600 border-2 border-rose-100 rounded-3xl font-black text-[15px] uppercase tracking-widest hover:bg-rose-50 transition-all flex items-center justify-center">
                                            <i class="fas fa-undo mr-3"></i> Request Revision
                                        </button>
                                    </div>
                                    <div class="mt-20 max-w-3xl mx-auto">
                                         <textarea x-model="auditNotes" class="w-full p-10 bg-white border-2 border-slate-50 rounded-[48px] text-[16px] font-semibold focus:outline-none focus:ring-8 focus:ring-indigo-600/5 min-h-[300px]" placeholder="Explain the rationale for this adjudication..."></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
    
    {{-- Feedback / Communication Modal --}}
    <div x-show="showFeedbackModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-md z-50 flex items-center justify-center p-4" x-cloak x-transition>
        <div class="bg-white rounded-[48px] shadow-2xl w-full max-w-2xl border border-white flex flex-col h-[700px]" @click.away="showFeedbackModal = false">
            <div class="px-10 py-8 border-b border-slate-100 flex justify-between items-center bg-slate-50/50 rounded-t-[48px]">
                <div>
                    <h2 class="text-2xl font-black text-slate-900 tracking-tight">Audit Communication</h2>
                    <p class="text-[10px] font-black text-indigo-600 uppercase tracking-widest mt-1" x-text="reviewFile ? reviewFile.original_filename : ''"></p>
                </div>
                <button @click="showFeedbackModal = false" class="text-slate-300 hover:text-indigo-600 transition-colors"><i class="fas fa-times text-xl"></i></button>
            </div>
            
            <div class="flex-1 overflow-y-auto p-10 space-y-6 custom-scrollbar bg-slate-50/30">
                <template x-if="reviewFile && reviewFile.feedbacks">
                    <template x-for="msg in reviewFile.feedbacks" :key="msg.id">
                        <div :class="msg.user_id == {{ auth()->id() }} ? 'items-end' : 'items-start'" class="flex flex-col space-y-2">
                             <div :class="msg.user_id == {{ auth()->id() }} ? 'bg-indigo-600 text-white rounded-l-2xl rounded-tr-2xl' : 'bg-white text-slate-800 rounded-r-2xl rounded-tl-2xl border border-slate-100'" class="px-6 py-4 max-w-[85%] shadow-sm">
                                <p class="text-sm font-semibold leading-relaxed" x-text="msg.message"></p>
                             </div>
                             <span class="text-[9px] font-black text-slate-400 uppercase pr-2" x-text="msg.user ? msg.user.username : 'Unknown'"></span>
                        </div>
                    </template>
                </template>
                <div x-show="!reviewFile || !reviewFile.feedbacks || reviewFile.feedbacks.length === 0" class="h-full flex flex-center items-center justify-center text-slate-300 italic text-sm">
                    Initiate integrity feedback loop...
                </div>
            </div>

            <div class="p-8 bg-white border-t border-slate-100 rounded-b-[48px]">
                <div class="relative">
                    <textarea 
                        x-model="auditNotes" 
                        class="w-full p-6 bg-slate-50 border-2 border-slate-100 rounded-3xl text-sm font-semibold focus:outline-none focus:ring-8 focus:ring-indigo-600/5 min-h-[100px] resize-none" 
                        placeholder="Type your feedback message here..."
                    ></textarea>
                    <div class="mt-4 flex justify-between items-center">
                        <div class="flex gap-2">
                            <button @click="submitAudit('reply')" class="px-8 py-3 bg-indigo-600 text-white rounded-2xl font-black text-[10px] uppercase tracking-widest shadow-lg shadow-indigo-600/20 hover:bg-indigo-700 transition-all">
                                Send Message
                            </button>
                            <template x-if="reviewFile && reviewFile.hitl_status !== 'accepted'">
                                <button @click="submitAudit('return')" class="px-6 py-3 bg-white text-rose-600 border border-rose-100 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-rose-50 transition-all">
                                    Flag for Revision
                                </button>
                            </template>
                        </div>
                        <template x-if="reviewFile && reviewFile.hitl_status !== 'accepted'">
                            <button @click="submitAudit('accept')" class="px-6 py-3 bg-emerald-500 text-white rounded-2xl font-black text-[10px] uppercase tracking-widest shadow-lg shadow-emerald-500/20 hover:bg-emerald-600 transition-all">
                                <i class="fas fa-check-circle mr-1"></i> Accept Asset
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- JSON DATA STORE --}}
<script id="integrity-hub-data" type="application/json">
    {
        "requirements": {!! json_encode($requirements) !!},
        "evidence": {!! $evidenceByRequirement->toJson() !!},
        "domains": {!! json_encode($domains) !!}
    }
</script>

<script>
    function premiumEvidenceWorkspace() {
        const _STORE = JSON.parse(document.getElementById('integrity-hub-data').textContent);
        
        return {
            projectId: {{ $project->id }},
            requirements: _STORE.requirements,
            evidence: _STORE.evidence,
            domains: _STORE.domains,
            activeReqId: null,
            activities: [],
            showUploadModal: false,
            showReviewPanel: false,
            showFeedbackModal: false,
            reviewFile: null,
            auditNotes: '',

            onBoot() {
                // Determine first active requirement
                if (this.requirements && this.requirements.length > 0) {
                    this.activeReqId = this.requirements[0].id;
                }
                this.fetchPulse();
                setInterval(() => this.fetchPulse(), 12000);
                setInterval(() => this.pollLoop(), 5000);
            },

            async fetchPulse() {
                try {
                    const r = await fetch(`/evidence/${this.projectId}/activities`);
                    if (r.ok) this.activities = await r.json();
                } catch (e) {}
            },

            getAcceptedCount(id) { 
                return (this.evidence[id] || []).filter(f => f.hitl_status === 'accepted').length; 
            },

            getBadgeClass(s) {
                if (s === 'accepted') return 'bg-emerald-100 text-emerald-700 border border-emerald-200';
                if (s === 'action_required') return 'bg-rose-100 text-rose-700 border border-rose-200';
                return 'bg-amber-100 text-amber-700 border border-amber-200';
            },

            getProgress(f) {
                if (f.hitl_status === 'accepted') return 100;
                if (f.ai_analysis_status === 'awaiting_review') return 80;
                if (f.ai_analysis_status === 'processing') return 60;
                if (f.scan_status === 'clean') return 40;
                return 20;
            },

            async toggleScope(id, state) {
                try {
                    const r = await fetch(`/evidence/${this.projectId}/${id}/toggle-scope`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
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
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
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
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: JSON.stringify({ action, message: this.auditNotes || 'Automated Synthesis Adjudication' })
                    });
                    if (r.ok) {
                        if (action === 'reply' && this.showFeedbackModal) {
                            // Refresh just the file data instead of reload if possible, 
                            // but reload is safer for now.
                            window.location.reload();
                        } else {
                            window.location.reload();
                        }
                    }
                } catch (e) {}
            },

            async pollLoop() {
                // Poll any file on the page that is actively scanning or processing
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
                return this.requirements.filter(r => r.domain === dom);
            },

            truncateFilename(n) { if (!n) return ''; return n.length > 25 ? n.substring(0, 12) + '...' + n.slice(-10) : n; }
        }
    }
</script>
@endsection
