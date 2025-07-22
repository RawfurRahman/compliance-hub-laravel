{{-- resources/views/dashboard.blade.php --}}

@extends('layouts.app')

@section('content')
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-slate-800">Compliance Dashboard</h1>
            <p class="mt-1 text-md text-slate-500">Overview of your compliance activities and projects</p>
        </div>
    </div>

    <div class="bg-white shadow-sm rounded-lg p-6 mb-8 border border-slate-200">
        <h2 class="text-xl font-semibold text-slate-800 mb-2">Welcome, {{ auth()->user()->username }}!</h2>
        <p class="text-slate-600">Your role is: <strong>{{ auth()->user()->roles->first()->name ?? 'Not Assigned' }}</strong></p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <a href="{{ route('projects.index') }}" class="block p-6 bg-white rounded-lg shadow-sm hover:shadow-lg transition-shadow border border-slate-200">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-sky-100 text-sky-600">
                    <i class="fas fa-folder-open fa-lg"></i>
                </div>
                <div class="ml-4">
                    <p class="text-xl font-bold text-slate-800">Projects</p>
                    <p class="text-sm text-slate-500">View All Assessments</p>
                </div>
            </div>
        </a>

        {{-- Evidence Hub button: The route helper is correct. If you're getting a 404, --}}
        {{-- it's likely a backend issue (e.g., route cache, controller not found). --}}
        {{-- Make sure to run 'php artisan route:clear' and 'php artisan optimize:clear' --}}
        {{-- after adding/modifying routes or controllers. --}}
        {{-- Assuming a default project ID for quick access, or you'd fetch the user's active project --}}
        <a href="{{ route('evidence.show', ['project' => $currentProjectId ?? 1]) }}" class="block p-6 bg-white rounded-lg shadow-sm hover:shadow-lg transition-shadow border border-slate-200">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-teal-100 text-teal-600">
                    <i class="fas fa-cloud-upload-alt fa-lg"></i>
                </div>
                <div class="ml-4">
                    <p class="text-xl font-bold text-slate-800">Evidence Hub</p>
                    <p class="text-sm text-slate-500">Upload & Review Files</p>
                </div>
            </div>
        </a>
        
        @can('is-admin')
        <a href="{{ route('users.index') }}" class="block p-6 bg-white rounded-lg shadow-sm hover:shadow-lg transition-shadow border border-slate-200">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-indigo-100 text-indigo-600">
                    <i class="fas fa-users-cog fa-lg"></i>
                </div>
                <div class="ml-4">
                    <p class="text-xl font-bold text-slate-800">Users</p>
                    <p class="text-sm text-slate-500">Manage System Users</p>
                </div>
            </div>
        </a>
        @endcan
    </div>
@endsection

