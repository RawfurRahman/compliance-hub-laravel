<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Security Tasks - Compliance Hub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">My Security Tasks</h1>
                <p class="text-gray-600 mt-1">Items assigned to you that need attention</p>
            </div>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-50 text-blue-700">
                {{ count($tasks) }} task{{ count($tasks) !== 1 ? 's' : '' }}
            </span>
        </div>

        @if(count($tasks) === 0)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-gray-400 text-sm">No pending tasks assigned to you.</p>
            </div>
        @else
            @foreach($grouped as $groupName => $groupTasks)
                <div class="mb-8">
                    <div class="flex items-center gap-2 mb-3">
                        <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">{{ $groupName }}</h2>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                            {{ count($groupTasks) }}
                        </span>
                    </div>
                    <div class="space-y-2">
                        @foreach($groupTasks as $task)
                            @php
                                $priorityColors = [
                                    'overdue' => ['border' => 'border-l-red-500', 'badge' => 'bg-red-50 text-red-700'],
                                    'due_soon' => ['border' => 'border-l-amber-500', 'badge' => 'bg-amber-50 text-amber-700'],
                                    'pending' => ['border' => 'border-l-blue-500', 'badge' => 'bg-blue-50 text-blue-700'],
                                    'attention' => ['border' => 'border-l-gray-400', 'badge' => 'bg-gray-50 text-gray-600'],
                                ];
                                $pc = $priorityColors[$task['priority']] ?? $priorityColors['attention'];
                            @endphp
                            <a href="{{ $task['url'] }}" class="block bg-white rounded-xl shadow-sm border border-gray-200 border-l-4 {{ $pc['border'] }} p-4 hover:shadow-md transition-shadow">
                                <div class="flex items-start justify-between">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-medium text-gray-400 uppercase tracking-wide">{{ $task['type'] }}</span>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $pc['badge'] }}">
                                                {{ $task['status'] }}
                                            </span>
                                        </div>
                                        <h3 class="text-sm font-semibold text-gray-900 mt-1 truncate">{{ $task['title'] }}</h3>
                                        @if($task['description'])
                                            <p class="text-xs text-gray-500 mt-0.5">{{ $task['description'] }}</p>
                                        @endif
                                    </div>
                                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        @endif
    </div>
</body>
</html>
