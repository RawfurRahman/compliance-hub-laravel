@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto">
    {{-- Header --}}
    <div class="mb-10">
        <div class="flex items-center space-x-2 mb-3">
            <a href="{{ route('projects.show', $project) }}" class="text-slate-400 hover:text-sky-600 transition-colors text-xs font-bold uppercase tracking-widest">{{ $project->name }}</a>
            <i class="fas fa-chevron-right text-[10px] text-slate-300"></i>
            <span class="text-sky-600 font-bold text-xs uppercase tracking-widest">Reports</span>
        </div>
        <h1 class="text-4xl font-extrabold text-slate-900 tracking-tight">Reports & Compliance <span class="text-sky-600">Documentation</span></h1>
        <p class="mt-2 text-md text-slate-500 font-medium">Generate and manage compliance reports for {{ $project->name }}.</p>
    </div>

    {{-- Navigation Tabs --}}
    <div class="mb-8 border-b border-slate-200">
        <div class="flex space-x-8">
            <a href="{{ route('projects.show', $project) }}" class="px-1 py-4 text-sm font-semibold text-slate-600 hover:text-slate-900 border-b-2 border-transparent hover:border-slate-300 transition-colors">
                Overview
            </a>
            <a href="{{ route('projects.scope', $project) }}" class="px-1 py-4 text-sm font-semibold text-slate-600 hover:text-slate-900 border-b-2 border-transparent hover:border-slate-300 transition-colors">
                Scope
            </a>
            <a href="{{ $project->module_type === 'pci_dss' ? route('pci-gap.index', $project) : ($project->module_type === 'iso_27001' ? route('iso-gap.index', $project) : route('projects.gap-assessment', $project)) }}" class="px-1 py-4 text-sm font-semibold text-slate-600 hover:text-slate-900 border-b-2 border-transparent hover:border-slate-300 transition-colors">
                Gap Assessment
            </a>
            <a href="{{ route('projects.reporting', $project) }}" class="px-1 py-4 text-sm font-semibold text-sky-600 border-b-2 border-sky-600">
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

    <div class="grid grid-cols-1 gap-10">

        {{-- Available Reports Section --}}
        <div>
            <h2 class="text-2xl font-bold text-slate-900 mb-6 flex items-center">
                <i class="fas fa-file-pdf text-sky-600 mr-3"></i> Available Reports
            </h2>

            @if ($availableReports->isEmpty())
                <div class="bg-slate-50 rounded-lg p-8 text-center">
                    <i class="fas fa-folder-open text-slate-300 text-4xl mb-4"></i>
                    <p class="text-slate-500 font-medium">No reports available for this project type.</p>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($availableReports as $report)
                        <div class="group relative glass-card rounded-2xl p-6 border border-white/60 shadow-lg hover:shadow-xl hover:border-{{ $report['color'] }}-300 transition-all {{ $report['disabled'] ?? false ? 'opacity-50 cursor-not-allowed' : '' }}">
                            {{-- Icon --}}
                            <div class="w-12 h-12 rounded-lg bg-{{ $report['color'] }}-500/10 flex items-center justify-center mb-4 group-hover:bg-{{ $report['color'] }}-500/20 transition-colors">
                                <i class="fas {{ $report['icon'] }} text-{{ $report['color'] }}-600 text-lg"></i>
                            </div>

                            {{-- Title & Description --}}
                            <h3 class="text-lg font-bold text-slate-900 mb-2">{{ $report['label'] }}</h3>
                            <p class="text-sm text-slate-600 mb-4">{{ $report['description'] }}</p>

                            {{-- Version Badge --}}
                            <div class="flex items-center space-x-2 mb-6">
                                <span class="px-2 py-1 text-[10px] font-semibold uppercase tracking-widest rounded bg-{{ $report['color'] }}-50 text-{{ $report['color'] }}-700 border border-{{ $report['color'] }}-200">
                                    v{{ $report['version'] }}
                                </span>
                                @if ($latestReports->has($report['type']))
                                    <span class="text-[10px] text-slate-500">
                                        Last generated: {{ $latestReports[$report['type']]->generated_at->format('M d, Y') }}
                                    </span>
                                @endif
                            </div>

                            {{-- Actions --}}
                            @if ($report['disabled'] ?? false)
                                <button disabled class="w-full px-4 py-2 text-xs font-bold uppercase tracking-widest rounded-lg bg-slate-100 text-slate-400 cursor-not-allowed transition-colors">
                                    <i class="fas fa-lock mr-2"></i> Coming Soon
                                </button>
                            @else
                                <div class="flex flex-col space-y-2">
                                    <a href="{{ route('projects.report', ['project' => $project->id, 'type' => $report['type']]) }}"
                                       target="_blank"
                                       class="px-4 py-2 text-xs font-bold uppercase tracking-widest text-{{ $report['color'] }}-600 hover:bg-{{ $report['color'] }}-50 rounded-lg border border-{{ $report['color'] }}-200 transition-colors text-center flex items-center justify-center">
                                        <i class="fas fa-arrow-up-right-from-square mr-1"></i> Generate
                                    </a>
                                    @if ($latestReports->has($report['type']))
                                        <div class="flex space-x-1">
                                            <a href="{{ route('projects.report.download', ['project' => $project->id, 'type' => $report['type'], 'format' => 'pdf']) }}"
                                               class="flex-1 px-2 py-2 text-xs font-bold uppercase tracking-widest text-{{ $report['color'] }}-600 hover:bg-{{ $report['color'] }}-50 rounded-lg border border-{{ $report['color'] }}-200 transition-colors text-center"
                                               title="Download PDF">
                                                <i class="fas fa-download mr-1"></i> PDF
                                            </a>
                                            <a href="{{ route('projects.report.download', ['project' => $project->id, 'type' => $report['type'], 'format' => 'html']) }}"
                                               class="flex-1 px-2 py-2 text-xs font-bold uppercase tracking-widest text-{{ $report['color'] }}-600 hover:bg-{{ $report['color'] }}-50 rounded-lg border border-{{ $report['color'] }}-200 transition-colors text-center"
                                               title="Download HTML">
                                                <i class="fas fa-code mr-1"></i> HTML
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Report History Section --}}
        @if ($reportHistory->isNotEmpty())
            <div>
                <h2 class="text-2xl font-bold text-slate-900 mb-6 flex items-center">
                    <i class="fas fa-history text-emerald-600 mr-3"></i> Report History
                </h2>

                <div class="glass-card rounded-2xl border border-white/60 shadow-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-slate-200 bg-slate-50">
                                    <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-widest text-slate-600">Report Type</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-widest text-slate-600">Generated By</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-widest text-slate-600">Date</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-widest text-slate-600">Status</th>
                                    <th class="px-6 py-4 text-center text-xs font-bold uppercase tracking-widest text-slate-600">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200">
                                @foreach ($reportHistory as $report)
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center space-x-2">
                                                <i class="fas fa-file-pdf text-sky-600"></i>
                                                <span class="font-semibold text-slate-900">{{ ucwords(str_replace('_', ' ', $report->report_type)) }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-slate-600">
                                            {{ optional($report->generatedBy)->username ?? 'System' }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-slate-600">
                                            {{ $report->generated_at->format('M d, Y H:i') }}
                                        </td>
                                        <td class="px-6 py-4">
                                            @switch($report->status)
                                                @case('final')
                                                    <span class="px-3 py-1 text-xs font-bold uppercase tracking-widest rounded-full bg-emerald-100 text-emerald-700">Final</span>
                                                    @break
                                                @case('draft')
                                                    <span class="px-3 py-1 text-xs font-bold uppercase tracking-widest rounded-full bg-amber-100 text-amber-700">Draft</span>
                                                    @break
                                                @case('archived')
                                                    <span class="px-3 py-1 text-xs font-bold uppercase tracking-widest rounded-full bg-slate-100 text-slate-700">Archived</span>
                                                    @break
                                            @endswitch
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <div class="flex justify-center space-x-2">
                                                <a href="{{ route('projects.report', ['project' => $project->id, 'type' => $report->report_type]) }}"
                                                   target="_blank"
                                                   class="p-2 text-slate-400 hover:text-sky-600 hover:bg-sky-50 rounded-lg transition-colors"
                                                   title="View Report">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('projects.report.download', ['project' => $project->id, 'type' => $report->report_type, 'format' => 'pdf']) }}"
                                                   class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors"
                                                   title="Download PDF">
                                                    <i class="fas fa-file-pdf"></i>
                                                </a>
                                                <a href="{{ route('projects.report.download', ['project' => $project->id, 'type' => $report->report_type, 'format' => 'html']) }}"
                                                   class="p-2 text-slate-400 hover:text-orange-600 hover:bg-orange-50 rounded-lg transition-colors"
                                                   title="Download HTML">
                                                    <i class="fas fa-file-code"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

    </div>
</div>
@endsection
