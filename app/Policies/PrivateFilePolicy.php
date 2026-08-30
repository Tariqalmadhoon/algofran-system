<?php

namespace App\Policies;

use App\Models\Certificate;
use App\Models\Guardian;
use App\Models\PrivateFile;
use App\Models\ReportExport;
use App\Models\Student;
use App\Models\UploadedReport;
use App\Models\User;

class PrivateFilePolicy
{
    public function view(User $user, PrivateFile $file): bool
    {
        $owner = $file->owner;
        $category = $file->metadata['category'] ?? null;

        if ($owner instanceof User) {
            return $owner->is($user) || $user->can('private-files.view');
        }

        if ($owner instanceof Guardian) {
            if ($owner->user_id === $user->id) {
                return true;
            }

            if ($user->can('guardian.private-data.view')) {
                return true;
            }

            return $owner->students()->get()->contains(
                fn (Student $student) => $user->can('update', $student),
            );
        }

        if ($owner instanceof Student) {
            if ($owner->user_id === $user->id) {
                return true;
            }

            if (in_array($category, ['identity_document', 'student-identity'], true)) {
                return $user->can('guardian.private-data.view') || $user->can('update', $owner);
            }

            return $user->can('view', $owner);
        }

        if ($owner instanceof Certificate) {
            return $user->can('certificates.manage') || $user->can('view', $owner->student);
        }

        if ($owner instanceof ReportExport) {
            return $owner->user_id === $user->id;
        }

        if ($owner instanceof UploadedReport) {
            if ($owner->uploaded_by === $user->id) {
                return true;
            }
            if ($owner->privacy === 'restricted') {
                return $user->can('reports.upload');
            }

            return $user->can('reports.view');
        }

        return $user->can('private-files.view');
    }
}
