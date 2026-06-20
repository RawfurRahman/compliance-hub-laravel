@extends('layouts.app')

@section('title', 'ISO 27001:2022 Gap Assessment – ' . $project->name)

@section('content')
<div class="space-y-6">

    {{-- ------------------------------------------------------------------ --}}
    {{-- Page Header                                                         --}}
    {{-- ------------------------------------------------------------------ --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">ISO 27001:2022 Gap Assessment</h1>
            <p class="mt-1 text-sm text-slate-500">
                Project: <span class="font-semibold text-indigo-600">{{ $project->name }}</span>
            </p>
        </div>
        <a href="{{ route('iso-gap.report', $project->id) }}"
           class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-700 transition shadow-sm shadow-indigo-200">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Generate PDF Report
        </a>
    </div>

    {{-- ------------------------------------------------------------------ --}}
    {{-- Summary Metric Cards                                                --}}
    {{-- ------------------------------------------------------------------ --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">

        {{-- Total --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-widest">Total Findings</p>
            <p class="mt-3 text-4xl font-extrabold text-slate-800">{{ $stats['total'] }}</p>
            <p class="mt-1 text-xs text-slate-400">across all risk levels</p>
        </div>

        {{-- High --}}
        <div class="bg-red-50 rounded-2xl border border-red-200 shadow-sm p-5">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold text-red-500 uppercase tracking-widest">High Risk</p>
                <span class="w-7 h-7 rounded-full bg-red-100 flex items-center justify-center">
                    <svg class="w-3.5 h-3.5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                </span>
            </div>
            <p class="mt-3 text-4xl font-extrabold text-red-700">{{ $stats['high_count'] }}</p>
            <p class="mt-1 text-sm font-semibold text-red-400">{{ $stats['high_pct'] }}% of total</p>
        </div>

        {{-- Medium --}}
        <div class="bg-amber-50 rounded-2xl border border-amber-200 shadow-sm p-5">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold text-amber-500 uppercase tracking-widest">Medium Risk</p>
                <span class="w-7 h-7 rounded-full bg-amber-100 flex items-center justify-center">
                    <svg class="w-3.5 h-3.5 text-amber-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                </span>
            </div>
            <p class="mt-3 text-4xl font-extrabold text-amber-700">{{ $stats['medium_count'] }}</p>
            <p class="mt-1 text-sm font-semibold text-amber-400">{{ $stats['medium_pct'] }}% of total</p>
        </div>

        {{-- Low --}}
        <div class="bg-emerald-50 rounded-2xl border border-emerald-200 shadow-sm p-5">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold text-emerald-500 uppercase tracking-widest">Low Risk</p>
                <span class="w-7 h-7 rounded-full bg-emerald-100 flex items-center justify-center">
                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                </span>
            </div>
            <p class="mt-3 text-4xl font-extrabold text-emerald-700">{{ $stats['low_count'] }}</p>
            <p class="mt-1 text-sm font-semibold text-emerald-400">{{ $stats['low_pct'] }}% of total</p>
        </div>
    </div>

    {{-- ------------------------------------------------------------------ --}}
    {{-- Excel Import                                                        --}}
    {{-- ------------------------------------------------------------------ --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
        <h2 class="text-sm font-bold text-slate-700 mb-4 flex items-center gap-2">
            <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
            </svg>
            Import from Excel
        </h2>
        <form action="{{ route('iso-gap.import', $project->id) }}" method="POST"
              enctype="multipart/form-data" class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
            @csrf
            <input type="file" name="file" accept=".xlsx,.xls,.csv" required
                   class="block text-sm text-slate-600
                          file:mr-4 file:py-2 file:px-4
                          file:rounded-xl file:border-0
                          file:text-sm file:font-semibold
                          file:bg-indigo-50 file:text-indigo-700
                          hover:file:bg-indigo-100 cursor-pointer">
            <button type="submit"
                    class="px-5 py-2 bg-slate-800 text-white text-sm font-semibold rounded-xl hover:bg-slate-700 transition">
                Import
            </button>
        </form>
        <p class="mt-3 text-xs text-slate-400">
            Expected columns: <em>Serial No., Status, Observation Title, Risk Rating,
            Current State / Observation, Gap Description, Impact / Risk, Recommendation,
            Relevant Standard Reference</em>
        </p>
    </div>

    {{-- ------------------------------------------------------------------ --}}
    {{-- Findings Table with Alpine.js Row Expansion                         --}}
    {{-- ------------------------------------------------------------------ --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-700">Detailed Findings</h2>
            <span class="text-xs text-slate-400">Click any row to expand details</span>
        </div>

        @if($findings->isEmpty())
            <div class="px-6 py-16 text-center">
                <svg class="mx-auto h-12 w-12 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="text-sm text-slate-400">No findings yet. Import an Excel file to get started.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-100">
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide w-24">Serial No.</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Clause Reference</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Observation Title</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide w-28">Risk Rating</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide w-28">Status</th>
                            <th class="px-4 py-3 w-10"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($findings as $finding)
                        <tr x-data="{ open: false, status: '{{ $finding->status }}', saving: false, saved: false }"
                            class="group">
                            <td colspan="6" class="p-0">

                                {{-- ---- Collapsed summary row ---- --}}
                                <div @click="open = !open"
                                     class="grid items-center cursor-pointer px-4 py-3 hover:bg-slate-50 transition"
                                     style="grid-template-columns: 6rem 1fr 2fr 7rem 7rem 2.5rem;">

                                    <span class="text-xs font-mono font-semibold text-slate-600">{{ $finding->serial_no }}</span>

                                    <span class="text-xs text-slate-500 truncate pr-4">{{ $finding->clause_reference }}</span>

                                    <span class="text-sm font-medium text-slate-800 truncate pr-4">{{ $finding->observation_title }}</span>

                                    {{-- Risk badge --}}
                                    <span>
                                        @if($finding->risk_rating === 'High')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-700">High</span>
                                        @elseif($finding->risk_rating === 'Medium')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-700">Medium</span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700">Low</span>
                                        @endif
                                    </span>

                                    {{-- Status badge (reactive) --}}
                                    <span>
                                        <span x-show="status === 'Open'"
                                              class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-700">Open</span>
                                        <span x-show="status === 'In Progress'"
                                              class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-700">In Progress</span>
                                        <span x-show="status === 'Closed'"
                                              class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700">Closed</span>
                                    </span>

                                    {{-- Chevron --}}
                                    <span class="flex justify-center text-slate-400">
                                        <svg x-show="!open" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                        <svg x-show="open" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                                        </svg>
                                    </span>
                                </div>

                                {{-- ---- Expanded detail panel ---- --}}
                                <div x-show="open"
                                     x-transition:enter="transition ease-out duration-150"
                                     x-transition:enter-start="opacity-0 -translate-y-1"
                                     x-transition:enter-end="opacity-100 translate-y-0"
                                     class="bg-indigo-50 border-t border-indigo-100 px-6 py-5">

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">

                                        <div class="space-y-1">
                                            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Current State / Observation</p>
                                            <p class="text-sm text-slate-700 leading-relaxed">{{ $finding->current_state }}</p>
                                        </div>

                                        <div class="space-y-1">
                                            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Gap Description</p>
                                            <p class="text-sm text-slate-700 leading-relaxed">{{ $finding->gap_description }}</p>
                                        </div>

                                        <div class="space-y-1">
                                            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Impact / Risk</p>
                                            <p class="text-sm text-slate-700 leading-relaxed">{{ $finding->impact_risk }}</p>
                                        </div>

                                        <div class="space-y-1">
                                            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Recommendation</p>
                                            <p class="text-sm text-slate-700 leading-relaxed">{{ $finding->recommendation }}</p>
                                        </div>
                                    </div>

                                    {{-- Status update --}}
                                    <div class="flex items-center gap-3 pt-4 border-t border-indigo-200">
                                        <label class="text-xs font-semibold text-slate-600 uppercase tracking-wide">Update Status:</label>

                                        <select
                                            x-model="status"
                                            @change="
                                                saving = true; saved = false;
                                                fetch('{{ route('iso-gap.update-status', $finding->id) }}', {
                                                    method: 'POST',
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
                                            class="text-sm border border-slate-300 rounded-lg px-3 py-1.5 bg-white
                                                   focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                                            <option value="Open">Open</option>
                                            <option value="In Progress">In Progress</option>
                                            <option value="Closed">Closed</option>
                                        </select>

                                        <span x-show="saving" class="text-xs text-slate-400 animate-pulse">Saving...</span>
                                        <span x-show="saved"
                                              x-transition
                                              class="text-xs font-semibold text-emerald-600 flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                            </svg>
                                            Saved
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

</div>
@endsection
