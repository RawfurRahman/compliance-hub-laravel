@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto" x-data="{ showShareModal: false, showScheduleModal: false, shareReportType: '', shareReportLabel: '', openShareModal(type, label) { this.shareReportType = type; this.shareReportLabel = label; this.showShareModal = true; } }">
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

        {{-- Reporting Analytics Dashboard --}}
        <div class="glass-card rounded-3xl border border-white/70 shadow-xl overflow-hidden bg-white/40 backdrop-blur-md p-8">
            <h2 class="text-2xl font-bold text-slate-900 mb-6 flex items-center">
                <i class="fas fa-chart-pie text-sky-600 mr-3"></i> Reporting Analytics & Metrics
            </h2>

            {{-- Stats row --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white/60 rounded-2xl p-5 border border-slate-100 shadow-sm flex items-center space-x-4">
                    <div class="w-12 h-12 rounded-xl bg-indigo-500/10 flex items-center justify-center">
                        <i class="fas fa-file-invoice text-indigo-600 text-lg"></i>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Total Reports</p>
                        <p class="text-2xl font-extrabold text-slate-900">{{ $totalReportsCount }}</p>
                    </div>
                </div>

                <div class="bg-white/60 rounded-2xl p-5 border border-slate-100 shadow-sm flex items-center space-x-4">
                    <div class="w-12 h-12 rounded-xl bg-sky-500/10 flex items-center justify-center">
                        <i class="fas fa-calendar-check text-sky-600 text-lg"></i>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Active Schedules</p>
                        <p class="text-2xl font-extrabold text-slate-900">{{ $activeSchedulesCount }}</p>
                    </div>
                </div>

                <div class="bg-white/60 rounded-2xl p-5 border border-slate-100 shadow-sm flex items-center space-x-4">
                    <div class="w-12 h-12 rounded-xl bg-amber-500/10 flex items-center justify-center">
                        <i class="fas fa-bookmark text-amber-600 text-lg"></i>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Saved Templates</p>
                        <p class="text-2xl font-extrabold text-slate-900">{{ $customTemplatesCount }}</p>
                    </div>
                </div>

                <div class="bg-white/60 rounded-2xl p-5 border border-slate-100 shadow-sm flex items-center space-x-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-500/10 flex items-center justify-center">
                        <i class="fas fa-shield-halved text-emerald-600 text-lg"></i>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Compliance Score</p>
                        <p class="text-2xl font-extrabold text-slate-900">{{ $currentCompliance }}%</p>
                    </div>
                </div>
            </div>

            {{-- Graphs and Details --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                {{-- Compliance Trend line chart --}}
                <div class="lg:col-span-2 bg-white/60 rounded-2xl p-6 border border-slate-100 shadow-sm flex flex-col justify-between">
                    <div>
                        <h3 class="text-md font-bold text-slate-800 mb-4 flex items-center">
                            <i class="fas fa-chart-line text-slate-500 mr-2"></i> Compliance Score Trend Timeline
                        </h3>
                        @if ($trendData->isEmpty())
                            <div class="h-48 flex flex-col items-center justify-center text-center p-4">
                                <i class="fas fa-project-diagram text-slate-300 text-3xl mb-3 animate-pulse"></i>
                                <p class="text-slate-400 text-xs font-semibold">No report metrics logged yet.</p>
                                <p class="text-slate-400 text-[10px] mt-1">Generating reports snapshot-logs compliance status over time.</p>
                            </div>
                        @else
                            @php
                                $count = count($trendData);
                                $points = '';
                                $areaPoints = '40,160 ';
                                $xCoords = [];
                                $yCoords = [];
                                if ($count > 1) {
                                    foreach ($trendData as $i => $item) {
                                        $x = 40 + $i * (520 / ($count - 1));
                                        $y = 160 - ($item['value'] / 100) * 140;
                                        $xCoords[] = $x;
                                        $yCoords[] = $y;
                                        $points .= "{$x},{$y} ";
                                        $areaPoints .= "{$x},{$y} ";
                                    }
                                    $areaPoints .= "560,160";
                                } elseif ($count == 1) {
                                    $x = 300;
                                    $val = $trendData[0]['value'];
                                    $y = 160 - ($val / 100) * 140;
                                    $xCoords[] = $x;
                                    $yCoords[] = $y;
                                    $points = "300,{$y}";
                                    $areaPoints = "40,160 300,{$y} 560,160";
                                }
                            @endphp
                            <div class="relative overflow-visible">
                                <svg class="w-full h-48 overflow-visible" viewBox="0 0 600 200" preserveAspectRatio="none">
                                    <defs>
                                        <linearGradient id="chartGradient" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="0%" stop-color="#0ea5e9" stop-opacity="0.3"></stop>
                                            <stop offset="100%" stop-color="#0ea5e9" stop-opacity="0.00"></stop>
                                        </linearGradient>
                                    </defs>
                                    <!-- Gridlines -->
                                    <line x1="40" y1="20" x2="560" y2="20" stroke="#f1f5f9" stroke-dasharray="4" stroke-width="1" />
                                    <line x1="40" y1="55" x2="560" y2="55" stroke="#f1f5f9" stroke-dasharray="4" stroke-width="1" />
                                    <line x1="40" y1="90" x2="560" y2="90" stroke="#f1f5f9" stroke-dasharray="4" stroke-width="1" />
                                    <line x1="40" y1="125" x2="560" y2="125" stroke="#f1f5f9" stroke-dasharray="4" stroke-width="1" />
                                    <line x1="40" y1="160" x2="560" y2="160" stroke="#cbd5e1" stroke-width="1" />
                                    
                                    <!-- Y-axis labels -->
                                    <text x="30" y="24" text-anchor="end" font-size="9" fill="#94a3b8" font-weight="bold">100%</text>
                                    <text x="30" y="94" text-anchor="end" font-size="9" fill="#94a3b8" font-weight="bold">50%</text>
                                    <text x="30" y="164" text-anchor="end" font-size="9" fill="#94a3b8" font-weight="bold">0%</text>

                                    <!-- Area under line -->
                                    <polygon points="{{ $areaPoints }}" fill="url(#chartGradient)" />

                                    <!-- Trend line -->
                                    @if ($count > 1)
                                        <polyline points="{{ $points }}" fill="none" stroke="#0ea5e9" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
                                    @elseif ($count == 1)
                                        <circle cx="300" cy="{{ $yCoords[0] }}" r="6" fill="#0ea5e9" />
                                    @endif

                                    <!-- Data points -->
                                    @foreach($trendData as $i => $item)
                                        @php
                                            $x = $xCoords[$i] ?? 0;
                                            $y = $yCoords[$i] ?? 0;
                                        @endphp
                                        <circle cx="{{ $x }}" cy="{{ $y }}" r="4" fill="#ffffff" stroke="#0ea5e9" stroke-width="2" class="cursor-pointer transition-all hover:scale-150">
                                            <title>{{ $item['type'] }}: {{ $item['value'] }}% ({{ $item['label'] }})</title>
                                        </circle>
                                        <text x="{{ $x }}" y="182" text-anchor="middle" font-size="8" fill="#64748b" font-weight="semibold">{{ $item['label'] }}</text>
                                    @endforeach
                                </svg>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Distributions list --}}
                <div class="bg-white/60 rounded-2xl p-6 border border-slate-100 shadow-sm flex flex-col justify-between">
                    <div>
                        <h3 class="text-md font-bold text-slate-800 mb-4 flex items-center">
                            <i class="fas fa-sliders-h text-slate-500 mr-2"></i> Report Distribution Insights
                        </h3>
                        
                        {{-- Types distribution --}}
                        <div class="mb-6">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">Report Types</h4>
                            @if ($reportTypesDist->isEmpty())
                                <p class="text-xs text-slate-400 italic">No reports recorded.</p>
                            @else
                                <div class="space-y-3">
                                    @foreach ($reportTypesDist as $type => $countVal)
                                        @php
                                            $label = collect($availableReports)->firstWhere('type', $type)['label'] ?? ucwords(str_replace('_', ' ', $type));
                                            $pct = $totalReportsCount > 0 ? ($countVal / $totalReportsCount) * 100 : 0;
                                        @endphp
                                        <div>
                                            <div class="flex justify-between text-xs font-semibold text-slate-700 mb-1">
                                                <span>{{ $label }}</span>
                                                <span>{{ $countVal }} ({{ round($pct) }}%)</span>
                                            </div>
                                            <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                                                <div class="bg-indigo-600 h-full rounded-full" style="width: {{ $pct }}%"></div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        {{-- Formats distribution --}}
                        <div>
                            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">Export Formats</h4>
                            @php
                                $totalFormats = array_sum($formatDist);
                                $pdfPct = $totalFormats > 0 ? ($formatDist['pdf'] / $totalFormats) * 100 : 0;
                                $htmlPct = $totalFormats > 0 ? ($formatDist['html'] / $totalFormats) * 100 : 0;
                            @endphp
                            <div class="space-y-3">
                                <div>
                                    <div class="flex justify-between text-xs font-semibold text-slate-700 mb-1">
                                        <span>PDF Attachments / Downloads</span>
                                        <span>{{ $formatDist['pdf'] }}</span>
                                    </div>
                                    <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                                        <div class="bg-rose-500 h-full rounded-full" style="width: {{ $pdfPct }}%"></div>
                                    </div>
                                </div>
                                <div>
                                    <div class="flex justify-between text-xs font-semibold text-slate-700 mb-1">
                                        <span>HTML Web Previews</span>
                                        <span>{{ $formatDist['html'] }}</span>
                                    </div>
                                    <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                                        <div class="bg-amber-500 h-full rounded-full" style="width: {{ $htmlPct }}%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

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
                                    <div class="flex space-x-1">
                                        <a href="{{ route('projects.report', ['project' => $project->id, 'type' => $report['type']]) }}"
                                           target="_blank"
                                           class="flex-1 px-4 py-2 text-xs font-bold uppercase tracking-widest text-{{ $report['color'] }}-600 hover:bg-{{ $report['color'] }}-50 rounded-lg border border-{{ $report['color'] }}-200 transition-colors text-center flex items-center justify-center">
                                            <i class="fas fa-arrow-up-right-from-square mr-1"></i> Generate
                                        </a>
                                        <button type="button" @click="openShareModal('{{ $report['type'] }}', '{{ $report['label'] }}')"
                                           class="px-3 py-2 text-xs font-bold uppercase tracking-widest text-{{ $report['color'] }}-600 hover:bg-{{ $report['color'] }}-50 rounded-lg border border-{{ $report['color'] }}-200 transition-colors text-center flex items-center justify-center"
                                           title="Share via Email">
                                            <i class="fas fa-share-alt"></i>
                                        </button>
                                    </div>
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

        {{-- Report Scheduling Section --}}
        <div>
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-slate-900 flex items-center">
                    <i class="fas fa-calendar-alt text-indigo-600 mr-3"></i> Report Scheduling
                </h2>
                <button @click="showScheduleModal = true" class="px-6 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest bg-indigo-600 text-white shadow-md hover:bg-indigo-700 hover:shadow-lg transition-all flex items-center">
                    <i class="fas fa-plus mr-2"></i> Schedule Report
                </button>
            </div>

            @if ($schedules->isEmpty())
                <div class="bg-slate-50 rounded-2xl p-8 text-center border border-dashed border-slate-200">
                    <i class="fas fa-clock text-slate-300 text-4xl mb-4"></i>
                    <p class="text-slate-500 font-medium">No automated report schedules configured yet.</p>
                </div>
            @else
                <div class="glass-card rounded-2xl border border-white/60 shadow-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-slate-200 bg-slate-50">
                                    <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-widest text-slate-600">Report Type</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-widest text-slate-600">Recipients</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-widest text-slate-600">Frequency</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-widest text-slate-600">Format</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-widest text-slate-600">Next Execution</th>
                                    <th class="px-6 py-4 text-center text-xs font-bold uppercase tracking-widest text-slate-600">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200">
                                @foreach ($schedules as $schedule)
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center space-x-2">
                                                <i class="fas fa-clock text-indigo-600"></i>
                                                <span class="font-semibold text-slate-900">
                                                    {{ collect($availableReports)->firstWhere('type', $schedule->report_type)['label'] ?? ucwords(str_replace('_', ' ', $schedule->report_type)) }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-slate-600">
                                            {{ $schedule->recipient_email }}
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="px-3 py-1 text-[10px] font-bold uppercase tracking-widest rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200">
                                                {{ $schedule->frequency }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-slate-600 uppercase">
                                            {{ $schedule->format }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-slate-600">
                                            {{ $schedule->next_run_at ? $schedule->next_run_at->format('M d, Y') : 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <form action="{{ route('projects.reporting.schedules.destroy', ['project' => $project->id, 'schedule' => $schedule->id]) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this schedule?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors" title="Delete Schedule">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>

        {{-- Custom Report Builder Section --}}
        <div class="border-t border-slate-200 pt-10" x-data="{
            reportType: '{{ $availableReports->first()['type'] ?? 'unified_gap' }}',
            sections: ['executive_summary', 'metrics', 'table', 'detailed_findings'],
            statusFilter: 'all',
            riskFilter: 'all',
            templateName: '',
            buildQueryString() {
                let params = new URLSearchParams();
                this.sections.forEach(s => params.append('sections[]', s));
                params.append('filters[status]', this.statusFilter);
                params.append('filters[risk]', this.riskFilter);
                return params.toString();
            },
            triggerPreview() {
                window.open(`/projects/{{ $project->id }}/reporting/${this.reportType}?` + this.buildQueryString(), '_blank');
            },
            triggerDownload(format) {
                window.location.href = `/projects/{{ $project->id }}/reporting/${this.reportType}/download?format=${format}&` + this.buildQueryString();
            }
        }">
            <h2 class="text-2xl font-bold text-slate-900 mb-6 flex items-center">
                <i class="fas fa-tools text-sky-600 mr-3"></i> Custom Report Builder
            </h2>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
                {{-- Builder Form Panel --}}
                <div class="lg:col-span-2 glass-card rounded-2xl p-8 border border-white/60 shadow-lg">
                    <h3 class="text-lg font-bold text-slate-800 mb-4 flex items-center">
                        <i class="fas fa-sliders-h text-slate-500 mr-2"></i> Configuration
                    </h3>
                    <div class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-black uppercase tracking-wider text-slate-400 mb-1.5">Select Report Type</label>
                                <select x-model="reportType" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800 focus:outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500 transition-all">
                                    @foreach ($availableReports as $report)
                                        @if(!($report['disabled'] ?? false))
                                            <option value="{{ $report['type'] }}">{{ $report['label'] }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-xs font-black uppercase tracking-wider text-slate-400 mb-1.5">Compliance Filter</label>
                                <select x-model="statusFilter" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800 focus:outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500 transition-all">
                                    <option value="all">All Findings</option>
                                    <option value="compliant">Compliant Only</option>
                                    <option value="non_compliant">Non-Compliant Only</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-black uppercase tracking-wider text-slate-400 mb-2">Sections to Include</label>
                                <div class="space-y-2.5">
                                    <label class="flex items-center space-x-2.5 cursor-pointer">
                                        <input type="checkbox" value="executive_summary" x-model="sections" class="w-4.5 h-4.5 text-sky-600 bg-slate-50 border-slate-300 rounded focus:ring-sky-500">
                                        <span class="text-sm font-semibold text-slate-700">Executive Summary</span>
                                    </label>
                                    <label class="flex items-center space-x-2.5 cursor-pointer">
                                        <input type="checkbox" value="metrics" x-model="sections" class="w-4.5 h-4.5 text-sky-600 bg-slate-50 border-slate-300 rounded focus:ring-sky-500">
                                        <span class="text-sm font-semibold text-slate-700">Postures & Metrics Breakdown</span>
                                    </label>
                                    <label class="flex items-center space-x-2.5 cursor-pointer">
                                        <input type="checkbox" value="table" x-model="sections" class="w-4.5 h-4.5 text-sky-600 bg-slate-50 border-slate-300 rounded focus:ring-sky-500">
                                        <span class="text-sm font-semibold text-slate-700">Table of Findings Mapping</span>
                                    </label>
                                    <label class="flex items-center space-x-2.5 cursor-pointer">
                                        <input type="checkbox" value="detailed_findings" x-model="sections" class="w-4.5 h-4.5 text-sky-600 bg-slate-50 border-slate-300 rounded focus:ring-sky-500">
                                        <span class="text-sm font-semibold text-slate-700">Detailed Observations</span>
                                    </label>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-black uppercase tracking-wider text-slate-400 mb-1.5">Risk Rating Filter</label>
                                <select x-model="riskFilter" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800 focus:outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500 transition-all">
                                    <option value="all">All Risks</option>
                                    <option value="High">High Risk Only</option>
                                    <option value="Medium">Medium Risk Only</option>
                                    <option value="Low">Low Risk Only</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Action / Save Template Panel --}}
                <div class="glass-card rounded-2xl p-8 border border-white/60 shadow-lg flex flex-col justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-slate-800 mb-4 flex items-center">
                            <i class="fas fa-file-export text-slate-500 mr-2"></i> Actions
                        </h3>
                        <div class="space-y-3">
                            <button type="button" @click="triggerPreview()" class="w-full py-3 text-xs font-black uppercase tracking-widest text-sky-600 hover:bg-sky-50 rounded-xl border border-sky-200 transition-all text-center flex items-center justify-center">
                                <i class="fas fa-eye mr-2"></i> HTML Preview
                            </button>
                            <button type="button" @click="triggerDownload('pdf')" class="w-full py-3 text-xs font-black uppercase tracking-widest text-white bg-sky-600 hover:bg-sky-700 rounded-xl shadow-md hover:shadow-lg transition-all text-center flex items-center justify-center">
                                <i class="fas fa-file-pdf mr-2"></i> Export Custom PDF
                            </button>
                        </div>
                    </div>

                    <div class="border-t border-slate-200 pt-6 mt-6">
                        <form action="{{ route('projects.reporting.custom-templates.store', $project) }}" method="POST">
                            @csrf
                            <input type="hidden" name="report_type" :value="reportType">
                            <template x-for="sec in sections" :key="sec">
                                <input type="hidden" name="sections[]" :value="sec">
                            </template>
                            <input type="hidden" name="filters[status]" :value="statusFilter">
                            <input type="hidden" name="filters[risk]" :value="riskFilter">

                            <label class="block text-xs font-black uppercase tracking-wider text-slate-400 mb-1.5">Save Configuration as Template</label>
                            <div class="flex space-x-2">
                                <input type="text" name="name" required placeholder="Template Name (e.g. Critical Gaps Only)" class="flex-1 px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-semibold text-slate-800 focus:outline-none focus:border-sky-500 transition-all">
                                <button type="submit" class="px-4 py-2 text-xs font-bold uppercase tracking-widest text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-all">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Saved Custom Templates List --}}
            <h3 class="text-lg font-bold text-slate-800 mb-4 flex items-center">
                <i class="fas fa-bookmark text-amber-500 mr-2"></i> Saved Custom Templates
            </h3>

            @if ($customTemplates->isEmpty())
                <div class="bg-slate-50 rounded-2xl p-6 text-center border border-dashed border-slate-200">
                    <p class="text-slate-500 text-sm font-medium">No saved custom templates found. Use the builder above to save your first configuration.</p>
                </div>
            @else
                <div class="glass-card rounded-2xl border border-white/60 shadow-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-slate-200 bg-slate-50">
                                    <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-widest text-slate-600">Template Name</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-widest text-slate-600">Report Type</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-widest text-slate-600">Included Sections</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-widest text-slate-600">Filters Applied</th>
                                    <th class="px-6 py-4 text-center text-xs font-bold uppercase tracking-widest text-slate-600">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200">
                                @foreach ($customTemplates as $template)
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="px-6 py-4">
                                            <span class="font-semibold text-slate-900">{{ $template->name }}</span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-slate-600">
                                            {{ collect($availableReports)->firstWhere('type', $template->report_type)['label'] ?? ucwords(str_replace('_', ' ', $template->report_type)) }}
                                        </td>
                                        <td class="px-6 py-4 text-xs text-slate-500 leading-relaxed">
                                            @foreach ($template->sections as $sec)
                                                <span class="inline-block bg-slate-100 text-slate-700 px-2 py-0.5 rounded mr-1 mb-1 font-semibold">
                                                    {{ ucwords(str_replace('_', ' ', $sec)) }}
                                                </span>
                                            @endforeach
                                        </td>
                                        <td class="px-6 py-4 text-xs text-slate-500">
                                            @if (isset($template->filters['status']) && $template->filters['status'] !== 'all')
                                                <span class="inline-block bg-sky-50 text-sky-700 border border-sky-100 px-2 py-0.5 rounded font-semibold">
                                                    Status: {{ ucwords(str_replace('_', ' ', $template->filters['status'])) }}
                                                </span>
                                            @endif
                                            @if (isset($template->filters['risk']) && $template->filters['risk'] !== 'all')
                                                <span class="inline-block bg-amber-50 text-amber-700 border border-amber-100 px-2 py-0.5 rounded font-semibold ml-1">
                                                    Risk: {{ $template->filters['risk'] }}
                                                </span>
                                            @endif
                                            @if ((!isset($template->filters['status']) || $template->filters['status'] === 'all') && (!isset($template->filters['risk']) || $template->filters['risk'] === 'all'))
                                                <span class="text-slate-400 italic">None</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <div class="flex justify-center space-x-2">
                                                <a href="{{ route('projects.reporting.custom-templates.download', ['project' => $project->id, 'template' => $template->id, 'format' => 'html']) }}"
                                                   target="_blank"
                                                   class="p-2 text-slate-400 hover:text-sky-600 hover:bg-sky-50 rounded-lg transition-colors"
                                                   title="Preview HTML">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('projects.reporting.custom-templates.download', ['project' => $project->id, 'template' => $template->id, 'format' => 'pdf']) }}"
                                                   class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors"
                                                   title="Download PDF">
                                                    <i class="fas fa-file-pdf"></i>
                                                </a>
                                                <form action="{{ route('projects.reporting.custom-templates.destroy', ['project' => $project->id, 'template' => $template->id]) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this template?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors" title="Delete Template">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>

    </div>

    {{-- Share Modal --}}
    <div x-show="showShareModal" 
         class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         style="display: none;">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showShareModal = false"></div>

        <div class="relative bg-white rounded-2xl shadow-2xl border border-slate-100 w-full max-w-lg p-8 m-4 z-50">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-slate-900 flex items-center">
                    <i class="fas fa-paper-plane text-sky-600 mr-3"></i> Share Report via Email
                </h3>
                <button @click="showShareModal = false" class="text-slate-400 hover:text-slate-600 transition">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <form :action="'/projects/{{ $project->id }}/reporting/' + shareReportType + '/share'" method="POST">
                @csrf
                <div class="space-y-5">
                    <div>
                        <label class="block text-xs font-black uppercase tracking-wider text-slate-400 mb-1">Report</label>
                        <input type="text" :value="shareReportLabel" disabled class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-600">
                    </div>

                    <div>
                        <label class="block text-xs font-black uppercase tracking-wider text-slate-400 mb-1">Recipient Email(s)</label>
                        <input type="text" name="email" required placeholder="compliance@company.com, auditor@company.com" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800 placeholder-slate-400 focus:outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500 transition-all">
                        <p class="text-[10px] text-slate-400 mt-1">Separate multiple emails with commas.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-black uppercase tracking-wider text-slate-400 mb-1">Custom Message / Note</label>
                        <textarea name="message" rows="3" placeholder="Hi team, please find attached the latest compliance report." class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800 placeholder-slate-400 focus:outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500 transition-all"></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-black uppercase tracking-wider text-slate-400 mb-2">Attachments</label>
                        <div class="flex space-x-6">
                            <label class="flex items-center space-x-2.5 cursor-pointer">
                                <input type="checkbox" name="formats[]" value="pdf" checked class="w-4.5 h-4.5 text-sky-600 bg-slate-50 border-slate-300 rounded focus:ring-sky-500">
                                <span class="text-sm font-semibold text-slate-700">PDF Report</span>
                            </label>
                            <label class="flex items-center space-x-2.5 cursor-pointer">
                                <input type="checkbox" name="formats[]" value="html" class="w-4.5 h-4.5 text-sky-600 bg-slate-50 border-slate-300 rounded focus:ring-sky-500">
                                <span class="text-sm font-semibold text-slate-700">HTML Summary</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="flex space-x-3 mt-8">
                    <button type="button" @click="showShareModal = false" class="flex-1 py-3 text-xs font-black uppercase tracking-widest text-slate-500 bg-slate-100 hover:bg-slate-200 rounded-xl transition-all text-center">Cancel</button>
                    <button type="submit" class="flex-1 py-3 text-xs font-black uppercase tracking-widest text-white bg-sky-600 hover:bg-sky-700 rounded-xl shadow-lg hover:shadow-xl transition-all text-center">Share</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Schedule Creator Modal --}}
    <div x-show="showScheduleModal" 
         class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         style="display: none;">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showScheduleModal = false"></div>

        <div class="relative bg-white rounded-2xl shadow-2xl border border-slate-100 w-full max-w-lg p-8 m-4 z-50">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-slate-900 flex items-center">
                    <i class="fas fa-calendar-plus text-indigo-600 mr-3"></i> Configure Report Schedule
                </h3>
                <button @click="showScheduleModal = false" class="text-slate-400 hover:text-slate-600 transition">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <form action="{{ route('projects.reporting.schedules.store', $project) }}" method="POST">
                @csrf
                <div class="space-y-5">
                    <div>
                        <label class="block text-xs font-black uppercase tracking-wider text-slate-400 mb-1">Select Report</label>
                        <select name="report_type" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:indigo-sky-500 transition-all">
                            @foreach ($availableReports as $report)
                                @if(!($report['disabled'] ?? false))
                                    <option value="{{ $report['type'] }}">{{ $report['label'] }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-black uppercase tracking-wider text-slate-400 mb-1">Recipient Email(s)</label>
                        <input type="text" name="recipient_email" required placeholder="compliance-alerts@company.com" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800 placeholder-slate-400 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all">
                        <p class="text-[10px] text-slate-400 mt-1">Separate multiple emails with commas.</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-black uppercase tracking-wider text-slate-400 mb-1">Frequency</label>
                            <select name="frequency" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all">
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-black uppercase tracking-wider text-slate-400 mb-1">Export Format</label>
                            <select name="format" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all">
                                <option value="pdf">PDF Attachment</option>
                                <option value="html">HTML Summary Link</option>
                                <option value="both">Both PDF & HTML</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="flex space-x-3 mt-8">
                    <button type="button" @click="showScheduleModal = false" class="flex-1 py-3 text-xs font-black uppercase tracking-widest text-slate-500 bg-slate-100 hover:bg-slate-200 rounded-xl transition-all text-center">Cancel</button>
                    <button type="submit" class="flex-1 py-3 text-xs font-black uppercase tracking-widest text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-lg hover:shadow-xl transition-all text-center">Schedule</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

