@extends('layouts.app')

@section('content')
<div class="fade-in-up max-w-2xl mx-auto">
    <div class="mb-8">
        <a href="{{ route('governance.policies.index', $project) }}" class="text-sm text-slate-500 hover:text-slate-700">
            <i class="fas fa-arrow-left mr-1"></i> Back to Policies
        </a>
    </div>

    <div class="glass-card rounded-2xl p-8">
        <h2 class="text-xl font-bold text-slate-900 mb-2">Bulk Import Policies</h2>
        <p class="text-sm text-slate-500 mb-6">
            Upload multiple policy PDFs at once. The system will use AI to extract titles, descriptions, approval dates, and approvers — then let you review before saving.
        </p>

        @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm mb-6">
                <i class="fas fa-exclamation-circle mr-1.5 text-red-500"></i>
                {{ session('error') }}
            </div>
        @endif

        <form action="{{ route('governance.policies.bulk.upload', $project) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Select PDF Files</label>
                <input type="file" name="files[]" multiple accept="application/pdf"
                       class="w-full px-4 py-3 border-2 border-dashed border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition-all file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-sky-50 file:text-sky-700 hover:file:bg-sky-100">
                @error('files')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                @enderror
                @error('files.*')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                @enderror
                <p class="text-xs text-slate-400 mt-2">Up to 20 files, 20MB each. Only PDF files are accepted.</p>
            </div>

            <button type="submit"
                    class="px-6 py-2.5 text-sm font-bold text-white bg-gradient-to-r from-sky-500 to-indigo-500 rounded-xl hover:shadow-lg hover:shadow-sky-500/25 transition-all">
                <i class="fas fa-cloud-upload-alt mr-1.5"></i> Upload & Extract
            </button>
        </form>
    </div>
</div>
@endsection
