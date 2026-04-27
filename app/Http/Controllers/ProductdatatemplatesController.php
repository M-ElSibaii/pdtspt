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
    public function productDataTemplate($pdtID)
    {
        try {
            $exporter = new \App\Services\Iso23387Exporter();

            $data = $exporter->exportWithRawData($pdtID);

            return response()->json($data, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 404);
        }
    }
    /**
     * Build ISO 23387-compliant property structure using the Iso23387Exporter service
     */
    private function buildProperty23387($propComplete)
    {
        $exporter = new \App\Services\Iso23387Exporter();
        // Use buildPropertyElement for strict ISO 23387 property structure
        return $exporter->buildPropertyElement($propComplete);
    }

    /**
     * Build ISO 23387-compliant group of properties structure
     */
    private function buildGroupOfProperties23387($gop)
    {
        return [
            'Name' => $this->buildMultilingualNames($gop->gopNameEn, $gop->gopNamePt),
            'Definition' => $this->buildMultilingualDefinitions($gop->definitionEn, $gop->definitionPt),
            'dt:GUID' => $gop->GUID,
            'referenceURI' => 'https://pdts.pt/groupofpropertiesview/' . $gop->Id . '-' . $this->convertToPascalCase($gop->gopNamePt)
        ];
    }

    /**
     * Build multilingual name array for ISO 23387
     */
    private function buildMultilingualNames($en, $pt): array
    {
        $names = [];
        if ($en) {
            $names[] = ['language' => 'en', 'value' => $en];
        }
        if ($pt) {
            $names[] = ['language' => 'pt', 'value' => $pt];
        }
        return !empty($names) ? $names : [['language' => 'en', 'value' => 'N/A']];
    }

    /**
     * Build multilingual definition array for ISO 23387
     */
    private function buildMultilingualDefinitions($en, $pt): array
    {
        $defs = [];
        if ($en) {
            $defs[] = ['language' => 'en', 'value' => $en];
        }
        if ($pt) {
            $defs[] = ['language' => 'pt', 'value' => $pt];
        }
        return !empty($defs) ? $defs : [['language' => 'en', 'value' => 'N/A']];
    }

    /**
     * Format date to ISO 8601 string
     */
    private function formatDate($date): string
    {
        if (!$date) {
            return Carbon::now()->toIso8601String();
        }
        return Carbon::parse($date)->toIso8601String();
    }

    /**
     * View a single PDT with all attributes
     */
    public function viewPdt($id)
    {
        $pdt = productdatatemplates::where('Id', $id)->firstOrFail();

        // Load related data
        $groupsOfProperties = groupofproperties::where('pdtId', $id)->get();
        $pdtVersions = productdatatemplates::where('GUID', $pdt->GUID)->get();

        // Load ObjectType if exists
        $objectType = null;
        if ($pdt->constructionObjectGUID) {
            $objectType = constructionobjects::where('GUID', $pdt->constructionObjectGUID)->first();
        }

        // Load Master properties info (count)
        $masterPropertiesCount = 0;
        if ($pdt->GUID !== '230d9954097541b793f2a1fddb8bd0ad') {
            $masterPdt = productdatatemplates::where('GUID', '230d9954097541b793f2a1fddb8bd0ad')
                ->orderByRaw('versionNumber DESC, revisionNumber DESC')
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
                MAX(revisionNumber) as max_revisionNumber
             
                FROM productdatatemplates 
                GROUP BY GUID) as mx"),
                function ($join) {
                    $join->on('mx.GUID', '=', 'pdt.GUID');
                    $join->on('mx.max_versionNumber', '=', 'pdt.versionNumber');
                    $join->on('mx.max_revisionNumber', '=', 'pdt.revisionNumber');
                }
            )
            ->get();

        $categories = DB::table('productdatatemplates as pdt')
            ->join(
                DB::raw("(SELECT 
                    GUID,
                    MAX(versionNumber) as max_versionNumber,
                    MAX(revisionNumber) as max_revisionNumber
                  FROM productdatatemplates 
                  GROUP BY GUID) as mx"),
                function ($join) {
                    $join->on('mx.GUID', '=', 'pdt.GUID');
                    $join->on('mx.max_versionNumber', '=', 'pdt.versionNumber');
                    $join->on('mx.max_revisionNumber', '=', 'pdt.revisionNumber');
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
    public function productDataTemplateout($pdtID)
    {
        try {
            // Get latest version of PDT
            $pdt = DB::table('productdatatemplates')
                ->where(function ($query) use ($pdtID) {
                    $query->where('Id', $pdtID)
                        ->orWhere('GUID', $pdtID);
                })
                ->orderByRaw('versionNumber DESC, revisionNumber DESC')
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
                    ->orderByRaw('versionNumber DESC, revisionNumber DESC')
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
                            'URI' => [
                                ['language' => 'en', 'value' => 'https://pdts.pt/referencedocumentview/' . $refDoc->GUID . '-' . str_replace(' ', '', $refDoc->rdName ?? '')]
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
                        'URI' => "https://pdts.pt/pdtview/{$pdt->Id}-" . self::convertToPascalCase($pdt->pdtNamePt),
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
            'dateOfRevision' => 'required|date',
            'dateOfVersion' => 'required|date',
            'status' => 'required|string',
            'category' => 'required|string',
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
        $pdt->dateOfRevision = now();
        $pdt->dateOfVersion = now();
        $pdt->created_at = now();
        $pdt->updated_at = now();
        $pdt->status = $request->input('status');
        $pdt->category = $request->input('category');
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
            'RevisionDateUtc' => $productDataTemplate->dateOfRevision,
            'RevisionNumber' => $productDataTemplate->revisionNumber,
            'Status' => $productDataTemplate->status,
            'Uid' => $productDataTemplate->GUID,
            'VersionDateUtc' => $productDataTemplate->dateOfVersion,
            'VersionNumber' => $productDataTemplate->versionNumber,

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
            'PropertySet' => self::convertToPascalCase($group),

        ];

        return $propertyData;
    }

    private function transformPropertyDataDictionary($property)
    {
        // get referencedocument of the property from properties table
        $propertyRD = properties::where('propertyId', $property->Id)->latest()->value('referenceDocumentGUID') ?? "n/a";

        $guid = $property->GUID;
        $versionNumber = $property->versionNumber;
        $revisionNumber = $property->revisionNumber;

        // ReplacingObjectCodes: same GUID, higher version/revision
        $replacingProperties = propertiesdatadictionaries::where('GUID', $guid)
            ->where(function ($query) use ($versionNumber, $revisionNumber) {
                $query->where('versionNumber', '>', $versionNumber)
                    ->orWhere(function ($query) use ($versionNumber, $revisionNumber) {
                        $query->where('versionNumber', $versionNumber)
                            ->where('revisionNumber', '>', $revisionNumber);
                    });
            })->get();

        $replacingCodes = $replacingProperties->map(fn($p) => self::sanitizePascalCase($p->namePt))->toArray();

        // ReplacedObjectCodes: same GUID, lower version/revision
        $replacedProperties = propertiesdatadictionaries::where('GUID', $guid)
            ->where(function ($query) use ($versionNumber, $revisionNumber) {
                $query->where('versionNumber', '<', $versionNumber)
                    ->orWhere(function ($query) use ($versionNumber, $revisionNumber) {
                        $query->where('versionNumber', $versionNumber)
                            ->where('revisionNumber', '<', $revisionNumber);
                    });
            })->get();

        $replacedCodes = $replacedProperties->map(fn($p) => self::sanitizePascalCase($p->namePt))->toArray();

        // FIX 3: Code is PascalCase of namePt (not GUID+Id)
        $code = self::sanitizePascalCase($property->namePt);

        // FIX 4: OwnedUri for properties: https://pdts.pt/datadictionaryview/Id-namePt
        $ownedUri = 'https://pdts.pt/datadictionaryview/' . $property->Id . '-' . self::sanitizePascalCase($property->namePt);

        // FIX 2: Map dataType from DB to bSDD allowed values
        $dataType = $this->mapDataType($property->dataType);

        $propertyData = [
            'Code'                      => $property->Id . '-' . $code,
            'Name'                      => $property->namePt,
            'Definition'                => $property->definitionPt,
            'DataType'                  => $dataType, // FIX 2: now included
            'Units'                     => $property->units ? [$property->units] : [],
            'ActivationDateUtc'         => ($property->dateOfVersion && $property->dateOfVersion !== '0000-00-00') ? $property->dateOfVersion : null,
            'CountryOfOrigin'           => $property->countryOfOrigin,
            'CreatorLanguageIsoCode'    => $property->creatorsLanguage,
            'DeActivationDateUtc'       => ($property->depreciationDate && $property->depreciationDate !== '0000-00-00') ? $property->depreciationDate : null,
            'DeprecationExplanation'    => $property->depreciationExplanation ?: null,
            'DocumentReference'         => $propertyRD !== "n/a" ? referencedocuments::where('GUID', $propertyRD)->value('rdName') : null,
            'IsDynamic'                 => (bool)($property->dynamicProperty ?? false),
            'OwnedUri'                  => $ownedUri, // FIX 4
            'PhysicalQuantity'          => $property->physicalQuantity,
            'ReplacedObjectCodes'       => $replacedCodes,
            'ReplacingObjectCodes'      => $replacingCodes,
            'RevisionDateUtc'           => $property->dateOfRevision ?: null,
            'RevisionNumber'            => (int)$property->revisionNumber,
            'Status'                    => $property->status,
            'TextFormat'                => $property->textFormat ?: null,
            'Uid'                       => $property->GUID,
            'VersionDateUtc'            => ($property->dateOfVersion && $property->dateOfVersion !== '0000-00-00') ? $property->dateOfVersion : null,
            'VersionNumber'             => (int)$property->versionNumber,
            'VisualRepresentationUri'   => $ownedUri ?: null,
        ];

        return $propertyData;
    }

    /**
     * FIX 2: Map your DB dataType values to bSDD allowed values.
     * bSDD only accepts: Boolean, Character, Integer, Real, String, Time
     */
    private function mapDataType(?string $dataType): string
    {
        if (!$dataType) return 'String'; // safe default

        $map = [
            'boolean'   => 'Boolean',
            'bool'      => 'Boolean',
            'character' => 'Character',
            'char'      => 'Character',
            'integer'   => 'Integer',
            'int'       => 'Integer',
            'number'    => 'Integer',
            'real'      => 'Real',
            'float'     => 'Real',
            'double'    => 'Real',
            'decimal'   => 'Real',
            'string'    => 'String',
            'text'      => 'String',
            'varchar'   => 'String',
            'time'      => 'Time',
            'date'      => 'Time',
            'datetime'  => 'Time',
        ];

        return $map[strtolower(trim($dataType))] ?? 'String';
    }

    public function exportDataToJsonPSETS()
    {
        // FIX 1: Only the latest version of each PDT (max versionNumber, then max revisionNumber)
        $productDataTemplates = DB::select('
        SELECT pdt.*
        FROM productdatatemplates pdt
        INNER JOIN (
            SELECT GUID, MAX(versionNumber) as maxVersion
            FROM productdatatemplates
            GROUP BY GUID
        ) latest ON pdt.GUID = latest.GUID AND pdt.versionNumber = latest.maxVersion
        INNER JOIN (
            SELECT GUID, versionNumber, MAX(revisionNumber) as maxRevision
            FROM productdatatemplates
            GROUP BY GUID, versionNumber
        ) latestRev ON pdt.GUID = latestRev.GUID
            AND pdt.versionNumber = latestRev.versionNumber
            AND pdt.revisionNumber = latestRev.maxRevision
    ');

        // FIX 1: Same for properties data dictionary
        $propertiesData = DB::select('
        SELECT p.*
        FROM propertiesdatadictionaries p
        INNER JOIN (
            SELECT GUID, MAX(versionNumber) as maxVersion
            FROM propertiesdatadictionaries
            GROUP BY GUID
        ) latest ON p.GUID = latest.GUID AND p.versionNumber = latest.maxVersion
        INNER JOIN (
            SELECT GUID, versionNumber, MAX(revisionNumber) as maxRevision
            FROM propertiesdatadictionaries
            GROUP BY GUID, versionNumber
        ) latestRev ON p.GUID = latestRev.GUID
            AND p.versionNumber = latestRev.versionNumber
            AND p.revisionNumber = latestRev.maxRevision
    ');

        $groupsOfProperties = DB::select('SELECT * FROM groupofproperties');

        $jsonData = $this->transformDataPSETS($productDataTemplates, $propertiesData, $groupsOfProperties);

        $tempFilePath = tempnam(sys_get_temp_dir(), 'PDTs.pt_Domain_bsdd_PSETS_');
        file_put_contents($tempFilePath, json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $currentDate = Carbon::now()->format('Y-m-d');
        $fileName = 'PDTs.pt_Domain_bsdd_PSETS_' . $currentDate . '.json';

        $headers = [
            'Content-Type'        => 'application/json',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ];

        return response()->streamDownload(
            function () use ($tempFilePath) {
                readfile($tempFilePath);
                unlink($tempFilePath);
            },
            $fileName,
            $headers
        );
    }

    private function transformDataPSETS($productDataTemplates, $propertiesData)
    {
        $jsonData = [
            'ModelVersion'               => '2.0',
            'OrganizationCode'           => 'pdtspt',
            'DictionaryCode'             => 'pdtspt',
            'LanguageIsoCode'            => 'pt-PT',
            'LanguageOnly'               => false,
            'UseOwnUri'                  => true,  // FIX 4: tell bSDD we supply our own URIs
            'DictionaryUri'              => 'https://pdts.pt', // FIX 4: base URI
            'DictionaryName'             => 'PDTs.pt',
            'DictionaryVersion'          => '0.1',
            'MoreInfoUrl'                => 'https://pdts.pt',
            'ChangeRequestEmailAddress'  => 'pdts.portugal@gmail.com',
            'License'                    => 'CC BY',
            'LicenseUrl'                 => 'https://creativecommons.org/share-your-work/cclicenses/',
            'QualityAssuranceProcedure'  => 'EN ISO 23386:2020',
            'Classes'                    => [],
            'Properties'                 => [],
        ];

        foreach ($productDataTemplates as $productDataTemplate) {
            $jsonData['Classes'][] = $this->transformProductDataTemplatePSETS($productDataTemplate);
        }

        foreach ($propertiesData as $property) {
            $jsonData['Properties'][] = $this->transformPropertyDataDictionary($property);
        }

        return $jsonData;
    }

    private function transformProductDataTemplatePSETS($productDataTemplate)
    {
        // FIX 3: Code is PascalCase of name
        $code = self::convertToPascalCase($productDataTemplate->pdtNamePt);

        // FIX 4: OwnedUri for PDTs: https://pdts.pt/pdtview/Id-pdtNamePt
        $ownedUri = 'https://pdts.pt/pdtview/' . $productDataTemplate->Id . '-' . self::convertToPascalCase($productDataTemplate->pdtNamePt);

        $classData = [
            'ClassType'              => 'Class',
            'Code'                   => $code, // FIX 3
            'Name'                   => $productDataTemplate->pdtNamePt,
            'Definition'             => $productDataTemplate->descriptionPt,
            'OwnedUri'               => $ownedUri, // FIX 4
            'ActivationDateUtc'      => ($productDataTemplate->dateOfVersion && $productDataTemplate->dateOfVersion !== '0000-00-00') ? $productDataTemplate->dateOfVersion : null,
            'DeActivationDateUtc'    => ($productDataTemplate->depreciationDate && $productDataTemplate->depreciationDate !== '0000-00-00') ? $productDataTemplate->depreciationDate : null,
            'DeprecationExplanation' => ($productDataTemplate->depreciationDate && $productDataTemplate->depreciationDate !== '0000-00-00') ? $productDataTemplate->depreciationExplanation : null,
            'DocumentReference'      => ($productDataTemplate->referenceDocumentGUID && $productDataTemplate->referenceDocumentGUID !== "n/a")
                ? referencedocuments::where('GUID', $productDataTemplate->referenceDocumentGUID)->value('rdName')
                : null,
            'RevisionDateUtc'        => $productDataTemplate->dateOfRevision ?: null,
            'RevisionNumber'         => (int)$productDataTemplate->revisionNumber,
            'Status'                 => $productDataTemplate->status,
            'Uid'                    => $productDataTemplate->GUID,
            'VersionDateUtc'         => ($productDataTemplate->dateOfVersion && $productDataTemplate->dateOfVersion !== '0000-00-00') ? $productDataTemplate->dateOfVersion : null,
            'VersionNumber'          => (int)$productDataTemplate->versionNumber,
            'ClassProperties'        => [],
        ];

        $groupOfProperties = groupofproperties::where('pdtId', $productDataTemplate->Id)->get();

        foreach ($groupOfProperties as $group) {
            $classProperties = DB::select('SELECT * FROM properties WHERE gopID = ?', [$group->Id]);
            foreach ($classProperties as $property) {
                $classData['ClassProperties'][] = $this->transformPropertyPSET($property);
            }
        }

        return $classData;
    }

    private function transformPropertyPSET($property)
    {
        $group = groupofproperties::where('Id', $property->gopID)->value('gopNamePt');

        $ddProperty = propertiesdatadictionaries::where('Id', $property->propertyId)->first();

        $propertyCode = self::sanitizePascalCase(
            $ddProperty->namePt ?? $property->descriptionPt
        );

        $ddId = $ddProperty->Id ?? $property->Id;
        $ddName = $ddProperty->namePt ?? $property->descriptionPt;

        return [
            'Code'         => (string)$ddId,
            'PropertyCode' => $propertyCode,
            'Description'  => $property->descriptionPt ?: null,
            'PropertySet'  => self::convertToPascalCase($group),
            'OwnedUri'     => 'https://pdts.pt/datadictionaryview/' . $ddId . '-' . self::sanitizePascalCase($ddName),
        ];
    }


    public static function sanitizePascalCase($string): string
    {
        if (!$string) return '';

        $accents = [
            'à' => 'a',
            'á' => 'a',
            'â' => 'a',
            'ã' => 'a',
            'ä' => 'a',
            'å' => 'a',
            'è' => 'e',
            'é' => 'e',
            'ê' => 'e',
            'ë' => 'e',
            'ì' => 'i',
            'í' => 'i',
            'î' => 'i',
            'ï' => 'i',
            'ò' => 'o',
            'ó' => 'o',
            'ô' => 'o',
            'õ' => 'o',
            'ö' => 'o',
            'ù' => 'u',
            'ú' => 'u',
            'û' => 'u',
            'ü' => 'u',
            'ý' => 'y',
            'ÿ' => 'y',
            'ç' => 'c',
            'ñ' => 'n',
            'À' => 'A',
            'Á' => 'A',
            'Â' => 'A',
            'Ã' => 'A',
            'Ä' => 'A',
            'Å' => 'A',
            'È' => 'E',
            'É' => 'E',
            'Ê' => 'E',
            'Ë' => 'E',
            'Ì' => 'I',
            'Í' => 'I',
            'Î' => 'I',
            'Ï' => 'I',
            'Ò' => 'O',
            'Ó' => 'O',
            'Ô' => 'O',
            'Õ' => 'O',
            'Ö' => 'O',
            'Ù' => 'U',
            'Ú' => 'U',
            'Û' => 'U',
            'Ü' => 'U',
            'Ý' => 'Y',
            'Ç' => 'C',
            'Ñ' => 'N',
        ];
        $string = strtr($string, $accents);

        // Remove disallowed characters and whitespace, preserve casing
        $string = preg_replace('/["#%\/\\\\:`{}\[\]|;<>?~\s]/', '', $string);

        return $string;
    }

    public static function convertToPascalCase($string): string
    {
        if (!$string) return '';

        // Replace Portuguese/accented characters with ASCII equivalents
        $accents = [
            'à' => 'a',
            'á' => 'a',
            'â' => 'a',
            'ã' => 'a',
            'ä' => 'a',
            'å' => 'a',
            'è' => 'e',
            'é' => 'e',
            'ê' => 'e',
            'ë' => 'e',
            'ì' => 'i',
            'í' => 'i',
            'î' => 'i',
            'ï' => 'i',
            'ò' => 'o',
            'ó' => 'o',
            'ô' => 'o',
            'õ' => 'o',
            'ö' => 'o',
            'ù' => 'u',
            'ú' => 'u',
            'û' => 'u',
            'ü' => 'u',
            'ý' => 'y',
            'ÿ' => 'y',
            'ç' => 'c',
            'ñ' => 'n',
            'À' => 'A',
            'Á' => 'A',
            'Â' => 'A',
            'Ã' => 'A',
            'Ä' => 'A',
            'Å' => 'A',
            'È' => 'E',
            'É' => 'E',
            'Ê' => 'E',
            'Ë' => 'E',
            'Ì' => 'I',
            'Í' => 'I',
            'Î' => 'I',
            'Ï' => 'I',
            'Ò' => 'O',
            'Ó' => 'O',
            'Ô' => 'O',
            'Õ' => 'O',
            'Ö' => 'O',
            'Ù' => 'U',
            'Ú' => 'U',
            'Û' => 'U',
            'Ü' => 'U',
            'Ý' => 'Y',
            'Ç' => 'C',
            'Ñ' => 'N',
        ];
        $string = strtr($string, $accents);

        // Remove any remaining non-alphanumeric characters (except spaces/dashes/underscores used as word separators)
        $string = preg_replace('/[^a-zA-Z0-9\s\-_]/', '', $string);

        // Split on word separators, lowercase each word, ucfirst, then join
        $words = preg_split('/[\s_\-]+/', trim($string));
        $pascalCaseString = implode('', array_map('ucfirst', array_map('strtolower', $words)));

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
        $fileName = $pdt->pdtNamePt . '_V' . $pdt->versionNumber . '.' . $pdt->revisionNumber . '_' . date('Y-m-d') . '.json';

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
        $fileName = $pdt->pdtNamePt . '_V' . $pdt->versionNumber . '.' . $pdt->revisionNumber . '_' . date('Y-m-d') . '.xml';

        return response()->streamDownload(
            function () use ($xml) {
                echo $xml;
            },
            $fileName,
            ['Content-Type' => 'application/xml']
        );
    }
}
