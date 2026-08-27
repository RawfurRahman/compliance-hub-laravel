{{-- resources/views/evidence/hub.blade.php --}}
@extends('layouts.app')

@push('styles')
    <link href="{{ asset('fonts/outfit.css') }}" rel="stylesheet">
    <style nonce="{{ $cspNonce }}">
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
<div class="p-2 font-outfit max-w-full" x-data="evidenceHub" x-init="initData()">
    
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
                <option value="" disabled {{ !$project ? 'selected' : '' }}>Select a Project...</option>
                @foreach($projects as $p)
                    <option value="{{ $p->id }}" {{ $project && $p->id === $project->id ? 'selected' : '' }}>
                        {{ $p->name }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    @if(!$project)
        <div class="bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden p-12 text-center">
            <div class="w-16 h-16 bg-sky-50 border border-sky-100 rounded-2xl flex items-center justify-center text-sky-500 mx-auto mb-6 shadow-sm">
                <i class="fas fa-project-diagram text-2xl"></i>
            </div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight mb-2">No Project Selected</h2>
            <p class="text-slate-500 text-sm max-w-md mx-auto mb-8 font-medium">Select a compliance project from the options below or the dropdown at the top to track and validate evidence.</p>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 max-w-4xl mx-auto text-left">
                @foreach($projects as $p)
                    <button @click="switchProject({{ $p->id }})" 
                            class="p-6 rounded-2xl border border-slate-150 hover:border-sky-400 hover:shadow-lg transition-all text-left bg-white group flex flex-col justify-between shadow-sm min-h-[160px]">
                        <div class="w-full">
                            <div class="flex items-center justify-between mb-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider {{ $p->module_type === 'pci_dss' ? 'bg-indigo-50 text-indigo-700' : 'bg-emerald-50 text-emerald-700' }}">
                                    {{ $p->module_type === 'pci_dss' ? 'PCI DSS' : strtoupper(str_replace('_', ' ', $p->module_type)) }}
                                </span>
                                <span class="text-xs font-semibold text-slate-400 group-hover:text-sky-500 transition-colors">
                                    Open Tracker <i class="fas fa-arrow-right ml-1"></i>
                                </span>
                            </div>
                            <h3 class="font-extrabold text-slate-800 text-base mb-1 group-hover:text-sky-600 transition-colors">{{ $p->name }}</h3>
                            <p class="text-xs text-slate-500 line-clamp-2 font-medium">{{ $p->description }}</p>
                        </div>
                    </button>
                @endforeach
            </div>
        </div>
    @else
        {{-- Main Container Card --}}
        <div class="bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden">
            
            {{-- Card Header matching the Mockup --}}
            <div class="px-6 py-5 border-b border-slate-100 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4" style="background: #f8fafc;">
                <div class="flex items-center gap-3">
                    <div class="w-2.5 h-6 rounded-full bg-sky-500"></div>
                    <h2 class="text-sm font-extrabold text-slate-800 uppercase tracking-widest">
                        Evidence Tracker - {{ $frameworkName }} Assessment ({{ $project->name }})
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
                                        <span class="inline-flex items-center w-fit px-2 py-0.5 rounded bg-slate-100 text-slate-800 border border-slate-200 text-[10px] font-bold uppercase tracking-wider" x-text="getRequirementNum(file) + getControlName(file)"></span>
                                        <span class="text-xs font-semibold text-slate-700 leading-normal" x-text="getRequirementDesc(file)"></span>
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
                                     :class="file.scan_status === 'clean' ? 'bg-emerald-50 text-emerald-700 border-emerald-200/60' : (file.scan_status === 'infected' ? 'bg-rose-50 text-rose-700 border-rose-200/60' : (file.scan_status === 'failed' ? 'bg-slate-100 text-slate-600 border-slate-200/60' : 'bg-amber-50 text-amber-700 border-amber-200/60'))">
                                    <span class="w-1.5 h-1.5 rounded-full" 
                                          :class="file.scan_status === 'clean' ? 'bg-emerald-500' : (file.scan_status === 'infected' ? 'bg-rose-500' : (file.scan_status === 'failed' ? 'bg-slate-400' : 'bg-amber-400'))"></span>
                                    <span x-text="file.scan_status === 'clean' ? 'Clean' : (file.scan_status === 'infected' ? 'Infected' : (file.scan_status === 'failed' ? 'Scan Failed' : 'Scanning'))"></span>
                                </div>
                            </td>
                            
                            {{-- Col 6: AI Assessment --}}
                            <td class="px-4 py-4">
                                <div class="flex flex-col gap-2 items-start">
                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold border"
                                         :class="getAssessmentBadgeClass(file)">
                                        <span class="w-1.5 h-1.5 rounded-full" :class="getAssessmentDot(file)"></span>
                                        <span x-text="getAssessmentLabel(file)"></span>
                                    </div>

                                    <template x-if="file.ai_gaps && file.ai_gaps.length > 0">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200/60">
                                            <i class="fas fa-triangle-exclamation text-[9px]"></i> Gap Identified
                                        </span>
                                    </template>

                                    <template x-if="file.ai_analysis_status === 'awaiting_review'">
                                        <button @click="approveAiAnalysis(file)" class="text-[10px] font-black uppercase tracking-wider text-emerald-700 hover:text-emerald-900 flex items-center gap-1">
                                            <i class="fas fa-stamp"></i> Approve AI Analysis
                                        </button>
                                    </template>
                                    <template x-if="file.ai_analysis_status === 'approved'">
                                        <span class="text-[10px] font-bold text-emerald-600 flex items-center gap-1">
                                            <i class="fas fa-check"></i> AI Analysis Approved
                                        </span>
                                    </template>

                                    <button @click="openAnalysisHistory(file)" class="text-[10px] font-black uppercase tracking-wider text-slate-500 hover:text-slate-700 flex items-center gap-1">
                                        <i class="fas fa-clock-rotate-left"></i> History
                                    </button>

                                    <template x-if="file.scan_status !== 'infected'">
                                        <button @click="openGapReviewModal(file)" class="w-full mt-1 py-1.5 px-2 text-[10px] font-black uppercase tracking-wider rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition flex items-center justify-center gap-1 shadow-sm">
                                            <i class="fas fa-clipboard-check"></i> Review & Push to Gap Assessment
                                        </button>
                                    </template>
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
                                            <template x-if="file.ai_recommendations && file.ai_recommendations.toLowerCase() !== 'none' && file.ai_recommendations.trim() !== ''">
                                                <div class="p-3.5 rounded-xl bg-amber-50/30 border border-amber-100/50 hover:border-amber-200/60 transition shadow-sm">
                                                    <span class="inline-flex items-center gap-1.5 text-[10px] font-extrabold text-amber-700 uppercase tracking-widest mb-1.5">
                                                        <i class="fas fa-lightbulb text-[10px]"></i> Actionable Recommendation
                                                    </span>
                                                    <p class="text-xs text-slate-600 leading-relaxed font-medium" x-text="file.ai_recommendations"></p>
                                                </div>
                                            </template>

                                            {{-- Gap Warning Badge --}}
                                            <template x-if="file.ai_gaps && file.ai_gaps.length > 0">
                                                <div class="p-3.5 rounded-xl bg-amber-100/40 border border-amber-200/60 transition shadow-sm">
                                                    <span class="inline-flex items-center gap-1.5 text-[10px] font-extrabold text-amber-800 uppercase tracking-widest mb-1.5">
                                                        <i class="fas fa-exclamation-triangle text-[10px]"></i> AI Gaps Detected
                                                    </span>
                                                    <div class="flex flex-wrap gap-1">
                                                        <template x-for="g in file.ai_gaps" :key="g.gap">
                                                            <span :class="'px-1.5 py-0.5 rounded text-[10px] font-bold ' + (g.severity === 'high' ? 'bg-red-100 text-red-700' : g.severity === 'medium' ? 'bg-amber-100 text-amber-700' : 'bg-yellow-100 text-yellow-700')" x-text="g.severity + ': ' + g.gap"></span>
                                                        </template>
                                                    </div>
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

                                    {{-- Gap Assessment / Observation / Risk workflow --}}
                                    <template x-if="file.assessment_finding">
                                        <div class="flex flex-col gap-1.5 w-full pt-2 border-t border-slate-100">
                                            <template x-if="!file.assessment_finding.observations || file.assessment_finding.observations.length === 0">
                                                <button @click="openObservationModal(file)" class="w-full py-1.5 px-2 text-[10px] font-black uppercase tracking-wider rounded-lg bg-indigo-50 text-indigo-700 border border-indigo-200/60 hover:bg-indigo-100 transition flex items-center justify-center gap-1">
                                                    <i class="fas fa-flag"></i> Raise Observation
                                                </button>
                                            </template>
                                            <template x-for="obs in (file.assessment_finding.observations || [])" :key="obs.id">
                                                <div class="p-2 rounded-lg bg-slate-50 border border-slate-200/60">
                                                    <p class="text-[10px] font-bold text-slate-700 truncate" x-text="obs.title"></p>
                                                    <p class="text-[9px] text-slate-400 uppercase font-bold mb-1" x-text="obs.status"></p>
                                                    <template x-if="!obs.risk_register_id">
                                                        <button @click="createRiskFromObservation(obs, file)" class="w-full py-1 px-2 text-[9px] font-black uppercase tracking-wider rounded-lg bg-rose-50 text-rose-700 border border-rose-200/60 hover:bg-rose-100 transition flex items-center justify-center gap-1">
                                                            <i class="fas fa-triangle-exclamation"></i> Create Risk
                                                        </button>
                                                    </template>
                                                    <template x-if="obs.risk_register_id">
                                                        <span class="text-[9px] font-bold text-emerald-600 flex items-center gap-1"><i class="fas fa-check"></i> Risk Created</span>
                                                    </template>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
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
                    <p class="font-semibold text-slate-700" x-text="getRequirementNum(selectedFile) + getControlName(selectedFile) + ' - ' + getRequirementDesc(selectedFile)"></p>
                </div>
                <div>
                    <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1">AI Audit Observations</h4>
                    <p class="text-slate-600 bg-slate-50 p-3 rounded-xl border border-slate-100 leading-relaxed italic" x-text="selectedFile.ai_observations || 'No observations'"></p>
                </div>
                <div>
                    <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1">AI Actionable Recommendations</h4>
                    <p class="text-slate-600 bg-slate-50 p-3 rounded-xl border border-slate-100 leading-relaxed italic" x-text="selectedFile.ai_recommendations || 'None'"></p>
                </div>
                <div x-show="selectedFile.ai_gaps && selectedFile.ai_gaps.length > 0">
                    <h4 class="text-xs font-black text-amber-700 uppercase tracking-widest mb-1">
                        <i class="fas fa-exclamation-triangle mr-1"></i> AI Gap Analysis
                    </h4>
                    <div class="space-y-1.5">
                        <template x-for="g in selectedFile.ai_gaps" :key="g.gap">
                            <div class="flex items-start gap-2 p-2 rounded-lg bg-amber-50 border border-amber-100">
                                <span :class="'px-1.5 py-0.5 rounded text-[10px] font-bold uppercase shrink-0 ' + (g.severity === 'high' ? 'bg-red-100 text-red-700' : g.severity === 'medium' ? 'bg-amber-100 text-amber-700' : 'bg-yellow-100 text-yellow-700')" x-text="g.severity"></span>
                                <span class="text-xs text-slate-700" x-text="g.gap"></span>
                            </div>
                        </template>
                        <p class="text-[10px] text-slate-400 italic">AI-generated, please verify</p>
                    </div>
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

    {{-- Analysis History Modal --}}
    <div x-show="historyModalOpen"
         class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4"
         @keydown.escape.window="historyModalOpen = false"
         x-cloak
         x-transition>
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden border border-slate-100" @click.away="historyModalOpen = false">
            <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between" style="background: #f8fafc;">
                <div class="flex items-center gap-2">
                    <i class="fas fa-clock-rotate-left text-slate-500"></i>
                    <h3 class="text-sm font-extrabold text-slate-800 uppercase tracking-widest">Analysis History</h3>
                </div>
                <button @click="historyModalOpen = false" class="text-slate-400 hover:text-slate-600 transition">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            <div class="p-6 space-y-3 max-h-[60vh] overflow-y-auto">
                <template x-if="analysisVersions.length === 0">
                    <p class="text-sm text-slate-400 italic">No analysis versions recorded yet.</p>
                </template>
                <template x-for="(v, idx) in analysisVersions" :key="v.id">
                    <div class="p-3.5 rounded-xl border border-slate-200/60 bg-slate-50/50">
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-[11px] font-black uppercase tracking-wider text-slate-700" x-text="'Version ' + (idx + 1) + ' — ' + (v.trigger_type === 'ai_analysis' ? 'AI Analysis' : 'Re-analysis Requested')"></span>
                            <span class="text-[10px] text-slate-400 font-semibold" x-text="formatDate(v.created_at)"></span>
                        </div>
                        <template x-if="v.triggered_by">
                            <p class="text-[10px] text-slate-500 mb-1">Requested by <span class="font-bold" x-text="v.triggered_by.username"></span></p>
                        </template>
                        <template x-if="v.reason">
                            <p class="text-xs text-slate-600 italic mb-1" x-text="'“' + v.reason + '”'"></p>
                        </template>
                        <p class="text-xs text-slate-700 leading-relaxed" x-text="v.ai_observations || 'No observations recorded.'"></p>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- Raise Observation Modal --}}
    <div x-show="observationModalOpen"
         class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4"
         @keydown.escape.window="observationModalOpen = false"
         x-cloak
         x-transition>
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden border border-slate-100" @click.away="observationModalOpen = false">
            <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between" style="background: #f8fafc;">
                <div class="flex items-center gap-2">
                    <i class="fas fa-flag text-indigo-500"></i>
                    <h3 class="text-sm font-extrabold text-slate-800 uppercase tracking-widest">Raise Observation</h3>
                </div>
                <button @click="observationModalOpen = false" class="text-slate-400 hover:text-slate-600 transition">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="text-xs font-bold text-slate-700 uppercase tracking-wider block mb-2">Title <span class="text-rose-500">*</span></label>
                    <input type="text" x-model="observationTitle"
                           class="w-full text-sm p-3 border-2 rounded-xl focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition border-slate-200"
                           placeholder="Short summary of the observation">
                    <p x-show="observationTitleError" class="text-xs text-rose-500 mt-1 font-semibold">A title is required.</p>
                </div>
                <div>
                    <label class="text-xs font-bold text-slate-700 uppercase tracking-wider block mb-2">Target Date</label>
                    <input type="date" x-model="observationTargetDate"
                           class="w-full text-sm p-3 border-2 rounded-xl focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition border-slate-200">
                </div>
            </div>
            <div class="px-6 py-4 border-t border-slate-100 flex justify-end gap-3 bg-slate-50">
                <button @click="observationModalOpen = false" class="px-4 py-2 text-xs font-bold uppercase tracking-wider text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition">
                    Cancel
                </button>
                <button @click="submitObservation()" class="px-5 py-2 text-xs font-bold uppercase tracking-wider text-white rounded-xl transition shadow-sm bg-indigo-600 hover:bg-indigo-700">
                    <i class="fas fa-flag mr-1"></i> Raise Observation
                </button>
            </div>
        </div>
    </div>

    {{-- Gap Assessment Review Modal --}}
    <div class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-start justify-center p-4 pt-12 overflow-y-auto"
         x-show="gapReviewModalOpen"
         x-transition.opacity
         @keydown.escape.window="gapReviewModalOpen = false"
         x-cloak>
        <div class="bg-white rounded-2xl w-full max-w-3xl overflow-hidden shadow-2xl border border-slate-100 my-8" @click.away="gapReviewModalOpen = false">
            {{-- Modal Header --}}
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-white">
                <div class="flex items-center gap-3 min-w-0">
                    <span class="px-2.5 py-1 bg-indigo-50 text-indigo-700 rounded-lg text-xs font-black uppercase tracking-wider shrink-0"
                          x-text="gapReviewFile ? (getRequirementNum(gapReviewFile) || 'N/A') : 'N/A'"></span>
                    <h3 class="text-lg font-bold text-slate-800 truncate" x-text="'Gap Assessment Review — ' + (gapReviewFile ? gapReviewFile.original_filename : '')"></h3>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <button @click="submitGapReview()" :disabled="gapReviewSubmitting"
                            class="px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest bg-indigo-600 text-white hover:bg-indigo-700 transition-all shadow-lg disabled:opacity-50">
                        <i class="fas fa-paper-plane mr-1"></i>
                        <span x-text="gapReviewSubmitting ? 'Sending...' : 'Push to Gap Assessment'"></span>
                    </button>
                    <button @click="gapReviewModalOpen = false" class="px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest text-slate-500 hover:bg-slate-100 transition-all">
                        <i class="fas fa-times mr-1"></i> Close
                    </button>
                </div>
            </div>

            {{-- Modal Body --}}
            <div class="px-6 py-5 space-y-5 max-h-[70vh] overflow-y-auto">
                <p x-show="gapReviewError" class="text-xs text-rose-600 font-semibold bg-rose-50 border border-rose-200/60 rounded-xl p-3" x-text="gapReviewError"></p>

                {{-- Requirement --}}
                <div>
                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1.5 block">Requirement</label>
                    <p class="text-sm text-slate-700 leading-relaxed p-3 bg-slate-50 rounded-xl border border-slate-100"
                       x-text="gapReviewFile ? (getRequirementDesc(gapReviewFile) || 'No requirement description is on file for this control — the AI analysis could not compare the evidence against specific requirement text.') : 'N/A'"></p>
                </div>

                {{-- Status & Risk Row --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1.5 block">Status</label>
                        <select x-model="gapReviewForm.workflow_status"
                                class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-700 bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                            <option value="Open">Open</option>
                            <option value="In Progress">In Progress</option>
                            <option value="Closed">Closed</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1.5 block">Risk Rating</label>
                        <select x-model="gapReviewForm.risk_rating"
                                class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-700 bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                            <option value="None">None</option>
                            <option value="Low">Low</option>
                            <option value="Medium">Medium</option>
                            <option value="High">High</option>
                        </select>
                    </div>
                </div>

                {{-- Compliance Status --}}
                <div class="flex items-center gap-3">
                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Compliance Status</label>
                    <button @click="gapReviewForm.is_compliant = !gapReviewForm.is_compliant; gapReviewForm.workflow_status = gapReviewForm.is_compliant ? 'Closed' : 'Open'"
                            class="relative w-12 h-6 rounded-full transition-colors duration-200"
                            :class="gapReviewForm.is_compliant ? 'bg-emerald-500' : 'bg-slate-300'">
                        <span class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform duration-200"
                              :class="gapReviewForm.is_compliant ? 'translate-x-6' : ''"></span>
                    </button>
                    <span class="text-xs font-bold uppercase tracking-wider"
                          :class="gapReviewForm.is_compliant ? 'text-emerald-600' : 'text-rose-600'"
                          x-text="gapReviewForm.is_compliant ? 'Compliant' : 'Non-Compliant'"></span>
                    <span class="text-[10px] text-slate-400">(Status auto-set: Compliant → Closed, Non-Compliant → Open)</span>
                </div>

                {{-- Observation --}}
                <div>
                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1.5 block">Observation</label>
                    <textarea x-model="gapReviewForm.observation" rows="3"
                              class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm text-slate-700 leading-relaxed focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all resize-none"
                              placeholder="What the evidence shows..."></textarea>
                </div>

                {{-- Recommendation --}}
                <div>
                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1.5 block">Recommendation</label>
                    <textarea x-model="gapReviewForm.recommended_action" rows="3"
                              class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm text-slate-700 leading-relaxed focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all resize-none"
                              placeholder="Recommend remediation actions..."></textarea>
                </div>

                {{-- Gap Category --}}
                <div>
                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1.5 block">Gap Category</label>
                    <select x-model="gapReviewForm.gap_category"
                            class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-700 bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                        <option value="">Select gap category</option>
                        <option value="Policy">Policy</option>
                        <option value="Technical">Technical</option>
                        <option value="Process">Process</option>
                        <option value="Organizational">Organizational</option>
                        <option value="Physical">Physical</option>
                    </select>
                </div>

                {{-- Evidence Files --}}
                <div>
                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1.5 block">Evidence Files</label>
                    <div class="flex items-center gap-2 p-2.5 bg-slate-50 rounded-lg border border-slate-100 text-sm text-slate-700">
                        <i class="fas fa-file text-slate-400"></i>
                        <span x-text="gapReviewFile ? gapReviewFile.original_filename : ''"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce }}">
