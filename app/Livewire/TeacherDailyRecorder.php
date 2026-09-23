<?php

namespace App\Livewire;

use App\Actions\Recitations\RecordStudentDailyRecordAction;
use App\Enums\AttendanceStatus;
use App\Enums\EvaluationRating;
use App\Enums\RecitationType;
use App\Models\DailyRecord;
use App\Models\Halaqa;
use App\Models\QuranAyah;
use App\Models\QuranSurah;
use App\Models\ReportExport;
use App\Models\Student;
use App\Models\TeacherProfile;
use App\Services\ReportExportService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class TeacherDailyRecorder extends Component
{
    public int $teacherProfileId;

    public string $recordDate = '';

    public string $halaqaId = '';

    public string $studentId = '';

    public string $studentSearch = '';

    public string $historyStudentId = '';

    public string $historyFilter = 'all';

    public int $historyLimit = 5;

    public string $attendanceStatus = 'present';

    public string $attendanceNotes = '';

    public string $generalEvaluation = '';

    public string $notes = '';

    public array $items = [];

    public bool $showExportPanel = false;

    public string $exportDateFrom = '';

    public string $exportDateTo = '';

    public ?int $latestExportId = null;

    public function mount(): void
    {
        Gate::authorize('recitations.create');
        $teacher = auth()->user()->teacherProfile;
        abort_unless($teacher?->active, 403, 'لا يوجد ملف محفظ فعال لهذا الحساب.');

        $this->teacherProfileId = $teacher->id;
        $this->recordDate = today()->toDateString();
        $this->exportDateFrom = today()->startOfMonth()->toDateString();
        $this->exportDateTo = today()->toDateString();
        $this->items = $this->freshItems();
        $this->halaqaId = (string) ($this->assignedHalaqas()->first()?->id ?? '');
    }

    public function updatedRecordDate(): void
    {
        $available = $this->assignedHalaqas();
        if (! $available->contains('id', (int) $this->halaqaId)) {
            $this->halaqaId = (string) ($available->first()?->id ?? '');
        }

        $this->studentSearch = '';
        $this->closeStudentHistory();
        $this->resetRecorder();
        $this->resetValidation();
    }

    public function updatedHalaqaId(): void
    {
        $this->studentSearch = '';
        $this->closeStudentHistory();
        $this->resetRecorder();
        $this->resetValidation();
    }

    public function updatedTeacherProfileId(mixed $value): void
    {
        $teacher = auth()->user()?->teacherProfile()->where('active', true)->first();
        if ($teacher && (int) $value !== (int) $teacher->id) {
            $this->teacherProfileId = $teacher->id;
            $this->addError('teacherProfileId', 'تم رفض تغيير هوية المحفّظ. أعد المحاولة من حسابك الحالي.');
        }
    }

    public function updatedAttendanceStatus(string $status): void
    {
        $this->resetValidation(['attendanceStatus', 'items']);

        $attendanceStatus = AttendanceStatus::tryFrom($status);
        if ($attendanceStatus?->isAbsence()) {
            foreach ($this->items as $index => $item) {
                $this->items[$index]['enabled'] = false;
            }
            $this->generalEvaluation = '';

            return;
        }

        if ($this->studentId !== '' && $this->quranReferenceIsReady() && ! collect($this->items)->contains('enabled', true)) {
            $this->items[0]['enabled'] = true;
        }
    }

    public function updatedItems(mixed $value, string $key): void
    {
        [$index, $field] = array_pad(explode('.', $key, 2), 2, null);
        $index = (int) $index;

        if (! isset($this->items[$index]) || $field === null) {
            return;
        }

        $this->resetValidation([
            "items.{$index}.{$field}",
            "items.{$index}.end_surah_id",
            "items.{$index}.end_ayah_number",
            'quran_range',
        ]);

        if ($field === 'start_surah_id') {
            $this->selectSurah($index, 'start', (string) ($value ?? ''));

            return;
        }

        if ($field === 'end_surah_id') {
            $this->selectSurah($index, 'end', (string) ($value ?? ''));

            return;
        }

        if ($field !== 'start_ayah_number' || ! ctype_digit((string) $value)) {
            return;
        }

        $item = $this->items[$index];
        if ($item['start_surah_id'] !== '' && $item['start_surah_id'] === $item['end_surah_id']) {
            $endAyah = (int) ($item['end_ayah_number'] ?: 0);
            if ($endAyah < (int) $value) {
                $this->items[$index]['end_ayah_number'] = (string) $value;
            }
        }
    }

    public function selectStudent(int $studentId): void
    {
        $this->resetValidation();

        if ($this->halaqaId === '' || $this->recordDate === '') {
            $this->addError('studentId', 'اختر الحلقة والتاريخ أولًا.');

            return;
        }

        $student = $this->studentInSelectedHalaqa($studentId);

        if (! $student) {
            $this->addError('studentId', 'الطالب غير متاح في هذه الحلقة في التاريخ المحدد.');

            return;
        }

        if ($student->dailyRecords()->whereDate('record_date', $this->recordDate)->exists()) {
            $this->addError('studentId', 'تم تسجيل هذا الطالب مسبقًا في التاريخ المحدد.');

            return;
        }

        $this->resetSessionDetails();
        $this->studentId = (string) $student->id;
        if ($this->quranReferenceIsReady()) {
            $this->items[0]['enabled'] = true;
        } else {
            $this->addError('quranReference', 'مرجع السور والآيات غير مهيّأ بعد. يمكن تسجيل الحضور فقط إلى أن يستكمل مدير النظام التهيئة.');
        }
        $this->dispatch('daily-student-selected');
    }

    public function clearSelectedStudent(): void
    {
        $this->resetRecorder();
        $this->resetValidation();
    }

    public function showStudentHistory(int $studentId): void
    {
        Gate::authorize('recitations.view');

        $student = $this->studentInSelectedHalaqa($studentId);
        if (! $student) {
            $this->historyStudentId = '';
            $this->addError('historyStudentId', 'لا يمكن عرض سجل طالب خارج الحلقة المسندة إليك.');

            return;
        }

        $this->historyStudentId = (string) $student->id;
        $this->historyFilter = 'all';
        $this->historyLimit = 5;
        $this->resetValidation('historyStudentId');
        $this->dispatch('daily-history-opened');
    }

    public function closeStudentHistory(): void
    {
        $this->historyStudentId = '';
        $this->historyFilter = 'all';
        $this->historyLimit = 5;
        $this->resetValidation('historyStudentId');
    }

    public function setHistoryFilter(string $filter): void
    {
        if (! in_array($filter, ['all', 'memorization', 'revision'], true)) {
            return;
        }

        $this->historyFilter = $filter;
        $this->historyLimit = 5;
    }

    public function loadMoreHistory(): void
    {
        if ($this->historyStudentId === '' || ! $this->studentInSelectedHalaqa((int) $this->historyStudentId)) {
            $this->closeStudentHistory();

            return;
        }

        $this->historyLimit = min(30, max(5, $this->historyLimit) + 5);
    }

    public function toggleItem(int $index): void
    {
        if (! isset($this->items[$index])) {
            return;
        }

        if (! $this->quranReferenceIsReady()) {
            $this->addError('quranReference', 'مرجع السور والآيات غير مهيّأ بعد؛ لا يمكن إضافة بند تسميع قبل استكماله.');

            return;
        }

        if (AttendanceStatus::tryFrom($this->attendanceStatus)?->isAbsence()) {
            $this->addError('items', 'لا يمكن إضافة تسميع للطالب الغائب، سواء كان الغياب بعذر أو دون عذر.');

            return;
        }

        $this->items[$index]['enabled'] = ! $this->items[$index]['enabled'];
        $this->resetValidation("items.{$index}");
    }

    public function selectSurah(int $index, string $boundary, string $surahId): void
    {
        if (! isset($this->items[$index]) || ! in_array($boundary, ['start', 'end'], true)) {
            return;
        }

        $field = "{$boundary}_surah_id";
        $ayahField = "{$boundary}_ayah_number";
        $this->resetValidation(["items.{$index}.{$field}", "items.{$index}.{$ayahField}", 'quran_range']);

        if ($surahId === '') {
            $this->items[$index][$field] = '';
            $this->items[$index][$ayahField] = '';

            if ($boundary === 'start') {
                $this->items[$index]['end_surah_id'] = '';
                $this->items[$index]['end_ayah_number'] = '';
            }

            return;
        }

        $surah = QuranSurah::query()->find((int) $surahId);
        if (! $surah) {
            $this->addError("items.{$index}.{$field}", 'السورة المحددة غير موجودة.');

            return;
        }

        if ($boundary === 'end') {
            $startSurahId = (int) ($this->items[$index]['start_surah_id'] ?: 0);
            if ($startSurahId > 0 && $surah->id < $startSurahId) {
                $this->addError("items.{$index}.end_surah_id", 'سورة النهاية لا يمكن أن تسبق سورة البداية.');

                return;
            }

            $this->items[$index]['end_surah_id'] = (string) $surah->id;
            $startAyah = (int) ($this->items[$index]['start_ayah_number'] ?: 0);
            $this->items[$index]['end_ayah_number'] = $startSurahId === $surah->id && $startAyah > 0
                ? (string) $startAyah
                : '';

            return;
        }

        $this->items[$index]['start_surah_id'] = (string) $surah->id;
        $this->items[$index]['start_ayah_number'] = '';
        $this->items[$index]['end_surah_id'] = (string) $surah->id;
        $this->items[$index]['end_ayah_number'] = '';
    }

    public function copyStartToEnd(int $index): void
    {
        if (! isset($this->items[$index]) || $this->items[$index]['start_surah_id'] === '') {
            return;
        }

        $this->items[$index]['end_surah_id'] = $this->items[$index]['start_surah_id'];
        $this->items[$index]['end_ayah_number'] = $this->items[$index]['start_ayah_number'];
        $this->resetValidation([
            "items.{$index}.end_surah_id",
            "items.{$index}.end_ayah_number",
            'quran_range',
        ]);
    }

    public function save(RecordStudentDailyRecordAction $recordDaily): void
    {
        if ($this->getErrorBag()->has('teacherProfileId')) {
            return;
        }

        $teacher = $this->authenticatedTeacherProfile();
        $data = $this->validate([
            'recordDate' => ['required', 'date', 'before_or_equal:today'],
            'halaqaId' => ['required', 'exists:halaqas,id'],
            'studentId' => ['required', 'exists:students,id'],
            'attendanceStatus' => ['required', Rule::enum(AttendanceStatus::class)],
            'attendanceNotes' => ['nullable', 'string', 'max:2000'],
            'generalEvaluation' => ['nullable', Rule::enum(EvaluationRating::class)],
            'notes' => ['nullable', 'string', 'max:3000'],
            'items' => ['array'],
            'items.*.enabled' => ['boolean'],
            'items.*.type' => ['required', Rule::enum(RecitationType::class)],
            'items.*.start_surah_id' => ['nullable'],
            'items.*.start_ayah_number' => ['nullable'],
            'items.*.end_surah_id' => ['nullable'],
            'items.*.end_ayah_number' => ['nullable'],
            'items.*.evaluation' => ['required', Rule::enum(EvaluationRating::class)],
            'items.*.memorization_errors' => ['integer', 'between:0,999'],
            'items.*.tajweed_errors' => ['integer', 'between:0,999'],
            'items.*.hesitation_count' => ['integer', 'between:0,999'],
            'items.*.teacher_prompt_count' => ['integer', 'between:0,999'],
            'items.*.notes' => ['nullable', 'string', 'max:2000'],
        ], [], [
            'recordDate' => 'التاريخ',
            'halaqaId' => 'الحلقة',
            'studentId' => 'الطالب',
            'attendanceStatus' => 'حالة الحضور',
            'attendanceNotes' => 'ملاحظة الحضور',
            'generalEvaluation' => 'التقييم العام',
            'notes' => 'ملاحظات الجلسة',
        ]);

        if (collect($data['items'])->contains(fn (array $item) => ! empty($item['enabled'])) && ! $this->quranReferenceIsReady()) {
            $this->addError('quranReference', 'مرجع السور والآيات غير مهيّأ بعد؛ لا يمكن حفظ التسميع قبل استكماله.');

            return;
        }

        $halaqa = Halaqa::query()->findOrFail($data['halaqaId']);
        $student = Student::query()->findOrFail($data['studentId']);
        Gate::authorize('create', [DailyRecord::class, $halaqa, $data['recordDate']]);

        $attendanceStatus = AttendanceStatus::from($data['attendanceStatus']);
        if ($attendanceStatus->isAbsence()) {
            $data['generalEvaluation'] = '';
            foreach ($data['items'] as $index => $item) {
                if (! empty($item['enabled'])) {
                    throw ValidationException::withMessages([
                        "items.{$index}.enabled" => 'لا يمكن تسجيل بنود تسميع لطالب غائب.',
                    ]);
                }
            }
        }

        $items = [];
        foreach ($data['items'] as $index => $item) {
            if (! $item['enabled']) {
                continue;
            }

            $rangeValidator = validator($item, [
                'start_surah_id' => ['required', 'exists:quran_surahs,id'],
                'start_ayah_number' => ['required', 'integer', 'min:1'],
                'end_surah_id' => ['required', 'exists:quran_surahs,id'],
                'end_ayah_number' => ['required', 'integer', 'min:1'],
            ], [], [
                'start_surah_id' => 'سورة البداية',
                'start_ayah_number' => 'آية البداية',
                'end_surah_id' => 'سورة النهاية',
                'end_ayah_number' => 'آية النهاية',
            ]);
            if ($rangeValidator->fails()) {
                $messages = [];
                foreach ($rangeValidator->errors()->messages() as $field => $fieldMessages) {
                    $messages["items.{$index}.{$field}"] = $fieldMessages;
                }
                throw ValidationException::withMessages($messages);
            }
            $range = $rangeValidator->validated();

            $start = $this->ayah((int) $range['start_surah_id'], (int) $range['start_ayah_number'], "items.{$index}.start_ayah_number");
            $end = $this->ayah((int) $range['end_surah_id'], (int) $range['end_ayah_number'], "items.{$index}.end_ayah_number");
            if ($end->global_order < $start->global_order) {
                throw ValidationException::withMessages([
                    "items.{$index}.end_ayah_number" => 'نهاية نطاق التسميع يجب ألا تسبق بدايته.',
                ]);
            }

            $items[] = $item + ['start_ayah_id' => $start->id, 'end_ayah_id' => $end->id];
        }

        try {
            $recordDaily->execute($student, $halaqa, $teacher, [
                'record_date' => $data['recordDate'],
                'attendance_status' => $data['attendanceStatus'],
                'attendance_notes' => $data['attendanceNotes'] ?: null,
                'general_evaluation' => $data['generalEvaluation'] ?: null,
                'notes' => $data['notes'] ?: null,
                'items' => $items,
            ], auth()->user());
        } catch (ValidationException $exception) {
            $this->rethrowForComponent($exception);
        }

        $studentName = $student->full_name;
        $itemsCount = count($items);
        $this->resetRecorder();
        session()->flash('success', "تم حفظ الحضور والتسميع اليومي للطالب. {$studentName}: {$itemsCount} بنود تسميع. يمكنك الآن اختيار الطالب التالي.");
    }

    public function exportMemorizationRecords(ReportExportService $exports): void
    {
        Gate::authorize('recitations.export');
        if ($this->getErrorBag()->has('teacherProfileId')) {
            return;
        }

        $teacher = $this->authenticatedTeacherProfile();
        $data = $this->validate([
            'exportDateFrom' => ['required', 'date', 'before_or_equal:exportDateTo'],
            'exportDateTo' => ['required', 'date', 'after_or_equal:exportDateFrom', 'before_or_equal:today'],
        ], [], [
            'exportDateFrom' => 'بداية فترة التصدير',
            'exportDateTo' => 'نهاية فترة التصدير',
        ]);

        $export = $exports->request('memorization_records', [
            'date_from' => $data['exportDateFrom'],
            'date_to' => $data['exportDateTo'],
            'teacher_profile_id' => $teacher->id,
        ], auth()->user());

        $this->latestExportId = $export->id;
        $this->showExportPanel = true;
        session()->flash(
            'success',
            $export->status === 'ready'
                ? 'تم إعداد ملف سجلات الحفظ، ويمكنك تنزيله الآن.'
                : 'بدأ إعداد ملف سجلات الحفظ، وسيظهر في مركز التقارير عند اكتماله.',
        );
    }

    public function render(): View
    {
        $halaqas = $this->assignedHalaqas();
        $hasAssignedHalaqa = $halaqas->contains('id', (int) $this->halaqaId);
        $allStudents = Student::query()
            ->when($hasAssignedHalaqa, fn ($query) => $query->whereHas('enrollments', function ($enrollments) {
                $enrollments->where('halaqa_id', $this->halaqaId)
                    ->whereDate('starts_at', '<=', $this->recordDate)
                    ->where(fn ($dates) => $dates->whereNull('ends_at')->orWhereDate('ends_at', '>=', $this->recordDate));
            }), fn ($query) => $query->whereRaw('1 = 0'))
            ->select(['id', 'student_number', 'full_name', 'first_name', 'family_name', 'photo_private_file_id'])
            ->with('photo:id')
            ->withExists(['dailyRecords as recorded_for_date' => fn ($query) => $query->whereDate('record_date', $this->recordDate)])
            ->withCount('dailyRecords')
            ->withMax('dailyRecords', 'record_date')
            ->where('status', 'active')
            ->orderBy('full_name')
            ->get();

        $students = $allStudents;
        if (trim($this->studentSearch) !== '') {
            $search = $this->normalizeSearch($this->studentSearch);
            $students = $allStudents->filter(fn (Student $student) => str_contains(
                $this->normalizeSearch($student->full_name.' '.$student->student_number),
                $search,
            ));
        }

        $surahs = QuranSurah::query()->orderBy('id')->get(['id', 'name_arabic', 'verses_count']);
        $quranReferenceReady = $surahs->count() === 114 && QuranAyah::query()->count() === 6236;
        $historyStudent = $allStudents->firstWhere('id', (int) $this->historyStudentId);
        $historyRecords = collect();
        $historySummary = ['total' => 0, 'filtered' => 0, 'last_date' => null];

        if ($historyStudent) {
            $allHistory = DailyRecord::query()->where('student_id', $historyStudent->id);
            $historySummary['total'] = (clone $allHistory)->count();
            $historySummary['last_date'] = (clone $allHistory)->max('record_date');

            $filteredHistory = match ($this->historyFilter) {
                'memorization' => (clone $allHistory)->whereHas(
                    'recitationItems',
                    fn ($items) => $items->where('type', RecitationType::NewMemorization->value),
                ),
                'revision' => (clone $allHistory)->whereHas(
                    'recitationItems',
                    fn ($items) => $items->whereIn('type', [
                        RecitationType::RecentRevision->value,
                        RecitationType::OldRevision->value,
                    ]),
                ),
                default => clone $allHistory,
            };

            $historySummary['filtered'] = (clone $filteredHistory)->count();
            $historyRecords = $filteredHistory
                ->with([
                    'attendance',
                    'halaqa:id,name',
                    'teacher.user:id,name',
                    'recitationItems.startAyah.surah:id,name_arabic',
                    'recitationItems.endAyah.surah:id,name_arabic',
                ])
                ->latest('record_date')
                ->latest('id')
                ->limit(min(30, max(5, $this->historyLimit)))
                ->get();
        }

        return view('livewire.teacher-daily-recorder', [
            'halaqas' => $halaqas,
            'students' => $students,
            'selectedStudent' => $allStudents->firstWhere('id', (int) $this->studentId),
            'historyStudent' => $historyStudent,
            'historyRecords' => $historyRecords,
            'historySummary' => $historySummary,
            'studentStats' => [
                'total' => $allStudents->count(),
                'recorded' => $allStudents->where('recorded_for_date', true)->count(),
                'waiting' => $allStudents->where('recorded_for_date', false)->count(),
            ],
            'surahs' => $surahs,
            'quranReferenceReady' => $quranReferenceReady,
            'rangeSummaries' => $this->rangeSummaries($surahs),
            'attendanceStatuses' => AttendanceStatus::cases(),
            'evaluations' => EvaluationRating::cases(),
            'recitationTypes' => collect(RecitationType::cases())->keyBy(fn (RecitationType $type) => $type->value),
            'latestExport' => $this->latestExportId
                ? ReportExport::query()->where('user_id', auth()->id())->with('privateFile:id,original_name')->find($this->latestExportId)
                : null,
        ]);
    }

    /** @return Collection<int, Halaqa> */
    private function assignedHalaqas(): Collection
    {
        $teacher = $this->authenticatedTeacherProfile(false);

        return Halaqa::query()
            ->where('active', true)
            ->whereHas('teacherAssignments', function ($assignments) use ($teacher) {
                $assignments->where('teacher_profile_id', $teacher->id)
                    ->whereDate('starts_at', '<=', $this->recordDate)
                    ->where(fn ($dates) => $dates->whereNull('ends_at')->orWhereDate('ends_at', '>=', $this->recordDate));
            })
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function authenticatedTeacherProfile(bool $rejectIdentityChange = true): TeacherProfile
    {
        $teacher = auth()->user()?->teacherProfile()
            ->where('active', true)
            ->whereHas('user', fn ($query) => $query->where('active', true)->whereNull('archived_at'))
            ->first();

        if (! $teacher) {
            throw ValidationException::withMessages([
                'teacherProfileId' => 'لا يوجد ملف محفّظ فعّال لهذا الحساب.',
            ]);
        }

        if (isset($this->teacherProfileId) && $this->teacherProfileId !== $teacher->id) {
            $this->teacherProfileId = $teacher->id;

            if ($rejectIdentityChange) {
                throw ValidationException::withMessages([
                    'teacherProfileId' => 'تم رفض تغيير هوية المحفّظ. أعد المحاولة من حسابك الحالي.',
                ]);
            }
        }

        return $teacher;
    }

    private function studentInSelectedHalaqa(int $studentId): ?Student
    {
        if ($this->halaqaId === '' || $this->recordDate === '') {
            return null;
        }

        $teacher = $this->authenticatedTeacherProfile(false);
        $hasAssignedHalaqa = Halaqa::query()
            ->whereKey((int) $this->halaqaId)
            ->where('active', true)
            ->whereHas('teacherAssignments', function ($assignments) use ($teacher) {
                $assignments->where('teacher_profile_id', $teacher->id)
                    ->whereDate('starts_at', '<=', $this->recordDate)
                    ->where(fn ($dates) => $dates->whereNull('ends_at')->orWhereDate('ends_at', '>=', $this->recordDate));
            })
            ->exists();

        if (! $hasAssignedHalaqa) {
            return null;
        }

        return Student::query()
            ->whereKey($studentId)
            ->where('status', 'active')
            ->whereHas('enrollments', function ($enrollments) {
                $enrollments->where('halaqa_id', $this->halaqaId)
                    ->whereDate('starts_at', '<=', $this->recordDate)
                    ->where(fn ($dates) => $dates->whereNull('ends_at')->orWhereDate('ends_at', '>=', $this->recordDate));
            })
            ->first();
    }

    private function ayah(int $surahId, int $ayahNumber, string $field): QuranAyah
    {
        $ayah = QuranAyah::query()->where('surah_id', $surahId)->where('ayah_number', $ayahNumber)->first();
        if (! $ayah) {
            throw ValidationException::withMessages([$field => 'رقم الآية غير موجود في السورة المختارة.']);
        }

        return $ayah;
    }

    private function quranReferenceIsReady(): bool
    {
        return QuranSurah::query()->count() === 114
            && QuranAyah::query()->count() === 6236;
    }

    private function resetRecorder(): void
    {
        $this->studentId = '';
        $this->resetSessionDetails();
    }

    private function resetSessionDetails(): void
    {
        $this->attendanceStatus = AttendanceStatus::Present->value;
        $this->attendanceNotes = '';
        $this->generalEvaluation = '';
        $this->notes = '';
        $this->items = $this->freshItems();
    }

    private function freshItems(): array
    {
        return collect(RecitationType::cases())->map(fn (RecitationType $type) => [
            'enabled' => false,
            'type' => $type->value,
            'start_surah_id' => '',
            'start_ayah_number' => '',
            'end_surah_id' => '',
            'end_ayah_number' => '',
            'evaluation' => EvaluationRating::Good->value,
            'memorization_errors' => 0,
            'tajweed_errors' => 0,
            'hesitation_count' => 0,
            'teacher_prompt_count' => 0,
            'notes' => '',
        ])->all();
    }

    private function rethrowForComponent(ValidationException $exception): never
    {
        $aliases = [
            'record_date' => 'recordDate',
            'halaqa_id' => 'halaqaId',
            'student_id' => 'studentId',
        ];
        $messages = [];

        foreach ($exception->errors() as $field => $fieldMessages) {
            $messages[$aliases[$field] ?? $field] = $fieldMessages;
        }

        throw ValidationException::withMessages($messages);
    }

    private function normalizeSearch(string $value): string
    {
        return Str::of($value)
            ->lower()
            ->replace(['أ', 'إ', 'آ', 'ٱ'], 'ا')
            ->replace('ة', 'ه')
            ->replace('ى', 'ي')
            ->replaceMatches('/[\x{064B}-\x{065F}\x{0670}\x{0640}]/u', '')
            ->squish()
            ->toString();
    }

    private function rangeSummaries(Collection $surahs): array
    {
        $offsets = [];
        $offset = 0;
        foreach ($surahs as $surah) {
            $offsets[$surah->id] = $offset;
            $offset += $surah->verses_count;
        }

        return collect($this->items)->map(function (array $item) use ($surahs, $offsets) {
            $startSurah = $surahs->firstWhere('id', (int) $item['start_surah_id']);
            $endSurah = $surahs->firstWhere('id', (int) $item['end_surah_id']);
            $startAyah = (int) ($item['start_ayah_number'] ?: 0);
            $endAyah = (int) ($item['end_ayah_number'] ?: 0);
            $complete = $startSurah && $endSurah && $startAyah > 0 && $endAyah > 0
                && $startAyah <= $startSurah->verses_count
                && $endAyah <= $endSurah->verses_count;

            if (! $complete) {
                return ['complete' => false, 'valid' => false, 'count' => 0, 'label' => 'أكمل اختيار السورة والآيات لعرض النطاق.'];
            }

            $startOrder = $offsets[$startSurah->id] + $startAyah;
            $endOrder = $offsets[$endSurah->id] + $endAyah;
            $valid = $endOrder >= $startOrder;
            $label = $startSurah->id === $endSurah->id
                ? "سورة {$startSurah->name_arabic}، من الآية {$startAyah} إلى {$endAyah}"
                : "من {$startSurah->name_arabic} {$startAyah} إلى {$endSurah->name_arabic} {$endAyah}";

            return [
                'complete' => true,
                'valid' => $valid,
                'count' => $valid ? $endOrder - $startOrder + 1 : 0,
                'label' => $valid ? $label : 'النهاية تسبق البداية؛ عدّل موضع النهاية.',
            ];
        })->all();
    }
}
