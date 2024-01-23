<?php

namespace App\Http\Controllers;

use App\Models\propertiesdatadictionaries;
use App\Models\properties;
use App\Models\productdatatemplates;

use Illuminate\Http\Request;

class PropertiesdatadictionariesController extends Controller
{
    public function getPropertyDataDictionary($propID, $propGUID)
    {

        $propdd = propertiesdatadictionaries::WHERE('Id', $propID)
            ->first();
        $propinpdts = properties::where('propertyId', $propID)
            ->get();
        $pdts = productdatatemplates::get();

        $propversions = propertiesdatadictionaries::where('GUID', $propGUID)->get();

        return view('datadictionaryview', compact('propdd', 'propinpdts', 'pdts', 'propversions'));
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
     * @param  \App\Models\propertiesdatadictionaries  $propertiesdatadictionaries
     * @return \Illuminate\Http\Response
     */
    public function show(propertiesdatadictionaries $propertiesdatadictionaries)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\propertiesdatadictionaries  $propertiesdatadictionaries
     * @return \Illuminate\Http\Response
     */
    public function edit(propertiesdatadictionaries $propertiesdatadictionaries)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\propertiesdatadictionaries  $propertiesdatadictionaries
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, propertiesdatadictionaries $propertiesdatadictionaries)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\propertiesdatadictionaries  $propertiesdatadictionaries
     * @return \Illuminate\Http\Response
     */
    public function destroy(propertiesdatadictionaries $propertiesdatadictionaries)
    {
        //
    }
}
