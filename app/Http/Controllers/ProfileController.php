<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use App\Models\comments;
use App\Models\Answers;
use App\Models\User;
use Illuminate\Support\Facades\File;


class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();

        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'Informação de perfil actualizada');
    }


    public function updatePhoto(Request $request)
    {

        $user = $request->user();

        // The file lands in the public web root: accept real images only, and never take the
        // file name or extension from the upload.
        $request->validate(['photo' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:5120']]);

        $filename = $user->id . '_' . bin2hex(random_bytes(8)) . '.png';
        $img = Image::make($request->file('photo'));
        $img->fit(200)->circle(100, 100, 100);
        $img->save(public_path('img/users/' . $filename), null, 'png');

        $old = $user->photo;
        $user->photo = 'img/users/' . $filename;
        $user->save();
        if ($old && str_starts_with($old, 'img/users/') && basename($old) !== 'default.png' && File::exists(public_path($old))) {
            File::delete(public_path($old));
        }

        return Redirect::route('profile.edit')->with('photostatus', 'Foto do perfil actualizada');
    }
    public function deletePhoto(Request $request)
    {
        $user = $request->user();
        $photoPath = public_path((string) $user->photo);

        // Only ever this user's own upload, never a shared image such as the default avatar.
        if ($user->photo && str_starts_with($user->photo, 'img/users/') && !str_contains($user->photo, '..')
            && basename($user->photo) !== 'default.png' && File::exists($photoPath)) {
            File::delete($photoPath);
            $user->photo = null;
            $user->save();

            return redirect()->back()->with('photodeletestatus', 'Foto do perfil apagada.');
        }

        return redirect()->back()->with('status', 'No photo to delete.');
    }
    public function updateSubscription(Request $request)
    {
        $user = $request->user();
        $user->subscribe = $request->input('subscribe');
        $user->save();
        return redirect()->back()->with('subscribestatus', 'Estado de subscrição actualizado.');
    }
    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current-password'],
        ]);

        $user = $request->user();

        // Update comments
        comments::where('users_id', $user->id)
            ->update(['users_id' => 1]);

        // Update answers
        answers::where('users_id', $user->id)
            ->update(['users_id' => 1]);

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
