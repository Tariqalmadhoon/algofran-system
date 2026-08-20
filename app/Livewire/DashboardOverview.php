<?php

namespace App\Livewire;

use App\Models\Branch;
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

    public string $branchId = '';

    public string $halaqaId = '';

    public string $teacherId = '';

    public string $studentId = '';

    public string $program = '';

    public function mount(): void
    {
        $this->dateFrom = today()->subDays(29)->toDateString();
        $this->dateTo = today()->toDateString();
    }

    public function render(DashboardMetricsService $metrics, StudentVisibilityService $visibility): View
    {
        $user = auth()->user();
        $seesAll = $user->hasAnyRole(['super-admin', 'center-manager', 'academic-supervisor', 'registrar']);
        $visibleHalaqaIds = $visibility->queryFor($user)->whereNotNull('current_halaqa_id')->select('current_halaqa_id');
        $halaqaQuery = Halaqa::query()->where('active', true)
            ->when(! $seesAll, fn (Builder $query) => $query->whereIn('id', clone $visibleHalaqaIds));

        $filters = [
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'branch_id' => $this->branchId ?: null,
            'halaqa_id' => $this->halaqaId ?: null,
            'teacher_id' => $this->teacherId ?: null,
            'student_id' => $this->studentId ?: null,
            'program' => $this->program ?: null,
        ];

        return view('livewire.dashboard-overview', [
            'analytics' => $metrics->for($user, $filters),
            'branches' => Branch::query()->where('active', true)
                ->when(! $seesAll, fn (Builder $query) => $query->whereHas('halaqas', fn (Builder $halaqas) => $halaqas->whereIn('id', clone $visibleHalaqaIds)))
                ->orderBy('name')->get(['id', 'name']),
            'halaqas' => (clone $halaqaQuery)->orderBy('name')->get(['id', 'name', 'program']),
            'teachers' => TeacherProfile::query()->where('active', true)
                ->when(! $seesAll, fn (Builder $query) => $query->whereHas('assignments', fn (Builder $assignments) => $assignments->whereIn('halaqa_id', clone $visibleHalaqaIds)))
                ->with('user:id,name')->get(['id', 'user_id']),
            'studentsForFilter' => $visibility->queryFor($user)->orderBy('full_name')->limit(500)->get(['id', 'full_name']),
            'programs' => (clone $halaqaQuery)->whereNotNull('program')->distinct()->orderBy('program')->pluck('program'),
        ]);
    }
}
