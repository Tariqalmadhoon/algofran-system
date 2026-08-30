<?php

namespace App\Services;

use App\Models\CmsContent;
use App\Models\CmsMedia;
use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
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

    public function updateMedia(CmsMedia $media, array $data, User $actor): CmsMedia
    {
        $old = $media->only(['title', 'alt_text', 'caption', 'is_gallery', 'sort_order']);
        $media->update($data);
        $this->audit->record(
            'cms-media.updated',
            $media,
            $old,
            $media->only(['title', 'alt_text', 'caption', 'is_gallery', 'sort_order']),
            $actor,
        );

        return $media->refresh();
    }

    public function deleteMedia(CmsMedia $media, User $actor): void
    {
        $disk = $media->disk;
        $path = $media->path;
        $old = $media->only(['kind', 'title', 'original_name', 'path', 'is_gallery']);

        DB::transaction(function () use ($media, $actor, $old): void {
            $media->delete();
            $this->audit->record('cms-media.deleted', $media, $old, actor: $actor);
        });

        Storage::disk($disk)->delete($path);
    }

    public function changeContentStatus(CmsContent $content, string $status, User $actor): CmsContent
    {
        $old = $content->only(['status', 'published_at']);
        $content->status = $status;

        if ($status === 'published') {
            $content->published_at = now();
        }

        $content->updated_by = $actor->id;
        $content->save();
        $this->audit->record(
            'cms-content.status-changed',
            $content,
            $old,
            $content->only(['status', 'published_at']),
            $actor,
        );

        return $content;
    }

    public function handleMessage(ContactMessage $message, User $actor): void
    {
        $message->update(['status' => 'handled', 'handled_at' => now(), 'handled_by' => $actor->id]);
        $this->audit->record('contact-message.handled', $message, newValues: ['status' => 'handled']);
    }

    public function reopenMessage(ContactMessage $message, User $actor): void
    {
        $old = $message->only(['status', 'handled_at', 'handled_by']);
        $message->update(['status' => 'new', 'handled_at' => null, 'handled_by' => null]);
        $this->audit->record('contact-message.reopened', $message, $old, ['status' => 'new'], $actor);
    }
}
