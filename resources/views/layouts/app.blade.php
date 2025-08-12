<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'ComplianceHub') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <!-- Scripts -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    
    <style>
        /* Custom styles for a more polished design */
        :root {
            --color-primary: #0ea5e9; /* sky-500 */
            --color-primary-hover: #0284c7; /* sky-600 */
            --color-secondary: #475569; /* slate-600 */
            --color-background: #f1f5f9; /* slate-100 */
            --color-card-bg: #ffffff;
            --color-text-main: #1e293b; /* slate-800 */
            --color-text-light: #64748b; /* slate-500 */
            --color-border: #e2e8f0; /* slate-200 */
        }
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--color-background);
            color: var(--color-text-main);
        }
        .assessment-table th, .assessment-table td {
            padding: 0.75rem 1rem;
            vertical-align: top;
            border-bottom: 1px solid var(--color-border);
        }
        .assessment-table input, .assessment-table select, .assessment-table textarea {
            width: 100%;
            border-radius: 0.375rem;
            border: 1px solid var(--color-border);
            padding: 0.5rem 0.75rem;
            transition: border-color 0.2s;
        }
        .assessment-table input:focus, .assessment-table select:focus, .assessment-table textarea:focus {
            border-color: var(--color-primary);
            box-shadow: 0 0 0 1px var(--color-primary);
            outline: none;
        }
    </style>

</head>
<body class="font-sans antialiased">
    <div x-data="{ mobileMenuOpen: false, showModal: false }" class="flex h-screen bg-slate-100">
        <!-- Sidebar -->
        @include('layouts.partials.sidebar')

        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            @include('layouts.partials.header')

            <!-- Main content -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto">
                <div class="container mx-auto px-6 py-8">
                    @yield('content')
                </div>
            </main>
        </div>
        
        <!-- New Project Modal -->
        <div x-show="showModal" class="fixed inset-0 bg-black bg-opacity-60 z-50 flex items-center justify-center" @keydown.escape.window="showModal = false" x-cloak>
            <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md" @click.away="showModal = false">
                <h2 class="text-xl font-bold mb-4 text-slate-800">Create New Project</h2>
                <form action="{{ route('projects.store') }}" method="POST">
                    @csrf
                    <div>
                        <label for="name" class="block text-sm font-medium text-slate-700">Project Name</label>
                        <input type="text" name="name" id="name" required class="mt-1 block w-full border-slate-300 rounded-md shadow-sm focus:ring-sky-500 focus:border-sky-500">
                    </div>
                    <div class="mt-4">
                        <label for="module_type" class="block text-sm font-medium text-slate-700">Module</label>
                        <select name="module_type" id="module_type" required class="mt-1 block w-full border-slate-300 rounded-md shadow-sm focus:ring-sky-500 focus:border-sky-500">
                            <option value="pci_dss">PCI DSS v4.0.1</option>
                        </select>
                    </div>
                    <div class="mt-6 flex justify-end space-x-4">
                        <button type="button" @click="showModal = false" class="px-4 py-2 bg-slate-200 text-slate-800 rounded-md hover:bg-slate-300">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-sky-500 text-white rounded-md hover:bg-sky-600">Create Project</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
    @stack('scripts')
</body>
</html>
