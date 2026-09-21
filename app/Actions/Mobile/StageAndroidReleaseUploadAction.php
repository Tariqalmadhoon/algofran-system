<?php

namespace App\Actions\Mobile;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class StageAndroidReleaseUploadAction
{
    public const CHUNK_BYTES = 4 * 1024 * 1024;

    public const MAX_BYTES = 512 * 1024 * 1024;

    /**
     * Store one small, authenticated part of an Android release and assemble it
     * only after every part is present. This avoids a single long-lived upload
     * request through shared-hosting proxies.
     *
     * @param  array{upload_id: string, index: int, total: int, total_size: int}  $data
     * @return array{complete: bool, upload_token: string|null}
     */
    public function stage(User $actor, array $data, UploadedFile $chunk): array
    {
        $uploadId = $this->validatedUploadId($data['upload_id']);
        $index = (int) $data['index'];
        $total = (int) $data['total'];
        $totalSize = (int) $data['total_size'];

        if ($index < 0 || $total < 1 || $index >= $total || $total > 128 || $totalSize < 1 || $totalSize > self::MAX_BYTES) {
            throw ValidationException::withMessages(['chunk' => 'بيانات تقسيم ملف APK غير صالحة.']);
        }

        $size = (int) ($chunk->getSize() ?? 0);
        if ($size < 1 || $size > self::CHUNK_BYTES) {
            throw ValidationException::withMessages(['chunk' => 'حجم جزء الرفع غير مسموح به.']);
        }

        $disk = Storage::disk('private');
        $directory = $this->directory($actor, $uploadId);
        $chunk->storeAs($directory, $this->partName($index), 'private');

        if (! $this->hasEveryPart($disk, $directory, $total)) {
            return ['complete' => false, 'upload_token' => null];
        }

        $assembled = $directory.'/release.apk';
        $disk->delete($assembled);
        $target = fopen($disk->path($assembled), 'wb');

        if ($target === false) {
            throw new RuntimeException('Unable to prepare the Android release staging file.');
        }

        try {
            foreach (range(0, $total - 1) as $part) {
                $source = $disk->readStream($directory.'/'.$this->partName($part));

                if ($source === false) {
                    throw new RuntimeException('An Android release upload part is missing.');
                }

                try {
                    stream_copy_to_stream($source, $target);
                } finally {
                    fclose($source);
                }
            }
        } finally {
            fclose($target);
        }

        if ($disk->size($assembled) !== $totalSize) {
            $disk->delete($assembled);

            throw ValidationException::withMessages(['chunk' => 'لم يكتمل ملف APK بالحجم المتوقع. أعد اختياره وحاول مجددًا.']);
        }

        return ['complete' => true, 'upload_token' => $uploadId];
    }

    public function uploadedFile(User $actor, string $uploadToken): UploadedFile
    {
        $path = $this->directory($actor, $this->validatedUploadId($uploadToken)).'/release.apk';
        $disk = Storage::disk('private');

        if (! $disk->exists($path) || $disk->size($path) < 1 || $disk->size($path) > self::MAX_BYTES) {
            throw ValidationException::withMessages(['apkUploadToken' => 'ملف APK المرحلي غير موجود أو لم يكتمل. أعد رفعه.']);
        }

        return new UploadedFile(
            $disk->path($path),
            'release.apk',
            'application/vnd.android.package-archive',
            null,
            true,
        );
    }

    public function discard(User $actor, ?string $uploadToken): void
    {
        if (! is_string($uploadToken) || ! Str::isUuid($uploadToken)) {
            return;
        }

        Storage::disk('private')->deleteDirectory($this->directory($actor, $uploadToken));
    }

    private function hasEveryPart($disk, string $directory, int $total): bool
    {
        foreach (range(0, $total - 1) as $part) {
            if (! $disk->exists($directory.'/'.$this->partName($part))) {
                return false;
            }
        }

        return true;
    }

    private function directory(User $actor, string $uploadId): string
    {
        return 'releases/.uploads/'.$actor->getKey().'/'.$uploadId;
    }

    private function partName(int $index): string
    {
        return sprintf('%05d.part', $index);
    }

    private function validatedUploadId(string $uploadId): string
    {
        if (! Str::isUuid($uploadId)) {
            throw ValidationException::withMessages(['upload_id' => 'معرّف الرفع غير صالح.']);
        }

        return $uploadId;
    }
}
