<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Cached lookup of reference documents for the shared referenceDocumentGUID dropdown
 * used in every editor (PDT/GOP/context, Modes 2-4 + CREATE). One source so the field
 * renders consistently everywhere.
 */
class RefDocs
{
    private static ?array $cache = null;

    /** @return array<int,object> {GUID, label} sorted by label. */
    public static function all(): array
    {
        if (self::$cache === null) {
            self::$cache = DB::table('referencedocuments')
                ->get(['GUID', 'title', 'rdName'])
                ->map(fn($r) => (object) [
                    'GUID' => $r->GUID,
                    // Show BOTH name and title so the user can search by either ("rdName: Title").
                    'label' => ($r->rdName && $r->title) ? ($r->rdName . ': ' . $r->title) : ($r->rdName ?: $r->title ?: $r->GUID),
                ])
                ->sortBy('label')->values()->all();
        }
        return self::$cache;
    }

    public static function flush(): void
    {
        self::$cache = null;
    }
}
