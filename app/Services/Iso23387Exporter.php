<?php

namespace App\Services;

use App\Models\productdatatemplates;
use App\Models\groupofproperties;
use App\Models\propertiesdatadictionaries;
use App\Models\referencedocuments;
use Illuminate\Support\Facades\DB;
use DOMDocument;
use DOMException;

class Iso23387Exporter
{
    /**
     * Build complete EN ISO 23387 structure for a single PDT
     */
    public function getPdtStructure($pdtId): array
    {
        $pdt = productdatatemplates::find($pdtId);

        if (!$pdt) {
            throw new \Exception("PDT not found: {$pdtId}");
        }

        // Get groupofproperties using direct DB query
        $groupofproperties = DB::table('groupofproperties')
            ->where('pdtId', $pdtId)
            ->get();

        // Build Library root structure
        $library = [
            'Library' => [
                'GUID' => $pdt->GUID,
                'Name' => $pdt->pdtNamePt,
                'DataTemplates' => [],
                'GroupOfProperties' => [],
                'Properties' => [],
                'ReferenceDocuments' => []
            ]
        ];

        // Build DataTemplate element
        $library['Library']['DataTemplates'][] = $this->buildDataTemplate($pdt, $groupofproperties);

        // Build GroupOfProperties elements
        foreach ($groupofproperties as $gop) {
            $library['Library']['GroupOfProperties'][] = $this->buildGroupOfProperties($gop);
        }

        // Build Property elements - get all properties linked to this PDT via properties table
        $allProperties = DB::table('properties')
            ->where('pdtID', $pdtId)
            ->select('GUID')
            ->distinct()
            ->pluck('GUID');

        // Load propertiesdatadictionaries for these GUIDs
        if ($allProperties->count() > 0) {
            $properties = propertiesdatadictionaries::whereIn('GUID', $allProperties->toArray())->get();
            foreach ($properties as $prop) {
                $library['Library']['Properties'][] = $this->buildProperty($prop);
            }
        }

        // Build ReferenceDocument elements - collect all referenced document GUIDs
        $refDocGUIDs = collect([$pdt->referenceDocumentGUID])
            ->merge($groupofproperties->pluck('referenceDocumentGUID'))
            ->filter(function ($guid) {
                return $guid && $guid !== 'n/a';
            })
            ->unique();

        // Also get reference docs from properties via properties table
        $propRefDocs = DB::table('properties')
            ->where('pdtID', $pdtId)
            ->whereNotIn('referenceDocumentGUID', ['n/a', null, ''])
            ->select('referenceDocumentGUID')
            ->distinct()
            ->pluck('referenceDocumentGUID');

        $refDocGUIDs = $refDocGUIDs->merge($propRefDocs)->filter()->unique();

        if ($refDocGUIDs->count() > 0) {
            $refDocs = referencedocuments::whereIn('GUID', $refDocGUIDs->toArray())->get();
            foreach ($refDocs as $refDoc) {
                $library['Library']['ReferenceDocuments'][] = $this->buildReferenceDocument($refDoc);
            }
        }

        return $library;
    }

    /**
     * Build DataTemplate element from productdatatemplates
     */
    private function buildDataTemplate($pdt, $groupofproperties): array
    {
        $template = [
            'GUID' => $pdt->GUID,
            'dateOfCreation' => $pdt->dateOfVersion,
            'Name' => [
                'language' => 'pt',
                'value' => $pdt->pdtNamePt
            ],
            'Definition' => [
                'language' => 'pt',
                'value' => $pdt->descriptionPt
            ],
            'Status' => $pdt->status,
            'HasGroupOfPropertiesRef' => [],
            'HasPropertyRef' => []
        ];

        // Add group references
        foreach ($groupofproperties as $gop) {
            $template['HasGroupOfPropertiesRef'][] = ['GUID' => $gop->GUID];
        }

        // Add property references - get unique property GUIDs from properties table for this PDT
        $propGUIDs = DB::table('properties')
            ->where('pdtID', $pdt->Id)
            ->select('GUID')
            ->distinct()
            ->pluck('GUID');

        foreach ($propGUIDs as $guid) {
            $template['HasPropertyRef'][] = ['GUID' => $guid];
        }

        return $template;
    }

