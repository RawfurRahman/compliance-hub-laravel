@extends('layouts.app')

@section('content')
<div class="fade-in-up">
    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between mb-8">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">
                Framework <span class="gradient-text">Library</span>
            </h1>
            <p class="mt-1.5 text-sm text-slate-500 font-medium">Manage compliance frameworks available for projects</p>
        </div>
    </div>

    {{-- Add Framework Form --}}
    <div class="glass-card rounded-2xl mb-8 overflow-hidden" x-data="{ showForm: false }">
        <button @click="showForm = !showForm" class="w-full flex items-center justify-between px-6 py-4 text-left hover:bg-slate-50/50 transition-colors">
            <div class="flex items-center gap-3">
                <div class="icon-badge icon-badge-sky">
                    <i class="fas fa-plus text-sm"></i>
                </div>
                <span class="text-sm font-bold text-slate-700">Add New Framework</span>
            </div>
            <i class="fas fa-chevron-down text-slate-400 text-xs transition-transform" :class="{ 'rotate-180': showForm }"></i>
        </button>

        <div x-show="showForm" x-transition class="border-t border-slate-100 px-6 py-5" x-cloak>
            <form action="{{ route('admin.frameworks.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                @csrf
                <div>
                    <label class="form-label">Name</label>
                    <input type="text" name="name" required placeholder="e.g. PCI DSS" class="form-input">
                </div>
                <div>
                    <label class="form-label">Slug</label>
                    <input type="text" name="slug" required placeholder="e.g. pci_dss" class="form-input">
                </div>
                <div>
                    <label class="form-label">Version</label>
                    <input type="text" name="version" placeholder="e.g. v4.0.1" class="form-input">
                </div>
                <div>
                    <label class="form-label">Description</label>
                    <input type="text" name="description" placeholder="Short description" class="form-input">
                </div>
                <div class="md:col-span-2 lg:col-span-4 flex items-center justify-between">
                    <label class="flex items-center gap-2 text-sm text-slate-600 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" checked class="rounded border-slate-300 text-sky-500 focus:ring-sky-400">
                        <span class="font-medium">Active</span>
                    </label>
                    <button type="submit" class="px-5 py-2 text-sm font-semibold text-white bg-gradient-to-r from-sky-500 to-indigo-500 rounded-xl hover:shadow-lg hover:shadow-sky-500/25 transition-all">
                        <i class="fas fa-plus mr-1.5 text-xs"></i> Add Framework
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Frameworks Table --}}
    <div class="glass-card rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-slate-100">
                        <th class="text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest px-6 py-3">Name</th>
                        <th class="text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest px-6 py-3">Slug</th>
                        <th class="text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest px-6 py-3">Version</th>
                        <th class="text-center text-[10px] font-bold text-slate-400 uppercase tracking-widest px-6 py-3">Status</th>
                        <th class="text-right text-[10px] font-bold text-slate-400 uppercase tracking-widest px-6 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($frameworks as $fw)
                    <tr class="hover:bg-slate-50/50 transition-colors" x-data="{ editing: false }">
                        {{-- Display Mode --}}
                        <template x-if="!editing">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-500">
                                        <i class="fas fa-cube text-sm"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-slate-800">{{ $fw->name }}</p>
                                        @if($fw->description)
                                            <p class="text-xs text-slate-400 mt-0.5 max-w-xs truncate">{{ $fw->description }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </template>
                        <template x-if="!editing">
                            <td class="px-6 py-4">
                                <code class="text-xs bg-slate-100 text-slate-600 px-2 py-0.5 rounded-md font-mono">{{ $fw->slug }}</code>
                            </td>
                        </template>
                        <template x-if="!editing">
                            <td class="px-6 py-4">
                                @if($fw->version)
                                    <span class="badge badge-sky">{{ $fw->version }}</span>
                                @else
                                    <span class="text-xs text-slate-300">—</span>
                                @endif
                            </td>
                        </template>
                        <template x-if="!editing">
                            <td class="px-6 py-4 text-center">
                                @if($fw->is_active)
                                    <span class="badge badge-emerald"><i class="fas fa-circle text-[6px] mr-1"></i> Active</span>
                                @else
                                    <span class="badge badge-slate"><i class="fas fa-circle text-[6px] mr-1"></i> Inactive</span>
                                @endif
                            </td>
                        </template>
                        <template x-if="!editing">
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.frameworks.controls.index', $fw) }}" class="w-8 h-8 rounded-lg bg-slate-50 border border-slate-200 flex items-center justify-center text-slate-400 hover:text-indigo-500 hover:border-indigo-200 transition-all text-xs" title="Manage Controls">
                                        <i class="fas fa-list-ul"></i>
                                    </a>
                                    <button @click="editing = true" class="w-8 h-8 rounded-lg bg-slate-50 border border-slate-200 flex items-center justify-center text-slate-400 hover:text-sky-500 hover:border-sky-200 transition-all text-xs">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                    <form action="{{ route('admin.frameworks.destroy', $fw) }}" method="POST" onsubmit="return confirm('Delete this framework?');">
                                        @csrf @method('DELETE')
                                        <button class="w-8 h-8 rounded-lg bg-slate-50 border border-slate-200 flex items-center justify-center text-slate-400 hover:text-rose-500 hover:border-rose-200 transition-all text-xs">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </template>

                        {{-- Edit Mode --}}
                        <template x-if="editing">
                            <td colspan="5" class="px-6 py-4">
                                <form action="{{ route('admin.frameworks.update', $fw) }}" method="POST" class="flex flex-wrap items-end gap-3">
                                    @csrf @method('PUT')
                                    <div class="flex-1 min-w-[140px]">
                                        <label class="form-label text-xs">Name</label>
                                        <input type="text" name="name" value="{{ $fw->name }}" required class="form-input text-sm">
                                    </div>
                                    <div class="w-32">
                                        <label class="form-label text-xs">Slug</label>
                                        <input type="text" name="slug" value="{{ $fw->slug }}" required class="form-input text-sm">
                                    </div>
                                    <div class="w-24">
                                        <label class="form-label text-xs">Version</label>
                                        <input type="text" name="version" value="{{ $fw->version }}" class="form-input text-sm">
                                    </div>
                                    <div class="flex-1 min-w-[140px]">
                                        <label class="form-label text-xs">Description</label>
                                        <input type="text" name="description" value="{{ $fw->description }}" class="form-input text-sm">
                                    </div>
                                    <label class="flex items-center gap-1.5 text-xs text-slate-600 cursor-pointer pb-2">
                                        <input type="checkbox" name="is_active" value="1" {{ $fw->is_active ? 'checked' : '' }} class="rounded border-slate-300 text-sky-500 focus:ring-sky-400">
                                        Active
                                    </label>
                                    <div class="flex gap-2 pb-0.5">
                                        <button type="submit" class="px-3 py-2 text-xs font-semibold text-white bg-sky-500 rounded-lg hover:bg-sky-600 transition-colors">Save</button>
                                        <button type="button" @click="editing = false" class="px-3 py-2 text-xs font-semibold text-slate-600 bg-slate-100 rounded-lg hover:bg-slate-200 transition-colors">Cancel</button>
                                    </div>
                                </form>
                            </td>
                        </template>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <div class="w-12 h-12 rounded-xl bg-slate-100 flex items-center justify-center text-slate-300 mb-3">
                                    <i class="fas fa-cubes text-xl"></i>
                                </div>
                                <p class="text-sm font-semibold text-slate-400">No frameworks yet</p>
                                <p class="text-xs text-slate-300 mt-1">Add your first compliance framework above</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
