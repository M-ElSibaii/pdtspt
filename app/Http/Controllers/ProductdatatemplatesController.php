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
    private const MASTER_PDT_GUID = '230d9954097541b793f2a1fddb8bd0ad';

    /** Memoized: has the relationship store been seeded with any PDT IsSubtypeOf edge? */
    private ?bool $pdtSubtypeSeeded = null;

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

        // Subtype/parent relations (EN ISO 23387:2025 R-23387-7), read-only display.
        // Resolves each IsSubtypeOf target lineage to its latest-Active PDT for linking.
        $subtypeParents = [];
        $relSvc = new \App\Services\RelationshipService();
        foreach ($relSvc->relationsFrom(\App\Models\EntityRelationship::TYPE_PDT, $pdt->GUID, \App\Models\EntityRelationship::REL_IS_SUBTYPE_OF) as $rel) {
            if ($rel->targetEntityType !== \App\Models\EntityRelationship::TYPE_PDT) continue;
            $target = productdatatemplates::where('GUID', $rel->targetGuid)
                ->orderByRaw('versionNumber DESC, revisionNumber DESC')
                ->first();
            if ($target) {
                $subtypeParents[] = ['name' => $target->pdtNamePt ?: $target->pdtNameEn, 'pdtId' => $target->Id, 'guid' => $target->GUID];
            }
        }

        return view('pdtview', compact('pdt', 'groupsOfProperties', 'pdtVersions', 'objectType', 'masterPropertiesCount', 'subtypeParents'));
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

    // [Legacy create()/store() (old "Create PDTs" form) removed — superseded by
    //  PdtCreateController (unified CREATE editor). bSDD export + viewPdt + dashboard +
    //  API methods in this controller are unaffected.]

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

    private function transformPropertyDataDictionary($property)
    {
        $propertyRD = properties::where('propertyId', $property->Id)->latest()->value('referenceDocumentGUID') ?? 'n/a';

        $guid = $property->GUID;
        $versionNumber = $property->versionNumber;
        $revisionNumber = $property->revisionNumber;

        $replacingProperties = propertiesdatadictionaries::where('GUID', $guid)
            ->where(function ($q) use ($versionNumber, $revisionNumber) {
                $q->where('versionNumber', '>', $versionNumber)
                    ->orWhere(function ($q2) use ($versionNumber, $revisionNumber) {
                        $q2->where('versionNumber', $versionNumber)->where('revisionNumber', '>', $revisionNumber);
                    });
            })->get();
        $replacingCodes = $replacingProperties->map(fn($p) => $this->propertyCodeFor($p))->toArray();

        $replacedProperties = propertiesdatadictionaries::where('GUID', $guid)
            ->where(function ($q) use ($versionNumber, $revisionNumber) {
                $q->where('versionNumber', '<', $versionNumber)
                    ->orWhere(function ($q2) use ($versionNumber, $revisionNumber) {
                        $q2->where('versionNumber', $versionNumber)->where('revisionNumber', '<', $revisionNumber);
                    });
            })->get();
        $replacedCodes = $replacedProperties->map(fn($p) => $this->propertyCodeFor($p))->toArray();

        $code = $this->propertyCodeFor($property);
        $ownedUri = 'https://pdts.pt/datadictionaryview/' . $property->Id . '-' . self::sanitizePascalCase($property->namePt);

        return [
            'Code'                    => $code,
            'Name'                    => $property->namePt,
            'Definition'              => $property->definitionPt,
            'DataType'                => $this->mapDataType($property->dataType),
            'Units'                   => $property->units ? [$property->units] : [],
            'ActivationDateUtc'       => ($property->dateOfVersion && $property->dateOfVersion !== '0000-00-00') ? $property->dateOfVersion : null,
            'CountryOfOrigin'         => $property->countryOfOrigin,
            'CreatorLanguageIsoCode'  => $property->creatorsLanguage,
            'DeActivationDateUtc'     => ($property->depreciationDate && $property->depreciationDate !== '0000-00-00') ? $property->depreciationDate : null,
            'DeprecationExplanation'  => $property->depreciationExplanation ?: null,
            'DocumentReference'       => $propertyRD !== 'n/a' ? referencedocuments::where('GUID', $propertyRD)->value('rdName') : null,
            'IsDynamic'               => (bool) ($property->dynamicProperty ?? false),
            'OwnedUri'                => $ownedUri,
            'PhysicalQuantity'        => $property->physicalQuantity,
            'ReplacedObjectCodes'     => $replacedCodes,
            'ReplacingObjectCodes'    => $replacingCodes,
            'RevisionDateUtc'         => $property->dateOfRevision ?: null,
            'RevisionNumber'          => (int) $property->revisionNumber,
            'Status'                  => $property->status,
            'TextFormat'              => $property->textFormat ?: null,
            'Uid'                     => $property->GUID,
            'VersionDateUtc'          => ($property->dateOfVersion && $property->dateOfVersion !== '0000-00-00') ? $property->dateOfVersion : null,
            'VersionNumber'           => (int) $property->versionNumber,
            'VisualRepresentationUri' => $ownedUri ?: null,
        ];
    }

    /**
     * Map DB dataType to a bSDD-allowed value.
     * If the stored value is ALREADY a valid bSDD type, keep it as-is.
     */
    private function mapDataType(?string $dataType): string
    {
        if (!$dataType) return 'String';
        $trimmed = trim($dataType);
        if (in_array($trimmed, self::BSDD_DATATYPES, true)) return $trimmed;

        $map = [
            'boolean' => 'Boolean',
            'bool' => 'Boolean',
            'character' => 'Character',
            'char' => 'Character',
            'integer' => 'Integer',
            'int' => 'Integer',
            'number' => 'Integer',
            'real' => 'Real',
            'float' => 'Real',
            'double' => 'Real',
            'decimal' => 'Real',
            'string' => 'String',
            'text' => 'String',
            'varchar' => 'String',
            'time' => 'Time',
            'date' => 'Time',
            'datetime' => 'Time',
        ];
        return $map[strtolower($trimmed)] ?? 'String';
    }

    public function exportDataToJsonPSETS()
    {
        // Latest ACTIVE version of each PDT (max version, then max revision), Active only.
        $productDataTemplates = DB::select("
            SELECT pdt.*
            FROM productdatatemplates pdt
            INNER JOIN (
                SELECT GUID, MAX(versionNumber) AS maxVersion
                FROM productdatatemplates
                WHERE status = 'Active'
                GROUP BY GUID
            ) latest ON pdt.GUID = latest.GUID AND pdt.versionNumber = latest.maxVersion
            INNER JOIN (
                SELECT GUID, versionNumber, MAX(revisionNumber) AS maxRevision
                FROM productdatatemplates
                WHERE status = 'Active'
                GROUP BY GUID, versionNumber
            ) latestRev ON pdt.GUID = latestRev.GUID
                AND pdt.versionNumber = latestRev.versionNumber
                AND pdt.revisionNumber = latestRev.maxRevision
            WHERE pdt.status = 'Active'
        ");

        // Latest ACTIVE version of each dictionary property.
        $propertiesData = DB::select("
            SELECT p.*
            FROM propertiesdatadictionaries p
            INNER JOIN (
                SELECT GUID, MAX(versionNumber) AS maxVersion
                FROM propertiesdatadictionaries
                WHERE status = 'Active'
                GROUP BY GUID
            ) latest ON p.GUID = latest.GUID AND p.versionNumber = latest.maxVersion
            INNER JOIN (
                SELECT GUID, versionNumber, MAX(revisionNumber) AS maxRevision
                FROM propertiesdatadictionaries
                WHERE status = 'Active'
                GROUP BY GUID, versionNumber
            ) latestRev ON p.GUID = latestRev.GUID
                AND p.versionNumber = latestRev.versionNumber
                AND p.revisionNumber = latestRev.maxRevision
            WHERE p.status = 'Active'
        ");

        $jsonData = $this->transformDataPSETS($productDataTemplates, $propertiesData);

        // Guard: fail loudly on any duplicate code BEFORE writing the file.
        $this->assertDistinctCodes($jsonData);

        $tempFilePath = tempnam(sys_get_temp_dir(), 'PDTs.pt_Domain_bsdd_PSETS_');
        file_put_contents($tempFilePath, json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $fileName = 'PDTs.pt_Domain_bsdd_PSETS_' . Carbon::now()->format('Y-m-d') . '.json';

        return response()->streamDownload(
            function () use ($tempFilePath) {
                readfile($tempFilePath);
                unlink($tempFilePath);
            },
            $fileName,
            [
                'Content-Type'        => 'application/json',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            ]
        );
    }

    private function transformDataPSETS($productDataTemplates, $propertiesData)
    {
        $jsonData = [
            'ModelVersion'              => '2.0',
            'OrganizationCode'          => 'pdtspt',
            'DictionaryCode'            => 'pdtspt',
            'LanguageIsoCode'           => 'pt-PT',
            'LanguageOnly'              => false,
            'UseOwnUri'                 => true,
            'DictionaryUri'             => 'https://pdts.pt',
            'DictionaryName'            => 'PDTs.pt',
            'DictionaryVersion'         => '0.1',
            'MoreInfoUrl'               => 'https://pdts.pt',
            'ChangeRequestEmailAddress' => 'pdts.portugal@gmail.com',
            'License'                   => 'CC BY',
            'LicenseUrl'                => 'https://creativecommons.org/share-your-work/cclicenses/',
            'QualityAssuranceProcedure' => 'EN ISO 23386:2020',
            'Classes'                   => [],
            'Properties'                => [],
        ];

        // Disambiguate PDT codes if two PDTs PascalCase to the same string.
        $seenPdtCodes = [];
        $seenGroupGuids = [];

        // Emit ANCESTORS before descendants (parents-before-children) so an inherited
        // GOP class is parented to the ancestor that owns it, not whichever child first
        // references the same-GUID group. Depth = number of IsSubtypeOf ancestors; roots
        // (incl. master, depth 0) sort first. Generalizes the old master-first rule.
        $seeded = $this->pdtSubtypeStoreSeeded();
        $svc = $this->relationshipService();
        $depth = [];
        foreach ($productDataTemplates as $p) {
            $depth[$p->GUID] = $seeded
                ? count($svc->subtypeAncestors(\App\Models\EntityRelationship::TYPE_PDT, $p->GUID))
                : ($p->GUID === self::MASTER_PDT_GUID ? 0 : 1);
        }
        usort($productDataTemplates, fn($a, $b) => ($depth[$a->GUID] ?? 0) <=> ($depth[$b->GUID] ?? 0));

        foreach ($productDataTemplates as $pdt) {
            [$pdtClass, $pdtCode] = $this->transformProductDataTemplatePSETS($pdt, $seenPdtCodes);
            $jsonData['Classes'][] = $pdtClass;

            // Emit each of this PDT's (inherited) groups as its own GroupOfProperties class.
            $groups = $this->resolvePdtGroups($pdt);
            foreach ($groups as $group) {
                $gopGuid = is_array($group) ? ($group['GUID'] ?? null) : ($group->GUID ?? null);
                // One GOP class per group GUID across the whole export.
                if ($gopGuid && isset($seenGroupGuids[$gopGuid])) continue;
                if ($gopGuid) $seenGroupGuids[$gopGuid] = true;

                $jsonData['Classes'][] = $this->transformGroupOfPropertiesPSETS($group, $pdtCode);
            }
        }

        foreach ($propertiesData as $property) {
            $jsonData['Properties'][] = $this->transformPropertyDataDictionary($property);
        }

        return $jsonData;
    }

    /**
     * @return array{0: array, 1: string}  [classData, pdtCode]
     */
    private function transformProductDataTemplatePSETS($pdt, array &$seenPdtCodes)
    {
        $code = self::convertToPascalCase($pdt->pdtNamePt) ?: ('Pdt' . $pdt->Id);
        if (isset($seenPdtCodes[$code])) {
            $code = $code . '-' . $pdt->Id; // collision-safe
        }
        $seenPdtCodes[$code] = true;

        $ownedUri = 'https://pdts.pt/pdtview/' . $pdt->Id . '-' . self::convertToPascalCase($pdt->pdtNamePt);

        $classData = [
            'ClassType'              => 'Class',
            'Code'                   => $code,
            'Name'                   => $pdt->pdtNamePt,
            'Definition'             => $pdt->descriptionPt,
            'OwnedUri'               => $ownedUri,
            'ActivationDateUtc'      => ($pdt->dateOfVersion && $pdt->dateOfVersion !== '0000-00-00') ? $pdt->dateOfVersion : null,
            'DeActivationDateUtc'    => ($pdt->depreciationDate && $pdt->depreciationDate !== '0000-00-00') ? $pdt->depreciationDate : null,
            'DeprecationExplanation' => ($pdt->depreciationDate && $pdt->depreciationDate !== '0000-00-00') ? $pdt->depreciationExplanation : null,
            'DocumentReference'      => ($pdt->referenceDocumentGUID && $pdt->referenceDocumentGUID !== 'n/a')
                ? referencedocuments::where('GUID', $pdt->referenceDocumentGUID)->value('rdName') : null,
            'RevisionDateUtc'        => $pdt->dateOfRevision ?: null,
            'RevisionNumber'         => (int) $pdt->revisionNumber,
            'Status'                 => $pdt->status,
            'Uid'                    => $pdt->GUID,
            'VersionDateUtc'         => ($pdt->dateOfVersion && $pdt->dateOfVersion !== '0000-00-00') ? $pdt->dateOfVersion : null,
            'VersionNumber'          => (int) $pdt->versionNumber,
            'ClassProperties'        => [],
        ];

        // Attach every property (deduped by dict GUID within this class) to the PDT,
        // with PropertySet = the group it belongs to. Groups include inherited ones.
        $groups = $this->resolvePdtGroups($pdt);
        $classData['ClassProperties'] = $this->buildClassPropertiesForGroups($groups, $code);

        // Subtype/parent links. Read from the generic relationship store (R-23387-7);
        // IsSubtypeOf -> bSDD IsChildOf. Falls back to the legacy MASTER_PDT_GUID
        // synthesis when the store has not been seeded, so output is unchanged then.
        $relations = $this->buildPdtClassRelations($pdt);
        if (!empty($relations)) {
            $classData['ClassRelations'] = $relations;
        }

        return [$classData, $code];
    }


    // ---------------------------------------------------------------------
    // bSDD PSETS export helpers
    // ---------------------------------------------------------------------

    private const BSDD_DATATYPES = ['Boolean', 'Character', 'Integer', 'Real', 'String', 'Time'];

    /**
     * Canonical property code — the single source of truth.
     * Used for Property.Code AND every ClassProperty.PropertyCode that points
     * at it, so a property reads identically under a PDT and under a GOP.
     */
    private function propertyCodeFor($ddRow): string
    {
        $id     = is_array($ddRow) ? ($ddRow['Id'] ?? null)     : ($ddRow->Id ?? null);
        $namePt = is_array($ddRow) ? ($ddRow['namePt'] ?? null) : ($ddRow->namePt ?? null);
        return $id . '-' . self::sanitizePascalCase($namePt);
    }

    /** GOP class code: "{gopId}-{PascalCaseName}" — gopId guarantees uniqueness. */
    private function gopCodeFor($gopId, $gopNamePt): string
    {
        $nameCode = self::convertToPascalCase($gopNamePt) ?: 'Other';
        return $gopId ? ($gopId . '-' . $nameCode) : $nameCode;
    }

    /**
     * Latest ACTIVE version of a dictionary property by GUID — the row that
     * actually appears in Properties[]. A membership (properties.propertyId) may
     * point at a superseded or InActive version; resolving here keeps every
     * ClassProperty.PropertyCode pointing at a Property that is really exported.
     */
    private function latestActiveDictionaryProperty($guid)
    {
        return propertiesdatadictionaries::where('GUID', $guid)
            ->where('status', 'Active')
            ->orderByRaw('versionNumber DESC, revisionNumber DESC')
            ->first();
    }

    /**
     * Latest version of the master PDT (or null if none).
     *
     * Deliberate divergence from Iso23387Exporter::getLatestPdt(), which does NOT
     * filter by status: this bSDD export is Active-only everywhere (the master
     * class is emitted from the Active productDataTemplates query), so the master
     * used for the inline merge + IsChildOf relation must be the SAME row that is
     * emitted as a class — i.e. the latest ACTIVE master. Otherwise a Preview/
     * InActive latest master would be merged/referenced but never emitted,
     * breaking the IsChildOf target.
     *
     * Distinct from loadLatestMasterPdt() (used by the non-PSETS transformData
     * export), which intentionally does NOT filter by status.
     */
    private function loadLatestActiveMasterPdt()
    {
        return DB::table('productdatatemplates')
            ->where('GUID', self::MASTER_PDT_GUID)
            ->where('status', 'Active')
            ->orderByRaw('versionNumber DESC, revisionNumber DESC')
            ->first();
    }

    /**
     * Merge the master PDT's groups into a child PDT's group list.
     *
     * Mirrors the inline merge in Iso23387Exporter::buildLibraryStructure():
     *   $childGroups->merge($masterGroups)->unique('Id')->values()
     * i.e. child groups first, master appended, first occurrence per Id wins
     * (child wins on conflict). Dedup key is Id (the specific-row identity), NOT
     * GUID: in our snapshot-clone versioning model multiple GOP rows can share a
     * lineage GUID, so deduping by GUID could wrongly collapse two genuinely
     * different master groups.
     *
     * Distinct from mergeMasterGroupsIntoPdtGroups() (used by the non-PSETS
     * transformData export), which merges by GUID and emits mergedGroupIds.
     */
    private function mergeMasterGroupsForPsets(array $pdtGroups, array $masterGroups, $pdtId): array
    {
        $merged = [];
        $seenIds = [];
        foreach (array_merge($pdtGroups, $masterGroups) as $group) {
            $id = is_array($group) ? ($group['Id'] ?? null) : ($group->Id ?? null);
            if ($id !== null && isset($seenIds[$id])) continue;
            if ($id !== null) $seenIds[$id] = true;
            $merged[] = $group;
        }
        return $merged;
    }

    private ?\App\Services\RelationshipService $relSvc = null;
    private function relationshipService(): \App\Services\RelationshipService
    {
        return $this->relSvc ??= new \App\Services\RelationshipService();
    }

    /**
     * Resolve a PDT's effective group list = its own groups + the groups inherited
     * from every IsSubtypeOf ancestor (latest active), collapsed by GOP GUID lineage
     * (nearest/self wins). This is the general R-23387-7 inheritance rule; the master
     * is now just the first ancestor on the chain (seeded as an IsSubtypeOf edge).
     *
     * Collapse is BY GUID ONLY — never by name. Two same-named groups with different
     * GUIDs are different groups and both remain (see the GOP name-collision report).
     *
     * Legacy fallback: when the relationship store has no PDT IsSubtypeOf edge, falls
     * back to the original single-master merge so behaviour is unchanged pre-seed.
     */
    private function resolvePdtGroups($pdt)
    {
        $selfGroups = groupofproperties::where('pdtId', $pdt->Id)->get()->toArray();

        if (!$this->pdtSubtypeStoreSeeded()) {
            $master = $this->loadLatestActiveMasterPdt();
            if ($master && $pdt->GUID !== self::MASTER_PDT_GUID) {
                $masterGroups = groupofproperties::where('pdtId', $master->Id)->get()->toArray();
                return $this->mergeMasterGroupsForPsets($selfGroups, $masterGroups, $pdt->Id);
            }
            return $selfGroups;
        }

        $merged = [];
        $seenGuids = [];
        $append = function ($groups) use (&$merged, &$seenGuids) {
            foreach ($groups as $g) {
                $guid = is_array($g) ? ($g['GUID'] ?? null) : ($g->GUID ?? null);
                if ($guid !== null && isset($seenGuids[$guid])) continue; // collapse by GUID lineage
                if ($guid !== null) $seenGuids[$guid] = true;
                $merged[] = $g;
            }
        };
        $append($selfGroups);

        foreach ($this->relationshipService()->subtypeAncestors(\App\Models\EntityRelationship::TYPE_PDT, $pdt->GUID) as $aGuid) {
            $aPdt = DB::table('productdatatemplates')
                ->where('GUID', $aGuid)->where('status', 'Active')
                ->orderByRaw('versionNumber DESC, revisionNumber DESC')->first();
            if (!$aPdt) continue;
            $append(groupofproperties::where('pdtId', $aPdt->Id)->get()->toArray());
        }
        return $merged;
    }

    /**
     * Resolve the master PDT's emitted Class Code + URI, so children can
     * reference it via an IsChildOf relation.
     *
     * @return array{0: ?string, 1: ?string, 2?: object}
     */
    private function masterClassCodeAndUri(): array
    {
        $master = $this->loadLatestActiveMasterPdt();
        if (!$master) return [null, null];

        $code = self::convertToPascalCase($master->pdtNamePt) ?: ('Pdt' . $master->Id);
        $uri  = 'https://pdts.pt/pdtview/' . $master->Id . '-' . self::convertToPascalCase($master->pdtNamePt);
        return [$code, $uri, $master];
    }

    /**
     * Build the bSDD ClassRelations for a PDT from the generic relationship store
     * (EN ISO 23387:2025 R-23387-7). PDT-level IsSubtypeOf edges map to bSDD IsChildOf.
     *
     * If the store has NOT been seeded with any PDT subtype edge, falls back to the
     * legacy MASTER_PDT_GUID synthesis so the export is byte-identical pre-seed.
     * Returns [] when the PDT has no parent (e.g. the master itself).
     */
    private function buildPdtClassRelations($pdt): array
    {
        if (!$this->pdtSubtypeStoreSeeded()) {
            return $this->legacyMasterRelation($pdt);
        }

        $rels = \App\Models\EntityRelationship::where('sourceEntityType', \App\Models\EntityRelationship::TYPE_PDT)
            ->where('sourceGuid', $pdt->GUID)
            ->whereIn('relationType', [\App\Models\EntityRelationship::REL_IS_SUBTYPE_OF, \App\Models\EntityRelationship::REL_HAS_PART])
            ->where('targetEntityType', \App\Models\EntityRelationship::TYPE_PDT)
            ->orderByRaw("FIELD(relationType,'IsSubtypeOf') DESC")
            ->orderByRaw('position IS NULL, position')
            ->get();

        $out = [];
        foreach ($rels as $r) {
            [$code, $uri] = $this->resolvePdtClassRef($r->targetGuid, $r->targetVersionNumber, $r->targetRevisionNumber);
            if (!$code || !$uri) continue;
            // IsSubtypeOf -> bSDD IsChildOf; HasPart -> bSDD HasPart.
            $bsddType = $r->relationType === \App\Models\EntityRelationship::REL_HAS_PART ? 'HasPart' : 'IsChildOf';
            // Preserve the legacy OwnedUri spelling for the master IsChildOf target.
            if ($bsddType === 'IsChildOf' && $r->targetGuid === self::MASTER_PDT_GUID) {
                $suffix = 'ischildof-master';
            } else {
                $suffix = strtolower($bsddType) . '-' . $r->targetGuid;
            }
            $out[] = [
                'RelationType'     => $bsddType,
                'RelatedClassUri'  => $uri,   // UseOwnUri=true, so our own URI
                'RelatedClassName' => $code,
                'OwnedUri'         => 'https://pdts.pt/classrelation/' . $pdt->Id . '-' . $suffix,
            ];
        }
        return $out;
    }

    /**
     * GOP-level ClassRelations from the store (R-23387-7): IsSubtypeOf -> IsChildOf,
     * HasPart -> HasPart, both targeting other GOP lineages. Returns [] when none.
     */
    private function buildGopClassRelations($group): array
    {
        $guid = is_array($group) ? ($group['GUID'] ?? null) : ($group->GUID ?? null);
        $gopId = is_array($group) ? ($group['Id'] ?? null) : ($group->Id ?? null);
        if (!$guid) return [];

        $rels = \App\Models\EntityRelationship::where('sourceEntityType', \App\Models\EntityRelationship::TYPE_GOP)
            ->where('sourceGuid', $guid)
            ->whereIn('relationType', [\App\Models\EntityRelationship::REL_IS_SUBTYPE_OF, \App\Models\EntityRelationship::REL_HAS_PART])
            ->where('targetEntityType', \App\Models\EntityRelationship::TYPE_GOP)
            ->orderByRaw("FIELD(relationType,'IsSubtypeOf') DESC")
            ->orderByRaw('position IS NULL, position')
            ->get();

        $out = [];
        foreach ($rels as $r) {
            [$code, $uri] = $this->resolveGopClassRef($r->targetGuid);
            if (!$code || !$uri) continue;
            $bsddType = $r->relationType === \App\Models\EntityRelationship::REL_HAS_PART ? 'HasPart' : 'IsChildOf';
            $out[] = [
                'RelationType'     => $bsddType,
                'RelatedClassUri'  => $uri,
                'RelatedClassName' => $code,
                'OwnedUri'         => 'https://pdts.pt/classrelation/gop-' . $gopId . '-' . strtolower($bsddType) . '-' . $r->targetGuid,
            ];
        }
        return $out;
    }

    /** Resolve a GOP lineage GUID to its emitted [classCode, ownedUri] (latest active). */
    private function resolveGopClassRef(string $guid): array
    {
        $g = DB::table('groupofproperties')->where('GUID', $guid)
            ->orderByRaw("FIELD(status,'Active') DESC")
            ->orderByRaw('versionNumber DESC, revisionNumber DESC')
            ->first();
        if (!$g) return [null, null];
        $code = $this->gopCodeFor($g->Id, $g->gopNamePt);
        return [$code, 'https://pdts.pt/datadictionaryviewGOP/' . $code];
    }

    /** True once any PDT IsSubtypeOf edge exists (store is authoritative). Memoized per request. */
    private function pdtSubtypeStoreSeeded(): bool
    {
        if ($this->pdtSubtypeSeeded === null) {
            $this->pdtSubtypeSeeded = \App\Models\EntityRelationship::where('sourceEntityType', \App\Models\EntityRelationship::TYPE_PDT)
                ->where('relationType', \App\Models\EntityRelationship::REL_IS_SUBTYPE_OF)
                ->where('targetEntityType', \App\Models\EntityRelationship::TYPE_PDT)
                ->exists();
        }
        return $this->pdtSubtypeSeeded;
    }

    /** Legacy synthesis: non-master PDT -> master class, identical to the pre-store behaviour. */
    private function legacyMasterRelation($pdt): array
    {
        if ($pdt->GUID === self::MASTER_PDT_GUID) return [];
        [$masterCode, $masterUri] = $this->masterClassCodeAndUri();
        if (!$masterCode || !$masterUri) return [];
        return [[
            'RelationType'     => 'IsChildOf',
            'RelatedClassUri'  => $masterUri,
            'RelatedClassName' => $masterCode,
            'OwnedUri'         => 'https://pdts.pt/classrelation/' . $pdt->Id . '-ischildof-master',
        ]];
    }

    /**
     * Resolve a target PDT lineage GUID (+optional version pin) to its emitted
     * [classCode, ownedUri]. NULL pin -> latest active of the lineage.
     */
    private function resolvePdtClassRef(string $guid, ?int $ver, ?int $rev): array
    {
        $q = DB::table('productdatatemplates')->where('GUID', $guid);
        if ($ver !== null) {
            $q->where('versionNumber', $ver);
            if ($rev !== null) $q->where('revisionNumber', $rev);
        } else {
            $q->where('status', 'Active');
        }
        $t = $q->orderByRaw('versionNumber DESC, revisionNumber DESC')->first();
        if (!$t) return [null, null];

        $code = self::convertToPascalCase($t->pdtNamePt) ?: ('Pdt' . $t->Id);
        $uri  = 'https://pdts.pt/pdtview/' . $t->Id . '-' . self::convertToPascalCase($t->pdtNamePt);
        return [$code, $uri];
    }

    private function transformGroupOfPropertiesPSETS($group, string $pdtCode): array
    {
        $get = function ($key) use ($group) {
            return is_array($group) ? ($group[$key] ?? null) : ($group->$key ?? null);
        };

        $gopId     = $get('Id');
        $gopGuid   = $get('GUID');
        $gopNamePt = $get('gopNamePt');
        $groupCode = $this->gopCodeFor($gopId, $gopNamePt);

        $classData = [
            'ClassType'         => 'GroupOfProperties',
            'Code'              => $groupCode,
            'Name'              => $gopNamePt,
            'Definition'        => $get('definitionPt'),
            'Status'            => $get('status'),
            'OwnedUri'          => 'https://pdts.pt/datadictionaryviewGOP/' . $groupCode,
            'ActivationDateUtc' => ($get('dateOfVersion') && $get('dateOfVersion') !== '0000-00-00') ? $get('dateOfVersion') : null,
            'Uid'               => $gopGuid,
            'VersionNumber'     => (int) $get('versionNumber'),
            'RevisionNumber'    => (int) $get('revisionNumber'),
            'ParentClassCode'   => $pdtCode,
            'ClassProperties'   => $this->buildClassPropertiesForGroups([$group], $groupCode),
        ];

        // GOP subtype/part relations from the store (R-23387-7); omit key when none
        // so output stays byte-identical to baseline until relations are added.
        $gopRelations = $this->buildGopClassRelations($group);
        if (!empty($gopRelations)) {
            $classData['ClassRelations'] = $gopRelations;
        }

        return $classData;
    }

    /**
     * Build ClassProperty entries for the given group list, deduping by dict GUID
     * within the scope, and disambiguating ClassProperty.Code only on real collision.
     */
    private function buildClassPropertiesForGroups($groups, string $ownerClassCode): array
    {
        // Pass 1: gather rows. Resolve each membership to the LATEST ACTIVE
        // dictionary version (the one actually in Properties[]); skip any whose
        // dictionary property has no Active version — it would dangle in bSDD.
        $rows = [];
        foreach ($groups as $group) {
            $primaryGroupId = is_array($group) ? $group['Id'] : $group->Id;
            $groupNamePt    = is_array($group) ? ($group['gopNamePt'] ?? null) : ($group->gopNamePt ?? null);

            $groupIds = [$primaryGroupId];
            $mergedIds = is_array($group) ? ($group['mergedGroupIds'] ?? []) : ($group->mergedGroupIds ?? []);
            if (!empty($mergedIds)) $groupIds = array_merge($groupIds, (array) $mergedIds);

            $psetCode = $this->gopCodeFor($primaryGroupId, $groupNamePt);

            $placeholders = implode(',', array_fill(0, count($groupIds), '?'));
            $classProperties = DB::select("SELECT * FROM properties WHERE gopID IN ($placeholders)", $groupIds);

            foreach ($classProperties as $property) {
                $ddRow = propertiesdatadictionaries::find($property->propertyId);
                $ddProperty = $ddRow ? $this->latestActiveDictionaryProperty($ddRow->GUID) : null;
                if (!$ddProperty) continue; // no Active dictionary version -> would dangle, skip
                $rows[] = ['property' => $property, 'dd' => $ddProperty, 'guid' => $ddProperty->GUID, 'pset' => $psetCode];
            }
        }

        // Pass 2: emit, skipping dict-GUID duplicates within this scope.
        $seen = [];
        $out = [];
        foreach ($rows as $r) {
            if (isset($seen[$r['guid']])) continue;
            $seen[$r['guid']] = true;
            $out[] = $this->buildClassProperty($r['property'], $r['dd'], $r['pset'], $ownerClassCode);
        }
        return $out;
    }

    private function buildClassProperty($property, $ddProperty, string $psetCode, string $ownerClassCode): array
    {
        $propertyCode = $this->propertyCodeFor($ddProperty);

        // ClassProperty.Code: unique within the class via properties.Id.
        $classPropertyCode = (string) $property->Id . '-' . $propertyCode;

        $nameSegment = self::sanitizePascalCase(
            is_array($ddProperty) ? ($ddProperty['namePt'] ?? null) : $ddProperty->namePt
        );

        // OwnedUri must be GLOBALLY unique. A property is listed both on its PDT
        // class and on its GOP class (and master properties repeat across
        // children), so scope the URI to the owning class code.
        $ownedUri = 'https://pdts.pt/classpropertyview/' . $ownerClassCode . '/' . $property->Id . '-' . $nameSegment;

        return [
            'Code'         => $classPropertyCode,
            'PropertyCode' => $propertyCode,
            'Description'  => $property->descriptionPt ?: null,
            'PropertySet'  => $psetCode,
            'OwnedUri'     => $ownedUri,
        ];
    }

    /**
     * Fail loudly if any duplicate Code would be sent to bSDD.
     * - Property.Code unique across Properties[]
     * - Class.Code unique across Classes[]
     * - ClassProperty.Code unique within each class
     */
    private function assertDistinctCodes(array $jsonData): void
    {
        $dups = [];

        $propCodes = array_map(fn($p) => $p['Code'], $jsonData['Properties']);
        foreach (array_count_values($propCodes) as $c => $n) {
            if ($n > 1) $dups[] = "Property.Code '$c' x$n";
        }

        $classCodes = array_map(fn($c) => $c['Code'], $jsonData['Classes']);
        foreach (array_count_values($classCodes) as $c => $n) {
            if ($n > 1) $dups[] = "Class.Code '$c' x$n";
        }

        foreach ($jsonData['Classes'] as $class) {
            $cpCodes = array_map(fn($cp) => $cp['Code'], $class['ClassProperties'] ?? []);
            foreach (array_count_values($cpCodes) as $c => $n) {
                if ($n > 1) $dups[] = "ClassProperty.Code '$c' x$n in class '{$class['Code']}'";
            }
        }

        // OwnedUri must be globally unique across everything bSDD ingests.
        $ownedUris = [];
        foreach ($jsonData['Classes'] as $class) {
            if (!empty($class['OwnedUri'])) $ownedUris[] = $class['OwnedUri'];
            foreach ($class['ClassProperties'] ?? [] as $cp) {
                if (!empty($cp['OwnedUri'])) $ownedUris[] = $cp['OwnedUri'];
            }
        }
        foreach ($jsonData['Properties'] as $p) {
            if (!empty($p['OwnedUri'])) $ownedUris[] = $p['OwnedUri'];
        }
        foreach (array_count_values($ownedUris) as $u => $n) {
            if ($n > 1) $dups[] = "OwnedUri '$u' x$n";
        }

        if (!empty($dups)) {
            throw new \RuntimeException(
                "bSDD export aborted — duplicate codes (bSDD rejects these):\n - " . implode("\n - ", $dups)
            );
        }
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
