<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Not Available — {{ config('app.name', 'ComplianceHub') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="font-sans antialiased bg-slate-50 flex items-center justify-center min-h-screen">
    <div class="text-center max-w-md px-4">
        <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-6">
            <i class="fas fa-lock text-slate-400 text-xl"></i>
        </div>
        <h1 class="text-2xl font-bold text-slate-800 mb-2">Trust Center Not Available</h1>
        <p class="text-slate-500 text-sm">
            The trust center you're looking for isn't publicly available yet. Please check back later or contact the project team for more information.
        </p>
    </div>
</body>
</html>