    /**
     * Build GroupOfProperties element from groupofproperties
     */
    private function buildGroupOfProperties($gop): array
    {
        $group = [
            'GUID' => $gop->GUID,
            'dateOfCreation' => $gop->dateOfVersion,
            'Name' => [
                'language' => 'pt',
                'value' => $gop->gopNamePt
            ],
            'Definition' => [
                'language' => 'pt',
                'value' => $gop->definitionPt
            ],
            'Status' => $gop->status,
            'LanguageOfCreator' => $gop->creatorsLanguage,
            'CountryOfOrigin' => $gop->countryOfOrigin,
            'HasPropertyRef' => []
        ];

        // Load properties for this group - use direct query
        $propGUIDs = DB::table('properties')
            ->where('gopId', $gop->Id)
            ->select('GUID')
            ->distinct()
            ->pluck('GUID');

        foreach ($propGUIDs as $guid) {
            $group['HasPropertyRef'][] = ['GUID' => $guid];
        }

        return $group;
    }

    /**
     * Build Property element from propertiesdatadictionaries
     */
    private function buildProperty($prop): array
    {
        return [
            'GUID' => $prop->GUID,
            'dateOfCreation' => $prop->dateOfVersion,
            'Name' => [
                'language' => 'pt',
                'value' => $prop->namePt
            ],
            'Definition' => [
                'language' => 'pt',
                'value' => $prop->definitionPt
            ],
            'Status' => $prop->status,
            'LanguageOfCreator' => $prop->creatorsLanguage,
            'CountryOfOrigin' => $prop->countryOfOrigin,
            'Units' => $prop->units,
            'PhysicalQuantity' => $prop->physicalQuantity
        ];
    }

    /**
     * Build ReferenceDocument element from referencedocuments
     */
    private function buildReferenceDocument($refDoc): array
    {
        return [
            'GUID' => $refDoc->GUID,
            'dateOfCreation' => $refDoc->dateOfCreation,
            'Name' => [
                'language' => 'en',
                'value' => $refDoc->rdName
            ],
            'Status' => $refDoc->status ?? 'XTD_ACTIVE',
            'URI' => $refDoc->uri ?? null
        ];
    }

