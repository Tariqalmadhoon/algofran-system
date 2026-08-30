<?php

namespace App\Exports;

use App\Models\Course;
use App\Models\CourseEnrollment;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\DefaultValueBinder;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AcademicCourseResultsExport extends DefaultValueBinder implements FromArray, ShouldAutoSize, WithColumnFormatting, WithCustomValueBinder, WithEvents, WithHeadings, WithStyles, WithTitle
{
    /** @param Collection<int, CourseEnrollment> $enrollments */
    public function __construct(
        private readonly Course $course,
        private readonly Collection $enrollments,
    ) {}

    public function array(): array
    {
        return $this->enrollments->values()->map(function ($enrollment, int $index): array {
            $student = $enrollment->student;
            $teacherName = $student->currentHalaqa?->primaryTeacher?->user?->name
                ?? $this->course->instructor?->user?->name
                ?? '—';

            return [
                $index + 1,
                $student->full_name,
                $student->identity_number ?: '—',
                $this->course->center->name,
                $teacherName,
                $this->course->name,
                $enrollment->result,
                $enrollment->grade ?: '—',
                $enrollment->completed_at ? ExcelDate::dateTimeToExcel($enrollment->completed_at) : null,
                $enrollment->notes ?: '—',
            ];
        })->all();
    }

    public function headings(): array
    {
        return [
            'متسلسل',
            'اسم الطالب',
            'رقم الهوية',
            'المركز',
            'المحفّظ',
            'الدورة المنجزة',
            'الدرجة من 100',
            'التقييم',
            'تاريخ الإنجاز',
            'الملاحظات',
        ];
    }

    public function title(): string
    {
        return mb_substr(str_replace(['\\', '/', '?', '*', '[', ']', ':'], '', $this->course->name), 0, 31);
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_NUMBER,
            'C' => NumberFormat::FORMAT_TEXT,
            'G' => '0.00',
            'I' => 'yyyy-mm-dd',
        ];
    }

    public function bindValue(Cell $cell, mixed $value): bool
    {
        if ($cell->getColumn() === 'C' || (is_string($value) && preg_match('/^[=+\-@]/u', $value))) {
            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->setRightToLeft(true);
        $sheet->freezePane('A2');
        $sheet->getStyle('1:1')->getFont()->setBold(true)->getColor()->setARGB(Color::COLOR_WHITE);
        $sheet->getStyle('1:1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF065F46');
        $sheet->getStyle('1:1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(30);

        return [1 => ['font' => ['bold' => true]]];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $lastRow = max(2, $this->enrollments->count() + 1);
                $sheet->setAutoFilter("A1:J{$lastRow}");
                $sheet->getStyle("A1:J{$lastRow}")->getFont()->setName('Arial')->setSize(11);
                $sheet->getStyle("A2:J{$lastRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle("B2:F{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle("H2:H{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("J2:J{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setWrapText(true);
                $sheet->getStyle("A1:J{$lastRow}")->getBorders()->getBottom()->setBorderStyle(Border::BORDER_HAIR)->setColor(new Color('FFDDE7E3'));
                $sheet->getColumnDimension('B')->setWidth(25);
                $sheet->getColumnDimension('C')->setWidth(18);
                $sheet->getColumnDimension('D')->setWidth(31);
                $sheet->getColumnDimension('E')->setWidth(22);
                $sheet->getColumnDimension('F')->setWidth(28);
                $sheet->getColumnDimension('H')->setWidth(16);
                $sheet->getColumnDimension('I')->setWidth(17);
                $sheet->getColumnDimension('J')->setWidth(38);
                $sheet->getPageSetup()->setOrientation('landscape')->setFitToWidth(1)->setFitToHeight(0);
                $sheet->getPageMargins()->setTop(0.4)->setBottom(0.4)->setLeft(0.3)->setRight(0.3);
            },
        ];
    }
}
