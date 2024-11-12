<?php

namespace App\Http\Controllers;

use App\Models\properties;
use App\Models\productdatatemplates;
use App\Models\groupofproperties;
use App\Models\referenceDocuments;
use App\Models\propertiesdatadictionaries;
use Illuminate\Support\Facades\Log;

use Illuminate\Http\Request;

class PropertiesdatadictionariesController extends Controller
{
    public function getPropertyDataDictionary($propID, $propGUID)
    {
        $propdd = propertiesdatadictionaries::where('Id', $propID)->first();
        $propinpdts = properties::where('propertyId', $propID)->get();
        $pdts = productdatatemplates::get();

        $propversions = propertiesdatadictionaries::where('GUID', $propGUID)->get();

        // Retrieve the latest referenceDocumentGUID
        $referenceDocumentData = properties::where('propertyId', $propID)->latest()->first('referenceDocumentGUID');

        // If referenceDocumentData is not null, extract GUID; otherwise, set as null
        $referencedocumentGUID = $referenceDocumentData ? $referenceDocumentData->referenceDocumentGUID : null;

        // Fetch the reference document only if a GUID was found
        $referencedocument = $referencedocumentGUID ? referencedocuments::where('GUID', $referencedocumentGUID)->first() : null;

        return view('datadictionaryview', compact('propdd', 'propinpdts', 'pdts', 'propversions', 'referencedocument'));
    }


    public function showddProperty($propertyddId)
    {
        $propertydd = Propertiesdatadictionaries::findOrFail($propertyddId);

        return view('properties.editdd', compact('propertydd'));
    }



    public function updateddProperty(Request $request, $propertyddId)
    {
        $request->validate([
            'relationToOtherDataDictionaries' => 'required|string',


            // Add validation rules for other fields as needed
        ]);

        try {
            $propertydd = Propertiesdatadictionaries::findOrFail($propertyddId);

            // Update property fields based on the form input
            $propertydd->relationToOtherDataDictionaries = $request->input('relationToOtherDataDictionaries');

            // Add other fields as needed
            $propertydd->save();

            // Log the update
            Log::info('Property in dd updated successfully.');

            // Redirect to the edit page with success message
            return redirect()->route('properties.editdd', ['propertyddId' => $propertydd->Id])
                ->with('success', 'Property in dd updated successfully.');
        } catch (\Exception $e) {
            // Log any exception
            Log::error('Error updating property: ' . $e->getMessage());
            // Handle the error as needed (you might want to redirect with an error message)
        }
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
