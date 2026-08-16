<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StudentProfileController extends Controller
{
    public function edit(Request $request): View
    {
        abort_unless($request->user()?->isStudent(), 403);

        return view('student.profile');
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user?->isStudent(), 403);

        $request->merge([
            'email' => is_string($request->input('email')) ? trim($request->input('email')) : $request->input('email'),
            'contact_phone' => is_string($request->input('contact_phone')) ? trim($request->input('contact_phone')) : $request->input('contact_phone'),
        ]);

        $data = $request->validate([
            'email' => ['required', 'string', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'profile_photo' => ['nullable', 'image', 'max:4096'],
            'password' => ['nullable', 'confirmed', 'string', 'min:8', 'regex:/^(?=.*[A-Za-z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/'],
        ]);

        $user->email = $data['email'];
        $user->contact_phone = $data['contact_phone'] ?? null;

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        if ($request->hasFile('profile_photo')) {
            if ($user->profile_photo_path) {
                File::delete(public_path($user->profile_photo_path));
            }

            $photo = $request->file('profile_photo');
            $fileName = $user->id . '-' . uniqid('', true) . '.' . $photo->extension();
            File::ensureDirectoryExists(public_path('uploads/profile-photos'));
            $photo->move(public_path('uploads/profile-photos'), $fileName);

            $user->profile_photo_path = 'uploads/profile-photos/' . $fileName;
        }

        $user->save();

        return back()->with('status', 'Profile updated.');
    }
}
