<?php

namespace App\Services;

use App\Models\productdatatemplates;
use App\Models\groupofproperties;
use App\Models\propertiesdatadictionaries;
use App\Models\referencedocuments;
use App\Models\constructionobjects;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use DOMDocument;
use DOMElement;

/**
 * STRICT EN ISO 23387 Serializer - FULLY COMPLIANT VERSION
 * 
 * FIXES:
 * 1. ConstructionObject/ObjectType now properly mapped with correct field names
 * 2. Element ordering in DataTemplate XML corrected (HasObjectTypeRef, HasPropertyRef, HasGroupOfPropertiesRef)
 * 3. ObjectType element now includes all required fields (ReferenceDocumentRef, LanguageOfCreator, etc.)
 * 4. All properties from PDT and Master are referenced in DataTemplate
 * 5. All groups of properties are referenced in DataTemplate
 */
class Iso23387Exporter
{
    private const NAMESPACE_URI = 'https://standards.iso.org/iso/23387/ed-2/en/';
    private const NAMESPACE_PREFIX = 'dt';
    private const MASTER_PDT_GUID = '230d9954097541b793f2a1fddb8bd0ad';

    /**
     * Export PDT to JSON (structurally identical to XML)
     */
    public function exportToJson($pdtId): string
    {
        $structure = $this->buildLibraryStructure($pdtId);
        return json_encode($structure, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Export PDT to XML (EN ISO 23387 compliant, strictly schema-aligned)
     */
    public function exportToXml($pdtId): string
    {
        $structure = $this->buildLibraryStructure($pdtId);
        return $this->structureToXml($structure);
    }

    /**
     * Get the LATEST version of a PDT by GUID or ID
     */
    public function getLatestPdt($pdtIdOrGuid)
    {
        // Check if it's a GUID or ID
        if (strlen($pdtIdOrGuid) > 5) {
            // Likely a GUID
            return DB::table('productdatatemplates')
                ->where('GUID', $pdtIdOrGuid)
                ->orderByRaw('versionNumber DESC, revisionNumber DESC, editionNumber DESC')
                ->first();
        } else {
            // Likely an ID
            return productdatatemplates::find($pdtIdOrGuid);
        }
    }

    /**
     * Build complete Library structure in proper element order per XSD
     * INCLUDES: Master Data Template properties if not already the Master
     * ObjectType is defined separately at Library level
     */
    private function buildLibraryStructure($pdtId): array
    {
        // Get latest version of the PDT
        $pdt = $this->getLatestPdt($pdtId);
        if (!$pdt) {
            throw new \Exception("PDT not found: {$pdtId}");
        }

        $groupsOfProperties = $this->loadGroupsOfProperties($pdt->Id);
        $properties = $this->loadPropertiesByPdt($pdt->Id);

        // Include Master Data Template properties if this is not the Master itself
        if ($pdt->GUID !== self::MASTER_PDT_GUID) {
            $masterPdt = $this->getLatestPdt(self::MASTER_PDT_GUID);

            $masterGroupsOfProperties = $this->loadGroupsOfProperties($masterPdt->Id);
            $masterProperties = $this->loadPropertiesByPdt($masterPdt->Id);
            // Merge but keep unique GOPs/Properties based on ID
            $groupsOfProperties = $groupsOfProperties
                ->merge($masterGroupsOfProperties)
                ->unique('Id')
                ->values();

            $properties = $properties
                ->merge($masterProperties)
                ->unique('Id')
                ->values();
        }

        $referenceDocuments = $this->loadReferenceDocuments($pdtId);

        // Load ObjectType element separately if it exists
        $objectType = null;
        if ($pdt->constructionObjectGUID) {
            $objectType = $this->loadObjectType($pdt->constructionObjectGUID);
        }

        $library = [
            'dt:GUID' => $pdt->GUID,
            'dateOfCreation' => $this->formatDate($pdt->dateOfVersion ?? $pdt->dateOfRevision),
            'Name' => $this->buildMultilingualNames($pdt->pdtNameEn, $pdt->pdtNamePt),
            'Definition' => $this->buildMultilingualDefinitions($pdt->descriptionEn, $pdt->descriptionPt),
            'URI' => 'https://pdts.pt/pdtview/' . $pdt->Id . '-' . $pdt->GUID
        ];

        $library['DataTemplates'] = [$this->buildDataTemplate($pdt, $groupsOfProperties, $properties)];

        // ADD: ObjectType as separate Library element (not nested within DataTemplate)
        if ($objectType) {
            $library['ObjectType'] = $this->buildObjectTypeElement($objectType);
        }

        $library['GroupOfProperties'] = $this->buildGroupsOfPropertiesElements($groupsOfProperties);
        $library['Properties'] = $this->buildPropertiesElements($properties);
        $library['ReferenceDocuments'] = $this->buildReferenceDocumentsElements($referenceDocuments);

        return [
            'Library' => $library
        ];
    }

    /**
     * Build DataTemplate element with STRICT element ordering from XSD
     * INCLUDES properties and groups from Master Data Template
     * Parameters now include merged $properties and $groupsOfProperties collections
     */
    private function buildDataTemplate($pdt, $groupsOfProperties, $properties): array
    {
        $template = [];

        // REQUIRED: Name (1..*)
        $template['Name'] = $this->buildMultilingualNames($pdt->pdtNameEn, $pdt->pdtNamePt);

        // REQUIRED: Definition (1..1)
        $template['Definition'] = $this->buildMultilingualDefinitions($pdt->descriptionEn, $pdt->descriptionPt);

        // OPTIONAL: ReferenceDocumentRef (0..*)
        if ($pdt->referenceDocumentGUID && $pdt->referenceDocumentGUID !== 'n/a') {
            $template['ReferenceDocumentRef'] = ['dt:GUID' => $pdt->referenceDocumentGUID];
        }

        // OPTIONAL: LanguageOfCreator (0..1)
        if ($pdt->creatorsLanguage ?? false) {
            $template['LanguageOfCreator'] = $pdt->creatorsLanguage;
        }

        // OPTIONAL: CountryOfOrigin (0..1)
        if ($pdt->countryOfOrigin ?? false) {
            $template['CountryOfOrigin'] = $pdt->countryOfOrigin;
        }

        // OPTIONAL: MajorVersion (0..1)
        if ($pdt->editionNumber) {
            $template['MajorVersion'] = (int)$pdt->editionNumber;
        }

        // OPTIONAL: MinorVersion (0..1)
        if ($pdt->versionNumber) {
            $template['MinorVersion'] = (int)$pdt->versionNumber;
        }

        // OPTIONAL: Status (0..*)
        if ($pdt->status) {
            $template['Status'] = $pdt->status;
        }

        // OPTIONAL: DeprecationExplanation (0..*)
        if ($pdt->depreciationExplanation ?? false) {
            $template['DeprecationExplanation'] = $pdt->depreciationExplanation;
        }

        // FIX #1: ADD HasObjectTypeRef FIRST (before properties and groups)
        // Per XSD lines 170-172, order is: HasObjectTypeRef, HasPropertyRef, HasGroupOfPropertiesRef
        if ($pdt->constructionObjectGUID) {
            $template['HasObjectTypeRef'] = ['dt:GUID' => $pdt->constructionObjectGUID];
        }

        // FIX #2: HasPropertyRef - ALL properties from PDT + Master
        // INCLUDES properties from current PDT + Master PDT (via merged collection)
        $propGuids = $this->buildPropertyRefsFromCollection($properties);
        if (!empty($propGuids)) {
            $template['HasPropertyRef'] = $propGuids;
        }

        // FIX #3: HasGroupOfPropertiesRef - ALL groups from PDT + Master
        // INCLUDES groups from Master PDT as well (via merged collection)
        $gopGuids = $this->buildGroupOfPropertiesRefsFromCollection($groupsOfProperties);
        if (!empty($gopGuids)) {
            $template['HasGroupOfPropertiesRef'] = $gopGuids;
        }

        // Attributes
        $template['dt:GUID'] = $pdt->GUID;
        $template['dateOfCreation'] = $this->formatDate($pdt->dateOfVersion ?? $pdt->dateOfRevision);
        $template['URI'] = 'https://pdts.pt/pdtview/' . $pdt->Id . '-' . $pdt->GUID;

        return $template;
    }

    /**
     * Build GroupOfProperties elements
     */
    private function buildGroupsOfPropertiesElements($groupsOfProperties): array
    {
        return $groupsOfProperties->map(function ($gop) {
            return $this->buildGroupOfPropertiesElement($gop);
        })->values()->toArray();
    }

    /**
     * Build single GroupOfProperties element with STRICT element ordering
     */
    private function buildGroupOfPropertiesElement($gop): array
    {
        $element = [];

        // REQUIRED: Name (1..*)
        $element['Name'] = $this->buildMultilingualNames($gop->gopNameEn, $gop->gopNamePt);

        // REQUIRED: Definition (1..1)
        $element['Definition'] = $this->buildMultilingualDefinitions($gop->definitionEn, $gop->definitionPt);

        // OPTIONAL: ReferenceDocumentRef (0..*)
        if ($gop->referenceDocumentGUID && $gop->referenceDocumentGUID !== 'n/a') {
            $element['ReferenceDocumentRef'] = ['dt:GUID' => $gop->referenceDocumentGUID];
        }

        // OPTIONAL: LanguageOfCreator (0..1)
        if ($gop->creatorsLanguage) {
            $element['LanguageOfCreator'] = $gop->creatorsLanguage;
        }

        // OPTIONAL: CountryOfOrigin (0..1)
        if ($gop->countryOfOrigin) {
            $element['CountryOfOrigin'] = $gop->countryOfOrigin;
        }

        // OPTIONAL: MajorVersion (0..1)
        if ($gop->versionNumber) {
            $element['MajorVersion'] = (int)$gop->versionNumber;
        }

        // OPTIONAL: MinorVersion (0..1)
        if ($gop->revisionNumber) {
            $element['MinorVersion'] = (int)$gop->revisionNumber;
        }

        // OPTIONAL: Status (0..*)
        if ($gop->status) {
            $element['Status'] = $gop->status;
        }

        // REQUIRED for GroupOfProperties: HasPropertyRef (1..*)
        $propGuids = $this->getPropertyGuidsForGop($gop->Id);
        if (!empty($propGuids)) {
            $element['HasPropertyRef'] = $propGuids;
        }

        // Attributes
        $element['dt:GUID'] = $gop->GUID;
        $element['dateOfCreation'] = $this->formatDate($gop->dateOfVersion);
        $element['URI'] = 'https://pdts.pt/datadictionaryviewGOP/' . $gop->Id . '-' . $gop->GUID;

        return $element;
    }

    /**
     * Build Property elements
     */
    private function buildPropertiesElements($properties): array
    {
        return $properties->map(function ($prop) {
            return $this->buildPropertyElement($prop);
        })->values()->toArray();
    }

    /**
     * Build single Property element with STRICT element ordering
     */
    public function buildPropertyElement($prop): array
    {
        $element = [];

        // REQUIRED: Name (1..*)
        $element['Name'] = $this->buildMultilingualNames($prop->nameEn, $prop->namePt);

        // REQUIRED: Definition (1..1)
        $element['Definition'] = $this->buildMultilingualDefinitions($prop->definitionEn, $prop->definitionPt);

        // ReferenceDocumentRef (0..*)
        if (isset($prop->referenceDocumentGUID) && $prop->referenceDocumentGUID && $prop->referenceDocumentGUID !== 'n/a') {
            $element['ReferenceDocumentRef'] = ['dt:GUID' => $prop->referenceDocumentGUID];
        }

        // OPTIONAL: LanguageOfCreator (0..1)
        if ($prop->creatorsLanguage) {
            $element['LanguageOfCreator'] = $prop->creatorsLanguage;
        }

        // OPTIONAL: CountryOfOrigin (0..1)
        if ($prop->countryOfOrigin) {
            $element['CountryOfOrigin'] = $prop->countryOfOrigin;
        }

        // OPTIONAL: MajorVersion (0..1)
        if ($prop->versionNumber) {
            $element['MajorVersion'] = (int)$prop->versionNumber;
        }

        // OPTIONAL: MinorVersion (0..1)
        if ($prop->revisionNumber) {
            $element['MinorVersion'] = (int)$prop->revisionNumber;
        }

        // OPTIONAL: Status (0..*)
        if ($prop->status) {
            $element['Status'] = $prop->status;
        }

        // REQUIRED: DataType (1..1) - XSD mandates this element
        $dataTypeName = $prop->dataType && $prop->dataType !== '' ? $prop->dataType : 'STRING';
        $element['DataType'] = [
            'name' => $dataTypeName
        ];

        // OPTIONAL: DimensionRef (0..1)
        if ($prop->dimension && $prop->dimension !== '') {
            $element['Dimension'] = $prop->dimension;
        }

        // OPTIONAL: Units - only if it exists
        if ($prop->units && $prop->units !== '') {
            $element['Units'] = $prop->units;
        }

        // Attributes
        $element['dt:GUID'] = $prop->GUID;
        $element['dateOfCreation'] = $this->formatDate($prop->dateOfVersion);
        $element['referenceURI'] = 'https://pdts.pt/datadictionaryview/' . $prop->Id . '-' . $prop->GUID;

        return $element;
    }

    /**
     * Build ReferenceDocument elements
     */
    private function buildReferenceDocumentsElements($referenceDocuments): array
    {
        return $referenceDocuments->map(function ($refDoc) {
            return $this->buildReferenceDocumentElement($refDoc);
        })->toArray();
    }

    /**
     * Build single ReferenceDocument element with STRICT element ordering
     * REQUIRED: Name, Definition, Language
     */
    private function buildReferenceDocumentElement($refDoc): array
    {
        $element = [];

        // REQUIRED: Name (1..*)
        $element['Name'] = [['language' => 'en', 'value' => $refDoc->rdName]];

        // REQUIRED: Definition (1..1)
        $definition = $refDoc->description ?: ($refDoc->title ?: 'Referenced document');
        $element['Definition'] = [['language' => 'en', 'value' => $definition]];

        // OPTIONAL: Status (0..*)
        if ($refDoc->status) {
            $element['Status'] = $refDoc->status;
        }
        // OPTIONAL: URI
        $element['URI'] = 'https://pdts.pt/referencedocumentview/' . $refDoc->GUID;

        // REQUIRED: Language (1..*) - XSD mandates at least one language
        $element['Language'] = 'en';

        // Attributes
        $element['dt:GUID'] = $refDoc->GUID;
        $element['dateOfCreation'] = $this->formatDate($refDoc->created_at);

        return $element;
    }

    /**
     * Load groups of properties for a PDT by ID only
     * Get all GOPs directly linked to this PDT Id
     */
    private function loadGroupsOfProperties($pdtId)
    {
        return groupofproperties::where('pdtId', $pdtId)->get();
    }

    /**
     * Load Master Data Template groups of properties by ID only
     * 1. Get Master PDT by GUID constant → returns its ID
     * 2. Get all GOPs linked to that Master PDT ID
     */
    private function loadMasterGroupsOfProperties()
    {
        // Get Master PDT ID
        $masterPdt = DB::table('productdatatemplates')
            ->where('GUID', self::MASTER_PDT_GUID)
            ->first();

        if (!$masterPdt) {
            return collect();
        }

        // Get all GOPs linked to Master PDT ID
        return groupofproperties::where('pdtId', $masterPdt->Id)->get();
    }

    /**
     * Load properties for a PDT by ID only
     * Get all properties directly linked to this PDT ID
     */
    private function loadPropertiesByPdt($pdtId)
    {
        return DB::table('properties as p')
            ->join('propertiesdatadictionaries as pdd', 'p.propertyId', '=', 'pdd.Id')
            ->where('p.pdtID', $pdtId)
            ->select(
                'p.Id as pivotId',
                'p.propertyId',
                'p.gopId',
                'p.pdtID',
                'p.referenceDocumentGUID',
                'pdd.*'
            )
            ->get();
    }

    /**
     * Load Master PDT properties by ID only
     * 1. Get Master PDT ID
     * 2. Get all properties linked to that Master PDT ID
     */
    private function loadMasterProperties()
    {
        $masterPdt = DB::table('productdatatemplates')
            ->where('GUID', self::MASTER_PDT_GUID)
            ->first();

        if (!$masterPdt) return collect();

        return DB::table('properties as p')
            ->join('propertiesdatadictionaries as pdd', 'p.propertyId', '=', 'pdd.Id')
            ->where('p.pdtID', $masterPdt->Id)
            ->select(
                'p.Id as pivotId',
                'p.propertyId',
                'p.gopId',
                'p.pdtID',
                'p.referenceDocumentGUID',
                'pdd.*'
            )
            ->get();
    }


    private function loadReferenceDocuments($pdtId)
    {
        $guids = collect();

        // 1. PDT reference document
        $pdt = DB::table('productdatatemplates')
            ->where('Id', $pdtId)
            ->first();

        if ($pdt && !empty($pdt->referenceDocumentGUID) && $pdt->referenceDocumentGUID !== 'n/a') {
            $guids->push($pdt->referenceDocumentGUID);
        }

        // 2. GOP reference documents
        $gopGuids = DB::table('groupofproperties')
            ->where('pdtId', $pdtId)
            ->whereNotNull('referenceDocumentGUID')
            ->where('referenceDocumentGUID', '!=', 'n/a')
            ->pluck('referenceDocumentGUID');

        $guids = $guids->merge($gopGuids);

        // 3. Property reference documents
        $propGuids = DB::table('properties')
            ->where('pdtID', $pdtId)
            ->whereNotNull('referenceDocumentGUID')
            ->where('referenceDocumentGUID', '!=', 'n/a')
            ->pluck('referenceDocumentGUID');

        $guids = $guids->merge($propGuids);

        // 4. MASTER PDT
        $masterPdt = DB::table('productdatatemplates')
            ->where('GUID', self::MASTER_PDT_GUID)
            ->orderByDesc('editionNumber')
            ->orderByDesc('versionNumber')
            ->orderByDesc('revisionNumber')
            ->first();

        if ($masterPdt) {

            // GOPs
            $masterGopGuids = DB::table('groupofproperties')
                ->where('pdtId', $masterPdt->Id)
                ->whereNotNull('referenceDocumentGUID')
                ->where('referenceDocumentGUID', '!=', 'n/a')
                ->pluck('referenceDocumentGUID');

            $guids = $guids->merge($masterGopGuids);

            // Properties
            $masterPropGuids = DB::table('properties')
                ->where('pdtID', $masterPdt->Id)
                ->whereNotNull('referenceDocumentGUID')
                ->where('referenceDocumentGUID', '!=', 'n/a')
                ->pluck('referenceDocumentGUID');

            $guids = $guids->merge($masterPropGuids);
        }

        // FINAL: unique
        $guids = $guids->filter()->unique()->values();

        return referencedocuments::whereIn('GUID', $guids)->get();
    }
    /**
     * Get property GUIDs for a PDT with referenceURI
     */
    private function getPropertyGuidsForPdt($pdtId): array
    {
        $properties = DB::table('properties as p')
            ->join('propertiesdatadictionaries as pdd', 'p.GUID', '=', 'pdd.GUID')
            ->where('p.pdtID', $pdtId)
            ->select('p.GUID', 'pdd.Id')
            ->distinct()
            ->get();

        return $properties->map(function ($prop) {
            return [
                'dt:GUID' => $prop->GUID,
                'referenceURI' => 'https://pdts.pt/datadictionaryview/' . $prop->Id . '-' . $prop->GUID
            ];
        })->toArray();
    }

    /**
     * Get property GUIDs for a GroupOfProperties with referenceURI
     */

    private function getPropertyGuidsForGop($gopId): array
    {
        $properties = DB::table('properties as p')
            ->join('propertiesdatadictionaries as pdd', 'p.propertyId', '=', 'pdd.Id')
            ->where('p.gopId', $gopId)
            ->select('pdd.GUID as GUID', 'pdd.Id')
            ->get();

        // Deduplicate by GUID, keep only the first property for each GUID
        return $properties->unique('GUID')->map(function ($prop) {
            return [
                'dt:GUID' => $prop->GUID,
                'referenceURI' => 'https://pdts.pt/datadictionaryview/' . $prop->Id . '-' . $prop->GUID
            ];
        })->values()->toArray();
    }

    /**
     * Get GroupOfProperties GUIDs for a PDT with referenceURI
     */
    private function getGroupOfPropertiesGuids($pdtId): array
    {
        $gops = DB::table('groupofproperties')
            ->where('pdtId', $pdtId)
            ->select('GUID', 'Id')
            ->distinct()
            ->get();

        return $gops->map(function ($gop) {
            return [
                'dt:GUID' => $gop->GUID,
                'referenceURI' => 'https://pdts.pt/datadictionaryviewGOP/' . $gop->Id . '-' . $gop->GUID
            ];
        })->toArray();
    }

    /**
     * Build property references from merged properties collection
     * Extracts GUIDs and IDs from already-loaded properties (including Master properties)
     */
    private function buildPropertyRefsFromCollection($properties): array
    {
        if (!$properties || $properties->isEmpty()) {
            return [];
        }

        return $properties->map(function ($prop) {
            return [
                'dt:GUID' => $prop->GUID,
                'referenceURI' => 'https://pdts.pt/datadictionaryview/' . $prop->Id . '-' . $prop->GUID
            ];
        })->unique('dt:GUID')->values()->toArray();
    }

    /**
     * Build GroupOfProperties references from merged collection
     * Extracts GUIDs and IDs from already-loaded GOPs (including Master GOPs)
     */
    private function buildGroupOfPropertiesRefsFromCollection($groupsOfProperties): array
    {
        if (!$groupsOfProperties || $groupsOfProperties->isEmpty()) {
            return [];
        }

        return $groupsOfProperties->map(function ($gop) {
            return [
                'dt:GUID' => $gop->GUID,
                'referenceURI' => 'https://pdts.pt/datadictionaryviewGOP/' . $gop->Id . '-' . $gop->GUID
            ];
        })->unique('dt:GUID')->values()->toArray();
    }

    /**
     * Build multilingual Name array
     */
    private function buildMultilingualNames($en, $pt): array
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
     * Build multilingual Definition array
     */
    private function buildMultilingualDefinitions($en, $pt): array
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
     * Format date to ISO 8601 (YYYY-MM-DDTHH:MM:SSZ)
     */
    private function formatDate($date): string
    {
        if (!$date) {
            return Carbon::now()->toIso8601String();
        }
        return Carbon::parse($date)->toIso8601String();
    }

    /**
     * FIX #1: Load ObjectType from constructionobjects table with CORRECT field names
     * The table has: constructionObjectNameEn, constructionObjectNamePt, descriptionEn, descriptionPt, etc.
     */
    private function loadObjectType($objectTypeGuid)
    {
        return constructionobjects::where('GUID', $objectTypeGuid)
            ->orderByRaw('versionNumber DESC, revisionNumber DESC')
            ->first();
    }

    /**
     * FIX #2: Build ObjectType element with ALL required fields per ISO 23387
     * Including: ReferenceDocumentRef, LanguageOfCreator, CountryOfOrigin, MajorVersion, MinorVersion, Status
     */
    private function buildObjectTypeElement($objectType): array
    {
        $element = [];

        // REQUIRED: Name (1..*)
        // FIX: Use correct field names from constructionobjects table
        $element['Name'] = $this->buildMultilingualNames(
            $objectType->constructionObjectNameEn ?? $objectType->nameEn ?? null,
            $objectType->constructionObjectNamePt ?? $objectType->namePt ?? null
        );

        // REQUIRED: Definition (1..1)
        // FIX: Use correct field names
        $element['Definition'] = $this->buildMultilingualDefinitions(
            $objectType->descriptionEn ?? null,
            $objectType->descriptionPt ?? null
        );

        // OPTIONAL: ReferenceDocumentRef (0..*)
        if (isset($objectType->referenceDocumentGUID) && $objectType->referenceDocumentGUID && $objectType->referenceDocumentGUID !== 'n/a') {
            $element['ReferenceDocumentRef'] = ['dt:GUID' => $objectType->referenceDocumentGUID];
        } elseif (isset($objectType->referenceDocument) && $objectType->referenceDocument && $objectType->referenceDocument !== 'n/a') {
            $element['ReferenceDocumentRef'] = ['dt:GUID' => $objectType->referenceDocument];
        }

        // OPTIONAL: LanguageOfCreator (0..1)
        if (isset($objectType->creatorsLanguage) && $objectType->creatorsLanguage) {
            $element['LanguageOfCreator'] = $objectType->creatorsLanguage;
        }

        // OPTIONAL: CountryOfOrigin (0..1)
        if (isset($objectType->countryOfOrigin) && $objectType->countryOfOrigin) {
            $element['CountryOfOrigin'] = $objectType->countryOfOrigin;
        }

        // OPTIONAL: MajorVersion (0..1)
        if (isset($objectType->versionNumber) && $objectType->versionNumber) {
            $element['MajorVersion'] = (int)$objectType->versionNumber;
        }

        // OPTIONAL: MinorVersion (0..1)
        if (isset($objectType->revisionNumber) && $objectType->revisionNumber) {
            $element['MinorVersion'] = (int)$objectType->revisionNumber;
        }

        // OPTIONAL: Status (0..*)
        if (isset($objectType->Status) && $objectType->Status) {
            $element['Status'] = $objectType->Status;
        } elseif (isset($objectType->status) && $objectType->status) {
            $element['Status'] = $objectType->status;
        }

        // Attributes
        $element['dt:GUID'] = $objectType->GUID;
        $element['dateOfCreation'] = $this->formatDate($objectType->dateOfVersion ?? $objectType->dateOfRevision ?? $objectType->created_at ?? null);

        return $element;
    }

    /**
     * Convert structure array to XML string with proper namespace and element order
     */
    private function structureToXml($structure): string
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $libData = $structure['Library'];
        $libraryElem = $dom->createElementNS(self::NAMESPACE_URI, self::NAMESPACE_PREFIX . ':Library');
        $libraryElem->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:' . self::NAMESPACE_PREFIX, self::NAMESPACE_URI);

        // Add GUID attribute
        if (isset($libData['dt:GUID'])) {
            $libraryElem->setAttributeNS(self::NAMESPACE_URI, self::NAMESPACE_PREFIX . ':GUID', $libData['dt:GUID']);
        }

        // Add dateOfCreation attribute
        if (isset($libData['dateOfCreation'])) {
            $libraryElem->setAttribute('dateOfCreation', $libData['dateOfCreation']);
        }

        // Add URI attribute
        if (isset($libData['URI'])) {
            $libraryElem->setAttribute('URI', $libData['URI']);
        }

        // Add Name elements
        if (isset($libData['Name'])) {
            foreach ($libData['Name'] as $name) {
                $this->appendTextElement($dom, $libraryElem, 'Name', $name['value'], $name['language']);
            }
        }

        // Add Definition elements
        if (isset($libData['Definition'])) {
            foreach ($libData['Definition'] as $def) {
                $this->appendTextElement($dom, $libraryElem, 'Definition', $def['value'], $def['language']);
            }
        }

        // Add DataTemplates
        if (isset($libData['DataTemplates'])) {
            foreach ($libData['DataTemplates'] as $dt) {
                $libraryElem->appendChild($this->buildXmlDataTemplate($dom, $dt));
            }
        }

        // Add ObjectType element (separate at Library level)
        if (isset($libData['ObjectType'])) {
            $libraryElem->appendChild($this->buildXmlObjectType($dom, $libData['ObjectType']));
        }

        // Add GroupOfProperties
        if (isset($libData['GroupOfProperties'])) {
            foreach ($libData['GroupOfProperties'] as $gop) {
                $libraryElem->appendChild($this->buildXmlGroupOfProperties($dom, $gop));
            }
        }

        // Add Properties
        if (isset($libData['Properties'])) {
            foreach ($libData['Properties'] as $prop) {
                $libraryElem->appendChild($this->buildXmlProperty($dom, $prop));
            }
        }

        // Add ReferenceDocuments
        if (isset($libData['ReferenceDocuments'])) {
            foreach ($libData['ReferenceDocuments'] as $refDoc) {
                $libraryElem->appendChild($this->buildXmlReferenceDocument($dom, $refDoc));
            }
        }

        $dom->appendChild($libraryElem);
        return $dom->saveXML();
    }

    /**
     * FIX #3: Build XML DataTemplate element with CORRECTED element ordering
     * Per XSD: HasObjectTypeRef BEFORE HasPropertyRef and HasGroupOfPropertiesRef
     */
    private function buildXmlDataTemplate($dom, $dtData): DOMElement
    {
        $dt = $dom->createElementNS(self::NAMESPACE_URI, self::NAMESPACE_PREFIX . ':DataTemplate');

        if (isset($dtData['dt:GUID'])) {
            $dt->setAttributeNS(self::NAMESPACE_URI, self::NAMESPACE_PREFIX . ':GUID', $dtData['dt:GUID']);
        }
        if (isset($dtData['dateOfCreation'])) {
            $dt->setAttribute('dateOfCreation', $dtData['dateOfCreation']);
        }
        if (isset($dtData['URI'])) {
            $dt->setAttribute('URI', $dtData['URI']);
        }

        // Name elements
        if (isset($dtData['Name'])) {
            foreach ($dtData['Name'] as $name) {
                $this->appendTextElement($dom, $dt, 'Name', $name['value'], $name['language']);
            }
        }

        // Definition elements
        if (isset($dtData['Definition'])) {
            foreach ($dtData['Definition'] as $def) {
                $this->appendTextElement($dom, $dt, 'Definition', $def['value'], $def['language']);
            }
        }

        // ReferenceDocumentRef
        if (isset($dtData['ReferenceDocumentRef'])) {
            $ref = $dom->createElementNS(self::NAMESPACE_URI, self::NAMESPACE_PREFIX . ':ReferenceDocumentRef');
            $ref->setAttributeNS(self::NAMESPACE_URI, self::NAMESPACE_PREFIX . ':GUID', $dtData['ReferenceDocumentRef']['dt:GUID']);
            $dt->appendChild($ref);
        }

        // LanguageOfCreator
        if (isset($dtData['LanguageOfCreator'])) {
            $this->appendTextElement($dom, $dt, 'LanguageOfCreator', $dtData['LanguageOfCreator']);
        }

        // CountryOfOrigin
        if (isset($dtData['CountryOfOrigin'])) {
            $this->appendTextElement($dom, $dt, 'CountryOfOrigin', $dtData['CountryOfOrigin']);
        }

        // MajorVersion
        if (isset($dtData['MajorVersion'])) {
            $this->appendTextElement($dom, $dt, 'MajorVersion', (string)$dtData['MajorVersion']);
        }

        // MinorVersion
        if (isset($dtData['MinorVersion'])) {
            $this->appendTextElement($dom, $dt, 'MinorVersion', (string)$dtData['MinorVersion']);
        }

        // Status
        if (isset($dtData['Status'])) {
            $this->appendTextElement($dom, $dt, 'Status', $dtData['Status']);
        }

        // DeprecationExplanation
        if (isset($dtData['DeprecationExplanation'])) {
            $this->appendTextElement($dom, $dt, 'DeprecationExplanation', $dtData['DeprecationExplanation']);
        }

        // FIX: CORRECT ORDER - HasObjectTypeRef comes FIRST (per XSD line 170)
        if (isset($dtData['HasObjectTypeRef'])) {
            $ref = $dom->createElementNS(self::NAMESPACE_URI, self::NAMESPACE_PREFIX . ':HasObjectTypeRef');
            $ref->setAttributeNS(self::NAMESPACE_URI, self::NAMESPACE_PREFIX . ':GUID', $dtData['HasObjectTypeRef']['dt:GUID']);
            $dt->appendChild($ref);
        }

        // FIX: HasPropertyRef comes SECOND (per XSD line 171)
        if (isset($dtData['HasPropertyRef'])) {
            foreach ($dtData['HasPropertyRef'] as $ref) {
                $refElem = $dom->createElementNS(self::NAMESPACE_URI, self::NAMESPACE_PREFIX . ':HasPropertyRef');
                $refElem->setAttributeNS(self::NAMESPACE_URI, self::NAMESPACE_PREFIX . ':GUID', $ref['dt:GUID']);
                if (isset($ref['referenceURI'])) {
                    $refElem->setAttribute('referenceURI', $ref['referenceURI']);
                }
                $dt->appendChild($refElem);
            }
        }

        // FIX: HasGroupOfPropertiesRef comes THIRD (per XSD line 172)
        if (isset($dtData['HasGroupOfPropertiesRef'])) {
            foreach ($dtData['HasGroupOfPropertiesRef'] as $ref) {
                $refElem = $dom->createElementNS(self::NAMESPACE_URI, self::NAMESPACE_PREFIX . ':HasGroupOfPropertiesRef');
                $refElem->setAttributeNS(self::NAMESPACE_URI, self::NAMESPACE_PREFIX . ':GUID', $ref['dt:GUID']);
                if (isset($ref['referenceURI'])) {
                    $refElem->setAttribute('referenceURI', $ref['referenceURI']);
                }
                $dt->appendChild($refElem);
            }
        }

        return $dt;
    }

    /**
     * Build XML ObjectType element (defined separately at Library level)
     * NOW WITH FULL ELEMENT SET per ISO 23387
     */
    private function buildXmlObjectType($dom, $objTypeData): DOMElement
    {
        $objType = $dom->createElementNS(self::NAMESPACE_URI, self::NAMESPACE_PREFIX . ':ObjectType');

        if (isset($objTypeData['dt:GUID'])) {
            $objType->setAttributeNS(self::NAMESPACE_URI, self::NAMESPACE_PREFIX . ':GUID', $objTypeData['dt:GUID']);
        }
        if (isset($objTypeData['dateOfCreation'])) {
            $objType->setAttribute('dateOfCreation', $objTypeData['dateOfCreation']);
        }

        // Name elements
        if (isset($objTypeData['Name'])) {
            foreach ($objTypeData['Name'] as $name) {
                $this->appendTextElement($dom, $objType, 'Name', $name['value'], $name['language']);
            }
        }

        // Definition elements
        if (isset($objTypeData['Definition'])) {
            foreach ($objTypeData['Definition'] as $def) {
                $this->appendTextElement($dom, $objType, 'Definition', $def['value'], $def['language']);
            }
        }

        // ReferenceDocumentRef
        if (isset($objTypeData['ReferenceDocumentRef'])) {
            $ref = $dom->createElementNS(self::NAMESPACE_URI, self::NAMESPACE_PREFIX . ':ReferenceDocumentRef');
            $ref->setAttributeNS(self::NAMESPACE_URI, self::NAMESPACE_PREFIX . ':GUID', $objTypeData['ReferenceDocumentRef']['dt:GUID']);
            $objType->appendChild($ref);
        }

        // LanguageOfCreator
        if (isset($objTypeData['LanguageOfCreator'])) {
            $this->appendTextElement($dom, $objType, 'LanguageOfCreator', $objTypeData['LanguageOfCreator']);
        }

        // CountryOfOrigin
        if (isset($objTypeData['CountryOfOrigin'])) {
            $this->appendTextElement($dom, $objType, 'CountryOfOrigin', $objTypeData['CountryOfOrigin']);
        }

        // MajorVersion
        if (isset($objTypeData['MajorVersion'])) {
            $this->appendTextElement($dom, $objType, 'MajorVersion', (string)$objTypeData['MajorVersion']);
        }

        // MinorVersion
        if (isset($objTypeData['MinorVersion'])) {
            $this->appendTextElement($dom, $objType, 'MinorVersion', (string)$objTypeData['MinorVersion']);
        }

        // Status
        if (isset($objTypeData['Status'])) {
            $this->appendTextElement($dom, $objType, 'Status', $objTypeData['Status']);
        }

        return $objType;
    }

    /**
     * Build XML GroupOfProperties element with STRICT element ordering
     */
    private function buildXmlGroupOfProperties($dom, $gopData): DOMElement
    {
        $gop = $dom->createElementNS(self::NAMESPACE_URI, self::NAMESPACE_PREFIX . ':GroupOfProperties');

        if (isset($gopData['dt:GUID'])) {
            $gop->setAttributeNS(self::NAMESPACE_URI, self::NAMESPACE_PREFIX . ':GUID', $gopData['dt:GUID']);
        }
        if (isset($gopData['dateOfCreation'])) {
            $gop->setAttribute('dateOfCreation', $gopData['dateOfCreation']);
        }
        if (isset($gopData['URI'])) {
            $gop->setAttribute('URI', $gopData['URI']);
        }

        // Name elements
        if (isset($gopData['Name'])) {
            foreach ($gopData['Name'] as $name) {
                $this->appendTextElement($dom, $gop, 'Name', $name['value'], $name['language']);
            }
        }

        // Definition elements
        if (isset($gopData['Definition'])) {
            foreach ($gopData['Definition'] as $def) {
                $this->appendTextElement($dom, $gop, 'Definition', $def['value'], $def['language']);
            }
        }

        // ReferenceDocumentRef
        if (isset($gopData['ReferenceDocumentRef'])) {
            $ref = $dom->createElementNS(self::NAMESPACE_URI, self::NAMESPACE_PREFIX . ':ReferenceDocumentRef');
            $ref->setAttributeNS(self::NAMESPACE_URI, self::NAMESPACE_PREFIX . ':GUID', $gopData['ReferenceDocumentRef']['dt:GUID']);
            $gop->appendChild($ref);
        }

        // LanguageOfCreator
        if (isset($gopData['LanguageOfCreator'])) {
            $this->appendTextElement($dom, $gop, 'LanguageOfCreator', $gopData['LanguageOfCreator']);
        }

        // CountryOfOrigin
        if (isset($gopData['CountryOfOrigin'])) {
            $this->appendTextElement($dom, $gop, 'CountryOfOrigin', $gopData['CountryOfOrigin']);
        }

        // MajorVersion
        if (isset($gopData['MajorVersion'])) {
            $this->appendTextElement($dom, $gop, 'MajorVersion', (string)$gopData['MajorVersion']);
        }

        // MinorVersion
        if (isset($gopData['MinorVersion'])) {
            $this->appendTextElement($dom, $gop, 'MinorVersion', (string)$gopData['MinorVersion']);
        }

        // Status
        if (isset($gopData['Status'])) {
            $this->appendTextElement($dom, $gop, 'Status', $gopData['Status']);
        }

        // HasPropertyRef
        if (isset($gopData['HasPropertyRef'])) {
            foreach ($gopData['HasPropertyRef'] as $ref) {
                $refElem = $dom->createElementNS(self::NAMESPACE_URI, self::NAMESPACE_PREFIX . ':HasPropertyRef');
                $refElem->setAttributeNS(self::NAMESPACE_URI, self::NAMESPACE_PREFIX . ':GUID', $ref['dt:GUID']);
                if (isset($ref['referenceURI'])) {
                    $refElem->setAttribute('referenceURI', $ref['referenceURI']);
                }
                $gop->appendChild($refElem);
            }
        }

        return $gop;
    }

    /**
     * Build XML Property element
     */
    private function buildXmlProperty($dom, $propData): DOMElement
    {
        $prop = $dom->createElementNS(self::NAMESPACE_URI, self::NAMESPACE_PREFIX . ':Property');

        if (isset($propData['dt:GUID'])) {
            $prop->setAttributeNS(self::NAMESPACE_URI, self::NAMESPACE_PREFIX . ':GUID', $propData['dt:GUID']);
        }
        if (isset($propData['dateOfCreation'])) {
            $prop->setAttribute('dateOfCreation', $propData['dateOfCreation']);
        }
        if (isset($propData['referenceURI'])) {
            $prop->setAttribute('referenceURI', $propData['referenceURI']);
        }

        // Name elements
        if (isset($propData['Name'])) {
            foreach ($propData['Name'] as $name) {
                $this->appendTextElement($dom, $prop, 'Name', $name['value'], $name['language']);
            }
        }

        // Definition elements
        if (isset($propData['Definition'])) {
            foreach ($propData['Definition'] as $def) {
                $this->appendTextElement($dom, $prop, 'Definition', $def['value'], $def['language']);
            }
        }

        // LanguageOfCreator
        if (isset($propData['LanguageOfCreator'])) {
            $this->appendTextElement($dom, $prop, 'LanguageOfCreator', $propData['LanguageOfCreator']);
        }

        // CountryOfOrigin
        if (isset($propData['CountryOfOrigin'])) {
            $this->appendTextElement($dom, $prop, 'CountryOfOrigin', $propData['CountryOfOrigin']);
        }

        // MajorVersion
        if (isset($propData['MajorVersion'])) {
            $this->appendTextElement($dom, $prop, 'MajorVersion', (string)$propData['MajorVersion']);
        }

        // MinorVersion
        if (isset($propData['MinorVersion'])) {
            $this->appendTextElement($dom, $prop, 'MinorVersion', (string)$propData['MinorVersion']);
        }

        // Status
        if (isset($propData['Status'])) {
            $this->appendTextElement($dom, $prop, 'Status', $propData['Status']);
        }

        // DataType
        if (isset($propData['DataType'])) {
            $dataType = $dom->createElementNS(self::NAMESPACE_URI, self::NAMESPACE_PREFIX . ':DataType');
            $dataType->setAttribute('name', $propData['DataType']['name']);
            $prop->appendChild($dataType);
        }

        // dimension

        if (isset($propData['Dimension'])) {
            $this->appendTextElement($dom, $prop, 'Dimension', $propData['Dimension']);
        }

        // ReferenceDocumentRef
        if (isset($propData['ReferenceDocumentRef'])) {
            $ref = $dom->createElementNS(self::NAMESPACE_URI, self::NAMESPACE_PREFIX . ':ReferenceDocumentRef');
            $ref->setAttributeNS(self::NAMESPACE_URI, self::NAMESPACE_PREFIX . ':GUID', $propData['ReferenceDocumentRef']['dt:GUID']);
            $prop->appendChild($ref);
        }

        // Units
        if (isset($propData['Units'])) {
            $this->appendTextElement($dom, $prop, 'Units', $propData['Units']);
        }

        return $prop;
    }

    /**
     * Build XML ReferenceDocument element
     */
    private function buildXmlReferenceDocument($dom, $refDocData): DOMElement
    {
        $refDoc = $dom->createElementNS(self::NAMESPACE_URI, self::NAMESPACE_PREFIX . ':ReferenceDocument');

        if (isset($refDocData['dt:GUID'])) {
            $refDoc->setAttributeNS(self::NAMESPACE_URI, self::NAMESPACE_PREFIX . ':GUID', $refDocData['dt:GUID']);
        }
        if (isset($refDocData['dateOfCreation'])) {
            $refDoc->setAttribute('dateOfCreation', $refDocData['dateOfCreation']);
        }

        // Name elements
        if (isset($refDocData['Name'])) {
            foreach ($refDocData['Name'] as $name) {
                $this->appendTextElement($dom, $refDoc, 'Name', $name['value'], $name['language']);
            }
        }

        // Definition elements
        if (isset($refDocData['Definition'])) {
            foreach ($refDocData['Definition'] as $def) {
                $this->appendTextElement($dom, $refDoc, 'Definition', $def['value'], $def['language']);
            }
        }

        // Status
        if (isset($refDocData['Status'])) {
            $this->appendTextElement($dom, $refDoc, 'Status', $refDocData['Status']);
        }

        // URI
        if (isset($refDocData['URI'])) {
            $this->appendTextElement($dom, $refDoc, 'URI', $refDocData['URI']);
        }

        // Language
        if (isset($refDocData['Language'])) {
            $this->appendTextElement($dom, $refDoc, 'Language', $refDocData['Language']);
        }

        return $refDoc;
    }

    /**
     * Helper: Append text element with optional language attribute
     */
    private function appendTextElement($dom, $parent, $name, $value, $language = null)
    {
        $elem = $dom->createElementNS(self::NAMESPACE_URI, self::NAMESPACE_PREFIX . ':' . $name, htmlspecialchars($value, ENT_XML1));
        if ($language) {
            $elem->setAttribute('language', $language);
        }
        $parent->appendChild($elem);
    }
}
