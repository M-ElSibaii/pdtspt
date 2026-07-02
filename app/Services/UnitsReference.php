<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Read helpers for the ISO 23387 units / physical-quantity / dimension reference tables.
 * The editor uses autofillMap() to derive the (read-only) physical quantity and dimension
 * from the unit the user selects. Cleanup/exporter use the same source of truth.
 */
class UnitsReference
{
    public const WITHOUT = 'without';
    public const DEFAULT_LANG = 'en.EN';

    /** Our platform is the resolve target for these entity URIs (R-LD-2). */
    public const BASE = 'https://pdts.pt';

    /**
     * Percent-encode a URI path value while keeping "/" literal so codes like "kg/m³"
     * stay routable path segments (the catch-all route captures the remainder).
     */
    private static function seg(string $value): string
    {
        return str_replace('%2F', '/', rawurlencode($value));
    }

    /** Canonical, resolvable pdts.pt identity URI for a unit code. */
    public static function unitUri(string $code): string
    {
        return self::BASE . '/unit/' . self::seg($code);
    }

    /** Canonical, resolvable pdts.pt identity URI for a physical quantity (quantity kind). */
    public static function quantityKindUri(string $name): string
    {
        return self::BASE . '/quantitykind/' . self::seg($name);
    }

    /** Canonical, resolvable pdts.pt identity URI for a dimension (by canonical string). */
    public static function dimensionUri(string $canonical): string
    {
        return self::BASE . '/dimension/' . self::seg($canonical);
    }

    /**
     * External authority (QUDT) citation for an entity (R-LD-4). Returns the sourced QUDT
     * IRI when present ('reference'), plus a discovery search URL ('search') so the page can
     * always point at QUDT without fabricating a specific identity IRI while physics/mapping
     * is deferred.
     *
     * @return array{name:string,reference:?string,search:string}
     */
    public static function qudtAuthority(?string $referenceUri, string $term, string $kind = 'unit'): array
    {
        return [
            'name' => 'QUDT',
            'reference' => $referenceUri ?: null,
            'search' => 'https://www.qudt.org/fuseki/qudt/query?term=' . rawurlencode($term)
                . '&kind=' . rawurlencode($kind),
        ];
    }

    /** @var array<string,array{pq:string,dim:string}>|null */
    private static ?array $mapCache = null;

    /**
     * unit code => [ 'pq' => "physical quantity" (CLEAN, no "| language"), 'dim' => canonical
     * dimension or "" ]. Physics is deferred, so 'dim' is "" for now (no fabricated strings).
     * The ISO 23386 "| language" pairing is applied only on export output, never stored.
     */
    public static function autofillMap(): array
    {
        if (self::$mapCache !== null) {
            return self::$mapCache;
        }

        $rows = DB::table('units as u')
            ->leftJoin('physical_quantities as q', 'u.physical_quantity_guid', '=', 'q.guid')
            ->leftJoin('dimensions as d', 'q.dimension_guid', '=', 'd.guid')
            ->select('u.code', 'q.name as pq_name', 'd.canonical as dim_canonical')
            ->get();

        $map = [];
        foreach ($rows as $r) {
            $pq = $r->pq_name !== null ? $r->pq_name : self::WITHOUT;
            $map[$r->code] = ['pq' => $pq, 'dim' => (string) ($r->dim_canonical ?? '')];
        }

        return self::$mapCache = $map;
    }

    /** Resolve one unit code to its CLEAN physical-quantity name (or "without" for blank/unknown). */
    public static function physicalQuantityFor(?string $code): string
    {
        $code = trim((string) $code);
        if ($code === '') {
            return self::WITHOUT;
        }
        return self::autofillMap()[$code]['pq'] ?? self::WITHOUT;
    }

    /** True if the unit code exists in the reference tables. */
    public static function isKnownUnit(?string $code): bool
    {
        $code = trim((string) $code);
        return $code !== '' && isset(self::autofillMap()[$code]);
    }
}
