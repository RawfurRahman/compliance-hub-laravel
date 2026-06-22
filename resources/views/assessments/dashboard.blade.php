@extends('layouts.app')

@push('styles')
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<style>
    /* Premium style for Quill inside table cells */
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
</style>
@endpush

@section('content')
<div class="space-y-6" x-data="{ showAddModal: false, showCloneModal: false }">

    {{-- ------------------------------------------------------------------ --}}
    {{-- Page Header & Segmented Switch                                     --}}
    {{-- ------------------------------------------------------------------ --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <a href="{{ route('projects.show', $project) }}"
               class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-indigo-600 transition mb-2">
                <i class="fas fa-arrow-left text-xs"></i> Back to Project Hub
            </a>
            <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">
                {{ $framework }} Assessment
            </h1>
            <p class="mt-1 text-sm text-slate-500 font-medium">
                Project: <span class="font-semibold text-indigo-600">{{ $project->name }}</span>
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
                <a href="{{ route('assessments.show', [$project, 'type' => 'gap']) }}" 
                   class="px-4 py-2 text-sm font-semibold rounded-lg transition-all {{ $type === 'Gap' ? 'bg-[#0a1e42] text-white shadow-sm' : 'text-slate-600 hover:text-slate-800' }}">
                    Gap Assessment
                </a>
                <a href="{{ route('assessments.show', [$project, 'type' => 'final']) }}" 
                   class="px-4 py-2 text-sm font-semibold rounded-lg transition-all {{ $type === 'Final' ? 'bg-[#0a1e42] text-white shadow-sm' : 'text-slate-600 hover:text-slate-800' }}">
                    Final Assessment
                </a>
            </div>

            @if($assessment)
                {{-- Actions --}}
                <div class="flex items-center gap-2">
                    <a href="{{ route('assessments.report', $assessment) }}"
                       class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-slate-800 text-white text-sm font-semibold rounded-xl hover:bg-slate-700 transition shadow-sm">
                        <i class="fas fa-file-pdf"></i> Export Report
                    </a>

                    <button @click="showAddModal = true"
                            class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-gradient-to-r from-sky-500 to-indigo-500 text-white text-sm font-semibold rounded-xl hover:shadow-lg hover:shadow-indigo-500/25 transition">
                        <i class="fas fa-plus text-xs"></i> Add Finding
                    </button>
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
            Initialize the {{ $type }} Assessment period to start auditing controls.
        </p>
        <form action="{{ route('assessments.store', $project) }}" method="POST" class="space-y-4 text-left">
            @csrf
            <input type="hidden" name="assessment_type" value="{{ $type }}">
            <input type="hidden" name="framework" value="{{ $framework }}">
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Start Date</label>
                    <input type="date" name="start_date" required 
                           class="w-full border border-slate-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">End Date</label>
                    <input type="date" name="end_date" required 
                           class="w-full border border-slate-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                </div>
            </div>
            <button type="submit" class="w-full px-5 py-3 bg-[#0a1e42] hover:bg-opacity-95 text-white text-sm font-semibold rounded-xl transition">
                <i class="fas fa-play mr-1.5 text-xs"></i> Initialize Assessment
            </button>
        </form>
    </div>
    @else

    {{-- ------------------------------------------------------------------ --}}
    {{-- Stats Cards                                                         --}}
    {{-- ------------------------------------------------------------------ --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-widest">Total Findings</p>
            <p class="mt-3 text-4xl font-extrabold text-slate-800">{{ $stats['total'] }}</p>
            <p class="mt-1 text-xs text-slate-400">audited entries</p>
        </div>

        <div class="bg-emerald-50 rounded-2xl border border-emerald-200 shadow-sm p-5">
            <p class="text-xs font-semibold text-emerald-500 uppercase tracking-widest">Compliance Score</p>
            <p class="mt-3 text-4xl font-extrabold text-emerald-700">{{ $stats['compliancePct'] }}%</p>
            <p class="mt-1 text-xs text-emerald-400">{{ $stats['compliant'] }} of {{ $stats['total'] }} compliant</p>
        </div>

        <div class="bg-red-50 rounded-2xl border border-red-200 shadow-sm p-5">
            <p class="text-xs font-semibold text-red-500 uppercase tracking-widest">High Risk</p>
            <p class="mt-3 text-4xl font-extrabold text-red-700">{{ $stats['high'] }}</p>
            <p class="mt-1 text-xs text-red-400">critical observations</p>
        </div>

        <div class="bg-amber-50 rounded-2xl border border-amber-200 shadow-sm p-5">
            <p class="text-xs font-semibold text-amber-500 uppercase tracking-widest">Open Gaps</p>
            <p class="mt-3 text-4xl font-extrabold text-amber-700">{{ $stats['open'] }}</p>
            <p class="mt-1 text-xs text-amber-400">require remediation</p>
        </div>
    </div>

    {{-- Compliance Progress Bar --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-bold text-slate-700">Overall Compliance Status</h3>
            <span class="text-sm font-bold text-indigo-600">{{ $stats['compliancePct'] }}%</span>
        </div>
        <div class="w-full bg-slate-100 rounded-full h-3">
            <div class="bg-gradient-to-r from-red-500 via-amber-400 to-emerald-500 h-3 rounded-full transition-all"
                 style="width: {{ $stats['compliancePct'] }}%"></div>
        </div>
        <div class="flex justify-between mt-3 text-xs text-slate-500">
            <span>Compliant: <strong class="text-emerald-600">{{ $stats['compliant'] }}</strong></span>
            <span>Non-Compliant: <strong class="text-red-500">{{ $stats['nonCompliant'] }}</strong></span>
            <span>Open: <strong class="text-red-500">{{ $stats['open'] }}</strong></span>
            <span>In Progress: <strong class="text-blue-500">{{ $stats['inProgress'] }}</strong></span>
            <span>Closed: <strong class="text-emerald-600">{{ $stats['closed'] }}</strong></span>
        </div>
    </div>

    {{-- Clone to Final Action Banner (Gap only) --}}
    @if($type === 'Gap')
        @if($stats['compliancePct'] == 100)
        <div class="bg-gradient-to-r from-emerald-500 to-teal-600 border border-emerald-400 rounded-2xl p-6 text-white shadow-xl hover:shadow-emerald-500/25 transition-all">
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
                <a href="{{ route('assessments.show', [$project, 'type' => 'final']) }}"
                   class="inline-flex items-center justify-center gap-2 px-5 py-3 bg-white hover:bg-emerald-50 text-emerald-800 text-xs font-bold uppercase tracking-wider rounded-xl transition-all shadow-md transform hover:scale-105">
                    Start Phase 2: Final Assessment <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
        @else
        <div class="bg-sky-50 border border-sky-200 rounded-2xl p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h3 class="text-sm font-bold text-sky-850">Gap Assessment Progress</h3>
                    <p class="text-xs text-sky-650 mt-1">Once all findings are marked as compliant, Phase 2 (Final Assessment) will be automatically started and findings synchronized.</p>
                </div>
            </div>
        </div>
        @endif
    @endif

    {{-- ------------------------------------------------------------------ --}}
    {{-- Summary Table (Strict Requirement)                                 --}}
    {{-- ------------------------------------------------------------------ --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50">
            <h2 class="text-lg font-bold text-slate-800">
                Summary of Findings
            </h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-100">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide w-20">S.N</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide w-36">Ref: Clause</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Observation Title</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold text-slate-500 uppercase tracking-wide w-32">Risk Rating</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold text-slate-500 uppercase tracking-wide w-32">Compliance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse($assessment->findings as $index => $finding)
                        <tr>
                            <td class="px-6 py-4 text-sm font-medium text-slate-500">{{ $index + 1 }}</td>
                            <td class="px-6 py-4 text-sm font-mono font-semibold text-slate-700">{{ $finding->serial_no }}</td>
                            <td class="px-6 py-4 text-sm font-medium text-slate-900">{{ $finding->observation_title }}</td>
                            <td class="px-6 py-4 text-center">
                                @if($finding->risk_rating === 'High')
                                    <span class="text-red-600 font-bold text-sm">High</span>
                                @elseif($finding->risk_rating === 'Medium')
                                    <span class="text-orange-500 font-bold text-sm">Medium</span>
                                @elseif($finding->risk_rating === 'Low')
                                    <span class="text-green-600 font-bold text-sm">Low</span>
                                @else
                                    <span class="text-slate-400 font-bold text-sm">None</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($finding->is_compliant)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700">Compliant</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-700">Non-Compliant</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-sm text-slate-400">
                                No findings recorded. Click "+ Add Finding" to get started.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ------------------------------------------------------------------ --}}
    {{-- Detailed Audit Tables (Strict 2-Column Navy Grid)                  --}}
    {{-- ------------------------------------------------------------------ --}}
    <div class="space-y-8">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-bold text-slate-800">Detailed Findings Entry</h2>
            <span class="text-xs text-slate-400">All data entry cells support HTML Bullet lists</span>
        </div>

        @foreach($assessment->findings as $finding)
        <div x-data="{ expanded: false }" class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden transition-all duration-200">
            {{-- Accordion Header --}}
            <div @click="expanded = !expanded" class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between cursor-pointer hover:bg-slate-100 transition">
                <div class="flex items-center gap-3">
                    <span class="text-sm font-mono font-bold text-[#0a1e42] bg-indigo-50 border border-indigo-100 rounded px-2.5 py-1">
                        {{ $finding->serial_no }}
                    </span>
                    <h3 class="text-md font-bold text-slate-800 truncate max-w-lg">
                        {{ $finding->observation_title }}
                    </h3>
                </div>
                <div class="flex items-center gap-3">
                    @if($finding->is_compliant)
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                            <i class="fas fa-check-circle mr-1"></i> Compliant
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-red-100 text-red-800">
                            <i class="fas fa-exclamation-circle mr-1"></i> Non-Compliant
                        </span>
                    @endif
                    <i :class="expanded ? 'fa-chevron-up' : 'fa-chevron-down'" class="fas text-slate-400 transition-transform"></i>
                </div>
            </div>

            {{-- Accordion Body Form --}}
            <div x-show="expanded" x-collapse class="p-6 border-t border-slate-100">
                <form action="{{ route('assessments.findings.update', $finding) }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <table class="w-full border-collapse border border-slate-300 text-sm">
                        <tbody>
                            <!-- Row 1 -->
                            <tr>
                                <td class="w-[18%] bg-[#0a1e42] text-white font-bold px-4 py-3 border border-slate-300 align-middle">Serial No:</td>
                                <td class="w-[32%] bg-white px-3 py-2 border border-slate-300">
                                    <input type="text" name="serial_no" value="{{ $finding->serial_no }}" required
                                           class="w-full border border-slate-300 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 transition">
                                </td>
                                <td class="w-[18%] bg-[#0a1e42] text-white font-bold px-4 py-3 border border-slate-300 align-middle">Status:</td>
                                <td class="w-[32%] bg-white px-3 py-2 border border-slate-300">
                                    <select name="status" required
                                            class="w-full border border-slate-300 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 transition bg-white">
                                        <option value="Open" {{ $finding->status === 'Open' ? 'selected' : '' }}>Open</option>
                                        <option value="In Progress" {{ $finding->status === 'In Progress' ? 'selected' : '' }}>In Progress</option>
                                        <option value="Closed" {{ $finding->status === 'Closed' ? 'selected' : '' }}>Closed</option>
                                    </select>
                                </td>
                            </tr>
                            <!-- Row 2 -->
                            <tr>
                                <td class="bg-[#0a1e42] text-white font-bold px-4 py-3 border border-slate-300 align-middle">Observation Title:</td>
                                <td class="bg-white px-3 py-2 border border-slate-300">
                                    <input type="text" name="observation_title" value="{{ $finding->observation_title }}" required
                                           class="w-full border border-slate-300 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 transition">
                                </td>
                                <td class="bg-[#0a1e42] text-white font-bold px-4 py-3 border border-slate-300 align-middle">Risk Rating:</td>
                                <td class="bg-white px-3 py-2 border border-slate-300">
                                    <select name="risk_rating" required
                                            class="w-full border border-slate-300 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 transition bg-white">
                                        <option value="High" {{ $finding->risk_rating === 'High' ? 'selected' : '' }}>High</option>
                                        <option value="Medium" {{ $finding->risk_rating === 'Medium' ? 'selected' : '' }}>Medium</option>
                                        <option value="Low" {{ $finding->risk_rating === 'Low' ? 'selected' : '' }}>Low</option>
                                        <option value="None" {{ $finding->risk_rating === 'None' ? 'selected' : '' }}>None</option>
                                    </select>
                                </td>
                            </tr>
                            <!-- Row 3 -->
                            <tr>
                                <td class="bg-[#0a1e42] text-white font-bold px-4 py-3 border border-slate-300">Current State / Observation</td>
                                <td class="bg-white p-0 border border-slate-300" colspan="3">
                                    <textarea name="current_state" class="hidden">{{ $finding->current_state }}</textarea>
                                    <div class="quill-editor" data-field="current_state"></div>
                                </td>
                            </tr>
                            <!-- Row 4 -->
                            <tr>
                                <td class="bg-[#0a1e42] text-white font-bold px-4 py-3 border border-slate-300">Gap Description</td>
                                <td class="bg-white p-0 border border-slate-300" colspan="3">
                                    <textarea name="gap_description" class="hidden">{{ $finding->gap_description }}</textarea>
                                    <div class="quill-editor" data-field="gap_description"></div>
                                </td>
                            </tr>
                            <!-- Row 5 -->
                            <tr>
                                <td class="bg-[#0a1e42] text-white font-bold px-4 py-3 border border-slate-300">Impact / Risk</td>
                                <td class="bg-white p-0 border border-slate-300" colspan="3">
                                    <textarea name="impact_risk" class="hidden">{{ $finding->impact_risk }}</textarea>
                                    <div class="quill-editor" data-field="impact_risk"></div>
                                </td>
                            </tr>
                            <!-- Row 6 -->
                            <tr>
                                <td class="bg-[#0a1e42] text-white font-bold px-4 py-3 border border-slate-300">Recommendation</td>
                                <td class="bg-white p-0 border border-slate-300" colspan="3">
                                    <textarea name="recommendation" class="hidden">{{ $finding->recommendation }}</textarea>
                                    <div class="quill-editor" data-field="recommendation"></div>
                                </td>
                            </tr>
                            <!-- Row 7 -->
                            <tr>
                                <td class="bg-[#0a1e42] text-white font-bold px-4 py-3 border border-slate-300">Relevant Standard Reference</td>
                                <td class="bg-white p-0 border border-slate-300" colspan="3">
                                    <textarea name="standard_reference" class="hidden">{{ $finding->standard_reference }}</textarea>
                                    <div class="quill-editor" data-field="standard_reference"></div>
                                </td>
                            </tr>
                            <!-- Row 8 (Is Compliant Checkbox) -->
                            <tr>
                                <td class="bg-[#0a1e42] text-white font-bold px-4 py-3 border border-slate-300 align-middle">Is Compliant?</td>
                                <td class="bg-white px-4 py-3 border border-slate-300" colspan="3">
                                    <div class="flex items-center gap-2">
                                        <input type="checkbox" name="is_compliant" value="1" id="compliant_{{ $finding->id }}"
                                               {{ $finding->is_compliant ? 'checked' : '' }}
                                               class="w-4 h-4 text-indigo-600 border-slate-300 rounded focus:ring-indigo-500">
                                        <label for="compliant_{{ $finding->id }}" class="text-sm font-semibold text-slate-700">
                                            Mark control as Compliant
                                        </label>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="flex justify-end gap-3 pt-3 border-t border-slate-100">
                        <button type="button" onclick="deleteFinding({{ $finding->id }})"
                                class="px-4 py-2 text-sm font-semibold text-rose-600 bg-rose-50 hover:bg-rose-100 rounded-xl transition">
                            <i class="fas fa-trash-alt mr-1"></i> Delete
                        </button>
                        <button type="submit"
                                class="px-5 py-2 text-sm font-semibold text-white bg-[#0a1e42] hover:bg-opacity-90 rounded-xl transition shadow">
                            <i class="fas fa-save mr-1"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Frappe Gantt Remediation Chart --}}
    @if(!$assessment->findings->isEmpty())
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
        <h2 class="text-lg font-bold text-slate-800 mb-4">Remediation Timeline</h2>
        <div id="gantt-container" class="overflow-x-auto"></div>
    </div>
    @endif

    {{-- ------------------------------------------------------------------ --}}
    {{-- Add Finding Modal (Strict 2-Column Navy Grid)                      --}}
    {{-- ------------------------------------------------------------------ --}}
    <div x-show="showAddModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/45 backdrop-blur-sm"
         @keydown.escape.window="showAddModal = false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto" @click.stop>
            <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between bg-slate-50 rounded-t-2xl">
                <h2 class="text-lg font-bold text-slate-800">Add New Audit Finding</h2>
                <button @click="showAddModal = false" class="text-slate-400 hover:text-slate-600 transition">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form action="{{ route('assessments.findings.store', $assessment) }}" method="POST" class="p-6 space-y-4">
                @csrf

                <table class="w-full border-collapse border border-slate-300 text-sm">
                    <tbody>
                        <!-- Row 1 -->
                        <tr>
                            <td class="w-[18%] bg-[#0a1e42] text-white font-bold px-4 py-3 border border-slate-300 align-middle">Serial No:</td>
                            <td class="w-[32%] bg-white px-3 py-2 border border-slate-300">
                                <input type="text" name="serial_no" placeholder="e.g. 4.1.1" required
                                       class="w-full border border-slate-300 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 transition">
                            </td>
                            <td class="w-[18%] bg-[#0a1e42] text-white font-bold px-4 py-3 border border-slate-300 align-middle">Status:</td>
                            <td class="w-[32%] bg-white px-3 py-2 border border-slate-300">
                                <select name="status" required
                                        class="w-full border border-slate-300 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 transition bg-white">
                                    <option value="Open" selected>Open</option>
                                    <option value="In Progress">In Progress</option>
                                    <option value="Closed">Closed</option>
                                </select>
                            </td>
                        </tr>
                        <!-- Row 2 -->
                        <tr>
                            <td class="bg-[#0a1e42] text-white font-bold px-4 py-3 border border-slate-300 align-middle">Observation Title:</td>
                            <td class="bg-white px-3 py-2 border border-slate-300">
                                <input type="text" name="observation_title" placeholder="Summary Observation text" required
                                       class="w-full border border-slate-300 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 transition">
                            </td>
                            <td class="bg-[#0a1e42] text-white font-bold px-4 py-3 border border-slate-300 align-middle">Risk Rating:</td>
                            <td class="bg-white px-3 py-2 border border-slate-300">
                                <select name="risk_rating" required
                                        class="w-full border border-slate-300 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-800 transition bg-white">
                                    <option value="High">High</option>
                                    <option value="Medium">Medium</option>
                                    <option value="Low">Low</option>
                                    <option value="None" selected>None</option>
                                </select>
                            </td>
                        </tr>
                        <!-- Row 3 -->
                        <tr>
                            <td class="bg-[#0a1e42] text-white font-bold px-4 py-3 border border-slate-300">Current State / Observation</td>
                            <td class="bg-white p-0 border border-slate-300" colspan="3">
                                <textarea name="current_state" class="hidden"></textarea>
                                <div class="quill-editor" data-field="current_state"></div>
                            </td>
                        </tr>
                        <!-- Row 4 -->
                        <tr>
                            <td class="bg-[#0a1e42] text-white font-bold px-4 py-3 border border-slate-300">Gap Description</td>
                            <td class="bg-white p-0 border border-slate-300" colspan="3">
                                <textarea name="gap_description" class="hidden"></textarea>
                                <div class="quill-editor" data-field="gap_description"></div>
                            </td>
                        </tr>
                        <!-- Row 5 -->
                        <tr>
                            <td class="bg-[#0a1e42] text-white font-bold px-4 py-3 border border-slate-300">Impact / Risk</td>
                            <td class="bg-white p-0 border border-slate-300" colspan="3">
                                <textarea name="impact_risk" class="hidden"></textarea>
                                <div class="quill-editor" data-field="impact_risk"></div>
                            </td>
                        </tr>
                        <!-- Row 6 -->
                        <tr>
                            <td class="bg-[#0a1e42] text-white font-bold px-4 py-3 border border-slate-300">Recommendation</td>
                            <td class="bg-white p-0 border border-slate-300" colspan="3">
                                <textarea name="recommendation" class="hidden"></textarea>
                                <div class="quill-editor" data-field="recommendation"></div>
                            </td>
                        </tr>
                        <!-- Row 7 -->
                        <tr>
                            <td class="bg-[#0a1e42] text-white font-bold px-4 py-3 border border-slate-300">Relevant Standard Reference</td>
                            <td class="bg-white p-0 border border-slate-300" colspan="3">
                                <textarea name="standard_reference" class="hidden"></textarea>
                                <div class="quill-editor" data-field="standard_reference"></div>
                            </td>
                        </tr>
                        <!-- Row 8 (Is Compliant Checkbox) -->
                        <tr>
                            <td class="bg-[#0a1e42] text-white font-bold px-4 py-3 border border-slate-300 align-middle">Is Compliant?</td>
                            <td class="bg-white px-4 py-3 border border-slate-300" colspan="3">
                                <div class="flex items-center gap-2">
                                    <input type="checkbox" name="is_compliant" value="1" id="new_compliant"
                                           class="w-4 h-4 text-indigo-600 border-slate-300 rounded focus:ring-indigo-500">
                                    <label for="new_compliant" class="text-sm font-semibold text-slate-700">
                                        Mark control as Compliant
                                    </label>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                    <button type="button" @click="showAddModal = false"
                            class="px-4 py-2 text-sm font-semibold text-slate-600 bg-slate-100 rounded-xl hover:bg-slate-200 transition">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-5 py-2 text-sm font-semibold text-white bg-gradient-to-r from-sky-500 to-indigo-500 rounded-xl hover:shadow-lg hover:shadow-indigo-500/25 transition">
                        <i class="fas fa-check mr-1.5 text-xs"></i> Create Finding
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Clone Modal --}}
    @if($type === 'Gap')
    <div x-show="showCloneModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/45 backdrop-blur-sm"
         @keydown.escape.window="showCloneModal = false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md" @click.stop>
            <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between bg-slate-50 rounded-t-2xl">
                <h2 class="text-lg font-bold text-slate-800">Clone to Final Assessment</h2>
                <button @click="showCloneModal = false" class="text-slate-400 hover:text-slate-600 transition">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form action="{{ route('assessments.clone', $project) }}" method="POST" class="px-6 py-5 space-y-4">
                @csrf
                <input type="hidden" name="source_id" value="{{ $assessment->id }}">
                <p class="text-sm text-slate-500 leading-relaxed">
                    This will duplicate all <strong>{{ $stats['total'] }}</strong> findings into a new Final Assessment.
                    Please define the audit timeline for the final phase.
                </p>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Start Date <span class="text-red-500">*</span></label>
                        <input type="date" name="start_date" required
                               class="w-full border border-slate-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 transition">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">End Date <span class="text-red-500">*</span></label>
                        <input type="date" name="end_date" required
                               class="w-full border border-slate-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 transition">
                    </div>
                </div>
                <div class="flex justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" @click="showCloneModal = false"
                            class="px-4 py-2 text-sm font-semibold text-slate-600 bg-slate-100 rounded-xl hover:bg-slate-200 transition">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-5 py-2 text-sm font-semibold text-white bg-sky-600 hover:bg-sky-700 rounded-xl transition">
                        <i class="fas fa-copy mr-1.5 text-xs"></i> Clone Now
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    @endif {{-- end if $assessment --}}

</div>
@endsection

@push('scripts')
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.quill-editor').forEach(function(editorEl) {
            initQuill(editorEl);
        });
    });

    function initQuill(editorEl) {
        const fieldName = editorEl.getAttribute('data-field');
        const form = editorEl.closest('form');
        const textarea = form.querySelector(`textarea[name="${fieldName}"]`);

        const quill = new Quill(editorEl, {
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
        if (textarea && textarea.value) {
            quill.root.innerHTML = textarea.value;
        }

        // Sync on change
        quill.on('text-change', function() {
            if (textarea) {
                textarea.value = quill.root.innerHTML;
            }
        });
    }

    function deleteFinding(id) {
        if (confirm('Are you sure you want to delete this finding? This will also remove any linked final cloned finding.')) {
            fetch(`/assessments/findings/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                }
            });
        }
    }
</script>

@if($assessment && !$assessment->findings->isEmpty())
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/frappe-gantt@0.6.1/dist/frappe-gantt.min.css">
<script src="https://cdn.jsdelivr.net/npm/frappe-gantt@0.6.1/dist/frappe-gantt.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const tasks = @json(json_decode($ganttJson));
        if (tasks && tasks.length > 0) {
            new Gantt('#gantt-container', tasks, {
                view_mode: 'Week',
                date_format: 'YYYY-MM-DD',
                custom_popup_html: function(task) {
                    return `<div class="p-2 text-xs text-slate-800 bg-white border border-slate-200 shadow rounded font-medium"><strong>${task.name}</strong><br>Progress: ${task.progress}%</div>`;
                }
            });
        }
    });
</script>
@endif
@endpush
