@extends('layouts.app')

@section('content')
    <!-- Page Header -->
    <div class="mb-10">
        <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">User <span class="text-sky-600">Management</span></h1>
        <p class="mt-1 text-md text-slate-500 font-medium">Enterprise directory and system access control.</p>
    </div>

    {{-- Success and Error Messages --}}
    @if (session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
            <p>{{ session('success') }}</p>
        </div>
    @endif
    @if (session('error'))
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
            <p>{{ session('error') }}</p>
        </div>
    @endif
    @if ($errors->any())
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Add New User Form -->
        <div class="lg:col-span-1">
            <div class="glass-card rounded-3xl p-8 border border-white/60 shadow-xl overflow-hidden relative">
                <div class="absolute -right-20 -top-20 w-80 h-80 bg-sky-500/5 rounded-full blur-3xl pointer-events-none"></div>
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-6 flex items-center">
                    <i class="fas fa-user-plus mr-2 text-sky-500"></i> Provision Account
                </h3>
                <form action="{{ route('users.store') }}" method="POST" class="space-y-5 relative z-10">
                    @csrf
                    <div>
                        <label for="username" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Username</label>
                        <input type="text" name="username" id="username" value="{{ old('username') }}" required class="block w-full py-2.5 px-4 bg-slate-50/50 border border-slate-200 rounded-xl focus:bg-white focus:border-sky-400 focus:ring-4 focus:ring-sky-400/10 focus:outline-none transition-all duration-200 text-sm">
                    </div>
                    <div>
                        <label for="email" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Email Address</label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" required class="block w-full py-2.5 px-4 bg-slate-50/50 border border-slate-200 rounded-xl focus:bg-white focus:border-sky-400 focus:ring-4 focus:ring-sky-400/10 focus:outline-none transition-all duration-200 text-sm">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Password</label>
                            <input type="password" name="password" id="password" required class="block w-full py-2.5 px-4 bg-slate-50/50 border border-slate-200 rounded-xl focus:bg-white focus:border-sky-400 focus:ring-4 focus:ring-sky-400/10 focus:outline-none transition-all duration-200 text-sm">
                        </div>
                        <div>
                            <label for="password_confirmation" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Confirm</label>
                            <input type="password" name="password_confirmation" id="password_confirmation" required class="block w-full py-2.5 px-4 bg-slate-50/50 border border-slate-200 rounded-xl focus:bg-white focus:border-sky-400 focus:ring-4 focus:ring-sky-400/10 focus:outline-none transition-all duration-200 text-sm">
                        </div>
                    </div>
                    <div>
                        <label for="role_id" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">System Role</label>
                        <select name="role_id" id="role_id" required class="block w-full py-2.5 px-4 bg-slate-100/50 border border-slate-200 rounded-xl focus:bg-white focus:border-sky-400 focus:ring-4 focus:ring-sky-400/10 focus:outline-none transition-all duration-200 text-sm appearance-none">
                            <option value="">Select authorization level...</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="pt-2">
                        <button type="submit" class="w-full py-3 px-4 btn-premium rounded-xl text-xs font-bold uppercase tracking-widest shadow-lg transform transition active:scale-[0.98]">
                            Complete Provisioning
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Users List -->
        <div class="lg:col-span-2">
            <div class="glass-card rounded-3xl overflow-hidden border border-white/60 shadow-xl">
                 <div class="px-8 py-6 border-b border-slate-100 bg-slate-50/30">
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest flex items-center">
                        <i class="fas fa-users mr-2 text-indigo-500"></i> Active System Directory
                    </h3>
                </div>
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50/50">
                        <tr>
                            <th scope="col" class="px-8 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">Identity</th>
                            <th scope="col" class="px-8 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">Access Role</th>
                            <th scope="col" class="px-8 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">Registered</th>
                            <th scope="col" class="relative px-8 py-4"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse ($users as $user)
                            <tr class="hover:bg-slate-50 transition-colors group">
                                <td class="px-8 py-5 flex items-center whitespace-nowrap">
                                    <div class="w-10 h-10 rounded-full bg-slate-100 border border-slate-100 flex items-center justify-center text-slate-400 font-bold mr-4 group-hover:bg-white group-hover:text-sky-500 transition-all">
                                        {{ substr($user->username, 0, 1) }}
                                    </div>
                                    <div>
                                        <div class="text-sm font-bold text-slate-800">{{ $user->username }}</div>
                                        <div class="text-[11px] font-medium text-slate-400">{{ $user->email }}</div>
                                    </div>
                                </td>
                                <td class="px-8 py-5 whitespace-nowrap">
                                    <span class="px-3 py-1 inline-flex text-[10px] font-bold uppercase tracking-widest rounded-full bg-indigo-50 text-indigo-700 border border-indigo-100">
                                        {{ $user->roles->first()->name ?? 'No Role' }}
                                    </span>
                                </td>
                                <td class="px-8 py-5 whitespace-nowrap text-[11px] font-medium text-slate-400">
                                    {{ $user->created_at->format('M d, Y') }}
                                </td>
                                <td class="px-8 py-5 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end gap-4">
                                        <a href="{{ route('users.edit', $user) }}" class="text-sky-600 hover:text-indigo-700 font-bold text-xs uppercase tracking-widest transition-colors">Edit</a>
                                        
                                        @if(auth()->id() !== $user->id)
                                        <form action="{{ route('users.destroy', $user) }}" method="POST" class="inline-block" onsubmit="return confirm('Terminate this user access?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-rose-400 hover:text-rose-600 transition-colors">
                                                <i class="fas fa-trash-alt text-xs"></i>
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-8 py-10 text-center text-sm font-bold text-slate-300">No active sessions found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
