<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Generates and validates IFC/bSDD-style GUIDs: a 32-character lowercase hex
 * string with no dashes (e.g. "616fe23846e24ef689d738d4c373020e").
 *
 * Used automatically wherever a brand-new element (a NEW GUID lineage) is created
 * — a from-scratch PDT, GOP, or dictionary property. New VERSIONS of an existing
 * element keep their lineage's existing GUID and must NOT call generate().
 *
 * A GUID is the lineage key: all rows sharing one GUID are versions of the same
 * element. "Unused lineage" therefore means the GUID appears in none of the
 * versioned tables yet.
 */
class GuidGenerator
{
    /** Tables whose GUID column participates in a lineage. */
    private const LINEAGE_TABLES = [
        'productdatatemplates',
        'groupofproperties',
        'propertiesdatadictionaries',
        'properties',
    ];

    /**
     * Generate a random 32-char lowercase hex GUID (no uniqueness check).
     */
    public static function generate(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Generate a GUID that is not already used by any existing lineage. Retries on
     * the (astronomically unlikely) collision.
     */
    public static function generateUnique(): string
    {
        do {
            $guid = self::generate();
        } while (self::existsAnywhere($guid));

        return $guid;
    }

    /**
     * Validate the canonical format: exactly 32 lowercase hex chars, no dashes.
     */
    public static function isValidFormat(?string $guid): bool
    {
        return is_string($guid) && preg_match('/^[0-9a-f]{32}$/', $guid) === 1;
    }

    /**
     * Normalise a user-supplied GUID for comparison/storage: trim, lowercase, and
     * strip dashes (so a pasted dashed UUID maps to the canonical 32-hex form).
     * Returns null if the result is not a valid 32-hex GUID.
     */
    public static function normalize(?string $guid): ?string
    {
        if (!is_string($guid)) {
            return null;
        }
        $candidate = strtolower(str_replace('-', '', trim($guid)));
        return self::isValidFormat($candidate) ? $candidate : null;
    }

    /**
     * True if the GUID already exists in any lineage table (i.e. it belongs to an
     * existing element). Used to enforce "a NEW lineage's GUID must be globally new".
     */
    public static function existsAnywhere(string $guid): bool
    {
        foreach (self::LINEAGE_TABLES as $table) {
            if (DB::table($table)->where('GUID', $guid)->exists()) {
                return true;
            }
        }
        return false;
    }
}
