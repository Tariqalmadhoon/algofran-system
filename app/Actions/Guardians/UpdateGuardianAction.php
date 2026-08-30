<?php

namespace App\Actions\Guardians;

use App\Models\Guardian;
use App\Models\Student;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\StudentTimelineService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateGuardianAction
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly StudentTimelineService $timeline,
    ) {}

    public function execute(Student $student, Guardian $guardian, array $data, User $actor): Guardian
    {
        if (! $actor->can('update', $student)) {
            throw new AuthorizationException;
        }

        return DB::transaction(function () use ($student, $guardian, $data, $actor) {
            $guardian = Guardian::query()->lockForUpdate()->findOrFail($guardian->id);
            $linkedGuardian = $student->guardians()->whereKey($guardian->id)->first();

            if (! $linkedGuardian) {
                throw new AuthorizationException;
            }

            if (! empty($data['identity_number']) && Guardian::query()
                ->where('identity_number', $data['identity_number'])
                ->whereKeyNot($guardian->id)
                ->exists()) {
                throw ValidationException::withMessages([
                    'guardianIdentityNumber' => 'رقم الهوية مستخدم لولي أمر آخر.',
                ]);
            }

            $oldValues = $guardian->only([
                'full_name', 'identity_number', 'phone', 'alternative_phone', 'email', 'notes',
            ]);
            $guardian->update([
                'full_name' => $data['full_name'],
                'identity_number' => $data['identity_number'] ?: null,
                'phone' => $data['phone'],
                'alternative_phone' => $data['alternative_phone'] ?: null,
                'email' => $data['email'] ?: null,
                'notes' => $data['notes'] ?: null,
                'updated_by' => $actor->id,
            ]);

            if ($data['is_primary']) {
                $student->guardians()->newPivotStatement()
                    ->where('student_id', $student->id)
                    ->where('guardian_id', '!=', $guardian->id)
                    ->update(['is_primary' => false]);
            }

            $student->guardians()->updateExistingPivot($guardian->id, [
                'relationship' => $data['relationship'],
                'is_primary' => $data['is_primary'],
                'can_receive_notifications' => $data['can_receive_notifications'],
            ]);

            $this->timeline->record(
                $student,
                'guardian.updated',
                'تحديث بيانات ولي الأمر',
                $guardian,
                $guardian->full_name,
                ['relationship' => $data['relationship']],
            );
            $this->auditLogger->record(
                'guardian.updated',
                $guardian,
                oldValues: $oldValues,
                newValues: $guardian->only(array_keys($oldValues)),
                actor: $actor,
            );

            return $guardian->refresh();
        });
    }
}
