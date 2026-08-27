{{-- resources/views/dashboard.blade.php --}}

@extends('layouts.app')

@section('content')
    <div class="-mt-8">
        {{-- Page Header --}}
    <div class="flex flex-col md:flex-row md:items-end md:justify-between mb-8 fade-in-up">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">
                Enterprise <span class="gradient-text">Dashboard</span>
            </h1>
            <p class="mt-1.5 text-sm text-slate-500 font-medium">Enterprise compliance and risk management dashboard</p>
        </div>
        <div class="mt-3 md:mt-0">
            <span class="badge badge-emerald">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5 pulse-dot"></span>
                System Online
            </span>
        </div>
    </div>

    {{-- Welcome Banner --}}
    <div class="welcome-banner mb-8 fade-in-up" style="animation-delay: 0.08s;">
        <div class="relative z-10 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-white mb-1.5">Welcome back, <span class="text-sky-300">{{ auth()->user()->username }}</span></h2>
                <div class="flex items-center gap-3 flex-wrap">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-white/10 text-white/80 text-xs font-semibold uppercase tracking-widest border border-white/10">
                        <i class="fas fa-id-badge mr-1.5 text-sky-400 text-[10px]"></i>
                        {{ auth()->user()->roles->first()->name ?? 'Not Assigned' }}
                    </span>
                    <span class="text-slate-400 text-xs">System access is authorized and active</span>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-[10px] text-slate-500 uppercase tracking-wider font-semibold">{{ now()->format('l, M d, Y') }}</span>
            </div>
        </div>
    </div>

    {{-- Stats Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-10 stagger-children">

        @if(!auth()->user()->hasRole('Admin'))
        {{-- Assessment Progress --}}
        <div class="glass-card stat-card-emerald p-5 rounded-2xl border border-white/50 hover:border-emerald-200 transition-all group overflow-hidden relative cursor-default">
            <div class="absolute -right-3 -bottom-3 text-emerald-500/[0.04] group-hover:text-emerald-500/[0.08] transition-colors pointer-events-none">
                <i class="fas fa-clipboard-check text-6xl transform -rotate-12"></i>
            </div>
            <div class="flex items-center justify-between mb-4 relative z-10">
                <h3 class="text-[11px] font-bold text-slate-400 uppercase tracking-widest">Assessment</h3>
                <div class="icon-badge icon-badge-emerald">
                    <i class="fas fa-clipboard-check"></i>
                </div>
            </div>
            <div class="space-y-2 relative z-10">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500">Completed</span>
                    <span class="text-lg font-bold text-emerald-600">{{ $stats['completed_requirements'] }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500">Pending</span>
                    <span class="text-lg font-bold text-amber-500">{{ $stats['pending_requirements'] }}</span>
                </div>
            </div>
        </div>

        {{-- Upcoming Meetings --}}
        <div class="glass-card stat-card-indigo p-5 rounded-2xl border border-white/50 hover:border-indigo-200 transition-all group overflow-hidden relative cursor-default">
            <div class="absolute -right-3 -bottom-3 text-indigo-500/[0.04] group-hover:text-indigo-500/[0.08] transition-colors pointer-events-none">
                <i class="fas fa-calendar-alt text-6xl transform -rotate-12"></i>
            </div>
            <div class="flex items-center justify-between mb-4 relative z-10">
                <h3 class="text-[11px] font-bold text-slate-400 uppercase tracking-widest">Meetings</h3>
                <div class="icon-badge icon-badge-indigo">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
            <p class="text-3xl font-extrabold text-slate-900 leading-none relative z-10">{{ $stats['meetings'] }}</p>
            <div class="mt-3 pt-3 border-t border-slate-100/60 relative z-10">
                <p class="text-[11px] text-slate-500 font-medium">Scheduled verifications</p>
            </div>
        </div>

        {{-- Notifications --}}
        <div class="glass-card stat-card-rose p-5 rounded-2xl border border-white/50 hover:border-rose-200 transition-all group overflow-hidden relative cursor-default">
            <div class="absolute -right-3 -bottom-3 text-rose-500/[0.04] group-hover:text-rose-500/[0.08] transition-colors pointer-events-none">
                <i class="fas fa-bell text-6xl transform -rotate-12"></i>
            </div>
            <div class="flex items-center justify-between mb-4 relative z-10">
                <h3 class="text-[11px] font-bold text-slate-400 uppercase tracking-widest">Notifications</h3>
                <div class="icon-badge icon-badge-rose">
                    <i class="fas fa-comment-dots"></i>
                </div>
            </div>
            <p class="text-3xl font-extrabold text-slate-900 leading-none relative z-10">0</p>
            <div class="mt-3 pt-3 border-t border-slate-100/60 relative z-10">
                <p class="text-[11px] text-slate-500 font-medium italic">No unread feedback</p>
            </div>
        </div>
        @endif
    </div>

    {{-- Quick Navigation --}}
    <div class="section-label">Quick Navigation</div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 stagger-children">

        {{-- Project Portfolio --}}
        <a href="{{ route('projects.index') }}" class="glass-card quick-nav-card block p-4 rounded-2xl border border-white/50 group">
            <div class="flex items-center">
                <div class="icon-badge icon-badge-sky w-12 h-12 rounded-xl shadow-md group-hover:shadow-lg group-hover:shadow-sky-500/20 transition-all">
                    <i class="fas fa-folder-open text-lg"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-bold text-slate-800 group-hover:text-sky-600 transition-colors">Project Portfolio</p>
                    <p class="text-[11px] font-medium text-slate-400 mt-0.5">Manage compliance audits</p>
                </div>
                <div class="ml-auto opacity-0 group-hover:opacity-100 transition-opacity">
                    <i class="fas fa-arrow-right text-sky-400 text-sm"></i>
                </div>
            </div>
        </a>
        {{-- Workflow Automation (n8n) --}}
        <a href="http://localhost:5678" target="_blank" class="glass-card quick-nav-card block p-4 rounded-2xl border border-white/50 group">
            <div class="flex items-center">
                <div class="icon-badge icon-badge-rose w-12 h-12 rounded-xl shadow-md group-hover:shadow-lg group-hover:shadow-rose-500/20 transition-all">
                    <i class="fas fa-diagram-project text-lg"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-bold text-slate-800 group-hover:text-rose-600 transition-colors">Workflow Automation (n8n)</p>
                    <p class="text-[11px] font-medium text-slate-400 mt-0.5">Watch evidence → scan → AI flows live</p>
                </div>
                <div class="ml-auto opacity-0 group-hover:opacity-100 transition-opacity">
                    <i class="fas fa-arrow-right text-rose-400 text-sm"></i>
                </div>
            </div>
        </a>
        @can('is-admin')
        {{-- Access Control --}}
        <a href="{{ route('users.index') }}" class="glass-card quick-nav-card block p-5 rounded-2xl border border-white/50 group mb-8">
            <div class="flex items-center">
                <div class="icon-badge icon-badge-indigo w-12 h-12 rounded-xl shadow-md group-hover:shadow-lg group-hover:shadow-indigo-500/20 transition-all">
                    <i class="fas fa-users-gear text-lg"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-bold text-slate-800 group-hover:text-indigo-600 transition-colors">Access Control</p>
                    <p class="text-[11px] font-medium text-slate-400 mt-0.5">System administration</p>
                </div>
                <div class="ml-auto opacity-0 group-hover:opacity-100 transition-opacity">
                    <i class="fas fa-arrow-right text-indigo-400 text-sm"></i>
                </div>
            </div>
        </a>

        {{-- Framework Library --}}
        <a href="{{ route('admin.frameworks.index') }}" class="glass-card quick-nav-card block p-5 rounded-2xl border border-white/50 group mb-8">
            <div class="flex items-center">
                <div class="icon-badge icon-badge-emerald w-12 h-12 rounded-xl shadow-md group-hover:shadow-lg group-hover:shadow-emerald-500/20 transition-all">
                    <i class="fas fa-cubes text-lg"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-bold text-slate-800 group-hover:text-emerald-600 transition-colors">Framework Library</p>
                    <p class="text-[11px] font-medium text-slate-400 mt-0.5">Manage compliance frameworks</p>
                </div>
                <div class="ml-auto opacity-0 group-hover:opacity-100 transition-opacity">
                    <i class="fas fa-arrow-right text-emerald-400 text-sm"></i>
                </div>
            </div>
        </a>

        {{-- Active Projects --}}
        <a href="{{ route('projects.index') }}" class="glass-card quick-nav-card block p-5 rounded-2xl border border-white/50 group mb-8">
            <div class="flex items-center">
                <div class="icon-badge icon-badge-sky w-12 h-12 rounded-xl shadow-md group-hover:shadow-lg group-hover:shadow-sky-500/20 transition-all">
                    <i class="fas fa-folder-open text-lg"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-bold text-slate-800 group-hover:text-sky-600 transition-colors">Active Projects</p>
                    <p class="text-[11px] font-medium text-slate-400 mt-0.5">Manage active compliance audits</p>
                </div>
                <div class="ml-auto opacity-0 group-hover:opacity-100 transition-opacity">
                    <i class="fas fa-arrow-right text-sky-400 text-sm"></i>
                </div>
            </div>
        </a>

        @endcan
    </div>

    {{-- ClamAV Scan Health Panel --}}
    <div class="mb-8" x-data="scanHealth()" x-init="init()">
        <div class="section-label flex items-center justify-between mb-4">
            <span>ClamAV Scan Health</span>
            <span class="text-[10px] text-slate-400 font-semibold uppercase tracking-widest" x-show="lastUpdated">
                <i class="fas fa-sync-alt mr-1" :class="{ 'animate-spin': loading }"></i>
                Updated <span x-text="lastUpdated"></span>
            </span>
        </div>

        {{-- Scan KPI Cards --}}
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-5">
            <div class="glass-card p-4 rounded-2xl border border-white/50">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Files Scanned</h3>
                    <div class="icon-badge icon-badge-sky"><i class="fas fa-microchip"></i></div>
                </div>
                <p class="text-2xl font-extrabold text-slate-900" x-text="stats.total_scanned || 0"></p>
            </div>
            <div class="glass-card p-4 rounded-2xl border border-white/50">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Clean</h3>
                    <div class="icon-badge icon-badge-emerald"><i class="fas fa-shield-check"></i></div>
                </div>
                <p class="text-2xl font-extrabold text-emerald-600" x-text="stats.clean || 0"></p>
            </div>
            <div class="glass-card p-4 rounded-2xl border border-white/50">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Infected</h3>
                    <div class="icon-badge icon-badge-rose"><i class="fas fa-bug"></i></div>
                </div>
                <p class="text-2xl font-extrabold text-rose-600" x-text="stats.infected || 0"></p>
            </div>
            <div class="glass-card p-4 rounded-2xl border border-white/50">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Quarantined</h3>
                    <div class="icon-badge icon-badge-amber"><i class="fas fa-lock"></i></div>
                </div>
                <p class="text-2xl font-extrabold text-amber-600" x-text="stats.quarantined || 0"></p>
            </div>
            <div class="glass-card p-4 rounded-2xl border border-white/50">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Scanning</h3>
                    <div class="icon-badge icon-badge-indigo"><i class="fas fa-radar"></i></div>
                </div>
                <p class="text-2xl font-extrabold text-indigo-600" x-text="(stats.pending||0) + (stats.processing||0)"></p>
            </div>
            <div class="glass-card p-4 rounded-2xl border border-white/50">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Failed</h3>
                    <div class="icon-badge icon-badge-rose"><i class="fas fa-triangle-exclamation"></i></div>
                </div>
                <p class="text-2xl font-extrabold text-slate-500" x-text="stats.failed || 0"></p>
            </div>
        </div>

        {{-- Recent Quarantined Files --}}
        <div class="glass-card rounded-2xl border border-white/50 overflow-hidden">
            <div class="px-5 py-3 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                    <i class="fas fa-virus mr-1.5 text-rose-500"></i> Recently Quarantined
                </h3>
                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest" x-show="recent.length === 0">No threats detected</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50/50 text-[9px] font-bold text-slate-400 uppercase tracking-widest">
                            <th class="px-5 py-3 text-left">File</th>
                            <th class="px-5 py-3 text-left">Project</th>
                            <th class="px-5 py-3 text-left">Virus / Threat</th>
                            <th class="px-5 py-3 text-left">Quarantined At</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <template x-for="row in recent" :key="row.id">
                            <tr class="hover:bg-slate-50/40 transition-colors">
                                <td class="px-5 py-3">
                                    <div class="flex items-center">
                                        <div class="w-7 h-7 rounded-lg bg-rose-50 border border-rose-100 flex items-center justify-center text-rose-500 mr-3 flex-shrink-0">
                                            <i class="fas fa-file-shield text-xs"></i>
                                        </div>
                                        <span class="text-[12px] font-bold text-slate-800 truncate max-w-[220px]" x-text="row.original_filename"></span>
                                    </div>
                                </td>
                                <td class="px-5 py-3 text-[11px] font-semibold text-slate-500" x-text="row.project_name || '—'"></td>
                                <td class="px-5 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-black tracking-widest bg-rose-100 text-rose-700">
                                        <i class="fas fa-bug mr-1"></i>
                                        <span x-text="row.virus_name || 'Unknown'"></span>
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-[11px] font-semibold text-slate-500" x-text="row.scanned_at || '—'"></td>
                            </tr>
                        </template>
                        <template x-if="recent.length === 0">
                            <tr>
                                <td colspan="4" class="py-10 text-center">
                                    <div class="text-slate-300">
                                        <i class="fas fa-shield-halved text-3xl mb-2 block"></i>
                                        <p class="text-[11px] font-black uppercase tracking-widest">All clear — no quarantined files</p>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Vue Analytics Layer --}}
    <div id="dashboard-app" class="mt-10">
        <div class="flex items-center justify-center py-20 text-slate-400">
            <div class="w-8 h-8 border-2 border-sky-500 border-t-transparent rounded-full animate-spin mr-3"></div>
            Loading analytics dashboard...
        </div>
    </div>

    <script nonce="{{ $cspNonce }}">
        function scanHealth() {
            return {
                stats: {},
                recent: [],
                loading: false,
                lastUpdated: '',
                async init() {
                    await this.fetchData();
                    setInterval(() => this.fetchData(), 15000);
                },
                async fetchData() {
                    this.loading = true;
                    try {
                        const r = await fetch('/dashboard/scan-stats');
                        if (r.ok) {
                            const data = await r.json();
                            this.stats = data.stats || {};
                            this.recent = data.recent_quarantined || [];
                            this.lastUpdated = new Date().toLocaleTimeString();
                        }
                    } catch (e) {}
                    this.loading = false;
                }
            };
        }
    </script>
    @push('scripts')
        @vite(['resources/js/dashboard/main.js'])
    @endpush
    </div>
@endsection
