@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto">
    {{-- Header --}}
    <div class="mb-10">
        <div class="flex items-center space-x-2 mb-3">
            <a href="{{ route('projects.show', $project) }}" class="text-slate-400 hover:text-sky-600 transition-colors text-xs font-bold uppercase tracking-widest">{{ $project->name }}</a>
            <i class="fas fa-chevron-right text-[10px] text-slate-300"></i>
            <span class="text-sky-600 font-bold text-xs uppercase tracking-widest">Scope</span>
        </div>
        <h1 class="text-4xl font-extrabold text-slate-900 tracking-tight">Scope & <span class="text-sky-600">Assessment Boundaries</span></h1>
        <p class="mt-2 text-md text-slate-500 font-medium">Framework controls and systems included in your {{ $framework->name }} assessment.</p>
    </div>

    {{-- Navigation Tabs --}}
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

    {{-- Flash Messages --}}
    @if (session('success'))
        <div class="bg-emerald-50 border-l-4 border-emerald-500 p-4 rounded shadow-sm mb-6 flex items-start">
            <i class="fas fa-check-circle text-emerald-500 mt-0.5 mr-3 text-lg"></i>
            <p class="text-sm font-medium text-emerald-800">{{ session('success') }}</p>
        </div>
    @endif

    @if (session('error'))
        <div class="bg-rose-50 border-l-4 border-rose-500 p-4 rounded shadow-sm mb-6 flex items-start">
            <i class="fas fa-exclamation-circle text-rose-500 mt-0.5 mr-3 text-lg"></i>
            <p class="text-sm font-medium text-rose-800">{{ session('error') }}</p>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {{-- Main Content --}}
        <div class="lg:col-span-2">
            <div class="glass-card rounded-2xl p-8 border border-white/60 shadow-lg">
                <h2 class="text-2xl font-bold text-slate-900 mb-6">
                    <i class="fas fa-network-wired text-sky-600 mr-3"></i>Assessment Scope
                </h2>

                {{-- Scope Description --}}
                <div class="mb-8">
                    <label class="block text-sm font-bold uppercase tracking-widest text-slate-600 mb-3">Scope Description</label>
                    <p class="text-slate-700 p-4 bg-slate-50 rounded-lg border border-slate-200">
                        {{ $project->scope_description ?? 'No scope description defined yet.' }}
                    </p>
                </div>

                {{-- Controls by Domain --}}
                <div class="mb-8">
                    <h3 class="text-lg font-bold text-slate-900 mb-4">
                        <i class="fas fa-layer-group text-sky-600 mr-2"></i>Controls by Domain
                    </h3>

                    @forelse($domains as $domain)
                        <div class="mb-6 last:mb-0">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="text-base font-bold text-slate-900">
                                    <i class="fas fa-folder-open text-sky-600 mr-2"></i>{{ $domain['name'] }}
                                </h4>
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

                {{-- PCI DSS Physical Scope --}}
                @if ($project->module_type === 'pci_dss')
                    {{-- Networks In Scope --}}
                    <div class="mb-8">
                        <h3 class="text-lg font-bold text-slate-900 mb-4">
                            <i class="fas fa-router text-emerald-600 mr-2"></i>Networks
                        </h3>
                        @if($project->pciDssDetails && $project->pciDssDetails->networks->isNotEmpty())
                            <div class="space-y-3">
                                @foreach($project->pciDssDetails->networks as $network)
                                    <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-lg flex items-center justify-between">
                                        <div>
                                            <p class="font-semibold text-slate-900">{{ $network->network_name }}</p>
                                            <p class="text-sm text-slate-600">{{ $network->network_type ?? 'Network' }}</p>
                                        </div>
                                        <span class="px-3 py-1 bg-emerald-200 text-emerald-800 text-xs font-bold rounded-full">In Scope</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="p-8 text-center bg-slate-50 rounded-lg border border-dashed border-slate-300">
                                <i class="fas fa-inbox text-slate-300 text-3xl mb-3"></i>
                                <p class="text-slate-500 font-medium">No networks defined</p>
                            </div>
                        @endif
                    </div>

                    {{-- Locations --}}
                    <div class="mb-8">
                        <h3 class="text-lg font-bold text-slate-900 mb-4">
                            <i class="fas fa-map-marker-alt text-sky-600 mr-2"></i>Physical Locations
                        </h3>
                        @if($project->pciDssDetails && $project->pciDssDetails->locations->isNotEmpty())
                            <div class="space-y-3">
                                @foreach($project->pciDssDetails->locations as $location)
                                    <div class="p-4 bg-sky-50 border border-sky-200 rounded-lg flex items-center justify-between">
                                        <div>
                                            <p class="font-semibold text-slate-900">{{ $location->location_name }}</p>
                                            <p class="text-sm text-slate-600">{{ $location->location_address ?? 'Address not specified' }}</p>
                                        </div>
                                        <span class="px-3 py-1 bg-sky-200 text-sky-800 text-xs font-bold rounded-full">In Scope</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="p-8 text-center bg-slate-50 rounded-lg border border-dashed border-slate-300">
                                <i class="fas fa-inbox text-slate-300 text-3xl mb-3"></i>
                                <p class="text-slate-500 font-medium">No locations defined</p>
                            </div>
                        @endif
                    </div>

                    {{-- System Components --}}
                    <div class="mb-8">
                        <h3 class="text-lg font-bold text-slate-900 mb-4">
                            <i class="fas fa-cube text-indigo-600 mr-2"></i>System Components
                        </h3>
                        @if($project->pciDssDetails && $project->pciDssDetails->components->isNotEmpty())
                            <div class="space-y-3">
                                @foreach($project->pciDssDetails->components as $component)
                                    <div class="p-4 bg-indigo-50 border border-indigo-200 rounded-lg flex items-center justify-between">
                                        <div>
                                            <p class="font-semibold text-slate-900">{{ $component->component_name }}</p>
                                            <p class="text-sm text-slate-600">{{ $component->component_type ?? 'Component' }}</p>
                                        </div>
                                        <span class="px-3 py-1 bg-indigo-200 text-indigo-800 text-xs font-bold rounded-full">In Scope</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="p-8 text-center bg-slate-50 rounded-lg border border-dashed border-slate-300">
                                <i class="fas fa-inbox text-slate-300 text-3xl mb-3"></i>
                                <p class="text-slate-500 font-medium">No components defined</p>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Edit Scope Button --}}
                <div class="mt-8 pt-6 border-t border-slate-200">
                    <button class="js-toggle" data-csp-target="scope-edit-form" "px-6 py-3 bg-sky-600 text-white font-semibold rounded-lg hover:bg-sky-700 transition-colors inline-flex items-center">
                        <i class="fas fa-edit mr-2"></i> Edit Scope Details
                    </button>
                </div>

                {{-- Inline Edit Form --}}
                <div id="scope-edit-form" class="hidden mt-6 p-6 bg-slate-50 rounded-xl border border-slate-200">
                    <h3 class="text-lg font-bold text-slate-900 mb-4">Update Scope Description</h3>
                    <form action="{{ route('projects.scope.update', $project) }}" method="POST">
                        @csrf
                        <div class="mb-4">
                            <label class="block text-sm font-bold uppercase tracking-widest text-slate-600 mb-2">Scope Description</label>
                            <textarea name="scope_description" rows="4" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm" placeholder="Describe the scope of this assessment...">{{ $project->scope_description }}</textarea>
                        </div>
                        <div class="flex space-x-3">
                            <button type="submit" class="px-4 py-2 bg-sky-600 text-white text-sm font-semibold rounded-lg hover:bg-sky-700 transition-colors">
                                <i class="fas fa-save mr-1"></i> Save Changes
                            </button>
                            <button type="button" class="js-hide" data-csp-target="scope-edit-form" "px-4 py-2 bg-white text-slate-600 text-sm font-semibold rounded-lg border border-slate-300 hover:bg-slate-50 transition-colors">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Sidebar Summary --}}
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

                    @if($project->module_type === 'pci_dss')
                        <div>
                            <p class="text-sm text-slate-600 uppercase tracking-widest font-semibold">Networks</p>
                            <p class="text-3xl font-bold text-slate-900 mt-1">{{ $project->pciDssDetails ? $project->pciDssDetails->networks->count() : 0 }}</p>
                        </div>
                        <div class="pt-4 border-t border-slate-200">
                            <p class="text-sm text-slate-600 uppercase tracking-widest font-semibold">Locations</p>
                            <p class="text-3xl font-bold text-slate-900 mt-1">{{ $project->pciDssDetails ? $project->pciDssDetails->locations->count() : 0 }}</p>
                        </div>
                        <div class="pt-4 border-t border-slate-200">
                            <p class="text-sm text-slate-600 uppercase tracking-widest font-semibold">Components</p>
                            <p class="text-3xl font-bold text-slate-900 mt-1">{{ $project->pciDssDetails ? $project->pciDssDetails->components->count() : 0 }}</p>
                        </div>
                    @endif
                </div>

                <div class="mt-8 p-4 bg-sky-50 border border-sky-200 rounded-lg">
                    <p class="text-sm text-sky-800">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Note:</strong> The controls and items listed above define the scope of your {{ $framework->name }} assessment. Each control will be evaluated during the gap assessment process.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
