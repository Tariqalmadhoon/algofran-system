<?php

namespace App\Policies;

use App\Models\DailyRecord;
use App\Models\Halaqa;
use App\Models\User;

class DailyRecordPolicy
{
    public function view(User $user, DailyRecord $record): bool
    {
        return $user->can('recitations.view') && $user->can('view', $record->student);
    }

    public function create(User $user, Halaqa $halaqa, ?string $recordDate = null): bool
    {
        if (! $user->can('recitations.create')) {
            return false;
        }

        if (! $user->requiresTeacherAssignmentScope()) {
            return true;
        }

        $date = $recordDate ?? today()->toDateString();

        return $halaqa->teacherAssignments()
            ->whereHas('teacher', fn ($query) => $query->where('user_id', $user->id))
            ->whereDate('starts_at', '<=', $date)
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhereDate('ends_at', '>=', $date))
            ->exists();
    }

    public function update(User $user, DailyRecord $record): bool
    {
        return $user->can('recitations.update')
            && (! $user->requiresTeacherAssignmentScope() || $record->teacher?->user_id === $user->id);
    }
}
