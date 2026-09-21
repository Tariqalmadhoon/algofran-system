<?php

namespace App\Livewire;

use App\Actions\Mobile\PublishAndroidReleaseAction;
use App\Services\AndroidReleaseService;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;

class MobileDistributionManager extends Component
{
    use WithFileUploads;

    public string $version = '';

    public string $versionCode = '';

    public string $minimumVersionCode = '1';

    public string $releaseNotes = '';

    public $apk;

    public $checksum;

    public $manifest;

    public function mount(AndroidReleaseService $releases): void
    {
        abort_unless(auth()->user()?->hasRole('super-admin'), 403);

        $current = $releases->current();
        $this->version = $current['version_name'] ?? (string) config('system.mobile_app.version');
        $this->versionCode = (string) ($current === null
            ? (int) config('system.mobile_app.version_code')
            : $current['version_code'] + 1);
        $this->minimumVersionCode = (string) ($current['minimum_version_code'] ?? 1);
    }

    public function publish(PublishAndroidReleaseAction $publisher): void
    {
        abort_unless(auth()->user()?->hasRole('super-admin'), 403);

        $maxSize = max(1024, (int) config('system.mobile_app.upload_max_kilobytes', 131072));
        $data = $this->validate([
            'version' => ['required', 'regex:/^\d+\.\d+\.\d+$/'],
            'versionCode' => ['required', 'integer', 'min:1'],
            'minimumVersionCode' => ['required', 'integer', 'min:1', 'lte:versionCode'],
            'releaseNotes' => ['nullable', 'string', 'max:2000'],
            'apk' => ['required', 'file', 'extensions:apk', "max:{$maxSize}"],
            'checksum' => ['required', 'file', 'extensions:sha256,txt', 'max:16'],
            'manifest' => ['required', 'file', 'extensions:json', 'max:128'],
        ]);

        $release = $publisher->execute(auth()->user(), [
            'version' => $data['version'],
            'version_code' => (int) $data['versionCode'],
            'minimum_version_code' => (int) $data['minimumVersionCode'],
            'release_notes' => $data['releaseNotes'] ?: null,
        ], $data['apk'], $data['checksum'], $data['manifest']);

        $this->reset(['apk', 'checksum', 'manifest', 'releaseNotes']);
        $this->version = $release['version_name'];
        $this->versionCode = (string) ($release['version_code'] + 1);
        $this->minimumVersionCode = (string) $release['minimum_version_code'];
        session()->flash('success', 'نُشر إصدار التطبيق بنجاح. يظهر زر التحميل الآن للمحفظين المصرح لهم، وسيصل تنبيه التحديث داخل التطبيق عند الاتصال.');
    }

    public function render(AndroidReleaseService $releases): View
    {
        $release = $releases->current();

        return view('livewire.mobile-distribution-manager', [
            'release' => $release,
            'teacherAppUrl' => route('teacher.mobile.app'),
            'downloadUrl' => $release === null ? null : route('teacher.mobile.app.download', [
                'versionCode' => $release['version_code'],
            ]),
            'maxUploadMegabytes' => max(1, (int) ceil(config('system.mobile_app.upload_max_kilobytes', 131072) / 1024)),
        ]);
    }
}
