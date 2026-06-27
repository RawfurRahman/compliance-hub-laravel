<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - ComplianceHub</title>
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
                                <span class="text-sm font-medium text-gray-700">{{ substr(auth()->user()->username ?? auth()->user()->email, 0, 1) }}</span>
                            </div>
                            <span>{{ auth()->user()->username ?? auth()->user()->email }}</span>
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
                    <h1 class="text-3xl font-bold leading-tight text-gray-900">User Settings</h1>
                    <p class="mt-2 text-sm text-gray-600">Customize your application preferences and display options</p>
                </div>
            </header>

            <main>
                <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                    <div class="px-4 py-6 sm:px-0">
                        @if (session('success'))
                            <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-400 text-green-700">
                                <p>{{ session('success') }}</p>
                            </div>
                        @endif

                        <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                            <div class="p-6">
                                <h2 class="text-xl font-semibold text-gray-900 mb-6">Application Preferences</h2>
                                
                                <form method="POST" action="{{ route('profile.update-settings') }}">
                                    @csrf
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label for="theme" class="block text-sm font-medium text-gray-700">Theme</label>
                                            <select name="theme" id="theme" required
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                                <option value="light" {{ old('theme', $settings['theme'] ?? 'light') === 'light' ? 'selected' : '' }}>Light Mode</option>
                                                <option value="dark" {{ old('theme', $settings['theme'] ?? 'light') === 'dark' ? 'selected' : '' }}>Dark Mode</option>
                                                <option value="system" {{ old('theme', $settings['theme'] ?? 'light') === 'system' ? 'selected' : '' }}>System Default</option>
                                            </select>
                                        </div>

                                        <div>
                                            <label for="language" class="block text-sm font-medium text-gray-700">Language</label>
                                            <select name="language" id="language" required
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                                <option value="en" {{ old('language', $settings['language'] ?? 'en') === 'en' ? 'selected' : '' }}>English</option>
                                                <option value="es" {{ old('language', $settings['language'] ?? 'en') === 'es' ? 'selected' : '' }}>Spanish</option>
                                                <option value="fr" {{ old('language', $settings['language'] ?? 'en') === 'fr' ? 'selected' : '' }}>French</option>
                                                <option value="de" {{ old('language', $settings['language'] ?? 'en') === 'de' ? 'selected' : '' }}>German</option>
                                                <option value="it" {{ old('language', $settings['language'] ?? 'en') === 'it' ? 'selected' : '' }}>Italian</option>
                                            </select>
                                        </div>

                                        <div>
                                            <label for="timezone" class="block text-sm font-medium text-gray-700">Timezone</label>
                                            <select name="timezone" id="timezone" required
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                                <option value="America/New_York" {{ old('timezone', $settings['timezone'] ?? 'America/New_York') === 'America/New_York' ? 'selected' : '' }}>Eastern Time</option>
                                                <option value="America/Chicago" {{ old('timezone', $settings['timezone'] ?? 'America/New_York') === 'America/Chicago' ? 'selected' : '' }}>Central Time</option>
                                                <option value="America/Denver" {{ old('timezone', $settings['timezone'] ?? 'America/New_York') === 'America/Denver' ? 'selected' : '' }}>Mountain Time</option>
                                                <option value="America/Los_Angeles" {{ old('timezone', $settings['timezone'] ?? 'America/New_York') === 'America/Los_Angeles' ? 'selected' : '' }}>Pacific Time</option>
                                                <option value="Europe/London" {{ old('language', $settings['language'] ?? 'en') === 'en' ? 'selected' : '' }}>London Time</option>
                                                <option value="Europe/Paris" {{ old('language', $settings['language'] ?? 'en') === 'fr' ? 'selected' : '' }}>Paris Time</option>
                                            </select>
                                        </div>

                                        <div>
                                            <label for="date_format" class="block text-sm font-medium text-gray-700">Date Format</label>
                                            <select name="date_format" id="date_format" required
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                                <option value="Y-m-d" {{ old('date_format', $settings['date_format'] ?? 'Y-m-d') === 'Y-m-d' ? 'selected' : '' }}>2025-06-26 (ISO)</option>
                                                <option value="m/d/Y" {{ old('date_format', $settings['date_format'] ?? 'Y-m-d') === 'm/d/Y' ? 'selected' : '' }}>06/26/2025</option>
                                                <option value="d/m/Y" {{ old('date_format', $settings['date_format'] ?? 'Y-m-d') === 'd/m/Y' ? 'selected' : '' }}>26/06/2025</option>
                                                <option value="F j, Y" {{ old('date_format', $settings['date_format'] ?? 'Y-m-d') === 'F j, Y' ? 'selected' : '' }}>June 26, 2025</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="mt-8 border-t pt-6">
                                        <h3 class="text-lg font-medium text-gray-900 mb-4">Notification Preferences</h3>
                                        
                                        <div class="space-y-4">
                                            <div class="flex items-center">
                                                <input type="checkbox" name="notifications_email" id="notifications_email" value="1" 
                                                    {{ old('notifications_email', $settings['notifications_email'] ?? true) ? 'checked' : '' }}
                                                    class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                <label for="notifications_email" class="ml-2 block text-sm font-medium text-gray-700">
                                                    Email Notifications
                                                </label>
                                            </div>

                                            <div class="flex items-center">
                                                <input type="checkbox" name="notifications_browser" id="notifications_browser" value="1" 
                                                    {{ old('notifications_browser', $settings['notifications_browser'] ?? true) ? 'checked' : '' }}
                                                    class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                <label for="notifications_browser" class="ml-2 block text-sm font-medium text-gray-700">
                                                    Browser Notifications
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-8">
                                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-md transition duration-150 ease-in-out">
                                            Save Settings
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>

        @vite(['resources/js/app.js'])
    </body>
</html>