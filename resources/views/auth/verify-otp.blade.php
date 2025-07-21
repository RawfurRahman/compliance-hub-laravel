<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP - ComplianceHub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 font-sans antialiased">

<div class="flex flex-col items-center justify-center min-h-screen bg-gradient-to-br from-blue-800 to-indigo-900 text-white p-4">
    <div class="bg-white rounded-lg shadow-xl p-8 w-full max-w-md text-gray-800">
        <div class="text-center mb-6">
            <div class="w-24 h-24 mx-auto mb-4 bg-blue-100 rounded-full flex items-center justify-center">
                <i class="fas fa-key text-blue-600 text-5xl"></i>
            </div>
            <h1 class="text-3xl font-extrabold text-gray-900">Two-Factor Authentication</h1>
            <p class="mt-2 text-sm text-gray-600">A 6-digit code has been sent to your email address.</p>
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

        {{-- Display Success Messages from previous page --}}
        @if (session('success_message'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4 text-sm" role="alert">
                {{ session('success_message') }}
            </div>
        @endif

        <form action="{{ route('otp.verify') }}" method="POST" class="space-y-5">
            @csrf  {{-- CSRF Protection Token --}}
            <div>
                <label for="otp" class="block text-sm font-medium text-gray-700 sr-only">One-Time Password</label>
                <input type="text" name="otp" id="otp" class="block w-full px-4 py-3 text-center text-2xl tracking-[1em] border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500" placeholder="------" required autofocus inputmode="numeric" pattern="[0-9]*" autocomplete="one-time-code">
            </div>
            <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-lg font-medium text-white bg-blue-700 hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out">
                Verify Code
            </button>
        </form>
        <p class="mt-6 text-center text-sm text-gray-600">
            Didn't receive the code? <a href="{{ route('login') }}" class="font-medium text-blue-600 hover:text-blue-500">Try logging in again.</a>
        </p>
    </div>
</div>

</body>
</html>
