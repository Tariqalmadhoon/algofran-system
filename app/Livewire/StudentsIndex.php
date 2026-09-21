<?php

namespace App\Livewire;

use App\Actions\Students\ArchiveStudentAction;
use App\Actions\Students\CreateStudentAction;
use App\Enums\StudentStatus;
use App\Models\Halaqa;
use App\Models\Student;
use App\Services\PrivateFileService;
use App\Services\StudentVisibilityService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;
use Livewire\WithPagination;

class StudentsIndex extends Component
{
    use WithFileUploads, WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public string $halaqaFilter = '';

    public string $studentNumber = '';

    public string $firstName = '';

    public string $fatherName = '';

    public string $grandfatherName = '';

    public string $familyName = '';

    public string $identityNumber = '';

    public string $birthDate = '';

    public string $contactPhone = '';

    public string $registrationDate = '';

    public string $status = 'active';

    public string $halaqaId = '';

    public string $notes = '';

    public $photo;

    public $identityDocument;

    public function mount(): void
    {
        Gate::authorize('viewAny', Student::class);
        $this->registrationDate = today()->toDateString();
        $this->selectOnlyAssignedHalaqa();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedHalaqaFilter(): void
    {
        $this->resetPage();
    }

    public function save(CreateStudentAction $createStudent, PrivateFileService $privateFiles): void
    {
        Gate::authorize('create', Student::class);
        $teacherMode = auth()->user()->requiresTeacherAssignmentScope();

        $data = $this->validate([
            'studentNumber' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:students,student_number'],
            'firstName' => ['required', 'string', 'max:100'],
            'fatherName' => ['required', 'string', 'max:100'],
            'grandfatherName' => ['required', 'string', 'max:100'],
            'familyName' => ['required', 'string', 'max:100'],
            'identityNumber' => ['nullable', 'string', 'max:50', 'unique:students,identity_number'],
            'birthDate' => ['nullable', 'date', 'before:today'],
            'contactPhone' => ['nullable', 'string', 'max:30'],
            'registrationDate' => ['required', 'date'],
            'status' => ['required', Rule::enum(StudentStatus::class)],
            'halaqaId' => [$teacherMode ? 'required' : 'nullable', 'exists:halaqas,id'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'identityDocument' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        if ($data['halaqaId'] !== '' && ! $this->availableHalaqas()->whereKey((int) $data['halaqaId'])->exists()) {
            $this->addError('halaqaId', 'الحلقة المحددة ليست ضمن نطاق حسابك.');

            return;
        }

        $student = $createStudent->execute([
            'student_number' => strtoupper($data['studentNumber']),
            'first_name' => $data['firstName'],
            'father_name' => $data['fatherName'],
            'grandfather_name' => $data['grandfatherName'],
            'family_name' => $data['familyName'],
            'identity_number' => $data['identityNumber'] ?: null,
            'birth_date' => $data['birthDate'] ?: null,
            'contact_phone' => $data['contactPhone'] ?: null,
            'registration_date' => $data['registrationDate'],
            'status' => $data['status'],
            'halaqa_id' => $data['halaqaId'] ?: null,
            'notes' => $data['notes'] ?: null,
        ], auth()->user());

        $updates = [];
        if ($this->photo) {
            $updates['photo_private_file_id'] = $privateFiles
                ->store($this->photo, $student, auth()->user(), "students/{$student->id}", 'student-photo')->id;
        }
        if ($this->identityDocument) {
            $updates['identity_private_file_id'] = $privateFiles
                ->store($this->identityDocument, $student, auth()->user(), "students/{$student->id}", 'student-identity')->id;
        }
        if ($updates !== []) {
            $student->update($updates);
        }

        $this->resetForm();
        session()->flash('success', 'تم إنشاء ملف الطالب بنجاح.');
    }

    public function archiveStudent(int $studentId, ArchiveStudentAction $archiveStudent): void
    {
        $student = Student::query()->findOrFail($studentId);
        Gate::authorize('archive', $student);

        $archiveStudent->execute($student, auth()->user());
        $this->resetPage();
        session()->flash('success', 'نُقل ملف الطالب إلى سلة المهملات مع الاحتفاظ بسجلاته كاملة.');
    }

    public function render(StudentVisibilityService $visibility): View
    {
        $students = $visibility->queryFor(auth()->user())
            ->with(['currentHalaqa:id,name', 'photo:id'])
            ->when($this->search, function ($query) {
                $term = '%'.trim($this->search).'%';
                $query->where(function ($search) use ($term) {
                    $search->where('full_name', 'like', $term)
                        ->orWhere('student_number', 'like', $term)
                        ->orWhere('identity_number', 'like', $term)
                        ->orWhere('contact_phone', 'like', $term);
                });
            })
            ->when($this->statusFilter, fn ($query) => $query->where('status', $this->statusFilter))
            ->when($this->halaqaFilter, fn ($query) => $query->where('current_halaqa_id', $this->halaqaFilter))
            ->orderBy('full_name')
            ->paginate(15);

        return view('livewire.students-index', [
            'students' => $students,
            'halaqas' => $this->availableHalaqas()->orderBy('name')->get(['id', 'name']),
            'statuses' => collect(StudentStatus::cases())
                ->reject(fn (StudentStatus $status) => $status === StudentStatus::Archived)
                ->all(),
            'teacherMode' => auth()->user()->requiresTeacherAssignmentScope(),
        ]);
    }

    private function resetForm(): void
    {
        $this->reset(
            'studentNumber', 'firstName', 'fatherName', 'grandfatherName', 'familyName', 'identityNumber',
            'birthDate', 'contactPhone', 'status', 'halaqaId', 'notes', 'photo', 'identityDocument',
        );
        $this->status = StudentStatus::Active->value;
        $this->registrationDate = today()->toDateString();
        $this->selectOnlyAssignedHalaqa();
    }

    /** @return Builder<Halaqa> */
    private function availableHalaqas(): Builder
    {
        $query = Halaqa::query()->where('active', true);
        $user = auth()->user();

        if ($user->hasRole('super-admin')) {
            return $query;
        }

        if ($user->requiresTeacherAssignmentScope()) {
            $teacherId = $user->teacherProfile?->id;
            if (! $teacherId) {
                return $query->whereRaw('1 = 0');
            }

            return $query->whereHas('teacherAssignments', fn (Builder $assignments) => $assignments
                ->where('teacher_profile_id', $teacherId)
                ->whereDate('starts_at', '<=', today())
                ->where(fn (Builder $dates) => $dates->whereNull('ends_at')->orWhereDate('ends_at', '>=', today())));
        }

        $centerId = $user->staffProfile?->active ? $user->staffProfile->center_id : null;

        return $centerId ? $query->where('center_id', $centerId) : $query->whereRaw('1 = 0');
    }

    private function selectOnlyAssignedHalaqa(): void
    {
        $teacherId = auth()->user()->teacherProfile?->active
            ? auth()->user()->teacherProfile->id
            : null;
        if (! $teacherId) {
            return;
        }

        $assignedHalaqas = Halaqa::query()
            ->where('active', true)
            ->whereHas('teacherAssignments', fn (Builder $assignments) => $assignments
                ->where('teacher_profile_id', $teacherId)
                ->whereDate('starts_at', '<=', today())
                ->where(fn (Builder $dates) => $dates->whereNull('ends_at')->orWhereDate('ends_at', '>=', today())))
            ->pluck('id');
        if ($assignedHalaqas->count() === 1) {
            $this->halaqaId = (string) $assignedHalaqas->first();
        }
    }
}
