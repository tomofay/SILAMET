<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReportExport implements FromArray, WithHeadings, WithTitle, WithStyles, ShouldAutoSize, WithColumnFormatting
{
    /**
     * @param array{
     *     type: string,
     *     sheet_title: string,
     *     headings: array<int, string>,
     *     rows: array<int, array<int, mixed>>,
     *     column_formats?: array<string, string>,
     *     center_columns?: array<int, string>,
     *     right_columns?: array<int, string>
     * } $payload
     */
    public function __construct(
        private array $payload
    ) {}

    public function array(): array
    {
        return $this->payload['rows'] ?? [];
    }

    public function headings(): array
    {
        return $this->payload['headings'] ?? [];
    }

    public function title(): string
    {
        $raw = (string) ($this->payload['sheet_title'] ?? 'Laporan SILAMET');

        return mb_substr(preg_replace('/[\\\\\/\?\*\[\]:]/', '-', $raw) ?: 'Laporan', 0, 31);
    }

    public function columnFormats(): array
    {
        return $this->payload['column_formats'] ?? [];
    }

    public function styles(Worksheet $sheet): array
    {
        $colCount = max(1, count($this->headings()));
        $rowCount = count($this->array()) + 1;
        $lastCol = Coordinate::stringFromColumnIndex($colCount);

        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFFFF'],
                'size' => 10,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF0F172A'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(26);

        if ($rowCount >= 2) {
            $sheet->getStyle("A2:{$lastCol}{$rowCount}")->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => 'FFE2E8F0'],
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);

            for ($r = 3; $r <= $rowCount; $r += 2) {
                $sheet->getStyle("A{$r}:{$lastCol}{$r}")->getFill()->applyFromArray([
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFF8FAFC'],
                ]);
            }

            foreach ($this->payload['center_columns'] ?? [] as $colLetter) {
                $sheet->getStyle("{$colLetter}2:{$colLetter}{$rowCount}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }

            foreach ($this->payload['right_columns'] ?? [] as $colLetter) {
                $sheet->getStyle("{$colLetter}2:{$colLetter}{$rowCount}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            }
        }

        return [];
    }
}

