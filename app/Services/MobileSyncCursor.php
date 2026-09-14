<?php

namespace App\Services;

use App\Models\DailyRecord;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;
use Throwable;

class MobileSyncCursor
{
    public function fromRecord(?DailyRecord $record = null): string
    {
        return $this->encode(
            $record?->updated_at?->toISOString() ?? now()->toISOString(),
            (int) ($record?->id ?? 0),
        );
    }

    /** @return array{updated_at:CarbonImmutable,id:int} */
    public function decode(string $cursor): array
    {
        try {
            $padding = strlen($cursor) % 4;
            if ($padding > 0) {
                $cursor .= str_repeat('=', 4 - $padding);
            }

            $decoded = json_decode(base64_decode(strtr($cursor, '-_', '+/'), true), true, 8, JSON_THROW_ON_ERROR);
            if (! is_array($decoded) || ! isset($decoded['updated_at'], $decoded['id'])) {
                throw new \RuntimeException('Invalid cursor payload.');
            }

            return [
                'updated_at' => CarbonImmutable::parse((string) $decoded['updated_at']),
                'id' => max(0, (int) $decoded['id']),
            ];
        } catch (Throwable) {
            throw ValidationException::withMessages([
                'cursor' => 'مؤشر المزامنة غير صالح. أعد تهيئة بيانات التطبيق.',
            ]);
        }
    }

    private function encode(string $updatedAt, int $id): string
    {
        return rtrim(strtr(base64_encode(json_encode([
            'updated_at' => $updatedAt,
            'id' => $id,
        ], JSON_THROW_ON_ERROR)), '+/', '-_'), '=');
    }
}
