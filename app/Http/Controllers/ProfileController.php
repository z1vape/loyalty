<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    /**
     * Display the authenticated user's profile.
     */
    public function show()
    {
        $user = auth()->user()->load('roles', 'stores');

        return view('profile.show', compact('user'));
    }

    /**
     * Update the authenticated user's profile details.
     */
    public function update(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
            'gender' => ['required', 'string', 'in:Male,Female'],
            'address' => ['nullable', 'string'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username,' . $user->id],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'mobile_phone' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string', Password::defaults()],
        ]);

        if ($request->hasFile('photo')) {
            if ($user->photo) {
                Storage::disk('public')->delete($user->photo);
            }
            $validated['photo'] = $request->file('photo')->store('users', 'public');
        } else {
            unset($validated['photo']);
        }

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()->route('profile.show')->with('success', 'Profile updated successfully.');
    }
}
