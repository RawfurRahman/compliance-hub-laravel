@extends('layouts.app')

@section('content')
<div class="fade-in-up max-w-5xl mx-auto">
    {{-- Tab Navigation --}}
    <div class="flex gap-4 mb-6 border-b border-slate-200">
        <a href="{{ route('admin.trust-centers.overview', $trustCenter) }}"
           class="pb-3 text-sm font-semibold text-slate-500 hover:text-slate-700">
           Overview
        </a>
        <a href="{{ route('admin.trust-centers.edit', $trustCenter) }}"
           class="pb-3 text-sm font-semibold text-slate-500 hover:text-slate-700">
           Settings
        </a>
        <a href="{{ route('admin.trust-centers.requests', $trustCenter) }}"
           class="pb-3 text-sm font-semibold text-slate-500 hover:text-slate-700">
           Requests
        </a>
        <a href="{{ route('admin.trust-centers.questionnaires', $trustCenter) }}"
           class="pb-3 text-sm font-semibold text-sky-600 border-b-2 border-sky-600">
           Questionnaires
        </a>
    </div>

    <div class="mb-6">
        <h2 class="text-xl font-bold text-slate-900">Client Questionnaires</h2>
    </div>

    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm mb-6">
            <i class="fas fa-check-circle mr-1.5 text-emerald-500"></i>
            {{ session('success') }}
        </div>
    @endif

    @if($questionnaires->count() > 0)
        <div class="space-y-6">
            @foreach($questionnaires as $q)
                <div class="glass-card rounded-2xl overflow-hidden">
                    <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                        <div>
                            <span class="font-semibold text-slate-800">{{ $q->requester_name }}</span>
                            <span class="text-slate-500 mx-2">&middot;</span>
                            <span class="text-slate-600">{{ $q->requester_email }}</span>
                            @if($q->requester_company)
                                <span class="text-slate-500 mx-2">&middot;</span>
                                <span class="text-slate-600">{{ $q->requester_company }}</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-3">
                            @php
                                $statusColors = [
                                    'Submitted' => 'bg-amber-100 text-amber-700',
                                    'In Review' => 'bg-blue-100 text-blue-700',
                                    'Responded' => 'bg-emerald-100 text-emerald-700',
                                ];
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-bold rounded-full {{ $statusColors[$q->status] ?? 'bg-slate-100 text-slate-600' }}">
                                {{ $q->status }}
                            </span>
                            <span class="text-xs text-slate-400">
                                {{ $q->submitted_at?->format('M d, Y g:i A') ?? $q->created_at->format('M d, Y g:i A') }}
                            </span>
                        </div>
                    </div>
                    <div class="px-6 py-4 space-y-4">
                        @if(is_array($q->responses))
                            @foreach($q->responses as $response)
                                <div>
                                    <p class="text-sm font-semibold text-slate-700 mb-1">{{ $response['question'] ?? 'Unknown Question' }}</p>
                                    <p class="text-sm text-slate-600 bg-slate-50 rounded-lg px-4 py-3 border border-slate-200">
                                        {{ $response['answer'] ?? '—' }}
                                    </p>
                                </div>
                            @endforeach
                        @else
                            <p class="text-sm text-slate-400 italic">No responses recorded.</p>
                        @endif
                    </div>
                    @if($q->status !== 'Responded')
                        <div class="px-6 py-3 bg-slate-50 border-t border-slate-200 flex justify-end">
                            <form action="{{ route('admin.trust-centers.questionnaires.responded', [$trustCenter, $q]) }}" method="POST">
                                @csrf
                                <button type="submit"
                                        class="px-4 py-2 text-xs font-bold text-white bg-sky-500 hover:bg-sky-600 rounded-lg transition-colors">
                                    <i class="fas fa-check mr-1"></i> Mark Responded
                                </button>
                            </form>
                        </div>
                    @else
                        <div class="px-6 py-3 bg-slate-50 border-t border-slate-200 flex justify-end">
                            <span class="text-xs text-slate-400">
                                Responded {{ $q->responded_at?->format('M d, Y g:i A') }}
                            </span>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $questionnaires->links() }}
        </div>
    @else
        <div class="glass-card rounded-2xl p-12 text-center">
            <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-clipboard-list text-slate-400 text-xl"></i>
            </div>
            <h3 class="text-lg font-semibold text-slate-700 mb-1">No Questionnaires</h3>
            <p class="text-sm text-slate-500">No client questionnaires have been submitted yet.</p>
        </div>
    @endif
</div>
@endsection
