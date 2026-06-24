<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * Downloadable "gap list": the uploaded property names that do NOT exist as an Active
 * dictionary property and therefore need to be created. One name per row — designed to
 * feed straight back into the brand-new-dictionary-property creation flow.
 */
class GapPropertiesExport implements FromArray, WithHeadings
{
    /** @param string[] $names */
    public function __construct(private array $names)
    {
    }

    public function array(): array
    {
        return array_map(fn($n) => [$n], array_values($this->names));
    }

    public function headings(): array
    {
        return ['nameEn (needs creation)'];
    }
}
