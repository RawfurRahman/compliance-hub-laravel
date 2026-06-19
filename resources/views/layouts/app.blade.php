<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'ComplianceHub') }}</title>
    <meta name="description" content="Enterprise compliance management platform for PCI DSS, ISO 27001, and more.">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <!-- Design System -->
    <link href="{{ asset('css/main.css') }}" rel="stylesheet">
    @stack('styles')

    <!-- Tailwind CDN + Alpine.js -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="font-sans antialiased" style="background:#f0f4f8;">
    <div x-data="{ mobileMenuOpen: false, showModal: false }" class="flex h-screen">

        <!-- Sidebar -->
        @include('layouts.partials.sidebar')

        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            @include('layouts.partials.header')

            <!-- Main Content -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto">
                <div class="container mx-auto px-6 py-8 max-w-7xl">

                    {{-- Flash Messages --}}
                    @if(session('success'))
                        <div class="alert-success mb-6 fade-in-up">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-check-circle text-emerald-500"></i>
                                <span>{{ session('success') }}</span>
                            </div>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert-error mb-6 fade-in-up">
                            <div class="flex items-start gap-2">
                                <i class="fas fa-exclamation-circle mt-0.5 text-rose-500"></i>
                                <ul class="space-y-0.5">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    @yield('content')
                </div>
            </main>
        </div>

        <!-- New Project Modal -->
        <div x-show="showModal" class="modal-overlay fixed inset-0 z-50 flex items-center justify-center p-4" @keydown.escape.window="showModal = false" x-cloak
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">

            <div class="modal-card w-full max-w-md" @click.away="showModal = false"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95">

                <div class="px-6 py-5 border-b border-slate-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-sky-50 flex items-center justify-center text-sky-500">
                            <i class="fas fa-folder-plus"></i>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-slate-800">Create New Project</h2>
                            <p class="text-xs text-slate-400 mt-0.5">Configure and assign a new compliance project</p>
                        </div>
                    </div>
                </div>

                <form action="{{ route('projects.store') }}" method="POST" class="px-6 py-5 space-y-5">
                    @csrf

                    {{-- Project Name --}}
                    <div>
                        <label for="proj_name" class="form-label">Project Name</label>
                        <input type="text" name="name" id="proj_name" required
                               placeholder="e.g. Acme Corp PCI Audit 2025"
                               class="form-input">
                    </div>

                    {{-- Framework / Module --}}
                    <div>
                        <label for="module_type" class="form-label">Module / Framework</label>
                        @php
                            $modalFrameworks = \App\Models\Framework::where('is_active', true)->get();
                        @endphp
                        <select name="module_type" id="module_type" required class="form-input">
                            <option value="" disabled selected>-- Select Framework --</option>
                            @foreach($modalFrameworks as $fw)
                                <option value="{{ $fw->slug }}">{{ $fw->name }}{{ $fw->version ? ' ' . $fw->version : '' }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Auditors & Customers (Admin only) --}}
                    @can('is-admin')
                    @php
                        $modalAuditors  = \App\Models\User::whereHas('roles', fn($q) => $q->where('name', 'Auditor'))->get();
                        $modalCustomers = \App\Models\User::whereHas('roles', fn($q) => $q->where('name', 'Customer'))->whereNull('parent_id')->get();
                    @endphp

                    <div>
                        <label for="auditors" class="form-label">
                            Assign Auditors <span class="font-normal text-slate-400 text-xs">(Ctrl/Cmd to multi-select)</span>
                        </label>
                        <select name="auditors[]" id="auditors" multiple class="form-input" size="4">
                            @foreach($modalAuditors as $auditor)
                                <option value="{{ $auditor->id }}">{{ $auditor->username }} &lt;{{ $auditor->email }}&gt;</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="customers" class="form-label">
                            Assign Customers <span class="font-normal text-slate-400 text-xs">(Ctrl/Cmd to multi-select)</span>
                        </label>
                        <select name="customers[]" id="customers" multiple class="form-input" size="4">
                            @foreach($modalCustomers as $customer)
                                <option value="{{ $customer->id }}">{{ $customer->username }} &lt;{{ $customer->email }}&gt;</option>
                            @endforeach
                        </select>
                    </div>
                    @endcan

                    <div class="flex justify-end gap-3 pt-3 border-t border-slate-100">
                        <button type="button" @click="showModal = false"
                                class="px-4 py-2 text-sm font-semibold text-slate-600 bg-slate-100 rounded-xl hover:bg-slate-200 transition-all">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-5 py-2 text-sm font-semibold text-white bg-gradient-to-r from-sky-500 to-indigo-500 rounded-xl hover:shadow-lg hover:shadow-sky-500/25 transition-all">
                            <i class="fas fa-plus mr-1.5 text-xs"></i> Create Project
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
    @stack('scripts')
</body>
</html>
