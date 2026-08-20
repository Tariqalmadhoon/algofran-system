<?php

namespace App\Livewire;

use App\Enums\AchievementType;
use App\Enums\CourseEnrollmentStatus;
use App\Enums\CourseStatus;
use App\Models\Achievement;
use App\Models\Branch;
use App\Models\Center;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Student;
use App\Models\TeacherProfile;
use App\Services\AcademicRecordsService;
use App\Services\PrivateFileService;
use App\Services\StudentVisibilityService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;

class AcademicManager extends Component
{
    use WithFileUploads;

    public string $courseCenterId = '';

    public string $courseBranchId = '';

    public string $courseInstructorId = '';

    public string $courseName = '';

    public string $courseDescription = '';

    public string $courseStartsAt = '';

    public string $courseEndsAt = '';

    public string $courseHours = '0';

    public string $courseStatus = 'planned';

    public string $enrollmentCourseId = '';

    public string $enrollmentStudentId = '';

    public string $enrollmentDate = '';

    public string $enrollmentStatus = 'enrolled';

    public string $enrollmentResult = '';

    public string $enrollmentGrade = '';

    public string $enrollmentCompletedAt = '';

    public string $enrollmentNotes = '';

    public string $certificateStudentId = '';

    public string $certificateCourseId = '';

    public string $certificateName = '';

    public string $certificateIssuer = '';

    public string $certificateNumber = '';

    public string $certificateIssuedAt = '';

    public string $certificateExpiresAt = '';

    public string $certificateGrade = '';

    public string $certificateNotes = '';

    public $certificateFile;

    public string $achievementStudentId = '';

    public string $achievementType = 'manual';

    public string $achievementTitle = '';

    public string $achievementDescription = '';

    public string $achievementDate = '';

    public string $achievementIssuer = '';

    public function mount(): void
    {
        abort_unless(
            Gate::allows('courses.manage') || Gate::allows('certificates.manage') || Gate::allows('achievements.manage'),
            403,
        );
        $this->courseStartsAt = today()->toDateString();
        $this->enrollmentDate = today()->toDateString();
        $this->certificateIssuedAt = today()->toDateString();
        $this->achievementDate = today()->toDateString();
    }

    public function saveCourse(AcademicRecordsService $academic): void
    {
        Gate::authorize('create', Course::class);
        $data = $this->validate([
            'courseCenterId' => ['required', 'exists:centers,id'],
            'courseBranchId' => ['nullable', 'exists:branches,id'],
            'courseInstructorId' => ['nullable', 'exists:teacher_profiles,id'],
            'courseName' => ['required', 'string', 'max:255'],
            'courseDescription' => ['nullable', 'string', 'max:3000'],
            'courseStartsAt' => ['required', 'date'],
            'courseEndsAt' => ['nullable', 'date', 'after_or_equal:courseStartsAt'],
            'courseHours' => ['required', 'numeric', 'between:0,9999'],
            'courseStatus' => ['required', Rule::enum(CourseStatus::class)],
        ]);
        if ($data['courseBranchId'] && ! Branch::query()->whereKey($data['courseBranchId'])->where('center_id', $data['courseCenterId'])->exists()) {
            throw ValidationException::withMessages(['courseBranchId' => 'الفرع لا يتبع المركز المختار.']);
        }
        $academic->createCourse([
            'center_id' => $data['courseCenterId'],
            'branch_id' => $data['courseBranchId'] ?: null,
            'instructor_id' => $data['courseInstructorId'] ?: null,
            'name' => $data['courseName'],
            'description' => $data['courseDescription'] ?: null,
            'starts_at' => $data['courseStartsAt'],
            'ends_at' => $data['courseEndsAt'] ?: null,
            'hours' => $data['courseHours'],
            'status' => $data['courseStatus'],
        ], auth()->user());
        $this->reset('courseCenterId', 'courseBranchId', 'courseInstructorId', 'courseName', 'courseDescription', 'courseEndsAt');
        $this->courseStartsAt = today()->toDateString();
        $this->courseHours = '0';
        $this->courseStatus = CourseStatus::Planned->value;
        session()->flash('success', 'تم إنشاء الدورة.');
    }

    public function saveEnrollment(AcademicRecordsService $academic): void
    {
        Gate::authorize('courses.manage');
        $data = $this->validate([
            'enrollmentCourseId' => ['required', 'exists:courses,id'],
            'enrollmentStudentId' => ['required', 'exists:students,id'],
            'enrollmentDate' => ['required', 'date'],
            'enrollmentStatus' => ['required', Rule::enum(CourseEnrollmentStatus::class)],
            'enrollmentResult' => ['nullable', 'numeric', 'between:0,100'],
            'enrollmentGrade' => ['nullable', 'string', 'max:50'],
            'enrollmentCompletedAt' => ['nullable', 'date', 'after_or_equal:enrollmentDate'],
            'enrollmentNotes' => ['nullable', 'string', 'max:2000'],
        ]);
        $student = Student::query()->findOrFail($data['enrollmentStudentId']);
        Gate::authorize('view', $student);
        $academic->enrollStudent(Course::query()->findOrFail($data['enrollmentCourseId']), $student, [
            'enrolled_at' => $data['enrollmentDate'],
            'status' => $data['enrollmentStatus'],
            'result' => $data['enrollmentResult'] === '' ? null : $data['enrollmentResult'],
            'grade' => $data['enrollmentGrade'] ?: null,
            'completed_at' => $data['enrollmentCompletedAt'] ?: null,
            'notes' => $data['enrollmentNotes'] ?: null,
        ], auth()->user());
        $this->reset('enrollmentCourseId', 'enrollmentStudentId', 'enrollmentResult', 'enrollmentGrade', 'enrollmentCompletedAt', 'enrollmentNotes');
        $this->enrollmentDate = today()->toDateString();
        $this->enrollmentStatus = CourseEnrollmentStatus::Enrolled->value;
        session()->flash('success', 'تم حفظ تسجيل الطالب ونتيجته.');
    }

