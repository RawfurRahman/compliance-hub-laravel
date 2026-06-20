@extends('layouts.app')

@section('content')
<div class="fade-in-up">
    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between mb-8">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">
                PCI DSS <span class="gradient-text">Requirements</span>
            </h1>
            <p class="mt-1.5 text-sm text-slate-500 font-medium">Manage the PCI DSS requirements library ({{ $requirements->total() }} total)</p>
        </div>
        <a href="{{ route('admin.requirements.create') }}" class="mt-3 sm:mt-0 inline-flex items-center px-4 py-2 text-xs font-bold uppercase tracking-wider text-white btn-premium rounded-xl">
            <i class="fas fa-plus mr-2 text-[10px]"></i> Add Requirement
        </a>
    </div>

    {{-- Search --}}
    <div class="glass-card rounded-2xl p-4 mb-6">
        <form method="GET" class="flex gap-3">
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                    <i class="fas fa-search text-sm text-slate-400"></i>
                </div>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by requirement number or description..." class="form-input pl-9">
            </div>
            <button type="submit" class="px-4 py-2 text-sm font-semibold text-white bg-sky-500 rounded-xl hover:bg-sky-600 transition-colors">Search</button>
            @if(request('search'))
                <a href="{{ route('admin.requirements.index') }}" class="px-4 py-2 text-sm font-semibold text-slate-600 bg-slate-100 rounded-xl hover:bg-slate-200 transition-colors">Clear</a>
            @endif
        </form>
    </div>

    {{-- Requirements Table --}}
    <div class="glass-card rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-slate-100">
                        <th class="text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest px-6 py-3 w-28">Req #</th>
                        <th class="text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest px-6 py-3">Description</th>
                        <th class="text-center text-[10px] font-bold text-slate-400 uppercase tracking-widest px-6 py-3 w-28">Procedures</th>
                        <th class="text-right text-[10px] font-bold text-slate-400 uppercase tracking-widest px-6 py-3 w-28">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($requirements as $req)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-indigo-50 text-indigo-700 text-xs font-bold font-mono">
                                {{ $req->req_num }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-sm text-slate-700 leading-relaxed line-clamp-2">{{ $req->req_description }}</p>
                        </td>
                        <td class="px-6 py-4 text-center">
                            @php $procCount = is_array($req->testing_procedures) ? count($req->testing_procedures) : 0; @endphp
                            <span class="badge badge-slate">{{ $procCount }}</span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.requirements.edit', $req) }}" class="w-8 h-8 rounded-lg bg-slate-50 border border-slate-200 flex items-center justify-center text-slate-400 hover:text-sky-500 hover:border-sky-200 transition-all text-xs">
                                    <i class="fas fa-pen"></i>
                                </a>
                                <form action="{{ route('admin.requirements.destroy', $req) }}" method="POST" onsubmit="return confirm('Delete this requirement?');">
                                    @csrf @method('DELETE')
                                    <button class="w-8 h-8 rounded-lg bg-slate-50 border border-slate-200 flex items-center justify-center text-slate-400 hover:text-rose-500 hover:border-rose-200 transition-all text-xs">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <div class="w-12 h-12 rounded-xl bg-slate-100 flex items-center justify-center text-slate-300 mb-3">
                                    <i class="fas fa-list-check text-xl"></i>
                                </div>
                                <p class="text-sm font-semibold text-slate-400">No requirements found</p>
                                <p class="text-xs text-slate-300 mt-1">Add your first PCI DSS requirement</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($requirements->hasPages())
        <div class="px-6 py-4 border-t border-slate-100">
            {{ $requirements->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
