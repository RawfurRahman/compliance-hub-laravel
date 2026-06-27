@extends('layouts.app')

@section('content')
<div class="fade-in-up max-w-4xl mx-auto">
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
           class="pb-3 text-sm font-semibold text-sky-600 border-b-2 border-sky-600">
           Requests
        </a>
        <a href="{{ route('admin.trust-centers.questionnaires', $trustCenter) }}"
           class="pb-3 text-sm font-semibold text-slate-500 hover:text-slate-700">
           Questionnaires
        </a>
    </div>

    <div class="mb-6">
        <h2 class="text-xl font-bold text-slate-900">Access Requests</h2>
    </div>

    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm mb-6">
            <i class="fas fa-check-circle mr-1.5 text-emerald-500"></i>
            {{ session('success') }}
        </div>
    @endif

    @if($requests->count() > 0)
        <div class="glass-card rounded-2xl overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="text-left px-5 py-3 font-semibold text-slate-600">Name</th>
                        <th class="text-left px-5 py-3 font-semibold text-slate-600">Email</th>
                        <th class="text-left px-5 py-3 font-semibold text-slate-600">Company</th>
                        <th class="text-left px-5 py-3 font-semibold text-slate-600">Note</th>
                        <th class="text-left px-5 py-3 font-semibold text-slate-600">Status</th>
                        <th class="text-left px-5 py-3 font-semibold text-slate-600">Reviewed By</th>
                        <th class="text-left px-5 py-3 font-semibold text-slate-600">Submitted</th>
                        <th class="text-left px-5 py-3 font-semibold text-slate-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($requests as $req)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-5 py-4 font-medium text-slate-800">{{ $req->requester_name }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ $req->requester_email }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ $req->requester_company ?? '—' }}</td>
                            <td class="px-5 py-4 text-slate-600 max-w-[200px] truncate">{{ $req->note ?? '—' }}</td>
                            <td class="px-5 py-4">
                                @php
                                    $statusColors = [
                                        'Pending' => 'bg-amber-100 text-amber-700',
                                        'Approved' => 'bg-emerald-100 text-emerald-700',
                                        'Denied' => 'bg-red-100 text-red-700',
                                    ];
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-bold rounded-full {{ $statusColors[$req->status] ?? 'bg-slate-100 text-slate-600' }}">
                                    {{ $req->status }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-slate-600">
                                {{ $req->reviewer?->username ?? ($req->reviewed_at ? 'Unknown' : '—') }}
                            </td>
                            <td class="px-5 py-4 text-slate-500 text-xs">{{ $req->created_at->format('M d, Y g:i A') }}</td>
                            <td class="px-5 py-4">
                                @if($req->status === 'Pending')
                                    <div class="flex items-center gap-2">
                                        <form action="{{ route('admin.trust-centers.requests.approve', [$trustCenter, $req]) }}" method="POST">
                                            @csrf
                                            <button type="submit"
                                                    class="px-3 py-1.5 text-xs font-bold text-white bg-emerald-500 hover:bg-emerald-600 rounded-lg transition-colors">
                                                Approve
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.trust-centers.requests.deny', [$trustCenter, $req]) }}" method="POST">
                                            @csrf
                                            <button type="submit"
                                                    class="px-3 py-1.5 text-xs font-bold text-white bg-red-500 hover:bg-red-600 rounded-lg transition-colors">
                                                Deny
                                            </button>
                                        </form>
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400">
                                        {{ $req->reviewed_at ? $req->reviewed_at->format('M d, Y g:i A') : '' }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $requests->links() }}
        </div>
    @else
        <div class="glass-card rounded-2xl p-12 text-center">
            <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-inbox text-slate-400 text-xl"></i>
            </div>
            <h3 class="text-lg font-semibold text-slate-700 mb-1">No Access Requests</h3>
            <p class="text-sm text-slate-500">No one has requested access to this trust center yet.</p>
        </div>
    @endif
</div>
@endsection
