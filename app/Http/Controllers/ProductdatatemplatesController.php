<?php

namespace App\Http\Controllers;

use App\Models\productdatatemplates;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\groupofproperties;
use App\Models\properties;
use App\Models\comments;
use App\Models\Answers;
use App\Models\constructionobjects;
use App\Models\propertiesdatadictionaries;
use App\Models\referencedocuments;
use Carbon\Carbon;

class ProductdatatemplatesController extends Controller
{
    /** Get the latest version of a PDT */
    public function getLatestPDTs()
    {
        $latestPDT = DB::table('productdatatemplates as pdt')
            ->join(
                DB::raw("(SELECT 
                GUID,
                MAX(versionNumber) as max_versionNumber,
                MAX(revisionNumber) as max_revisionNumber,
                MAX(editionNumber) as max_editionNumber
             
                FROM productdatatemplates 
                GROUP BY GUID) as mx"),
                function ($join) {
                    $join->on('mx.GUID', '=', 'pdt.GUID');
                    $join->on('mx.max_versionNumber', '=', 'pdt.versionNumber');
                    $join->on('mx.max_revisionNumber', '=', 'pdt.revisionNumber');
                    $join->on('mx.max_editionNumber', '=', 'pdt.editionNumber');
                }
            )
            ->get();

        return view('dashboard', compact('latestPDT'));
    }





    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function productDataTemplate($pdtID)
    {
        $pdt = ProductDataTemplates::where('Id', $pdtID)->first();

        $gops = GroupOfProperties::where('pdtId', $pdtID)->get();

        $allReferenceDocuments = [];

        foreach ($gops as $gop) {
            $properties = Properties::where('gopID', $gop->Id)->get();
            foreach ($properties as $property) {
                $propertyAttributes = PropertiesDataDictionaries::where('Id', $property->propertyId)->first();
                $property->propertiesAttributesInDataDictionary = $propertyAttributes;

                // Collect reference documents GUID
                $allReferenceDocuments[] = $property->referenceDocumentGUID;
            }

            $gop->properties = $properties;
        }

        // Fetch reference documents based on collected GUIDs
        $referenceDocuments = ReferenceDocuments::whereIn('GUID', $allReferenceDocuments)->get();

        $pdt->groupOfProperties = $gops;
        $pdt->referenceDocuments = $referenceDocuments;

        return response()->json(['productDataTemplate' => $pdt]);
    }


    public function productDataTemplateold($pdtID)
    {
        $pdt = ProductDataTemplates::where('Id', $pdtID)->get();
        $gop = GroupOfProperties::where('pdtId', $pdtID)->get();
        $referenceDocument = ReferenceDocuments::all();

        $properties = Properties::where('pdtID', $pdtID)->get();

        $propertiesInDataDictionary = Properties::leftJoin('propertiesdatadictionaries', function ($join) {
            $join->on('properties.propertyId', '=', 'propertiesdatadictionaries.Id');
        })->select('propertiesdatadictionaries.*')
            ->get();

        $data = [
            'productDataTemplate' => $pdt,
            'groupsOfProperties' => $gop,
            'properties' => $properties,
            'referenceDocuments' => $referenceDocument,
            'propertiesAttributesInDataDictionary' => $propertiesInDataDictionary,
        ];

        return response()->json($data);
    }

    public function constructionObjects()
    {
        return constructionobjects::all();
    }

    public function productDataTemplates()
    {
        return productdatatemplates::all();
    }

    public function dataDictionary()
    {
        return propertiesdatadictionaries::all();
    }

    public function propertyInDataDictionary($Id)
    {
        return propertiesdatadictionaries::Where("Id", $Id)->first();
    }

    public function referenceDocuments()
    {
        return referenceDocuments::all();
    }

    public function referenceDocument($GUID)
    {
        return referenceDocuments::Where("GUID", $GUID)->first();
    }

    public function groupsOfProperties()
    {
        return groupOfProperties::all();
    }

