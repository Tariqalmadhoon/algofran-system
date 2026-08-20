<?php

namespace App\Services;

use App\Enums\AchievementType;
use App\Enums\CourseEnrollmentStatus;
use App\Enums\CourseStatus;
use App\Models\Achievement;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AcademicRecordsService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly StudentTimelineService $timeline,
    ) {}

    public function createCourse(array $data, User $actor): Course
    {
        return DB::transaction(function () use ($data, $actor) {
            $course = Course::query()->create([
                ...$data,
                'status' => CourseStatus::from($data['status'])->value,
                'created_by' => $actor->id,
            ]);
            $this->auditLogger->record('course.created', $course, newValues: $course->getAttributes());

            return $course;
        });
    }

    public function enrollStudent(Course $course, Student $student, array $data, User $actor): CourseEnrollment
    {
        return DB::transaction(function () use ($course, $student, $data, $actor) {
            $enrollment = CourseEnrollment::query()->updateOrCreate(
                ['course_id' => $course->id, 'student_id' => $student->id],
                [
                    'enrolled_at' => $data['enrolled_at'],
                    'status' => CourseEnrollmentStatus::from($data['status'])->value,
                    'result' => $data['result'] ?? null,
                    'grade' => $data['grade'] ?? null,
                    'completed_at' => $data['completed_at'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'enrolled_by' => $actor->id,
                ],
            );
            $eventType = $enrollment->status === CourseEnrollmentStatus::Completed
                ? 'course.completed'
                : ($enrollment->wasRecentlyCreated ? 'course.enrolled' : 'course.updated');
            $this->timeline->record(
                $student,
                $eventType,
                $enrollment->status === CourseEnrollmentStatus::Completed ? 'إكمال دورة' : 'تحديث تسجيل دورة',
                $enrollment,
                $course->name,
                ['course_id' => $course->id, 'status' => $enrollment->status->value, 'result' => $enrollment->result],
                $enrollment->completed_at ?? $enrollment->enrolled_at,
            );
            $this->auditLogger->record('course.enrollment.saved', $enrollment, newValues: $enrollment->getAttributes());

            return $enrollment;
        });
    }

    public function issueCertificate(Student $student, array $data, User $actor): Certificate
    {
        return DB::transaction(function () use ($student, $data, $actor) {
            $certificate = Certificate::query()->create([
                ...$data,
                'student_id' => $student->id,
                'issued_by' => $actor->id,
            ]);
            $this->timeline->record($student, 'certificate.issued', 'إصدار شهادة', $certificate, $certificate->name, ['certificate_number' => $certificate->certificate_number], $certificate->issued_at);
            $this->auditLogger->record('certificate.issued', $certificate, newValues: $certificate->getAttributes());

            return $certificate;
        });
    }

    public function recordAchievement(Student $student, array $data, User $actor): Achievement
    {
        return DB::transaction(function () use ($student, $data, $actor) {
            $achievement = Achievement::query()->create([
                ...$data,
                'student_id' => $student->id,
                'type' => AchievementType::from($data['type'])->value,
                'created_by' => $actor->id,
            ]);
            $this->timeline->record($student, 'achievement.recorded', 'تسجيل إنجاز', $achievement, $achievement->title, ['type' => $achievement->type->value], $achievement->achieved_at);
            $this->auditLogger->record('achievement.created', $achievement, newValues: $achievement->getAttributes());

            return $achievement;
        });
    }
}
