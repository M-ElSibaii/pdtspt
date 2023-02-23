<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\comments;
use Illuminate\Http\Request;

class CommentsController extends Controller
{
    //   public function createComment(Request $request, $propertyId)
    // {
    // Validate the user input
    //   $validatedData = $request->validate([
    //       'body' => 'required|string',
    //   ]);

    // Create a new comment
    //  $comment = new Comments;
    //  $comment->body = $validatedData['body'];
    //  $comment->property_id = $propertyId;
    //  $comment->user_id = auth()->user()->id;
    // $comment->save();

    // return redirect()->back()->with('status', 'Comment created successfully!');
    // }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $comments = comments::all();

        return view('pdtssurvey', compact('comments'));
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
        $validatedData = $request->validate([
            'body' => 'required|string',
            'properties_Id' => 'required|integer',
        ]);

        $comment = new comments;
        $comment->body = $validatedData['body'];
        $comment->properties_Id = $request->properties_Id;
        $comment->users_id = Auth::user()->id;
        $comment->published_at = now();
        $comment->created_at = now();
        $comment->updated_at = now();
        $comment->save();

        return redirect()->back();
    }

    public function replyStore(Request $request)
    {
        $validatedData = $request->validate([
            'body' => 'required|string',
            'comment_id' => 'required|integer',
        ]);

        $reply = new comments;
        $reply->body = $validatedData['body'];
        $reply->parent_id = $request->comment_id;
        $reply->users_id = Auth::user()->id;
        $reply->save();

        return redirect()->back();
    }
    /**
     * Display the specified resource.
     *
     * @param  \App\Models\comments  $comments
     * @return \Illuminate\Http\Response
     */
    public function show(comments $comments)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\comments  $comments
     * @return \Illuminate\Http\Response
     */
    public function edit(comments $comments)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\comments  $comments
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
        $comment = comments::find($id);
        $comment->body = $request->input('body');
        $comment->save();
        return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\comments  $comments
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
        $comment = comments::find($id);
        $comment->delete();
        return redirect()->back();
    }
}