    public function groupOfProperties($Id)
    {
        return groupOfProperties::Where("Id", $Id)->first();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $referenceDocuments = ReferenceDocuments::all();
        return view('productdatatemplates.create', compact('referenceDocuments'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Validate the request
        $request->validate([
            'GUID' => 'required|string',
            'referenceDocumentGUID' => 'nullable|string',
            'pdtNameEn' => 'required|string',
            'pdtNamePt' => 'required|string',
            'descriptionEn' => 'nullable|string',
            'descriptionPt' => 'nullable|string',
            'dateOfEdition' => 'required|date',
            'dateOfRevision' => 'required|date',
            'dateOfVersion' => 'required|date',
            'status' => 'required|string',
            'editionNumber' => 'required|integer',
            'versionNumber' => 'required|integer',
            'revisionNumber' => 'required|integer',
            // Add other fields validation as needed
        ]);

        // Create a new PDT
        $pdt = new productdatatemplates();
        $pdt->GUID = $request->input('GUID');
        $pdt->referenceDocumentGUID = $request->input('referenceDocumentGUID');
        $pdt->pdtNameEn = $request->input('pdtNameEn');
        $pdt->pdtNamePt = $request->input('pdtNamePt');
        $pdt->descriptionEn = $request->input('descriptionEn');
        $pdt->descriptionPt = $request->input('descriptionPt');
        $pdt->dateOfEdition = now();
        $pdt->dateOfRevision = now();
        $pdt->dateOfVersion = now();
        $pdt->created_at = now();
        $pdt->updated_at = now();
        $pdt->status = $request->input('status');
        $pdt->editionNumber = $request->input('editionNumber');
        $pdt->versionNumber = $request->input('versionNumber');
        $pdt->revisionNumber = $request->input('revisionNumber');
        // Set other fields as needed
        $pdt->save();

        return redirect()->route('pdtinput')->with('successpdt', 'PDT added successfully!');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\productdatatemplates  $productdatatemplates
     * @return \Illuminate\Http\Response
     */
    public function show(productdatatemplates $productdatatemplates)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\productdatatemplates  $productdatatemplates
     * @return \Illuminate\Http\Response
     */
    public function edit(productdatatemplates $productdatatemplates)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\productdatatemplates  $productdatatemplates
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, productdatatemplates $productdatatemplates)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\productdatatemplates  $productdatatemplates
     * @return \Illuminate\Http\Response
     */
    public function destroy(productdatatemplates $productdatatemplates)
    {
        //
    }

    // TO DO : 
    // add remaining properties in each section

    public function exportDataToJson()
    {
        // Fetch data using raw SQL queries
        $productDataTemplates = DB::select('SELECT * FROM productdatatemplates');

        // Fetch properties from propertiesdatadictionary
        $propertiesData = DB::select('SELECT * FROM propertiesdatadictionaries');

        // Transform data into the desired JSON format
        $jsonData = $this->transformData($productDataTemplates, $propertiesData);

        // Save JSON data to a temporary file
        $tempFilePath = tempnam(sys_get_temp_dir(), 'PDTs.pt_Domain_bsdd_');
        file_put_contents($tempFilePath, json_encode($jsonData, JSON_PRETTY_PRINT));


        // Add the current date to the file name
        $currentDate = Carbon::now()->format('Y-m-d');
        $fileName = 'PDTs.pt_Domain_bsdd_' . $currentDate . '.json';
        // Stream download the file to the user
        $headers = [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ];

        return response()->streamDownload(
            function () use ($tempFilePath) {
                readfile($tempFilePath);
                unlink($tempFilePath); // Delete the temporary file after streaming
            },
            $fileName,
            $headers
        );
    }

    private function transformData($productDataTemplates, $propertiesData)
    {
        $jsonData = [
            'ModelVersion' => '2.0',
            'OrganizationCode' => 'pdtspt',
            'DictionaryCode' => 'pdtspt',
            'LanguageIsoCode' => 'pt-PT',
            'DictionaryName' => 'pdtspt',
            'DictionaryVersion' => "1.1",

            // Add other top-level keys as needed
            'Classes' => [],
            'Properties' => [],
        ];

        foreach ($productDataTemplates as $productDataTemplate) {
            $classData = $this->transformProductDataTemplate($productDataTemplate);
            $jsonData['Classes'][] = $classData;
            // Fetch related data using another raw SQL query
            $groupOfProperties = DB::select('SELECT * FROM groupofproperties WHERE pdtId = ?', [$productDataTemplate->Id]);

            foreach ($groupOfProperties as $group) {
                $groupData = $this->transformGroupOfProperties($group);
                $jsonData['Classes'][] = $groupData;
            }
        }


        // Transform properties from propertiesdatadictionary
        foreach ($propertiesData as $property) {
            $jsonData['Properties'][] = $this->transformPropertyDataDictionary($property);
        }

        return $jsonData;
    }

    private function transformProductDataTemplate($productDataTemplate)
    {
        // Implement the transformation logic for a product data template
        // Use $productDataTemplate and its properties to structure the data

        $classData = [
            'ClassType' => 'Class',
            'Code' =>  (string) $productDataTemplate->GUID . '-' . (string) $productDataTemplate->Id,
            'Name' => $productDataTemplate->pdtNameEn, // Adjust based on your schema
            'Status' => $productDataTemplate->status, // Adjust based on your schema
            'ActivationDateUtc' => $productDataTemplate->dateOfVersion, // Adjust based on your schema
        ];



        return $classData;
    }


    private function transformGroupOfProperties($groupOfProperties)
    {
        // Implement the transformation logic for a group of properties
        // Use $groupOfProperties and its properties to structure the data
        $productDataTemplate = productdatatemplates::WHERE("Id", [$groupOfProperties->pdtId])->first();

        $groupData = [
            'ClassType' => 'GroupOfProperties',
            'ParentClassCode' =>   (string) $productDataTemplate->GUID . '-' . (string) $groupOfProperties->pdtId, // Assuming PDT is the parent class
            'Code' =>  (string) $groupOfProperties->GUID . '-' . (string) $groupOfProperties->Id,
            'Name' => $groupOfProperties->gopNameEn, // Adjust based on your schema
            'Status' => $groupOfProperties->status, // Adjust based on your schema
            'ClassProperties' => [],
        ];

        // Fetch related data using another raw SQL query
        $classProperties = DB::select('SELECT * FROM properties WHERE gopId = ?', [$groupOfProperties->Id]);

        foreach ($classProperties as $property) {
            $groupData['ClassProperties'][] = $this->transformProperty($property);
        }

        return $groupData;
    }

    private function transformProperty($property)
    {
        // Implement the transformation logic for a property
        // Use $property and its properties to structure the data

        $groupOfProperties = groupofproperties::where("Id", $property->gopID)->first();
        $propertyData = [

            'PropertyCode' =>  (string) $property->GUID . '-' . (string) $property->Id,
            'Code' =>  (string) $property->Id,
            'Description' => $property->descriptionEn, // Adjust based on your schema
            'PropertySet' => str_replace(' ', '', ucwords(strtolower($groupOfProperties->gopNameEn))),
            // Add other properties as needed
        ];

        return $propertyData;
    }

    private function transformPropertyDataDictionary($property)
    {
        // Implement the transformation logic for a property from propertiesdatadictionary
        // Use $property and its properties to structure the data

        $propertyData = [

            'Code' =>  (string) $property->GUID . '-' . (string) $property->Id,
            'Name' => $property->nameEn,
            'Definition' => $property->definitionEn,
            'Status' => $property->status,
            'ActivationDateUtc' => ($property->dateOfVersion != '0000-00-00') ? $property->dateOfVersion : '9999-99-99',
            'VersionDateUtc' => ($property->dateOfVersion != '0000-00-00') ? $property->dateOfVersion : '9999-99-99',
            // Add other properties as needed
        ];

        return $propertyData;
    }
}
