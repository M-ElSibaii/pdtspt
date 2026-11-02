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

    /** Canonical identifier for a unit code: https://pdts.pt/uri/{v}/unit/{code}. */
    public static function unitUri(string $code): string
    {
        return UriService::uri(UriService::UNIT, $code);
    }

    /** Canonical identifier for a quantity kind: https://pdts.pt/uri/{v}/pq/{name}. */
    public static function quantityKindUri(string $name): string
    {
        return UriService::uri(UriService::QUANTITY_KIND, $name);
    }

    /** Canonical, resolvable pdts.pt identity URI for a dimension (by canonical string). */
    public static function dimensionUri(string $canonical): string
    {
        return UriService::uri(UriService::DIMENSION, $canonical);
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

    /** @var array<string,object>|null unit code => {code,name,pq,pqPt,dim} */
    private static ?array $unitCache = null;

    /**
     * Spellings found in the dictionary that are not the unit's symbol and that the general
     * rules in {@see resolveCode()} cannot reach (missing brackets, ASCII for sub-/superscripts).
     */
    private const ALIASES = [
        'W/mK'             => 'W/(m·K)',
        'W/m²K'            => 'W/(m²·K)',
        'W/m2K'            => 'W/(m²·K)',
        'kg CO2 eq'        => 'kg CO₂ eq',
        'kg CO2-eq'        => 'kg CO₂ eq',
        'kg CFC-11 eq'     => 'kg CFC11 eq',
        'kg (PO4)^(3-) eq' => 'kg PO₄³⁻ eq',
        'kg PO4 eq'        => 'kg PO₄³⁻ eq',
        'months'           => 'Month',
        'month'            => 'Month',
        'dimensionless'    => 'unitless',
        'cmH2O'            => 'cmH₂O',
    ];

    /**
     * Every unit with its quantity and dimension, keyed by code. The unit's own dimension wins;
     * otherwise its quantity's.
     *
     * @return array<string,object>
     */
    public static function units(): array
    {
        if (self::$unitCache !== null) {
            return self::$unitCache;
        }

        $rows = DB::table('units as u')
            ->leftJoin('physical_quantities as q', 'u.physical_quantity_guid', '=', 'q.guid')
            ->leftJoin('dimensions as ud', 'u.dimension_guid', '=', 'ud.guid')
            ->leftJoin('dimensions as qd', 'q.dimension_guid', '=', 'qd.guid')
            ->select('u.code', 'u.name', 'q.name as pq', 'q.namePt as pqPt', DB::raw('COALESCE(ud.canonical, qd.canonical) as dim'))
            ->get();

        $out = [];
        foreach ($rows as $r) {
            $out[$r->code] = $r;
        }

        return self::$unitCache = $out;
    }

    /** Forget the cached reference tables (after the seeder rewrote them). */
    public static function flush(): void
    {
        self::$mapCache = self::$unitCache = self::$codeCache = null;
    }

    /** @var array<string,?string>|null */
    private static ?array $codeCache = null;

    /**
     * The reference unit a stored value means, or null. The dictionary was typed by hand, so
     * besides the exact symbol this accepts: surrounding spaces, ASCII powers ("m2", "N/mm2",
     * "kg/m3", "cm4", "m^2"), a known alias ("W/mK"), and a different letter case when that
     * still points at exactly one unit ("KN" → kN, "Eur" → EUR — never MW vs mW).
     */
    public static function resolveCode(?string $raw): ?string
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return null;
        }
        self::$codeCache ??= [];
        if (array_key_exists($raw, self::$codeCache)) {
            return self::$codeCache[$raw];
        }

        $units = self::units();
        $candidates = [$raw, self::ALIASES[$raw] ?? null];
        // ASCII powers after a letter or ")": m2 → m², N/mm2 → N/mm², m^3 → m³.
        $sup = ['2' => '²', '3' => '³', '4' => '⁴'];
        $candidates[] = preg_replace_callback('/(?<=[A-Za-zµ\)])\^?([234])(?![0-9])/u', fn ($m) => $sup[$m[1]], $raw);
        $candidates[] = str_replace([' / ', '*', '.'], ['/', '·', '·'], end($candidates));

        foreach (array_filter($candidates) as $c) {
            if (isset($units[$c])) {
                return self::$codeCache[$raw] = $c;
            }
            $ci = array_values(array_filter(array_keys($units), fn ($code) => mb_strtolower((string) $code) === mb_strtolower($c)));
            if (count($ci) === 1) {
                return self::$codeCache[$raw] = $ci[0];
            }
        }

        return self::$codeCache[$raw] = null;
    }

    /**
     * Unit, physical quantity and dimension for a stored unit value, ready for display:
     * {code, name, pq, pqPt, dim} with the reference unit's code, or null when unknown.
     */
    public static function describe(?string $raw): ?object
    {
        $code = self::resolveCode($raw);

        return $code === null ? null : self::units()[$code];
    }

    /**
     * unit code => [ 'pq' => "physical quantity" (CLEAN, no "| language"), 'dim' => canonical
     * dimension or "" ]. The ISO 23386 "| language" pairing is applied only on export output,
     * never stored.
     */
    public static function autofillMap(): array
    {
        if (self::$mapCache !== null) {
            return self::$mapCache;
        }

        $map = [];
        foreach (self::units() as $code => $u) {
            $map[$code] = ['pq' => $u->pq ?? self::WITHOUT, 'dim' => (string) ($u->dim ?? '')];
        }

        return self::$mapCache = $map;
    }

    /** Resolve one unit value to its CLEAN physical-quantity name (or "without" for blank/unknown). */
    public static function physicalQuantityFor(?string $code): string
    {
        $code = self::resolveCode($code);

        return $code === null ? self::WITHOUT : self::autofillMap()[$code]['pq'];
    }

    /**
     * A canonical dimension written the ISO 80000 way: "L-1M1T-2" → "L⁻¹·M·T⁻²", "1" → "1".
     */
    public static function prettyDimension(?string $canonical): string
    {
        $canonical = (string) $canonical;
        if (!preg_match_all('/([LMTIΘNJ])(-?[0-9.]+)/u', $canonical, $m, PREG_SET_ORDER)) {
            return $canonical;
        }
        $sup = ['-' => '⁻', '.' => '·', '0' => '⁰', '1' => '¹', '2' => '²', '3' => '³', '4' => '⁴',
            '5' => '⁵', '6' => '⁶', '7' => '⁷', '8' => '⁸', '9' => '⁹'];
        $parts = array_map(fn ($p) => $p[1] . ($p[2] === '1' ? '' : strtr($p[2], $sup)), $m);

        return implode('·', $parts);
    }

    /**
     * Dictionary properties whose unit is one of $codes (in any spelling resolveCode() accepts),
     * newest version per property, published first.
     *
     * @param string[] $codes reference unit codes
     */
    public static function propertiesUsing(array $codes, int $limit = 200): \Illuminate\Support\Collection
    {
        $codes = array_flip($codes);
        $spellings = DB::table('propertiesdatadictionaries')->whereNotNull('units')->where('units', '<>', '')
            ->distinct()->pluck('units')
            ->filter(fn ($raw) => isset($codes[self::resolveCode($raw) ?? '']))
            ->values()->all();
        if (!$spellings) {
            return collect();
        }

        return DB::table('propertiesdatadictionaries')
            ->whereIn('units', $spellings)
            ->where('status', '<>', 'Preview')
            ->orderByRaw("status = 'Active' DESC")->orderBy('nameEn')->orderByDesc('versionNumber')->orderByDesc('revisionNumber')
            ->get(['Id', 'GUID', 'nameEn', 'namePt', 'nameEnSc', 'namePtSc', 'units', 'status', 'versionNumber', 'revisionNumber'])
            ->unique('GUID')->take($limit)->values();
    }

    /** True if the value names a unit of the reference tables (allowing the spellings resolveCode() accepts). */
    public static function isKnownUnit(?string $code): bool
    {
        return self::resolveCode($code) !== null;
    }
}
