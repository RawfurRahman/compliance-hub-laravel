@extends('layouts.app')

@section('content')
<div class="fade-in-up max-w-4xl mx-auto">
    <div class="mb-8">
        <h2 class="text-xl font-bold text-slate-900">Trust Centers</h2>
        <p class="text-sm text-slate-500 mt-1">Manage your organization's trust center pages.</p>
    </div>

    @if($trustCenters->count() > 0)
        <div class="glass-card rounded-2xl overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="text-left px-5 py-3 font-semibold text-slate-600">Project</th>
                        <th class="text-left px-5 py-3 font-semibold text-slate-600">Headline</th>
                        <th class="text-left px-5 py-3 font-semibold text-slate-600">Status</th>
                        <th class="text-left px-5 py-3 font-semibold text-slate-600">Public Slug</th>
                        <th class="text-left px-5 py-3 font-semibold text-slate-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($trustCenters as $tc)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-5 py-4 font-medium text-slate-800">{{ $tc->project?->name ?? '—' }}</td>
                            <td class="px-5 py-4 text-slate-600 max-w-[240px] truncate">{{ $tc->headline }}</td>
                            <td class="px-5 py-4">
                                @if($tc->is_published)
                                    <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-bold rounded-full bg-emerald-100 text-emerald-700">
                                        Published
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-bold rounded-full bg-slate-100 text-slate-600">
                                        Draft
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-slate-500 text-xs font-mono">{{ $tc->public_slug }}</td>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('admin.trust-centers.overview', $tc) }}"
                                       class="px-3 py-1.5 text-xs font-bold text-white bg-sky-500 hover:bg-sky-600 rounded-lg transition-colors">
                                        Overview
                                    </a>
                                    <a href="{{ route('admin.trust-centers.edit', $tc) }}"
                                       class="px-3 py-1.5 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors">
                                        Edit
                                    </a>
                                    <a href="{{ route('admin.trust-centers.requests', $tc) }}"
                                       class="px-3 py-1.5 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors">
                                        Requests
                                    </a>
                                    <a href="{{ route('admin.trust-centers.questionnaires', $tc) }}"
                                       class="px-3 py-1.5 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors">
                                        Questionnaires
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="glass-card rounded-2xl p-12 text-center">
            <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-globe text-slate-400 text-xl"></i>
            </div>
            <h3 class="text-lg font-semibold text-slate-700 mb-1">No Trust Centers</h3>
            <p class="text-sm text-slate-500">No trust centers have been created yet.</p>
        </div>
    @endif
</div>
@endsection
