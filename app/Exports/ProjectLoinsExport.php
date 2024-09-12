<?php

namespace App\Exports;

use App\Models\Loins;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ProjectLoinsExport implements WithMultipleSheets
{
    protected $projectName;

    public function __construct($projectName)
    {
        $this->projectName = $projectName;
    }

    public function sheets(): array
    {
        $loins = Loins::where('projectName', $this->projectName)->get();
        $sheets = [];

        foreach ($loins as $loin) {
            $sheets[] = new LoinExport($loin->id);
        }

        return $sheets;
    }
}
