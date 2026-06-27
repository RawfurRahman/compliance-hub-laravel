@extends('layouts.app')

@section('content')
<div class="fade-in-up max-w-6xl mx-auto">
    <div class="mb-8 flex items-center justify-between">
        <div>
            <a href="{{ route('governance.policies.bulk', $project) }}" class="text-sm text-slate-500 hover:text-slate-700">
                <i class="fas fa-arrow-left mr-1"></i> Back to Upload
            </a>
            <h2 class="text-xl font-bold text-slate-900 mt-2">Review Extracted Policies</h2>
            <p class="text-sm text-slate-500">Review AI-extracted fields for each file, edit if needed, then confirm to create policies.</p>
        </div>
        <span class="text-sm text-slate-400">{{ count($imports) }} file(s)</span>
    </div>

    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm mb-6">
            <i class="fas fa-check-circle mr-1.5 text-emerald-500"></i>
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm mb-6">
            <i class="fas fa-exclamation-circle mr-1.5 text-red-500"></i>
            {{ session('error') }}
        </div>
    @endif

    <form action="{{ route('governance.policies.bulk.confirm', $project) }}" method="POST">
        @csrf
        <div class="space-y-6">
            @foreach($imports as $item)
                <div class="glass-card rounded-2xl overflow-hidden">
                    <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <input type="checkbox" name="confirmed[]" value="{{ $item['id'] }}"
                                   class="w-4 h-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                            <span class="font-semibold text-slate-800 text-sm">{{ $item['original_filename'] }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            @if($item['all_fields_populated'])
                                <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-bold rounded-full bg-emerald-100 text-emerald-700">
                                    <i class="fas fa-check-circle mr-1"></i> All details recorded
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-bold rounded-full bg-amber-100 text-amber-700">
                                    <i class="fas fa-exclamation-triangle mr-1"></i> Details missing
                                </span>
                            @endif
                            @if($item['duplicate_policy_id'])
                                <a href="{{ route('governance.policies.show', [$project, $item['duplicate_policy_id']]) }}"
                                   target="_blank"
                                   class="inline-flex items-center px-2.5 py-0.5 text-xs font-bold rounded-full bg-red-100 text-red-700 hover:bg-red-200">
                                    <i class="fas fa-copy mr-1"></i> Duplicate: {{ $item['duplicate_policy_number'] ?? '#' . $item['duplicate_policy_id'] }}
                                </a>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-bold rounded-full bg-slate-100 text-slate-600">
                                    <i class="fas fa-check mr-1"></i> No duplicate
                                </span>
                            @endif
                        </div>
                    </div>
                    <div class="px-6 py-4 grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1">Title</label>
                            <input type="text" name="items[{{ $item['id'] }}][title]"
                                   value="{{ $item['extracted_title'] }}"
                                   class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1">Approver</label>
                            <input type="text" name="items[{{ $item['id'] }}][approver]"
                                   value="{{ $item['extracted_approver'] }}"
                                   class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-xs font-semibold text-slate-500 mb-1">Description</label>
                            <textarea name="items[{{ $item['id'] }}][description]" rows="2"
                                      class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none">{{ $item['extracted_description'] }}</textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1">Approval Date</label>
                            <input type="date" name="items[{{ $item['id'] }}][approval_date]"
                                   value="{{ $item['extracted_approval_date'] }}"
                                   class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none">
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-8 flex items-center justify-between">
            <p class="text-sm text-slate-500">
                <i class="fas fa-info-circle mr-1 text-slate-400"></i>
                Only checked files will be imported. Edits are applied on confirmation.
            </p>
            <button type="submit"
                    class="px-6 py-2.5 text-sm font-bold text-white bg-gradient-to-r from-emerald-500 to-green-600 rounded-xl hover:shadow-lg hover:shadow-emerald-500/25 transition-all">
                <i class="fas fa-check-double mr-1.5"></i> Confirm Selected
            </button>
        </div>
    </form>
</div>
@endsection
