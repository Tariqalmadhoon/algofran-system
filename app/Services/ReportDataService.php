<?php

namespace App\Services;

use App\Enums\RecitationType;
use App\Models\Attendance;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\DailyRecord;
use App\Models\Halaqa;
use App\Models\QuranAyah;
use App\Models\RecitationItem;
use App\Models\Student;
use App\Models\StudentAlert;
use App\Models\StudentProgressSnapshot;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ReportDataService
{
    public function __construct(
        private readonly StudentVisibilityService $visibility,
        private readonly MemorizationJourneyService $memorizationJourney,
    ) {}

    public function types(): array
    {
        return [
            'student_comprehensive' => 'الكشف الشامل للطلاب والحفظ',
            'students' => 'تقرير الطلاب', 'halaqas' => 'تقرير الحلقات', 'teachers' => 'تقرير المحفّظين',
            'attendance' => 'تقرير الحضور', 'memorization' => 'ملخص تقدم الحفظ', 'memorization_records' => 'سجلات الحفظ والتسميع', 'revision' => 'تقرير المراجعة',
            'evaluation' => 'تقرير التقييم', 'alerts' => 'تقرير التنبيهات', 'courses' => 'تقرير الدورات',
            'certificates' => 'تقرير الشهادات', 'management_summary' => 'الملخص الإداري',
        ];
    }

    public function typesFor(User $user): array
    {
        return $this->canBuildComprehensive($user)
            ? $this->types()
            : collect($this->types())->except('student_comprehensive')->all();
    }

    public function canBuildComprehensive(User $user): bool
    {
        $centerExport = $user->can('reports.export') && $user->can('guardian.private-data.view');
        $teacherExport = $user->requiresTeacherAssignmentScope()
            && $user->can('recitations.export')
            && $user->teacherProfile?->active;

        return $centerExport || $teacherExport;
    }

    /** @return array{title:string, headings:array, rows:array, sheets?:array} */
    public function build(string $type, array $filters, User $user): array
    {
        abort_unless(array_key_exists($type, $this->types()), 422);
        if ($type === 'student_comprehensive') {
            abort_unless($this->canBuildComprehensive($user), 403);
        }
        $studentIds = $this->visibleStudentIds($user, $filters);

        if ($type === 'management_summary') {
            return $this->managementSummary($filters, $user, $studentIds);
        }

        [$headings, $rows] = match ($type) {
            'student_comprehensive' => $this->comprehensiveStudents($studentIds),
            'students' => $this->students($studentIds),
            'halaqas' => $this->halaqas($studentIds, $filters),
            'teachers' => $this->teachers($studentIds, $filters),
            'attendance' => $this->attendance($studentIds, $filters),
            'memorization' => $this->memorization($studentIds),
            'memorization_records' => $this->memorizationRecords($studentIds, $filters, $user),
            'revision' => $this->revision($studentIds, $filters),
            'evaluation' => $this->evaluation($studentIds, $filters),
            'alerts' => $this->alerts($studentIds, $filters),
            'courses' => $this->courses($studentIds, $filters),
            'certificates' => $this->certificates($studentIds, $filters),
        };

        return [
            'title' => $this->types()[$type],
            'headings' => $headings,
            'rows' => $rows,
            ...($type === 'student_comprehensive' ? ['layout' => 'comprehensive_students'] : []),
        ];
    }

    private function visibleStudentIds(User $user, array $filters): array
    {
        $query = $user->hasRole('report-viewer') && $user->can('reports.view')
            ? Student::query()
            : $this->visibility->queryFor($user);

        return $query
            ->when($filters['center_id'] ?? null, fn (Builder $query, $id) => $query
                ->whereHas('currentHalaqa', fn (Builder $halaqa) => $halaqa->where('center_id', $id)))
            ->when(
                $user->requiresTeacherAssignmentScope() ? $user->teacherProfile?->id : ($filters['teacher_profile_id'] ?? null),
                fn (Builder $query, $teacherId) => $query->whereHas('currentHalaqa.teacherAssignments', fn (Builder $assignments) => $assignments
                    ->where('teacher_profile_id', $teacherId)
                    ->whereDate('starts_at', '<=', today())
                    ->where(fn (Builder $dates) => $dates->whereNull('ends_at')->orWhereDate('ends_at', '>=', today()))),
            )
            ->when($filters['halaqa_id'] ?? null, fn (Builder $query, $id) => $query->where('current_halaqa_id', $id))
            ->when($filters['student_id'] ?? null, fn (Builder $query, $id) => $query->whereKey($id))
            ->pluck('id')->all();
    }

    private function students(array $ids): array
    {
        $rows = $this->visibilityRows($ids)->map(fn ($student) => [$student->student_number, $student->full_name, $student->currentHalaqa?->name ?: '—', $student->status->label(), $student->registration_date?->format('Y-m-d')])->all();

        return [['رقم الطالب', 'اسم الطالب', 'الحلقة', 'الحالة', 'تاريخ التسجيل'], $rows];
    }

    private function halaqas(array $studentIds, array $filters): array
    {
        $rows = Halaqa::query()->with(['branch:id,name', 'primaryTeacher.user:id,name'])->withCount(['currentStudents' => fn (Builder $query) => $query->whereIn('id', $studentIds)])
            ->when($filters['halaqa_id'] ?? null, fn (Builder $query, $id) => $query->whereKey($id))
            ->whereHas('currentStudents', fn (Builder $query) => $query->whereIn('id', $studentIds))->orderBy('name')->get()
            ->map(fn (Halaqa $halaqa) => [$halaqa->code, $halaqa->name, $halaqa->branch?->name ?: '—', $halaqa->primaryTeacher?->user?->name ?: '—', $halaqa->current_students_count, $halaqa->capacity])->all();

        return [['الرمز', 'الحلقة', 'الفرع', 'المحفّظ', 'عدد الطلاب', 'السعة'], $rows];
    }

    private function teachers(array $studentIds, array $filters): array
    {
        $rows = TeacherProfile::query()->with(['user:id,name', 'branch:id,name'])->withCount(['dailyRecords' => fn (Builder $query) => $this->dateFilter($query->whereIn('student_id', $studentIds), $filters, 'record_date')])
            ->whereHas('dailyRecords', fn (Builder $query) => $query->whereIn('student_id', $studentIds))->get()
            ->map(fn (TeacherProfile $teacher) => [$teacher->employee_number, $teacher->user->name, $teacher->branch?->name ?: '—', $teacher->specialization ?: '—', $teacher->daily_records_count])->all();

        return [['الرقم الوظيفي', 'اسم المحفّظ', 'الفرع', 'التخصص', 'السجلات خلال الفترة'], $rows];
    }

    private function attendance(array $studentIds, array $filters): array
    {
        $query = Attendance::query()->with(['student:id,student_number,full_name', 'halaqa:id,name'])->whereIn('student_id', $studentIds);
        $rows = $this->dateFilter($query, $filters, 'record_date')->latest('record_date')->get()->map(fn (Attendance $attendance) => [$attendance->record_date->format('Y-m-d'), $attendance->student->student_number, $attendance->student->full_name, $attendance->halaqa->name, $attendance->status->label(), $attendance->notes ?: '—'])->all();

        return [['التاريخ', 'رقم الطالب', 'اسم الطالب', 'الحلقة', 'الحالة', 'ملاحظات'], $rows];
    }

    private function memorization(array $studentIds): array
    {
        $latestIds = StudentProgressSnapshot::query()->whereIn('student_id', $studentIds)->selectRaw('MAX(id)')->groupBy('student_id');
        $rows = StudentProgressSnapshot::query()->with('student:id,student_number,full_name')->whereIn('id', $latestIds)->get()->map(fn ($snapshot) => [$snapshot->student->student_number, $snapshot->student->full_name, $snapshot->as_of_date->format('Y-m-d'), $snapshot->memorized_ayahs, $snapshot->memorized_percentage.'%', $snapshot->completed_juz, $snapshot->score])->all();

        return [['رقم الطالب', 'اسم الطالب', 'حتى تاريخ', 'الآيات المحفوظة', 'نسبة الحفظ', 'الأجزاء', 'مؤشر الأداء'], $rows];
    }

    private function comprehensiveStudents(array $studentIds): array
    {
        $students = Student::query()
            ->with([
                'guardians' => fn ($query) => $query
                    ->orderByDesc('guardian_student.is_primary')
                    ->orderBy('guardians.id'),
                'currentHalaqa.center:id,name',
                'currentHalaqa.primaryTeacher.user:id,name',
            ])
            ->whereIn('id', $studentIds)
            ->orderBy('full_name')
            ->get();

        $frontierOrders = DB::table('recitation_items')
            ->join('daily_records', 'daily_records.id', '=', 'recitation_items.daily_record_id')
            ->join('quran_ayahs', 'quran_ayahs.id', '=', 'recitation_items.start_ayah_id')
            ->where('recitation_items.type', RecitationType::NewMemorization->value)
            ->whereIn('daily_records.student_id', $studentIds)
            ->groupBy('daily_records.student_id')
            ->selectRaw('daily_records.student_id, MIN(quran_ayahs.global_order) as frontier_order')
            ->pluck('frontier_order', 'student_id');
        $frontiers = QuranAyah::query()
            ->with('surah:id,name_arabic')
            ->whereIn('global_order', $frontierOrders->values()->all())
            ->get(['id', 'surah_id', 'ayah_number', 'global_order', 'juz'])
            ->keyBy('global_order');

        $latestRecitations = RecitationItem::query()
            ->select('recitation_items.*', 'daily_records.student_id as report_student_id', 'daily_records.record_date as report_record_date')
            ->join('daily_records', 'daily_records.id', '=', 'recitation_items.daily_record_id')
            ->whereIn('daily_records.student_id', $studentIds)
            ->whereIn('recitation_items.type', [RecitationType::Recitation->value, RecitationType::Exam->value])
            ->with(['startAyah:id,juz', 'endAyah:id,juz'])
            ->orderByDesc('daily_records.record_date')
            ->orderByDesc('recitation_items.id')
            ->get()
            ->unique('report_student_id')
            ->keyBy('report_student_id');

        $rows = $students->values()->map(function (Student $student, int $index) use ($frontierOrders, $frontiers, $latestRecitations) {
            $guardian = $student->guardians->first();
            $teacher = $student->currentHalaqa?->primaryTeacher;
            $frontierOrder = $frontierOrders->get($student->id);
            $journey = $this->memorizationJourney->summarize(
                $frontierOrder ? $frontiers->get((int) $frontierOrder) : null,
            );
            $recitation = $latestRecitations->get($student->id);
            $completedJuz = (int) $journey['completed_juz'];

            return [
                $index + 1,
                $student->currentHalaqa?->center?->name ?: '—',
                $student->full_name,
                $student->identity_number ?: '—',
                $student->birth_date?->format('Y-m-d') ?: '—',
                $guardian?->identity_number ?: '—',
                $guardian?->full_name ?: '—',
                $this->relationshipLabel($guardian?->pivot?->relationship),
                $student->sponsorship_type ?: '—',
                $student->sponsorship_organization ?: '—',
                $teacher?->user?->name ?: '—',
                $teacher?->identity_number ?: '—',
                $recitation ? max((int) $recitation->startAyah->juz, (int) $recitation->endAyah->juz) : '—',
                $recitation ? min((int) $recitation->startAyah->juz, (int) $recitation->endAyah->juz) : '—',
                $completedJuz > 0 ? 30 : '—',
                $completedJuz > 0 ? 31 - $completedJuz : '—',
                $journey['frontier_surah_name'] ?: '—',
                $journey['frontier_ayah_number'] ?: '—',
            ];
        })->all();

        return [[
            'متسلسل', 'المركز', 'اسم الطالب رباعيًا', 'رقم هوية الطالب', 'تاريخ الميلاد',
            'رقم هوية ولي الأمر', 'اسم ولي الأمر', 'صلة القرابة لولي الأمر', 'نوع الكفالة', 'جهة الكفالة',
            'اسم المعلم رباعيًا', 'رقم هوية المعلم', 'السرد من', 'السرد إلى', 'الحفظ من', 'الحفظ إلى', 'السورة', 'الآية',
        ], $rows];
    }

    private function relationshipLabel(?string $relationship): string
    {
        return match ($relationship) {
            'father' => 'الأب',
            'mother' => 'الأم',
            'brother' => 'الأخ',
            'sister' => 'الأخت',
            'uncle' => 'العم/الخال',
            'aunt' => 'العمة/الخالة',
            'other' => 'أخرى',
            default => '—',
        };
    }

    private function memorizationRecords(array $studentIds, array $filters, User $user): array
    {
        $query = DailyRecord::query()
            ->with([
                'student:id,student_number,full_name,identity_number',
                'recitationItems.startAyah.surah:id,name_arabic',
                'recitationItems.endAyah.surah:id,name_arabic',
            ])
            ->whereIn('student_id', $studentIds)
            ->when(
                $user->requiresTeacherAssignmentScope(),
                fn (Builder $query) => $query->where('teacher_profile_id', $user->teacherProfile?->id ?? 0),
            )
            ->when(
                ! $user->requiresTeacherAssignmentScope() && ($filters['teacher_profile_id'] ?? null),
                fn (Builder $query) => $query->where('teacher_profile_id', $filters['teacher_profile_id']),
            )
            ->when($filters['halaqa_id'] ?? null, fn (Builder $query, $id) => $query->where('halaqa_id', $id));

        $rows = $this->dateFilter($query, $filters, 'record_date')
            ->orderByDesc('record_date')
            ->orderBy('student_id')
            ->get()
            ->map(function (DailyRecord $record) {
                $memorization = $record->recitationItems
                    ->filter(fn (RecitationItem $item) => $item->type === RecitationType::NewMemorization)
                    ->map(fn (RecitationItem $item) => $this->quranRangeLabel($item))
                    ->implode(' | ');
                $revision = $record->recitationItems
                    ->filter(fn (RecitationItem $item) => in_array($item->type, [RecitationType::RecentRevision, RecitationType::OldRevision], true))
                    ->map(fn (RecitationItem $item) => $item->type->label().': '.$this->quranRangeLabel($item))
                    ->implode(' | ');
                $evaluations = $record->recitationItems
                    ->filter(fn (RecitationItem $item) => in_array($item->type, [RecitationType::NewMemorization, RecitationType::RecentRevision, RecitationType::OldRevision], true))
                    ->map(fn (RecitationItem $item) => $item->type->label().': '.$item->evaluation->label())
                    ->unique()
                    ->values();

                if ($record->general_evaluation) {
                    $evaluations->prepend('عام: '.$record->general_evaluation->label());
                }

                return [
                    $record->student->student_number,
                    $record->record_date->locale('ar')->translatedFormat('Y-m-d — l'),
                    $record->student->full_name,
                    $record->student->identity_number ?: '—',
                    $memorization ?: '—',
                    $revision ?: '—',
                    $evaluations->implode(' | ') ?: '—',
                ];
            })
            ->all();

        return [['رقم الطالب', 'التاريخ واليوم', 'اسم الطالب', 'هوية الطالب', 'الحفظ', 'المراجعة', 'التقييم'], $rows];
    }

    private function quranRangeLabel(RecitationItem $item): string
    {
        $start = $item->startAyah;
        $end = $item->endAyah;

        if ($start->surah_id === $end->surah_id) {
            $ayahLabel = $start->ayah_number === $end->ayah_number
                ? "الآية {$start->ayah_number}"
                : "الآيات {$start->ayah_number}–{$end->ayah_number}";

            return "سورة {$start->surah->name_arabic}، {$ayahLabel}";
        }

        return "من سورة {$start->surah->name_arabic} آية {$start->ayah_number} إلى سورة {$end->surah->name_arabic} آية {$end->ayah_number}";
    }

    private function revision(array $studentIds, array $filters): array
    {
        $query = RecitationItem::query()->with(['dailyRecord.student:id,student_number,full_name'])->whereIn('type', ['recent_revision', 'old_revision'])->whereHas('dailyRecord', fn (Builder $query) => $query->whereIn('student_id', $studentIds));
        $query->whereHas('dailyRecord', fn (Builder $query) => $this->dateFilter($query, $filters, 'record_date'));
        $rows = $query->latest()->get()->map(fn (RecitationItem $item) => [$item->dailyRecord->record_date->format('Y-m-d'), $item->dailyRecord->student->student_number, $item->dailyRecord->student->full_name, $item->start_ayah_id, $item->end_ayah_id, $item->evaluation->label(), $item->memorization_errors + $item->tajweed_errors])->all();

        return [['التاريخ', 'رقم الطالب', 'اسم الطالب', 'من آية', 'إلى آية', 'التقييم', 'الأخطاء'], $rows];
    }

    private function evaluation(array $studentIds, array $filters): array
    {
        $query = DailyRecord::query()->with(['student:id,student_number,full_name', 'teacher.user:id,name'])->whereIn('student_id', $studentIds);
        $rows = $this->dateFilter($query, $filters, 'record_date')->latest('record_date')->get()->map(fn (DailyRecord $record) => [$record->record_date->format('Y-m-d'), $record->student->student_number, $record->student->full_name, $record->teacher->user->name, $record->general_evaluation?->label() ?: '—', $record->notes ?: '—'])->all();

        return [['التاريخ', 'رقم الطالب', 'اسم الطالب', 'المحفّظ', 'التقييم العام', 'ملاحظات'], $rows];
    }

    private function alerts(array $studentIds, array $filters): array
    {
        $query = StudentAlert::query()->with('student:id,student_number,full_name')->whereIn('student_id', $studentIds);
        $rows = $this->dateFilter($query, $filters, 'generated_at')->latest('generated_at')->get()->map(fn (StudentAlert $alert) => [$alert->generated_at->format('Y-m-d'), $alert->student->student_number, $alert->student->full_name, $alert->type, $alert->severity->label(), $alert->status->label(), $alert->reason])->all();

        return [['التاريخ', 'رقم الطالب', 'اسم الطالب', 'النوع', 'الخطورة', 'الحالة', 'السبب'], $rows];
    }

    private function courses(array $studentIds, array $filters): array
    {
        $rows = Course::query()->with(['branch:id,name', 'instructor.user:id,name'])->withCount(['enrollments' => fn (Builder $query) => $query->whereIn('student_id', $studentIds)])
            ->whereHas('enrollments', fn (Builder $query) => $query->whereIn('student_id', $studentIds))
            ->when($filters['date_from'] ?? null, fn (Builder $query, $date) => $query->whereDate('starts_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn (Builder $query, $date) => $query->whereDate('starts_at', '<=', $date))->get()
            ->map(fn (Course $course) => [$course->name, $course->branch?->name ?: '—', $course->instructor?->user?->name ?: '—', $course->starts_at->format('Y-m-d'), $course->ends_at?->format('Y-m-d') ?: '—', $course->status->label(), $course->enrollments_count])->all();

        return [['الدورة', 'الفرع', 'المدرّب', 'البداية', 'النهاية', 'الحالة', 'المسجلون'], $rows];
    }

    private function certificates(array $studentIds, array $filters): array
    {
        $query = Certificate::query()->with(['student:id,student_number,full_name', 'course:id,name'])->whereIn('student_id', $studentIds);
        $rows = $this->dateFilter($query, $filters, 'issued_at')->latest('issued_at')->get()->map(fn (Certificate $certificate) => [$certificate->certificate_number ?: '—', $certificate->student->student_number, $certificate->student->full_name, $certificate->name, $certificate->course?->name ?: '—', $certificate->issuer, $certificate->issued_at->format('Y-m-d'), $certificate->grade ?: '—'])->all();

        return [['رقم الشهادة', 'رقم الطالب', 'اسم الطالب', 'الشهادة', 'الدورة', 'الجهة', 'تاريخ الإصدار', 'التقدير'], $rows];
    }

    private function managementSummary(array $filters, User $user, array $studentIds): array
    {
        $attendance = $this->attendance($studentIds, $filters);
        $students = $this->students($studentIds);
        $halaqas = $this->halaqas($studentIds, $filters);
        $openAlerts = StudentAlert::query()->whereIn('student_id', $studentIds)->where('status', 'open')->count();
        $present = collect($attendance[1])->where(4, 'حاضر')->count();
        $rate = count($attendance[1]) ? round(($present / count($attendance[1])) * 100, 1).'%' : '0%';
        $summary = [['المؤشر', 'القيمة'], [['الطلاب الظاهرون', count($studentIds)], ['الحلقات', count($halaqas[1])], ['سجلات الحضور', count($attendance[1])], ['نسبة الحضور', $rate], ['التنبيهات المفتوحة', $openAlerts]]];

        return ['title' => $this->types()['management_summary'], 'headings' => $summary[0], 'rows' => $summary[1], 'sheets' => [
            ['title' => 'الملخص', 'headings' => $summary[0], 'rows' => $summary[1]],
            ['title' => 'الطلاب', 'headings' => $students[0], 'rows' => $students[1]],
            ['title' => 'الحلقات', 'headings' => $halaqas[0], 'rows' => $halaqas[1]],
            ['title' => 'الحضور', 'headings' => $attendance[0], 'rows' => $attendance[1]],
        ]];
    }

    private function visibilityRows(array $ids)
    {
        return Student::query()->with('currentHalaqa:id,name')->whereIn('id', $ids)->orderBy('full_name')->get();
    }

    private function dateFilter(Builder $query, array $filters, string $column): Builder
    {
        return $query->when($filters['date_from'] ?? null, fn (Builder $query, $date) => $query->whereDate($column, '>=', $date))
            ->when($filters['date_to'] ?? null, fn (Builder $query, $date) => $query->whereDate($column, '<=', $date));
    }
}
