<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ComplianceHub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 font-sans antialiased">

<div class="flex flex-col items-center justify-center min-h-screen bg-gradient-to-br from-blue-800 to-indigo-900 text-white p-4">
    <div class="bg-white rounded-lg shadow-xl p-8 w-full max-w-md text-gray-800">
        <div class="text-center mb-6">
            <!-- ComplianceHub Logo Placeholder -->
            <div class="w-24 h-24 mx-auto mb-4 bg-blue-100 rounded-full flex items-center justify-center">
                <i class="fas fa-shield-alt text-blue-600 text-5xl"></i>
            </div>
            <h1 class="text-3xl font-extrabold text-gray-900">Welcome Back!</h1>
            <p class="mt-2 text-sm text-gray-600">Securely access your compliance dashboard.</p>
        </div>
        
        {{-- Display Validation Errors --}}
        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4 text-sm" role="alert">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Display Success Messages --}}
        @if (session('success_message'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4 text-sm" role="alert">
                {{ session('success_message') }}
            </div>
        @endif

        <form action="{{ route('login') }}" method="POST" class="space-y-5">
            @csrf  {{-- CSRF Protection Token --}}
            <div>
                <label for="username_or_email" class="block text-sm font-medium text-gray-700 sr-only">Username or Email</label>
                <input type="text" name="username_or_email" id="username_or_email" class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500" placeholder="Username or Email" required value="{{ old('username_or_email') }}">
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 sr-only">Password</label>
                <input type="password" name="password" id="password" class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500" placeholder="Password" required>
            </div>
            <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-lg font-medium text-white bg-blue-700 hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out">
                Login
            </button>
        </form>
        <p class="mt-6 text-center text-sm text-gray-600">
            Forgot your password? <a href="#" class="font-medium text-blue-600 hover:text-blue-500">Reset it here</a>
        </p>
        <p class="mt-2 text-center text-sm text-gray-600">
            Don't have an account? Please contact your Admin.
        </p>
    </div>
</div>

</body>
</html>
