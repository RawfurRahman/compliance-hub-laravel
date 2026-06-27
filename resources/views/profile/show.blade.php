<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $user->username ?? 'User' }}'s Profile - ComplianceHub</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Navigation -->
        <x-nav>
            <x-slot name="user">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="flex items-center text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none transition duration-150 ease-in-out">
                            <div class="w-8 h-8 rounded-full bg-gray-300 flex items-center justify-center mr-2">
                                <span class="text-sm font-medium text-gray-700">{{ substr($user->username ?? $user->email, 0, 1) }}</span>
                            </div>
                            <span>{{ $user->username ?? $user->email }}</span>
                            <svg class="ml-2 -mr-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                    </x-slot>
                    
                    <x-slot name="content">
                        <x-dropdown-item href="{{ route('profile.show') }}">Profile</x-dropdown-item>
                        <x-dropdown-item href="{{ route('profile.settings') }}">Settings</x-dropdown-item>
                        <x-dropdown-item href="{{ route('logout') }}" 
                                         onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            {{ __('Logout') }}
                        </x-dropdown-item>
                        <form id="logout-form" method="POST" action="{{ route('logout') }}">
                            @csrf
                        </form>
                    </x-slot>
                </x-dropdown>
            </x-slot>
        </x-nav>

        <div class="py-10">
            <header>
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <h1 class="text-3xl font-bold leading-tight text-gray-900">Your Profile</h1>
                    <p class="mt-2 text-sm text-gray-600">Manage your account information and preferences</p>
                </div>
            </header>

            <main>
                <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div class="px-4 py-6 sm:px-0">
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            <!-- Profile Information -->
                            <div class="lg:col-span-2">
                                <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                                    <div class="p-6">
                                        <h2 class="text-xl font-semibold text-gray-900 mb-6">Profile Information</h2>
                                        
                                        @if (session('success'))
                                            <div class="mb-4 p-4 bg-green-50 border-l-4 border-green-400 text-green-700">
                                                <p>{{ session('success') }}</p>
                                            </div>
                                        @endif

                                        <form method="POST" action="{{ route('profile.update') }}">
                                            @csrf
                                            
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                                <div>
                                                    <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                                                    <input type="text" name="username" id="username" value="{{ old('username', $user->username) }}" required
                                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                                    @error('username')
                                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                                    @enderror
                                                </div>

                                                <div>
                                                    <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                                                    <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required
                                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                                    @error('email')
                                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                                    @enderror
                                                </div>
                                            </div>

                                            <div class="mt-6">
                                                <label for="current_password" class="block text-sm font-medium text-gray-700">Current Password (to confirm changes)</label>
                                                <input type="password" name="current_password" id="current_password" required
                                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                                @error('current_password')
                                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                                @enderror
                                            </div>

                                            <div class="mt-6">
                                                <label for="password" class="block text-sm font-medium text-gray-700">New Password (optional)</label>
                                                <input type="password" name="password" id="password"
                                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                                @error('password')
                                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                                @enderror
                                                <p class="mt-1 text-xs text-gray-500">If you want to change your password, enter a new one here.</p>
                                            </div>

                                            <div class="mt-6">
                                                <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                                                <input type="password" name="password_confirmation" id="password_confirmation"
                                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                            </div>

                                            <div class="mt-8">
                                                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition duration-150 ease-in-out">
                                                    Update Profile
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- User Information Card -->
                            <div class="lg:col-span-1">
                                <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                                    <div class="p-6">
                                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Account Details</h2>
                                        
                                        <div class="space-y-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">User ID</label>
                                                <p class="mt-1 text-sm text-gray-900">{{ $user->id }}</p>
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Email Verified</label>
                                                <p class="mt-1 text-sm {{ $user->email_verified_at ? 'text-green-600' : 'text-yellow-600' }}">
                                                    {{ $user->email_verified_at ? 'Yes' : 'No' }}
                                                </p>
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Account Created</label>
                                                <p class="mt-1 text-sm text-gray-900">{{ $user->created_at->format('M d, Y') }}</p>
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Last Updated</label>
                                                <p class="mt-1 text-sm text-gray-900">{{ $user->updated_at->format('M d, Y g:i A') }}</p>
                                            </div>

                                            @if($user->hasRole('Admin'))
                                                <div class="mt-6 p-4 bg-blue-50 rounded-lg">
                                                    <h3 class="text-sm font-medium text-blue-900">Admin User</h3>
                                                    <p class="mt-1 text-xs text-blue-700">You have administrator privileges</p>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    @vite(['resources/js/app.js'])
</body>
</html>