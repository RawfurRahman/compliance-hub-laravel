{{-- resources/views/dashboard.blade.php --}}

@extends('layouts.app')

@section('content')
    {{-- Page Header --}}
    <div class="flex flex-col md:flex-row md:items-end md:justify-between mb-8 fade-in-up">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">
                Compliance <span class="gradient-text">Dashboard</span>
            </h1>
            <p class="mt-1.5 text-sm text-slate-500 font-medium">Real-time overview of your enterprise compliance posture</p>
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

        {{-- Active Projects --}}
        <div class="glass-card stat-card-sky p-5 rounded-2xl border border-white/50 hover:border-sky-200 transition-all group overflow-hidden relative cursor-default">
            <div class="absolute -right-3 -bottom-3 text-sky-500/[0.04] group-hover:text-sky-500/[0.08] transition-colors pointer-events-none">
                <i class="fas fa-folder-open text-6xl transform -rotate-12"></i>
            </div>
            <div class="flex items-center justify-between mb-4 relative z-10">
                <h3 class="text-[11px] font-bold text-slate-400 uppercase tracking-widest">Active Projects</h3>
                <div class="icon-badge icon-badge-sky">
                    <i class="fas fa-chart-line"></i>
                </div>
            </div>
            <p class="text-3xl font-extrabold text-slate-900 leading-none relative z-10">{{ $stats['active_projects'] }}</p>
            <div class="mt-3 pt-3 border-t border-slate-100/60 relative z-10">
                <p class="text-[11px] text-slate-500 flex items-center font-medium">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-2 pulse-dot"></span> Currently assigned
                </p>
            </div>
        </div>

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
        <a href="{{ route('projects.index') }}" class="glass-card quick-nav-card block p-5 rounded-2xl border border-white/50 group">
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

        @can('is-admin')
        {{-- Access Control --}}
        <a href="{{ route('users.index') }}" class="glass-card quick-nav-card block p-5 rounded-2xl border border-white/50 group">
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
        <a href="{{ route('admin.frameworks.index') }}" class="glass-card quick-nav-card block p-5 rounded-2xl border border-white/50 group">
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
        @endcan
    </div>
@endsection
