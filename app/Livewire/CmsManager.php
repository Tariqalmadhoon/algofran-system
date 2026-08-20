<?php

namespace App\Livewire;

use App\Models\CmsContent;
use App\Models\CmsMedia;
use App\Models\ContactMessage;
use App\Services\AuditLogger;
use App\Services\CmsService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;

class CmsManager extends Component
{
    use WithFileUploads;

    public string $tab = 'content';

    public string $typeFilter = '';

    public string $statusFilter = '';

    public ?int $editingContentId = null;

    public bool $showContentForm = false;

    public string $contentType = 'page';

    public string $contentTitle = '';

    public string $contentSlug = '';

    public string $contentExcerpt = '';

    public string $contentBody = '';

    public string $contentStatus = 'draft';

    public string $contentPublishedAt = '';

    public string $contentFeaturedMediaId = '';

    public string $contentMetaTitle = '';

    public string $contentMetaDescription = '';

    public bool $contentFeatured = false;

    public int $contentSortOrder = 0;

    public $mediaFile;

    public string $mediaTitle = '';

    public string $mediaAlt = '';

    public string $mediaCaption = '';

    public bool $mediaGallery = true;

    public int $mediaSortOrder = 0;

    public function mount(): void
    {
        Gate::authorize('website.manage');
    }

    public function newContent(): void
    {
        $this->resetContentForm();
        $this->showContentForm = true;
    }

    public function editContent(int $id): void
    {
        Gate::authorize('website.manage');
        $content = CmsContent::query()->findOrFail($id);
        $this->editingContentId = $content->id;
        $this->contentType = $content->type;
        $this->contentTitle = $content->title;
        $this->contentSlug = $content->slug;
        $this->contentExcerpt = $content->excerpt ?? '';
        $this->contentBody = $content->body ?? '';
        $this->contentStatus = $content->status;
        $this->contentPublishedAt = $content->published_at?->format('Y-m-d\TH:i') ?? '';
        $this->contentFeaturedMediaId = (string) ($content->featured_media_id ?? '');
        $this->contentMetaTitle = $content->meta_title ?? '';
        $this->contentMetaDescription = $content->meta_description ?? '';
        $this->contentFeatured = $content->featured;
        $this->contentSortOrder = $content->sort_order;
        $this->showContentForm = true;
    }

    public function saveContent(CmsService $cms): void
    {
        Gate::authorize('website.manage');
        $content = $this->editingContentId ? CmsContent::query()->findOrFail($this->editingContentId) : null;
        $data = $this->validate([
            'contentType' => ['required', Rule::in(array_keys($this->types()))],
            'contentTitle' => ['required', 'string', 'max:255'],
            'contentSlug' => ['required', 'string', 'max:180', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('cms_contents', 'slug')->ignore($content)],
            'contentExcerpt' => ['nullable', 'string', 'max:1000'], 'contentBody' => ['nullable', 'string'],
            'contentStatus' => ['required', Rule::in(['draft', 'published', 'archived'])],
            'contentPublishedAt' => ['nullable', 'date'], 'contentFeaturedMediaId' => ['nullable', 'exists:cms_media,id'],
            'contentMetaTitle' => ['nullable', 'string', 'max:255'], 'contentMetaDescription' => ['nullable', 'string', 'max:500'],
            'contentFeatured' => ['boolean'], 'contentSortOrder' => ['integer', 'between:0,65535'],
        ]);
        $cms->saveContent([
            'type' => $data['contentType'], 'title' => $data['contentTitle'], 'slug' => $data['contentSlug'],
            'excerpt' => $data['contentExcerpt'] ?: null, 'body' => $data['contentBody'] ?: null, 'status' => $data['contentStatus'],
            'published_at' => $data['contentPublishedAt'] ?: null, 'featured_media_id' => $data['contentFeaturedMediaId'] ?: null,
            'meta_title' => $data['contentMetaTitle'] ?: null, 'meta_description' => $data['contentMetaDescription'] ?: null,
            'featured' => $data['contentFeatured'], 'sort_order' => $data['contentSortOrder'],
        ], auth()->user(), $content);
        $this->resetContentForm();
        session()->flash('success', $content ? 'تم تحديث المحتوى.' : 'تم إنشاء المحتوى.');
    }

    public function deleteContent(int $id, AuditLogger $audit): void
    {
        Gate::authorize('website.manage');
        $content = CmsContent::query()->findOrFail($id);
        $content->delete();
        $audit->record('cms-content.deleted', $content, oldValues: $content->only(['type', 'slug', 'title', 'status']));
        session()->flash('success', 'تم نقل المحتوى إلى الأرشيف المحذوف.');
    }

    public function uploadMedia(CmsService $cms): void
    {
        Gate::authorize('website.manage');
        $data = $this->validate([
            'mediaFile' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf,mp4', 'max:51200'],
            'mediaTitle' => ['nullable', 'string', 'max:255'], 'mediaAlt' => ['nullable', 'string', 'max:255'],
            'mediaCaption' => ['nullable', 'string', 'max:1000'], 'mediaGallery' => ['boolean'], 'mediaSortOrder' => ['integer', 'between:0,65535'],
        ]);
        $cms->uploadMedia($this->mediaFile, ['title' => $data['mediaTitle'], 'alt_text' => $data['mediaAlt'], 'caption' => $data['mediaCaption'], 'is_gallery' => $data['mediaGallery'], 'sort_order' => $data['mediaSortOrder']], auth()->user());
        $this->reset('mediaFile', 'mediaTitle', 'mediaAlt', 'mediaCaption', 'mediaSortOrder');
        $this->mediaGallery = true;
        session()->flash('success', 'تم رفع الوسيط وإضافته إلى المكتبة.');
    }

    public function handleMessage(int $id, CmsService $cms): void
    {
        Gate::authorize('website.manage');
        $cms->handleMessage(ContactMessage::query()->findOrFail($id), auth()->user());
        session()->flash('success', 'تم تعليم الرسالة كمعالجة.');
    }

    public function render(): View
    {
        $contents = CmsContent::query()->with('featuredMedia:id,disk,path,alt_text')
            ->when($this->typeFilter, fn ($query) => $query->where('type', $this->typeFilter))
            ->when($this->statusFilter, fn ($query) => $query->where('status', $this->statusFilter))
            ->latest()->limit(150)->get();

        return view('livewire.cms-manager', [
            'types' => $this->types(), 'contents' => $contents,
            'media' => CmsMedia::query()->latest()->limit(150)->get(),
            'mediaChoices' => CmsMedia::query()->where('kind', 'image')->latest()->get(['id', 'title', 'original_name']),
            'messages' => ContactMessage::query()->latest()->limit(150)->get(),
            'statusCounts' => CmsContent::query()->selectRaw('status, count(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status'),
            'newMessages' => ContactMessage::query()->where('status', 'new')->count(),
        ]);
    }

    private function resetContentForm(): void
    {
        $this->reset('editingContentId', 'contentTitle', 'contentSlug', 'contentExcerpt', 'contentBody', 'contentPublishedAt', 'contentFeaturedMediaId', 'contentMetaTitle', 'contentMetaDescription', 'contentFeatured', 'contentSortOrder', 'showContentForm');
        $this->contentType = 'page';
        $this->contentStatus = 'draft';
    }

    private function types(): array
    {
        return ['page' => 'صفحة', 'news' => 'خبر', 'activity' => 'نشاط', 'announcement' => 'إعلان', 'achievement' => 'إنجاز عام'];
    }
}
