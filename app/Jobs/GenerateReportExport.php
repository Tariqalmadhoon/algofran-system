<?php

namespace App\Jobs;

use App\Exports\ReportWorkbookExport;
use App\Models\PrivateFile;
use App\Models\ReportExport;
use App\Notifications\SystemNotification;
use App\Services\ReportDataService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class GenerateReportExport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 900;

    public int $tries = 2;

    public function __construct(public readonly int $reportExportId) {}

    public function handle(ReportDataService $reports): void
    {
        $export = ReportExport::query()->with('user')->findOrFail($this->reportExportId);
        if ($export->status === 'ready') {
            return;
        }

        $path = null;
        try {
            $report = $reports->build($export->report_type, $export->filters ?? [], $export->user);
            $path = 'report-exports/'.$export->user_id.'/'.$export->uuid.'.xlsx';
            Excel::store(new ReportWorkbookExport($report), $path, 'private', ExcelFormat::XLSX);
            $rows = isset($report['sheets']) ? array_sum(array_map(fn ($sheet) => count($sheet['rows']), $report['sheets'])) : count($report['rows']);
            DB::transaction(function () use ($export, $report, $path, $rows): void {
                $file = PrivateFile::query()->create([
                    'disk' => 'private', 'path' => $path,
                    'original_name' => $this->safeFilename($report['title']).'-'.now()->format('Y-m-d-His').'.xlsx',
                    'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'size' => Storage::disk('private')->size($path),
                    'owner_type' => $export->getMorphClass(), 'owner_id' => $export->id, 'uploaded_by' => $export->user_id,
                    'metadata' => ['category' => 'report-export', 'report_type' => $export->report_type],
                ]);
                $export->update(['status' => 'ready', 'private_file_id' => $file->id, 'rows_count' => $rows, 'completed_at' => now(), 'failure_message' => null]);
            });
        } catch (Throwable $exception) {
            if ($path) {
                Storage::disk('private')->delete($path);
            }
            $export->update(['status' => 'failed', 'failure_message' => 'تعذر إنشاء الملف. راجع سجل النظام باستخدام رقم العملية.', 'completed_at' => now()]);
            Log::error('Report export failed.', ['report_export_id' => $export->id, 'exception' => $exception]);
            try {
                $export->user?->notify(new SystemNotification('تعذّر تصدير التقرير', 'حدث خطأ أثناء إعداد ملف Excel. يمكنك إعادة المحاولة.', route('reports.index'), 'error'));
            } catch (Throwable $notificationException) {
                Log::warning('Failed to send report export failure notification.', ['report_export_id' => $export->id, 'exception' => $notificationException]);
            }
            throw $exception;
        }

        try {
            $export->user->notify(new SystemNotification('اكتمل تصدير التقرير', "أصبح {$report['title']} جاهزًا للتنزيل.", route('reports.index'), 'success'));
        } catch (Throwable $exception) {
            Log::warning('Failed to send report export completion notification.', ['report_export_id' => $export->id, 'exception' => $exception]);
        }
    }

    private function safeFilename(string $title): string
    {
        return trim(preg_replace('/[^\pL\pN_-]+/u', '-', $title), '-') ?: 'report';
    }
}
