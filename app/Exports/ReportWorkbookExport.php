<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ReportWorkbookExport implements WithMultipleSheets
{
    public function __construct(private readonly array $report) {}

    public function sheets(): array
    {
        $sheets = $this->report['sheets'] ?? [[
            'title' => $this->report['title'], 'headings' => $this->report['headings'], 'rows' => $this->report['rows'],
        ]];

        return array_map(fn (array $sheet) => new ReportSheetExport($sheet['title'], $sheet['headings'], $sheet['rows']), $sheets);
    }
}
