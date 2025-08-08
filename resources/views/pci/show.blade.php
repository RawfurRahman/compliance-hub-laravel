{{-- resources/views/pci/show.blade.php --}}
@extends('layouts.app')

@section('content')
    {{--
        This is the main view for a PCI DSS Project assessment.
        It uses Alpine.js to manage the state of the form, allowing users to toggle between
        a read-only view ('view mode') and an editable form ('edit mode').

        - x-data: Initializes the Alpine.js component with several state variables:
            - isEditing: A boolean to track if the form is in edit mode.
            - addMode: A boolean to determine if this is a new, unsaved project. This is set to false as we are viewing an existing project.
            - originalDetails: A JSON object holding the initial state of the project details. Used for the "Cancel" button.
            - details: A JSON object that is bound to the form inputs. Changes are made to this object.
        - :action: The form's action URL is dynamically set based on whether it's a new project or an update.
    --}}
    <div x-data="{
        isEditing: {{ $project->pciDssDetails->wasRecentlyCreated ? 'true' : 'false' }},
        originalDetails: {{ json_encode($project->pciDssDetails) }},
        details: {{ json_encode($project->pciDssDetails) }}
    }">
        <!-- Page Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
            <div>
                <h1 class="text-3xl font-bold text-slate-800">PCI DSS v4.0.1 Assessment</h1>
                <p class="mt-1 text-md text-slate-500">Project: <span class="font-semibold text-slate-600">{{ $project->name }}</span></p>
            </div>
            <div class="mt-4 md:mt-0 flex items-center space-x-4">
                 <!-- Report Generation Button -->
                <a href="{{ route('reports.pci.generate', $project) }}" target="_blank" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-teal-600 hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500">
                    <i class="fas fa-file-alt mr-2"></i> Generate Report
                </a>
                <!-- Edit / Save / Cancel Buttons -->
                <div x-show="!isEditing && !addMode">
                    <button type="button" @click="isEditing = true" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-sky-600 hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-500">
                        <i class="fas fa-edit mr-2"></i> Edit Project Information
                    </button>
                </div>
            </div>
        </div>

        @if (session('success'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                <p>{{ session('success') }}</p>
            </div>
        @endif

        <form :action="addMode ? '{{ route('pci.store') }}' : '{{ route('pci.update', $project) }}'" method="POST">
            @csrf
            <template x-if="!addMode">
                @method('PUT')
            </template>

            <!-- Sticky Save/Cancel bar for Edit Mode -->
            <div x-show="isEditing" x-transition class="sticky top-0 bg-white/80 backdrop-blur-sm z-10 p-4 mb-6 rounded-lg shadow-md border border-slate-200 flex justify-end items-center space-x-4">
                <button type="button" @click="isEditing = false; details = JSON.parse(JSON.stringify(originalDetails))" class="px-4 py-2 bg-slate-200 text-slate-800 rounded-md hover:bg-slate-300">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 shadow-sm">
                    <i class="fas fa-save mr-2"></i> Save Changes
                </button>
            </div>

            <!-- Assessment Components -->
            {{--
                Each section of the assessment is broken out into its own Blade component.
                - We pass the project details to each component using the `:details` prop.
                - The `isEditing` state is implicitly available to all child components because they
                  are inside the main `x-data` scope. This allows them to show/hide input fields.
            --}}
            <div class="space-y-8">
                <x-pci.project-info :project="$project" />
                <x-pci.assessment-timeframe />
                <x-pci.business-overview :paymentChannels="$paymentChannels" />
                <x-pci.scope-of-work />
                <x-pci.environment-details />
                <x-pci.reviewed-environments />
                <x-pci.quarterly-scans />
                <x-pci.assessment-activities />
                <x-pci.overall-findings />

                {{-- The Requirements List is the most complex component, handling its own search state --}}
                <x-pci.requirements-list :requirements="$requirements" :findings="$findings" />
            </div>
        </form>
    </div>
@endsection
