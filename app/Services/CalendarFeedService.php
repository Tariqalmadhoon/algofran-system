<?php

namespace App\Services;

use App\Models\CalendarEvent;
use App\Models\Course;
use App\Models\HalaqaSchedule;
use App\Models\StudentAlert;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CalendarFeedService
{
    public function __construct(private readonly StudentVisibilityService $visibility) {}

    public function events(User $user, Carbon $from, Carbon $to, ?string $type = null, ?int $halaqaId = null): Collection
    {
        $studentQuery = $this->visibility->queryFor($user);
        $studentIds = (clone $studentQuery)->pluck('id');
        $halaqaIds = (clone $studentQuery)->whereNotNull('current_halaqa_id')->pluck('current_halaqa_id')->unique();
        $seesAll = $user->hasAnyRole(['super-admin', 'center-manager', 'academic-supervisor', 'registrar']);

        $custom = CalendarEvent::query()
            ->with(['halaqa:id,name', 'course:id,name', 'student:id,full_name'])
            ->where('starts_at', '<=', $to)
            ->where(fn (Builder $query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', $from))
            ->when(! $seesAll, fn (Builder $query) => $query->where(function (Builder $scope) use ($user, $halaqaIds, $studentIds) {
                $scope->where('privacy', '!=', 'restricted')
                    ->orWhere('created_by', $user->id)
                    ->orWhereIn('halaqa_id', $halaqaIds)
                    ->orWhereIn('student_id', $studentIds);
            }))
            ->when($type, fn (Builder $query) => $query->where('type', $type))
            ->when($halaqaId, fn (Builder $query) => $query->where('halaqa_id', $halaqaId))
            ->get()
            ->map(fn (CalendarEvent $event) => [
                'id' => 'event-'.$event->id,
                'source' => 'event',
                'type' => $event->type,
                'title' => $event->title,
                'description' => $event->description,
                'start' => $event->starts_at,
                'end' => $event->ends_at,
                'all_day' => $event->all_day,
                'location' => $event->location,
                'halaqa' => $event->halaqa?->name,
            ]);

        $result = collect($custom);

        if (! $type || $type === 'halaqa') {
            $schedules = HalaqaSchedule::query()->with('halaqa:id,name,room')
                ->whereHas('halaqa', fn (Builder $query) => $query->where('active', true))
                ->when(! $seesAll, fn (Builder $query) => $query->whereIn('halaqa_id', $halaqaIds))
                ->when($halaqaId, fn (Builder $query) => $query->where('halaqa_id', $halaqaId))
                ->get();

            foreach (CarbonPeriod::create($from->copy()->startOfDay(), $to->copy()->startOfDay()) as $day) {
                foreach ($schedules->where('weekday', $day->dayOfWeek) as $schedule) {
                    $start = Carbon::parse($day->toDateString().' '.$schedule->starts_at);
                    $end = Carbon::parse($day->toDateString().' '.$schedule->ends_at);
                    $result->push([
                        'id' => "halaqa-{$schedule->id}-{$day->format('Ymd')}", 'source' => 'schedule', 'type' => 'halaqa',
                        'title' => 'حلقة '.$schedule->halaqa->name, 'description' => 'موعد الحلقة الأسبوعي',
                        'start' => $start, 'end' => $end, 'all_day' => false,
                        'location' => $schedule->room ?: $schedule->halaqa->room, 'halaqa' => $schedule->halaqa->name,
                    ]);
                }
            }
        }

        if (! $type || $type === 'course') {
            Course::query()->with('enrollments:id,course_id,student_id')
                ->whereDate('starts_at', '<=', $to)
                ->where(fn (Builder $query) => $query->whereNull('ends_at')->orWhereDate('ends_at', '>=', $from))
                ->when(! $seesAll, fn (Builder $query) => $query->whereHas('enrollments', fn (Builder $enrollment) => $enrollment->whereIn('student_id', $studentIds)))
                ->get()
                ->each(fn (Course $course) => $result->push([
                    'id' => 'course-'.$course->id, 'source' => 'course', 'type' => 'course', 'title' => 'دورة: '.$course->name,
                    'description' => $course->description, 'start' => $course->starts_at->copy()->startOfDay(),
                    'end' => $course->ends_at?->copy()->endOfDay(), 'all_day' => true, 'location' => null, 'halaqa' => null,
                ]));
        }

        if (! $type || $type === 'revision') {
            StudentAlert::query()->with('student:id,full_name')->whereIn('student_id', $studentIds)
                ->where('type', 'revision_delay')->whereBetween('generated_at', [$from, $to])
                ->get()->each(fn (StudentAlert $alert) => $result->push([
                    'id' => 'revision-'.$alert->id, 'source' => 'alert', 'type' => 'revision',
                    'title' => 'مراجعة: '.$alert->student->full_name, 'description' => $alert->reason,
                    'start' => $alert->generated_at, 'end' => null, 'all_day' => true, 'location' => null, 'halaqa' => null,
                ]));
        }

        return $result->sortBy('start')->values();
    }
}
