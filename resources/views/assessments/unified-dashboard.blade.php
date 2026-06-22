@extends('layouts.app')

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/frappe-gantt@0.6.1/dist/frappe-gantt.min.css">
<style>
    .font-outfit { font-family: 'Outfit', sans-serif; }
    .ql-container.ql-snow {
        border: none !important;
        font-family: inherit;
        font-size: 0.875rem;
    }
    .ql-toolbar.ql-snow {
        border: none !important;
        border-bottom: 1px solid #e2e8f0 !important;
        background-color: #f8fafc;
    }
    .quill-editor {
        min-height: 120px;
        background-color: #ffffff;
    }
    .glass-card {
        background: rgba(255, 255, 255, 0.85);
        backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.6);
    }
    .gantt .bar-project { fill: #0a1e42; }
    .gantt .bar-high { fill: #ef4444; }
    .gantt .bar-medium { fill: #f59e0b; }
    .gantt .bar-low { fill: #10b981; }
</style>
@endpush

@section('content')
<div class="space-y-6 font-outfit max-w-full" x-data="unifiedDashboard()">

    {{-- Alert Messages --}}
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-semibold flex items-center gap-3">
            <i class="fas fa-check-circle text-emerald-500 text-lg"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    {{-- Top Header Section --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <a href="{{ route('projects.show', $project) }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-400 hover:text-slate-600 uppercase tracking-widest mb-2 transition">
                <i class="fas fa-arrow-left"></i> Back to Project Hub
            </a>
            <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">
                {{ $framework->name }} <span class="gradient-text">Assessment</span>
            </h1>
            <p class="mt-1 text-sm text-slate-500 font-medium">
                Project: <span class="font-bold text-slate-700">{{ $project->name }}</span>
                @if($assessment)
                    &bull;
                    <span class="text-slate-600">
                        {{ $assessment->start_date->format('d M Y') }} &ndash; {{ $assessment->end_date->format('d M Y') }}
                    </span>
                @endif
            </p>
        </div>

        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
            {{-- Segmented View Toggle --}}
            <div class="flex bg-slate-200 p-1 rounded-xl self-start">
                <a href="{{ route('assessments.unified.show', [$project, $framework->slug, 'gap']) }}" 
                   class="px-4 py-2 text-xs font-bold uppercase tracking-wider rounded-lg transition-all {{ $type === 'Gap' ? 'bg-[#0a1e42] text-white shadow-sm' : 'text-slate-600 hover:text-slate-800' }}">
                    Gap Assessment
                </a>
                <a href="{{ route('assessments.unified.show', [$project, $framework->slug, 'final']) }}" 
                   class="px-4 py-2 text-xs font-bold uppercase tracking-wider rounded-lg transition-all {{ $type === 'Final' ? 'bg-[#0a1e42] text-white shadow-sm' : 'text-slate-600 hover:text-slate-800' }}">
                    Final Assessment
                </a>
            </div>

            @if($assessment)
                <div class="flex items-center gap-2">
                    <a href="{{ route('assessments.unified.report', $assessment) }}"
                       class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-slate-850 hover:bg-slate-900 text-white text-xs font-bold uppercase tracking-wider rounded-xl transition shadow-sm">
                        <i class="fas fa-file-pdf"></i> Export PDF
                    </a>
                </div>
            @endif
        </div>
    </div>

    {{-- ------------------------------------------------------------------ --}}
    {{-- Initialise Form (if no assessment is found)                        --}}
    {{-- ------------------------------------------------------------------ --}}
    @if(!$assessment)
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-10 text-center max-w-lg mx-auto my-10">
        <div class="w-16 h-16 rounded-2xl bg-indigo-50 flex items-center justify-center mx-auto mb-5">
            <i class="fas fa-{{ $type === 'Gap' ? 'search-plus' : 'clipboard-check' }} text-2xl text-indigo-500"></i>
        </div>
        <h2 class="text-xl font-bold text-slate-800 mb-2">
            No {{ $type }} Assessment Found
        </h2>
        <p class="text-sm text-slate-500 mb-6">
            Initialize the {{ $framework->name }} {{ $type }} Assessment period to generate control findings and start auditing.
        </p>
        <form action="{{ route('assessments.unified.initialize', [$project, $framework->slug, strtolower($type)]) }}" method="POST" class="space-y-4 text-left">
            @csrf
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Start Date</label>
                    <input type="date" name="start_date" required 
                           class="w-full border border-slate-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-[#0a1e42] focus:border-[#0a1e42] transition">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">End Date</label>
                    <input type="date" name="end_date" required 
                           class="w-full border border-slate-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-[#0a1e42] focus:border-[#0a1e42] transition">
                </div>
            </div>
            <button type="submit" class="w-full px-5 py-3 bg-[#0a1e42] hover:bg-opacity-95 text-white text-xs font-bold uppercase tracking-wider rounded-xl transition">
                <i class="fas fa-play mr-1.5"></i> Initialize Assessment
            </button>
        </form>
    </div>
    @else

    {{-- ------------------------------------------------------------------ --}}
    {{-- Stats Cards                                                         --}}
    {{-- ------------------------------------------------------------------ --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Findings</p>
            <p class="mt-3 text-4xl font-extrabold text-slate-800" x-text="stats.total"></p>
            <p class="mt-1 text-xs text-slate-400">audited entries</p>
        </div>

        <div class="bg-emerald-50 rounded-2xl border border-emerald-200/60 shadow-sm p-5">
            <p class="text-xs font-bold text-emerald-600 uppercase tracking-wider">Compliance Score</p>
            <p class="mt-3 text-4xl font-extrabold text-emerald-700" x-text="stats.compliancePct + '%'"></p>
            <p class="mt-1 text-xs text-emerald-600" x-text="stats.compliant + ' of ' + stats.total + ' compliant'"></p>
        </div>

        <div class="bg-rose-50 rounded-2xl border border-rose-200/60 shadow-sm p-5">
            <p class="text-xs font-bold text-rose-600 uppercase tracking-wider">High Risk</p>
            <p class="mt-3 text-4xl font-extrabold text-rose-700" x-text="stats.high"></p>
            <p class="mt-1 text-xs text-rose-600">critical observations</p>
        </div>

        <div class="bg-amber-50 rounded-2xl border border-amber-200/60 shadow-sm p-5">
            <p class="text-xs font-bold text-amber-600 uppercase tracking-wider">Open Gaps</p>
            <p class="mt-3 text-4xl font-extrabold text-amber-700" x-text="stats.open"></p>
            <p class="mt-1 text-xs text-amber-600">require remediation</p>
        </div>
    </div>

    {{-- Compliance Progress Bar --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-bold text-slate-700">Overall Compliance Status</h3>
            <span class="text-sm font-bold text-[#0a1e42]" x-text="stats.compliancePct + '%'"></span>
        </div>
        <div class="w-full bg-slate-100 rounded-full h-3">
            <div class="bg-gradient-to-r from-red-500 via-amber-400 to-emerald-500 h-3 rounded-full transition-all duration-300"
                 :style="'width: ' + stats.compliancePct + '%'"></div>
        </div>
        <div class="flex flex-wrap justify-between mt-3 text-xs text-slate-500 gap-2">
            <span>Compliant: <strong class="text-emerald-600" x-text="stats.compliant"></strong></span>
            <span>Non-Compliant: <strong class="text-rose-500" x-text="stats.nonCompliant"></strong></span>
            <span>Open: <strong class="text-rose-500" x-text="stats.open"></strong></span>
            <span>In Progress: <strong class="text-blue-500" x-text="stats.inProgress"></strong></span>
            <span>Closed: <strong class="text-emerald-600" x-text="stats.closed"></strong></span>
        </div>
    </div>

    {{-- Clone Info Notification Banner --}}
    @if($type === 'Gap')
    <div x-show="stats.compliancePct == 100" x-transition class="bg-gradient-to-r from-emerald-500 to-teal-600 border border-emerald-400 rounded-2xl p-6 text-white shadow-xl hover:shadow-emerald-500/25 transition-all">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-white/10 flex items-center justify-center animate-bounce">
                    <i class="fas fa-trophy text-xl text-yellow-300"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold">Phase 1 Completed!</h3>
                    <p class="text-xs text-emerald-100 mt-1">The Gap Assessment is 100% compliant. Phase 2 (Final Assessment) has been automatically started and all findings synchronized.</p>
                </div>
            </div>
            <a href="{{ route('assessments.unified.show', [$project, $framework->slug, 'final']) }}"
               class="inline-flex items-center justify-center gap-2 px-5 py-3 bg-white hover:bg-emerald-50 text-emerald-800 text-xs font-bold uppercase tracking-wider rounded-xl transition-all shadow-md transform hover:scale-105">
                Start Phase 2: Final Assessment <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>

    <div x-show="stats.compliancePct < 100" class="bg-sky-50 border border-sky-200 rounded-2xl p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h3 class="text-sm font-bold text-sky-850">Gap Assessment Progress</h3>
                <p class="text-xs text-sky-650 mt-1">Once all findings are marked as compliant, Phase 2 (Final Assessment) will be automatically started and findings synchronized.</p>
            </div>
        </div>
    </div>
    @endif

    {{-- ------------------------------------------------------------------ --}}
    {{-- Summary Table                                                      --}}
    {{-- ------------------------------------------------------------------ --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
            <h2 class="text-sm font-extrabold text-slate-800 uppercase tracking-wider">
                Summary of Findings
            </h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[600px]">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-xs font-bold text-slate-500 uppercase tracking-widest">
                        <th class="px-6 py-3 w-16">S.N</th>
                        <th class="px-6 py-3 w-28">Ref ID</th>
                        <th class="px-6 py-3">Requirement Description</th>
                        <th class="px-6 py-3 w-28 text-center">Risk</th>
                        <th class="px-6 py-3 w-28 text-center">Status</th>
                        <th class="px-6 py-3 w-28 text-center">Compliance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse($assessment->findings as $index => $finding)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-3.5 text-slate-500 font-semibold">{{ $index + 1 }}</td>
                            <td class="px-6 py-3.5 font-mono font-bold text-slate-800">{{ $finding->frameworkControl ? $finding->frameworkControl->control_id : 'N/A' }}</td>
                            <td class="px-6 py-3.5 font-medium text-slate-700 leading-normal">{{ Str::limit($finding->frameworkControl ? $finding->frameworkControl->requirement_description : 'N/A', 100) }}</td>
                            <td class="px-6 py-3.5 text-center">
                                <span class="font-bold text-xs" 
                                      :class="{
                                        'text-rose-600': findingsData[{{ $finding->id }}].risk_rating === 'High',
                                        'text-amber-500': findingsData[{{ $finding->id }}].risk_rating === 'Medium',
                                        'text-emerald-600': findingsData[{{ $finding->id }}].risk_rating === 'Low',
                                        'text-slate-400': findingsData[{{ $finding->id }}].risk_rating === 'None'
                                      }"
                                      x-text="findingsData[{{ $finding->id }}].risk_rating"></span>
                            </td>
                            <td class="px-6 py-3.5 text-center font-bold text-xs"
                                  :class="{
                                    'text-rose-600': findingsData[{{ $finding->id }}].status === 'Open',
                                    'text-blue-500': findingsData[{{ $finding->id }}].status === 'In Progress',
                                    'text-emerald-600': findingsData[{{ $finding->id }}].status === 'Closed'
                                  }"
                                  x-text="findingsData[{{ $finding->id }}].status"></td>
                            <td class="px-6 py-3.5 text-center">
                                <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider"
                                      :class="findingsData[{{ $finding->id }}].is_compliant ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-rose-100 text-rose-800 border border-rose-200'"
                                      x-text="findingsData[{{ $finding->id }}].is_compliant ? 'Compliant' : 'Non-Compliant'"></span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-sm text-slate-400">
                                No framework controls defined. Please import framework controls in library first.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ------------------------------------------------------------------ --}}
    {{-- Timeline / Gantt                                                   --}}
    {{-- ------------------------------------------------------------------ --}}
    @if(!$assessment->findings->isEmpty())
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
        <h2 class="text-sm font-extrabold text-slate-800 uppercase tracking-wider mb-4">Remediation Timeline Chart</h2>
        <div id="gantt-container" class="overflow-x-auto"></div>
    </div>
    @endif

    {{-- ------------------------------------------------------------------ --}}
    {{-- Detailed Audit Tables (Collapsible Rows)                           --}}
    {{-- ------------------------------------------------------------------ --}}
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-extrabold text-slate-800 uppercase tracking-widest">Detailed Control Findings Entry</h2>
            <span class="text-xs text-slate-400 font-bold">Fields support HTML formatted bullets</span>
        </div>

        @foreach($assessment->findings as $finding)
        <div x-data="{ expanded: false }" class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden transition-all">
            {{-- Accordion Header --}}
            <div @click="expanded = !expanded" class="px-6 py-4 bg-slate-50/50 border-b border-slate-200 flex items-center justify-between cursor-pointer hover:bg-slate-100/50 transition">
                <div class="flex items-center gap-3">
                    <span class="text-xs font-mono font-bold text-[#0a1e42] bg-indigo-50 border border-indigo-100 rounded-lg px-2.5 py-1">
                        {{ $finding->frameworkControl ? $finding->frameworkControl->control_id : 'N/A' }}
                    </span>
                    <h3 class="text-sm font-bold text-slate-750 truncate max-w-lg">
                        {{ $finding->frameworkControl ? Str::limit($finding->frameworkControl->requirement_description, 60) : 'N/A' }}
                    </h3>
                </div>
                <div class="flex items-center gap-3">
                    <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider"
                          :class="findingsData[{{ $finding->id }}].is_compliant ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'"
                          x-text="findingsData[{{ $finding->id }}].is_compliant ? 'Compliant' : 'Non-Compliant'"></span>
                    <i :class="expanded ? 'fa-chevron-up' : 'fa-chevron-down'" class="fas text-slate-400 transition-transform"></i>
                </div>
            </div>

            {{-- Accordion Body Form --}}
            <div x-show="expanded" x-collapse class="p-6 border-t border-slate-100" x-cloak>
                <div class="space-y-4">
                    {{-- 2-Column Bordered Navy Grid Layout --}}
                    <table class="w-full border-collapse border border-slate-300 text-sm">
                        <tbody>
                            <!-- Row 1: S.N and Status -->
                            <tr>
                                <td class="w-[18%] bg-[#0a1e42] text-white font-bold px-4 py-3 border border-slate-300 align-middle text-xs uppercase tracking-wider">Control ID:</td>
                                <td class="w-[32%] bg-white px-3 py-2 border border-slate-300 font-mono font-bold text-slate-800">
                                    {{ $finding->frameworkControl ? $finding->frameworkControl->control_id : 'N/A' }}
                                </td>
                                <td class="w-[18%] bg-[#0a1e42] text-white font-bold px-4 py-3 border border-slate-300 align-middle text-xs uppercase tracking-wider">Status:</td>
                                <td class="w-[32%] bg-white px-3 py-2 border border-slate-300">
                                    <select x-model="findingsData[{{ $finding->id }}].status"
                                            class="w-full border border-slate-300 rounded-xl px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-[#0a1e42] text-slate-800 transition bg-white text-xs font-semibold">
                                        <option value="Open">Open</option>
                                        <option value="In Progress">In Progress</option>
                                        <option value="Closed">Closed</option>
                                    </select>
                                </td>
                            </tr>
                            
                            <!-- Row 2: Domain and Risk Rating -->
                            <tr>
                                <td class="bg-[#0a1e42] text-white font-bold px-4 py-3 border border-slate-300 align-middle text-xs uppercase tracking-wider">Domain:</td>
                                <td class="bg-white px-3 py-2 border border-slate-300 text-xs font-semibold text-slate-700">
                                    {{ $finding->frameworkControl ? $finding->frameworkControl->domain : 'N/A' }}
                                </td>
                                <td class="bg-[#0a1e42] text-white font-bold px-4 py-3 border border-slate-300 align-middle text-xs uppercase tracking-wider">Risk Rating:</td>
                                <td class="bg-white px-3 py-2 border border-slate-300">
                                    <select x-model="findingsData[{{ $finding->id }}].risk_rating"
                                            class="w-full border border-slate-300 rounded-xl px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-[#0a1e42] text-slate-800 transition bg-white text-xs font-semibold">
                                        <option value="High">High</option>
                                        <option value="Medium">Medium</option>
                                        <option value="Low">Low</option>
                                        <option value="None">None</option>
                                    </select>
                                </td>
                            </tr>

                            <!-- Row 3: Requirement Description (static) -->
                            <tr>
                                <td class="bg-[#0a1e42] text-white font-bold px-4 py-3 border border-slate-300 text-xs uppercase tracking-wider">Control Description:</td>
                                <td class="bg-white px-4 py-3 border border-slate-300 text-xs text-slate-600 leading-relaxed font-medium" colspan="3">
                                    {{ $finding->frameworkControl ? $finding->frameworkControl->requirement_description : 'N/A' }}
                                </td>
                            </tr>

                            <!-- Row 4: Observation (Quill Editor) -->
                            <tr>
                                <td class="bg-[#0a1e42] text-white font-bold px-4 py-3 border border-slate-300 text-xs uppercase tracking-wider">Observation / Current State:</td>
                                <td class="bg-white p-0 border border-slate-300" colspan="3">
                                    <div class="quill-editor" id="quill-obs-{{ $finding->id }}" data-id="{{ $finding->id }}" data-field="observation"></div>
                                </td>
                            </tr>

                            <!-- Row 5: Gap Description (Quill Editor) -->
                            <tr>
                                <td class="bg-[#0a1e42] text-white font-bold px-4 py-3 border border-slate-300 text-xs uppercase tracking-wider">Gap Description:</td>
                                <td class="bg-white p-0 border border-slate-300" colspan="3">
                                    <div class="quill-editor" id="quill-gap-{{ $finding->id }}" data-id="{{ $finding->id }}" data-field="gap_description"></div>
                                </td>
                            </tr>

                            <!-- Row 6: Impact / Risk (Quill Editor) -->
                            <tr>
                                <td class="bg-[#0a1e42] text-white font-bold px-4 py-3 border border-slate-300 text-xs uppercase tracking-wider">Impact & Risk:</td>
                                <td class="bg-white p-0 border border-slate-300" colspan="3">
                                    <div class="quill-editor" id="quill-imp-{{ $finding->id }}" data-id="{{ $finding->id }}" data-field="impact"></div>
                                </td>
                            </tr>

                            <!-- Row 7: Recommendation (Quill Editor) -->
                            <tr>
                                <td class="bg-[#0a1e42] text-white font-bold px-4 py-3 border border-slate-300 text-xs uppercase tracking-wider">Recommendations:</td>
                                <td class="bg-white p-0 border border-slate-300" colspan="3">
                                    <div class="quill-editor" id="quill-rec-{{ $finding->id }}" data-id="{{ $finding->id }}" data-field="recommendation"></div>
                                </td>
                            </tr>

                            <!-- Row 8: Compliance Status Toggle -->
                            <tr>
                                <td class="bg-[#0a1e42] text-white font-bold px-4 py-3 border border-slate-300 align-middle text-xs uppercase tracking-wider">Compliance:</td>
                                <td class="bg-white px-4 py-3 border border-slate-300" colspan="3">
                                    <div class="flex items-center gap-2">
                                        <input type="checkbox" :checked="findingsData[{{ $finding->id }}].is_compliant"
                                               @change="findingsData[{{ $finding->id }}].is_compliant = $event.target.checked"
                                               id="compliant_checkbox_{{ $finding->id }}"
                                               class="w-4 h-4 text-emerald-600 border-slate-300 rounded focus:ring-emerald-500">
                                        <label for="compliant_checkbox_{{ $finding->id }}" class="text-xs font-bold text-slate-700 uppercase tracking-wide">
                                            Mark this Control Requirement as Compliant
                                        </label>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    {{-- Evidence Links Subpanel --}}
                    <div class="border border-slate-200 rounded-xl p-4 bg-slate-50/30">
                        <span class="text-xs font-extrabold text-[#0a1e42] uppercase tracking-wider block mb-3">Linked Evidence Documents</span>
                        
                        <div class="flex flex-wrap gap-2 mb-4">
                            <template x-for="doc in linkedDocs[{{ $finding->id }}]" :key="doc.id">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-white border border-slate-200 text-xs font-bold shadow-sm">
                                    <i class="fas fa-file-alt text-slate-400"></i>
                                    <a :href="doc.url" target="_blank" class="text-sky-600 hover:text-sky-800 hover:underline truncate max-w-[200px]" x-text="doc.name"></a>
                                    <button type="button" @click="detachEvidence({{ $finding->id }}, doc.id)" class="text-slate-400 hover:text-rose-500 transition-colors ml-1">
                                        <i class="fas fa-times-circle"></i>
                                    </button>
                                </span>
                            </template>
                            <template x-if="!linkedDocs[{{ $finding->id }}] || linkedDocs[{{ $finding->id }}].length === 0">
                                <span class="text-xs text-slate-400 italic">No files currently linked. Link an existing file or upload a new file below.</span>
                            </template>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {{-- Link Existing --}}
                            <div class="p-3.5 bg-white border border-slate-200/60 rounded-xl shadow-sm">
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Link Existing Project Document</label>
                                <div class="flex gap-2">
                                    <select x-model="selectedDocForFinding[{{ $finding->id }}]" class="flex-1 bg-white border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-semibold text-slate-700 focus:outline-none">
                                        <option value="">-- Select File --</option>
                                        @foreach($projectEvidence as $doc)
                                            <option value="{{ $doc->id }}">{{ $doc->name }} ({{ $doc->created_at->format('d M Y') }})</option>
                                        @endforeach
                                    </select>
                                    <button type="button" @click="attachEvidence({{ $finding->id }})" class="px-4 py-1.5 bg-[#0a1e42] text-white text-xs font-extrabold uppercase tracking-wide rounded-xl hover:bg-opacity-95 transition whitespace-nowrap shadow-sm">
                                        Link File
                                    </button>
                                </div>
                            </div>

                            {{-- Upload New --}}
                            <div class="p-3.5 bg-white border border-slate-200/60 rounded-xl shadow-sm">
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Upload & Link New Document</label>
                                <div class="flex gap-2">
                                    <input type="file" @change="uploadNewEvidence({{ $finding->id }}, $event)" class="flex-1 bg-white border border-slate-200 rounded-xl px-3 py-1 text-xs text-slate-600 focus:outline-none">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Save Button --}}
                    <div class="flex justify-end pt-3">
                        <button type="button" @click="saveFinding({{ $finding->id }})" class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#0a1e42] hover:bg-opacity-95 text-white text-xs font-extrabold uppercase tracking-wide rounded-xl transition shadow">
                            <i class="fas fa-save"></i> Save Finding Details
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
<script src="https://cdn.jsdelivr.net/npm/frappe-gantt@0.6.1/dist/frappe-gantt.min.js"></script>
<script>
function unifiedDashboard() {
    return {
        stats: @json($stats),
        findingsData: {
            @if($assessment)
                @foreach($assessment->findings as $finding)
                {{ $finding->id }}: {
                    status: '{{ $finding->status }}',
                    risk_rating: '{{ $finding->risk_rating }}',
                    is_compliant: {{ $finding->is_compliant ? 'true' : 'false' }},
                    observation: `{!! addslashes($finding->observation) !!}`,
                    gap_description: `{!! addslashes($finding->gap_description) !!}`,
                    impact: `{!! addslashes($finding->impact) !!}`,
                    recommendation: `{!! addslashes($finding->recommendation) !!}`
                },
                @endforeach
            @endif
        },
        linkedDocs: {
            @if($assessment)
                @foreach($assessment->findings as $finding)
                {{ $finding->id }}: @json($finding->evidence->map(fn($e) => ['id' => $e->id, 'name' => $e->name, 'url' => $e->url])),
                @endforeach
            @endif
        },
        selectedDocForFinding: {},
        quillInstances: {},

        init() {
            this.$nextTick(() => {
                // Initialize Quill editors
                @if($assessment)
                    @foreach($assessment->findings as $finding)
                        this.initQuill({{ $finding->id }}, 'observation');
                        this.initQuill({{ $finding->id }}, 'gap_description');
                        this.initQuill({{ $finding->id }}, 'impact');
                        this.initQuill({{ $finding->id }}, 'recommendation');
                    @endforeach
                    
                    // Initialize Gantt
                    this.initGantt();
                @endif
            });
        },

        initQuill(findingId, field) {
            const containerId = `#quill-${field.slice(0,3)}-${findingId}`;
            const container = document.querySelector(containerId);
            if (!container) return;

            const quill = new Quill(containerId, {
                theme: 'snow',
                modules: {
                    toolbar: [
                        ['bold', 'italic', 'underline'],
                        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                        ['clean']
                    ]
                }
            });

            // Set initial content
            const content = this.findingsData[findingId][field];
            if (content) {
                quill.root.innerHTML = content;
            }

            // Sync text change to findingsData
            quill.on('text-change', () => {
                this.findingsData[findingId][field] = quill.root.innerHTML;
            });

            if (!this.quillInstances[findingId]) {
                this.quillInstances[findingId] = {};
            }
            this.quillInstances[findingId][field] = quill;
        },

        initGantt() {
            const container = document.querySelector('#gantt-container');
            if (!container) return;

            const ganttTasks = @json($assessment ? $assessment->ganttTasks() : []);
            if (ganttTasks.length > 0) {
                new Gantt('#gantt-container', ganttTasks, {
                    view_mode: 'Week',
                    date_format: 'YYYY-MM-DD',
                    custom_popup_html: function(task) {
                        return `<div class="p-2 text-xs text-slate-800 bg-white border border-slate-200 shadow rounded font-medium"><strong>${task.name}</strong><br>Progress: ${task.progress}%</div>`;
                    }
                });
            }
        },

        async saveFinding(findingId) {
            const data = this.findingsData[findingId];
            
            try {
                const res = await fetch(`/assessments/findings/${findingId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(data)
                });

                if (!res.ok) throw new Error('Failed to update');

                const result = await res.json();
                if (result.success) {
                    this.stats = result.stats;
                    
                    // Show a quick custom toast
                    const toast = document.createElement('div');
                    toast.className = 'fixed bottom-5 right-5 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold shadow-lg z-50 flex items-center gap-2';
                    toast.innerHTML = '<i class="fas fa-check-circle text-emerald-500"></i> Finding saved successfully.';
                    document.body.appendChild(toast);
                    setTimeout(() => toast.remove(), 3000);
                }
            } catch (err) {
                alert('Error saving finding details. Please try again.');
            }
        },

        async attachEvidence(findingId) {
            const evidenceId = this.selectedDocForFinding[findingId];
            if (!evidenceId) return;

            try {
                const res = await fetch(`/assessments/findings/${findingId}/evidence/attach`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ evidence_id: evidenceId })
                });

                if (!res.ok) throw new Error('Failed to link file');

                const result = await res.json();
                if (result.success) {
                    this.linkedDocs[findingId] = result.evidence.map(e => ({ id: e.id, name: e.name, url: e.url }));
                    this.selectedDocForFinding[findingId] = '';
                }
            } catch (err) {
                alert('Failed to link evidence document.');
            }
        },

        async detachEvidence(findingId, evidenceId) {
            if (!confirm('Are you sure you want to unlink this evidence document?')) return;

            try {
                const res = await fetch(`/assessments/findings/${findingId}/evidence/${evidenceId}/detach`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });

                if (!res.ok) throw new Error('Failed to unlink');

                const result = await res.json();
                if (result.success) {
                    this.linkedDocs[findingId] = this.linkedDocs[findingId].filter(doc => doc.id !== evidenceId);
                }
            } catch (err) {
                alert('Failed to unlink evidence document.');
            }
        },

        async uploadNewEvidence(findingId, event) {
            const file = event.target.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('file', file);

            try {
                const res = await fetch(`/assessments/findings/${findingId}/evidence/upload`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: formData
                });

                if (!res.ok) throw new Error('Failed to upload');

                const result = await res.json();
                if (result.success) {
                    this.linkedDocs[findingId].push({
                        id: result.evidence.id,
                        name: result.evidence.name,
                        url: result.evidence.url
                    });
                    
                    // Reset input
                    event.target.value = '';
                }
            } catch (err) {
                alert('File upload failed. Ensure the size is under 20MB.');
            }
        }
    };
}
</script>
@endpush
