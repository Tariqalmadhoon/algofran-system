<?php

namespace App\Exports;

use DateTimeImmutable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class ComprehensiveStudentReportExport extends DefaultValueBinder implements FromArray, WithColumnFormatting, WithCustomValueBinder, WithEvents, WithHeadings, WithStrictNullComparison, WithTitle
{
    private const LAST_COLUMN = 'R';

    public function __construct(private readonly array $rows) {}

    public function array(): array
    {
        return array_map(function (array $row): array {
            if (isset($row[4]) && is_string($row[4]) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $row[4])) {
                $row[4] = ExcelDate::dateTimeToExcel(new DateTimeImmutable($row[4]));
            }

            return array_map(fn ($value) => $this->safeValue($value), $row);
        }, $this->rows);
    }

    public function headings(): array
    {
        return [
            ['', '', '', '', '', '', '', '', '', '', '', '', 'آخر سرد أتمه الطالب (بالأجزاء)', '', 'إجمالي الحفظ (بالأجزاء)', '', 'آخر إنجاز حالي', ''],
            [
                'متسلسل', 'المركز', 'اسم الطالب رباعيًا', 'رقم هوية الطالب', 'تاريخ الميلاد',
                'رقم هوية ولي الأمر', 'اسم ولي الأمر', 'صلة القرابة لولي الأمر', 'نوع الكفالة', 'جهة الكفالة',
                'اسم المعلم رباعيًا', 'رقم هوية المعلم', 'السرد من', 'السرد إلى', 'الحفظ من', 'الحفظ إلى', 'السورة', 'الآية',
            ],
        ];
    }

    public function title(): string
    {
        return 'الكشف الشامل';
    }

    public function columnFormats(): array
    {
        return [
            'D' => '@',
            'E' => 'yyyy-mm-dd',
            'F' => '@',
            'L' => '@',
            'M' => '0',
            'N' => '0',
            'O' => '0',
            'P' => '0',
            'R' => '0',
        ];
    }

    public function bindValue(Cell $cell, $value): bool
    {
        if (in_array($cell->getColumn(), ['D', 'F', 'L'], true)) {
            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $lastRow = max(2, count($this->rows) + 2);
                $sheet->setRightToLeft(true);
                $sheet->setShowGridlines(false);
                $sheet->freezePane('A3');
                $sheet->mergeCells('M1:N1');
                $sheet->mergeCells('O1:P1');
                $sheet->mergeCells('Q1:R1');
                $sheet->setAutoFilter('A2:'.self::LAST_COLUMN.$lastRow);
                $sheet->getPageSetup()
                    ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
                    ->setPaperSize(PageSetup::PAPERSIZE_A3)
                    ->setFitToWidth(1)
                    ->setFitToHeight(0);
                $sheet->getPageMargins()->setTop(0.35)->setRight(0.3)->setBottom(0.35)->setLeft(0.3);
                $sheet->getHeaderFooter()->setOddFooter('&Cصفحة &P من &N');

                $sheet->getStyle('A1:'.self::LAST_COLUMN.$lastRow)->getFont()->setName('Tajawal')->setSize(10);
                $sheet->getStyle('A1:'.self::LAST_COLUMN.'2')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['argb' => 'FF172033']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF2C40F']],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                    'borders' => [
                        'bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => 'FF8A6D00']],
                        'vertical' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF9F830E']],
                    ],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(30);
                $sheet->getRowDimension(2)->setRowHeight(38);
                $sheet->getStyle('A3:'.self::LAST_COLUMN.$lastRow)->applyFromArray([
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                    'borders' => [
                        'bottom' => ['borderStyle' => Border::BORDER_HAIR, 'color' => ['argb' => 'FFD8DEE7']],
                    ],
                ]);
                $sheet->getStyle("A3:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("E3:E{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("M3:R{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                foreach (range(3, $lastRow) as $row) {
                    $sheet->getRowDimension($row)->setRowHeight(26);
                    if ($row % 2 === 0) {
                        $sheet->getStyle("A{$row}:".self::LAST_COLUMN.$row)
                            ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFFBEB');
                    }
                }

                foreach ([
                    'A' => 10, 'B' => 28, 'C' => 29, 'D' => 19, 'E' => 16, 'F' => 20,
                    'G' => 27, 'H' => 22, 'I' => 19, 'J' => 24, 'K' => 27, 'L' => 19,
                    'M' => 13, 'N' => 13, 'O' => 13, 'P' => 13, 'Q' => 19, 'R' => 10,
                ] as $column => $width) {
                    $sheet->getColumnDimension($column)->setWidth($width);
                }

                $sheet->getSheetView()->setZoomScale(80);
            },
        ];
    }

    private function safeValue(mixed $value): mixed
    {
        if (is_string($value) && preg_match('/^[=+\-@]/u', $value)) {
            return "'{$value}";
        }

        return $value;
    }
}
