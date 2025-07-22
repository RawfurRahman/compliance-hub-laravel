@extends('layouts.app')

@section('content')
    <!-- Page Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-slate-800">Compliance Projects</h1>
        <p class="mt-1 text-md text-slate-500">View and manage all of your ongoing assessments.</p>
    </div>

    <div class="bg-white shadow-sm rounded-lg overflow-hidden border border-slate-200">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Project Name</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Module Type</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Created At</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-slate-200">
                @forelse ($projects as $project)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900">{{ $project->name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-sky-100 text-sky-800">
                                {{ strtoupper(str_replace('_', ' ', $project->module_type)) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                            {{ $project->created_at->format('M d, Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-4">
                            {{-- Link to the main assessment page --}}
                            @if($project->module_type == 'pci_dss')
                                <a href="{{ route('pci.show', $project) }}" class="text-indigo-600 hover:text-indigo-900">View Assessment</a>
                            @endif
                            
                            {{-- ** THE FIX IS HERE (New Button) ** --}}
                            {{-- This link now correctly points to the evidence page for this specific project --}}
                            <a href="{{ route('evidence.show', $project) }}" class="text-teal-600 hover:text-teal-900">Manage Evidence</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">No projects found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
