<?php

namespace App\Http\Controllers;

use App\Models\properties;
use App\Models\productdatatemplates;
use App\Models\groupofproperties;
use App\Models\referenceDocuments;
use App\Models\propertiesdatadictionaries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
    public function choosePDT(Request $request)
    {
        // Fetch all PDTs
        $pdts = productdatatemplates::Where('status', "Under Review")->get();

        return view('properties.choose_pdt', compact('pdts'));
    }

    public function createprops(Request $request)
    {
        Log::info('Entering createprops method');
        // Validate the request
        $request->validate([
            'pdtId' => 'required|exists:productdatatemplates,Id',
        ]);

        $pdtId = $request->input('pdtId');

        // Fetch the selected PDT and pass it to the view
        $selectedPdt = productdatatemplates::find($pdtId);
        $groupofproperties = GroupOfProperties::where('pdtId', $pdtId)->get();
        $properties = Properties::where('pdtId', $pdtId)->get();
        $selectedProperties = Properties::where('pdtId', $pdtId)
            ->join('propertiesDataDictionaries', function ($join) {
                $join->on('Properties.GUID', '=', 'propertiesDataDictionaries.GUID')
                    ->orderByDesc('propertiesDataDictionaries.versionNumber')
                    ->orderByDesc('propertiesDataDictionaries.revisionNumber')
                    ->limit(1);
            })
            ->select('Properties.*', 'propertiesDataDictionaries.nameEn', 'propertiesDataDictionaries.units')
            ->get();
        Log::info('Exiting createprops method');
        return view('properties.createprops', compact('selectedPdt', 'groupofproperties', 'properties'));
    }
    /*  public function showAddFromDictionary($pdtId, $gopId)
    {
        $selectedPdt = ProductDataTemplates::findOrFail($pdtId);
        $selectedGroup = GroupOfProperties::findOrFail($gopId);
        $dataDictionary = propertiesDataDictionaries::all();
        $addedProperties = Properties::where('pdtId', $pdtId)->where('gopId', $gopId)->get();

        return view('properties.add_from_dictionary', compact('selectedPdt', 'selectedGroup', 'dataDictionary', 'addedProperties'));
    }
*/
    /*public function addFromDictionary(Request $request)
    {
        // Validate the request
        $request->validate([
            'pdtId' => 'required|exists:productdatatemplates,Id',
            'gopId' => 'required|exists:groupofproperties,Id',
            'selectedProperties' => 'required|array',
            'selectedProperties.*' => 'exists:data_dictionary,id',
        ]);

        // Add selected properties to the Properties table
        $pdtId = $request->input('pdtId');
        $gopId = $request->input('gopId');
        $selectedProperties = $request->input('selectedProperties');

        foreach ($selectedProperties as $propertyId) {
            $property = propertiesDataDictionaries::findOrFail($propertyId);

            // Create a new property in the Properties table
            $newProperty = new Properties();
            $newProperty->pdtId = $pdtId;
            $newProperty->gopId = $gopId;
            $newProperty->GUID = $property->GUID;
            $newProperty->referenceDocumentGUID = $property->referenceDocumentGUID;
            $newProperty->descriptionEn = $property->descriptionEn;
            $newProperty->descriptionPt = $property->descriptionPt;
            $newProperty->visualRepresentation = $property->visualRepresentation;
            // Add other fields as needed
            $newProperty->save();
        }

        return redirect()->back()->with('success', 'Properties added successfully!');
    }
*/
    public function NewPropertyAdd(Request $request)
    {
        Log::info('Entering newpropadd method');
        $pdtId = $request->input('pdtId');
        $gopId = $request->input('gopId');
        $selectedPdt = ProductDataTemplates::findOrFail($pdtId);
        $selectedGroup = GroupOfProperties::findOrFail($gopId);

        // Fetch properties from the Properties table
        $addedProperties = Properties::where('pdtId', $pdtId)->where('gopId', $gopId)->get();

        // Fetch additional information from the propertiesDataDictionaries table
        $selectedProperties = Properties::where('pdtId', $pdtId)->where('gopId', $gopId)
            ->join('propertiesDataDictionaries', function ($join) {
                $join->on('Properties.GUID', '=', 'propertiesDataDictionaries.GUID')
                    ->whereRaw('propertiesDataDictionaries.versionNumber = (SELECT MAX(versionNumber) FROM propertiesDataDictionaries WHERE GUID = Properties.GUID)')
                    ->whereRaw('propertiesDataDictionaries.revisionNumber = (SELECT MAX(revisionNumber) FROM propertiesDataDictionaries WHERE GUID = Properties.GUID)');
            })
            ->select('Properties.*', 'propertiesDataDictionaries.nameEn', 'propertiesDataDictionaries.units')
            ->get();
        $referenceDocuments = ReferenceDocuments::all();
        Log::info('Entering newpropadd method');
        return view('properties.addNew', compact('selectedPdt', 'selectedGroup', 'selectedProperties', 'referenceDocuments'));
    }


    public function addPropertyManual(Request $request)
    {
        Log::info('Entering addNewManually method');
        // Validate the request
        $request->validate([
            'pdtId' => 'required|exists:productdatatemplates,Id',
            'gopId' => 'required|exists:groupofproperties,Id',
            'nameEn' => 'required|string',
            'namePt' => 'required|string',
            'GUID' => 'required|string',
            'definitionEn' => 'required|string',
            'definitionPt' => 'required|string',
            'status' => 'required|string',
            'dateOfCreation' => 'required|date',
            'dateofActivation' => 'required|date',
            'dateOfLastChange' => 'required|date',
            'dateOfRevision' => 'required|date',
            'dateOfVersion' => 'required|date',
            'versionNumber' => 'required|integer',
            'revisionNumber' => 'required|integer',
            'listOfReplacedProperties' => 'nullable|string',
            'referenceDocumentGUID' => 'nullable|string',
            'listOfReplacingProperties' => 'nullable|string',
            'relationToOtherDataDictionaries' => 'nullable|string',
            'creatorsLanguage' => 'nullable|string',
            'visualRepresentation' => 'nullable|string',
            'countryOfUse' => 'nullable|string',
            'countryOfOrigin' => 'nullable|string',
            'physicalQuantity' => 'nullable|string',
            'dimension' => 'nullable|string',
            'dataType' => 'nullable|string',
            'dynamicProperty' => 'nullable|string',
            'parametersOfTheDynamicProperty' => 'nullable|string',
            'units' => 'nullable|string',
            'namesOfDefiningValues' => 'nullable|string',
            'definingValues' => 'nullable|string',
            'tolerance' => 'nullable|string',
            'digitalFormat' => 'nullable|string',
            'textFormat' => 'nullable|string',
            'listOfPossibleValuesInLanguageN' => 'nullable|string',
            'boundaryValues' => 'nullable|string',
        ]);

        // Create a new property in the data dictionary
        $dataDictionaryProperty = new propertiesDataDictionaries();
        $dataDictionaryProperty->GUID = $request->input('GUID');
        $dataDictionaryProperty->nameEn = $request->input('nameEn');
        $dataDictionaryProperty->namePt = $request->input('namePt');
        $dataDictionaryProperty->definitionEn = $request->input('definitionEn');
        $dataDictionaryProperty->definitionPt = $request->input('definitionPt');
        $dataDictionaryProperty->status = $request->input('status');
        $dataDictionaryProperty->dateOfCreation = $request->input('dateOfCreation');
        $dataDictionaryProperty->dateofActivation = $request->input('dateofActivation');
        $dataDictionaryProperty->dateOfLastChange = $request->input('dateOfLastChange');
        $dataDictionaryProperty->dateOfRevision = $request->input('dateOfRevision');
        $dataDictionaryProperty->dateOfVersion = $request->input('dateOfVersion');
        $dataDictionaryProperty->versionNumber = $request->input('versionNumber');
        $dataDictionaryProperty->revisionNumber = $request->input('revisionNumber');
        $dataDictionaryProperty->listOfReplacedProperties = $request->input('listOfReplacedProperties');
        $dataDictionaryProperty->listOfReplacingProperties = $request->input('listOfReplacingProperties');
        $dataDictionaryProperty->relationToOtherDataDictionaries = $request->input('relationToOtherDataDictionaries');
        $dataDictionaryProperty->creatorsLanguage = $request->input('creatorsLanguage');
        $dataDictionaryProperty->visualRepresentation = $request->input('visualRepresentation');
        $dataDictionaryProperty->countryOfUse = $request->input('countryOfUse');
        $dataDictionaryProperty->countryOfOrigin = $request->input('countryOfOrigin');
        $dataDictionaryProperty->physicalQuantity = $request->input('physicalQuantity');
        $dataDictionaryProperty->dimension = $request->input('dimension');
        $dataDictionaryProperty->dataType = $request->input('dataType');
        $dataDictionaryProperty->dynamicProperty = $request->input('dynamicProperty');
        $dataDictionaryProperty->parametersOfTheDynamicProperty = $request->input('parametersOfTheDynamicProperty');
        $dataDictionaryProperty->units = $request->input('units');
        $dataDictionaryProperty->namesOfDefiningValues = $request->input('namesOfDefiningValues');
        $dataDictionaryProperty->definingValues = $request->input('definingValues');
        $dataDictionaryProperty->tolerance = $request->input('tolerance');
        $dataDictionaryProperty->digitalFormat = $request->input('digitalFormat');
        $dataDictionaryProperty->textFormat = $request->input('textFormat');
        $dataDictionaryProperty->listOfPossibleValuesInLanguageN = $request->input('listOfPossibleValuesInLanguageN');
        $dataDictionaryProperty->boundaryValues = $request->input('boundaryValues');
        $dataDictionaryProperty->save();

        // Create a new property in the properties table
        $property = new Properties();
        $property->GUID = $dataDictionaryProperty->GUID;
        $property->gopID = $request->input('gopId');
        $property->pdtID = $request->input('pdtId');
        $property->referenceDocumentGUID = $request->input('referenceDocumentGUID');
        $property->descriptionEn = $dataDictionaryProperty->definitionEn;
        $property->descriptionPt = $dataDictionaryProperty->definitionPt;
        $property->visualRepresentation = $dataDictionaryProperty->visualRepresentation;
        $property->propertyVersion = $dataDictionaryProperty->versionNumber;
        $property->propertyRevision = $dataDictionaryProperty->revisionNumber;
        $property->save();
        Log::info('Entering addNewManually method');
        // Redirect back or to the desired page

        $pdtId = $request->input('pdtId');
        $gopId = $request->input('gopId');
        $selectedPdt = ProductDataTemplates::findOrFail($pdtId);
        $selectedGroup = GroupOfProperties::findOrFail($gopId);

        // Fetch properties from the Properties table
        $addedProperties = Properties::where('pdtId', $pdtId)->where('gopId', $gopId)->get();

        // Fetch additional information from the propertiesDataDictionaries table
        $selectedProperties = Properties::where('pdtId', $pdtId)->where('gopId', $gopId)
            ->join('propertiesDataDictionaries', function ($join) {
                $join->on('Properties.GUID', '=', 'propertiesDataDictionaries.GUID')
                    ->whereRaw('propertiesDataDictionaries.versionNumber = (SELECT MAX(versionNumber) FROM propertiesDataDictionaries WHERE GUID = Properties.GUID)')
                    ->whereRaw('propertiesDataDictionaries.revisionNumber = (SELECT MAX(revisionNumber) FROM propertiesDataDictionaries WHERE GUID = Properties.GUID)');
            })
            ->select('Properties.*', 'propertiesDataDictionaries.nameEn', 'propertiesDataDictionaries.units')
            ->get();
        $referenceDocuments = ReferenceDocuments::all();
        return view('properties.addNew', compact('selectedPdt', 'selectedGroup', 'selectedProperties', 'referenceDocuments'))->with('success', 'Property added successfully.');
    }

    public function storeFromDictionary(Request $request)
    {
        // Logic to store properties from the data dictionary
        // ...

        return redirect()->route('properties.addFromDictionary', $request->input('groupId'))
            ->with('success', 'Properties added successfully!');
    }

    public function storeNew(Request $request)
    {
        // Logic to store new properties
        // ...

        return redirect()->route('properties.addNew', $request->input('groupId'))
            ->with('success', 'Property added successfully!');
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
