<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use App\Mail\LoginOtpMail;

class AuthController extends Controller
{
    /**
     * Display the login form.
     */
    public function showLoginForm()
    {
        if (auth()->check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    /**
     * Handle a login request to the application.
     */
    public function login(Request $request)
    {
        // ... (login logic remains the same)
        $credentials = $request->validate([
            'username_or_email' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('username', $credentials['username_or_email'])
                    ->orWhere('email', $credentials['username_or_email'])
                    ->first();

        if ($user && Hash::check($credentials['password'], $user->password)) {
            $otp = random_int(100000, 999999);
            
            $user->otp = $otp;
            $user->otp_expires_at = now()->addMinutes(5);
            $user->save();

            $request->session()->put('temp_user_id', $user->id);

            try {
                Mail::to($user->email)->send(new LoginOtpMail($otp));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('OTP Mail sending failed: ' . $e->getMessage(), [
                    'exception' => $e
                ]);
                return back()->withErrors([
                    'username_or_email' => 'Could not send OTP. Please check mail configuration and try again.',
                ]);
            }

            $request->session()->regenerate();

            return redirect()->route('otp.show')->with('success_message', 'An OTP has been sent to your email.');
        }

        return back()->withErrors([
            'username_or_email' => 'The provided credentials do not match our records.',
        ]);
    }

    /**
     * Show the OTP verification form.
     */
    public function showOtpForm()
    {
        // ... (showOtpForm logic remains the same)
        if (!session('temp_user_id')) {
            return redirect()->route('login')->withErrors(['username_or_email' => 'Please log in first.']);
        }

        return view('auth.verify-otp');
    }

    /**
     * Verify the submitted OTP.
     */
    public function verifyOtp(Request $request)
    {
        // ... (verifyOtp logic remains the same)
        $request->validate([
            'otp' => 'required|string|digits:6',
        ]);

        $userId = session('temp_user_id');
        if (!$userId) {
            return redirect()->route('login')->withErrors(['username_or_email' => 'Your session has expired. Please log in again.']);
        }

        $user = User::find($userId);

        if (!$user || $user->otp !== $request->otp || now()->isAfter($user->otp_expires_at)) {
            return back()->withErrors(['otp' => 'The OTP is invalid or has expired.']);
        }

        Auth::login($user);

        $user->otp = null;
        $user->otp_expires_at = null;
        $user->save();
        $request->session()->forget('temp_user_id');

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
