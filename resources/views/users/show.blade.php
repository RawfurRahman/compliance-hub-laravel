@extends('layouts.app')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">User Details</h1>
        <p class="mt-1 text-sm text-gray-500">Viewing details for <span class="font-semibold">{{ $user->username }}</span>.</p>
    </div>

    <div class="bg-white shadow rounded-lg p-6 max-w-lg mx-auto">
        <dl class="space-y-4">
            <div>
                <dt class="text-sm font-medium text-gray-500">Username</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $user->username }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Email</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $user->email }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Role</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $user->roles->pluck('name')->implode(', ') ?: 'No role assigned' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Registered</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $user->created_at->format('M d, Y H:i') }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Verified</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $user->is_verified ? 'Yes' : 'No' }}</dd>
            </div>
        </dl>

        <div class="flex items-center justify-end space-x-4 pt-6">
            <a href="{{ route('users.index') }}" class="text-gray-600 hover:text-gray-900">Back to Users</a>
            <a href="{{ route('users.edit', $user) }}" class="inline-flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                Edit User
            </a>
        </div>
    </div>
@endsection
