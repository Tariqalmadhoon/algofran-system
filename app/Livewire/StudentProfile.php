<?php

namespace App\Livewire;

use App\Actions\Guardians\CreateGuardianAction;
use App\Actions\Students\EnrollStudentInHalaqaAction;
use App\Actions\Students\RecordInitialBaselineAction;
use App\Actions\Students\UpdateStudentAction;
use App\Enums\StudentStatus;
use App\Models\Halaqa;
use App\Models\QuranAyah;
use App\Models\QuranSurah;
use App\Models\Student;
use App\Services\PrivateFileService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;
use Livewire\WithPagination;

class StudentProfile extends Component
{
    use WithFileUploads, WithPagination;

    public int $studentId;

    public string $profileFirstName = '';

    public string $profileFatherName = '';

    public string $profileGrandfatherName = '';

    public string $profileFamilyName = '';

    public string $profileIdentityNumber = '';

    public string $profileBirthDate = '';

    public string $profileContactPhone = '';

    public string $profileStatus = 'active';

    public string $profileNotes = '';

    public $profilePhoto;

    public $profileIdentityDocument;

    public string $guardianName = '';

    public string $guardianIdentityNumber = '';

    public string $guardianPhone = '';

    public string $guardianAlternativePhone = '';

    public string $guardianEmail = '';

    public string $guardianRelationship = 'father';

    public bool $guardianIsPrimary = true;

    public bool $guardianCanReceiveNotifications = true;

    public string $guardianNotes = '';

    public $guardianIdentityDocument;

    public string $baselineStartSurahId = '';

    public string $baselineStartAyahNumber = '';

    public string $baselineEndSurahId = '';

    public string $baselineEndAyahNumber = '';

    public string $baselineRecordedAt = '';

    public string $baselineNotes = '';

    public string $enrollmentHalaqaId = '';

    public string $enrollmentStartsAt = '';

    public string $enrollmentReason = '';

    public function mount(Student $student): void
    {
        Gate::authorize('view', $student);
        $this->studentId = $student->id;
        $this->profileFirstName = $student->first_name;
        $this->profileFatherName = $student->father_name;
        $this->profileGrandfatherName = $student->grandfather_name;
        $this->profileFamilyName = $student->family_name;
        $this->profileIdentityNumber = $student->identity_number ?? '';
        $this->profileBirthDate = $student->birth_date?->toDateString() ?? '';
        $this->profileContactPhone = $student->contact_phone ?? '';
        $this->profileStatus = $student->status->value;
        $this->profileNotes = $student->notes ?? '';
        $this->baselineRecordedAt = today()->toDateString();
        $this->enrollmentStartsAt = today()->toDateString();
    }

    public function updatedBaselineStartSurahId(string $surahId): void
    {
        $this->baselineStartAyahNumber = '';
        $this->baselineEndSurahId = $surahId;
        $this->baselineEndAyahNumber = '';
        $this->resetValidation([
            'baselineStartSurahId', 'baselineStartAyahNumber',
            'baselineEndSurahId', 'baselineEndAyahNumber',
        ]);
    }

    public function updatedBaselineEndSurahId(string $surahId): void
    {
        if ($this->baselineStartSurahId !== '' && (int) $surahId < (int) $this->baselineStartSurahId) {
            $this->baselineEndSurahId = $this->baselineStartSurahId;
            $this->addError('baselineEndSurahId', 'سورة النهاية لا يمكن أن تسبق سورة البداية.');

            return;
        }

        $this->baselineEndAyahNumber = $surahId === $this->baselineStartSurahId
            ? $this->baselineStartAyahNumber
            : '';
        $this->resetValidation(['baselineEndSurahId', 'baselineEndAyahNumber']);
    }

    public function updatedBaselineStartAyahNumber(string $ayahNumber): void
    {
        if ($this->baselineStartSurahId === $this->baselineEndSurahId
            && (int) $this->baselineEndAyahNumber < (int) $ayahNumber) {
            $this->baselineEndAyahNumber = $ayahNumber;
        }

        $this->resetValidation(['baselineStartAyahNumber', 'baselineEndAyahNumber']);
    }

