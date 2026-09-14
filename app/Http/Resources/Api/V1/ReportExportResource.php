<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportExportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'report_type' => $this->report_type,
            'format' => $this->format,
            'filters' => $this->filters,
            'status' => $this->status,
            'rows_count' => $this->rows_count,
            'failure_message' => $this->failure_message,
            'completed_at' => $this->completed_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'download_url' => $this->when(
                $this->status === 'ready' && $this->private_file_id,
                fn () => route('api.v1.mobile.report-exports.download', ['reportExport' => $this->uuid]),
            ),
        ];
    }
}
