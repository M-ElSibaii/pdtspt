<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        $users = User::paginate(10);
        return view('admin', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateUsers(Request $request)
    {

        // Either list may be absent (a partial form) and ids may be stale (a user deleted
        // since the page loaded): skip what is missing instead of failing.
        $selfRefused = false;
        foreach ((array) $request->input('isAdmin', []) as $userId => $value) {
            $user = User::find($userId);
            if (!$user) {
                continue;
            }
            $wantAdmin = $value == 1;
            // An admin may not change their own admin flag: another admin has to.
            if ((int) $user->id === (int) $request->user()->id && $wantAdmin !== (bool) $user->isAdmin) {
                $selfRefused = true;
                continue;
            }
            $user->isAdmin = $wantAdmin;
            $user->save();
        }

        foreach ((array) $request->input('subscribe', []) as $userId => $value) {
            $user = User::find($userId);
            if (!$user) {
                continue;
            }
            $user->subscribe = $value == 1;
            $user->save();
        }

        if ($selfRefused) {
            return redirect()->back()
                ->withErrors(['isAdmin' => 'You cannot change your own admin status; ask another admin.'])
                ->with('success', 'The other changes were saved.');
        }

        return redirect()->back()->with('success', 'User status updated successfully');
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
