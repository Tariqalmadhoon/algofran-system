<?php

namespace App\Services;

use App\Models\PrivateFile;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Throwable;

class PrivateFileService
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function store(UploadedFile $upload, Model $owner, User $uploader, string $directory, string $category): PrivateFile
    {
        $path = $upload->store($directory, 'private');

        try {
            $file = PrivateFile::query()->create([
                'disk' => 'private',
                'path' => $path,
                'original_name' => $upload->getClientOriginalName(),
                'mime_type' => $upload->getMimeType() ?: 'application/octet-stream',
                'size' => $upload->getSize(),
                'owner_type' => $owner->getMorphClass(),
                'owner_id' => $owner->getKey(),
                'uploaded_by' => $uploader->id,
                'metadata' => ['category' => $category],
            ]);
        } catch (Throwable $exception) {
            Storage::disk('private')->delete($path);
            throw $exception;
        }

        $this->auditLogger->record('private-file.uploaded', $file, newValues: [
            'owner_type' => $file->owner_type,
            'owner_id' => $file->owner_id,
            'mime_type' => $file->mime_type,
            'size' => $file->size,
            'category' => $category,
        ]);

        return $file;
    }
}