document.addEventListener('alpine:init', () => {
    Alpine.data('evidenceHub', () => {
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
        // Analysis history modal state
        historyModalOpen: false,
        analysisVersions: [],
        // Observation modal state
        observationModalOpen: false,
        observationFile: null,
        observationTitle: '',
        observationTargetDate: '',
        observationTitleError: false,
        // Gap Assessment review modal state
        gapReviewModalOpen: false,
        gapReviewFile: null,
        gapReviewForm: {},
        gapReviewSubmitting: false,
        gapReviewError: '',

        initData() {
            // Backfill ai_gaps for any row where getStatus() hasn't polled yet.
            this.files.forEach(f => { if (!f.ai_gaps) f.ai_gaps = f.ai_gaps || []; });
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
                        f.ai_gaps = data.gaps;
                        f.analysis_report_data = data.analysis_report_data;
                    }
                } catch (e) {}
            }
        },

        switchProject(projectId) {
            window.location.href = "{{ url('evidence-hub') }}/" + projectId;
        },

        getRequirementNum(file) {
            if (!file) return 'General';
            if (file.requirement) {
                return file.requirement.req_num;
            }
            const fc = file.framework_control || file.frameworkControl;
            if (fc) {
                return fc.control_id;
            }
            return 'General';
        },

        getRequirementDesc(file) {
            if (!file) return 'General Evidence File';
            if (file.requirement) {
                return file.requirement.description || file.requirement.req_description;
            }
            const fc = file.framework_control || file.frameworkControl;
            if (fc) {
                return fc.requirement_description;
            }
            return 'General Evidence File';
        },

        getControlName(file) {
            if (!file) return '';
            if (file.requirement) return '';
            const fc = file.framework_control || file.frameworkControl;
            if (fc && fc.control_name) {
                return ' - ' + fc.control_name;
            }
            return '';
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
        },

        async approveAiAnalysis(file) {
            try {
                const res = await fetch(`/evidence/${file.id}/ai/approve`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }
                });
                const data = await res.json();
                if (data.status === 'success') {
                    file.ai_analysis_status = 'approved';
                    file.assessment_finding = data.assessment_finding || file.assessment_finding;
                } else {
                    alert(data.message || 'Failed to approve AI analysis.');
                }
            } catch (err) {
                alert('Network error: Failed to approve AI analysis.');
            }
        },

        async openAnalysisHistory(file) {
            this.analysisVersions = [];
            this.historyModalOpen = true;
            try {
                const res = await fetch(`/evidence/${file.id}/analysis-versions`);
                if (res.ok) {
                    this.analysisVersions = await res.json();
                }
            } catch (err) {}
        },

        openObservationModal(file) {
            this.observationFile = file;
            this.observationTitle = '';
            this.observationTargetDate = '';
            this.observationTitleError = false;
            this.observationModalOpen = true;
        },

        async submitObservation() {
            if (!this.observationTitle.trim()) {
                this.observationTitleError = true;
                return;
            }
            this.observationTitleError = false;
            const file = this.observationFile;
            const findingId = file.assessment_finding.id;

            try {
                const res = await fetch(`/gap-assessment/findings/${findingId}/observations`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        title: this.observationTitle,
                        target_date: this.observationTargetDate || null,
                    })
                });
                const data = await res.json();
                if (data.success) {
                    if (!file.assessment_finding.observations) file.assessment_finding.observations = [];
                    file.assessment_finding.observations.push(data.observation);
                    this.observationModalOpen = false;
                } else {
                    alert(data.message || 'Failed to raise observation.');
                }
            } catch (err) {
                alert('Network error: Failed to raise observation.');
            }
        },

        async createRiskFromObservation(observation, file) {
            if (!confirm('Create a risk register entry from this observation?')) return;
            try {
                const res = await fetch(`/observations/${observation.id}/add-to-risk-register`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }
                });
                const data = await res.json();
                if (data.success) {
                    observation.risk_register_id = data.risk.id;
                } else {
                    alert(data.message || 'Failed to create risk.');
                }
            } catch (err) {
                alert('Network error: Failed to create risk.');
            }
        },

        openGapReviewModal(file) {
            this.gapReviewFile = file;
            this.gapReviewError = '';
            const draft = file.analysis_report_data || {};
            const isCompliant = draft.is_compliant ?? false;

            // Prefer the full numbered gaps list over a single free-text gap_description --
            // it's the authoritative structured source and avoids the two disagreeing.
            const gapsNumbered = (file.ai_gaps && file.ai_gaps.length)
                ? file.ai_gaps.map((g, i) => `${i + 1}. ${g.gap}`).join('\n')
                : '';

            // draft.status (when present) is the AI's three-class compliance verdict
            // (compliant/partial/non_compliant) -- NOT the Open/In Progress/Closed
            // workflow value this form's Status field holds. Translate it; never assign
            // it directly, or the <select> silently ends up on no matching option.
            const workflowStatus = { compliant: 'Closed', partial: 'In Progress', non_compliant: 'Open' }[draft.status]
                || (isCompliant ? 'Closed' : 'Open');

            this.gapReviewForm = {
                // workflow_status isn't an AI judgment -- it's derived from the AI's
                // compliance verdict (draft.status, a DIFFERENT field consumed by the
                // evaluation harness) above, unless the auditor already set it explicitly
                // on a prior submission.
                workflow_status: ['Open', 'In Progress', 'Closed'].includes(draft.workflow_status) ? draft.workflow_status : workflowStatus,
                risk_rating: draft.risk_rating || 'None',
                is_compliant: isCompliant,
                observation: draft.observation || file.ai_observations || '',
                gap_description: gapsNumbered || draft.gap_description || '',
                impact_assessment: draft.impact_assessment || '',
                recommended_action: draft.recommended_action || file.ai_recommendations || '',
                gap_category: draft.gap_category || '',
                // Not shown in this form but still sent through so nothing the AI
                // captured is lost -- the Gap Assessment page can still edit these.
                due_date: draft.due_date || '',
                non_compliant_details: draft.non_compliant_details || '',
                compliant_description: draft.compliant_description || '',
                remediation_plan: draft.remediation_plan || '',
                evidence_provided: draft.evidence_provided || file.original_filename || '',
                test_results: draft.test_results || '',
                meets_standard: draft.meets_standard ?? false,
                auditor_notes: draft.auditor_notes || '',
            };

            this.gapReviewModalOpen = true;
        },

        async submitGapReview() {
            this.gapReviewSubmitting = true;
            this.gapReviewError = '';
            const file = this.gapReviewFile;

            try {
                const res = await fetch(`/evidence/${file.id}/review-and-send-to-gap-assessment`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(this.gapReviewForm)
                });
                const data = await res.json();
                if (data.success) {
                    file.analysis_report_data = { ...(file.analysis_report_data || {}), ...this.gapReviewForm };
                    file.assessment_finding = { ...(data.finding || {}), observations: (file.assessment_finding && file.assessment_finding.observations) || [] };
                    this.gapReviewModalOpen = false;
                } else {
                    this.gapReviewError = data.message || 'Failed to push analysis to gap assessment.';
                }
            } catch (err) {
                this.gapReviewError = 'Network error: Failed to push analysis to gap assessment.';
            } finally {
                this.gapReviewSubmitting = false;
            }
        }
        };
    });
});
</script>
@endpush
