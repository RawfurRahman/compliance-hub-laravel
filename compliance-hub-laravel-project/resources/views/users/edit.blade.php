@extends('layouts.app')

@section('content')
    <!-- Page Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Edit User</h1>
        <p class="mt-1 text-sm text-gray-500">Update user details for <span class="font-semibold">{{ $user->username }}</span>.</p>
    </div>

    <div class="bg-white shadow rounded-lg p-6 max-w-lg mx-auto">
        
        {{-- Validation Errors --}}
        @if ($errors->any())
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('users.update', $user) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT') {{-- Specify the request method as PUT for updates --}}
            
            <div>
                <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                <input type="text" name="username" id="username" value="{{ old('username', $user->username) }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
            </div>
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
            </div>

            <hr>

            <p class="text-sm text-gray-500">Leave the password fields blank if you do not want to change the password.</p>
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">New Password</label>
                <input type="password" name="password" id="password" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
            </div>
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                <input type="password" name="password_confirmation" id="password_confirmation" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
            </div>

            <hr>

            <div>
                <label for="role_id" class="block text-sm font-medium text-gray-700">Role</label>
                <select name="role_id" id="role_id" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    @foreach($roles as $role)
                        {{-- Select the user's current role by default --}}
                        <option value="{{ $role->id }}" @if($user->roles->first()->id == $role->id) selected @endif>
                            {{ $role->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center justify-end space-x-4 pt-4">
                <a href="{{ route('users.index') }}" class="text-gray-600">Cancel</a>
                <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                    Update User
                </button>
            </div>
        </form>
    </div>
@endsection
