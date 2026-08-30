<?php

namespace App\Livewire;

use App\Models\CmsContent;
use App\Models\CmsMedia;
use App\Models\ContactMessage;
use App\Services\AuditLogger;
use App\Services\CmsService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;

class CmsManager extends Component
{
    use WithFileUploads;

    public string $tab = 'content';

    public string $typeFilter = '';

    public string $statusFilter = '';

    public string $contentSearch = '';

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

    public $contentImageFile;

    public string $contentImageAlt = '';

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

    public string $mediaSearch = '';

    public string $mediaKindFilter = '';

    public ?int $editingMediaId = null;

    public bool $showMediaEditor = false;

    public string $editMediaTitle = '';

    public string $editMediaAlt = '';

    public string $editMediaCaption = '';

    public bool $editMediaGallery = false;

    public int $editMediaSortOrder = 0;

    public string $messageSearch = '';

    public string $messageStatusFilter = '';

    public function mount(): void
    {
        Gate::authorize('website.manage');
    }

    public function newContent(): void
    {
        $this->resetContentForm();
        $this->showContentForm = true;
    }

    public function generateSlug(): void
    {
        $base = Str::slug($this->contentTitle);
        if ($base === '') {
            $base = $this->contentType.'-'.now()->format('Ymd-His');
        }

        $slug = $base;
        $suffix = 2;
        while (CmsContent::withTrashed()
            ->where('slug', $slug)
            ->when($this->editingContentId, fn ($query) => $query->where('id', '!=', $this->editingContentId))
            ->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        $this->contentSlug = $slug;
        $this->resetValidation('contentSlug');
    }

    public function updatedContentImageFile(): void
    {
        $this->contentFeaturedMediaId = '';
        $this->validateOnly('contentImageFile', [
            'contentImageFile' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);
    }

    public function updatedContentFeaturedMediaId(): void
    {
        if ($this->contentFeaturedMediaId !== '') {
            $this->reset('contentImageFile');
            $this->resetValidation('contentImageFile');
        }
    }

    public function removeContentImage(): void
    {
        $this->reset('contentImageFile', 'contentImageAlt');
        $this->resetValidation('contentImageFile');
    }

    public function editContent(int $id): void
    {
        Gate::authorize('website.manage');
        $this->reset('contentImageFile', 'contentImageAlt');
        $this->resetValidation();
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
            'contentImageFile' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'contentImageAlt' => ['nullable', 'string', 'max:255'],
            'contentMetaTitle' => ['nullable', 'string', 'max:255'], 'contentMetaDescription' => ['nullable', 'string', 'max:500'],
            'contentFeatured' => ['boolean'], 'contentSortOrder' => ['integer', 'between:0,65535'],
        ]);

        $featuredMediaId = $data['contentFeaturedMediaId'] ?: null;
        if ($this->contentImageFile) {
            $uploadedMedia = $cms->uploadMedia($this->contentImageFile, [
                'title' => $data['contentTitle'],
                'alt_text' => $data['contentImageAlt'] ?: $data['contentTitle'],
                'caption' => $data['contentExcerpt'] ?: null,
                'is_gallery' => false,
                'sort_order' => $data['contentSortOrder'],
            ], auth()->user());
            $featuredMediaId = $uploadedMedia->id;
        }

        $cms->saveContent([
            'type' => $data['contentType'], 'title' => $data['contentTitle'], 'slug' => $data['contentSlug'],
            'excerpt' => $data['contentExcerpt'] ?: null, 'body' => $data['contentBody'] ?: null, 'status' => $data['contentStatus'],
            'published_at' => $data['contentPublishedAt'] ?: null, 'featured_media_id' => $featuredMediaId,
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

    public function changeContentStatus(int $id, string $status, CmsService $cms): void
    {
        Gate::authorize('website.manage');
        abort_unless(in_array($status, ['draft', 'published', 'archived'], true), 422);

        $content = CmsContent::query()->findOrFail($id);
        $cms->changeContentStatus($content, $status, auth()->user());
        session()->flash('success', match ($status) {
            'published' => 'تم نشر المحتوى وأصبح ظاهرًا في الموقع.',
            'archived' => 'تمت أرشفة المحتوى وإخفاؤه عن الزوار.',
            default => 'أعيد المحتوى إلى المسودة.',
        });
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

    public function editMedia(int $id): void
    {
        Gate::authorize('website.manage');
        $media = CmsMedia::query()->findOrFail($id);
        $this->editingMediaId = $media->id;
        $this->editMediaTitle = $media->title ?? '';
        $this->editMediaAlt = $media->alt_text ?? '';
        $this->editMediaCaption = $media->caption ?? '';
        $this->editMediaGallery = $media->is_gallery;
        $this->editMediaSortOrder = $media->sort_order;
        $this->showMediaEditor = true;
        $this->resetValidation();
    }

    public function saveMedia(CmsService $cms): void
    {
        Gate::authorize('website.manage');
        $media = CmsMedia::query()->findOrFail($this->editingMediaId);
        $data = $this->validate([
            'editMediaTitle' => ['nullable', 'string', 'max:255'],
            'editMediaAlt' => ['nullable', 'string', 'max:255'],
            'editMediaCaption' => ['nullable', 'string', 'max:1000'],
            'editMediaGallery' => ['boolean'],
            'editMediaSortOrder' => ['integer', 'between:0,65535'],
        ]);

        $cms->updateMedia($media, [
            'title' => $data['editMediaTitle'] ?: null,
            'alt_text' => $data['editMediaAlt'] ?: null,
            'caption' => $data['editMediaCaption'] ?: null,
            'is_gallery' => $data['editMediaGallery'],
            'sort_order' => $data['editMediaSortOrder'],
        ], auth()->user());

        $this->resetMediaEditor();
        session()->flash('success', 'تم تحديث بيانات الوسيط.');
    }

    public function toggleMediaGallery(int $id, CmsService $cms): void
    {
        Gate::authorize('website.manage');
        $media = CmsMedia::query()->findOrFail($id);
        $cms->updateMedia($media, ['is_gallery' => ! $media->is_gallery], auth()->user());
        session()->flash('success', $media->is_gallery ? 'أضيفت الصورة إلى معرض الموقع.' : 'أزيلت الصورة من معرض الموقع.');
    }

    public function deleteMedia(int $id, CmsService $cms): void
    {
        Gate::authorize('website.manage');
        $media = CmsMedia::query()->withCount('featuredContents')->findOrFail($id);

        if ($media->featured_contents_count > 0) {
            $this->addError('mediaLibrary', 'لا يمكن حذف هذا الوسيط لأنه مستخدم كصورة بارزة في '.$media->featured_contents_count.' محتوى. غيّر الصورة البارزة أولًا.');

            return;
        }

        $cms->deleteMedia($media, auth()->user());
        session()->flash('success', 'تم حذف الوسيط من المكتبة والتخزين.');
    }

    public function handleMessage(int $id, CmsService $cms): void
    {
        Gate::authorize('website.manage');
        $cms->handleMessage(ContactMessage::query()->findOrFail($id), auth()->user());
        session()->flash('success', 'تم تعليم الرسالة كمعالجة.');
    }

    public function reopenMessage(int $id, CmsService $cms): void
    {
        Gate::authorize('website.manage');
        $cms->reopenMessage(ContactMessage::query()->findOrFail($id), auth()->user());
        session()->flash('success', 'أعيدت الرسالة إلى قائمة المتابعة.');
    }

    public function render(): View
    {
        $contents = CmsContent::query()->with('featuredMedia:id,disk,path,alt_text')
            ->when($this->contentSearch, function ($query): void {
                $term = '%'.trim($this->contentSearch).'%';
                $query->where(fn ($search) => $search
                    ->where('title', 'like', $term)
                    ->orWhere('slug', 'like', $term)
                    ->orWhere('excerpt', 'like', $term));
            })
            ->when($this->typeFilter, fn ($query) => $query->where('type', $this->typeFilter))
            ->when($this->statusFilter, fn ($query) => $query->where('status', $this->statusFilter))
            ->orderBy('sort_order')->latest()->limit(150)->get();

        $media = CmsMedia::query()->withCount('featuredContents')
            ->when($this->mediaSearch, function ($query): void {
                $term = '%'.trim($this->mediaSearch).'%';
                $query->where(fn ($search) => $search
                    ->where('title', 'like', $term)
                    ->orWhere('original_name', 'like', $term)
                    ->orWhere('alt_text', 'like', $term));
            })
            ->when($this->mediaKindFilter, fn ($query) => $query->where('kind', $this->mediaKindFilter))
            ->orderBy('sort_order')->latest()->limit(150)->get();

        $messages = ContactMessage::query()
            ->when($this->messageSearch, function ($query): void {
                $term = '%'.trim($this->messageSearch).'%';
                $query->where(fn ($search) => $search
                    ->where('name', 'like', $term)
                    ->orWhere('subject', 'like', $term)
                    ->orWhere('phone', 'like', $term)
                    ->orWhere('email', 'like', $term));
            })
            ->when($this->messageStatusFilter, fn ($query) => $query->where('status', $this->messageStatusFilter))
            ->latest()->limit(150)->get();

        return view('livewire.cms-manager', [
            'types' => $this->types(), 'contents' => $contents,
            'media' => $media,
            'mediaChoices' => CmsMedia::query()->where('kind', 'image')->orderBy('sort_order')->latest()->get(),
            'messages' => $messages,
            'statusCounts' => CmsContent::query()->selectRaw('status, count(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status'),
            'typeCounts' => CmsContent::query()->selectRaw('type, count(*) as aggregate')->groupBy('type')->pluck('aggregate', 'type'),
            'mediaCount' => CmsMedia::query()->count(),
            'galleryCount' => CmsMedia::query()->where('is_gallery', true)->count(),
            'newMessages' => ContactMessage::query()->where('status', 'new')->count(),
        ]);
    }

    private function resetContentForm(): void
    {
        $this->reset('editingContentId', 'contentTitle', 'contentSlug', 'contentExcerpt', 'contentBody', 'contentPublishedAt', 'contentFeaturedMediaId', 'contentImageFile', 'contentImageAlt', 'contentMetaTitle', 'contentMetaDescription', 'contentFeatured', 'contentSortOrder', 'showContentForm');
        $this->contentType = 'page';
        $this->contentStatus = 'draft';
        $this->resetValidation();
    }

    private function resetMediaEditor(): void
    {
        $this->reset('editingMediaId', 'showMediaEditor', 'editMediaTitle', 'editMediaAlt', 'editMediaCaption', 'editMediaGallery', 'editMediaSortOrder');
        $this->resetValidation();
    }

    private function types(): array
    {
        return ['page' => 'صفحة', 'news' => 'خبر', 'activity' => 'نشاط', 'announcement' => 'إعلان', 'achievement' => 'إنجاز عام'];
    }
}
