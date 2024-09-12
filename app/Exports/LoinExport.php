<?php

namespace App\Exports;

use App\Models\Loins;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LoinExport implements FromArray, WithTitle, WithStyles, WithColumnWidths
{
    protected $loinId;

    public function __construct($loinId)
    {
        $this->loinId = $loinId;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function array(): array
    {
        $loin = Loins::where("id", $this->loinId)->first();
        $data = [
            ['Nome de Projeto', $loin->projectName],
            ['Objeto', $loin->objectName],
            ['IFC Class', $loin->ifcElement],
            ['PDT Nome', $loin->pdtName],
            ['Ator Fornecedor', $loin->actorProviding],
            ['Ator Requerente', $loin->actorRequesting],
            ['Fase de Projeto', $loin->projectPhase],
            ['Propósito', $loin->purpose],
            ['Sistema de Classificação', $loin->classificationSystem],
            ['Tabela de Classificação', $loin->classificationTable],
            ['Código de Classificação', $loin->classificationCode],
            ['Documentação', $loin->documentation],
            ['Informação Geométrica'],
            ['Detalhe', $loin->detail],
            ['Dimensão', $loin->dimension],
            ['Localização', $loin->location],
            ['Aparência', $loin->appearance],
            ['Comportamento Paramétrico', $loin->parametricBehaviour],
            ['Informação Alfanumérica'],
            ['Nome', $loin->name],
        ];

        // Add properties as alphanumerical data
        $properties = json_decode($loin->properties, true);
        $data[] = ['Propriedades', 'Grupo de propriedade', 'Fonte'];
        foreach ($properties as $property) {
            $data[] = [$property['property'], $property['group'], $property['source']];
        }

        return $data;
    }

    public function title(): string
    {
        $loin = Loins::where("id", $this->loinId)->first();
        return $loin->objectName; // Set the sheet name as the object name or any other unique identifier
    }


    public function styles(Worksheet $sheet)
    {

        // Style Geometrical Properties and Alphanumerical Properties sections
        $sheet->getStyle('A13')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFF2CC'], // Light orange
            ],
        ]);

        $sheet->getStyle('A19')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFF2CC'], // Light orange
            ],
        ]);

        $sheet->getStyle('A21:C21')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'cbeff5'], // Light blue
            ],
        ]);

        $sheet->getStyle('A12')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFF2CC'], // Light orange
            ],
        ]);

        // Define the style array
        $styleArray = [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'D0E0F0'], // Light blue background
            ],
        ];

        // Apply styles to each range
        $sheet->getStyle('A1:A12')->applyFromArray($styleArray);
        $sheet->getStyle('A14:A18')->applyFromArray($styleArray);
        $sheet->getStyle('A20')->applyFromArray($styleArray);

        // Merge cells for Geometrical Properties and Alphanumerical Properties headers
        $sheet->mergeCells('A13:B13'); // Merge cells A14 and B14
        $sheet->mergeCells('A19:C19'); // Merge cells A20, B20, and C20
    }

    public function columnWidths(): array
    {
        return [
            'A' => 40, // Column A width
            'B' => 50, // Column B width
            'C' => 30, // Column C width
        ];
    }
}