    public function saveProfile(UpdateStudentAction $updateStudent, PrivateFileService $privateFiles): void
    {
        $student = $this->student();
        Gate::authorize('update', $student);

        $data = $this->validate([
            'profileFirstName' => ['required', 'string', 'max:100'],
            'profileFatherName' => ['required', 'string', 'max:100'],
            'profileGrandfatherName' => ['required', 'string', 'max:100'],
            'profileFamilyName' => ['required', 'string', 'max:100'],
            'profileIdentityNumber' => ['nullable', 'string', 'max:50', Rule::unique('students', 'identity_number')->ignore($student->id)],
            'profileBirthDate' => ['nullable', 'date', 'before:today'],
            'profileContactPhone' => ['nullable', 'string', 'max:30'],
            'profileStatus' => ['required', Rule::enum(StudentStatus::class)],
            'profileNotes' => ['nullable', 'string', 'max:3000'],
            'profilePhoto' => ['nullable', 'image', 'max:2048'],
            'profileIdentityDocument' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $updates = [
            'first_name' => $data['profileFirstName'],
            'father_name' => $data['profileFatherName'],
            'grandfather_name' => $data['profileGrandfatherName'],
            'family_name' => $data['profileFamilyName'],
            'identity_number' => $data['profileIdentityNumber'] ?: null,
            'birth_date' => $data['profileBirthDate'] ?: null,
            'contact_phone' => $data['profileContactPhone'] ?: null,
            'status' => $data['profileStatus'],
            'notes' => $data['profileNotes'] ?: null,
        ];
        if ($this->profilePhoto) {
            $updates['photo_private_file_id'] = $privateFiles
                ->store($this->profilePhoto, $student, auth()->user(), "students/{$student->id}", 'student-photo')->id;
        }
        if ($this->profileIdentityDocument) {
            $updates['identity_private_file_id'] = $privateFiles
                ->store($this->profileIdentityDocument, $student, auth()->user(), "students/{$student->id}", 'student-identity')->id;
        }
        $updateStudent->execute($student, $updates, auth()->user());

        $this->reset('profilePhoto', 'profileIdentityDocument');
        session()->flash('success', 'تم تحديث بيانات الطالب.');
    }

    public function saveGuardian(CreateGuardianAction $createGuardian, PrivateFileService $privateFiles): void
    {
        $student = $this->student();
        Gate::authorize('update', $student);

        $data = $this->validate([
            'guardianName' => ['required', 'string', 'max:255'],
            'guardianIdentityNumber' => ['nullable', 'string', 'max:50'],
            'guardianPhone' => ['required', 'string', 'max:30'],
            'guardianAlternativePhone' => ['nullable', 'string', 'max:30'],
            'guardianEmail' => ['nullable', 'email', 'max:255'],
            'guardianRelationship' => ['required', 'in:father,mother,brother,sister,uncle,aunt,other'],
            'guardianIsPrimary' => ['boolean'],
            'guardianCanReceiveNotifications' => ['boolean'],
            'guardianNotes' => ['nullable', 'string', 'max:3000'],
            'guardianIdentityDocument' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $guardian = $createGuardian->execute($student, [
            'full_name' => $data['guardianName'],
            'identity_number' => $data['guardianIdentityNumber'] ?: null,
            'phone' => $data['guardianPhone'],
            'alternative_phone' => $data['guardianAlternativePhone'] ?: null,
            'email' => $data['guardianEmail'] ?: null,
            'relationship' => $data['guardianRelationship'],
            'is_primary' => $data['guardianIsPrimary'],
            'can_receive_notifications' => $data['guardianCanReceiveNotifications'],
            'notes' => $data['guardianNotes'] ?: null,
        ], auth()->user());

        if ($this->guardianIdentityDocument) {
            $file = $privateFiles->store(
                $this->guardianIdentityDocument,
                $guardian,
                auth()->user(),
                "guardians/{$guardian->id}",
                'guardian-identity',
            );
            $guardian->update(['identity_private_file_id' => $file->id, 'updated_by' => auth()->id()]);
        }

        $this->resetGuardianForm();
        session()->flash('success', 'تم ربط ولي الأمر بالطالب.');
    }

    public function saveBaseline(RecordInitialBaselineAction $recordBaseline): void
    {
        $student = $this->student();
        Gate::authorize('update', $student);

        $data = $this->validate([
            'baselineStartSurahId' => ['required', 'exists:quran_surahs,id'],
            'baselineStartAyahNumber' => ['required', 'integer', 'min:1'],
            'baselineEndSurahId' => ['required', 'exists:quran_surahs,id'],
            'baselineEndAyahNumber' => ['required', 'integer', 'min:1'],
            'baselineRecordedAt' => ['required', 'date', 'before_or_equal:today'],
            'baselineNotes' => ['nullable', 'string', 'max:3000'],
        ]);

        $start = $this->ayah((int) $data['baselineStartSurahId'], (int) $data['baselineStartAyahNumber'], 'baselineStartAyahNumber');
        $end = $this->ayah((int) $data['baselineEndSurahId'], (int) $data['baselineEndAyahNumber'], 'baselineEndAyahNumber');
        $recordBaseline->execute($student, $start->id, $end->id, $data['baselineRecordedAt'], auth()->user(), $data['baselineNotes'] ?: null);

        $this->reset('baselineStartSurahId', 'baselineStartAyahNumber', 'baselineEndSurahId', 'baselineEndAyahNumber', 'baselineNotes');
        $this->baselineRecordedAt = today()->toDateString();
        session()->flash('success', 'تم تسجيل نقطة بداية الحفظ.');
    }

    public function saveEnrollment(EnrollStudentInHalaqaAction $enrollStudent): void
    {
        $student = $this->student();
        Gate::authorize('update', $student);

        $data = $this->validate([
            'enrollmentHalaqaId' => ['required', 'exists:halaqas,id'],
            'enrollmentStartsAt' => ['required', 'date'],
            'enrollmentReason' => ['nullable', 'string', 'max:255'],
        ]);
        $enrollStudent->execute(
            $student,
            Halaqa::query()->findOrFail($data['enrollmentHalaqaId']),
            $data['enrollmentStartsAt'],
            auth()->user(),
            $data['enrollmentReason'] ?: null,
        );

        $this->reset('enrollmentHalaqaId', 'enrollmentReason');
        $this->enrollmentStartsAt = today()->toDateString();
        session()->flash('success', 'تم تحديث التحاق الطالب مع حفظ التاريخ السابق.');
    }

    public function render(): View
    {
        $student = Student::query()->with([
            'currentHalaqa:id,name',
            'photo:id,original_name',
            'identityDocument:id,original_name',
            'guardians.identityDocument:id,original_name',
            'enrollments.halaqa:id,name',
            'baselines.startAyah.surah',
            'baselines.endAyah.surah',
            'latestProgress.lastMemorizedAyah.surah',
            'courseEnrollments.course:id,name',
            'certificates.course:id,name',
            'certificates.privateFile:id,original_name',
            'achievements',
        ])->findOrFail($this->studentId);

        $timeline = $student->timelineEvents()
            ->latest('occurred_at')
            ->paginate(15, ['*'], 'timelinePage');
        $dailyRecords = auth()->user()->can('recitations.view')
            ? $student->dailyRecords()
                ->with([
                    'attendance',
                    'teacher.user:id,name',
                    'halaqa:id,name',
                    'recitationItems.startAyah.surah',
                    'recitationItems.endAyah.surah',
                ])
                ->latest('record_date')
                ->paginate(10, ['*'], 'recordsPage')
            : null;

        return view('livewire.student-profile', [
            'student' => $student,
            'timeline' => $timeline,
            'dailyRecords' => $dailyRecords,
            'surahs' => QuranSurah::query()->orderBy('id')->get(['id', 'name_arabic', 'verses_count']),
            'halaqas' => Halaqa::query()->where('active', true)->orderBy('name')->get(['id', 'name']),
            'studentStatuses' => StudentStatus::cases(),
        ]);
    }

    private function student(): Student
    {
        return Student::query()->findOrFail($this->studentId);
    }

    private function ayah(int $surahId, int $ayahNumber, string $field): QuranAyah
    {
        $ayah = QuranAyah::query()->where('surah_id', $surahId)->where('ayah_number', $ayahNumber)->first();
        if (! $ayah) {
            throw ValidationException::withMessages([$field => 'رقم الآية غير موجود في السورة المختارة.']);
        }

        return $ayah;
    }

    private function resetGuardianForm(): void
    {
        $this->reset(
            'guardianName', 'guardianIdentityNumber', 'guardianPhone', 'guardianAlternativePhone',
            'guardianEmail', 'guardianNotes', 'guardianIdentityDocument',
        );
        $this->guardianRelationship = 'father';
        $this->guardianIsPrimary = true;
        $this->guardianCanReceiveNotifications = true;
    }
}
