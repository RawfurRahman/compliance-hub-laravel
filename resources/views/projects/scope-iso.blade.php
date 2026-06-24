@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="mb-10">
        <div class="flex items-center space-x-2 mb-3">
            <a href="{{ route('projects.show', $project) }}" class="text-slate-400 hover:text-sky-600 transition-colors text-xs font-bold uppercase tracking-widest">{{ $project->name }}</a>
            <i class="fas fa-chevron-right text-[10px] text-slate-300"></i>
            <span class="text-sky-600 font-bold text-xs uppercase tracking-widest">Scope</span>
        </div>
        <h1 class="text-4xl font-extrabold text-slate-900 tracking-tight">Scope & <span class="text-sky-600">Assessment Boundaries</span></h1>
        <p class="mt-2 text-md text-slate-500 font-medium">Framework controls and domains included in your {{ $framework->name }} assessment.</p>
    </div>

    <div class="mb-8 border-b border-slate-200">
        <div class="flex space-x-8">
            <a href="{{ route('projects.show', $project) }}" class="px-1 py-4 text-sm font-semibold text-slate-600 hover:text-slate-900 border-b-2 border-transparent hover:border-slate-300 transition-colors">
                Overview
            </a>
            <a href="{{ route('projects.scope', $project) }}" class="px-1 py-4 text-sm font-semibold text-sky-600 border-b-2 border-sky-600">
                Scope
            </a>
            <a href="{{ route('projects.gap-assessment', $project) }}" class="px-1 py-4 text-sm font-semibold text-slate-600 hover:text-slate-900 border-b-2 border-transparent hover:border-slate-300 transition-colors">
                Gap Assessment
            </a>
            <a href="{{ route('projects.reporting', $project) }}" class="px-1 py-4 text-sm font-semibold text-slate-600 hover:text-slate-900 border-b-2 border-transparent hover:border-slate-300 transition-colors">
                Reports
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2">
            <div class="glass-card rounded-2xl p-8 border border-white/60 shadow-lg">
                <h2 class="text-2xl font-bold text-slate-900 mb-6">
                    <i class="fas fa-layer-group text-sky-600 mr-3"></i>Controls by Domain
                </h2>

                @forelse($domains as $domain)
                    <div class="mb-8 last:mb-0">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold text-slate-900">
                                <i class="fas fa-folder-open text-sky-600 mr-2"></i>{{ $domain['name'] }}
                            </h3>
                            <span class="px-3 py-1 bg-sky-50 text-sky-700 text-xs font-bold rounded-full border border-sky-200">
                                {{ $domain['total'] }} {{ Str::plural('control', $domain['total']) }}
                            </span>
                        </div>
                        <div class="space-y-2">
                            @foreach($domain['controls'] as $control)
                                <div class="p-3 bg-white rounded-lg border border-slate-200 flex items-center justify-between hover:border-sky-300 transition-colors">
                                    <div class="flex items-center space-x-3">
                                        <span class="px-2 py-1 bg-slate-100 text-slate-700 text-xs font-mono font-bold rounded">{{ $control->control_id }}</span>
                                        <span class="text-sm text-slate-700">{{ $control->control_name ?: $control->requirement_description }}</span>
                                    </div>
                                    <i class="fas fa-check-circle text-slate-300"></i>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center bg-slate-50 rounded-lg border border-dashed border-slate-300">
                        <i class="fas fa-inbox text-slate-300 text-3xl mb-3"></i>
                        <p class="text-slate-500 font-medium">No controls defined for this framework.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <div>
            <div class="glass-card rounded-2xl p-6 border border-white/60 shadow-lg">
                <h3 class="text-lg font-bold text-slate-900 mb-6">Scope Summary</h3>

                <div class="space-y-4">
                    <div>
                        <p class="text-sm text-slate-600 uppercase tracking-widest font-semibold">Framework</p>
                        <p class="text-xl font-bold text-slate-900 mt-1">{{ $framework->name }}</p>
                        @if($framework->version)
                            <p class="text-xs text-slate-500">Version {{ $framework->version }}</p>
                        @endif
                    </div>
                    <div class="pt-4 border-t border-slate-200">
                        <p class="text-sm text-slate-600 uppercase tracking-widest font-semibold">Total Domains</p>
                        <p class="text-3xl font-bold text-slate-900 mt-1">{{ $domains->count() }}</p>
                    </div>
                    <div class="pt-4 border-t border-slate-200">
                        <p class="text-sm text-slate-600 uppercase tracking-widest font-semibold">Total Controls</p>
                        <p class="text-3xl font-bold text-slate-900 mt-1">{{ $domains->sum('total') }}</p>
                    </div>
                </div>

                <div class="mt-8 p-4 bg-sky-50 border border-sky-200 rounded-lg">
                    <p class="text-sm text-sky-800">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Note:</strong> The controls listed above define the scope of your {{ $framework->name }} assessment. Each control will be evaluated during the gap assessment process.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection