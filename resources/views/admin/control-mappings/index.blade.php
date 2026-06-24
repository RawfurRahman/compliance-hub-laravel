@extends('layouts.app')

@section('content')
<div class="fade-in-up">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between mb-8">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">
                Control <span class="gradient-text">Mappings</span>
            </h1>
            <p class="mt-1.5 text-sm text-slate-500 font-medium">Review and manage risk-to-control mappings across all projects</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="glass-card rounded-2xl mb-6 p-4">
        <form method="GET" class="flex flex-wrap items-end gap-4">
            <div>
                <label class="form-label text-xs">Status</label>
                <select name="status" class="form-input text-sm" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    @foreach($statuses as $s)
                        <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label text-xs">Risk Entry</label>
                <select name="risk_register_id" class="form-input text-sm" onchange="this.form.submit()">
                    <option value="">All Risks</option>
                    @foreach($risks as $r)
                        <option value="{{ $r->id }}" {{ request('risk_register_id') == $r->id ? 'selected' : '' }}>
                            #{{ $r->serial_no }} — {{ Str::limit($r->asset_process_service, 40) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <a href="{{ route('admin.control-mappings.index') }}" class="btn-ghost text-xs">Clear Filters</a>
            </div>
        </form>
    </div>

    {{-- Mappings Table --}}
    <div class="glass-card rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bg-slate-50/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Risk</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Framework Control</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Local Control</th>
                        <th class="px-4 py-3 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">Confidence</th>
                        <th class="px-4 py-3 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Mapped By</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($mappings as $m)
                        <tr class="hover:bg-slate-50/30 transition-colors">
                            <td class="px-4 py-3">
                                <div class="text-xs font-bold text-slate-700">#{{ $m->risk?->serial_no ?? '?' }}</div>
                                <div class="text-xs text-slate-400">{{ Str::limit($m->risk?->asset_process_service, 30) }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-xs font-medium text-slate-700">{{ $m->frameworkControl?->control_id ?? '—' }}</div>
                                <div class="text-xs text-slate-400">{{ Str::limit($m->frameworkControl?->control_name ?? $m->frameworkControl?->domain, 35) }}</div>
                                <div class="text-xs text-slate-300">{{ $m->frameworkControl?->framework?->name ?? '' }}</div>
                            </td>
                            <td class="px-4 py-3">
                                @if($m->control)
                                    <span class="text-xs font-medium text-slate-700">{{ $m->control->code ?? $m->control->control_code }}</span>
                                @else
                                    <span class="text-xs text-slate-300">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($m->confidence_score)
                                    <span class="text-xs font-bold {{ $m->confidence_score >= 70 ? 'text-emerald-600' : ($m->confidence_score >= 40 ? 'text-amber-600' : 'text-red-600') }}">
                                        {{ $m->confidence_score }}%
                                    </span>
                                @else
                                    <span class="text-xs text-slate-300">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center gap-1 text-xs font-bold px-2.5 py-1 rounded-full
                                    {{ $m->mapping_status === 'confirmed' ? 'bg-emerald-100 text-emerald-700' : '' }}
                                    {{ $m->mapping_status === 'suggested' ? 'bg-amber-100 text-amber-700' : '' }}
                                    {{ $m->mapping_status === 'rejected' ? 'bg-slate-100 text-slate-500' : '' }}">
                                    {{ $m->mapping_status }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-xs text-slate-500">{{ $m->mappedBy?->name ?? $m->mappedBy?->email ?? '—' }}</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if($m->mapping_status === 'suggested')
                                    <div class="flex items-center justify-end gap-1">
                                        <form action="{{ route('admin.control-mappings.confirm', $m) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="btn-ghost text-xs text-emerald-600 hover:text-emerald-800">
                                                <i class="fas fa-check mr-1"></i> Confirm
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.control-mappings.reject', $m) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="btn-ghost text-xs text-red-500 hover:text-red-700">
                                                <i class="fas fa-times mr-1"></i> Reject
                                            </button>
                                        </form>
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400">{{ $m->mapped_at?->diffForHumans() ?? '' }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center">
                                <p class="text-sm text-slate-400">No mappings found.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">
        {{ $mappings->links() }}
    </div>
</div>
@endsection
