<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Imported rows carry MySQL "zero" dates ('0000-00-00'): every dictionary property does.
 * Reading them is fine, but the connection runs in strict mode, so writing one back —
 * which is exactly what copying a row does (forking a definition, starting a draft) —
 * fails with "Incorrect date value". Clean a row before inserting a copy of it.
 */
class LegacyDates
{
    /** Date columns that are NOT NULL in the versioned tables; they get a real date. */
    private const REQUIRED = ['dateOfRevision', 'dateOfVersion', 'created_at', 'updated_at'];

    public static function isZero($value): bool
    {
        return is_string($value) && str_starts_with($value, '0000-00-00');
    }

    /** Zero dates -> null, or today for the NOT NULL date columns. */
    public static function clean(array $row): array
    {
        foreach ($row as $col => $value) {
            if (self::isZero($value)) {
                $row[$col] = in_array($col, self::REQUIRED, true) ? Carbon::today()->toDateString() : null;
            }
        }

        return $row;
    }

    /** A date to carry over, or today when the stored one is a zero date. */
    public static function orToday($value): string
    {
        return ($value === null || self::isZero($value)) ? Carbon::today()->toDateString() : (string) $value;
    }
}
