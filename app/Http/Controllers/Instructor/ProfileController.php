<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = auth()->user()->load('professor.department');
        
        return Inertia::render('Instructor/Settings', [
            'user' => $user,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'id_number' => ['required', 'integer', 'unique:users,id_number,' . auth()->id()],
        ]);

        $request->user()->update($validated);

        return back()->with('flash', [
            'type' => 'success',
            'message' => 'Profile updated successfully!'
        ]);
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('flash', [
            'type' => 'success',
            'message' => 'Password updated successfully!'
        ]);
    }
}

