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
            ['PDT Nome', $loin->pdtName],
            ['Ator Fornecedor', $loin->actorProviding],
            ['Ator Requerente', $loin->actorRequesting],
            ['Fase de Projeto', $loin->milestone],
            ['Propósito', $loin->purpose],

            // Geometrical Data section
            ['Geometrical Data'],
            ['Detalhe', $loin->detail],
            ['Dimensão', $loin->dimension],
            ['Localização', $loin->location],
            ['Aparência', $loin->appearance],
            ['Comportamento Paramétrico', $loin->parametricBehaviour],

            // Alphanumerical Data section
            ['Alphanumerical Data'],
            ['IFC Class', $loin->ifcClass],
            ['IFC class name', $loin->ifcClassName],
            ['IFC class description', $loin->ifcClassDescription],
            ['IFC class PredefinedType', $loin->ifcClassPredefinedType],
            ['Sistema de Classificação', $loin->classificationSystem],
            ['Tabela de Classificação', $loin->classificationTable],
            ['Código de Classificação', $loin->classificationCode],
            ['IfcMaterial Name', $loin->materialName],

            // Properties header
            ['Propriedades', 'Grupo de propriedade', 'Fonte'],
        ];

        // Add properties if available
        $properties = json_decode($loin->properties, true);
        foreach ($properties as $property) {
            $data[] = [
                $property['property'] ?? '',
                $property['group'] ?? '',
                $property['source'] ?? ''
            ];
        }

        $propertiesCount = count($properties);
        $documentationStartRow = 23 + $propertiesCount;

        // Documentation section
        $data[] = ['Documentação'];
        $documentation = json_decode($loin->documentation, true);

        // Handle if documentation is a JSON object with 'document' and 'format'
        if (is_array($documentation) && isset($documentation[0]['document'], $documentation[0]['format'])) {
            $data[] = ['Document', 'Format'];
            foreach ($documentation as $doc) {
                $data[] = [
                    $doc['document'] ?? '',
                    $doc['format'] ?? '',
                ];
            }
        } else {
            // If documentation is a string or a single JSON field, add it as a single row
            $data[] = [$loin->documentation];
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
        $loin = Loins::where("id", $this->loinId)->first();
        $propertiesCount = count(json_decode($loin->properties, true));
        $documentationStartRow = 24 + $propertiesCount;
        $documentationStartRow2 = 25 + $propertiesCount;
        // Style sections (Geometrical Data, Alphanumerical Data, and Properties)
        $sheet->getStyle('A8')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFF2CC'], // Light orange for Geometrical Data
            ],
        ]);

        $sheet->getStyle('A14')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFF2CC'], // Light orange for Alphanumerical Data
            ],
        ]);


        // Apply style to Documentation header based on dynamic row number
        $sheet->getStyle("A{$documentationStartRow}:A{$documentationStartRow}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFF2CC'], // Light orange for documentation
            ],
        ]);

        $sheet->getStyle('A23:C23')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'cbeff5'], // Light blue for Properties
            ],
        ]);

        $sheet->getStyle("A{$documentationStartRow2}:B{$documentationStartRow2}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'cbeff5'], // Light blue for docs
            ],
        ]);

        // Define styles for other headers
        $styleArray = [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'D0E0F0'], // blue background
            ],
        ];


        // Apply styles to each main header range
        $sheet->getStyle('A1:A7')->applyFromArray($styleArray);
        $sheet->getStyle('A9:A13')->applyFromArray($styleArray);
        $sheet->getStyle('A15:A22')->applyFromArray($styleArray);

        // Merge cells for Geometrical and Alphanumerical Data headers
        $sheet->mergeCells('A8:B8');
        $sheet->mergeCells('A14:C14');
        $sheet->mergeCells("A{$documentationStartRow}:C{$documentationStartRow}");
    }

    public function columnWidths(): array
    {
        return [
            'A' => 40,
            'B' => 50,
            'C' => 30,
        ];
    }
}
