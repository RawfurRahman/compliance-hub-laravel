<div class="flex items-center justify-between h-16 px-4 border-b border-gray-200 bg-white">
    <div class="flex md:hidden">
        <button @click="mobileMenuOpen = !mobileMenuOpen" class="text-gray-500 hover:text-gray-700 focus:outline-none">
            <i class="fas fa-bars text-xl"></i>
        </button>
    </div>
    
    <div class="flex-1 max-w-md ml-4 md:ml-6">
        <div class="relative">
            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                <i class="fas fa-search text-gray-400"></i>
            </div>
            <input class="block w-full py-2 pl-10 pr-3 text-sm bg-gray-100 border border-gray-300 rounded-md focus:bg-white focus:border-blue-300 focus:ring-blue-300 focus:outline-none" placeholder="Search...">
        </div>
    </div>
    
    <div class="flex items-center space-x-4">
        <button @click="showModal = true" class="hidden sm:inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            <i class="fas fa-plus mr-2"></i> New Project
        </button>

        <button class="p-1 text-gray-500 rounded-full hover:text-gray-700 hover:bg-gray-100 focus:outline-none">
            <i class="fas fa-bell text-lg"></i>
        </button>
        
        <div class="relative ml-3" x-data="{ open: false }">
            <div>
                <button @click="open = !open" class="flex items-center max-w-xs text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <img class="w-8 h-8 rounded-full" src="https://randomuser.me/api/portraits/men/32.jpg" alt="User avatar">
                </button>
            </div>
            <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 w-48 py-1 mt-2 origin-top-right bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none" role="menu">
                <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">Your Profile</a>
                
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <a href="{{ route('logout') }}"
                       onclick="event.preventDefault(); this.closest('form').submit();"
                       class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">
                        Sign out
                    </a>
                </form>
            </div>
        </div>
    </div>
</div>
