<?php

namespace App\Services;

use App\Models\CmsContent;
use App\Models\CmsMedia;
use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Throwable;

class CmsService
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function saveContent(array $data, User $actor, ?CmsContent $content = null): CmsContent
    {
        $content ??= new CmsContent;
        $old = $content->exists ? $content->getOriginal() : [];
        $content->fill($data + ['updated_by' => $actor->id]);
        if (! $content->exists) {
            $content->created_by = $actor->id;
        }
        if ($content->status === 'published' && ! $content->published_at) {
            $content->published_at = now();
        }
        $content->save();
        $this->audit->record($old ? 'cms-content.updated' : 'cms-content.created', $content, $old, $content->only(['type', 'slug', 'title', 'status', 'published_at', 'featured']));

        return $content;
    }

    public function uploadMedia(UploadedFile $upload, array $data, User $actor): CmsMedia
    {
        $path = $upload->store('cms/'.now()->format('Y/m'), 'public');
        try {
            $media = CmsMedia::query()->create([
                'disk' => 'public', 'path' => $path, 'original_name' => $upload->getClientOriginalName(),
                'mime_type' => $upload->getMimeType() ?: 'application/octet-stream', 'size' => $upload->getSize(),
                'kind' => str_starts_with((string) $upload->getMimeType(), 'image/') ? 'image' : (str_starts_with((string) $upload->getMimeType(), 'video/') ? 'video' : 'document'),
                'title' => $data['title'] ?: null, 'alt_text' => $data['alt_text'] ?: null, 'caption' => $data['caption'] ?: null,
                'is_gallery' => (bool) $data['is_gallery'], 'sort_order' => (int) ($data['sort_order'] ?? 0), 'uploaded_by' => $actor->id,
            ]);
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($path);
            throw $exception;
        }
        $this->audit->record('cms-media.uploaded', $media, newValues: $media->only(['kind', 'title', 'mime_type', 'size', 'is_gallery']));

        return $media;
    }

    public function handleMessage(ContactMessage $message, User $actor): void
    {
        $message->update(['status' => 'handled', 'handled_at' => now(), 'handled_by' => $actor->id]);
        $this->audit->record('contact-message.handled', $message, newValues: ['status' => 'handled']);
    }
}
