@extends('layouts.app')

@section('content')
<div class="fade-in-up max-w-5xl mx-auto">
    {{-- Tab Navigation --}}
    <div class="flex gap-4 mb-6 border-b border-slate-200">
        <a href="{{ route('admin.trust-centers.overview', $trustCenter) }}"
           class="pb-3 text-sm font-semibold text-sky-600 border-b-2 border-sky-600">
           Overview
        </a>
        <a href="{{ route('admin.trust-centers.edit', $trustCenter) }}"
           class="pb-3 text-sm font-semibold text-slate-500 hover:text-slate-700">
           Settings
        </a>
        <a href="{{ route('admin.trust-centers.requests', $trustCenter) }}"
           class="pb-3 text-sm font-semibold text-slate-500 hover:text-slate-700">
           Requests
        </a>
        <a href="{{ route('admin.trust-centers.questionnaires', $trustCenter) }}"
           class="pb-3 text-sm font-semibold text-slate-500 hover:text-slate-700">
           Questionnaires
        </a>
    </div>

    <h2 class="text-xl font-bold text-slate-900 mb-6">{{ $trustCenter->headline }} — Overview</h2>

    {{-- Date Range Filter --}}
    <div class="glass-card rounded-2xl p-4 mb-6">
        <form method="GET" class="flex items-end gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">From</label>
                <input type="date" name="from" value="{{ $from }}"
                       class="px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">To</label>
                <input type="date" name="to" value="{{ $to }}"
                       class="px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none">
            </div>
            <button type="submit"
                    class="px-4 py-2 text-sm font-semibold text-white bg-sky-500 hover:bg-sky-600 rounded-lg transition-colors">
                <i class="fas fa-filter mr-1"></i> Filter
            </button>
        </form>
    </div>

    {{-- Stat Cards --}}
    <div class="grid grid-cols-3 gap-6 mb-8">
        <div class="glass-card rounded-2xl p-6">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-sky-50 flex items-center justify-center text-sky-600">
                    <i class="fas fa-chart-simple text-lg"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900">{{ $chartData->sum('count') }}</p>
                    <p class="text-sm text-slate-500">Visits{{ $from !== $to ? " ($from to $to)" : '' }}</p>
                </div>
            </div>
        </div>

        <a href="{{ route('admin.trust-centers.requests', $trustCenter) }}" class="block">
            <div class="glass-card rounded-2xl p-6 hover:shadow-md transition-shadow">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-amber-50 flex items-center justify-center text-amber-600">
                        <i class="fas fa-inbox text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-slate-900">{{ $pendingRequests }}</p>
                        <p class="text-sm text-slate-500">Pending Requests</p>
                    </div>
                </div>
            </div>
        </a>

        <a href="{{ route('admin.trust-centers.questionnaires', $trustCenter) }}" class="block">
            <div class="glass-card rounded-2xl p-6 hover:shadow-md transition-shadow">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600">
                        <i class="fas fa-clipboard-list text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-slate-900">{{ $unrespondedQuestionnaires }}</p>
                        <p class="text-sm text-slate-500">Active Questionnaires</p>
                    </div>
                </div>
            </div>
        </a>
    </div>

    {{-- Chart --}}
    <div class="glass-card rounded-2xl p-6">
        <h3 class="text-sm font-semibold text-slate-700 mb-4">Visits per Day</h3>
        @if($chartData->isEmpty())
            <div class="text-center py-16">
                <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-chart-line text-slate-400 text-xl"></i>
                </div>
                <p class="text-sm text-slate-500">No visit data for the selected range.</p>
            </div>
        @else
            <canvas id="visitsChart" height="280"></canvas>
        @endif
    </div>

    {{-- Quick Links --}}
    <div class="mt-6 text-center">
        <a href="{{ route('trust-center.public.show', $trustCenter->public_slug) }}"
           target="_blank"
           class="text-sm text-sky-600 hover:text-sky-700 font-semibold">
            <i class="fas fa-external-link-alt mr-1"></i> View Public Page
        </a>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('visitsChart'), {
    type: 'line',
    data: {
        labels: @json($chartData->pluck('date')),
        datasets: [{
            label: 'Visits',
            data: @json($chartData->pluck('count')),
            borderColor: '#2563eb',
            backgroundColor: 'rgba(37, 99, 235, 0.1)',
            fill: true,
            tension: 0.3,
            pointRadius: 4,
            pointBackgroundColor: '#2563eb',
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#0f172a',
                titleColor: '#fff',
                bodyColor: '#94a3b8',
                cornerRadius: 8,
                padding: 10,
            }
        },
        scales: {
            x: {
                grid: { display: false },
                ticks: { color: '#94a3b8', font: { size: 11 } }
            },
            y: {
                beginAtZero: true,
                ticks: { precision: 0, color: '#94a3b8', font: { size: 11 } },
                grid: { color: '#f1f5f9' }
            }
        }
    }
});
</script>
@endpush
