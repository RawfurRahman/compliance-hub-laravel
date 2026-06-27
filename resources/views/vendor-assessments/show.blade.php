{{-- resources/views/vendor-assessments/show.blade.php --}}
@extends('layouts.app')

@push('styles')
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .font-outfit { font-family: 'Outfit', sans-serif; }
        .va-score-low { background-color: #d1fae5; color: #065f46; }
        .va-score-medium { background-color: #fef3c7; color: #92400e; }
        .va-score-high { background-color: #fde68a; color: #92400e; }
        .va-score-critical { background-color: #fee2e2; color: #991b1b; }
        .va-badge-ref {
            display: inline-flex;
            align-items: center;
            padding: 1px 6px;
            font-size: 10px;
            font-weight: 700;
            border-radius: 4px;
            background-color: #e0e7ff;
            color: #4338ca;
            border: 1px solid #c7d2fe;
        }
    </style>
@endpush

@section('content')
<div x-data="vendorAssessmentDetail()" x-init="init()" class="font-outfit">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-6 gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Vendor Assessment</h1>
            <p class="text-sm text-slate-500 mt-1">
                {{ $vendor->vendor_name }} &mdash;
                <span class="font-semibold text-slate-700">{{ $assessment->assessment_type }}</span>
                &mdash; Assessed {{ $assessment->assessment_date?->format('M d, Y') ?? 'N/A' }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            @if ($assessment->ai_summary)
                <button @click="toggleSummary()"
                    class="inline-flex items-center px-4 py-2 text-xs font-bold uppercase tracking-wider rounded-xl text-white transition-all shadow-sm"
                    :class="showAiSummary ? 'bg-slate-600 hover:bg-slate-700' : 'bg-indigo-600 hover:bg-indigo-700'">
                    <i class="fas" :class="showAiSummary ? 'fa-eye-slash' : 'fa-robot'"></i>
                    <span class="ml-1.5" x-text="showAiSummary ? 'Hide AI Summary' : 'Show AI Summary'"></span>
                </button>
            @else
                <button @click="runAiAnalysis()" :disabled="loading"
                    class="inline-flex items-center px-4 py-2 text-xs font-bold uppercase tracking-wider rounded-xl bg-indigo-600 text-white hover:bg-indigo-700 transition-all shadow-sm disabled:opacity-50">
                    <i class="fas fa-robot mr-1.5"></i>
                    <span x-text="loading ? 'Analyzing...' : 'Run AI Analysis'"></span>
                </button>
            @endif
            <a href="{{ route('vendors.show', [$project, $vendor]) }}" class="inline-flex items-center px-4 py-2 text-xs font-bold uppercase tracking-wider rounded-xl bg-white text-slate-700 border border-slate-200 hover:bg-slate-50 transition shadow-sm">
                <i class="fas fa-arrow-left mr-1.5"></i> Back
            </a>
        </div>
    </div>

    {{-- AI Summary Card --}}
    <div x-show="showAiSummary" x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform -translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         class="mb-6 bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden">

        <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-2" style="background: #f8fafc;">
            <i class="fas fa-robot text-indigo-500"></i>
            <h2 class="text-sm font-extrabold text-slate-800 uppercase tracking-widest">AI Vendor Summary</h2>
        </div>

        <div class="p-6 space-y-6">
            {{-- Strengths --}}
            <template x-if="summary.strengths && summary.strengths.length > 0">
                <div>
                    <h3 class="text-xs font-black text-emerald-700 uppercase tracking-widest mb-3 flex items-center gap-1.5">
                        <i class="fas fa-check-circle"></i> Strengths
                    </h3>
                    <ul class="space-y-2">
                        <template x-for="(s, idx) in summary.strengths" :key="idx">
                            <li class="flex items-start gap-2 p-3 rounded-xl bg-emerald-50/40 border border-emerald-100/60">
                                <span class="text-emerald-600 mt-0.5 text-xs shrink-0"><i class="fas fa-plus-circle"></i></span>
                                <div>
                                    <p class="text-sm text-slate-700 font-medium" x-text="s.strength"></p>
                                    <div class="flex flex-wrap gap-1 mt-1.5" x-show="s.questions && s.questions.length > 0">
                                        <template x-for="q in s.questions" :key="q">
                                            <span class="va-badge-ref" x-text="q"></span>
                                        </template>
                                    </div>
                                </div>
                            </li>
                        </template>
                    </ul>
                </div>
            </template>

            {{-- Weaknesses --}}
            <template x-if="summary.weaknesses && summary.weaknesses.length > 0">
                <div>
                    <h3 class="text-xs font-black text-amber-700 uppercase tracking-widest mb-3 flex items-center gap-1.5">
                        <i class="fas fa-exclamation-triangle"></i> Weaknesses &amp; Gaps
                    </h3>
                    <ul class="space-y-2">
                        <template x-for="(w, idx) in summary.weaknesses" :key="idx">
                            <li class="flex items-start gap-2 p-3 rounded-xl bg-amber-50/40 border border-amber-100/60">
                                <span class="text-amber-500 mt-0.5 text-xs shrink-0"><i class="fas fa-exclamation-circle"></i></span>
                                <div>
                                    <p class="text-sm text-slate-700 font-medium" x-text="w.weakness"></p>
                                    <div class="flex flex-wrap gap-1 mt-1.5" x-show="w.questions && w.questions.length > 0">
                                        <template x-for="q in w.questions" :key="q">
                                            <span class="va-badge-ref" x-text="q"></span>
                                        </template>
                                    </div>
                                </div>
                            </li>
                        </template>
                    </ul>
                </div>
            </template>

            {{-- Disclaimer --}}
            <p class="text-[10px] text-slate-400 italic border-t border-slate-100 pt-4">AI-generated, please verify</p>
        </div>
    </div>

    {{-- Assessment Overview Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-4">
            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Status</span>
            <p class="text-lg font-extrabold text-slate-800 mt-1">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold border"
                      :class="statusBadgeClass('{{ $assessment->status }}')">
                    <span class="w-1.5 h-1.5 rounded-full" :class="statusDotClass('{{ $assessment->status }}')"></span>
                    {{ ucfirst($assessment->status) }}
                </span>
            </p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-4">
            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Overall Score</span>
            <p class="text-lg font-extrabold text-slate-800 mt-1">
                {{ $assessment->overall_score !== null ? $assessment->overall_score . '%' : 'N/A' }}
            </p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-4">
            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Risk Rating</span>
            <p class="text-lg font-extrabold mt-1">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold"
                      :class="riskBadgeClass('{{ $assessment->risk_rating ?? 'N/A' }}')">
                    {{ $assessment->risk_rating ?? 'N/A' }}
                </span>
            </p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-4">
            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Due Date</span>
            <p class="text-lg font-extrabold text-slate-800 mt-1">
                {{ $assessment->due_date?->format('M d, Y') ?? 'N/A' }}
                @if ($assessment->isOverdue)
                    <span class="text-[10px] text-rose-600 font-bold ml-1">OVERDUE</span>
                @endif
            </p>
        </div>
    </div>

    {{-- Questions & Answers --}}
    <div class="bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-2" style="background: #f8fafc;">
            <i class="fas fa-list-check text-sky-500"></i>
            <h2 class="text-sm font-extrabold text-slate-800 uppercase tracking-widest">Questionnaire Responses</h2>
            <span class="ml-auto text-xs font-bold text-slate-400">{{ $assessment->responses->count() }} questions</span>
        </div>

        <div class="divide-y divide-slate-100">
            @forelse ($assessment->responses as $i => $response)
                @php $qNum = 'Q' . ($i + 1); @endphp
                <div class="p-5 hover:bg-slate-50/50 transition-colors" x-data="{ flagging: false }">
                    <div class="flex items-start gap-3">
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-indigo-50 border border-indigo-100 text-indigo-700 text-xs font-extrabold shrink-0">
                            {{ $qNum }}
                        </span>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    @if ($response->section)
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ $response->section }}</span>
                                    @endif
                                    <p class="text-sm font-bold text-slate-800 mt-0.5">{{ $response->question_text }}</p>
                                </div>
                                <span class="shrink-0 text-xs font-bold text-slate-400">{{ $response->question_key }}</span>
                            </div>

                            <div class="mt-2">
                                @if ($response->response_text)
                                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-sm text-slate-700">
                                        <i class="fas fa-reply text-slate-400 text-[10px]"></i>
                                        {{ $response->response_text }}
                                    </div>
                                @else
                                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-amber-50 border border-amber-200 text-sm text-amber-700">
                                        <i class="fas fa-clock text-amber-500 text-[10px]"></i>
                                        Not answered
                                    </div>
                                @endif
                            </div>

                            {{-- AI Suggested Answer --}}
                            @if ($response->ai_suggested_answer)
                                <div class="mt-2.5 p-3 rounded-xl bg-amber-100/30 border border-amber-200/50">
                                    <span class="text-[10px] font-extrabold text-amber-700 uppercase tracking-widest flex items-center gap-1">
                                        <i class="fas fa-lightbulb"></i> AI Suggestion — Vendor Must Confirm
                                    </span>
                                    <p class="text-xs text-slate-600 mt-1 font-medium">{{ $response->ai_suggested_answer }}</p>
                                    <div class="mt-2 flex items-center gap-2">
                                        <button @click="flagForReview({{ $response->id }})" :disabled="flagging"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider rounded-lg transition"
                                            :class="{{ $response->needs_vendor_review ? 'true' : 'false' }}"
                                            style="{{ $response->needs_vendor_review ? 'background-color: #fef3c7; color: #92400e;' : 'background-color: #f1f5f9; color: #64748b;' }}">
                                            <i class="fas fa-flag"></i>
                                            <span>{{ $response->needs_vendor_review ? 'Flagged for Review' : 'Flag for Vendor Review' }}</span>
                                        </button>
                                    </div>
                                </div>
                            @endif

                            {{-- Score & Compliance --}}
                            <div class="flex flex-wrap items-center gap-3 mt-2.5">
                                @if ($response->score !== null && $response->max_score !== null)
                                    <span class="text-[11px] font-bold text-slate-500">
                                        Score: <span class="text-slate-800">{{ $response->score }}/{{ $response->max_score }}</span>
                                    </span>
                                @endif
                                @if ($response->is_compliant !== null)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold"
                                          style="background: {{ $response->is_compliant ? '#d1fae5' : '#fee2e2' }}; color: {{ $response->is_compliant ? '#065f46' : '#991b1b' }};">
                                        <i class="fas fa-{{ $response->is_compliant ? 'check-circle' : 'times-circle' }}"></i>
                                        {{ $response->is_compliant ? 'Compliant' : 'Non-Compliant' }}
                                    </span>
                                @endif
                                @if ($response->needs_vendor_review)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold"
                                          style="background: #fef3c7; color: #92400e;">
                                        <i class="fas fa-flag"></i> Needs Vendor Review
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-6 text-center text-sm text-slate-400">
                    <i class="fas fa-inbox text-2xl mb-2 block text-slate-300"></i>
                    No questionnaire responses yet.
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function vendorAssessmentDetail() {
    return {
        showAiSummary: {{ $assessment->ai_summary ? 'true' : 'false' }},
        loading: false,
        summary: @json($assessment->ai_summary ?? []),

        init() {},

        toggleSummary() {
            this.showAiSummary = !this.showAiSummary;
        },

        async runAiAnalysis() {
            this.loading = true;
            try {
                const res = await fetch('{{ route('vendors.assessments.run-ai', [$project, $vendor, $assessment]) }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json();
                if (data.data) {
                    this.summary = data.data;
                    this.showAiSummary = true;
                } else {
                    alert(data.message || 'AI analysis could not be completed.');
                }
            } catch (e) {
                alert('Network error while running AI analysis.');
            } finally {
                this.loading = false;
            }
        },

        async flagForReview(responseId) {
            const res = await fetch('{{ route('vendors.assessments.responses.flag', [$project, $vendor, $assessment, '__RESPONSE_ID__']) }}'.replace('__RESPONSE_ID__', responseId), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                },
            });
            if (res.ok) {
                location.reload();
            } else {
                alert('Failed to flag for review.');
            }
        },

        statusBadgeClass(status) {
            const map = { completed: 'bg-emerald-50 text-emerald-700 border-emerald-200/60', in_progress: 'bg-sky-50 text-sky-700 border-sky-200/60', pending: 'bg-slate-50 text-slate-500 border-slate-200/60', failed: 'bg-rose-50 text-rose-700 border-rose-200/60' };
            return map[status] || 'bg-slate-50 text-slate-500 border-slate-200/60';
        },

        statusDotClass(status) {
            const map = { completed: 'bg-emerald-500', in_progress: 'bg-sky-400', pending: 'bg-slate-300', failed: 'bg-rose-500' };
            return map[status] || 'bg-slate-300';
        },

        riskBadgeClass(rating) {
            const map = { Low: 'bg-emerald-50 text-emerald-700', Medium: 'bg-amber-50 text-amber-700', High: 'bg-orange-50 text-orange-700', Critical: 'bg-rose-50 text-rose-700' };
            return map[rating] || 'bg-slate-50 text-slate-500';
        },
    };
}
</script>
@endpush
