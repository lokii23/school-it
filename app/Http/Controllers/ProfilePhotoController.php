<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfilePhotoController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'photo.max'   => 'The photo must be 2 MB or smaller.',
            'photo.image' => 'Please choose a valid image file.',
            'photo.mimes' => 'Only JPG, PNG or WebP photos are allowed.',
        ]);

        $user = $request->user();

        // Random file name, stored in storage/app/public/avatars
        $path = $request->file('photo')->store('avatars', 'public');

        if ($user->photo) {
            Storage::disk('public')->delete($user->photo);
        }

        $user->photo = $path;
        $user->save();

        return back()->with('success', 'Profile photo updated.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->photo) {
            Storage::disk('public')->delete($user->photo);
            $user->photo = null;
            $user->save();
        }

        return back()->with('success', 'Profile photo removed.');
    }
}