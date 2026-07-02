<?php

namespace App\Support;

/**
 * Deterministic, stable GUIDs for the ISO 23387 reference layer.
 *
 * Uses RFC 4122 UUIDv5 (SHA-1, name-based) over a fixed project namespace plus a
 * canonical key. Same canonical value -> same GUID on every re-run, so seeding and
 * re-seeding never churn identifiers. Output is dashed-UUID form, matching the XSD
 * GuidType pattern.
 */
class StableGuid
{
    /** Fixed namespace UUID for the pdts.pt ISO 23387 reference layer. */
    private const NAMESPACE = 'b7c9a3e2-1f4d-5a6b-8c7d-9e0f1a2b3c4d';

    public static function forDimension(string $canonical): string
    {
        return self::v5('dimension:' . $canonical);
    }

    public static function forPhysicalQuantity(string $name, string $language): string
    {
        // Case-preserving: "MOhm" and "mOhm" are distinct dictionary names, so they must
        // yield distinct GUIDs (lowercasing would collapse them and churn on re-seed).
        return self::v5('physical_quantity:' . trim($name) . '|' . trim($language));
    }

    public static function forUnit(string $code): string
    {
        return self::v5('unit:' . trim($code));
    }

    /** RFC 4122 name-based UUIDv5 (SHA-1) of $name within the project namespace. */
    public static function v5(string $name): string
    {
        $nsHex = str_replace('-', '', self::NAMESPACE);
        $nsBytes = pack('H*', $nsHex);
        $hash = sha1($nsBytes . $name);

        // Take first 16 bytes; set version (5) and RFC 4122 variant bits.
        $bytes = substr($hash, 0, 32);
        $timeHiVersion = (hexdec(substr($bytes, 12, 4)) & 0x0fff) | 0x5000;
        $clockSeq = (hexdec(substr($bytes, 16, 4)) & 0x3fff) | 0x8000;

        return sprintf(
            '%08s-%04s-%04x-%04x-%12s',
            substr($bytes, 0, 8),
            substr($bytes, 8, 4),
            $timeHiVersion,
            $clockSeq,
            substr($bytes, 20, 12)
        );
    }
}
