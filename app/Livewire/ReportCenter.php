<?php

namespace App\Livewire;

use App\Models\Halaqa;
use App\Models\ReportExport;
use App\Models\UploadedReport;
use App\Services\AuditLogger;
use App\Services\PrivateFileService;
use App\Services\ReportDataService;
use App\Services\ReportExportService;
use App\Services\StudentVisibilityService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;
use Throwable;

class ReportCenter extends Component
{
    use WithFileUploads;

    public string $tab = 'reports';

    public string $reportType = 'management_summary';

    public string $dateFrom = '';

    public string $dateTo = '';

    public string $halaqaId = '';

    public string $studentId = '';

    public string $uploadTitle = '';

    public string $uploadType = 'management';

    public string $uploadPeriodStart = '';

    public string $uploadPeriodEnd = '';

    public string $uploadPrivacy = 'internal';

    public string $uploadNotes = '';

    public $uploadFile;

    public function mount(): void
    {
        Gate::authorize('reports.view');
        $this->dateFrom = today()->startOfMonth()->toDateString();
        $this->dateTo = today()->toDateString();
    }

    public function requestExport(ReportExportService $exports, ReportDataService $reports): void
    {
        Gate::authorize('reports.export');
        $this->validateFilters($reports);
        $export = $exports->request($this->reportType, $this->filters(), auth()->user());
        $this->tab = 'exports';
        session()->flash('success', $export->status === 'ready' ? 'تم إعداد ملف Excel وهو جاهز للتنزيل.' : 'أضيف التقرير إلى قائمة الانتظار، وسيصلك إشعار عند اكتماله.');
    }

    public function uploadReport(PrivateFileService $files, AuditLogger $audit): void
    {
        Gate::authorize('reports.upload');
        $data = $this->validate([
            'uploadTitle' => ['required', 'string', 'max:255'],
            'uploadType' => ['required', Rule::in(['management', 'academic', 'attendance', 'financial', 'other'])],
            'uploadPeriodStart' => ['nullable', 'date'],
            'uploadPeriodEnd' => ['nullable', 'date', 'after_or_equal:uploadPeriodStart'],
            'uploadPrivacy' => ['required', Rule::in(['internal', 'restricted'])],
            'uploadNotes' => ['nullable', 'string', 'max:3000'],
            'uploadFile' => ['required', 'file', 'mimes:pdf,xlsx,xls,csv,doc,docx,jpg,jpeg,png', 'max:20480'],
        ]);
        $storedPath = null;
        try {
            DB::transaction(function () use ($data, $files, $audit, &$storedPath): void {
                $report = UploadedReport::query()->create([
                    'title' => $data['uploadTitle'], 'type' => $data['uploadType'],
                    'period_start' => $data['uploadPeriodStart'] ?: null, 'period_end' => $data['uploadPeriodEnd'] ?: null,
                    'privacy' => $data['uploadPrivacy'], 'notes' => $data['uploadNotes'] ?: null, 'uploaded_by' => auth()->id(),
                ]);
                $file = $files->store($this->uploadFile, $report, auth()->user(), 'uploaded-reports/'.$report->id, 'uploaded-report');
                $storedPath = $file->path;
                $report->update(['private_file_id' => $file->id]);
                $audit->record('uploaded-report.created', $report, newValues: $report->only(['title', 'type', 'privacy', 'period_start', 'period_end']));
            });
        } catch (Throwable $exception) {
            if ($storedPath) {
                Storage::disk('private')->delete($storedPath);
            }
            throw $exception;
        }
        $this->reset('uploadTitle', 'uploadPeriodStart', 'uploadPeriodEnd', 'uploadNotes', 'uploadFile');
        $this->uploadType = 'management';
        $this->uploadPrivacy = 'internal';
        session()->flash('success', 'تم رفع التقرير وحفظه بصورة خاصة وآمنة.');
    }

    public function render(ReportDataService $reports, StudentVisibilityService $visibility): View
    {
        $preview = $reports->build($this->reportType, $this->filters(), auth()->user());
        $uploaded = UploadedReport::query()->with(['privateFile:id,original_name', 'uploader:id,name'])
            ->when(! auth()->user()->can('reports.upload'), fn (Builder $query) => $query->where('privacy', 'internal'))
            ->latest()->limit(100)->get();

        return view('livewire.report-center', [
            'types' => $reports->types(), 'preview' => $preview,
            'students' => $visibility->queryFor(auth()->user())->orderBy('full_name')->limit(1000)->get(['id', 'full_name', 'student_number']),
            'halaqas' => Halaqa::query()->where('active', true)->orderBy('name')->get(['id', 'name']),
            'exports' => ReportExport::query()->where('user_id', auth()->id())->with('privateFile:id,original_name')->latest()->limit(50)->get(),
            'uploadedReports' => $uploaded,
        ]);
    }

    private function filters(): array
    {
        return array_filter(['date_from' => $this->dateFrom ?: null, 'date_to' => $this->dateTo ?: null, 'halaqa_id' => $this->halaqaId ?: null, 'student_id' => $this->studentId ?: null], fn ($value) => $value !== null);
    }

    private function validateFilters(ReportDataService $reports): void
    {
        $this->validate([
            'reportType' => ['required', Rule::in(array_keys($reports->types()))],
            'dateFrom' => ['nullable', 'date'], 'dateTo' => ['nullable', 'date', 'after_or_equal:dateFrom'],
            'halaqaId' => ['nullable', 'exists:halaqas,id'], 'studentId' => ['nullable', 'exists:students,id'],
        ]);
    }
}
