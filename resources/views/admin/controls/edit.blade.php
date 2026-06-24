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
</div>
@endsection
