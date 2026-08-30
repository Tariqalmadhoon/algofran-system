<?php

namespace App\Livewire;

use App\Enums\AchievementType;
use App\Enums\CourseEnrollmentStatus;
use App\Enums\CourseStatus;
use App\Enums\EvaluationRating;
use App\Exports\AcademicCourseResultsExport;
use App\Models\Achievement;
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;

class AcademicManager extends Component
{
    use WithFileUploads;

    public string $courseCenterId = '';

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

    public string $bulkCourseId = '';

    public string $bulkEnrollmentDate = '';

    public string $studentSearch = '';

    /** @var array<int, int|string> */
    public array $selectedStudentIds = [];

    public string $resultsCourseId = '';

    /** @var array<int, array{result: string, grade: string, completed_at: string, notes: string}> */
    public array $courseResultRows = [];

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
        $this->bulkEnrollmentDate = today()->toDateString();
        $this->certificateIssuedAt = today()->toDateString();
        $this->achievementDate = today()->toDateString();

        $availableCenters = $this->centerQuery()->pluck('id');
        if ($availableCenters->count() === 1) {
            $this->courseCenterId = (string) $availableCenters->first();
        }
    }

    public function saveCourse(AcademicRecordsService $academic): void
    {
        Gate::authorize('create', Course::class);
        $data = $this->validate([
            'courseCenterId' => ['required', 'exists:centers,id'],
            'courseInstructorId' => ['nullable', Rule::exists('teacher_profiles', 'id')->where('center_id', $this->courseCenterId)],
            'courseName' => ['required', 'string', 'max:255'],
            'courseDescription' => ['nullable', 'string', 'max:3000'],
            'courseStartsAt' => ['required', 'date'],
            'courseEndsAt' => ['nullable', 'date', 'after_or_equal:courseStartsAt'],
            'courseHours' => ['required', 'numeric', 'between:0,9999'],
            'courseStatus' => ['required', Rule::enum(CourseStatus::class)],
        ]);
        abort_unless($this->centerQuery()->whereKey($data['courseCenterId'])->exists(), 403);

        $course = $academic->createCourse([
            'center_id' => $data['courseCenterId'],
            'branch_id' => null,
            'instructor_id' => $data['courseInstructorId'] ?: null,
            'name' => $data['courseName'],
            'description' => $data['courseDescription'] ?: null,
            'starts_at' => $data['courseStartsAt'],
            'ends_at' => $data['courseEndsAt'] ?: null,
            'hours' => $data['courseHours'],
            'status' => $data['courseStatus'],
        ], auth()->user());
        $this->reset('courseInstructorId', 'courseName', 'courseDescription', 'courseEndsAt');
        $this->courseCenterId = (string) $course->center_id;
        $this->courseStartsAt = today()->toDateString();
        $this->courseHours = '0';
        $this->courseStatus = CourseStatus::Planned->value;
        $this->bulkCourseId = (string) $course->id;
        $this->resultsCourseId = (string) $course->id;
        $this->loadCourseResultRows();
        $this->dispatch('academic:open-enrollments');
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

    public function updatedBulkCourseId(): void
    {
        $this->selectedStudentIds = [];
        $this->resultsCourseId = $this->bulkCourseId;
        $this->loadCourseResultRows();
        $this->resetValidation();
    }

    public function updatedResultsCourseId(): void
    {
        $this->loadCourseResultRows();
        $this->resetValidation();
    }

    public function updatedCourseResultRows(mixed $value, string $key): void
    {
        [$enrollmentId, $field] = array_pad(explode('.', $key, 2), 2, null);
        if ($field !== 'result' || ! is_numeric($value)) {
            return;
        }

        $this->courseResultRows[(int) $enrollmentId]['grade'] = $this->evaluationFor((float) $value);
    }

    public function selectAllStudents(StudentVisibilityService $visibility): void
    {
        if ($this->bulkCourseId === '') {
            $this->addError('bulkCourseId', 'اختر الدورة أولًا لعرض طلاب مركزها.');

            return;
        }

        $course = $this->scopedCourseQuery()->findOrFail($this->bulkCourseId);
        $this->selectedStudentIds = $this->studentRosterQuery($visibility, $course)
            ->whereDoesntHave('courseEnrollments', fn (Builder $enrollment) => $enrollment->where('course_id', $course->id))
            ->pluck('students.id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function clearSelectedStudents(): void
    {
        $this->selectedStudentIds = [];
    }

    public function registerSelectedStudents(AcademicRecordsService $academic, StudentVisibilityService $visibility): void
    {
        Gate::authorize('courses.manage');
        $data = $this->validate([
            'bulkCourseId' => ['required', 'integer', 'exists:courses,id'],
            'bulkEnrollmentDate' => ['required', 'date', 'before_or_equal:today'],
            'selectedStudentIds' => ['required', 'array', 'min:1'],
            'selectedStudentIds.*' => ['required', 'integer', 'distinct', 'exists:students,id'],
        ]);

        $course = $this->scopedCourseQuery()->findOrFail($data['bulkCourseId']);
        Gate::authorize('update', $course);
        $selectedIds = collect($data['selectedStudentIds'])->map(fn ($id) => (int) $id)->unique()->values();
        $students = $visibility->queryFor(auth()->user())
            ->whereIn('students.id', $selectedIds)
            ->whereHas('currentHalaqa', fn (Builder $halaqa) => $halaqa->where('center_id', $course->center_id))
            ->get();

        abort_unless($students->count() === $selectedIds->count(), 403);
        $summary = $academic->enrollStudents($course, $students, $data['bulkEnrollmentDate'], auth()->user());

        $this->selectedStudentIds = [];
        $this->resultsCourseId = (string) $course->id;
        $this->loadCourseResultRows();
        session()->flash('success', "تم تسجيل {$summary['created']} طالب في الدورة".($summary['skipped'] ? "، وتجاوز {$summary['skipped']} مسجلين سابقًا." : '.'));
    }

    public function saveCourseResults(AcademicRecordsService $academic, StudentVisibilityService $visibility): void
    {
        Gate::authorize('courses.manage');
        $grades = array_map(fn (EvaluationRating $rating) => $rating->label(), EvaluationRating::cases());
        $data = $this->validate([
            'resultsCourseId' => ['required', 'integer', 'exists:courses,id'],
            'courseResultRows' => ['required', 'array', 'min:1'],
            'courseResultRows.*.result' => ['required', 'numeric', 'between:0,100'],
            'courseResultRows.*.grade' => ['required', 'string', Rule::in($grades)],
            'courseResultRows.*.completed_at' => ['required', 'date', 'before_or_equal:today'],
            'courseResultRows.*.notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $course = $this->scopedCourseQuery()->findOrFail($data['resultsCourseId']);
        Gate::authorize('update', $course);
        $visibleStudentIds = $visibility->queryFor(auth()->user())->select('students.id');
        $enrollments = CourseEnrollment::query()
            ->where('course_id', $course->id)
            ->whereIn('student_id', $visibleStudentIds)
            ->whereIn('id', array_keys($data['courseResultRows']))
            ->with('student')
            ->get();

        abort_unless($enrollments->count() === count($data['courseResultRows']), 403);
        foreach ($enrollments as $enrollment) {
            $row = $data['courseResultRows'][$enrollment->id];
            $academic->enrollStudent($course, $enrollment->student, [
                'enrolled_at' => $enrollment->enrolled_at->toDateString(),
                'status' => CourseEnrollmentStatus::Completed->value,
                'result' => $row['result'],
                'grade' => $row['grade'],
                'completed_at' => $row['completed_at'],
                'notes' => $row['notes'] ?: null,
            ], auth()->user());
        }

        $this->loadCourseResultRows();
        session()->flash('success', 'تم اعتماد نتائج وإنجازات '.$enrollments->count().' طالب وتحديث ملفاتهم الأكاديمية.');
    }

    public function exportCourseResults(StudentVisibilityService $visibility): mixed
    {
        Gate::authorize('courses.manage');
        $this->validate(['resultsCourseId' => ['required', 'integer', 'exists:courses,id']]);
        $course = $this->scopedCourseQuery()
            ->with(['center:id,name', 'instructor.user:id,name'])
            ->findOrFail($this->resultsCourseId);
        $visibleStudentIds = $visibility->queryFor(auth()->user())->select('students.id');
        $enrollments = CourseEnrollment::query()
            ->where('course_id', $course->id)
            ->where('status', CourseEnrollmentStatus::Completed->value)
            ->whereIn('student_id', $visibleStudentIds)
            ->with([
                'student:id,full_name,identity_number,current_halaqa_id',
                'student.currentHalaqa:id,center_id,primary_teacher_id',
                'student.currentHalaqa.primaryTeacher:id,user_id',
                'student.currentHalaqa.primaryTeacher.user:id,name',
            ])
            ->get()
            ->sortBy('student.full_name', SORT_NATURAL)
            ->values();

        if ($enrollments->isEmpty()) {
            $this->addError('courseExport', 'لا توجد نتائج مكتملة لهذه الدورة لتصديرها. اعتمد النتائج أولًا.');

            return null;
        }

        $filename = (Str::slug($course->name) ?: 'course-'.$course->id).'-results-'.today()->format('Y-m-d').'.xlsx';

        return Excel::download(new AcademicCourseResultsExport($course, $enrollments), $filename, ExcelFormat::XLSX);
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
        $centers = $this->centerQuery()->orderBy('name')->get(['id', 'name']);
        $courses = $this->scopedCourseQuery()
            ->with(['center:id,name', 'instructor.user:id,name'])
            ->withCount(['enrollments', 'enrollments as completed_enrollments_count' => fn (Builder $query) => $query->where('status', CourseEnrollmentStatus::Completed->value)])
            ->latest()
            ->limit(150)
            ->get();
        $bulkCourse = $this->bulkCourseId !== '' ? $courses->firstWhere('id', (int) $this->bulkCourseId) : null;
        $resultCourse = $this->resultsCourseId !== '' ? $courses->firstWhere('id', (int) $this->resultsCourseId) : null;
        $visibleStudentIds = $visibility->queryFor(auth()->user())->pluck('students.id');
        $studentRoster = $bulkCourse
            ? $this->studentRosterQuery($visibility, $bulkCourse)->get()
            : collect();
        $bulkRegisteredStudentIds = $bulkCourse
            ? CourseEnrollment::query()->where('course_id', $bulkCourse->id)->pluck('student_id')->map(fn ($id) => (int) $id)->all()
            : [];
        $resultEnrollments = $resultCourse
            ? CourseEnrollment::query()
                ->where('course_id', $resultCourse->id)
                ->whereIn('student_id', $visibleStudentIds)
                ->with(['student:id,full_name,first_name,family_name,student_number,identity_number,current_halaqa_id,photo_private_file_id', 'student.photo:id'])
                ->orderBy(Student::select('full_name')->whereColumn('students.id', 'course_enrollments.student_id'))
                ->get()
            : collect();

        return view('livewire.academic-manager', [
            'centers' => $centers,
            'teachers' => TeacherProfile::query()->where('active', true)->whereIn('center_id', $centers->modelKeys())->with('user:id,name')->get(['id', 'user_id', 'center_id']),
            'students' => Student::query()->whereIn('id', $visibleStudentIds)->orderBy('full_name')->limit(2000)->get(['id', 'full_name', 'student_number']),
            'studentRoster' => $studentRoster,
            'bulkRegisteredStudentIds' => $bulkRegisteredStudentIds,
            'bulkCourse' => $bulkCourse,
            'resultCourse' => $resultCourse,
            'courses' => $courses,
            'resultEnrollments' => $resultEnrollments,
            'certificates' => Certificate::query()->whereIn('student_id', $visibleStudentIds)->with(['student:id,full_name,first_name,family_name,photo_private_file_id', 'student.photo:id', 'course:id,name', 'privateFile:id,original_name'])->latest('issued_at')->limit(100)->get(),
            'achievements' => Achievement::query()->whereIn('student_id', $visibleStudentIds)->with(['student:id,full_name,first_name,family_name,photo_private_file_id', 'student.photo:id'])->latest('achieved_at')->limit(100)->get(),
            'courseStatuses' => CourseStatus::cases(),
            'enrollmentStatuses' => CourseEnrollmentStatus::cases(),
            'evaluationRatings' => EvaluationRating::cases(),
            'achievementTypes' => AchievementType::cases(),
        ]);
    }

    /** @return Builder<Center> */
    private function centerQuery(): Builder
    {
        $query = Center::query()->where('active', true);
        if (auth()->user()?->hasRole('super-admin')) {
            return $query;
        }

        $centerId = auth()->user()?->staffProfile?->center_id ?? auth()->user()?->teacherProfile?->center_id;

        return $centerId ? $query->whereKey($centerId) : $query->whereRaw('1 = 0');
    }

    /** @return Builder<Course> */
    private function scopedCourseQuery(): Builder
    {
        $query = Course::query();
        if (auth()->user()?->hasRole('super-admin')) {
            return $query;
        }

        $centerId = auth()->user()?->staffProfile?->center_id ?? auth()->user()?->teacherProfile?->center_id;

        return $centerId ? $query->where('center_id', $centerId) : $query->whereRaw('1 = 0');
    }

    /** @return Builder<Student> */
    private function studentRosterQuery(StudentVisibilityService $visibility, Course $course): Builder
    {
        return $visibility->queryFor(auth()->user())
            ->whereHas('currentHalaqa', fn (Builder $halaqa) => $halaqa->where('center_id', $course->center_id))
            ->with([
                'currentHalaqa:id,name,center_id,primary_teacher_id',
                'currentHalaqa.primaryTeacher:id,user_id',
                'currentHalaqa.primaryTeacher.user:id,name',
                'photo:id',
            ])
            ->when(trim($this->studentSearch) !== '', function (Builder $query): void {
                $term = '%'.trim($this->studentSearch).'%';
                $query->where(fn (Builder $search) => $search
                    ->where('full_name', 'like', $term)
                    ->orWhere('student_number', 'like', $term)
                    ->orWhere('identity_number', 'like', $term));
            })
            ->orderBy('full_name')
            ->select(['id', 'full_name', 'first_name', 'family_name', 'student_number', 'identity_number', 'current_halaqa_id', 'photo_private_file_id']);
    }

    private function loadCourseResultRows(): void
    {
        $this->courseResultRows = [];
        if ($this->resultsCourseId === '' || ! $this->scopedCourseQuery()->whereKey($this->resultsCourseId)->exists()) {
            return;
        }

        $visibleStudentIds = app(StudentVisibilityService::class)->queryFor(auth()->user())->select('students.id');
        $enrollments = CourseEnrollment::query()
            ->where('course_id', $this->resultsCourseId)
            ->whereIn('student_id', $visibleStudentIds)
            ->get();

        foreach ($enrollments as $enrollment) {
            $this->courseResultRows[$enrollment->id] = [
                'result' => $enrollment->result === null ? '' : (string) $enrollment->result,
                'grade' => $enrollment->grade ?? '',
                'completed_at' => $enrollment->completed_at?->toDateString() ?? today()->toDateString(),
                'notes' => $enrollment->notes ?? '',
            ];
        }
    }

    private function evaluationFor(float $result): string
    {
        return match (true) {
            $result >= 90 => EvaluationRating::Excellent->label(),
            $result >= 75 => EvaluationRating::VeryGood->label(),
            $result >= 60 => EvaluationRating::Good->label(),
            default => EvaluationRating::Poor->label(),
        };
    }
}
