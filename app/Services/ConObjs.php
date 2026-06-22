<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Cached lookup of construction objects for the shared constructionObjectGUID dropdown
 * (a PDT's construction object is user-selectable, searchable). Latest instance per GUID
 * lineage so the picker isn't cluttered with old versions.
 */
class ConObjs
{
    private static ?array $cache = null;

    /** @return array<int,object> {GUID, label} sorted by label. */
    public static function all(): array
    {
        if (self::$cache === null) {
            self::$cache = DB::table('constructionobjects')
                ->get(['GUID', 'constructionObjectNameEn', 'constructionObjectNamePt'])
                ->groupBy('GUID')->map->first()->values()
                ->map(fn($r) => (object) [
                    'GUID' => $r->GUID,
                    'label' => trim(($r->constructionObjectNamePt ?: '') . ($r->constructionObjectNameEn ? ' / ' . $r->constructionObjectNameEn : '')) ?: $r->GUID,
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
