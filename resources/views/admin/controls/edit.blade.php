@extends('layouts.app')

@section('content')
<div class="fade-in-up max-w-2xl mx-auto">
    <div class="mb-8">
        <a href="{{ route('admin.controls.index') }}" class="text-sm text-slate-500 hover:text-slate-700">
            <i class="fas fa-arrow-left mr-1"></i> Back to Catalog
        </a>
    </div>

    <div class="glass-card rounded-2xl p-6">
        <h2 class="text-xl font-bold text-slate-900 mb-6">Edit Control</h2>

        <form action="{{ route('admin.controls.update', $control) }}" method="POST" class="space-y-4">
            @csrf @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Code</label>
                    <input type="text" name="code" value="{{ old('code', $control->code ?? $control->control_code) }}" required class="form-input">
                </div>
                <div>
                    <label class="form-label">Title</label>
                    <input type="text" name="title" value="{{ old('title', $control->title ?? $control->name) }}" required class="form-input">
                </div>
                <div>
                    <label class="form-label">Framework</label>
                    <select name="framework_id" class="form-input">
                        <option value="">-- None --</option>
                        @foreach($frameworks as $fw)
                            <option value="{{ $fw->id }}" {{ $control->framework_id == $fw->id ? 'selected' : '' }}>{{ $fw->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Owner</label>
                    <select name="control_owner_id" class="form-input">
                        <option value="">-- Select --</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}" {{ $control->control_owner_id == $u->id ? 'selected' : '' }}>{{ $u->name ?? $u->email }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="form-label">Description</label>
                    <textarea name="description" rows="3" class="form-input">{{ old('description', $control->description) }}</textarea>
                </div>
                <div>
                    <label class="form-label">Effectiveness Score (%)</label>
                    <input type="number" name="effectiveness_score" min="0" max="100" step="0.1" value="{{ old('effectiveness_score', $control->effectiveness_score) }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Status</label>
                    <select name="status" class="form-input">
                        <option value="active" {{ ($control->status ?? 'active') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="draft" {{ $control->status === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="deprecated" {{ $control->status === 'deprecated' ? 'selected' : '' }}>Deprecated</option>
                    </select>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4">
                <a href="{{ route('admin.controls.index') }}" class="btn-secondary text-sm px-6">Cancel</a>
                <button type="submit" class="btn-primary text-sm px-6">
                    <i class="fas fa-save mr-2"></i> Update Control
                </button>
            </div>
        </form>
    </div>

    {{-- Mapped Compliance Tests --}}
    <div class="glass-card rounded-2xl p-6 mt-8">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold text-slate-900">Mapped Compliance Tests</h2>
            @if($totalCount > 0)
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-bold rounded-full
                    {{ $passingCount === $totalCount ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                    <span class="text-base">{{ $passingCount }}</span>
                    <span class="text-slate-400">/</span>
                    <span class="text-base">{{ $totalCount }}</span>
                    <span class="ml-1">Passing</span>
                </span>
            @endif
        </div>

        @if($complianceTests->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Test Name</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Owner</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Last Run</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($complianceTests as $ct)
                            <tr class="hover:bg-slate-50/30 transition-colors">
                                <td class="px-4 py-3">
                                    <span class="text-sm font-medium text-slate-800">{{ $ct->name }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    @php
                                        $statusColors = [
                                            'Passing' => 'bg-emerald-100 text-emerald-700',
                                            'Overdue' => 'bg-red-100 text-red-700',
                                            'Needs Remediation' => 'bg-orange-100 text-orange-700',
                                            'Due Soon' => 'bg-yellow-100 text-yellow-700',
                                            'Not Yet Run' => 'bg-slate-100 text-slate-500',
                                        ];
                                    @endphp
                                    <span class="inline-flex px-2.5 py-1 text-xs font-bold rounded-full {{ $statusColors[$ct->status] ?? 'bg-slate-100 text-slate-500' }}">
                                        {{ $ct->status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex px-2.5 py-1 text-xs font-bold rounded-full {{ $ct->test_type === 'Automated' ? 'bg-sky-100 text-sky-700' : 'bg-purple-100 text-purple-700' }}">
                                        {{ $ct->test_type }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-sm text-slate-600">{{ $ct->ownerUser?->name ?? '—' }}</span>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-500">
                                    {{ $ct->last_run_at ? $ct->last_run_at->format('M d, Y') : 'Never' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-10">
                <p class="text-sm text-slate-400">No compliance tests mapped to this control yet.</p>
            </div>
        @endif
    </div>
</div>
@endsection
