<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $trustCenter->headline ?? config('app.name', 'ComplianceHub') }}</title>
    <meta name="description" content="Trust Center — Security and Compliance Information">
    
    <link href="{{ asset('fonts/inter.css') }}" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('vendor/font-awesome/css/all.min.css') }}">
    <link href="{{ asset('css/main.css') }}" rel="stylesheet">
    <script src="{{ asset('js/tailwind.min.js') }}"></script>
    <script src="{{ asset('js/alpine.min.js') }}" defer></script>
    @stack('styles')
</head>
<body class="font-sans antialiased bg-slate-50">
    <div class="min-h-screen">
        <main>
            @if(session('success'))
                <div class="max-w-3xl mx-auto mt-6 px-4">
                    <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm">
                        <i class="fas fa-check-circle mr-1.5 text-emerald-500"></i>
                        {{ session('success') }}
                    </div>
                </div>
            @endif
            @yield('content')
        </main>
    </div>
    @stack('scripts')
    <script src="{{ asset('js/csp-bindings.js') }}" defer></script>
</body>
</html>
