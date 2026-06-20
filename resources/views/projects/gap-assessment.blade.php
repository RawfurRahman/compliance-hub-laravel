@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto">
    {{-- Header --}}
    <div class="mb-10">
        <div class="flex items-center space-x-2 mb-3">
            <a href="{{ route('projects.show', $project) }}" class="text-slate-400 hover:text-sky-600 transition-colors text-xs font-bold uppercase tracking-widest">{{ $project->name }}</a>
            <i class="fas fa-chevron-right text-[10px] text-slate-300"></i>
            <span class="text-sky-600 font-bold text-xs uppercase tracking-widest">Gap Assessment</span>
        </div>
        <h1 class="text-4xl font-extrabold text-slate-900 tracking-tight">Gap <span class="text-sky-600">Assessment</span></h1>
        <p class="mt-2 text-md text-slate-500 font-medium">Identify compliance gaps and track remediation efforts.</p>
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
            <a href="{{ route('projects.gap-assessment', $project) }}" class="px-1 py-4 text-sm font-semibold text-sky-600 border-b-2 border-sky-600">
                Gap Assessment
            </a>
            <a href="{{ route('projects.reporting', $project) }}" class="px-1 py-4 text-sm font-semibold text-slate-600 hover:text-slate-900 border-b-2 border-transparent hover:border-slate-300 transition-colors">
                Reports
            </a>
        </div>
    </div>

    {{-- Gap Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
        <div class="glass-card rounded-2xl p-6 border border-white/60 shadow-lg">
            <p class="text-sm font-semibold text-slate-600 uppercase tracking-widest">Total Requirements</p>
            <p class="text-3xl font-bold text-slate-900 mt-2">{{ $requirementStatus ? count($requirementStatus) : 0 }}</p>
        </div>

        <div class="glass-card rounded-2xl p-6 border border-white/60 shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-semibold text-slate-600 uppercase tracking-widest">Compliant</p>
                    <p class="text-3xl font-bold text-emerald-600 mt-2">
                        {{ $requirementStatus ? collect($requirementStatus)->filter(fn($r) => $r['failed'] == 0)->count() : 0 }}
                    </p>
                </div>
                <i class="fas fa-check-circle text-emerald-600 text-3xl opacity-30"></i>
            </div>
        </div>

        <div class="glass-card rounded-2xl p-6 border border-white/60 shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-semibold text-slate-600 uppercase tracking-widest">In Progress</p>
                    <p class="text-3xl font-bold text-amber-600 mt-2">
                        {{ $requirementStatus ? collect($requirementStatus)->filter(fn($r) => $r['failed'] > 0 && $r['failed'] < $r['total'])->count() : 0 }}
                    </p>
                </div>
                <i class="fas fa-clock text-amber-600 text-3xl opacity-30"></i>
            </div>
        </div>

        <div class="glass-card rounded-2xl p-6 border border-white/60 shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-semibold text-slate-600 uppercase tracking-widest">Critical Gaps</p>
                    <p class="text-3xl font-bold text-rose-600 mt-2">
                        {{ $requirementStatus ? collect($requirementStatus)->filter(fn($r) => $r['failed'] == $r['total'])->count() : 0 }}
                    </p>
                </div>
                <i class="fas fa-exclamation-triangle text-rose-600 text-3xl opacity-30"></i>
            </div>
        </div>
    </div>

    {{-- Requirements Status Table --}}
    <div class="glass-card rounded-2xl border border-white/60 shadow-lg overflow-hidden">
        <div class="p-6 border-b border-slate-200">
            <h2 class="text-2xl font-bold text-slate-900 flex items-center">
                <i class="fas fa-tasks text-sky-600 mr-3"></i> Requirement Status
            </h2>
        </div>

        @if($requirementStatus && count($requirementStatus) > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200">
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-widest text-slate-600">Requirement</th>
                            <th class="px-6 py-4 text-center text-xs font-bold uppercase tracking-widest text-slate-600">Status</th>
                            <th class="px-6 py-4 text-center text-xs font-bold uppercase tracking-widest text-slate-600">Total Tests</th>
                            <th class="px-6 py-4 text-center text-xs font-bold uppercase tracking-widest text-slate-600">Passed</th>
                            <th class="px-6 py-4 text-center text-xs font-bold uppercase tracking-widest text-slate-600">Failed</th>
                            <th class="px-6 py-4 text-center text-xs font-bold uppercase tracking-widest text-slate-600">Not Tested</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @foreach($requirementStatus as $reqId => $status)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4">
                                    <p class="font-semibold text-slate-900">Requirement {{ $reqId }}</p>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    @if($status['failed'] == 0 && $status['not_tested'] == 0)
                                        <span class="px-3 py-1 text-xs font-bold uppercase tracking-widest rounded-full bg-emerald-100 text-emerald-700">
                                            <i class="fas fa-check mr-1"></i> Compliant
                                        </span>
                                    @elseif($status['failed'] > 0)
                                        <span class="px-3 py-1 text-xs font-bold uppercase tracking-widest rounded-full bg-rose-100 text-rose-700">
                                            <i class="fas fa-times mr-1"></i> Non-Compliant
                                        </span>
                                    @else
                                        <span class="px-3 py-1 text-xs font-bold uppercase tracking-widest rounded-full bg-amber-100 text-amber-700">
                                            <i class="fas fa-clock mr-1"></i> In Progress
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-center text-slate-700 font-medium">{{ $status['total'] }}</td>
                                <td class="px-6 py-4 text-center">
                                    <span class="px-3 py-1 text-xs font-bold bg-emerald-100 text-emerald-700 rounded-full">{{ $status['passed'] }}</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="px-3 py-1 text-xs font-bold bg-rose-100 text-rose-700 rounded-full">{{ $status['failed'] }}</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="px-3 py-1 text-xs font-bold bg-slate-100 text-slate-700 rounded-full">{{ $status['not_tested'] }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-12 text-center">
                <i class="fas fa-inbox text-slate-300 text-4xl mb-3"></i>
                <p class="text-slate-500 font-medium">No requirement assessments available yet.</p>
                <p class="text-sm text-slate-400 mt-2">Run a PCI DSS assessment to populate requirement statuses.</p>
            </div>
        @endif
    </div>

    {{-- Remediation Guide --}}
    <div class="mt-10 glass-card rounded-2xl p-8 border border-white/60 shadow-lg">
        <h2 class="text-2xl font-bold text-slate-900 mb-6 flex items-center">
            <i class="fas fa-tools text-amber-600 mr-3"></i> Remediation Guidance
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="p-6 bg-rose-50 border border-rose-200 rounded-lg">
                <h3 class="font-bold text-rose-900 mb-3 flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i> Critical Issues
                </h3>
                <p class="text-sm text-rose-800 mb-4">
                    Requirements marked as "Not in Place" must be remediated immediately to achieve PCI DSS compliance.
                </p>
                <ul class="text-sm text-rose-800 space-y-2">
                    <li>• Create detailed remediation plans</li>
                    <li>• Assign ownership and deadlines</li>
                    <li>• Document all remediation steps</li>
                    <li>• Re-test after remediation</li>
                </ul>
            </div>

            <div class="p-6 bg-amber-50 border border-amber-200 rounded-lg">
                <h3 class="font-bold text-amber-900 mb-3 flex items-center">
                    <i class="fas fa-hourglass-half mr-2"></i> In Progress Items
                </h3>
                <p class="text-sm text-amber-800 mb-4">
                    Partial compliance requires completion of remaining test procedures.
                </p>
                <ul class="text-sm text-amber-800 space-y-2">
                    <li>• Identify missing evidence</li>
                    <li>• Collect and document controls</li>
                    <li>• Re-test incomplete requirements</li>
                    <li>• Track completion dates</li>
                </ul>
            </div>
        </div>

        <div class="mt-8 p-4 bg-sky-50 border border-sky-200 rounded-lg">
            <p class="text-sm text-sky-800">
                <i class="fas fa-lightbulb mr-2"></i>
                <strong>Tip:</strong> Use the Evidence Hub to upload supporting documentation for each remediation step. This will help during re-assessment and prove compliance measures to auditors.
            </p>
        </div>
    </div>
</div>
@endsection
