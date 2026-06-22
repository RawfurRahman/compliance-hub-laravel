@extends('layouts.app')

@section('content')
@php
    $activeFramework = null;
    if (request()->has('module')) {
        $activeFramework = \App\Models\Framework::where('slug', request('module'))->first();
    }
    $allAuditors  = \App\Models\User::whereHas('roles', fn($q) => $q->where('name', 'Auditor'))->get();
    $allCustomers = \App\Models\User::whereHas('roles', fn($q) => $q->where('name', 'Customer'))->whereNull('parent_id')->get();
@endphp

<div x-data="projectsManager()">

    {{-- ── Page Header ── --}}
    <div class="mb-10 flex flex-col sm:row sm:items-end sm:justify-between gap-6">
        <div class="max-w-2xl">
            @if($activeFramework)
                <div class="flex items-center space-x-2 mb-3">
                    <a href="{{ route('projects.index') }}" class="text-slate-400 hover:text-sky-600 transition-colors text-xs font-bold uppercase tracking-widest">Portfolio</a>
                    <i class="fas fa-chevron-right text-[10px] text-slate-300"></i>
                    <span class="text-sky-600 font-bold text-xs uppercase tracking-widest">{{ $activeFramework->name }}</span>
                </div>
                <h1 class="text-4xl font-extrabold text-slate-900 tracking-tight">{{ $activeFramework->name }} <span class="text-slate-400 font-light">Assessments</span></h1>
                <p class="mt-2 text-md text-slate-500 font-medium">{{ $activeFramework->description ?? 'Targeted compliance review for this framework.' }}</p>
            @else
                <h1 class="text-4xl font-extrabold text-slate-900 tracking-tight">Project <span class="text-sky-600">Portfolio</span></h1>
                <p class="mt-2 text-md text-slate-500 font-medium">Strategic overview and management of all enterprise compliance operations.</p>
            @endif
        </div>
    </div>

    {{-- ── Flash Messages ── --}}
    @if (session('success'))
        <div class="bg-emerald-50 border-l-4 border-emerald-500 p-4 rounded shadow-sm mb-6 flex items-start">
            <i class="fas fa-check-circle text-emerald-500 mt-0.5 mr-3 text-lg"></i>
            <p class="text-sm font-medium text-emerald-800">{{ session('success') }}</p>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6">
        @forelse ($projects as $project)
            @php
                $fw = \App\Models\Framework::where('slug', $project->module_type)->first();
                $fwLabel = $fw ? ($fw->name . ($fw->version ? ' '.$fw->version : '')) : strtoupper(str_replace('_', ' ', $project->module_type));
                $project->load('assignedUsers.roles');
                $auditors  = $project->assignedUsers->filter(fn($u) => optional($u->roles->first())->name === 'Auditor');
                $customers = $project->assignedUsers->filter(fn($u) => optional($u->roles->first())->name === 'Customer');
            @endphp
            <div class="glass-card rounded-2xl p-6 border border-white/60 shadow-lg hover:shadow-xl hover:border-sky-300 transition-all group">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                    <div class="flex-1">
                        <div class="flex items-center space-x-3 mb-2">
                             <span class="px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-widest rounded-lg bg-sky-500/10 text-sky-600 border border-sky-500/20">
                                {{ $fwLabel }}
                            </span>
                            <span class="text-[11px] font-medium text-slate-400 italic">Created {{ $project->created_at->format('M d, Y') }}</span>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 group-hover:text-sky-700 transition-colors">
                            <a href="{{ route('projects.show', $project) }}">{{ $project->name }}</a>
                        </h3>
                        
                        <div class="mt-4 flex flex-wrap gap-2">
                            @if($auditors->isNotEmpty())
                                <div class="flex -space-x-2 mr-4">
                                    @foreach($auditors as $u)
                                        <div class="w-8 h-8 rounded-full bg-indigo-600 border-2 border-white flex items-center justify-center text-[10px] font-bold text-white shadow-sm ring-2 ring-indigo-50" title="Auditor: {{ $u->username }}">
                                            {{ substr($u->username, 0, 1) }}
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                            @foreach($customers as $u)
                                <span class="px-3 py-1 text-[11px] font-bold bg-slate-50 text-slate-600 border border-slate-200 rounded-full flex items-center">
                                    <i class="fas fa-building mr-2 opacity-40"></i> {{ $u->username }}
                                </span>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex items-center gap-3 lg:border-l lg:border-slate-100 lg:pl-10">
                        @if($project->module_type == 'pci_dss')
                            <a href="{{ route('pci.show', $project) }}" class="px-4 py-2 text-xs font-bold uppercase tracking-widest text-indigo-600 hover:bg-indigo-50 rounded-xl transition-colors border border-indigo-100">
                                Assessment
                            </a>
                        @else
                            <a href="{{ route('assessments.show', $project) }}" class="px-4 py-2 text-xs font-bold uppercase tracking-widest text-indigo-600 hover:bg-indigo-50 rounded-xl transition-colors border border-indigo-100">
                                Assessment
                            </a>
                        @endif
                        <a href="{{ route('evidence.show', $project) }}" class="px-4 py-2 text-xs font-bold uppercase tracking-widest text-emerald-600 hover:bg-emerald-50 rounded-xl transition-colors border border-emerald-100">
                            Evidence <span class="ml-1 opacity-50">Hub</span>
                        </a>
                        <div class="flex items-center space-x-1 ml-2">
                             <a href="{{ route('meetings.index', $project) }}" class="p-2 text-slate-400 hover:text-sky-600 hover:bg-sky-50 rounded-lg transition-all" title="Meetings">
                                <i class="fas fa-calendar-alt"></i>
                            </a>
                            @can('is-admin')
                                <button
                                    @click="openEdit({{ $project->id }}, '{{ addslashes($project->name) }}', '{{ $project->module_type }}', {{ json_encode($auditors->pluck('id')->values()) }}, {{ json_encode($customers->pluck('id')->values()) }})"
                                    class="p-2 text-slate-400 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-all"
                                    title="Edit Project"
                                >
                                    <i class="fas fa-pencil-alt text-xs"></i>
                                </button>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="glass-card rounded-3xl p-20 text-center border border-dashed border-slate-300">
                <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-folder-open text-3xl text-slate-300"></i>
                </div>
                <h3 class="text-xl font-bold text-slate-800">Portfolio is empty</h3>
                <p class="text-slate-500 mt-2">Initialize a new project to start your compliance journey.</p>
                <button @click="showModal = true" class="mt-6 btn-premium px-8 py-3 rounded-2xl text-xs font-bold uppercase tracking-widest">
                    Create First Project
                </button>
            </div>
        @endforelse
    </div>

    {{-- ── Edit Project Modal (Admin Only) ── --}}
    @can('is-admin')
    <div x-show="showEdit" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4" @keydown.escape.window="showEdit = false" x-cloak>
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg" @click.away="showEdit = false">

            {{-- Modal Header --}}
            <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-bold text-slate-800">Edit Project</h2>
                    <p class="text-sm text-slate-500 mt-0.5" x-text="'Modifying: ' + editProject.name"></p>
                </div>
                <button type="button" @click="showEdit = false" class="text-slate-400 hover:text-slate-600 transition">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            {{-- Modal Form --}}
            <form :action="'{{ url('projects') }}/' + editProject.id" method="POST" class="px-6 py-5 space-y-5">
                @csrf
                @method('PUT')

                {{-- Project Name --}}
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Project Name</label>
                    <input type="text" name="name" :value="editProject.name" required
                        class="block w-full rounded-lg border-slate-300 shadow-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 text-sm transition">
                </div>

                {{-- Framework (read-only display) --}}
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Framework</label>
                    <div class="flex items-center bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-600">
                        <i class="fas fa-lock text-slate-400 mr-2 text-xs"></i>
                        <span x-text="editProject.module_type_label"></span>
                        <span class="ml-auto text-xs text-slate-400 italic">Cannot change after creation</span>
                    </div>
                </div>

                {{-- Assign Auditors --}}
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">
                        Assign Auditors
                        <span class="font-normal text-slate-400 text-xs ml-1">(Ctrl/Cmd to multi-select)</span>
                    </label>
                    <select name="auditors[]" multiple size="4"
                        class="block w-full rounded-lg border border-slate-300 shadow-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 text-sm transition"
                        x-ref="auditorsSelect">
                        @foreach($allAuditors as $auditor)
                            <option value="{{ $auditor->id }}"
                                :selected="editProject.auditor_ids && editProject.auditor_ids.includes({{ $auditor->id }})">
                                {{ $auditor->username }} &lt;{{ $auditor->email }}&gt;
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1.5 text-xs text-slate-400">
                        <i class="fas fa-info-circle mr-1"></i>
                        To deselect, Ctrl/Cmd+click a highlighted name.
                    </p>
                </div>

                {{-- Assign Customers --}}
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">
                        Assign Customers
                        <span class="font-normal text-slate-400 text-xs ml-1">(Ctrl/Cmd to multi-select)</span>
                    </label>
                    <select name="customers[]" multiple size="4"
                        class="block w-full rounded-lg border border-slate-300 shadow-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 text-sm transition"
                        x-ref="customersSelect">
                        @foreach($allCustomers as $customer)
                            <option value="{{ $customer->id }}"
                                :selected="editProject.customer_ids && editProject.customer_ids.includes({{ $customer->id }})">
                                {{ $customer->username }} &lt;{{ $customer->email }}&gt;
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Actions --}}
                <div class="flex justify-between items-center pt-3 border-t border-slate-100">
                    <p class="text-xs text-slate-400 italic">
                        <i class="fas fa-shield-alt mr-1 text-sky-400"></i>
                        Sub-users automatically inherit project access.
                    </p>
                    <div class="flex gap-3">
                        <button type="button" @click="showEdit = false"
                            class="px-4 py-2 text-sm font-semibold text-slate-700 bg-slate-100 rounded-lg hover:bg-slate-200 transition">
                            Cancel
                        </button>
                        <button type="submit"
                            class="px-5 py-2 text-sm font-semibold text-white bg-sky-600 rounded-lg hover:bg-sky-700 shadow-sm transition">
                            Save Changes
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    @endcan
</div>

@push('scripts')
<script>
function projectsManager() {
    return {
        showEdit: false,
        editProject: {
            id: null,
            name: '',
            module_type: '',
            module_type_label: '',
            auditor_ids: [],
            customer_ids: [],
        },

        frameworkLabels: @json(\App\Models\Framework::all()->keyBy('slug')->map(fn($f) => $f->name . ($f->version ? ' '.$f->version : ''))),

        openEdit(id, name, moduleType, auditorIds, customerIds) {
            this.editProject = {
                id,
                name,
                module_type: moduleType,
                module_type_label: this.frameworkLabels[moduleType] ?? moduleType.toUpperCase().replace('_', ' '),
                auditor_ids: auditorIds,
                customer_ids: customerIds,
            };
            this.showEdit = true;

            // After Alpine renders, pre-select options
            this.$nextTick(() => {
                this.applySelections(this.$refs.auditorsSelect,  auditorIds);
                this.applySelections(this.$refs.customersSelect, customerIds);
            });
        },

        applySelections(selectEl, ids) {
            if (!selectEl) return;
            Array.from(selectEl.options).forEach(opt => {
                opt.selected = ids.includes(parseInt(opt.value));
            });
        }
    };
}
</script>
@endpush
@endsection
