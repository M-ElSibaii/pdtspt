<?php

namespace App\Http\Controllers;

use App\Models\properties;
use App\Models\productdatatemplates;
use App\Models\groupofproperties;
use App\Models\propertiesdatadictionaries;
use Illuminate\Http\Request;

/**
 * NOTE: the legacy "Create / Edit Properties" tool methods (choosePDT, createprops,
 * PropertiesAdded, addPropertyManual, addFromDictionary, PropertiesAddedDictionaryPage,
 * uploadExcel, showProperty, updateProperty) were removed — superseded by the unified
 * editor (PreviewService / PdtVersioning / ActivePdtEdit) and PropertyPickerController.
 * Only the shared class-property view remains, reached through /uri/{v}/classprop/{id}-{Name}.
 */
class PropertiesController extends Controller
{
    /**
     * GET Class Property View (ISO 23387 ClassProperty) — shows a specific class property
     * usage within a class/group context. Reached through the classprop identifier,
     * which UriController has already resolved to a properties.Id.
     */
    public function getClassPropertyView($id)
    {
        $property = properties::where('Id', $id)->first();
        if (!$property) {
            abort(404, 'Class Property not found');
        }

        $propertyDefinition = propertiesdatadictionaries::find($property->propertyId);
        $group = groupofproperties::find($property->gopID);
        $pdt = productdatatemplates::find($property->pdtID);
        $referencedocument = $property->referenceDocumentGUID
            ? \App\Models\referencedocuments::where('GUID', $property->referenceDocumentGUID)->first()
            : null;

        return view('classpropertyview', compact('property', 'propertyDefinition', 'group', 'pdt', 'referencedocument'));
    }
}
