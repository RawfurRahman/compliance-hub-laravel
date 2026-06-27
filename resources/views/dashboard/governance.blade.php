@extends('layouts.app')

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
@endpush

@section('content')
<div class="space-y-6 max-w-full">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">
                Governance <span class="text-blue-600">Module</span>
            </h1>
            <p class="mt-1 text-sm text-gray-500">
                Enterprise governance, risk management, and compliance dashboard with policy management and compliance tracking.
            </p>
        </div>
    </div>

    <div id="governance-dashboard-app">
        <div class="flex items-center justify-center py-20 text-gray-400">
            <div class="w-8 h-8 border-2 border-blue-600 border-t-transparent rounded-full animate-spin mr-3" />
            Loading governance dashboard...
        </div>
    </div>

</div>
@endsection

@push('scripts')
@vite(['resources/js/dashboard/governance.js'])
@endpush
