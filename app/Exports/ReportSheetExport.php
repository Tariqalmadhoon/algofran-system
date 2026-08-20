<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReportSheetExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    public function __construct(private readonly string $sheetTitle, private readonly array $columns, private readonly array $data) {}

    public function array(): array
    {
        return $this->data;
    }

    public function headings(): array
    {
        return $this->columns;
    }

    public function title(): string
    {
        return mb_substr(str_replace(['\\', '/', '?', '*', '[', ']', ':'], '', $this->sheetTitle), 0, 31);
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->setRightToLeft(true);
        $sheet->freezePane('A2');
        $sheet->getStyle('1:1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('1:1')->getFill()->setFillType('solid')->getStartColor()->setARGB('FF116149');

        return [1 => ['font' => ['bold' => true]]];
    }
}
