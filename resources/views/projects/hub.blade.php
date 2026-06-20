@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto">
    {{-- Header --}}
    <div class="mb-10">
        <h1 class="text-4xl font-extrabold text-slate-900 tracking-tight">{{ $project->name }}</h1>
        <p class="mt-2 text-md text-slate-500 font-medium">Project overview and management</p>
    </div>

    {{-- Navigation Tabs --}}
    <div class="mb-8 border-b border-slate-200">
        <div class="flex space-x-8">
            <a href="{{ route('projects.show', $project) }}" class="px-1 py-4 text-sm font-semibold text-sky-600 border-b-2 border-sky-600">
                Overview
            </a>
            @if($project->module_type === 'pci_dss')
                <a href="{{ route('projects.scope', $project) }}" class="px-1 py-4 text-sm font-semibold text-slate-600 hover:text-slate-900 border-b-2 border-transparent hover:border-slate-300 transition-colors">
                    Scope
                </a>
                <a href="{{ route('projects.gap-assessment', $project) }}" class="px-1 py-4 text-sm font-semibold text-slate-600 hover:text-slate-900 border-b-2 border-transparent hover:border-slate-300 transition-colors">
                    Gap Assessment
                </a>
            @elseif($project->module_type === 'iso_27001')
                <a href="{{ route('iso-gap.index', $project) }}" class="px-1 py-4 text-sm font-semibold text-slate-600 hover:text-slate-900 border-b-2 border-transparent hover:border-slate-300 transition-colors">
                    ISO Gap Assessment
                </a>
                <a href="{{ route('assessments.show', $project) }}" class="px-1 py-4 text-sm font-semibold text-slate-600 hover:text-slate-900 border-b-2 border-transparent hover:border-slate-300 transition-colors">
                    Unified Assessment
                </a>
            @elseif($project->module_type === 'hitrust')
                <a href="{{ route('assessments.show', $project) }}" class="px-1 py-4 text-sm font-semibold text-slate-600 hover:text-slate-900 border-b-2 border-transparent hover:border-slate-300 transition-colors">
                    Unified Assessment
                </a>
            @endif
            <a href="{{ route('projects.reporting', $project) }}" class="px-1 py-4 text-sm font-semibold text-slate-600 hover:text-slate-900 border-b-2 border-transparent hover:border-slate-300 transition-colors">
                Reports
            </a>
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
        <div class="glass-card rounded-2xl p-6 border border-white/60 shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-semibold text-slate-600 uppercase tracking-widest">Evidence Items</p>
                    <p class="text-3xl font-bold text-slate-900 mt-2">{{ $stats['total_evidence'] ?? 0 }}</p>
                    @if(($stats['pending_evidence'] ?? 0) > 0)
                        <p class="text-xs text-amber-600 mt-1">{{ $stats['pending_evidence'] }} pending</p>
                    @endif
                </div>
                <div class="w-12 h-12 rounded-lg bg-sky-500/10 flex items-center justify-center">
                    <i class="fas fa-file-upload text-sky-600 text-lg"></i>
                </div>
            </div>
        </div>

        <div class="glass-card rounded-2xl p-6 border border-white/60 shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-semibold text-slate-600 uppercase tracking-widest">Team Members</p>
                    <p class="text-3xl font-bold text-slate-900 mt-2">{{ $stats['team_members'] ?? 0 }}</p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-indigo-500/10 flex items-center justify-center">
                    <i class="fas fa-users text-indigo-600 text-lg"></i>
                </div>
            </div>
        </div>

        <div class="glass-card rounded-2xl p-6 border border-white/60 shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-semibold text-slate-600 uppercase tracking-widest">Meetings</p>
                    <p class="text-3xl font-bold text-slate-900 mt-2">{{ $stats['total_meetings'] ?? 0 }}</p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-emerald-500/10 flex items-center justify-center">
                    <i class="fas fa-calendar text-emerald-600 text-lg"></i>
                </div>
            </div>
        </div>

        @if($stats['total_requirements'] ?? false)
            <div class="glass-card rounded-2xl p-6 border border-white/60 shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold text-slate-600 uppercase tracking-widest">Requirements</p>
                        <p class="text-3xl font-bold text-slate-900 mt-2">{{ $stats['completed_requirements'] ?? 0 }}/{{ $stats['total_requirements'] }}</p>
                        <p class="text-xs text-slate-500 mt-1">{{ round(($stats['completed_requirements'] ?? 0) / ($stats['total_requirements'] ?? 1) * 100) }}% complete</p>
                    </div>
                    <div class="w-12 h-12 rounded-lg bg-rose-500/10 flex items-center justify-center">
                        <i class="fas fa-tasks text-rose-600 text-lg"></i>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Quick Actions --}}
    <div class="glass-card rounded-2xl p-8 border border-white/60 shadow-lg">
        <h2 class="text-xl font-bold text-slate-900 mb-6">Quick Actions</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <a href="{{ route('evidence.show', $project) }}" class="px-4 py-3 text-center font-semibold text-emerald-600 bg-emerald-50 hover:bg-emerald-100 rounded-lg border border-emerald-200 transition-colors">
                <i class="fas fa-archive mr-2"></i> Evidence Hub
            </a>
            <a href="{{ route('meetings.index', $project) }}" class="px-4 py-3 text-center font-semibold text-sky-600 bg-sky-50 hover:bg-sky-100 rounded-lg border border-sky-200 transition-colors">
                <i class="fas fa-calendar-alt mr-2"></i> Meetings
            </a>
            <a href="{{ route('required-documents.index', $project) }}" class="px-4 py-3 text-center font-semibold text-violet-600 bg-violet-50 hover:bg-violet-100 rounded-lg border border-violet-200 transition-colors">
                <i class="fas fa-folder-tree mr-2"></i> Required Documents
            </a>
            @if($project->module_type === 'pci_dss')
                <a href="{{ route('pci.show', $project) }}" class="px-4 py-3 text-center font-semibold text-indigo-600 bg-indigo-50 hover:bg-indigo-100 rounded-lg border border-indigo-200 transition-colors">
                    <i class="fas fa-shield-alt mr-2"></i> PCI Assessment
                </a>
            @elseif($project->module_type === 'iso_27001')
                <a href="{{ route('iso-gap.index', $project) }}" class="px-4 py-3 text-center font-semibold text-indigo-600 bg-indigo-50 hover:bg-indigo-100 rounded-lg border border-indigo-200 transition-colors">
                    <i class="fas fa-shield-halved mr-2"></i> ISO Gap Assessment
                </a>
                <a href="{{ route('assessments.show', $project) }}" class="px-4 py-3 text-center font-semibold text-indigo-600 bg-indigo-50 hover:bg-indigo-100 rounded-lg border border-indigo-200 transition-colors">
                    <i class="fas fa-clipboard-check mr-2"></i> Unified Assessment
                </a>
            @elseif($project->module_type === 'hitrust')
                <a href="{{ route('assessments.show', $project) }}" class="px-4 py-3 text-center font-semibold text-indigo-600 bg-indigo-50 hover:bg-indigo-100 rounded-lg border border-indigo-200 transition-colors">
                    <i class="fas fa-clipboard-check mr-2"></i> Unified Assessment
                </a>
            @endif
            <a href="{{ route('projects.reporting', $project) }}" class="px-4 py-3 text-center font-semibold text-amber-600 bg-amber-50 hover:bg-amber-100 rounded-lg border border-amber-200 transition-colors">
                <i class="fas fa-file-pdf mr-2"></i> Reports
            </a>
        </div>
    </div>
</div>
@endsection
