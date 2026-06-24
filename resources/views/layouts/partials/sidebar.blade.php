<div class="hidden md:flex md:flex-shrink-0">
    <div class="flex flex-col w-64" style="background: linear-gradient(180deg, #0f172a 0%, #0c1324 50%, #0a1020 100%);">

        {{-- Brand Logo --}}
        <div class="flex items-center h-16 flex-shrink-0 px-5 border-b border-white/5">
            <div class="flex items-center justify-center w-9 h-9 rounded-xl bg-gradient-to-br from-sky-400 to-indigo-500 shadow-lg shadow-sky-500/20">
                <i class="fas fa-shield-alt text-white text-sm"></i>
            </div>
            <span class="ml-3 font-bold text-lg tracking-tight text-white">Compliance<span class="text-sky-400">Hub</span></span>
        </div>

        {{-- Navigation --}}
        <div class="flex-1 flex flex-col overflow-y-auto">
            <nav class="flex-1 px-3 py-5 space-y-1">

                {{-- Main Navigation --}}
                <div class="px-3 mb-3">
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-[0.15em]">Main</p>
                </div>

                <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'nav-item-active' : 'text-slate-400 nav-item-hover' }} group flex items-center px-3 py-2.5 text-sm font-semibold rounded-xl transition-all duration-200">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center mr-3 {{ request()->routeIs('dashboard') ? 'bg-sky-500/15 text-sky-400' : 'bg-white/5 text-slate-500 group-hover:text-slate-300' }} transition-colors">
                        <i class="fas fa-gauge-high text-sm"></i>
                    </div>
                    Dashboard
                </a>

                @can('view-dashboard')
                <a href="{{ route('dashboard.executive') }}" class="{{ request()->routeIs('dashboard.executive') ? 'nav-item-active' : 'text-slate-400 nav-item-hover' }} group flex items-center px-3 py-2.5 text-sm font-semibold rounded-xl transition-all duration-200">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center mr-3 {{ request()->routeIs('dashboard.executive') ? 'bg-sky-500/15 text-sky-400' : 'bg-white/5 text-slate-500 group-hover:text-slate-300' }} transition-colors">
                        <i class="fas fa-chart-line text-sm"></i>
                    </div>
                    Executive Dashboard
                </a>
                @endcan

                <a href="{{ route('projects.index') }}" class="{{ request()->routeIs('projects.index') && !request()->has('module') ? 'nav-item-active' : 'text-slate-400 nav-item-hover' }} group flex items-center px-3 py-2.5 text-sm font-semibold rounded-xl transition-all duration-200">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center mr-3 {{ request()->routeIs('projects.index') && !request()->has('module') ? 'bg-sky-500/15 text-sky-400' : 'bg-white/5 text-slate-500 group-hover:text-slate-300' }} transition-colors">
                        <i class="fas fa-layer-group text-sm"></i>
                    </div>
                    All Projects
                </a>

                <a href="{{ route('evidence.hub') }}" class="{{ request()->routeIs('evidence.hub') ? 'nav-item-active' : 'text-slate-400 nav-item-hover' }} group flex items-center px-3 py-2.5 text-sm font-semibold rounded-xl transition-all duration-200">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center mr-3 {{ request()->routeIs('evidence.hub') ? 'bg-sky-500/15 text-sky-400' : 'bg-white/5 text-slate-500 group-hover:text-slate-300' }} transition-colors">
                        <i class="fas fa-project-diagram text-sm"></i>
                    </div>
                    Evidence Hub
                </a>

                {{-- Dynamic Framework Links --}}
                @php
                    $sidebarFrameworks = \App\Models\Framework::where('is_active', true)->get();
                    $frameworkIcons = [
                        'pci_dss'          => 'fa-credit-card',
                        'iso_27001'        => 'fa-shield-halved',
                        'swift_csp'        => 'fa-building-columns',
                        'swift_cscf_2026'  => 'fa-building-columns',
                        'vapt'             => 'fa-bug',
                    ];
                @endphp

                @if($sidebarFrameworks->isNotEmpty())
                <div class="px-3 mt-5 mb-3">
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-[0.15em]">Frameworks</p>
                </div>
                @foreach($sidebarFrameworks as $fw)
                    @php
                        $fwIcon = $frameworkIcons[$fw->slug] ?? 'fa-clipboard-check';
                        $isActive = request()->routeIs('projects.index') && request()->get('module') === $fw->slug;
                    @endphp
                    <a href="{{ route('projects.index', ['module' => $fw->slug]) }}" class="{{ $isActive ? 'nav-item-active' : 'text-slate-400 nav-item-hover' }} group flex items-center px-3 py-2.5 text-sm font-semibold rounded-xl transition-all duration-200">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center mr-3 {{ $isActive ? 'bg-sky-500/15 text-sky-400' : 'bg-white/5 text-slate-500 group-hover:text-slate-300' }} transition-colors">
                            <i class="fas {{ $fwIcon }} text-sm"></i>
                        </div>
                        <span>{{ $fw->name }}</span>
                        @if($fw->version)
                            <span class="ml-auto text-[9px] px-1.5 py-0.5 rounded-md bg-white/5 text-slate-500 font-bold uppercase">{{ $fw->version }}</span>
                        @endif
                    </a>
                @endforeach
                @endif

                <div class="px-3 mt-5 mb-3">
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-[0.15em]">Risk Management</p>
                </div>
                @if(isset($project) && $project->id)
                <a href="{{ route('risk-register.index', $project) }}" class="{{ request()->routeIs('risk-register.*') && !request()->routeIs('risk-register.heatmap') ? 'nav-item-active' : 'text-slate-400 nav-item-hover' }} group flex items-center px-3 py-2.5 text-sm font-semibold rounded-xl transition-all duration-200">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center mr-3 {{ request()->routeIs('risk-register.*') && !request()->routeIs('risk-register.heatmap') ? 'bg-sky-500/15 text-sky-400' : 'bg-white/5 text-slate-500 group-hover:text-slate-300' }} transition-colors">
                        <i class="fas fa-triangle-exclamation text-sm"></i>
                    </div>
                    Risk Register
                </a>
                <a href="{{ route('risk-register.heatmap', $project) }}" class="{{ request()->routeIs('risk-register.heatmap') ? 'nav-item-active' : 'text-slate-400 nav-item-hover' }} group flex items-center px-3 py-2.5 text-sm font-semibold rounded-xl transition-all duration-200">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center mr-3 {{ request()->routeIs('risk-register.heatmap') ? 'bg-sky-500/15 text-sky-400' : 'bg-white/5 text-slate-500 group-hover:text-slate-300' }} transition-colors">
                        <i class="fas fa-fire text-sm"></i>
                    </div>
                    Risk Heat Map
                </a>

                @else
                <a href="{{ route('projects.index') }}" class="text-slate-400 nav-item-hover group flex items-center px-3 py-2.5 text-sm font-semibold rounded-xl transition-all duration-200">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center mr-3 bg-white/5 text-slate-500 group-hover:text-slate-300 transition-colors">
                        <i class="fas fa-shield text-sm"></i>
                    </div>
                    Risk Overview
                </a>
                @endif

                {{-- Admin Section --}}
                @can('is-admin')
                <div class="px-3 mt-5 mb-3">
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-[0.15em]">Administration</p>
                </div>

                <a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.*') ? 'nav-item-active' : 'text-slate-400 nav-item-hover' }} group flex items-center px-3 py-2.5 text-sm font-semibold rounded-xl transition-all duration-200">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center mr-3 {{ request()->routeIs('users.*') ? 'bg-sky-500/15 text-sky-400' : 'bg-white/5 text-slate-500 group-hover:text-slate-300' }} transition-colors">
                        <i class="fas fa-users-gear text-sm"></i>
                    </div>
                    User Management
                </a>

                <a href="{{ route('admin.frameworks.index') }}" class="{{ request()->routeIs('admin.frameworks.*') ? 'nav-item-active' : 'text-slate-400 nav-item-hover' }} group flex items-center px-3 py-2.5 text-sm font-semibold rounded-xl transition-all duration-200">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center mr-3 {{ request()->routeIs('admin.frameworks.*') ? 'bg-sky-500/15 text-sky-400' : 'bg-white/5 text-slate-500 group-hover:text-slate-300' }} transition-colors">
                        <i class="fas fa-cubes text-sm"></i>
                    </div>
                    Framework Library
                </a>

                <a href="{{ route('admin.requirements.index') }}" class="{{ request()->routeIs('admin.requirements.*') ? 'nav-item-active' : 'text-slate-400 nav-item-hover' }} group flex items-center px-3 py-2.5 text-sm font-semibold rounded-xl transition-all duration-200">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center mr-3 {{ request()->routeIs('admin.requirements.*') ? 'bg-sky-500/15 text-sky-400' : 'bg-white/5 text-slate-500 group-hover:text-slate-300' }} transition-colors">
                        <i class="fas fa-list-check text-sm"></i>
                    </div>
                    PCI Requirements
                </a>
                @endcan

                {{-- Organization (Customer Team) --}}
                @if(auth()->user()->isPrimaryCustomer())
                <div class="px-3 mt-5 mb-3">
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-[0.15em]">Organization</p>
                </div>
                <a href="{{ route('team.index') }}" class="{{ request()->routeIs('team.*') ? 'nav-item-active' : 'text-slate-400 nav-item-hover' }} group flex items-center px-3 py-2.5 text-sm font-semibold rounded-xl transition-all duration-200">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center mr-3 {{ request()->routeIs('team.*') ? 'bg-sky-500/15 text-sky-400' : 'bg-white/5 text-slate-500 group-hover:text-slate-300' }} transition-colors">
                        <i class="fas fa-people-group text-sm"></i>
                    </div>
                    My Team
                </a>
                @endif

            </nav>

            {{-- Bottom User Card --}}
            <div class="px-3 pb-4">
                <div class="p-3 rounded-xl bg-white/5 border border-white/5">
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-sky-400 to-indigo-500 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                            {{ strtoupper(substr(auth()->user()->username, 0, 2)) }}
                        </div>
                        <div class="ml-3 min-w-0">
                            <p class="text-xs font-semibold text-slate-200 truncate">{{ auth()->user()->username }}</p>
                            <p class="text-[10px] text-slate-500 truncate">{{ auth()->user()->roles->first()->name ?? 'User' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
