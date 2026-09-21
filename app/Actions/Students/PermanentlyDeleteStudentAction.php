<?php

namespace App\Actions\Students;

use App\Models\PrivateFile;
use App\Models\Student;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class PermanentlyDeleteStudentAction
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function execute(Student $student, User $actor): void
    {
        /** @var Collection<int, PrivateFile> $files */
        $files = DB::transaction(function () use ($student, $actor): Collection {
            $student = Student::withTrashed()->lockForUpdate()->findOrFail($student->id);
            abort_unless($student->trashed(), 422, 'لا يمكن الحذف النهائي إلا من سلة المهملات.');

            $files = PrivateFile::query()
                ->where('owner_type', $student->getMorphClass())
                ->where('owner_id', $student->id)
                ->get();

            $this->audit->record('student.permanently_deleted', $student, $student->getAttributes(), [], $actor);
            $student->forceDelete();

            return $files;
        });

        foreach ($files as $file) {
            try {
                Storage::disk($file->disk)->delete($file->path);
                $file->forceDelete();
            } catch (Throwable $exception) {
                report($exception);
            }
        }
    }
}
