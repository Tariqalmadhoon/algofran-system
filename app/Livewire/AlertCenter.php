<?php

namespace App\Livewire;

use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use App\Models\Halaqa;
use App\Models\Student;
use App\Models\StudentAlert;
use App\Services\StudentAchievementEngine;
use App\Services\StudentAlertEngine;
use App\Services\StudentProgressService;
use App\Services\StudentVisibilityService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class AlertCenter extends Component
{
    use WithPagination;

    public string $statusFilter = 'open';

    public string $severityFilter = '';

    public string $halaqaFilter = '';

    public string $search = '';

    public bool $teachingScope = false;

    public array $resolutionNotes = [];

    public function mount(): void
    {
        Gate::authorize('viewAny', StudentAlert::class);
        $this->teachingScope = request('scope') === 'teaching'
            && (bool) auth()->user()->teacherProfile?->active;
    }

    public function updated($property): void
    {
        if (in_array($property, ['statusFilter', 'severityFilter', 'halaqaFilter', 'search'], true)) {
            $this->resetPage();
        }
    }

    public function acknowledge(int $alertId, StudentAlertEngine $engine): void
    {
        $alert = StudentAlert::query()->findOrFail($alertId);
        Gate::authorize('update', $alert);
        $engine->acknowledge($alert, auth()->user());
        session()->flash('success', 'تم تحويل التنبيه إلى قيد المتابعة.');
    }

    public function resolve(int $alertId, StudentAlertEngine $engine): void
    {
        $alert = StudentAlert::query()->findOrFail($alertId);
        Gate::authorize('update', $alert);
        $data = validator(
            ['notes' => $this->resolutionNotes[$alertId] ?? null],
            ['notes' => ['required', 'string', 'min:5', 'max:2000']],
            [],
            ['notes' => 'ملاحظات المعالجة'],
        )->validate();
        $engine->resolve($alert, auth()->user(), $data['notes']);
        unset($this->resolutionNotes[$alertId]);
        session()->flash('success', 'تمت معالجة التنبيه وحفظ الإجراء.');
    }

    public function refreshInsights(
        StudentVisibilityService $visibility,
        StudentProgressService $progress,
        StudentAlertEngine $alerts,
        StudentAchievementEngine $achievements,
    ): void {
        Gate::authorize('alerts.manage');
        $count = 0;
        $this->visibleStudents($visibility)->where('status', 'active')->chunkById(100, function ($students) use ($progress, $alerts, $achievements, &$count) {
            foreach ($students as $student) {
                $snapshot = $progress->snapshot($student);
                $alerts->evaluate($student->refresh(), $snapshot);
                $achievements->evaluate($student, $snapshot);
                $count++;
            }
        });
        session()->flash('success', "تم تحديث مؤشرات وتنبيهات {$count} طالب.");
    }

    public function render(StudentVisibilityService $visibility): View
    {
        $studentIds = $this->visibleStudents($visibility)->select('students.id');
        $activeSeverityCounts = StudentAlert::query()
            ->whereIn('student_id', (clone $studentIds))
            ->whereIn('status', [AlertStatus::Open->value, AlertStatus::Acknowledged->value])
            ->selectRaw('severity, COUNT(*) as aggregate')
            ->groupBy('severity')
            ->pluck('aggregate', 'severity');
        $alerts = StudentAlert::query()
            ->whereIn('student_id', (clone $studentIds))
            ->with(['student:id,full_name,first_name,family_name,student_number,photo_private_file_id', 'student.photo:id', 'teacher.user:id,name', 'halaqa:id,name'])
            ->when($this->statusFilter, fn ($query) => $this->statusFilter === 'active'
                ? $query->whereIn('status', ['open', 'acknowledged'])
                : $query->where('status', $this->statusFilter))
            ->when($this->severityFilter, fn ($query) => $query->where('severity', $this->severityFilter))
            ->when($this->halaqaFilter, fn ($query) => $query->where('halaqa_id', $this->halaqaFilter))
            ->when($this->search, fn ($query) => $query->whereHas('student', fn ($student) => $student->where('full_name', 'like', '%'.trim($this->search).'%')->orWhere('student_number', 'like', '%'.trim($this->search).'%')))
            ->orderByRaw("CASE severity WHEN 'critical' THEN 1 WHEN 'warning' THEN 2 ELSE 3 END")
            ->latest('generated_at')
            ->paginate(15);

        return view('livewire.alert-center', [
            'alerts' => $alerts,
            'statuses' => AlertStatus::cases(),
            'severities' => AlertSeverity::cases(),
            'halaqas' => $this->visibleHalaqas()->orderBy('name')->get(['id', 'name']),
            'activeSeverityCounts' => $activeSeverityCounts,
            'hasTeachingProfile' => (bool) auth()->user()->teacherProfile?->active,
        ]);
    }

    /** @return Builder<Student> */
    private function visibleStudents(StudentVisibilityService $visibility): Builder
    {
        $query = $visibility->queryFor(auth()->user());
        $teacherId = auth()->user()->teacherProfile?->id;

        if (! $this->teachingScope || ! $teacherId) {
            return $query;
        }

        return $query->whereHas('currentHalaqa.teacherAssignments', fn (Builder $assignments) => $assignments
            ->where('teacher_profile_id', $teacherId)
            ->whereDate('starts_at', '<=', today())
            ->where(fn (Builder $dates) => $dates->whereNull('ends_at')->orWhereDate('ends_at', '>=', today())));
    }

    /** @return Builder<Halaqa> */
    private function visibleHalaqas(): Builder
    {
        $query = Halaqa::query()->where('active', true);
        $user = auth()->user();
        $teacherId = $user->teacherProfile?->id;

        if (! $this->teachingScope && $user->hasRole('super-admin')) {
            return $query;
        }

        if (! $this->teachingScope && $user->hasAnyRole(['center-manager', 'academic-supervisor', 'registrar'])) {
            $centerId = $user->staffProfile?->center_id;

            return $centerId
                ? $query->where('center_id', $centerId)
                : $query->whereRaw('1 = 0');
        }

        if (! $teacherId) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('teacherAssignments', fn (Builder $assignments) => $assignments
            ->where('teacher_profile_id', $teacherId)
            ->whereDate('starts_at', '<=', today())
            ->where(fn (Builder $dates) => $dates->whereNull('ends_at')->orWhereDate('ends_at', '>=', today())));
    }
}
