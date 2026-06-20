@extends('layouts.app')

@section('content')
<div class="space-y-6" x-data="assessmentDashboard()">

    {{-- ------------------------------------------------------------------ --}}
    {{-- Page Header                                                         --}}
    {{-- ------------------------------------------------------------------ --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <a href="{{ route('assessments.show', $project) }}"
               class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-indigo-600 transition mb-2">
                <i class="fas fa-arrow-left text-xs"></i> Change Assessment Type
            </a>
            <h1 class="text-2xl font-bold text-slate-800">
                {{ $type === 'gap' ? 'Gap Assessment' : 'Final Assessment' }}
            </h1>
            <p class="mt-1 text-sm text-slate-500">
                Project: <span class="font-semibold text-indigo-600">{{ $project->name }}</span>
                @if($assessment)
                    &bull;
                    <span class="font-semibold text-slate-600">
                        {{ $assessment->framework === 'iso_27001' ? 'ISO 27001:2022' : 'HITRUST CSF' }}
                    </span>
                    &bull;
                    <span class="text-slate-400">
                        {{ $assessment->start_date->format('d M Y') }} &ndash; {{ $assessment->end_date->format('d M Y') }}
                    </span>
                @endif
            </p>
        </div>

        @if($assessment)
        <div class="flex items-center gap-3">
            {{-- PDF Report --}}
            <a href="{{ route('assessments.report', $assessment) }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-800 text-white text-sm font-semibold rounded-xl hover:bg-slate-700 transition shadow-sm">
                <i class="fas fa-file-pdf"></i> PDF Report
            </a>

            {{-- Add Finding --}}
            <button @click="showAddModal = true"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-indigo-500 to-sky-500 text-white text-sm font-semibold rounded-xl hover:shadow-lg hover:shadow-indigo-500/25 transition">
                <i class="fas fa-plus text-xs"></i> Add Finding
            </button>
        </div>
        @endif
    </div>

    {{-- ------------------------------------------------------------------ --}}
    {{-- No Assessment Yet — Initialise Form                                 --}}
    {{-- ------------------------------------------------------------------ --}}
    @if(!$assessment)
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-10 text-center max-w-lg mx-auto">
        <div class="w-16 h-16 rounded-2xl bg-indigo-50 flex items-center justify-center mx-auto mb-5">
            <i class="fas fa-{{ $type === 'gap' ? 'search-plus' : 'clipboard-check' }} text-2xl text-indigo-500"></i>
        </div>
        <h2 class="text-lg font-bold text-slate-800 mb-2">
            No {{ $type === 'gap' ? 'Gap' : 'Final' }} Assessment Found
        </h2>
        <p class="text-sm text-slate-500 mb-6">
            Initialise a new {{ $type === 'gap' ? 'Gap' : 'Final' }} Assessment to start tracking findings.
        </p>
        <form action="{{ route('assessments.store', $project) }}" method="POST" class="space-y-4 text-left">
            @csrf
            <input type="hidden" name="type" value="{{ $type }}">
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Framework</label>
                <select name="framework" required class="w-full border border-slate-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                    <option value="" disabled selected>-- Select --</option>
                    <option value="iso_27001">ISO 27001:2022</option>
                    <option value="hitrust">HITRUST CSF</option>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Start Date</label>
                    <input type="date" name="start_date" required class="w-full border border-slate-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">End Date</label>
                    <input type="date" name="end_date" required class="w-full border border-slate-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                </div>
            </div>
            <button type="submit" class="w-full px-5 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-700 transition">
                <i class="fas fa-check mr-1.5 text-xs"></i> Initialise Assessment
            </button>
        </form>
    </div>
    @endif

    {{-- ------------------------------------------------------------------ --}}
    {{-- Stats Cards                                                         --}}
    {{-- ------------------------------------------------------------------ --}}
    @if($assessment)
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-widest">Total Findings</p>
            <p class="mt-3 text-4xl font-extrabold text-slate-800">{{ $stats['total'] }}</p>
            <p class="mt-1 text-xs text-slate-400">across all controls</p>
        </div>

        <div class="bg-emerald-50 rounded-2xl border border-emerald-200 shadow-sm p-5">
            <p class="text-xs font-semibold text-emerald-500 uppercase tracking-widest">Compliance</p>
            <p class="mt-3 text-4xl font-extrabold text-emerald-700">{{ $stats['compliancePct'] }}%</p>
            <p class="mt-1 text-xs text-emerald-400">compliant + partial</p>
        </div>

        <div class="bg-red-50 rounded-2xl border border-red-200 shadow-sm p-5">
            <p class="text-xs font-semibold text-red-500 uppercase tracking-widest">High Risk</p>
            <p class="mt-3 text-4xl font-extrabold text-red-700">{{ $stats['high'] }}</p>
            <p class="mt-1 text-xs text-red-400">require immediate action</p>
        </div>

        <div class="bg-amber-50 rounded-2xl border border-amber-200 shadow-sm p-5">
            <p class="text-xs font-semibold text-amber-500 uppercase tracking-widest">Open Findings</p>
            <p class="mt-3 text-4xl font-extrabold text-amber-700">{{ $stats['open'] }}</p>
            <p class="mt-1 text-xs text-amber-400">pending remediation</p>
        </div>
    </div>

    {{-- Compliance Progress Bar --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-bold text-slate-700">Overall Compliance Score</h3>
            <span class="text-sm font-bold text-indigo-600">{{ $stats['compliancePct'] }}%</span>
        </div>
        <div class="w-full bg-slate-100 rounded-full h-3">
            <div class="bg-gradient-to-r from-indigo-500 to-emerald-500 h-3 rounded-full transition-all"
                 style="width: {{ $stats['compliancePct'] }}%"></div>
        </div>
        <div class="flex justify-between mt-2 text-xs text-slate-400">
            <span>Compliant: {{ $stats['compliant'] }}</span>
            <span>Partial: {{ $stats['partial'] }}</span>
            <span>Non-Compliant: {{ $stats['nonCompliant'] }}</span>
            <span>N/A: {{ $stats['na'] }}</span>
        </div>
    </div>

    {{-- ------------------------------------------------------------------ --}}
    {{-- Clone to Final (Gap only)                                           --}}
    {{-- ------------------------------------------------------------------ --}}
    @if($type === 'gap')
    <div class="bg-sky-50 border border-sky-200 rounded-2xl p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h3 class="text-sm font-bold text-sky-800">Ready for Final Assessment?</h3>
                <p class="text-xs text-sky-600 mt-1">Clone all findings from this Gap Assessment into a new Final Assessment.</p>
            </div>
            <button @click="showCloneModal = true"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-sky-600 text-white text-sm font-semibold rounded-xl hover:bg-sky-700 transition whitespace-nowrap">
                <i class="fas fa-copy text-xs"></i> Clone to Final Assessment
            </button>
        </div>
    </div>
    @endif

    {{-- ------------------------------------------------------------------ --}}
    {{-- Findings Table                                                      --}}
    {{-- ------------------------------------------------------------------ --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-700">Findings
                <span class="ml-2 text-xs font-normal text-slate-400">({{ $stats['total'] }} total)</span>
            </h2>
            <span class="text-xs text-slate-400">Click any row to expand details</span>
        </div>

        @if($assessment->findings->isEmpty())
        <div class="px-6 py-16 text-center">
            <i class="fas fa-clipboard-list text-4xl text-slate-200 mb-3"></i>
            <p class="text-sm text-slate-400">No findings yet. Click <strong>Add Finding</strong> to get started.</p>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100">
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide w-20">Serial</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Clause</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Observation</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide w-36">Compliance</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide w-24">Risk</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide w-28">Status</th>
                        <th class="px-4 py-3 w-10"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($assessment->findings as $finding)
                    <tr x-data="{
                            open: false,
                            status: '{{ $finding->status }}',
                            saving: false, saved: false
                        }" class="group">
                        <td colspan="7" class="p-0">

                            {{-- Collapsed row --}}
                            <div @click="open = !open"
                                 class="grid items-center cursor-pointer px-4 py-3 hover:bg-slate-50 transition"
                                 style="grid-template-columns: 5rem 1fr 2fr 9rem 6rem 7rem 2.5rem;">

                                <span class="text-xs font-mono font-semibold text-slate-600">{{ $finding->serial_no }}</span>
                                <span class="text-xs text-slate-500 truncate pr-3">{{ $finding->clause_reference }}</span>
                                <span class="text-sm font-medium text-slate-800 truncate pr-3">{{ $finding->observation_title }}</span>

                                {{-- Compliance badge --}}
                                <span>
                                    @if($finding->compliance_status === 'Compliant')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700">Compliant</span>
                                    @elseif($finding->compliance_status === 'Partially Compliant')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-700">Partial</span>
                                    @elseif($finding->compliance_status === 'Non-Compliant')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-700">Non-Compliant</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-600">N/A</span>
                                    @endif
                                </span>

                                {{-- Risk badge --}}
                                <span>
                                    @if($finding->risk_rating === 'High')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-700">High</span>
                                    @elseif($finding->risk_rating === 'Medium')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-700">Medium</span>
                                    @elseif($finding->risk_rating === 'Low')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700">Low</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-600">None</span>
                                    @endif
                                </span>

                                {{-- Status badge (reactive) --}}
                                <span>
                                    <span x-show="status === 'Open'" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-700">Open</span>
                                    <span x-show="status === 'In Progress'" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-700">In Progress</span>
                                    <span x-show="status === 'Closed'" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700">Closed</span>
                                </span>

                                {{-- Chevron --}}
                                <span class="flex justify-center text-slate-400">
                                    <i x-show="!open" class="fas fa-chevron-down text-xs"></i>
                                    <i x-show="open" class="fas fa-chevron-up text-xs"></i>
                                </span>
                            </div>

                            {{-- Expanded detail panel --}}
                            <div x-show="open"
                                 x-transition:enter="transition ease-out duration-150"
                                 x-transition:enter-start="opacity-0 -translate-y-1"
                                 x-transition:enter-end="opacity-100 translate-y-0"
                                 class="bg-indigo-50 border-t border-indigo-100 px-6 py-5">

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                                    <div>
                                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Current State</p>
                                        <p class="text-sm text-slate-700 leading-relaxed">{{ $finding->current_state ?: '—' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Gap Description</p>
                                        <p class="text-sm text-slate-700 leading-relaxed">{{ $finding->gap_description ?: '—' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Impact / Risk</p>
                                        <p class="text-sm text-slate-700 leading-relaxed">{{ $finding->impact_risk ?: '—' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Recommendation</p>
                                        <p class="text-sm text-slate-700 leading-relaxed">{{ $finding->recommendation ?: '—' }}</p>
                                    </div>
                                </div>

                                {{-- Inline status update --}}
                                <div class="flex items-center gap-3 pt-4 border-t border-indigo-200">
                                    <label class="text-xs font-semibold text-slate-600 uppercase tracking-wide">Update Status:</label>
                                    <select x-model="status"
                                            @change="
                                                saving = true; saved = false;
                                                fetch('{{ route('assessments.findings.update', $finding) }}', {
                                                    method: 'PUT',
                                                    headers: {
                                                        'Content-Type': 'application/json',
                                                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                                                    },
                                                    body: JSON.stringify({ status: status })
                                                })
                                                .then(r => r.json())
                                                .then(() => { saving = false; saved = true; setTimeout(() => saved = false, 2500); })
                                                .catch(() => { saving = false; })
                                            "
                                            class="text-sm border border-slate-300 rounded-lg px-3 py-1.5 bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                                        <option value="Open">Open</option>
                                        <option value="In Progress">In Progress</option>
                                        <option value="Closed">Closed</option>
                                    </select>
                                    <span x-show="saving" class="text-xs text-slate-400 animate-pulse">Saving...</span>
                                    <span x-show="saved" x-transition class="text-xs font-semibold text-emerald-600 flex items-center gap-1">
                                        <i class="fas fa-check text-xs"></i> Saved
                                    </span>
                                </div>
                            </div>

                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- ------------------------------------------------------------------ --}}
    {{-- Gantt Chart                                                         --}}
    {{-- ------------------------------------------------------------------ --}}
    @if($assessment && !$assessment->findings->isEmpty())
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
        <h2 class="text-sm font-bold text-slate-700 mb-4">Remediation Gantt Chart</h2>
        <div id="gantt-container" class="overflow-x-auto"></div>
    </div>
    @endif

    {{-- ------------------------------------------------------------------ --}}
    {{-- Add Finding Modal                                                   --}}
    {{-- ------------------------------------------------------------------ --}}
    <div x-show="showAddModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40"
         @keydown.escape.window="showAddModal = false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto"
             @click.stop>
            <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                <h2 class="text-lg font-bold text-slate-800">Add New Finding</h2>
                <button @click="showAddModal = false" class="text-slate-400 hover:text-slate-600 transition">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form action="{{ route('assessments.findings.store', $assessment) }}" method="POST" class="px-6 py-5 space-y-4">
                @csrf
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Serial No. <span class="text-red-500">*</span></label>
                        <input type="text" name="serial_no" required maxlength="50"
                               class="w-full border border-slate-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Clause Reference <span class="text-red-500">*</span></label>
                        <input type="text" name="clause_reference" required maxlength="255"
                               class="w-full border border-slate-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Observation Title <span class="text-red-500">*</span></label>
                    <input type="text" name="observation_title" required maxlength="255"
                           class="w-full border border-slate-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Compliance Status <span class="text-red-500">*</span></label>
                        <select name="compliance_status" required class="w-full border border-slate-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                            <option value="Compliant">Compliant</option>
                            <option value="Partially Compliant">Partially Compliant</option>
                            <option value="Non-Compliant" selected>Non-Compliant</option>
                            <option value="Not Applicable">Not Applicable</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Risk Rating <span class="text-red-500">*</span></label>
                        <select name="risk_rating" required class="w-full border border-slate-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                            <option value="High">High</option>
                            <option value="Medium" selected>Medium</option>
                            <option value="Low">Low</option>
                            <option value="None">None</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Status <span class="text-red-500">*</span></label>
                        <select name="status" required class="w-full border border-slate-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                            <option value="Open" selected>Open</option>
                            <option value="In Progress">In Progress</option>
                            <option value="Closed">Closed</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Current State / Observation</label>
                    <textarea name="current_state" rows="2"
                              class="w-full border border-slate-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Gap Description</label>
                    <textarea name="gap_description" rows="2"
                              class="w-full border border-slate-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Impact / Risk</label>
                    <textarea name="impact_risk" rows="2"
                              class="w-full border border-slate-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Recommendation</label>
                    <textarea name="recommendation" rows="2"
                              class="w-full border border-slate-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"></textarea>
                </div>
                <div class="flex justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" @click="showAddModal = false"
                            class="px-4 py-2 text-sm font-semibold text-slate-600 bg-slate-100 rounded-xl hover:bg-slate-200 transition">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-5 py-2 text-sm font-semibold text-white bg-gradient-to-r from-indigo-500 to-sky-500 rounded-xl hover:shadow-lg hover:shadow-indigo-500/25 transition">
                        <i class="fas fa-plus mr-1.5 text-xs"></i> Add Finding
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ------------------------------------------------------------------ --}}
    {{-- Clone to Final Modal                                                --}}
    {{-- ------------------------------------------------------------------ --}}
    @if($type === 'gap' && $assessment)
    <div x-show="showCloneModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40"
         @keydown.escape.window="showCloneModal = false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md" @click.stop>
            <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                <h2 class="text-lg font-bold text-slate-800">Clone to Final Assessment</h2>
                <button @click="showCloneModal = false" class="text-slate-400 hover:text-slate-600 transition">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form action="{{ route('assessments.clone', $project) }}" method="POST" class="px-6 py-5 space-y-4">
                @csrf
                <input type="hidden" name="source_id" value="{{ $assessment->id }}">
                <p class="text-sm text-slate-500">
                    This will deep-copy all <strong>{{ $stats['total'] }}</strong> findings into a new Final Assessment.
                    Set the date range for the final audit period.
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
                            class="px-5 py-2 text-sm font-semibold text-white bg-sky-600 rounded-xl hover:bg-sky-700 transition">
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
                    return `<div class="p-2 text-xs"><strong>${task.name}</strong><br>Progress: ${task.progress}%</div>`;
                }
            });
        }
    });
</script>
@endif

<script>
    function assessmentDashboard() {
        return {
            showAddModal: false,
            showCloneModal: false,
        };
    }
</script>
@endpush
