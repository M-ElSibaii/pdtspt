<?php

namespace App\Http\Controllers;

use App\Models\constructionobjects;
use App\Models\productdatatemplates;
use App\Models\referencedocuments;

/**
 * Public page for a construction object (EN ISO 23387 ObjectType), reached through its
 * identifier: /uri/{dictVersion}/class/{Name}.
 *
 * Construction objects previously had no page at all, which left the ObjectType
 * references the ISO exporter emits pointing at nothing. They now resolve like every
 * other entity in the dictionary.
 */
class ConstructionObjectController extends Controller
{
    public function view(string $guid)
    {
        $object = constructionobjects::where('GUID', $guid)->firstOrFail();

        // Every stored version of this lineage, for the version list.
        $versions = constructionobjects::where('GUID', $guid)
            ->orderByDesc('versionNumber')->orderByDesc('revisionNumber')
            ->get();

        // The data templates typed by this object.
        $templates = productdatatemplates::where('constructionObjectGUID', $guid)
            ->orderBy('pdtNamePt')
            ->get();

        $referencedocument = $object->referenceDocumentGUID
            ? referencedocuments::where('GUID', $object->referenceDocumentGUID)->first()
            : null;

        return view('constructionobjectview', compact('object', 'versions', 'templates', 'referencedocument'));
    }
}
