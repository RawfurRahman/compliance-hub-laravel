<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ComplianceHub</title>
    <meta name="description" content="Securely access your ComplianceHub compliance management dashboard.">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="{{ asset('css/main.css') }}" rel="stylesheet">
</head>
<body class="font-sans antialiased">

<div class="auth-bg flex items-center justify-center min-h-screen p-4">

    {{-- Floating grid pattern --}}
    <div style="position:absolute;inset:0;background-image:radial-gradient(rgba(255,255,255,0.04) 1px, transparent 1px);background-size:32px 32px;pointer-events:none;z-index:1;"></div>

    <div class="w-full max-w-md fade-in-up" style="z-index:10;">
        <div class="auth-card p-8 sm:p-10">

            {{-- Logo --}}
            <div class="text-center mb-8">
                <div class="auth-logo">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Welcome Back</h1>
                <p class="mt-2 text-sm text-slate-500 font-medium">Securely access your compliance dashboard</p>
            </div>

            {{-- Validation Errors --}}
            @if ($errors->any())
                <div class="alert-error mb-5">
                    <div class="flex items-start gap-2">
                        <i class="fas fa-exclamation-circle mt-0.5 text-rose-500"></i>
                        <ul class="space-y-0.5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            {{-- Success Messages --}}
            @if (session('success_message'))
                <div class="alert-success mb-5">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-check-circle text-emerald-500"></i>
                        <span>{{ session('success_message') }}</span>
                    </div>
                </div>
            @endif

            <form action="{{ route('login') }}" method="POST" class="space-y-5">
                @csrf

                {{-- Username / Email --}}
                <div>
                    <label for="username_or_email" class="form-label">Username or Email</label>
                    <div class="auth-input-group">
                        <input type="text" name="username_or_email" id="username_or_email"
                               class="auth-input" placeholder="Enter your username or email"
                               required value="{{ old('username_or_email') }}" autofocus>
                        <i class="fas fa-user auth-input-icon"></i>
                    </div>
                </div>

                {{-- Password --}}
                <div>
                    <label for="password" class="form-label">Password</label>
                    <div class="auth-input-group">
                        <input type="password" name="password" id="password"
                               class="auth-input" placeholder="Enter your password" required>
                        <i class="fas fa-lock auth-input-icon"></i>
                    </div>
                </div>

                {{-- Submit --}}
                <button type="submit" class="auth-btn mt-2">
                    <i class="fas fa-arrow-right-to-bracket mr-2"></i> Sign In
                </button>
            </form>

            <div class="mt-6 pt-5 border-t border-slate-100 text-center space-y-2">
                <p class="text-sm text-slate-500">
                    Forgot your password? <a href="#" class="font-semibold text-sky-600 hover:text-sky-500 transition-colors">Reset it here</a>
                </p>
                <p class="text-xs text-slate-400">
                    Don't have an account? Contact your administrator.
                </p>
            </div>
        </div>

        {{-- Footer --}}
        <p class="text-center text-xs text-slate-500/60 mt-6">
            &copy; {{ date('Y') }} ComplianceHub &mdash; Enterprise Compliance Management
        </p>
    </div>
</div>

</body>
</html>
