<?php

namespace App\Services;

/**
 * Single source of truth for the bSDD controlled vocabularies, seeded verbatim from
 * resources/data/bsdd_enumerations.json. The lists are INDEPENDENT (units is one flat
 * list, NOT grouped per dataType). Dropdowns and (future) validation both read from here.
 *
 * NOTE for the DEFERRED data-cleanup pass: this same reference is what existing dictionary
 * rows' units/dataType will be reconciled against later (dry-run-first, auto-normalise
 * exact/near matches, flag non-matches; blank units are always fine, never flagged).
 *
 * Encoding caveat: the provided JSON contains some mojibake unit symbols (e.g. "Â°C" where
 * "°C" is intended). It is stored verbatim as the supplied canonical source — repair is a
 * decision to make before the reconciliation pass, not silently here.
 */
class BsddEnums
{
    private static ?array $cache = null;

    private static function all(): array
    {
        if (self::$cache === null) {
            $path = resource_path('data/bsdd_enumerations.json');
            $json = is_file($path) ? file_get_contents($path) : '{}';
            self::$cache = json_decode($json, true) ?: [];
        }
        return self::$cache;
    }

    /** @return string[] */
    public static function get(string $key): array
    {
        return self::all()[$key] ?? [];
    }

    public static function dataType(): array
    {
        return self::get('dataType');
    }

    public static function units(): array
    {
        return self::get('units');
    }

    public static function propertyValueKind(): array
    {
        return self::get('propertyValueKind');
    }

    public static function status(): array
    {
        return self::get('status');
    }

    /** True if $value is blank or a member of the named enum (controlled-value guard). */
    public static function isValid(string $key, ?string $value): bool
    {
        $value = $value === null ? '' : trim($value);
        if ($value === '') {
            return true; // blank allowed (e.g. units is optional)
        }
        return in_array($value, self::get($key), true);
    }

    /** Dropdown enum map for the dictionary form (only fields that have a DB column). */
    public static function dictionaryFieldEnums(): array
    {
        return [
            'dataType' => self::dataType(),
            'units'    => self::units(),
        ];
    }
}
