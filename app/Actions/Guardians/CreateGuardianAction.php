<?php

namespace App\Actions\Guardians;

use App\Models\Guardian;
use App\Models\Student;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\StudentTimelineService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateGuardianAction
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly StudentTimelineService $timeline,
    ) {}

    public function execute(Student $student, array $data, User $actor): Guardian
    {
        return DB::transaction(function () use ($student, $data, $actor) {
            $guardian = ! empty($data['identity_number'])
                ? Guardian::query()->firstWhere('identity_number', $data['identity_number'])
                : null;

            if (! $guardian) {
                $guardian = Guardian::query()->create([
                    'full_name' => $data['full_name'],
                    'identity_number' => $data['identity_number'] ?: null,
                    'phone' => $data['phone'],
                    'alternative_phone' => $data['alternative_phone'] ?: null,
                    'email' => $data['email'] ?: null,
                    'notes' => $data['notes'] ?: null,
                    'created_by' => $actor->id,
                    'updated_by' => $actor->id,
                ]);
                $this->auditLogger->record('guardian.created', $guardian, newValues: $guardian->getAttributes());
            }

            if ($student->guardians()->whereKey($guardian->id)->exists()) {
                throw ValidationException::withMessages(['guardianIdentityNumber' => 'ولي الأمر مرتبط بهذا الطالب بالفعل.']);
            }

            if ($data['is_primary']) {
                $student->guardians()->newPivotStatement()
                    ->where('student_id', $student->id)
                    ->update(['is_primary' => false]);
            }

            $student->guardians()->attach($guardian->id, [
                'relationship' => $data['relationship'],
                'is_primary' => $data['is_primary'],
                'can_receive_notifications' => $data['can_receive_notifications'],
            ]);
            $this->timeline->record($student, 'guardian.attached', 'ربط ولي أمر', $guardian, $guardian->full_name, ['relationship' => $data['relationship']]);
            $this->auditLogger->record('student.guardian.attached', $student, newValues: ['guardian_id' => $guardian->id, 'relationship' => $data['relationship']]);

            return $guardian;
        });
    }
}
