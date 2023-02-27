<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\productdatatemplates;
use App\Models\groupofproperties;
use App\Models\properties;
use App\Models\comments;
use App\Models\Answers;
use App\Models\Likes;
use App\Models\propertiesdatadictionaries;
use App\Models\referencedocuments;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GroupofpropertiesController extends Controller
{
    public function getGroupOfProperties($pdtID)
    {
        $pdt = productdatatemplates::where('Id', $pdtID)
            ->get();
        $gop = DB::table('groupofproperties as gop')->where('pdtId', $pdtID)
            ->join(
                DB::raw("(SELECT 
                GUID,
                MAX(versionNumber) as max_versionNumber,
                MAX(revisionNumber) as max_revisionNumber
                FROM groupofproperties 
                GROUP BY GUID) as mx"),
                function ($join) {
                    $join->on('mx.GUID', '=', 'gop.GUID');
                    $join->on('mx.max_versionNumber', '=', 'gop.versionNumber');
                    $join->on('mx.max_revisionNumber', '=', 'gop.revisionNumber');
                }
            )
            ->get();
        $referenceDocument = referencedocuments::all();
        $properties_dict = propertiesdatadictionaries::all();
        $properties = properties::where('pdtID', $pdtID)->get();

        // Join the properties and propertiesdatadictionaries tables
        $joined_properties = properties::leftJoin('propertiesdatadictionaries', function ($join) {
            $join->on('properties.GUID', '=', 'propertiesdatadictionaries.GUID');
            $join->on(
                DB::raw('(propertiesdatadictionaries.versionNumber, propertiesdatadictionaries.revisionNumber)'),
                DB::raw('(select max(versionNumber), max(revisionNumber) from propertiesdatadictionaries where GUID = properties.GUID)'),
                '='
            );
        })->select(
            'properties.descriptionEn',
            'properties.descriptionPt',
            'properties.GUID',
            'properties.Id',
            'properties.pdtID',
            'propertiesdatadictionaries.versionNumber',
            'propertiesdatadictionaries.revisionNumber',
            'properties.gopID',
            'properties.referenceDocumentGUID',
            'propertiesdatadictionaries.units',
            'propertiesdatadictionaries.nameEn',
            'propertiesdatadictionaries.namePt',
            'properties.visualRepresentation'
        )
            ->get();

        return view('pdtsdownload', compact('gop', 'joined_properties', 'properties_dict', 'pdt', 'referenceDocument'));
    }
    public function getGroupOfProperties2($pdtID)
    {
        $pdt = productdatatemplates::where('Id', $pdtID)
            ->get();
        $gop = DB::table('groupofproperties as gop')->where('pdtId', $pdtID)
            ->join(
                DB::raw("(SELECT 
                GUID,
                MAX(versionNumber) as max_versionNumber,
                MAX(revisionNumber) as max_revisionNumber
                FROM groupofproperties 
                GROUP BY GUID) as mx"),
                function ($join) {
                    $join->on('mx.GUID', '=', 'gop.GUID');
                    $join->on('mx.max_versionNumber', '=', 'gop.versionNumber');
                    $join->on('mx.max_revisionNumber', '=', 'gop.revisionNumber');
                }
            )
            ->get();
        $referenceDocument = referencedocuments::all();
        $properties_dict = propertiesdatadictionaries::all();
        $properties = properties::where('pdtID', $pdtID)->get();

        // Join the properties and propertiesdatadictionaries tables
        $joined_properties = properties::leftJoin('propertiesdatadictionaries', function ($join) {
            $join->on('properties.GUID', '=', 'propertiesdatadictionaries.GUID');
            $join->on(
                DB::raw('(propertiesdatadictionaries.versionNumber, propertiesdatadictionaries.revisionNumber)'),
                DB::raw('(select max(versionNumber), max(revisionNumber) from propertiesdatadictionaries where GUID = properties.GUID)'),
                '='
            );
        })->select(
            'properties.descriptionEn',
            'properties.descriptionPt',
            'properties.GUID',
            'properties.Id',
            'properties.pdtID',
            'propertiesdatadictionaries.versionNumber',
            'propertiesdatadictionaries.revisionNumber',
            'properties.gopID',
            'properties.referenceDocumentGUID',
            'propertiesdatadictionaries.units',
            'propertiesdatadictionaries.nameEn',
            'propertiesdatadictionaries.namePt',
            'properties.visualRepresentation'
        )
            ->get();

        $comments = comments::with('user')->get();


        $answers = Answers::where('users_id', Auth::id())->get();


        return view('pdtssurvey', compact('gop', 'joined_properties', 'properties_dict', 'pdt', 'referenceDocument', 'comments', 'answers', 'properties'));
    }



    public function saveAnswers(Request $request)
    {

        $answers = $request->input('answers');
        foreach ($answers as $answer) {
            $answer = json_decode($answer);
            $Answer = new Answers;
            $Answer->answer = $answer->answer;
            $Answer->properties_Id = $answer->propertyId;
            $Answer->users_id = Auth::user()->id;
            $Answer->save();
        }
        return redirect()->back()->with('success', 'Answers saved successfully');
    }



    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'body' => 'required|string',
            'properties_Id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => 'The feedback field is required.'
            ]);
        } else {

            $comment = new comments;
            $comment->body = $request->input('body');
            $comment->properties_Id = $request->input('properties_Id');
            $comment->users_id = Auth::user()->id;
            $comment->save();

            return response()->json([
                'status' => 200,
                'message' => 'Feedback added successfully.'
            ]);
        }
    }
    public function fetchfeedback($propertyId)
    {

        $comments = comments::select()->where('properties_Id', $propertyId);
        return response()->json([
            'comments' => $comments,
        ]);
    }

    public function destroyfeedback($id)
    {
        $comment = comments::find($id);
        if ($comment) {
            $comment->delete();
            return response()->json([
                'status' => 200,
                'message' => 'Feedback Deleted Successfully.'
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => 'No Feedback Found.'
            ]);
        }
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


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
     * Display the specified resource.
     *
     * @param  \App\Models\groupofproperties  $groupofproperties
     * @return \Illuminate\Http\Response
     */
    public function show(groupofproperties $groupofproperties)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\groupofproperties  $groupofproperties
     * @return \Illuminate\Http\Response
     */
    public function edit(groupofproperties $groupofproperties)
    {
        //
    }
}
