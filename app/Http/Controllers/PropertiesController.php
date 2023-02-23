<?php

namespace App\Http\Controllers;

use App\Models\properties;
use Illuminate\Http\Request;

class PropertiesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Retrieve all properties from the database
        $properties = Properties::all();
        //foreach ($properties as $property) {
        // Retrieve comments and replies for a specific property
        //    $comments = Comments::where('properties_id', $property->Id)->get();
        //    $replies = Replies::where('properties_id', $property->Id)->get();

        return view('/pdtssurvey.index', compact(
            'properties'
            //, 'comments', 'replies'
        ));
        //}

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
     * @param  \App\Models\properties  $properties
     * @return \Illuminate\Http\Response
     */
    public function show(properties $properties)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\properties  $properties
     * @return \Illuminate\Http\Response
     */
    public function edit(properties $properties)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\properties  $properties
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, properties $properties)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\properties  $properties
     * @return \Illuminate\Http\Response
     */
    public function destroy(properties $properties)
    {
        //
    }
}
