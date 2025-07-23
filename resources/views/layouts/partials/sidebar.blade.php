<div class="hidden md:flex md:flex-shrink-0">
    <div class="flex flex-col w-64">
        <div class="flex items-center h-16 flex-shrink-0 px-4 bg-gray-900 text-white">
            <i class="fas fa-shield-alt text-2xl text-sky-400"></i>
            <span class="ml-3 font-semibold text-xl">ComplianceHub</span>
        </div>
        <div class="h-0 flex-1 flex flex-col overflow-y-auto bg-gray-800">
            <nav class="flex-1 px-2 py-4 space-y-1">
                <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                    <i class="fas fa-tachometer-alt mr-3 fa-fw"></i>
                    Dashboard
                </a>
                <a href="{{ route('projects.index') }}" class="{{ request()->routeIs('projects.index') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                    <i class="fas fa-folder mr-3 fa-fw"></i>
                    Projects
                </a>
                
                <a href="{{ route('evidence.show', ['project' => 1]) }}" class="{{ request()->routeIs('evidence.show') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                    <i class="fas fa-folder-open mr-3 fa-fw"></i>
                    Evidence Hub
                </a>

                @can('is-admin')
                <a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.index') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                    <i class="fas fa-users-cog mr-3 fa-fw"></i>
                    User Management
                </a>
                @endcan
            </nav>
        </div>
    </div>
</div>

