<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Center;
use App\Models\Halaqa;
use App\Models\StaffProfile;
use App\Models\TeacherProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class SequentialCodeService
{
    public function center(): string
    {
        return $this->next('organization.center', 'CTR', 3, Center::query()->withTrashed(), 'code');
    }

    public function branch(int $centerId): string
    {
        return $this->next(
            "organization.branch.{$centerId}",
            'BR',
            3,
            Branch::query()->withTrashed()->where('center_id', $centerId),
            'code',
        );
    }

    public function halaqa(int $branchId): string
    {
        return $this->next(
            "organization.halaqa.{$branchId}",
            'HLQ',
            3,
            Halaqa::query()->withTrashed()->where('branch_id', $branchId),
            'code',
        );
    }

    public function staffEmployeeNumber(): string
    {
        return $this->next('organization.employee.staff', 'STF', 4, StaffProfile::query(), 'employee_number');
    }

    public function teacherEmployeeNumber(): string
    {
        return $this->next('organization.employee.teacher', 'TCH', 4, TeacherProfile::query(), 'employee_number');
    }

    /**
     * Reserve the next identifier while the caller's database transaction is open.
     * Existing identifiers seed the counter, which keeps upgraded databases safe.
     */
    private function next(string $key, string $prefix, int $width, Builder $query, string $column): string
    {
        $pattern = '/^'.preg_quote($prefix, '/').'-(\d+)$/';
        $seed = $query
            ->where($column, 'like', $prefix.'-%')
            ->pluck($column)
            ->reduce(function (int $maximum, string $value) use ($pattern): int {
                return preg_match($pattern, $value, $matches)
                    ? max($maximum, (int) $matches[1])
                    : $maximum;
            }, 0);

        DB::table('number_sequences')->insertOrIgnore([
            'key' => $key,
            'current_value' => $seed,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $sequence = DB::table('number_sequences')
            ->where('key', $key)
            ->lockForUpdate()
            ->first();
        $nextValue = max((int) $sequence->current_value, $seed) + 1;

        DB::table('number_sequences')
            ->where('key', $key)
            ->update(['current_value' => $nextValue, 'updated_at' => now()]);

        return $prefix.'-'.str_pad((string) $nextValue, $width, '0', STR_PAD_LEFT);
    }
}
