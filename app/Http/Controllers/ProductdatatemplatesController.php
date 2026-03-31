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

    /**
     * View a single PDT with all attributes
     */
    public function viewPdt($id, $guid)
    {
        $pdt = productdatatemplates::where('Id', $id)->where('GUID', $guid)->firstOrFail();

        // Load related data
        $groupsOfProperties = groupofproperties::where('pdtId', $id)->get();
        $pdtVersions = productdatatemplates::where('GUID', $guid)->get();

        // Load ObjectType if exists
        $objectType = null;
        if ($pdt->constructionObjectGUID) {
            $objectType = constructionobjects::where('GUID', $pdt->constructionObjectGUID)->first();
        }

        // Load Master properties info (count)
        $masterPropertiesCount = 0;
        if ($pdt->GUID !== '230d9954097541b793f2a1fddb8bd0ad') {
            $masterPdt = productdatatemplates::where('GUID', '230d9954097541b793f2a1fddb8bd0ad')
                ->orderByRaw('versionNumber DESC, revisionNumber DESC, editionNumber DESC')
                ->first();
            if ($masterPdt) {
                $masterPropertiesCount = DB::table('properties')
                    ->where('pdtID', $masterPdt->Id)
                    ->select('GUID')
                    ->distinct()
                    ->count();
            }
        }

        return view('pdtview', compact('pdt', 'groupsOfProperties', 'pdtVersions', 'objectType', 'masterPropertiesCount'));
    }


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

        $categories = DB::table('productdatatemplates as pdt')
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
            ->select('pdt.category')
            ->groupBy('pdt.category')
            ->selectRaw('count(*) as count')
            ->get();

        $allpdts = productdatatemplates::all();

        return view('dashboard', compact('latestPDT', 'categories', 'allpdts'));
    }


    /**
     * GET /api/{pdtID}
     * EN ISO 23387 compliant JSON export with COMPLETE data
     * 
     * Includes ALL attributes from:
     * - productdatatemplates
     * - constructionobjects
     * - groupofproperties
     * - propertiesdatadictionaries (COMPLETE)
     * - referencedocuments
     * 
     * Structure: 23387 hierarchy
     * Data: COMPLETE from all EN ISO 23386 data dictionary tables
     */
    public function productDataTemplate($pdtID)
    {
        try {
            // Get latest version of PDT
            $pdt = DB::table('productdatatemplates')
                ->where(function ($query) use ($pdtID) {
                    $query->where('Id', $pdtID)
                        ->orWhere('GUID', $pdtID);
                })
                ->orderByRaw('versionNumber DESC, revisionNumber DESC, editionNumber DESC')
                ->first();

            if (!$pdt) {
                return response()->json(['error' => 'PDT not found'], 404);
            }

            // ========== PDT DATA ==========
            $pdtData = (array)$pdt;

            // ========== CONSTRUCTION OBJECT DATA ==========
            $constructionObjectData = null;
            $hasObjectTypeRef = null;
            if ($pdt->constructionObjectGUID) {
                $constructionObject = constructionobjects::where('GUID', $pdt->constructionObjectGUID)->first();
                if ($constructionObject) {
                    $constructionObjectData = $constructionObject->toArray();
                    $hasObjectTypeRef = [
                        'dt:GUID' => $pdt->constructionObjectGUID,
                        'name' => $constructionObject->constructionObjectNamePt ?? $constructionObject->constructionObjectNameEn,
                        'nameEn' => $constructionObject->constructionObjectNameEn,
                        'namePt' => $constructionObject->constructionObjectNamePt,
                        'description' => $constructionObject->descriptionEn,
                        'descriptionPt' => $constructionObject->descriptionPt,
                        'descriptionEn' => $constructionObject->descriptionEn,
                    ];
                }
            }

            // ========== GROUPS OF PROPERTIES WITH COMPLETE DATA ==========
            $groupsOfPropertiesData = [];
            $groupOfPropertiesIds = DB::table('groupofproperties')
                ->where('pdtId', $pdt->Id)
                ->orderBy('Id')
                ->pluck('Id');

            foreach ($groupOfPropertiesIds as $gopId) {
                $gop = groupofproperties::find($gopId);
                if ($gop) {
                    $gopArray = $gop->toArray();

                    // Load ALL properties for this GOP with COMPLETE propertiesdatadictionaries data
                    $gopProperties = [];
                    $propertyIds = DB::table('properties')
                        ->where('gopId', $gopId)
                        ->select('GUID', 'propertyId', 'Id')
                        ->get();

                    foreach ($propertyIds as $prop) {
                        // Get property from propertiesdatadictionaries (COMPLETE data)
                        $propComplete = propertiesdatadictionaries::where('GUID', $prop->GUID)->first();
                        if ($propComplete) {
                            $gopProperties[] = [
                                'properties_link_id' => $prop->Id,
                                'propertyId' => $prop->propertyId,
                                'GUID' => $prop->GUID,
                                // COMPLETE propertiesdatadictionaries attributes
                                'completeDictionary' => $propComplete->toArray(),
                                // Structured for 23387
                                'ISO23387' => $this->buildProperty23387($propComplete),
                            ];
                        }
                    }

                    $groupsOfPropertiesData[] = [
                        'gopRawData' => $gopArray,
                        'gopId' => $gopId,
                        'GUID' => $gop->GUID,
                        'properties' => $gopProperties,
                        'ISO23387' => $this->buildGroupOfProperties23387($gop),
                    ];
                }
            }

            // ========== PROPERTIES LINKED DIRECTLY TO PDT ==========
            $pdtPropertiesData = [];
            $directPropertyGuids = DB::table('properties')
                ->where('pdtID', $pdt->Id)
                ->select('GUID', 'propertyId', 'Id')
                ->distinct()
                ->get();

            foreach ($directPropertyGuids as $prop) {
                $propComplete = propertiesdatadictionaries::where('GUID', $prop->GUID)->first();
                if ($propComplete) {
                    $pdtPropertiesData[] = [
                        'properties_link_id' => $prop->Id,
                        'propertyId' => $prop->propertyId,
                        'GUID' => $prop->GUID,
                        'completeDictionary' => $propComplete->toArray(),
                        'ISO23387' => $this->buildProperty23387($propComplete),
                    ];
                }
            }

            // ========== MASTER DATA TEMPLATE PROPERTIES (if not master itself) ==========
            $masterProperties = [];
            $masterGOP = [];
            if ($pdt->GUID !== '230d9954097541b793f2a1fddb8bd0ad') {
                $masterPdt = DB::table('productdatatemplates')
                    ->where('GUID', '230d9954097541b793f2a1fddb8bd0ad')
                    ->orderByRaw('versionNumber DESC, revisionNumber DESC, editionNumber DESC')
                    ->first();

                if ($masterPdt) {
                    // Master GOPs
                    $masterGopIds = DB::table('groupofproperties')
                        ->where('pdtId', $masterPdt->Id)
                        ->orderBy('Id')
                        ->pluck('Id');

                    foreach ($masterGopIds as $gopId) {
                        $gop = groupofproperties::find($gopId);
                        if ($gop) {
                            $masterGOP[] = [
                                'source' => 'Master',
                                'gopRawData' => $gop->toArray(),
                                'GUID' => $gop->GUID,
                                'ISO23387' => $this->buildGroupOfProperties23387($gop),
                            ];
                        }
                    }

                    // Master Properties
                    $masterPropGuids = DB::table('properties')
                        ->where('pdtID', $masterPdt->Id)
                        ->select('GUID', 'propertyId', 'Id')
                        ->distinct()
                        ->get();

                    foreach ($masterPropGuids as $prop) {
                        $propComplete = propertiesdatadictionaries::where('GUID', $prop->GUID)->first();
                        if ($propComplete) {
                            $masterProperties[] = [
                                'source' => 'Master',
                                'GUID' => $prop->GUID,
                                'completeDictionary' => $propComplete->toArray(),
                                'ISO23387' => $this->buildProperty23387($propComplete),
                            ];
                        }
                    }
                }
            }

            // ========== REFERENCE DOCUMENTS WITH COMPLETE DATA ==========
            $referenceDocumentsData = [];

            // Collect all ref doc GUIDs from PDT, GOPs, and properties
            $refDocGuids = collect();
            if ($pdt->referenceDocumentGUID && $pdt->referenceDocumentGUID !== 'n/a') {
                $refDocGuids->push($pdt->referenceDocumentGUID);
            }

            foreach ($groupsOfPropertiesData as $gopData) {
                $gop = groupofproperties::find($gopData['gopId']);
                if ($gop && $gop->referenceDocumentGUID && $gop->referenceDocumentGUID !== 'n/a') {
                    $refDocGuids->push($gop->referenceDocumentGUID);
                }
            }

            $propRefDocs = DB::table('properties')
                ->where('pdtID', $pdt->Id)
                ->whereNotNull('referenceDocumentGUID')
                ->where('referenceDocumentGUID', '!=', 'n/a')
                ->select('referenceDocumentGUID')
                ->distinct()
                ->pluck('referenceDocumentGUID');

            $refDocGuids = $refDocGuids->merge($propRefDocs)->filter()->unique();

            foreach ($refDocGuids as $guid) {
                $refDoc = referencedocuments::where('GUID', $guid)->first();
                if ($refDoc) {
                    $referenceDocumentsData[] = [
                        'GUID' => $guid,
                        'rawData' => $refDoc->toArray(),
                        'ISO23387' => [
                            'dt:GUID' => $guid,
                            'Name' => [
                                ['language' => 'en', 'value' => $refDoc->rdName ?? $refDoc->title ?? '']
                            ],
                            'Definition' => [
                                ['language' => 'en', 'value' => $refDoc->description ?? $refDoc->title ?? 'Referenced document']
                            ],
                            'Status' => $refDoc->status ?? 'Active',
                            'Language' => 'en',
                        ]
                    ];
                }
            }

            // ========== BUILD COMPLETE JSON STRUCTURE ==========
            $response = [
                'meta' => [
                    'exportFormat' => 'EN ISO 23387 + COMPLETE EN ISO 23386 DATA',
                    'version' => '1.0',
                    'timestamp' => Carbon::now()->toIso8601String(),
                    'pdtVersion' => "{$pdt->versionNumber}.{$pdt->revisionNumber}",
                ],
                'Library' => [
                    'ISO23387' => [
                        'dt:GUID' => $pdt->GUID,
                        'dateOfCreation' => $this->formatDate($pdt->dateOfVersion ?? $pdt->dateOfRevision),
                        'Name' => $this->buildMultilingualNames($pdt->pdtNameEn, $pdt->pdtNamePt),
                        'Definition' => $this->buildMultilingualDefinitions($pdt->descriptionEn, $pdt->descriptionPt),
                        'URI' => "https://pdts.pt/pdtview/{$pdt->Id}-{$pdt->GUID}",
                        'HasObjectTypeRef' => $hasObjectTypeRef,
                    ],
                    'rawData' => $pdtData,
                    'ObjectType' => $constructionObjectData,
                    'DataTemplate' => [
                        'ISO23387' => [
                            'dt:GUID' => $pdt->GUID,
                            'dateOfCreation' => $this->formatDate($pdt->dateOfVersion ?? $pdt->dateOfRevision),
                            'Name' => $this->buildMultilingualNames($pdt->pdtNameEn, $pdt->pdtNamePt),
                            'Definition' => $this->buildMultilingualDefinitions($pdt->descriptionEn, $pdt->descriptionPt),
                            'MajorVersion' => (int)$pdt->versionNumber,
                            'MinorVersion' => (int)$pdt->revisionNumber,
                            'Status' => $pdt->status ?? 'Active',
                        ],
                        'rawData' => $pdtData,
                    ],
                    'GroupsOfProperties' => [
                        'current' => $groupsOfPropertiesData,
                        'master' => $masterGOP,
                        'count' => [
                            'current' => count($groupsOfPropertiesData),
                            'master' => count($masterGOP),
                        ]
                    ],
                    'Properties' => [
                        'directToTemplate' => $pdtPropertiesData,
                        'fromMaster' => $masterProperties,
                        'count' => [
                            'directToTemplate' => count($pdtPropertiesData),
                            'fromMaster' => count($masterProperties),
                            'total' => count($pdtPropertiesData) + count($masterProperties),
                        ]
                    ],
                    'ReferenceDocuments' => $referenceDocumentsData,
                ],
            ];

            return response()->json($response, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to export PDT',
                'message' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    /**
     * Helper: Build Property in ISO 23387 format with complete data
     */
    private function buildProperty23387($propComplete)
    {
        return [
            'dt:GUID' => $propComplete->GUID,
            'dateOfCreation' => $this->formatDate($propComplete->dateOfVersion ?? $propComplete->dateOfCreation),
            'Name' => $this->buildMultilingualNames($propComplete->nameEn, $propComplete->namePt),
            'Definition' => $this->buildMultilingualDefinitions($propComplete->definitionEn, $propComplete->definitionPt),
            'LanguageOfCreator' => $propComplete->creatorsLanguage ?? 'pt-PT',
            'CountryOfOrigin' => $propComplete->countryOfOrigin ?? 'PT',
            'MajorVersion' => (int)($propComplete->versionNumber ?? 1),
            'MinorVersion' => (int)($propComplete->revisionNumber ?? 0),
            'Status' => $propComplete->status ?? 'Active',
            'DataType' => [
                'name' => $propComplete->dataType ?? 'STRING'
            ],
            'Units' => $propComplete->units ?? null,
            'Dimension' => $propComplete->dimension ?? null,
            'PhysicalQuantity' => $propComplete->physicalQuantity ?? null,
            'DimensionRef' => $propComplete->dimension ? ['dt:GUID' => $propComplete->dimension] : null,
        ];
    }

    /**
     * Helper: Build GroupOfProperties in ISO 23387 format
     */
    private function buildGroupOfProperties23387($gop)
    {
        return [
            'dt:GUID' => $gop->GUID,
            'dateOfCreation' => $this->formatDate($gop->dateOfVersion ?? $gop->dateOfCreation),
            'Name' => $this->buildMultilingualNames($gop->gopNameEn, $gop->gopNamePt),
            'Definition' => $this->buildMultilingualDefinitions($gop->definitionEn, $gop->definitionPt),
            'LanguageOfCreator' => $gop->creatorsLanguage ?? 'pt-PT',
            'CountryOfOrigin' => $gop->countryOfOrigin ?? 'PT',
            'MajorVersion' => (int)($gop->versionNumber ?? 1),
            'MinorVersion' => (int)($gop->revisionNumber ?? 0),
            'Status' => $gop->status ?? 'Active',
            'URI' => "https://pdts.pt/datadictionaryviewGOP/{$gop->Id}-{$gop->GUID}",
        ];
    }

    /**
     * Helper: Build multilingual Names
     */
    private function buildMultilingualNames($en, $pt)
    {
        $names = [];
        if ($pt) {
            $names[] = ['language' => 'pt', 'value' => $pt];
        }
        if ($en) {
            $names[] = ['language' => 'en', 'value' => $en];
        }
        return $names ?: [['language' => 'en', 'value' => 'Unnamed']];
    }

    /**
     * Helper: Build multilingual Definitions
     */
    private function buildMultilingualDefinitions($en, $pt)
    {
        $defs = [];
        if ($pt) {
            $defs[] = ['language' => 'pt', 'value' => $pt];
        }
        if ($en) {
            $defs[] = ['language' => 'en', 'value' => $en];
        }
        return $defs ?: [['language' => 'en', 'value' => 'No definition']];
    }

    /**
     * Helper: Format date to ISO 8601
     */
    private function formatDate($date)
    {
        if (!$date) {
            return Carbon::now()->toIso8601String();
        }
        return Carbon::parse($date)->toIso8601String();
    }

    /**
     * GET /api/{pdtID}/json - EN ISO 23387 JSON export
     */
    public function productDataTemplateJson($pdtID)
    {
        try {
            $exporter = new \App\Services\Iso23387Exporter();
            $jsonString = $exporter->exportToJson($pdtID);
            $structure = json_decode($jsonString, true);

            return response()->json($structure, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }

    /**
     * GET /api/{pdtID}/xml - EN ISO 23387 XML export
     */
    public function productDataTemplateXml($pdtID)
    {
        try {
            $exporter = new \App\Services\Iso23387Exporter();
            $xmlString = $exporter->exportToXml($pdtID);

            return response($xmlString, 200)
                ->header('Content-Type', 'application/xml');
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function productDataTemplateold($pdtID)
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
            'category' => 'required|string',
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
        $pdt->category = $request->input('category');
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
            'QualityAssuranceProcedure' => "EN ISO 23386:2020",


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
            'Code' => (string) $productDataTemplate->GUID . '-' . (string) $productDataTemplate->Id,
            'Name' => $productDataTemplate->pdtNamePt,
            'Definition' => $productDataTemplate->descriptionPt,
            'ActivationDateUtc' => $productDataTemplate->dateOfVersion,
            'DeActivationDateUtc' => $productDataTemplate->depreciationDate,
            'DeprecationExplanation' => ($productDataTemplate->depreciationDate != '0000-00-00') ? $productDataTemplate->depreciationDate : null,
            'DocumentReference' => referencedocuments::where('GUID', $productDataTemplate->referenceDocumentGUID)->value('rdName'),
            'RevisionDateUtc' => $productDataTemplate->dateOfVersion,
            'RevisionNumber' => (int)($productDataTemplate->versionNumber + $productDataTemplate->revisionNumber - 1),
            'Status' => $productDataTemplate->status,
            'Uid' => $productDataTemplate->GUID,
            'VersionDateUtc' => $productDataTemplate->dateOfEdition,
            'VersionNumber' => (int)$productDataTemplate->editionNumber,

        ];




        return $classData;
    }


    private function transformGroupOfProperties($groupOfProperties)
    {
        // Implement the transformation logic for a group of properties
        // Use $groupOfProperties and its properties to structure the data
        $productDataTemplate = productdatatemplates::WHERE("Id", [$groupOfProperties->pdtId])->first();
        // Assuming $groupOfProperties is an instance of the GroupOfProperties model
        $pdtId = $groupOfProperties->pdtId;
        $versionNumber = $groupOfProperties->versionNumber;
        $revisionNumber = $groupOfProperties->revisionNumber;

        // Fetch Group of Properties with the same pdtId and higher version or same version with higher revision (ReplacingObjectCodes)
        $replacingGroups = groupofproperties::where('pdtId', $pdtId)
            ->where(function ($query) use ($versionNumber, $revisionNumber) {
                $query->where('versionNumber', '>', $versionNumber)
                    ->orWhere(function ($query) use ($versionNumber, $revisionNumber) {
                        $query->where('versionNumber', $versionNumber)
                            ->where('revisionNumber', '>', $revisionNumber);
                    });
            })
            ->get();
        // Extract the codes from the result set with the format "GUID-ID"
        $replacingCodes = $replacingGroups->map(function ($replacingGroup) {
            return $replacingGroup->GUID . '-' . $replacingGroup->Id;
        })->toArray();
        // Fetch Group of Properties with the same pdtId and lower version or same version with lower revision (ReplacedObjectCodes)
        $replacedGroups = groupofproperties::where('pdtId', $pdtId)
            ->where(function ($query) use ($versionNumber, $revisionNumber) {
                $query->where('versionNumber', '<', $versionNumber)
                    ->orWhere(function ($query) use ($versionNumber, $revisionNumber) {
                        $query->where('versionNumber', $versionNumber)
                            ->where('revisionNumber', '<', $revisionNumber);
                    });
            })
            ->get();
        // Extract the codes from the result set with the format "GUID-ID"
        $replacedCodes = $replacedGroups->map(function ($replacedGroup) {
            return $replacedGroup->GUID . '-' . $replacedGroup->Id;
        })->toArray();


        $groupData = [
            'ClassType' => 'GroupOfProperties',
            'ParentClassCode' =>   (string) $productDataTemplate->GUID . '-' . (string) $groupOfProperties->pdtId,
            'Code' => (string)$groupOfProperties->GUID . '-' . (string)$groupOfProperties->Id,
            'Name' => $groupOfProperties->gopNamePt,
            'Status' => $groupOfProperties->status,
            'Definition' => $groupOfProperties->definitionPt,
            'ActivationDateUtc' => $groupOfProperties->dateOfVersion,
            'CountryOfOrigin' => $groupOfProperties->countryOfOrigin,
            'CreatorLanguageIsoCode' => $groupOfProperties->creatorsLanguage,
            'DeprecationExplanation' => ($groupOfProperties->depreciationDate != '0000-00-00') ? $groupOfProperties->depreciationDate : null,
            'DeprecationExplanation' => $groupOfProperties->depreciationExplanation,
            'DocumentReference' => referencedocuments::where('GUID', $groupOfProperties->referenceDocumentGUID)->value('rdName'),
            'ReplacedObjectCodes' => $replacedCodes,
            'ReplacingObjectCodes' => $replacingCodes,
            'RevisionDateUtc' => $groupOfProperties->dateOfRevision,
            'RevisionNumber' => (int)$groupOfProperties->revisionNumber,
            'Uid' => $groupOfProperties->GUID,
            'VersionDateUtc' => $groupOfProperties->dateOfVersion,
            'VersionNumber' => (int)$groupOfProperties->versionNumber,
            'VisualRepresentationUri' => $groupOfProperties->visualRepresentation,
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
        $group = groupofproperties::WHERE('Id', $property->gopID)->value('gopNamePt');
        $propertyData = [

            'PropertyCode' =>  (string) $property->GUID . '-' . (string) $property->propertyId,
            'Code' =>  (string) $property->Id,
            'Description' => $property->descriptionPt,
            'PropertySet' => $this->convertToPascalCase($group),

        ];

        return $propertyData;
    }

    private function transformPropertyDataDictionary($property)
    {
        // get referencedocument of the property from properties table
        $propertyRD = properties::where('propertyId', $property->Id)->latest()->value('referenceDocumentGUID') ?? "n/a";

        //get replacing and replaced properties
        // Assuming $property is an instance of the Property model
        $guid = $property->GUID;
        $versionNumber = $property->versionNumber;
        $revisionNumber = $property->revisionNumber;
        // Fetch properties with the same GUID and higher version or same version with higher revision (ReplacingObjectCodes)
        $replacingProperties = propertiesdatadictionaries::where('GUID', $guid)
            ->where(function ($query) use ($versionNumber, $revisionNumber) {
                $query->where('versionNumber', '>', $versionNumber)
                    ->orWhere(function ($query) use ($versionNumber, $revisionNumber) {
                        $query->where('versionNumber', $versionNumber)
                            ->where('revisionNumber', '>', $revisionNumber);
                    });
            })
            ->get();
        // Extract the codes from the result set with the format "GUID-ID"
        $replacingCodes = $replacingProperties->map(function ($replacingProperty) {
            return $replacingProperty->GUID . '-' . $replacingProperty->Id;
        })->toArray();
        // Fetch properties with the same GUID and lower version or same version with lower revision (ReplacedObjectCodes)
        $replacedProperties = propertiesdatadictionaries::where('GUID', $guid)
            ->where(function ($query) use ($versionNumber, $revisionNumber) {
                $query->where('versionNumber', '<', $versionNumber)
                    ->orWhere(function ($query) use ($versionNumber, $revisionNumber) {
                        $query->where('versionNumber', $versionNumber)
                            ->where('revisionNumber', '<', $revisionNumber);
                    });
            })
            ->get();

        // Extract the codes from the result set with the format "GUID-ID"
        $replacedCodes = $replacedProperties->map(function ($replacedProperty) {
            return $replacedProperty->GUID . '-' . $replacedProperty->Id;
        })->toArray();


        $propertyData = [
            'Code' => (string)$property->GUID . '-' . (string)$property->Id,
            'Name' => $property->namePt,
            'Definition' => $property->definitionPt,
            'Units' =>  [$property->units],
            'ActivationDateUtc' => ($property->dateOfVersion != '0000-00-00') ? $property->dateOfVersion : '9999-99-99',
            'CountryOfOrigin' => $property->countryOfOrigin,
            'CreatorLanguageIsoCode' => $property->creatorsLanguage,
            'DeActivationDateUtc' => ($property->depreciationDate != '0000-00-00') ? $property->depreciationDate : null,
            'DeprecationExplanation' => $property->depreciationExplanation,
            'DocumentReference' => $propertyRD !== "n/a" ? referencedocuments::WHERE('GUID', $propertyRD)->value('rdName') : null,
            'IsDynamic' => false,
            'PhysicalQuantity' => $property->physicalQuantity,
            'ReplacedObjectCodes' => $replacedCodes,
            'ReplacingObjectCodes' => $replacingCodes,
            'RevisionDateUtc' => $property->dateOfRevision,
            'RevisionNumber' => (int)$property->revisionNumber,
            'Status' => $property->status,
            'TextFormat' => $property->textFormat,
            'Uid' => $property->GUID,
            'VersionDateUtc' => $property->dateOfVersion,
            'VersionNumber' => (int)$property->versionNumber,
            'VisualRepresentationUri' => $property->visualRepresentation,

        ];


        return $propertyData;
    }

    public function exportDataToJsonPSETS()
    {
        // Fetch data using raw SQL queries
        $productDataTemplates = DB::select('SELECT * FROM productdatatemplates');

        // Fetch properties from propertiesdatadictionary
        $propertiesData = DB::select('SELECT * FROM propertiesdatadictionaries');

        // Fetch groups of properties from groupofproperties
        $groupsOfProperties = DB::select('SELECT * FROM groupofproperties');

        // Transform data into the desired JSON format
        $jsonData = $this->transformDataPSETS($productDataTemplates, $propertiesData, $groupsOfProperties);

        // Save JSON data to a temporary file
        $tempFilePath = tempnam(sys_get_temp_dir(), 'PDTs.pt_Domain_bsdd_PSETS_');
        file_put_contents($tempFilePath, json_encode($jsonData, JSON_PRETTY_PRINT));

        // Add the current date to the file name
        $currentDate = Carbon::now()->format('Y-m-d');
        $fileName = 'PDTs.pt_Domain_bsdd_PSETS_' . $currentDate . '.json';

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

    private function transformDataPSETS($productDataTemplates, $propertiesData)
    {
        $jsonData = [
            'ModelVersion' => '2.0',
            'OrganizationCode' => 'pdtspt',
            'DictionaryCode' => 'pdtspt',
            'LanguageIsoCode' => 'pt-PT',
            'DictionaryName' => 'pdtspt',
            'DictionaryVersion' => "0.1",
            'MoreInfoUrl' => "https://pdts.pt",
            'ChangeRequestEmailAddress' => "pdts.portugal@gmail.com",
            'License' => "CC BY",
            'LicenseUrl' => "https://creativecommons.org/share-your-work/cclicenses/",
            'QualityAssuranceProcedure' => "EN ISO 23386:2020",
            'Classes' => [],
            'Properties' => [],
        ];

        foreach ($productDataTemplates as $productDataTemplate) {
            $classData = $this->transformProductDataTemplatePSETS($productDataTemplate);
            $jsonData['Classes'][] = $classData;
        }

        // Transform properties from propertiesdatadictionary
        foreach ($propertiesData as $property) {
            $jsonData['Properties'][] = $this->transformPropertyDataDictionary($property);
        }

        return $jsonData;
    }

    private function transformProductDataTemplatePSETS($productDataTemplate)
    {
        // Implement the transformation logic for a product data template
        // Use $productDataTemplate and its properties to structure the data

        $classData = [
            'ClassType' => 'Class',
            'Code' => (string) $productDataTemplate->GUID . '-' . (string) $productDataTemplate->Id,
            'Name' => $productDataTemplate->pdtNamePt,
            'Definition' => $productDataTemplate->descriptionPt,
            'ActivationDateUtc' => $productDataTemplate->dateOfVersion,
            'DeActivationDateUtc' => $productDataTemplate->depreciationDate,
            'DeprecationExplanation' => ($productDataTemplate->depreciationDate != '0000-00-00') ? $productDataTemplate->depreciationDate : null,
            'DocumentReference' => $productDataTemplate->referenceDocumentGUID !== "n/a" ? referencedocuments::where('GUID', $productDataTemplate->referenceDocumentGUID)->value('rdName') : null,
            'RevisionDateUtc' => $productDataTemplate->dateOfVersion,
            'RevisionNumber' => (int)($productDataTemplate->versionNumber + $productDataTemplate->revisionNumber - 1),
            'Status' => $productDataTemplate->status,
            'Uid' => $productDataTemplate->GUID,
            'VersionDateUtc' => $productDataTemplate->dateOfEdition,
            'VersionNumber' => (int)$productDataTemplate->editionNumber,

            'ClassProperties' => [],
        ];

        $groupOfProperties = groupofproperties::WHERE('pdtId', $productDataTemplate->Id)->get();

        foreach ($groupOfProperties as $group) {
            $classProperties = DB::select('SELECT * FROM properties WHERE gopID = ?', [$group->Id]);

            foreach ($classProperties as $property) {
                $propertyData = $this->transformPropertyPSET($property);
                $classData['ClassProperties'][] = $propertyData;
            }
        }

        return $classData;
    }


    private function transformPropertyPSET($property)
    {

        $group = groupofproperties::WHERE('Id', $property->gopID)->value('gopNamePt');
        $propertyData = [
            'PropertyCode' =>  (string) $property->GUID . '-' . (string) $property->propertyId,
            'Code' =>  (string) $property->Id,
            'Description' => $property->descriptionPt,
            'PropertySet' => $this->convertToPascalCase($group),
        ];

        return $propertyData;
    }

    private function convertToPascalCase($string)
    {
        // Remove accented characters
        $string = iconv('UTF-8', 'ASCII//TRANSLIT', $string);

        // Remove special characters
        $string = preg_replace('/[^a-zA-Z0-9]/', '', $string);

        // Convert the string to lowercase
        $string = strtolower($string);

        // Convert the string to PascalCase
        $words = explode(' ', $string);
        $pascalCaseWords = array_map('ucfirst', $words);
        $pascalCaseString = implode('', $pascalCaseWords);

        return $pascalCaseString;
    }

    /**
     * Download single PDT as JSON (EN ISO 23387 format)
     */
    public function downloadPdtJson($pdtId)
    {
        $exporter = new \App\Services\Iso23387Exporter();
        $json = $exporter->exportToJson($pdtId);

        $pdt = productdatatemplates::find($pdtId);
        $fileName = $pdt->pdtNamePt . '_V' . $pdt->editionNumber . '.' . $pdt->versionNumber . '.' . $pdt->revisionNumber . '_' . date('Y-m-d') . '.json';

        return response()->streamDownload(
            function () use ($json) {
                echo $json;
            },
            $fileName,
            ['Content-Type' => 'application/json']
        );
    }

    /**
     * Download single PDT as XML (EN ISO 23387 format with XSD validation)
     */
    public function downloadPdtXml($pdtId)
    {
        $exporter = new \App\Services\Iso23387Exporter();
        $xml = $exporter->exportToXml($pdtId);

        $pdt = productdatatemplates::find($pdtId);
        $fileName = $pdt->pdtNamePt . '_V' . $pdt->editionNumber . '.' . $pdt->versionNumber . '.' . $pdt->revisionNumber . '_' . date('Y-m-d') . '.xml';

        return response()->streamDownload(
            function () use ($xml) {
                echo $xml;
            },
            $fileName,
            ['Content-Type' => 'application/xml']
        );
    }
}
