<?php

namespace App\Http\Controllers;

use App\Models\properties;
use App\Models\productdatatemplates;
use App\Models\groupofproperties;
use App\Models\referenceDocuments;
use App\Models\propertiesdatadictionaries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

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
        $pdts = productdatatemplates::get();

        return view('properties.choose_pdt', compact('pdts'));
    }

    public function createprops(Request $request)
    {
        // Log::info('Entering createprops method');
        // Validate the request
        $request->validate([
            'pdtId' => 'required|exists:productdatatemplates,Id',
        ]);

        $pdtId = $request->input('pdtId');

        // Fetch the selected PDT and pass it to the view
        $selectedPdt = productdatatemplates::where("Id", $pdtId)->first();
        $groupofproperties = GroupOfProperties::where('pdtId', $pdtId)->get();
        $properties = Properties::where('pdtId', $pdtId)->get();
        $selectedProperties = Properties::where('pdtId', $pdtId)
            ->join('propertiesDataDictionaries', function ($join) {
                $join->on('Properties.propertyId', '=', 'propertiesDataDictionaries.Id')
                    ->limit(1);
            })
            ->select('Properties.*', 'propertiesDataDictionaries.nameEn', 'propertiesDataDictionaries.units')
            ->get();
        $lastIdDataDictionary =  PropertiesDataDictionaries::latest('Id')->value('Id');
        $nextIdDataDictionary = $lastIdDataDictionary + 1;
        // Log::info('Exiting createprops method');
        return view('properties.createprops', compact('selectedPdt', 'groupofproperties', 'properties', 'nextIdDataDictionary'));
    }


    public function showProperty($propertyId)
    {
        $property = Properties::findOrFail($propertyId);
        $referenceDocuments = referenceDocuments::all();
        return view('properties.edit', compact('property', 'referenceDocuments'));
    }



    public function updateProperty(Request $request, $propertyId)
    {
        $request->validate([
            'descriptionPt' => 'required|string',
            'descriptionEn' => 'required|string',
            'referenceDocumentGUID' => 'nullable|exists:referencedocuments,GUID',

            // Add validation rules for other fields as needed
        ]);

        try {
            $property = Properties::findOrFail($propertyId);

            // Update property fields based on the form input
            $property->descriptionEn = $request->input('descriptionEn');
            $property->descriptionPt = $request->input('descriptionPt');
            $property->referenceDocumentGUID = $request->input('referenceDocumentGUID'); // Update the reference document

            // Add other fields as needed
            $property->save();

            // Log the update
            Log::info('Property updated successfully.');



            // Temporary action for testing, you can remove this after testing
            $this->testActionBeforeRedirect($property);

            // Redirect to the edit page with success message
            return redirect()->route('properties.edit', ['propertyId' => $property->Id])
                ->with('success', 'Property updated successfully.');
        } catch (\Exception $e) {
            // Log any exception
            Log::error('Error updating property: ' . $e->getMessage());
            // Handle the error as needed (you might want to redirect with an error message)
        }
    }

    // Temporary action for testing, you can remove this after testing
    private function testActionBeforeRedirect($property)
    {
        // This is a temporary action for testing, you can perform additional actions here
        // For example, you can log information or perform other tasks
        // This will help you identify whether the issue is related to the redirect or something else

        // Example: Log the property data
        Log::info('Test action - Property data: ' . json_encode($property->toArray()));
    }


    public function PropertiesAddedDictionaryPage(Request $request)
    {
        //Log::info('Entering newpropadd method');
        $pdtId = $request->input('pdtId');
        $gopId = $request->input('gopId');
        $selectedPdt = ProductDataTemplates::findOrFail($pdtId);
        $selectedGroup = GroupOfProperties::findOrFail($gopId);
        $dataDictionary = propertiesdatadictionaries::All();
        // Fetch properties from the Properties table
        $addedProperties = Properties::where('pdtId', $pdtId)->where('gopId', $gopId)->get();

        // Fetch additional information from the propertiesDataDictionaries table
        $selectedProperties = Properties::where('pdtId', $pdtId)->where('gopId', $gopId)
            ->join('propertiesDataDictionaries', function ($join) {
                $join->on('Properties.propertyId', '=', 'propertiesDataDictionaries.Id');
            })
            ->select('Properties.*', 'propertiesDataDictionaries.nameEn', 'propertiesDataDictionaries.units')
            ->get();
        $referenceDocuments = ReferenceDocuments::all();
        // Log::info('Entering newpropadd method');
        return view('properties.addFromDictionary', compact('selectedPdt', 'selectedGroup', 'selectedProperties', 'referenceDocuments', 'dataDictionary'));
    }

    public function addFromDictionary(Request $request)
    {
        $pdtId = $request->input('pdtId');
        $gopId = $request->input('gopId');
        $selectedPdt = ProductDataTemplates::findOrFail($pdtId);
        $selectedGroup = GroupOfProperties::findOrFail($gopId);
        $dataDictionary = PropertiesDataDictionaries::select('GUID', 'nameEn', 'namePt', 'units', 'versionNumber', 'revisionNumber')
            ->whereIn('versionNumber', function ($query) {
                $query->selectRaw('MAX(versionNumber)')
                    ->from('propertiesDataDictionaries')
                    ->groupBy('GUID');
            })
            ->whereIn('revisionNumber', function ($query) {
                $query->selectRaw('MAX(revisionNumber)')
                    ->from('propertiesDataDictionaries')
                    ->groupBy('GUID');
            })
            ->get();

        // Get selected property IDs and reference documents from the form
        $selectedProperties = $request->input('selectedProperties');

        // Loop through selected properties and add them to the database
        foreach ($selectedProperties as $propertyId) {
            $selectedProperty = PropertiesDataDictionaries::findOrFail($propertyId);

            // Create a new property in the properties table
            $property = new Properties();
            $property->GUID = $selectedProperty->GUID;
            $property->propertyId = $selectedProperty->Id;
            $property->gopID = $gopId;
            $property->pdtID = $pdtId;
            $property->referenceDocumentGUID = 'n/a';
            $property->descriptionEn = $selectedProperty->definitionEn;
            $property->descriptionPt = $selectedProperty->definitionPt;
            $property->visualRepresentation = $selectedProperty->visualRepresentation;
            $property->save();
        }
        // Fetch properties from the Properties table
        $addedProperties = Properties::where('pdtId', $pdtId)->where('gopId', $gopId)->get();

        // Fetch additional information from the propertiesDataDictionaries table
        $selectedProperties = Properties::where('pdtId', $pdtId)->where('gopId', $gopId)
            ->join('propertiesDataDictionaries', function ($join) {
                $join->on('Properties.propertyId', '=', 'propertiesDataDictionaries.Id');
            })
            ->select('Properties.*', 'propertiesDataDictionaries.nameEn', 'propertiesDataDictionaries.units')
            ->get();

        $referenceDocuments = ReferenceDocuments::all();

        return view('properties.addFromDictionary', compact('selectedPdt', 'selectedGroup', 'selectedProperties', 'referenceDocuments', 'dataDictionary'))->with('success', 'Properties added successfully.');
    }


    public function PropertiesAdded(Request $request)
    {
        //Log::info('Entering newpropadd method');
        $pdtId = $request->input('pdtId');
        $gopId = $request->input('gopId');
        $selectedPdt = ProductDataTemplates::findOrFail($pdtId);
        $selectedGroup = GroupOfProperties::findOrFail($gopId);
        $dataDictionary = PropertiesDataDictionaries::select('GUID', 'nameEn', 'namePt', 'units', 'versionNumber', 'revisionNumber')
            ->whereIn('versionNumber', function ($query) {
                $query->selectRaw('MAX(versionNumber)')
                    ->from('propertiesDataDictionaries')
                    ->groupBy('GUID');
            })
            ->whereIn('revisionNumber', function ($query) {
                $query->selectRaw('MAX(revisionNumber)')
                    ->from('propertiesDataDictionaries')
                    ->groupBy('GUID');
            })
            ->get();
        // Fetch properties from the Properties table
        $addedProperties = Properties::where('pdtId', $pdtId)->where('gopId', $gopId)->get();

        // Fetch additional information from the propertiesDataDictionaries table
        $selectedProperties = Properties::where('pdtId', $pdtId)->where('gopId', $gopId)
            ->join('propertiesDataDictionaries', function ($join) {
                $join->on('Properties.propertyId', '=', 'propertiesDataDictionaries.Id');
            })
            ->select('Properties.*', 'propertiesDataDictionaries.nameEn', 'propertiesDataDictionaries.units')
            ->get();
        $referenceDocuments = ReferenceDocuments::all();
        // Log::info('Entering newpropadd method');

        $lastIdDataDictionary =  PropertiesDataDictionaries::latest('Id')->value('Id');
        $nextIdDataDictionary = $lastIdDataDictionary + 1;

        return view('properties.addNew', compact('selectedPdt', 'selectedGroup', 'selectedProperties', 'referenceDocuments', 'dataDictionary', 'nextIdDataDictionary'));
    }

    public function addPropertyManual(Request $request)
    {
        //Log::info('Entering addNewManually method');
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
        $property->propertyId = $request->input('nextIdDataDictionary');
        $property->referenceDocumentGUID = $request->input('referenceDocumentGUID');
        $property->descriptionEn = $dataDictionaryProperty->definitionEn;
        $property->descriptionPt = $dataDictionaryProperty->definitionPt;
        $property->visualRepresentation = $dataDictionaryProperty->visualRepresentation;
        $property->save();
        // Log::info('Entering addNewManually method');
        // Redirect back or to the desired page

        $pdtId = $request->input('pdtId');
        $gopId = $request->input('gopId');
        $propertyId = $request->input('nextIdDataDictionary');
        $selectedPdt = ProductDataTemplates::findOrFail($pdtId);
        $selectedGroup = GroupOfProperties::findOrFail($gopId);

        // Fetch properties from the Properties table
        $addedProperties = Properties::where('pdtId', $pdtId)->where('gopId', $gopId)->get();

        // Fetch additional information from the propertiesDataDictionaries table
        $selectedProperties = Properties::where('pdtId', $pdtId)->where('gopId', $gopId)
            ->join('propertiesDataDictionaries', function ($join) {
                $join->on('Properties.propertyId', '=', 'propertiesDataDictionaries.Id');
            })
            ->select('Properties.*', 'propertiesDataDictionaries.nameEn', 'propertiesDataDictionaries.units')
            ->get();
        $referenceDocuments = ReferenceDocuments::all();
        return view('properties.addNew', compact('selectedPdt', 'selectedGroup', 'selectedProperties', 'referenceDocuments'))->with('success', 'Property added successfully.');
    }


    public function uploadExcel(Request $request)
    {
        Log::info("you are here1");

        // Validate uploaded file
        $request->validate([
            'excelFile' => 'required|mimes:xlsx,xls,csv|max:2048',
        ]);
        Log::info("you are here2");

        // Get the uploaded file path
        $path = $request->file('excelFile');
        Log::info("you are here3");

        // Load the Excel file with sheet names
        $excelData = Excel::toArray([], $path);

        // Fetch sheet names using IOFactory
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($path->getRealPath());
        $spreadsheet = $reader->load($path->getRealPath());
        $sheetNames = $spreadsheet->getSheetNames();

        Log::info("Sheet names: " . implode(', ', $sheetNames));

        // Get the selected group name from the request
        $selectedGroupName = $request->input('selectedGroupName');
        Log::info("Selected group name: {$selectedGroupName}");

        // Initialize empty arrays for matched and unmatched properties
        $matchedPropertyIds = [];
        $unmatchedProperties = [];
        Log::info("you are here5");

        // Iterate through the sheets with names
        foreach ($sheetNames as $index => $sheetName) {
            Log::info("Sheet name: {$sheetName}");

            // Check if the sheet name matches the selected group name (case-insensitive)
            if (strtolower(trim($sheetName)) === strtolower(trim($selectedGroupName))) {
                Log::info("Sheet found and processing: {$sheetName}");

                // Extract property names from the sheet (assuming the first column contains property names)
                $sheetData = $excelData[$index] ?? [];
                $propertyNames = collect($sheetData)->flatten()->map(fn($name) => trim($name))->toArray();
                Log::info("Extracted property names: " . implode(', ', $propertyNames));

                // Fetch matching properties from the database
                $matchedProperties = \App\Models\PropertiesDataDictionaries::whereIn('nameEn', $propertyNames)
                    ->orWhereIn('namePt', $propertyNames)
                    ->get();

                // Add matched property IDs to the array
                $matchedPropertyIds = $matchedProperties->pluck('Id')->toArray();
                Log::info("Matched property IDs: " . implode(', ', $matchedPropertyIds));

                // Find unmatched properties by comparing with the ones in the database
                foreach ($propertyNames as $propertyName) {
                    $propertyExists = $matchedProperties->firstWhere('nameEn', $propertyName) || $matchedProperties->firstWhere('namePt', $propertyName);

                    if (!$propertyExists) {
                        $unmatchedProperties[] = $propertyName;
                        Log::info("Unmatched property: {$propertyName}");
                    }
                }

                // Break the loop once the matching sheet is found
                Log::info("Finished processing sheet: {$sheetName}");
                break; // Stops further iteration after the matching sheet is found
            }
        }

        // Return the matched and unmatched properties as a response
        return response()->json([
            'matchedPropertyIds' => $matchedPropertyIds,
            'unmatchedProperties' => $unmatchedProperties,
        ]);
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
