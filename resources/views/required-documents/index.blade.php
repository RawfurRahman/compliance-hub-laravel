@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl">
    <div class="mb-8 flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <div class="mb-2 flex items-center gap-2 text-xs font-bold uppercase tracking-[0.18em] text-sky-600">
                <a href="{{ route('projects.show', $project) }}" class="hover:text-sky-800">{{ $project->name }}</a>
                <i class="fas fa-chevron-right text-[9px]"></i>
                <span>Project evidence</span>
            </div>
            <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900">Required Document Lists</h1>
            <p class="mt-2 max-w-2xl text-sm font-medium text-slate-500">Import a Word or Excel requirements list and prepare the required evidence for this project only.</p>
        </div>
        @if(auth()->user()->hasRole('Admin') || auth()->user()->hasRole('Auditor'))
            <button type="button" onclick="document.getElementById('import-panel').classList.toggle('hidden')" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-700">
                <i class="fas fa-file-import mr-1.5"></i> Import Required List
            </button>
        @endif
    </div>

    @if(session('error'))
        <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">{{ session('error') }}</div>
    @endif

    @if(auth()->user()->hasRole('Admin') || auth()->user()->hasRole('Auditor'))
        <div id="import-panel" class="{{ $errors->any() ? '' : 'hidden' }} mb-7 rounded-2xl border border-sky-100 bg-sky-50/60 p-6">
            <form action="{{ route('required-documents.import', $project) }}" method="POST" enctype="multipart/form-data" class="grid gap-4 md:grid-cols-[1fr,1fr,auto] md:items-end">
                @csrf
                <div>
                    <label for="list-name" class="mb-1.5 block text-sm font-bold text-slate-700">List name</label>
                    <input id="list-name" name="name" value="{{ old('name') }}" required placeholder="e.g. ISO 27001 evidence requirements" class="w-full rounded-xl border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500">
                </div>
                <div>
                    <label for="requirement-file" class="mb-1.5 block text-sm font-bold text-slate-700">Source document</label>
                    <input id="requirement-file" name="file" type="file" accept=".docx,.xlsx,.xls,.csv" required class="block w-full rounded-xl border border-slate-300 bg-white text-sm text-slate-600 file:mr-3 file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-slate-700">
                    <p class="mt-1 text-xs text-slate-500">DOCX tables/bullets and Excel/CSV rows are supported.</p>
                </div>
                <button class="rounded-xl bg-sky-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-sky-700">Prepare list</button>
            </form>
        </div>
    @endif

    <div class="grid gap-7 lg:grid-cols-[290px,1fr]">
        <aside class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="mb-3 px-2 text-xs font-bold uppercase tracking-widest text-slate-400">Imported lists</div>
            <div class="space-y-2">
                @forelse($lists as $list)
                    <a href="{{ route('required-documents.show', [$project, $list]) }}" class="block rounded-xl p-3 transition {{ optional($activeList)->id === $list->id ? 'bg-sky-50 text-sky-800 ring-1 ring-sky-200' : 'hover:bg-slate-50 text-slate-700' }}">
                        <p class="truncate text-sm font-bold">{{ $list->name }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $list->documents_count }} documents · {{ $list->created_at->format('M j, Y') }}</p>
                    </a>
                @empty
                    <p class="px-2 py-6 text-sm text-slate-500">No lists have been imported.</p>
                @endforelse
            </div>
        </aside>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            @if($activeList)
                <div class="flex flex-col gap-3 border-b border-slate-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="font-bold text-slate-900">{{ $activeList->name }}</h2>
                        <p class="mt-1 text-sm text-slate-500">Source: {{ $activeList->source_file_name }} · Imported by {{ optional($activeList->importedBy)->username ?? 'Unknown' }}</p>
                    </div>
                    @if(auth()->user()->hasRole('Admin') || auth()->user()->hasRole('Auditor'))
                        <form action="{{ route('required-documents.destroy', [$project, $activeList]) }}" method="POST" onsubmit="return confirm('Delete this list and all of its document requirements?')">
                            @csrf @method('DELETE')
                            <button class="text-sm font-semibold text-rose-600 hover:text-rose-800"><i class="fas fa-trash-can mr-1"></i> Delete list</button>
                        </form>
                    @endif
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wider text-slate-500"><tr><th class="px-5 py-4">#</th><th class="px-5 py-4">Required Document</th><th class="px-5 py-4">Category</th><th class="px-5 py-4">Reference</th><th class="px-5 py-4">Description</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($documents as $document)
                                <tr><td class="px-5 py-4 text-sm font-bold text-slate-400">{{ $document->sort_order }}</td><td class="px-5 py-4 text-sm font-semibold text-slate-800">{{ $document->document_name }}</td><td class="px-5 py-4 text-sm text-slate-600">{{ $document->category ?: '—' }}</td><td class="px-5 py-4 text-sm text-slate-600">{{ $document->reference ?: '—' }}</td><td class="max-w-md px-5 py-4 text-sm leading-6 text-slate-600">{{ $document->description ?: '—' }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="px-6 py-20 text-center"><i class="fas fa-folder-open mb-4 text-4xl text-slate-300"></i><p class="font-semibold text-slate-600">Import a source file to prepare the shared document requirements list.</p></div>
            @endif
        </section>
    </div>
</div>
@endsection
