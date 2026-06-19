<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP - ComplianceHub</title>
    <meta name="description" content="Enter your one-time password to complete two-factor authentication.">
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
                    <i class="fas fa-key"></i>
                </div>
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Two-Factor Authentication</h1>
                <p class="mt-2 text-sm text-slate-500 font-medium">A 6-digit code has been sent to your email</p>
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

            {{-- Email hint --}}
            <div class="flex items-center gap-3 mb-6 p-3 rounded-xl bg-sky-50 border border-sky-100">
                <div class="flex-shrink-0 w-9 h-9 rounded-lg bg-sky-100 flex items-center justify-center">
                    <i class="fas fa-envelope text-sky-500 text-sm"></i>
                </div>
                <p class="text-xs text-sky-700 leading-relaxed">
                    Check your inbox for the verification code. It's valid for <strong>5 minutes</strong>.
                </p>
            </div>

            <form action="{{ route('otp.verify') }}" method="POST" class="space-y-5">
                @csrf
                <div>
                    <label for="otp" class="form-label text-center block">One-Time Password</label>
                    <input type="text" name="otp" id="otp"
                           class="otp-input" placeholder="• • • • • •"
                           required autofocus inputmode="numeric"
                           pattern="[0-9]*" autocomplete="one-time-code"
                           maxlength="6">
                </div>
                <button type="submit" class="auth-btn">
                    <i class="fas fa-shield-check mr-2"></i> Verify Code
                </button>
            </form>

            <div class="mt-6 pt-5 border-t border-slate-100 text-center">
                <p class="text-sm text-slate-500">
                    Didn't receive the code?
                    <a href="{{ route('login') }}" class="font-semibold text-sky-600 hover:text-sky-500 transition-colors">Try logging in again</a>
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
