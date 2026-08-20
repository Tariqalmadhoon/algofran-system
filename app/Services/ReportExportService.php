<?php

namespace App\Services;

use App\Jobs\GenerateReportExport;
use App\Models\ReportExport;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReportExportService
{
    public function __construct(private readonly StudentVisibilityService $visibility, private readonly AuditLogger $audit) {}

    public function request(string $type, array $filters, User $user): ReportExport
    {
        $export = DB::transaction(function () use ($type, $filters, $user) {
            $export = ReportExport::query()->create([
                'uuid' => (string) Str::uuid(), 'user_id' => $user->id, 'report_type' => $type,
                'format' => 'xlsx', 'filters' => $filters, 'status' => 'preparing',
            ]);
            $this->audit->record('report-export.requested', $export, newValues: ['report_type' => $type, 'filters' => $filters]);

            return $export;
        });

        if ($this->visibility->queryFor($user)->count() > 2000) {
            GenerateReportExport::dispatch($export->id);
        } else {
            GenerateReportExport::dispatchSync($export->id);
        }

        return $export->refresh();
    }
}
