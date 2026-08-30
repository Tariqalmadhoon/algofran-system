<?php

namespace App\Http\Controllers;

use App\Models\PrivateFile;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PrivateFileController extends Controller
{
    public function show(PrivateFile $privateFile, AuditLogger $auditLogger): StreamedResponse
    {
        Gate::authorize('view', $privateFile);
        abort_unless(Storage::disk($privateFile->disk)->exists($privateFile->path), 404);
        $auditLogger->record('private-file.downloaded', $privateFile);

        return Storage::disk($privateFile->disk)->download($privateFile->path, $privateFile->original_name);
    }

    public function preview(PrivateFile $privateFile): StreamedResponse
    {
        Gate::authorize('view', $privateFile);
        abort_unless(str_starts_with($privateFile->mime_type, 'image/'), 415);
        abort_unless(Storage::disk($privateFile->disk)->exists($privateFile->path), 404);

        return Storage::disk($privateFile->disk)->response(
            $privateFile->path,
            $privateFile->original_name,
            ['Content-Type' => $privateFile->mime_type, 'Cache-Control' => 'private, max-age=300'],
            'inline',
        );
    }
}
