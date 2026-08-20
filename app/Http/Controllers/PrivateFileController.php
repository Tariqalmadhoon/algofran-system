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
}
