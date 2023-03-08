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

        return Redirect::route('profile.edit')->with('status', 'profile information updated');
    }


    public function updatePhoto(Request $request)
    {

        $user = $request->user();

        if ($request->hasFile('photo')) {
            $image = $request->file('photo');
            $filename = time() . '.' . $image->getClientOriginalExtension();
            $img = Image::make($image);
            $img->fit(200)->circle(100, 100, 100);
            $img->save(public_path('img/users/' . $filename));
            $user->photo = 'img/users/' . $filename;
            $request->user()->save();
        }

        return Redirect::route('profile.edit')->with('photostatus', 'Profile photo updated');
    }
    public function deletePhoto(Request $request)
    {
        $user = $request->user();
        $photoPath = public_path($user->photo);

        if (File::exists($photoPath)) {
            File::delete($photoPath);
            $user->photo = null;
            $user->save();

            return redirect()->back()->with('photodeletestatus', 'Profile photo deleted.');
        }

        return redirect()->back()->with('status', 'No photo to delete.');
    }
    public function updateSubscription(Request $request)
    {
        $user = $request->user();
        $user->subscribe = $request->input('subscribe');
        $user->save();
        return redirect()->back()->with('subscribestatus', 'Subscription status updated.');
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
