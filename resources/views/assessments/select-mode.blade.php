@extends('layouts.app')

@section('content')
<div class="space-y-6">

    {{-- Page Header --}}
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('projects.show', $project) }}"
               class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-indigo-600 transition mb-2">
                <i class="fas fa-arrow-left text-xs"></i> Back to Project Hub
            </a>
            <h1 class="text-2xl font-bold text-slate-800">Unified Assessment Module</h1>
            <p class="mt-1 text-sm text-slate-500">
                Project: <span class="font-semibold text-indigo-600">{{ $project->name }}</span>
            </p>
        </div>
    </div>

    {{-- Mode Selection --}}
    <div class="text-center mb-2">
        <p class="text-slate-500 text-sm">Select the type of assessment you want to run for this project.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-4xl mx-auto">

        {{-- Gap Assessment Card --}}
        <div x-data="{ open: false }" class="bg-white rounded-2xl border border-slate-200 shadow-sm hover:shadow-md hover:border-indigo-200 transition-all p-8 flex flex-col items-center text-center">
            <div class="w-16 h-16 rounded-2xl bg-indigo-50 flex items-center justify-center mb-5">
                <i class="fas fa-search-plus text-2xl text-indigo-500"></i>
            </div>
            <h2 class="text-xl font-bold text-slate-800 mb-2">Gap Assessment</h2>
            <p class="text-sm text-slate-500 mb-6 leading-relaxed">
                Identify gaps between your current security posture and the target framework.
                Capture findings, risk ratings, and recommendations.
            </p>

            <button @click="open = !open"
                    class="w-full px-5 py-2.5 bg-gradient-to-r from-indigo-500 to-sky-500 text-white text-sm font-semibold rounded-xl hover:shadow-lg hover:shadow-indigo-500/25 transition-all">
                <i class="fas fa-play mr-1.5 text-xs"></i> Start Gap Assessment
            </button>

            {{-- Inline form --}}
            <div x-show="open" x-transition class="w-full mt-5 text-left space-y-4">
                <form action="{{ route('assessments.store', $project) }}" method="POST" class="space-y-4">
                    @csrf
                    <input type="hidden" name="type" value="gap">

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
                            <input type="date" name="start_date" required
                                   class="w-full border border-slate-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">End Date</label>
                            <input type="date" name="end_date" required
                                   class="w-full border border-slate-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                        </div>
                    </div>

                    <button type="submit"
                            class="w-full px-5 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-700 transition">
                        <i class="fas fa-check mr-1.5 text-xs"></i> Initialise Gap Assessment
                    </button>
                </form>
            </div>
        </div>

        {{-- Final Assessment Card --}}
        <div x-data="{ open: false }" class="bg-white rounded-2xl border border-slate-200 shadow-sm hover:shadow-md hover:border-sky-200 transition-all p-8 flex flex-col items-center text-center">
            <div class="w-16 h-16 rounded-2xl bg-sky-50 flex items-center justify-center mb-5">
                <i class="fas fa-clipboard-check text-2xl text-sky-500"></i>
            </div>
            <h2 class="text-xl font-bold text-slate-800 mb-2">Final Assessment</h2>
            <p class="text-sm text-slate-500 mb-6 leading-relaxed">
                Conduct the formal final audit. Clone findings from a completed Gap Assessment
                or start fresh with a new set of audit findings.
            </p>

            <button @click="open = !open"
                    class="w-full px-5 py-2.5 bg-gradient-to-r from-sky-500 to-cyan-500 text-white text-sm font-semibold rounded-xl hover:shadow-lg hover:shadow-sky-500/25 transition-all">
                <i class="fas fa-play mr-1.5 text-xs"></i> Start Final Assessment
            </button>

            {{-- Inline form --}}
            <div x-show="open" x-transition class="w-full mt-5 text-left space-y-4">
                <form action="{{ route('assessments.store', $project) }}" method="POST" class="space-y-4">
                    @csrf
                    <input type="hidden" name="type" value="final">

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Framework</label>
                        <select name="framework" required class="w-full border border-slate-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 transition">
                            <option value="" disabled selected>-- Select --</option>
                            <option value="iso_27001">ISO 27001:2022</option>
                            <option value="hitrust">HITRUST CSF</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Start Date</label>
                            <input type="date" name="start_date" required
                                   class="w-full border border-slate-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 transition">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">End Date</label>
                            <input type="date" name="end_date" required
                                   class="w-full border border-slate-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 transition">
                        </div>
                    </div>

                    <button type="submit"
                            class="w-full px-5 py-2.5 bg-sky-600 text-white text-sm font-semibold rounded-xl hover:bg-sky-700 transition">
                        <i class="fas fa-check mr-1.5 text-xs"></i> Initialise Final Assessment
                    </button>
                </form>
            </div>
        </div>

    </div>

    {{-- Info note --}}
    <div class="max-w-4xl mx-auto">
        <div class="bg-amber-50 border border-amber-200 rounded-xl px-5 py-4 flex items-start gap-3">
            <i class="fas fa-lightbulb text-amber-500 mt-0.5"></i>
            <p class="text-sm text-amber-800">
                <strong>Tip:</strong> Run a <em>Gap Assessment</em> first to identify control gaps,
                then clone it into a <em>Final Assessment</em> to track remediation progress and
                generate your formal audit report.
            </p>
        </div>
    </div>

</div>
@endsection
