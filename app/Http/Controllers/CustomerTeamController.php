<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules;

class CustomerTeamController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $teamMembers = $user->getOrganizationUsers();

        return view('team.index', compact('teamMembers'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        if (! $user->isPrimaryCustomer()) {
            return redirect()->route('team.index')->with('error', 'Only the primary account holder can add team members.');
        }

        $request->validate([
            'username' => ['required', 'string', 'max:255', 'unique:users'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $newUser = User::create([
            'username' => $request->username,
            'email' => $request->email,
            'password' => $request->password,
            'parent_id' => $user->id,
            'is_verified' => 1,
        ]);

        return redirect()->route('team.index')->with('success', 'Team member added successfully.');
    }

    public function destroy($team)
    {
        $user = Auth::user();

        $teamMember = User::findOrFail($team);

        if ($teamMember->parent_id !== $user->id && $teamMember->id !== $user->id) {
            return redirect()->route('team.index')->with('error', 'Unauthorized to remove this team member.');
        }

        if ($teamMember->id === $user->id) {
            return redirect()->route('team.index')->with('error', 'You cannot remove yourself.');
        }

        $teamMember->delete();

        return redirect()->route('team.index')->with('success', 'Team member removed successfully.');
    }
}
