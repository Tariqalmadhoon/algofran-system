<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ReportExportResource;
use App\Models\Halaqa;
use App\Models\ReportExport;
use App\Services\AuditLogger;
use App\Services\MobileTeacherScopeService;
use App\Services\ReportDataService;
use App\Services\ReportExportService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MobileReportExportController extends Controller
{
    private const ALLOWED_TYPES = ['student_comprehensive', 'memorization_records'];

    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'per_page' => ['nullable', 'integer', 'between:1,50'],
        ]);

        return ReportExportResource::collection(
            ReportExport::query()
                ->where('user_id', $request->user()->id)
                ->whereIn('report_type', self::ALLOWED_TYPES)
                ->latest()
                ->paginate((int) ($filters['per_page'] ?? 20)),
        );
    }

    public function store(
        Request $request,
        MobileTeacherScopeService $scope,
        ReportDataService $reports,
        ReportExportService $exports,
    ): ReportExportResource {
        $data = $request->validate([
            'report_type' => ['required', Rule::in(self::ALLOWED_TYPES)],
            'halaqa_id' => ['nullable', 'integer', 'exists:halaqas,id'],
            'date_from' => ['nullable', 'date', 'required_if:report_type,memorization_records', 'before_or_equal:date_to'],
            'date_to' => ['nullable', 'date', 'required_if:report_type,memorization_records', 'after_or_equal:date_from', 'before_or_equal:today'],
        ]);

        $user = $request->user();
        abort_unless($user->can('recitations.export'), 403);
        $teacher = $scope->teacher($user);

        if (isset($data['halaqa_id'])) {
            $halaqa = Halaqa::query()->findOrFail($data['halaqa_id']);
            abort_unless($scope->canAccessHalaqa($user, $halaqa), 403);
        }

        if ($data['report_type'] === 'student_comprehensive') {
            abort_unless($reports->canBuildComprehensive($user), 403);
        }

        $filters = array_filter([
            'teacher_profile_id' => $teacher->id,
            'halaqa_id' => $data['halaqa_id'] ?? null,
            'date_from' => $data['date_from'] ?? null,
            'date_to' => $data['date_to'] ?? null,
        ], fn ($value) => $value !== null);

        $export = $exports->request($data['report_type'], $filters, $user);

        return new ReportExportResource($export);
    }

    public function show(Request $request, ReportExport $reportExport): ReportExportResource
    {
        $this->authorizeOwner($request, $reportExport);

        return new ReportExportResource($reportExport);
    }

    public function download(
        Request $request,
        ReportExport $reportExport,
        AuditLogger $audit,
    ): StreamedResponse {
        $this->authorizeOwner($request, $reportExport);
        abort_unless($reportExport->status === 'ready' && $reportExport->private_file_id, 409, 'ملف التصدير ليس جاهزًا بعد.');

        $file = $reportExport->privateFile()->firstOrFail();
        Gate::authorize('view', $file);
        abort_unless(Storage::disk($file->disk)->exists($file->path), 404);
        $audit->record('report-export.downloaded', $reportExport, actor: $request->user());

        return Storage::disk($file->disk)->download($file->path, $file->original_name, [
            'Content-Type' => $file->mime_type,
        ]);
    }

    private function authorizeOwner(Request $request, ReportExport $reportExport): void
    {
        abort_unless(
            $reportExport->user_id === $request->user()->id
                && in_array($reportExport->report_type, self::ALLOWED_TYPES, true),
            403,
        );
    }
}
