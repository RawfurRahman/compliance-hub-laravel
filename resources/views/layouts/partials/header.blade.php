<div class="glass-header flex items-center justify-between h-16 px-6 sticky top-0">

    {{-- Mobile Menu Toggle --}}
    <div class="flex md:hidden">
        <button @click="mobileMenuOpen = !mobileMenuOpen" class="text-slate-400 hover:text-slate-600 focus:outline-none transition-colors">
            <i class="fas fa-bars text-lg"></i>
        </button>
    </div>

    {{-- Search Bar --}}
    <div class="flex-1 max-w-lg ml-4 md:ml-0">
        <div class="relative group">
            <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none transition-colors group-focus-within:text-sky-500">
                <i class="fas fa-search text-sm text-slate-400"></i>
            </div>
            <input class="block w-full py-2.5 pl-10 pr-4 text-sm bg-slate-50/80 border border-slate-200/60 rounded-xl focus:bg-white focus:border-sky-300 focus:ring-4 focus:ring-sky-400/10 focus:outline-none transition-all duration-200 placeholder-slate-400" placeholder="Search projects, frameworks, users...">
        </div>
    </div>

    {{-- Right Actions --}}
    <div class="flex items-center gap-2 sm:gap-3 ml-4">

        {{-- New Project Button --}}
        <button @click="showModal = true" class="hidden sm:inline-flex items-center px-4 py-2 text-xs font-bold uppercase tracking-wider text-white btn-premium rounded-xl focus:outline-none gap-1.5">
            <i class="fas fa-plus text-[10px]"></i>
            <span>New Project</span>
        </button>

        {{-- Notification Bell --}}
        <button class="relative w-9 h-9 rounded-xl bg-slate-50 border border-slate-200/60 flex items-center justify-center text-slate-400 hover:text-sky-500 hover:border-sky-200 hover:bg-sky-50/50 transition-all duration-200 focus:outline-none">
            <i class="fas fa-bell text-sm"></i>
        </button>

        {{-- User Dropdown --}}
        <div class="relative ml-1" x-data="{ open: false }">
            <button @click="open = !open" class="avatar-ring flex items-center gap-2 focus:outline-none p-0.5" title="{{ auth()->user()->username }}">
                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-sky-400 to-indigo-500 flex items-center justify-center text-white text-xs font-bold">
                    {{ strtoupper(substr(auth()->user()->username, 0, 2)) }}
                </div>
            </button>
            <div x-show="open" @click.away="open = false" x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-1"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="dropdown-menu absolute right-0 w-56 mt-2 origin-top-right z-50" role="menu" x-cloak>

                {{-- User Info --}}
                <div class="px-4 py-3 border-b border-slate-100">
                    <p class="text-sm font-semibold text-slate-800">{{ auth()->user()->username }}</p>
                    <p class="text-xs text-slate-400 truncate">{{ auth()->user()->email }}</p>
                </div>

                <div class="py-1">
                    <a href="#" class="dropdown-item" role="menuitem">
                        <i class="fas fa-user-circle"></i> Your Profile
                    </a>
                    <a href="#" class="dropdown-item" role="menuitem">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                </div>

                <div class="border-t border-slate-100 py-1">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <a href="{{ route('logout') }}"
                           onclick="event.preventDefault(); this.closest('form').submit();"
                           class="dropdown-item text-rose-500 hover:text-rose-600" role="menuitem">
                            <i class="fas fa-arrow-right-from-bracket"></i> Sign Out
                        </a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
