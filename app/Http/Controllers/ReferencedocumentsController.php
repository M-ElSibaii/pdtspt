<?php

namespace App\Http\Controllers;

use App\Models\referencedocuments;
use App\Models\properties;
use App\Models\propertiesdatadictionaries;
use App\Models\productdatatemplates;
use Illuminate\Http\Request;

class ReferencedocumentsController extends Controller
{
    public function getReferenceDocument($rdGUID)
    {
        $rd = referencedocuments::where('GUID', $rdGUID)
            ->first();

        $pdts = productdatatemplates::get();

        $rdinprop = properties::leftJoin('propertiesdatadictionaries', function ($join) {
            $join->on('properties.propertyId', '=', 'propertiesdatadictionaries.Id');
        })->leftJoin('productdatatemplates', function ($join) {
            $join->on('productdatatemplates.Id', '=', 'properties.pdtID');
        })
            ->where('properties.referenceDocumentGUID', $rdGUID)
            ->select(
                'propertiesdatadictionaries.Id',
                'propertiesdatadictionaries.GUID',
                'propertiesdatadictionaries.namePt',
                'productdatatemplates.pdtNamePt',
                'productdatatemplates.Id',
                'productdatatemplates.versionNumber',
                'productdatatemplates.editionNumber',
                'productdatatemplates.revisionNumber'
            )
            ->get();


        return view('referencedocumentview', compact('rd', 'rdinprop', 'pdts'));
    }

    public function getReferenceDocuments()
    {
        $rds = referencedocuments::All();

        return view('referencedocuments.list', compact('rds'));
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
    public function referenceDocumentCreate(Request $request)
    {
        // Validate incoming request data
        $validated = $request->validate([
            'GUID' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'status' => 'required|string|max:50',
        ]);

        // Create a new reference document in the database
        ReferenceDocuments::create([
            'GUID' => $validated['GUID'],
            'rdName' => $validated['name'],
            'title' => $validated['title'],
            'description' => $validated['description'],
            'status' => $validated['status'],
        ]);
        $rds = referencedocuments::All();

        return view('referencedocuments.list', compact('rds'))->with('success', 'Property added successfully.');
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
     * @param  \App\Models\referencedocuments  $referencedocuments
     * @return \Illuminate\Http\Response
     */
    public function show(referencedocuments $referencedocuments)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\referencedocuments  $referencedocuments
     * @return \Illuminate\Http\Response
     */
    public function edit(referencedocuments $referencedocuments)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\referencedocuments  $referencedocuments
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, referencedocuments $referencedocuments)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\referencedocuments  $referencedocuments
     * @return \Illuminate\Http\Response
     */
    public function destroy(referencedocuments $referencedocuments)
    {
        //
    }
}
