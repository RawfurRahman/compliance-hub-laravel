@extends('layouts.app')

@section('content')
<div class="fade-in-up">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between mb-8">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">
                Control <span class="gradient-text">Catalog</span>
            </h1>
            <p class="mt-1.5 text-sm text-slate-500 font-medium">Manage internal controls mapped to framework requirements</p>
        </div>
    </div>

    {{-- Add Control Form --}}
    <div class="glass-card rounded-2xl mb-8 overflow-hidden" x-data="{ showForm: false }">
        <button @click="showForm = !showForm" class="w-full flex items-center justify-between px-6 py-4 text-left hover:bg-slate-50/50 transition-colors">
            <div class="flex items-center gap-3">
                <div class="icon-badge icon-badge-sky">
                    <i class="fas fa-plus text-sm"></i>
                </div>
                <span class="text-sm font-bold text-slate-700">Add New Control</span>
            </div>
            <i class="fas fa-chevron-down text-slate-400 text-xs transition-transform" :class="{ 'rotate-180': showForm }"></i>
        </button>

        <div x-show="showForm" x-transition class="border-t border-slate-100 px-6 py-5" x-cloak>
            <form action="{{ route('admin.controls.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                @csrf
                <div>
                    <label class="form-label">Code</label>
                    <input type="text" name="code" required placeholder="e.g. DC-001" class="form-input">
                </div>
                <div>
                    <label class="form-label">Title</label>
                    <input type="text" name="title" required placeholder="e.g. Data Classification Policy" class="form-input">
                </div>
                <div>
                    <label class="form-label">Framework</label>
                    <select name="framework_id" class="form-input">
                        <option value="">-- None --</option>
                        @foreach($frameworks as $fw)
                            <option value="{{ $fw->id }}">{{ $fw->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Owner</label>
                    <select name="control_owner_id" class="form-input">
                        <option value="">-- Select --</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}">{{ $u->name ?? $u->email }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="form-label">Description</label>
                    <textarea name="description" rows="2" class="form-input" placeholder="Describe the control"></textarea>
                </div>
                <div>
                    <label class="form-label">Effectiveness Score (%)</label>
                    <input type="number" name="effectiveness_score" min="0" max="100" step="0.1" value="0" class="form-input">
                </div>
                <div>
                    <label class="form-label">Status</label>
                    <select name="status" class="form-input">
                        <option value="active">Active</option>
                        <option value="draft">Draft</option>
                        <option value="deprecated">Deprecated</option>
                    </select>
                </div>
                <div class="md:col-span-2 lg:col-span-4 flex justify-end pt-2">
                    <button type="submit" class="btn-primary text-sm px-6">
                        <i class="fas fa-save mr-2"></i> Save Control
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Controls Table --}}
    <div class="glass-card rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bg-slate-50/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Code</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Title</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Framework</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Owner</th>
                        <th class="px-4 py-3 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">Effectiveness</th>
                        <th class="px-4 py-3 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($controls as $c)
                        <tr class="hover:bg-slate-50/30 transition-colors">
                            <td class="px-4 py-3">
                                <span class="font-mono text-sm font-bold text-slate-800">{{ $c->code ?? $c->control_code }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-sm font-medium text-slate-700">{{ $c->title ?? $c->name }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-xs text-slate-500">{{ $c->framework?->name ?? '—' }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-xs text-slate-500">{{ $c->controlOwner?->name ?? $c->controlOwner?->email ?? '—' }}</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($c->effectiveness_score > 0)
                                    <span class="inline-flex items-center gap-1 text-xs font-bold px-2.5 py-1 rounded-full
                                        {{ $c->effectiveness_score >= 70 ? 'bg-emerald-100 text-emerald-700' : ($c->effectiveness_score >= 40 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') }}">
                                        {{ $c->effectiveness_score }}%
                                    </span>
                                @else
                                    <span class="text-xs text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center gap-1 text-xs font-bold px-2.5 py-1 rounded-full
                                    {{ $c->status === 'active' ? 'bg-emerald-100 text-emerald-700' : ($c->status === 'draft' ? 'bg-sky-100 text-sky-700' : 'bg-slate-100 text-slate-500') }}">
                                    {{ $c->status ?? 'active' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.controls.edit', $c) }}" class="btn-ghost text-xs">
                                        <i class="fas fa-edit mr-1"></i> Edit
                                    </a>
                                    <form action="{{ route('admin.controls.destroy', $c) }}" method="POST" onsubmit="return confirm('Delete this control?');" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-ghost text-xs text-red-500 hover:text-red-700">
                                            <i class="fas fa-trash mr-1"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center">
                                <p class="text-sm text-slate-400">No controls found. Create your first control above.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