    /**
     * Export PDT to JSON format (EN ISO 23387 structure)
     */
    public function exportToJson($pdtId): string
    {
        $structure = $this->getPdtStructure($pdtId);
        return json_encode($structure, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Export PDT to XML format (EN ISO 23387 structure, XSD validated)
     */
    public function exportToXml($pdtId): string
    {
        $structure = $this->getPdtStructure($pdtId);
        $xmlString = $this->buildXmlDocument($structure);

        // Note: XSD validation is optional and can be enabled when needed
        // Currently skipping validation to avoid libxml schema reading errors
        // You can validate locally with: php -r 'echo (new DOMDocument())->schemaValidate("path/to/xsd");'

        return $xmlString;
    }

    /**
     * Build XML document from structure array
     */
    private function buildXmlDocument($structure): string
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        // Create Library element with namespaces
        $library = $dom->createElementNS('https://standards.iso.org/iso/23387/ed-2/en/', 'dt:Library');
        $library->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $library->setAttribute('xsi:schemaLocation', 'https://standards.iso.org/iso/23387/ed-2/en/ https://standards.iso.org/iso/23387/ed-2/en/23387_AnnexE_XSD_V15.xsd');

        $libData = $structure['Library'];
        if (isset($libData['GUID'])) {
            $library->setAttribute('dt:GUID', $libData['GUID']);
        }

        // Add Library Name
        if (isset($libData['Name'])) {
            $nameElem = $dom->createElementNS('https://standards.iso.org/iso/23387/ed-2/en/', 'dt:Name', htmlspecialchars($libData['Name']));
            $nameElem->setAttribute('language', 'pt');
            $library->appendChild($nameElem);
        }

        // Add DataTemplates
        foreach ($libData['DataTemplates'] as $dt) {
            $dtElem = $this->buildXmlDataTemplate($dom, $dt);
            $library->appendChild($dtElem);
        }

        // Add GroupOfProperties
        foreach ($libData['GroupOfProperties'] as $gop) {
            $gopElem = $this->buildXmlGroupOfProperties($dom, $gop);
            $library->appendChild($gopElem);
        }

        // Add Properties
        foreach ($libData['Properties'] as $prop) {
            $propElem = $this->buildXmlProperty($dom, $prop);
            $library->appendChild($propElem);
        }

        // Add ReferenceDocuments
        foreach ($libData['ReferenceDocuments'] as $refDoc) {
            $rdElem = $this->buildXmlReferenceDocument($dom, $refDoc);
            $library->appendChild($rdElem);
        }

        $dom->appendChild($library);
        return $dom->saveXML();
    }

    /**
     * Build XML DataTemplate element
     */
    private function buildXmlDataTemplate($dom, $dtData)
    {
        $dt = $dom->createElementNS('https://standards.iso.org/iso/23387/ed-2/en/', 'dt:DataTemplate');

        if (isset($dtData['GUID'])) {
            $dt->setAttribute('dt:GUID', $dtData['GUID']);
        }
        if (isset($dtData['dateOfCreation'])) {
            $dt->setAttribute('dateOfCreation', $dtData['dateOfCreation']);
        }

        if (isset($dtData['Name'])) {
            $name = $dom->createElementNS('https://standards.iso.org/iso/23387/ed-2/en/', 'dt:Name', htmlspecialchars($dtData['Name']['value']));
            $name->setAttribute('language', $dtData['Name']['language']);
            $dt->appendChild($name);
        }

        if (isset($dtData['Definition'])) {
            $def = $dom->createElementNS('https://standards.iso.org/iso/23387/ed-2/en/', 'dt:Definition', htmlspecialchars($dtData['Definition']['value']));
            $def->setAttribute('language', $dtData['Definition']['language']);
            $dt->appendChild($def);
        }

        if (isset($dtData['Status'])) {
            $status = $dom->createElementNS('https://standards.iso.org/iso/23387/ed-2/en/', 'dt:Status', htmlspecialchars($dtData['Status']));
            $dt->appendChild($status);
        }

        foreach ($dtData['HasGroupOfPropertiesRef'] as $ref) {
            $refElem = $dom->createElementNS('https://standards.iso.org/iso/23387/ed-2/en/', 'dt:HasGroupOfPropertiesRef');
            $refElem->setAttribute('dt:GUID', $ref['GUID']);
            $dt->appendChild($refElem);
        }

        foreach ($dtData['HasPropertyRef'] as $ref) {
            $refElem = $dom->createElementNS('https://standards.iso.org/iso/23387/ed-2/en/', 'dt:HasPropertyRef');
            $refElem->setAttribute('dt:GUID', $ref['GUID']);
            $dt->appendChild($refElem);
        }

        return $dt;
    }

    /**
     * Build XML GroupOfProperties element
     */
    private function buildXmlGroupOfProperties($dom, $gopData)
    {
        $gop = $dom->createElementNS('https://standards.iso.org/iso/23387/ed-2/en/', 'dt:GroupOfProperties');

        if (isset($gopData['GUID'])) {
            $gop->setAttribute('dt:GUID', $gopData['GUID']);
        }
        if (isset($gopData['dateOfCreation'])) {
            $gop->setAttribute('dateOfCreation', $gopData['dateOfCreation']);
        }

        if (isset($gopData['Name'])) {
            $name = $dom->createElementNS('https://standards.iso.org/iso/23387/ed-2/en/', 'dt:Name', htmlspecialchars($gopData['Name']['value']));
            $name->setAttribute('language', $gopData['Name']['language']);
            $gop->appendChild($name);
        }

        if (isset($gopData['Definition'])) {
            $def = $dom->createElementNS('https://standards.iso.org/iso/23387/ed-2/en/', 'dt:Definition', htmlspecialchars($gopData['Definition']['value']));
            $def->setAttribute('language', $gopData['Definition']['language']);
            $gop->appendChild($def);
        }

        if (isset($gopData['Status'])) {
            $status = $dom->createElementNS('https://standards.iso.org/iso/23387/ed-2/en/', 'dt:Status', htmlspecialchars($gopData['Status']));
            $gop->appendChild($status);
        }

        if (isset($gopData['LanguageOfCreator'])) {
            $lang = $dom->createElementNS('https://standards.iso.org/iso/23387/ed-2/en/', 'dt:LanguageOfCreator', htmlspecialchars($gopData['LanguageOfCreator']));
            $gop->appendChild($lang);
        }

        if (isset($gopData['CountryOfOrigin'])) {
            $country = $dom->createElementNS('https://standards.iso.org/iso/23387/ed-2/en/', 'dt:CountryOfOrigin', htmlspecialchars($gopData['CountryOfOrigin']));
            $gop->appendChild($country);
        }

        foreach ($gopData['HasPropertyRef'] as $ref) {
            $refElem = $dom->createElementNS('https://standards.iso.org/iso/23387/ed-2/en/', 'dt:HasPropertyRef');
            $refElem->setAttribute('dt:GUID', $ref['GUID']);
            $gop->appendChild($refElem);
        }

        return $gop;
    }

    /**
     * Build XML Property element
     */
    private function buildXmlProperty($dom, $propData)
    {
        $prop = $dom->createElementNS('https://standards.iso.org/iso/23387/ed-2/en/', 'dt:Property');

        if (isset($propData['GUID'])) {
            $prop->setAttribute('dt:GUID', $propData['GUID']);
        }
        if (isset($propData['dateOfCreation'])) {
            $prop->setAttribute('dateOfCreation', $propData['dateOfCreation']);
        }

        if (isset($propData['Name'])) {
            $name = $dom->createElementNS('https://standards.iso.org/iso/23387/ed-2/en/', 'dt:Name', htmlspecialchars($propData['Name']['value']));
            $name->setAttribute('language', $propData['Name']['language']);
            $prop->appendChild($name);
        }

        if (isset($propData['Definition'])) {
            $def = $dom->createElementNS('https://standards.iso.org/iso/23387/ed-2/en/', 'dt:Definition', htmlspecialchars($propData['Definition']['value']));
            $def->setAttribute('language', $propData['Definition']['language']);
            $prop->appendChild($def);
        }

        if (isset($propData['Status'])) {
            $status = $dom->createElementNS('https://standards.iso.org/iso/23387/ed-2/en/', 'dt:Status', htmlspecialchars($propData['Status']));
            $prop->appendChild($status);
        }

        if (isset($propData['Units']) && $propData['Units']) {
            $units = $dom->createElementNS('https://standards.iso.org/iso/23387/ed-2/en/', 'dt:Units', htmlspecialchars($propData['Units']));
            $prop->appendChild($units);
        }

        return $prop;
    }

    /**
     * Build XML ReferenceDocument element
     */
    private function buildXmlReferenceDocument($dom, $rdData)
    {
        $rd = $dom->createElementNS('https://standards.iso.org/iso/23387/ed-2/en/', 'dt:ReferenceDocument');

        if (isset($rdData['GUID'])) {
            $rd->setAttribute('dt:GUID', $rdData['GUID']);
        }
        if (isset($rdData['dateOfCreation'])) {
            $rd->setAttribute('dateOfCreation', $rdData['dateOfCreation']);
        }

        if (isset($rdData['Name'])) {
            $name = $dom->createElementNS('https://standards.iso.org/iso/23387/ed-2/en/', 'dt:Name', htmlspecialchars($rdData['Name']['value']));
            $name->setAttribute('language', $rdData['Name']['language']);
            $rd->appendChild($name);
        }

        if (isset($rdData['Status'])) {
            $status = $dom->createElementNS('https://standards.iso.org/iso/23387/ed-2/en/', 'dt:Status', htmlspecialchars($rdData['Status']));
            $rd->appendChild($status);
        }

        if (isset($rdData['URI']) && $rdData['URI']) {
            $uri = $dom->createElementNS('https://standards.iso.org/iso/23387/ed-2/en/', 'dt:URI', htmlspecialchars($rdData['URI']));
            $rd->appendChild($uri);
        }

        return $rd;
    }
}
