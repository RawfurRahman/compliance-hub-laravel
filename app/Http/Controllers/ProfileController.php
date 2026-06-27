<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    /**
     * Display the authenticated user's profile.
     */
    public function show()
    {
        $user = Auth::user();
        return view('profile.show', compact('user'));
    }

    /**
     * Update the authenticated user's profile.
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'username' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'current_password' => ['required', 'string'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ]);

        // Verify current password
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'The current password is incorrect.']);
        }

        $userData = [
            'username' => $request->username,
            'email' => $request->email,
        ];

        if ($request->filled('password')) {
            $userData['password'] = $request->password;
        }

        $user->update($userData);

        return redirect()->route('profile.show')->with('success', 'Your profile has been updated successfully.');
    }

    /**
     * Display the user's settings/preferences.
     */
    public function settings()
    {
        $user = Auth::user();
        $settings = json_decode($user->settings ?? '{}', true);
        
        return view('profile.settings', compact('user', 'settings'));
    }

    /**
     * Update the user's settings/preferences.
     */
    public function updateSettings(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'theme' => ['required', 'string', 'in:light,dark,system'],
            'notifications_email' => ['boolean'],
            'notifications_browser' => ['boolean'],
            'language' => ['required', 'string', 'in:en,es,fr,de,it'],
            'timezone' => ['required', 'string'],
            'date_format' => ['required', 'string'],
        ]);

        $settings = [
            'theme' => $request->theme,
            'notifications_email' => $request->boolean('notifications_email'),
            'notifications_browser' => $request->boolean('notifications_browser'),
            'language' => $request->language,
            'timezone' => $request->timezone,
            'date_format' => $request->date_format,
            'last_updated' => now()->toDateTimeString(),
        ];

        $user->update(['settings' => json_encode($settings)]);

        return redirect()->route('profile.settings')->with('success', 'Your settings have been updated successfully.');
    }
}