    public function saveCertificate(AcademicRecordsService $academic, PrivateFileService $privateFiles): void
    {
        Gate::authorize('create', Certificate::class);
        $data = $this->validate([
            'certificateStudentId' => ['required', 'exists:students,id'],
            'certificateCourseId' => ['nullable', 'exists:courses,id'],
            'certificateName' => ['required', 'string', 'max:255'],
            'certificateIssuer' => ['required', 'string', 'max:255'],
            'certificateNumber' => ['nullable', 'string', 'max:100', 'unique:certificates,certificate_number'],
            'certificateIssuedAt' => ['required', 'date'],
            'certificateExpiresAt' => ['nullable', 'date', 'after:certificateIssuedAt'],
            'certificateGrade' => ['nullable', 'string', 'max:50'],
            'certificateNotes' => ['nullable', 'string', 'max:2000'],
            'certificateFile' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);
        $student = Student::query()->findOrFail($data['certificateStudentId']);
        Gate::authorize('view', $student);
        $certificate = $academic->issueCertificate($student, [
            'course_id' => $data['certificateCourseId'] ?: null,
            'name' => $data['certificateName'],
            'issuer' => $data['certificateIssuer'],
            'certificate_number' => $data['certificateNumber'] ?: null,
            'issued_at' => $data['certificateIssuedAt'],
            'expires_at' => $data['certificateExpiresAt'] ?: null,
            'grade' => $data['certificateGrade'] ?: null,
            'notes' => $data['certificateNotes'] ?: null,
        ], auth()->user());
        if ($this->certificateFile) {
            $file = $privateFiles->store($this->certificateFile, $certificate, auth()->user(), "certificates/{$certificate->id}", 'certificate');
            $certificate->update(['private_file_id' => $file->id]);
        }
        $this->reset('certificateStudentId', 'certificateCourseId', 'certificateName', 'certificateIssuer', 'certificateNumber', 'certificateExpiresAt', 'certificateGrade', 'certificateNotes', 'certificateFile');
        $this->certificateIssuedAt = today()->toDateString();
        session()->flash('success', 'تم إصدار الشهادة وحفظ ملفها الخاص.');
    }

    public function saveAchievement(AcademicRecordsService $academic): void
    {
        Gate::authorize('achievements.manage');
        $data = $this->validate([
            'achievementStudentId' => ['required', 'exists:students,id'],
            'achievementType' => ['required', Rule::enum(AchievementType::class)],
            'achievementTitle' => ['required', 'string', 'max:255'],
            'achievementDescription' => ['nullable', 'string', 'max:2000'],
            'achievementDate' => ['required', 'date', 'before_or_equal:today'],
            'achievementIssuer' => ['nullable', 'string', 'max:255'],
        ]);
        $student = Student::query()->findOrFail($data['achievementStudentId']);
        Gate::authorize('view', $student);
        $academic->recordAchievement($student, [
            'type' => $data['achievementType'],
            'title' => $data['achievementTitle'],
            'description' => $data['achievementDescription'] ?: null,
            'achieved_at' => $data['achievementDate'],
            'issuer' => $data['achievementIssuer'] ?: null,
            'metadata' => null,
        ], auth()->user());
        $this->reset('achievementStudentId', 'achievementTitle', 'achievementDescription', 'achievementIssuer');
        $this->achievementType = AchievementType::Manual->value;
        $this->achievementDate = today()->toDateString();
        session()->flash('success', 'تم تسجيل الإنجاز.');
    }

    public function render(StudentVisibilityService $visibility): View
    {
        return view('livewire.academic-manager', [
            'centers' => Center::query()->where('active', true)->orderBy('name')->get(['id', 'name']),
            'branches' => Branch::query()->where('active', true)->orderBy('name')->get(['id', 'center_id', 'name']),
            'teachers' => TeacherProfile::query()->where('active', true)->with('user:id,name')->get(['id', 'user_id']),
            'students' => $visibility->queryFor(auth()->user())->orderBy('full_name')->limit(1000)->get(['id', 'full_name', 'student_number']),
            'courses' => Course::query()->with(['center:id,name', 'instructor.user:id,name'])->withCount('enrollments')->latest()->limit(100)->get(),
            'enrollments' => CourseEnrollment::query()->with(['student:id,full_name', 'course:id,name'])->latest()->limit(100)->get(),
            'certificates' => Certificate::query()->with(['student:id,full_name', 'course:id,name', 'privateFile:id,original_name'])->latest('issued_at')->limit(100)->get(),
            'achievements' => Achievement::query()->with('student:id,full_name')->latest('achieved_at')->limit(100)->get(),
            'courseStatuses' => CourseStatus::cases(),
            'enrollmentStatuses' => CourseEnrollmentStatus::cases(),
            'achievementTypes' => AchievementType::cases(),
        ]);
    }
}
