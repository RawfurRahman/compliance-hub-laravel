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
        .glass-card {
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 4px 24px 0 rgba(31, 38, 135, 0.03);
        }
        [x-cloak] { display: none !important; }

        .compliance-gauge {
            position: relative;
            width: 160px;
            height: 80px;
            overflow: hidden;
        }
        .compliance-gauge-fill {
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 160px;
            height: 160px;
            border-radius: 50%;
            border: 10px solid #e2e8f0;
            border-bottom-color: transparent;
            border-left-color: transparent;
            border-right-color: transparent;
            transform-origin: center bottom;
            transition: border-color 0.8s ease;
        }
        .gauge-green { border-top-color: #10b981; }
        .gauge-yellow { border-top-color: #f59e0b; }
        .gauge-red { border-top-color: #ef4444; }

        .control-card {
            transition: all 0.2s ease;
        }
        .control-card:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.06);
        }

        .risk-badge {
            @apply px-2 py-0.5 text-[10px] font-black uppercase tracking-widest rounded-md;
        }
        .risk-High { @apply bg-red-100 text-red-700; }
        .risk-Medium { @apply bg-amber-100 text-amber-700; }
        .risk-Low { @apply bg-emerald-100 text-emerald-700; }
        .risk-None { @apply bg-slate-100 text-slate-500; }

        .status-badge {
            @apply px-2.5 py-1 text-[10px] font-black uppercase tracking-widest rounded-lg;
        }
        .status-Open { @apply bg-amber-50 text-amber-700 border border-amber-200; }
        .status-In\ Progress { @apply bg-blue-50 text-blue-700 border border-blue-200; }
        .status-Closed { @apply bg-emerald-50 text-emerald-700 border border-emerald-200; }

        .modal-overlay {
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
        }

        .evidence-drop-zone {
            border: 2px dashed #cbd5e1;
            transition: all 0.2s ease;
        }
        .evidence-drop-zone:hover,
        .evidence-drop-zone.drag-over {
            border-color: #6366f1;
            background: rgba(99, 102, 241, 0.05);
        }

        .tab-active {
            @apply text-indigo-600 border-indigo-600;
        }
        .tab-inactive {
            @apply text-slate-500 hover:text-slate-700 border-transparent hover:border-slate-300;
        }

        @keyframes fade-in-up {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in-up {
            animation: fade-in-up 0.3s ease-out;
        }
    </style>
@endpush

@section('content')
<div class="min-h-screen" x-data="gapAssessmentWorkspace()" x-cloak>
    {{-- Top Navigation Bar --}}
    <div class="sticky top-0 z-40 glass-premium border-b border-slate-200/60">
        <div class="max-w-7xl mx-auto px-6">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-slate-400">
                        <a href="{{ route('dashboard') }}" class="hover:text-indigo-600 transition-colors">Dashboard</a>
                        <i class="fas fa-chevron-right text-[7px]"></i>
                        <a href="{{ route('projects.index') }}" class="hover:text-indigo-600 transition-colors">Projects</a>
                        <i class="fas fa-chevron-right text-[7px]"></i>
                        <span class="text-indigo-600">{{ $project->name }}</span>
                        <i class="fas fa-chevron-right text-[7px]"></i>
                        <span class="text-slate-600">Gap Assessment</span>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <template x-if="!assessmentExists">
                        <button @click="initializeAssessment()"
                                class="px-5 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest bg-indigo-600 text-white hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-500/20">
                            <i class="fas fa-rocket mr-1.5"></i> Initialize Assessment
                        </button>
                    </template>
                    <template x-if="assessmentExists">
                        <a :href="'{{ route('gap-assessment.report', $project) }}'"
                           class="px-5 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest bg-white text-slate-700 border border-slate-200 hover:bg-slate-50 transition-all shadow-sm">
                            <i class="fas fa-file-pdf mr-1.5"></i> Export Report
                        </a>
                    </template>
                    <a href="{{ route('projects.show', $project) }}"
                       class="px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest text-slate-500 hover:text-slate-700 hover:bg-slate-100 transition-all">
                        <i class="fas fa-arrow-left mr-1"></i> Back
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-6 py-8">
        {{-- Flash Messages --}}
        <template x-if="flashMessage">
            <div class="mb-6 p-4 rounded-xl text-sm font-semibold flex items-center shadow-sm transition-all"
                 :class="flashType === 'success' ? 'bg-emerald-50 border border-emerald-200 text-emerald-800' : 'bg-rose-50 border border-rose-200 text-rose-800'"
                 x-text="flashMessage"
                 x-init="setTimeout(() => flashMessage = null, 5000)">
            </div>
        </template>

        {{-- Framework Selector (if multiple) --}}
        <template x-if="frameworks.length > 1">
            <div class="mb-8">
                <div class="flex flex-wrap gap-2">
                    <template x-for="fw in frameworks" :key="fw.id">
                        <a :href="`{{ url('gap-assessment') }}/${fw.slug}/{{ $project->id }}`"
                           class="px-4 py-2 rounded-xl text-xs font-bold transition-all border"
                           :class="selectedFrameworkSlug === fw.slug
                                ? 'bg-indigo-600 text-white border-indigo-600 shadow-lg'
                                : 'bg-white text-slate-600 border-slate-200 hover:border-indigo-300 hover:text-indigo-600'"
                           x-text="fw.name">
                        </a>
                    </template>
                </div>
            </div>
        </template>

        {{-- Header Stats Row --}}
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-8">
            <div class="glass-premium rounded-2xl p-6 lg:col-span-1 flex flex-col items-center justify-center">
                <div class="compliance-gauge mb-2">
                    <div class="compliance-gauge-fill"
                         :class="overallCompliancePct >= 70 ? 'gauge-green' : overallCompliancePct >= 40 ? 'gauge-yellow' : 'gauge-red'"
                         :style="`transform: translateX(-50%) rotate(${overallCompliancePct / 100 * 180}deg)`">
                    </div>
                </div>
                <span class="text-3xl font-extrabold text-slate-800" x-text="`${overallCompliancePct}%`"></span>
                <span class="text-[10px] font-black uppercase tracking-widest text-slate-400 mt-0.5">Compliance</span>
            </div>

            <div class="glass-premium rounded-2xl p-6 lg:col-span-3">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="text-center">
                        <span class="text-2xl font-extrabold text-slate-800" x-text="overallStats.total"></span>
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mt-0.5">Total Controls</p>
                    </div>
                    <div class="text-center">
                        <span class="text-2xl font-extrabold text-emerald-600" x-text="overallStats.compliant"></span>
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mt-0.5">Compliant</p>
                    </div>
                    <div class="text-center">
                        <span class="text-2xl font-extrabold text-red-500" x-text="overallStats.high"></span>
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mt-0.5">High Risk</p>
                    </div>
                    <div class="text-center">
                        <span class="text-2xl font-extrabold text-slate-800" x-text="`${overallStats.progressScore}%`"></span>
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mt-0.5">Progress</p>
                    </div>
                </div>
                <div class="mt-4 w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                    <div class="h-full rounded-full transition-all duration-700 ease-out"
                         :style="`width: ${overallStats.progressScore}%`"
                         :class="overallStats.progressScore >= 70 ? 'bg-emerald-500' : overallStats.progressScore >= 40 ? 'bg-amber-500' : 'bg-red-500'">
                    </div>
                </div>
            </div>
        </div>

        {{-- Domain Stats Bar --}}
        <div class="glass-premium rounded-2xl p-5 mb-8 overflow-x-auto">
            <div class="flex gap-4 min-w-max">
                <template x-for="(stats, domain) in groupedStats" :key="domain">
                    <button @click="activeDomain = domain"
                            class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all border whitespace-nowrap"
                            :class="activeDomain === domain
                                ? 'bg-indigo-600 text-white border-indigo-600 shadow-md'
                                : 'bg-white text-slate-600 border-slate-200 hover:border-indigo-300'">
                        <span class="text-xs font-bold" x-text="domain"></span>
                        <span class="text-[10px] font-black"
                              :class="activeDomain === domain ? 'text-indigo-200' : 'text-slate-400'"
                              x-text="`${stats.compliantPct}%`"></span>
                        <span class="w-16 h-1.5 rounded-full bg-slate-200 overflow-hidden">
                            <span class="h-full block rounded-full transition-all"
                                  :class="stats.compliancePct >= 70 ? 'bg-emerald-500' : stats.compliancePct >= 40 ? 'bg-amber-500' : 'bg-red-500'"
                                  :style="`width: ${stats.compliancePct}%`"></span>
                        </span>
                    </button>
                </template>
            </div>
        </div>

        {{-- Main Content --}}
        <template x-for="(findings, domain) in groupedFindings" :key="domain">
            <div x-show="activeDomain === domain" class="animate-fade-in-up">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-bold text-slate-800" x-text="domain"></h2>
                    <span class="text-xs font-semibold text-slate-400" x-text="`${findings.length} controls`"></span>
                </div>

                <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                    <template x-for="finding in findings" :key="finding.id">
                        <div class="glass-card rounded-xl p-5 control-card cursor-pointer"
                             @click="openEditor(finding)"
                             @keydown.enter="openEditor(finding)"
                             tabindex="0"
                             role="button"
                             :aria-label="`Edit ${finding.framework_control?.control_id || 'control'}`">
                            {{-- Card Header --}}
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="px-2 py-0.5 bg-indigo-50 text-indigo-700 rounded-md text-[10px] font-black uppercase tracking-wider flex-shrink-0"
                                          x-text="finding.framework_control?.control_id || 'N/A'">
                                    </span>
                                    <span class="text-xs font-semibold text-slate-500 truncate"
                                          x-text="finding.framework_control?.control_name || ''">
                                    </span>
                                </div>
                                <div class="flex items-center gap-1.5 flex-shrink-0">
                                    <span class="risk-badge"
                                          :class="`risk-${finding.risk_rating}`"
                                          x-text="finding.risk_rating">
                                    </span>
                                    <span class="status-badge"
                                          :class="`status-${finding.status.replace(' ', '\\ ')}`"
                                          x-text="finding.status">
                                    </span>
                                </div>
                            </div>

                            {{-- Description --}}
                            <p class="text-sm text-slate-600 leading-relaxed line-clamp-2 mb-3"
                               x-text="finding.framework_control?.requirement_description || 'No description'">
                            </p>

                            {{-- Observation / Gap --}}
                            <template x-if="finding.observation">
                                <div class="mb-3 p-3 bg-slate-50 rounded-lg border border-slate-100">
                                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Observation</p>
                                    <p class="text-xs text-slate-600 leading-relaxed line-clamp-2" x-text="finding.observation"></p>
                                </div>
                            </template>

                            {{-- Footer: Compliance + Evidence --}}
                            <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                                <div class="flex items-center gap-2">
                                    <template x-if="finding.is_compliant">
                                        <span class="flex items-center gap-1 text-[10px] font-black text-emerald-600 uppercase tracking-widest">
                                            <i class="fas fa-check-circle"></i> Compliant
                                        </span>
                                    </template>
                                    <template x-if="!finding.is_compliant">
                                        <span class="flex items-center gap-1 text-[10px] font-black text-amber-600 uppercase tracking-widest">
                                            <i class="fas fa-exclamation-triangle"></i> Non-Compliant
                                        </span>
                                    </template>
                                </div>
                                <div class="flex items-center gap-1 text-[10px] text-slate-400 font-semibold">
                                    <i class="fas fa-paperclip"></i>
                                    <span x-text="`${finding.evidence_count || 0} files`"></span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </template>

        {{-- Empty State --}}
        <template x-if="totalFindings === 0">
            <div class="glass-premium rounded-2xl p-16 text-center">
                <div class="w-20 h-20 mx-auto mb-6 rounded-2xl bg-indigo-50 flex items-center justify-center">
                    <i class="fas fa-clipboard-list text-3xl text-indigo-400"></i>
                </div>
                <h3 class="text-xl font-bold text-slate-700 mb-2">No Assessment Data</h3>
                <p class="text-sm text-slate-400 mb-6 max-w-md mx-auto">This project has no gap assessment findings yet. Initialize the assessment to get started.</p>
                <button @click="initializeAssessment()"
                        class="px-8 py-3 rounded-xl text-xs font-black uppercase tracking-widest bg-indigo-600 text-white hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-500/20">
                    <i class="fas fa-rocket mr-2"></i> Initialize Assessment
                </button>
            </div>
        </template>
    </div>

    {{-- Editor Modal --}}
    <div class="fixed inset-0 z-50 modal-overlay flex items-start justify-center p-4 pt-12 overflow-y-auto"
         x-show="editorOpen"
         x-transition.opacity
         @keydown.escape="closeEditor()"
         @keydown.ctrl.enter="saveFinding()"
         @keydown.meta.enter="saveFinding()"
         style="display: none;">

        <div class="bg-white rounded-2xl w-full max-w-3xl overflow-hidden shadow-2xl border border-slate-100 my-8"
             @click.away="closeEditor()">
            {{-- Modal Header --}}
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-white">
                <div class="flex items-center gap-3">
                    <span class="px-2.5 py-1 bg-indigo-50 text-indigo-700 rounded-lg text-xs font-black uppercase tracking-wider"
                          x-text="editingFinding?.framework_control?.control_id || 'N/A'">
                    </span>
                    <h3 class="text-lg font-bold text-slate-800 truncate max-w-md"
                        x-text="editingFinding?.framework_control?.control_name || 'Edit Finding'">
                    </h3>
                </div>
                <div class="flex items-center gap-2">
                    <button @click="saveFinding()"
                            class="px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest bg-indigo-600 text-white hover:bg-indigo-700 transition-all shadow-lg">
                        <i class="fas fa-check mr-1"></i> Save
                    </button>
                    <button @click="closeEditor()"
                            class="px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest text-slate-500 hover:bg-slate-100 transition-all">
                        <i class="fas fa-times mr-1"></i> Close
                    </button>
                </div>
            </div>

            {{-- Modal Body --}}
            <div class="px-6 py-5 space-y-5 max-h-[70vh] overflow-y-auto">
                {{-- Requirement Description --}}
                <div>
                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1.5 block">Requirement</label>
                    <p class="text-sm text-slate-700 leading-relaxed p-3 bg-slate-50 rounded-xl border border-slate-100"
                       x-text="editingFinding?.framework_control?.requirement_description || 'N/A'">
                    </p>
                </div>

                {{-- Status & Risk Row --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1.5 block">Status</label>
                        <select x-model="editForm.status"
                                class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-700 bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                            <option value="Open">Open</option>
                            <option value="In Progress">In Progress</option>
                            <option value="Closed">Closed</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1.5 block">Risk Rating</label>
                        <select x-model="editForm.risk_rating"
                                class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-700 bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                            <option value="None">None</option>
                            <option value="Low">Low</option>
                            <option value="Medium">Medium</option>
                            <option value="High">High</option>
                        </select>
                    </div>
                </div>

                {{-- Compliance Toggle --}}
                <div class="flex items-center gap-3">
                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Compliant</label>
                    <button @click="editForm.is_compliant = !editForm.is_compliant"
                            class="relative w-12 h-6 rounded-full transition-colors duration-200"
                            :class="editForm.is_compliant ? 'bg-emerald-500' : 'bg-slate-300'">
                        <span class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform duration-200"
                              :class="editForm.is_compliant ? 'translate-x-6' : ''">
                        </span>
                    </button>
                </div>

                {{-- Observation (contenteditable) --}}
                <div>
                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1.5 block">Observation / Current State</label>
                    <div class="border border-slate-200 rounded-xl overflow-hidden focus-within:ring-2 focus-within:ring-indigo-500 focus-within:border-indigo-500">
                        <div class="flex items-center gap-1 px-3 py-2 bg-slate-50 border-b border-slate-200">
                            <button @click="execCmd('bold')" class="px-2 py-1 rounded text-xs font-bold text-slate-600 hover:bg-slate-200 transition-colors" title="Bold"><i class="fas fa-bold"></i></button>
                            <button @click="execCmd('italic')" class="px-2 py-1 rounded text-xs font-bold text-slate-600 hover:bg-slate-200 transition-colors" title="Italic"><i class="fas fa-italic"></i></button>
                            <button @click="execCmd('underline')" class="px-2 py-1 rounded text-xs font-bold text-slate-600 hover:bg-slate-200 transition-colors" title="Underline"><i class="fas fa-underline"></i></button>
                            <span class="w-px h-4 bg-slate-300 mx-1"></span>
                            <button @click="execCmd('insertUnorderedList')" class="px-2 py-1 rounded text-xs font-bold text-slate-600 hover:bg-slate-200 transition-colors" title="Bullet List"><i class="fas fa-list-ul"></i></button>
                            <button @click="execCmd('insertOrderedList')" class="px-2 py-1 rounded text-xs font-bold text-slate-600 hover:bg-slate-200 transition-colors" title="Numbered List"><i class="fas fa-list-ol"></i></button>
                        </div>
                        <div contenteditable="true"
                             x-ref="observationEditor"
                             class="px-4 py-3 text-sm text-slate-700 leading-relaxed min-h-[100px] focus:outline-none"
                             @input="editForm.observation = $event.target.innerHTML">
                        </div>
                    </div>
                </div>

                {{-- Gap Description --}}
                <div>
                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1.5 block">Gap Description</label>
                    <textarea x-model="editForm.gap_description"
                              class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm text-slate-700 leading-relaxed focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all resize-none"
                              rows="3"
                              placeholder="Describe the gap..."></textarea>
                </div>

                {{-- Impact --}}
                <div>
                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1.5 block">Impact</label>
                    <textarea x-model="editForm.impact"
                              class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm text-slate-700 leading-relaxed focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all resize-none"
                              rows="3"
                              placeholder="Describe the business impact..."></textarea>
                </div>

                {{-- Recommendation --}}
                <div>
                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1.5 block">Recommendation</label>
                    <textarea x-model="editForm.recommendation"
                              class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm text-slate-700 leading-relaxed focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all resize-none"
                              rows="3"
                              placeholder="Recommend remediation actions..."></textarea>
                </div>

                {{-- Due Date --}}
                <div>
                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1.5 block">Due Date</label>
                    <input type="date" x-model="editForm.due_date"
                           class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-700 bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                </div>

                {{-- Evidence Section --}}
                <div>
                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1.5 block">Evidence Files</label>

                    {{-- Existing Evidence --}}
                    <div class="space-y-1.5 mb-3">
                        <template x-for="ev in evidenceFiles" :key="ev.id">
                            <div class="flex items-center justify-between p-2.5 bg-slate-50 rounded-lg border border-slate-100">
                                <div class="flex items-center gap-2 min-w-0">
                                    <i class="fas fa-file-alt text-slate-400 text-xs"></i>
                                    <span class="text-xs font-semibold text-slate-600 truncate" x-text="ev.name"></span>
                                    <span class="text-[9px] font-bold text-slate-400 uppercase" x-text="ev.type"></span>
                                </div>
                                <button @click="detachEvidence(ev)" class="text-rose-400 hover:text-rose-600 transition-colors flex-shrink-0 ml-2">
                                    <i class="fas fa-times text-xs"></i>
                                </button>
                            </div>
                        </template>
                    </div>

                    {{-- Upload / Attach --}}
                    <div class="evidence-drop-zone rounded-xl p-4 text-center" id="evidenceDropZone"
                         @dragover.prevent="dragOver = true"
                         @dragleave.prevent="dragOver = false"
                         @drop.prevent="handleFileDrop($event)">
                        <input type="file" x-ref="fileInput" class="hidden" @change="uploadEvidence($event)">
                        <template x-if="!uploading">
                            <div>
                                <i class="fas fa-cloud-upload-alt text-xl text-slate-300 mb-1.5 block"></i>
                                <p class="text-xs text-slate-400 font-semibold">
                                    Drag & drop evidence here or
                                    <button @click="$refs.fileInput.click()" class="text-indigo-600 hover:underline font-bold">browse</button>
                                </p>
                            </div>
                        </template>
                        <template x-if="uploading">
                            <div class="flex items-center justify-center gap-2">
                                <i class="fas fa-spinner fa-spin text-indigo-500"></i>
                                <span class="text-xs font-semibold text-slate-500">Uploading...</span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50 flex items-center justify-between">
                <div class="text-[10px] text-slate-400 font-semibold">
                    <i class="fas fa-keyboard mr-1"></i>
                    <span>Ctrl+Enter to save &middot; Esc to close</span>
                </div>
                <div class="flex items-center gap-2">
                    <button @click="closeEditor()"
                            class="px-5 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest text-slate-500 bg-white border border-slate-200 hover:bg-slate-100 transition-all">
                        Cancel
                    </button>
                    <button @click="saveFinding()"
                            class="px-6 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest bg-indigo-600 text-white hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-500/20">
                        <i class="fas fa-check mr-1"></i> Save Changes
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function gapAssessmentWorkspace() {
    return {
        // State
        assessmentExists: {{ $assessment->exists ? 'true' : 'false' }},
        selectedFrameworkSlug: '{{ $framework?->slug ?? '' }}',
        frameworks: @json($frameworks),
        groupedFindings: @json($groupedFindings),
        groupedStats: @json($groupedStats),
        overallStats: @json($overallStats),
        activeDomain: Object.keys(@json($groupedFindings))[0] || '',
        flashMessage: '{{ session('success') ?: session('error') ?: '' }}',
        flashType: '{{ session('success') ? 'success' : (session('error') ? 'error' : '') }}',

        // Editor state
        editorOpen: false,
        editingFinding: null,
        evidenceFiles: [],
        uploading: false,
        dragOver: false,
        editForm: {
            status: 'Open',
            risk_rating: 'None',
            is_compliant: false,
            observation: '',
            gap_description: '',
            impact: '',
            recommendation: '',
            due_date: '',
        },

        get totalFindings() {
            let count = 0;
            for (const domain in this.groupedFindings) {
                count += this.groupedFindings[domain].length;
            }
            return count;
        },

        get overallCompliancePct() {
            return this.overallStats?.compliancePct || 0;
        },

        get overallProgress() {
            return this.overallStats?.progressScore || 0;
        },

        initializeAssessment() {
            const baseUrl = '{{ route("gap-assessment.initialize", ["project" => $project, "framework" => "_framework_id_"]) }}';
            const url = baseUrl.replace('_framework_id_', this.selectedFramework || '{{ $framework?->id ?? "" }}');
            fetch(url, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            }).then(res => {
                if (res.redirected) window.location.href = res.url;
                else window.location.reload();
            }).catch(() => window.location.reload());
        },

        openEditor(finding) {
            this.editingFinding = finding;
            this.editForm = {
                status: finding.status || 'Open',
                risk_rating: finding.risk_rating || 'None',
                is_compliant: finding.is_compliant || false,
                observation: finding.observation || '',
                gap_description: finding.gap_description || '',
                impact: finding.impact || '',
                recommendation: finding.recommendation || '',
                due_date: finding.due_date || '',
            };
            this.loadEvidence(finding);
            this.editorOpen = true;
            this.$nextTick(() => {
                if (this.$refs.observationEditor) {
                    this.$refs.observationEditor.innerHTML = this.editForm.observation;
                }
            });
        },

        closeEditor() {
            this.editorOpen = false;
            this.editingFinding = null;
            this.evidenceFiles = [];
        },

        saveFinding() {
            if (!this.editingFinding) return;

            const baseUrl = '{{ route("gap-assessment.update", ["project" => $project, "finding" => "_finding_id_"]) }}';
            const url = baseUrl.replace('_finding_id_', this.editingFinding.id);
            fetch(url, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(this.editForm)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Update local finding data
                    Object.assign(this.editingFinding, data.finding);
                    this.showFlash('Finding updated successfully', 'success');
                    this.closeEditor();
                }
            })
            .catch(() => {
                this.showFlash('Failed to save finding', 'error');
            });
        },

        loadEvidence(finding) {
            if (!finding?.id) return;
            const baseUrl = '{{ route("gap-assessment.get-finding", ["project" => $project, "finding" => "_finding_id_"]) }}';
            const url = baseUrl.replace('_finding_id_', finding.id);
            fetch(url)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        this.evidenceFiles = data.evidence_files || [];
                    }
                })
                .catch(() => {});
        },

        uploadEvidence(event) {
            const file = event.target.files?.[0];
            if (!file) return;
            this.doUpload(file);
        },

        handleFileDrop(event) {
            this.dragOver = false;
            const file = event.dataTransfer?.files?.[0];
            if (file) this.doUpload(file);
        },

        doUpload(file) {
            if (!this.editingFinding?.id) return;
            this.uploading = true;

            const formData = new FormData();
            formData.append('file', file);

            const baseUrl = '{{ route("assessments.unified.upload-evidence", ["finding" => "_finding_id_"]) }}';
            const url = baseUrl.replace('_finding_id_', this.editingFinding.id);
            fetch(url, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    this.loadEvidence(this.editingFinding);
                    this.showFlash('Evidence uploaded', 'success');
                }
            })
            .catch(() => {
                this.showFlash('Upload failed', 'error');
            })
            .finally(() => {
                this.uploading = false;
            });
        },

        detachEvidence(evidence) {
            if (!this.editingFinding?.id || !evidence?.id) return;
            const url = '{{ url("assessments/findings") }}' + '/' + this.editingFinding.id + '/evidence/' + evidence.id + '/detach';
            fetch(url, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    this.loadEvidence(this.editingFinding);
                    this.showFlash('Evidence detached', 'success');
                }
            })
            .catch(() => {
                this.showFlash('Failed to detach evidence', 'error');
            });
        },

        execCmd(command) {
            document.execCommand(command, false, null);
            this.$refs.observationEditor?.focus();
        },

        showFlash(message, type) {
            this.flashMessage = message;
            this.flashType = type || 'success';
            setTimeout(() => this.flashMessage = null, 5000);
        },
    };
}
</script>
@endpush
@endsection
