@extends('layouts.app')

@section('content')
<div class="fade-in-up max-w-2xl mx-auto">
    {{-- Tab Navigation --}}
    <div class="flex gap-4 mb-6 border-b border-slate-200">
        <a href="{{ route('admin.trust-centers.overview', $trustCenter) }}"
           class="pb-3 text-sm font-semibold text-slate-500 hover:text-slate-700">
           Overview
        </a>
        <a href="{{ route('admin.trust-centers.edit', $trustCenter) }}"
           class="pb-3 text-sm font-semibold text-sky-600 border-b-2 border-sky-600">
           Settings
        </a>
        <a href="{{ route('admin.trust-centers.requests', $trustCenter) }}"
           class="pb-3 text-sm font-semibold text-slate-500 hover:text-slate-700">
           Requests
        </a>
        <a href="{{ route('admin.trust-centers.questionnaires', $trustCenter) }}"
           class="pb-3 text-sm font-semibold text-slate-500 hover:text-slate-700">
           Questionnaires
        </a>
    </div>

    <div class="glass-card rounded-2xl p-6">
        <h2 class="text-xl font-bold text-slate-900 mb-6">Edit Trust Center</h2>

        <form action="{{ route('admin.trust-centers.update', $trustCenter) }}" method="POST" class="space-y-4">
            @csrf @method('PUT')

            <div>
                <label class="form-label">Headline</label>
                <input type="text" name="headline" value="{{ old('headline', $trustCenter->headline) }}" required class="form-input">
                @error('headline')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="form-label">Summary</label>
                <textarea name="summary" rows="4" required class="form-input">{{ old('summary', $trustCenter->summary) }}</textarea>
                @error('summary')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="form-label">Contact Email</label>
                <input type="email" name="contact_email" value="{{ old('contact_email', $trustCenter->contact_email) }}" class="form-input">
                @error('contact_email')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center gap-3">
                <input type="checkbox" name="is_published" id="is_published" value="1"
                    {{ $trustCenter->is_published ? 'checked' : '' }}
                    class="w-5 h-5 rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                <label for="is_published" class="form-label mb-0">Published</label>
            </div>

            {{-- Framework Visibility --}}
            @if($assessments->count() > 0)
                <div class="pt-4 border-t border-slate-100">
                    <label class="form-label block mb-3">Framework Visibility</label>
                    <p class="text-xs text-slate-400 mb-4">
                        Select which frameworks to show publicly on the trust center page.
                    </p>
                    <div class="space-y-2">
                        @foreach($assessments as $assessment)
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox"
                                       name="framework_visibility[{{ $assessment->id }}]"
                                       value="1"
                                       {{ $assessment->is_publicly_visible ? 'checked' : '' }}
                                       class="w-4 h-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                                <span class="text-sm text-slate-700 font-medium">
                                    {{ $assessment->framework?->name ?? 'Unknown Framework' }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="flex justify-end gap-3 pt-4">
                <a href="{{ url('/') }}" class="btn-secondary text-sm px-6">Cancel</a>
                <button type="submit" class="btn-primary text-sm px-6">
                    <i class="fas fa-save mr-2"></i> Save
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
