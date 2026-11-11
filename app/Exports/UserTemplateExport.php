<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class UserTemplateExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithColumnFormatting
{
    /**
     * Data contoh untuk template Excel.
     */
    public function array(): array
    {
        return [
            [
                'nama'    => 'Ahmad Fadhil Prasetyo',
                'nis_nip' => '23241001',
                'email'   => 'ahmad.fadhil@smkn1cibinong.sch.id',
                'phone'   => '081234567890',
                'role'    => 'siswa',
            ],
            [
                'nama'    => 'Siti Nur Aisyah',
                'nis_nip' => '23241002',
                'email'   => 'siti.aisyah@smkn1cibinong.sch.id',
                'phone'   => '085712345678',
                'role'    => 'siswa',
            ],
            [
                'nama'    => 'Rahmat Hidayat, S.Kom',
                'nis_nip' => '198501152010011005',
                'email'   => 'rahmat.hidayat@smkn1cibinong.sch.id',
                'phone'   => '081398765432',
                'role'    => 'teknisi',
            ],
            [
                'nama'    => 'Drs. Bambang Sutrisno, M.T',
                'nis_nip' => '197604122003121002',
                'email'   => 'bambang.sutrisno@smkn1cibinong.sch.id',
                'phone'   => '082111223344',
                'role'    => 'kabeng',
            ],
        ];
    }

    /**
     * Header kolom Excel (kunci baku yang dikenali sistem).
     */
    public function headings(): array
    {
        return [
            'nama',
            'nis_nip',
            'email',
            'phone',
            'role',
        ];
    }

    /**
     * Format kolom agar angka NIS/NIP dan Phone tidak terpotong (disimpan sebagai teks).
     */
    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_TEXT,
            'D' => NumberFormat::FORMAT_TEXT,
        ];
    }

    /**
     * Styling tabel Excel agar profesional, terpisah-pisah rapi, dan mudah dibaca.
     */
    public function styles(Worksheet $sheet)
    {
        // Style Header Row (Baris 1)
        $sheet->getStyle('A1:E1')->applyFromArray([
            'font' => [
                'bold'  => true,
                'color' => ['argb' => 'FFFFFFFF'],
                'size'  => 11,
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF1E293B'], // Dark Navy Slate (#1E293B)
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(28);

        // Style Data Rows (Baris 2 s.d. 5)
        $sheet->getStyle('A2:E5')->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['argb' => 'FFE2E8F0'],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Alignment kolom tertentu
        $sheet->getStyle('B2:B5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D2:D5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('E2:E5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Highlight baris contoh warna selang-seling halus
        $sheet->getStyle('A3:E3')->getFill()->applyFromArray([
            'fillType'   => Fill::FILL_SOLID,
            'startColor' => ['argb' => 'FFF8FAFC'],
        ]);
        $sheet->getStyle('A5:E5')->getFill()->applyFromArray([
            'fillType'   => Fill::FILL_SOLID,
            'startColor' => ['argb' => 'FFF8FAFC'],
        ]);

        return [];
    }
}
