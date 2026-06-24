<?php

namespace App\Http\Controllers;

use App\Models\properties;
use App\Models\productdatatemplates;
use App\Models\groupofproperties;
use App\Models\referencedocuments;
use App\Models\propertiesdatadictionaries;
use Illuminate\Support\Facades\Log;

use Illuminate\Http\Request;

class PropertiesdatadictionariesController extends Controller
{
    public function getPropertyDataDictionary($propID)
    {
        $propdd = propertiesdatadictionaries::where('Id', $propID)->first();
        $propinpdts = properties::where('propertyId', $propID)->get();
        $pdts = productdatatemplates::get();

        $propversions = propertiesdatadictionaries::where('GUID', $propinpdts->first()->GUID)->get();

        // Retrieve the latest referenceDocumentGUID
        $referenceDocumentData = properties::where('propertyId', $propID)->latest()->first('referenceDocumentGUID');

        // If referenceDocumentData is not null, extract GUID; otherwise, set as null
        $referencedocumentGUID = $referenceDocumentData ? $referenceDocumentData->referenceDocumentGUID : null;

        // Fetch the reference document only if a GUID was found
        $referencedocument = $referencedocumentGUID ? referencedocuments::where('GUID', $referencedocumentGUID)->first() : 'n/a';

        return view('datadictionaryview', compact('propdd', 'propinpdts', 'pdts', 'propversions', 'referencedocument'));
    }


    // [Legacy showddProperty()/updateddProperty() (old dictionary-mapping edit form) removed
    //  — superseded by ActivePdtEditController::updateDictMapping (in-place mapping edit) and
    //  the unified editor. getPropertyDataDictionary above (public datadictionaryview) stays.]

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
