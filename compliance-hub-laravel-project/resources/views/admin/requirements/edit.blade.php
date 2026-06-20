@extends('layouts.app')

@section('content')
<div class="fade-in-up max-w-2xl">
    <div class="mb-6">
        <a href="{{ route('admin.requirements.index') }}" class="inline-flex items-center text-sm text-slate-500 hover:text-sky-500 transition-colors font-medium">
            <i class="fas fa-arrow-left mr-2 text-xs"></i> Back to Requirements
        </a>
    </div>

    <div class="glass-card rounded-2xl overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-100">
            <h1 class="text-xl font-bold text-slate-800">Edit Requirement</h1>
            <p class="text-xs text-slate-400 mt-0.5">Editing requirement <strong>{{ $requirement->req_num }}</strong></p>
        </div>

        <form action="{{ route('admin.requirements.update', $requirement) }}" method="POST" class="px-6 py-5 space-y-5">
            @csrf @method('PUT')
            <div>
                <label class="form-label">Requirement Number</label>
                <input type="text" name="req_num" required value="{{ old('req_num', $requirement->req_num) }}" class="form-input">
                @error('req_num') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="form-label">Description</label>
                <textarea name="req_description" rows="4" required class="form-input">{{ old('req_description', $requirement->req_description) }}</textarea>
                @error('req_description') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="flex justify-end gap-3 pt-3 border-t border-slate-100">
                <a href="{{ route('admin.requirements.index') }}" class="px-4 py-2 text-sm font-semibold text-slate-600 bg-slate-100 rounded-xl hover:bg-slate-200 transition-all">Cancel</a>
                <button type="submit" class="px-5 py-2 text-sm font-semibold text-white bg-gradient-to-r from-sky-500 to-indigo-500 rounded-xl hover:shadow-lg hover:shadow-sky-500/25 transition-all">
                    <i class="fas fa-save mr-1.5 text-xs"></i> Update
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
