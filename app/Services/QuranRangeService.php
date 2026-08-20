<?php

namespace App\Services;

use App\Models\QuranAyah;
use Illuminate\Validation\ValidationException;

class QuranRangeService
{
    /** @return array{0: QuranAyah, 1: QuranAyah} */
    public function validate(int $startAyahId, int $endAyahId): array
    {
        $ayahs = QuranAyah::query()->whereKey([$startAyahId, $endAyahId])->get()->keyBy('id');
        $start = $ayahs->get($startAyahId);
        $end = $ayahs->get($endAyahId);

        if (! $start || ! $end) {
            throw ValidationException::withMessages(['quran_range' => 'موضع الآية المحدد غير موجود في المرجع المعتمد.']);
        }

        if ($end->global_order < $start->global_order) {
            throw ValidationException::withMessages(['quran_range' => 'نهاية نطاق التسميع يجب ألا تسبق بدايته.']);
        }

        return [$start, $end];
    }
}
