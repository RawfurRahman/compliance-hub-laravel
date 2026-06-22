@extends('layouts.app')

@section('content')
<div class="fade-in-up">
    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8 gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold text-slate-400 uppercase tracking-widest mb-1.5">
                <a href="{{ route('admin.frameworks.index') }}" class="hover:text-slate-600 transition-colors">Frameworks</a>
                <i class="fas fa-chevron-right text-[8px] text-slate-300"></i>
                <span class="text-slate-500">Controls</span>
            </div>
            <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">
                {{ $framework->name }} <span class="gradient-text">Controls</span>
            </h1>
            <p class="mt-1 text-sm text-slate-500 font-medium">Manage controls and evidence requirements for {{ $framework->name }} {{ $framework->version }}</p>
        </div>
        <div>
            <a href="{{ route('admin.frameworks.index') }}" class="inline-flex items-center px-4 py-2 text-xs font-bold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-all shadow-sm">
                <i class="fas fa-arrow-left mr-2"></i> Back to Library
            </a>
        </div>
    </div>

    {{-- Alert Messages --}}
    @if(session('success'))
        <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-semibold flex items-center gap-3">
            <i class="fas fa-check-circle text-emerald-500 text-lg"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm font-semibold flex items-center gap-3">
            <i class="fas fa-exclamation-circle text-rose-500 text-lg"></i>
            <div>{{ session('error') }}</div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
        {{-- Left: Forms Section --}}
        <div class="space-y-6 lg:col-span-1">
            {{-- Manual Form Card --}}
            <div class="glass-card rounded-2xl overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-3" style="background: #f8fafc;">
                    <div class="icon-badge icon-badge-sky">
                        <i class="fas fa-plus text-xs"></i>
                    </div>
                    <span class="text-sm font-extrabold text-slate-700 uppercase tracking-wider">Add Single Control</span>
                </div>

                <div class="p-6">
                    <form action="{{ route('admin.frameworks.controls.store', $framework) }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label class="form-label text-xs">Control ID <span class="text-rose-500">*</span></label>
                            <input type="text" name="control_id" required placeholder="e.g. A.5.1" class="form-input text-sm">
                            @error('control_id') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="form-label text-xs">Control Domain <span class="text-rose-500">*</span></label>
                            <input type="text" name="domain" required placeholder="e.g. Information Security Policies" class="form-input text-sm">
                            @error('domain') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="form-label text-xs">Requirement Description <span class="text-rose-500">*</span></label>
                            <textarea name="requirement_description" required rows="3" placeholder="Define the requirement description..." class="form-input text-sm"></textarea>
                            @error('requirement_description') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="form-label text-xs">Required Evidence (Optional)</label>
                            <textarea name="required_evidence" rows="2" placeholder="Evidence documents checklist..." class="form-input text-sm"></textarea>
                            @error('required_evidence') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <button type="submit" class="w-full py-2.5 px-4 text-sm font-bold text-white bg-gradient-to-r from-sky-500 to-indigo-500 rounded-xl hover:shadow-lg hover:shadow-sky-500/25 transition-all flex items-center justify-center gap-2">
                            <i class="fas fa-plus"></i> Save Control
                        </button>
                    </form>
                </div>
            </div>

            {{-- Bulk Excel Upload Card --}}
            <div class="glass-card rounded-2xl overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-3" style="background: #f8fafc;">
                    <div class="icon-badge icon-badge-indigo">
                        <i class="fas fa-file-excel text-xs"></i>
                    </div>
                    <span class="text-sm font-extrabold text-slate-700 uppercase tracking-wider">Excel Bulk Import</span>
                </div>

                <div class="p-6">
                    <form action="{{ route('admin.frameworks.controls.import', $framework) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <div>
                            <label class="form-label text-xs">Select Spreadsheet (XLSX, XLS, CSV) <span class="text-rose-500">*</span></label>
                            <input type="file" name="file" required accept=".xlsx,.xls,.csv" class="form-input text-sm p-1.5">
                            @error('file') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        
                        <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/60 text-[11px] text-slate-500 leading-normal">
                            <span class="font-bold text-slate-700 block mb-1">Flexible Column Header Support:</span>
                            Columns are matched dynamically by name. Try to include fields like:
                            <ul class="list-disc list-inside mt-1 font-mono text-[10px]">
                                <li>Control ID / Control No</li>
                                <li>Domain / Domain Name</li>
                                <li>Requirement Description / Description</li>
                                <li>Required Evidence / Evidence</li>
                            </ul>
                        </div>

                        <button type="submit" class="w-full py-2.5 px-4 text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl transition-all shadow-sm flex items-center justify-center gap-2">
                            <i class="fas fa-upload"></i> Upload & Import
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Right: List Section --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="glass-card rounded-2xl overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between" style="background: #f8fafc;">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-extrabold text-slate-700 uppercase tracking-wider">Framework Controls Library</span>
                        <span class="badge badge-sky text-xs font-bold">{{ $controls->count() }} controls</span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-100">
                                <th class="text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest px-6 py-3 w-[30%]">Domain</th>
                                <th class="text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest px-6 py-3 w-[20%]">Control ID</th>
                                <th class="text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest px-6 py-3 w-[40%]">Description & Evidence</th>
                                <th class="text-right text-[10px] font-bold text-slate-400 uppercase tracking-widest px-6 py-3 w-[10%]">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @forelse($controls as $ctrl)
                            <tr class="hover:bg-slate-50/30 transition-colors">
                                <td class="px-6 py-4 text-xs font-semibold text-slate-700">
                                    {{ $ctrl->domain }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex px-2 py-0.5 rounded bg-slate-100 text-slate-800 border border-slate-200 text-xs font-bold font-mono">{{ $ctrl->control_id }}</span>
                                </td>
                                <td class="px-6 py-4 text-xs space-y-2">
                                    <div>
                                        <span class="font-bold text-slate-400 uppercase text-[9px] tracking-wider block mb-0.5">Requirement</span>
                                        <p class="text-slate-600 font-medium leading-relaxed">{{ $ctrl->requirement_description }}</p>
                                    </div>
                                    @if($ctrl->required_evidence)
                                    <div>
                                        <span class="font-bold text-indigo-400 uppercase text-[9px] tracking-wider block mb-0.5">Required Evidence</span>
                                        <p class="text-indigo-600/80 font-medium leading-relaxed italic bg-indigo-50/20 border border-indigo-100/50 p-2 rounded-lg">{{ $ctrl->required_evidence }}</p>
                                    </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <form action="{{ route('admin.frameworks.controls.destroy', [$framework, $ctrl]) }}" method="POST" onsubmit="return confirm('Delete this control?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="w-8 h-8 rounded-lg bg-slate-50 border border-slate-200 flex items-center justify-center text-slate-400 hover:text-rose-500 hover:border-rose-200 transition-all text-xs" title="Delete Control">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center">
                                        <div class="w-12 h-12 rounded-xl bg-slate-100 flex items-center justify-center text-slate-300 mb-3">
                                            <i class="fas fa-list-check text-xl"></i>
                                        </div>
                                        <p class="text-sm font-semibold text-slate-400">No controls defined yet</p>
                                        <p class="text-xs text-slate-300 mt-1">Upload an Excel sheet or add a control manually to populate this library.</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
