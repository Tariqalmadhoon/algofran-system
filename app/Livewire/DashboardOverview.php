<?php

namespace App\Livewire;

use App\Models\Halaqa;
use App\Models\TeacherProfile;
use App\Services\DashboardMetricsService;
use App\Services\StudentVisibilityService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class DashboardOverview extends Component
{
    public string $dateFrom = '';

    public string $dateTo = '';

    public string $halaqaId = '';

    public string $teacherId = '';

    public string $studentId = '';

    public string $program = '';

    public function mount(): void
    {
        $this->dateFrom = today()->startOfMonth()->toDateString();
        $this->dateTo = today()->toDateString();
    }

    public function resetFilters(): void
    {
        $this->reset('halaqaId', 'teacherId', 'studentId', 'program');
        $this->dateFrom = today()->startOfMonth()->toDateString();
        $this->dateTo = today()->toDateString();
    }

    public function render(DashboardMetricsService $metrics, StudentVisibilityService $visibility): View
    {
        $user = auth()->user();
        $seesAll = $user->hasRole('super-admin');
        $centerId = $user->staffProfile?->center_id ?? $user->teacherProfile?->center_id;
        $visibleHalaqaIds = $visibility->queryFor($user)->whereNotNull('current_halaqa_id')->select('current_halaqa_id');
        $halaqaQuery = Halaqa::query()->where('active', true);
        if (! $seesAll) {
            if ($user->hasAnyRole(['center-manager', 'academic-supervisor', 'registrar']) && $centerId) {
                $halaqaQuery->where('center_id', $centerId);
            } elseif ($user->requiresTeacherAssignmentScope() && $user->teacherProfile) {
                $halaqaQuery->whereHas('teacherAssignments', fn (Builder $assignments) => $assignments
                    ->where('teacher_profile_id', $user->teacherProfile->id)
                    ->whereDate('starts_at', '<=', today())
                    ->where(fn (Builder $dates) => $dates->whereNull('ends_at')->orWhereDate('ends_at', '>=', today())));
            } else {
                $halaqaQuery->whereIn('id', clone $visibleHalaqaIds);
            }
        }

        $filters = [
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'branch_id' => null,
            'halaqa_id' => $this->halaqaId ?: null,
            'teacher_id' => $this->teacherId ?: null,
            'student_id' => $this->studentId ?: null,
            'program' => $this->program ?: null,
        ];

        return view('livewire.dashboard-overview', [
            'analytics' => $metrics->for($user, $filters),
            'halaqas' => (clone $halaqaQuery)->orderBy('name')->get(['id', 'name', 'program']),
            'teachers' => TeacherProfile::query()->where('active', true)
                ->when(! $seesAll && $centerId, fn (Builder $query) => $query->where('center_id', $centerId))
                ->when(! $seesAll && ! $centerId, fn (Builder $query) => $query->whereHas('assignments', fn (Builder $assignments) => $assignments->whereIn('halaqa_id', clone $visibleHalaqaIds)))
                ->with('user:id,name')->get(['id', 'user_id']),
            'studentsForFilter' => $visibility->queryFor($user)->orderBy('full_name')->limit(500)->get(['id', 'full_name']),
            'programs' => (clone $halaqaQuery)->whereNotNull('program')->distinct()->orderBy('program')->pluck('program'),
        ]);
    }
}
