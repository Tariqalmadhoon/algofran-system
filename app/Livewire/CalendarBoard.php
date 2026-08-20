<?php

namespace App\Livewire;

use App\Models\CalendarEvent;
use App\Models\Center;
use App\Models\Halaqa;
use App\Models\User;
use App\Notifications\SystemNotification;
use App\Services\AuditLogger;
use App\Services\CalendarFeedService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;

class CalendarBoard extends Component
{
    public string $viewMode = 'month';

    public string $anchorDate = '';

    public string $typeFilter = '';

    public string $halaqaFilter = '';

    public bool $showEventForm = false;

    public string $eventTitle = '';

    public string $eventType = 'event';

    public string $eventStartsAt = '';

    public string $eventEndsAt = '';

    public string $eventLocation = '';

    public string $eventDescription = '';

    public string $eventPrivacy = 'internal';

    public string $eventCenterId = '';

    public string $eventHalaqaId = '';

    public bool $eventAllDay = false;

    public function mount(): void
    {
        Gate::authorize('calendar.view');
        $this->anchorDate = today()->toDateString();
        $this->eventStartsAt = now()->addHour()->format('Y-m-d\TH:i');
    }

    public function setView(string $mode): void
    {
        abort_unless(in_array($mode, ['day', 'week', 'month'], true), 422);
        $this->viewMode = $mode;
    }

    public function move(int $direction): void
    {
        $date = Carbon::parse($this->anchorDate);
        $this->anchorDate = match ($this->viewMode) {
            'day' => $date->addDays($direction)->toDateString(),
            'week' => $date->addWeeks($direction)->toDateString(),
            default => $date->addMonthsNoOverflow($direction)->toDateString(),
        };
    }

    public function today(): void
    {
        $this->anchorDate = today()->toDateString();
    }

    public function saveEvent(AuditLogger $audit): void
    {
        Gate::authorize('calendar.manage');
        $data = $this->validate([
            'eventTitle' => ['required', 'string', 'max:255'],
            'eventType' => ['required', Rule::in(array_keys($this->eventTypes()))],
            'eventStartsAt' => ['required', 'date'],
            'eventEndsAt' => ['nullable', 'date', 'after_or_equal:eventStartsAt'],
            'eventLocation' => ['nullable', 'string', 'max:255'],
            'eventDescription' => ['nullable', 'string', 'max:3000'],
            'eventPrivacy' => ['required', Rule::in(['internal', 'restricted'])],
            'eventCenterId' => ['nullable', 'exists:centers,id'],
            'eventHalaqaId' => ['nullable', 'exists:halaqas,id'],
            'eventAllDay' => ['boolean'],
        ]);
        $event = CalendarEvent::query()->create([
            'title' => $data['eventTitle'], 'type' => $data['eventType'], 'starts_at' => $data['eventStartsAt'],
            'ends_at' => $data['eventEndsAt'] ?: null, 'location' => $data['eventLocation'] ?: null,
            'description' => $data['eventDescription'] ?: null, 'privacy' => $data['eventPrivacy'],
            'center_id' => $data['eventCenterId'] ?: null, 'halaqa_id' => $data['eventHalaqaId'] ?: null,
            'all_day' => $data['eventAllDay'], 'created_by' => auth()->id(),
        ]);
        $audit->record('calendar-event.created', $event, newValues: $event->only(['title', 'type', 'starts_at', 'privacy']));
        User::permission('notifications.view')->where('id', '!=', auth()->id())->get()->each->notify(
            new SystemNotification('موعد جديد في التقويم', $event->title, route('calendar.index'), 'calendar')
        );
        $this->reset('eventTitle', 'eventEndsAt', 'eventLocation', 'eventDescription', 'eventCenterId', 'eventHalaqaId', 'eventAllDay', 'showEventForm');
        $this->eventStartsAt = now()->addHour()->format('Y-m-d\TH:i');
        $this->eventType = 'event';
        $this->eventPrivacy = 'internal';
        session()->flash('success', 'تمت إضافة الموعد وإشعار المستخدمين المعنيين.');
    }

    public function render(CalendarFeedService $feed): View
    {
        $anchor = Carbon::parse($this->anchorDate);
        [$from, $to] = match ($this->viewMode) {
            'day' => [$anchor->copy()->startOfDay(), $anchor->copy()->endOfDay()],
            'week' => [$anchor->copy()->startOfWeek(Carbon::SATURDAY), $anchor->copy()->endOfWeek(Carbon::FRIDAY)],
            default => [$anchor->copy()->startOfMonth()->startOfWeek(Carbon::SATURDAY), $anchor->copy()->endOfMonth()->endOfWeek(Carbon::FRIDAY)],
        };
        $events = $feed->events(auth()->user(), $from, $to, $this->typeFilter ?: null, $this->halaqaFilter ? (int) $this->halaqaFilter : null);
        $days = collect();
        for ($day = $from->copy()->startOfDay(); $day->lte($to); $day->addDay()) {
            $days->push($day->copy());
        }

        return view('livewire.calendar-board', [
            'events' => $events, 'days' => $days, 'from' => $from, 'to' => $to,
            'eventTypes' => $this->eventTypes(),
            'halaqas' => Halaqa::query()->where('active', true)->orderBy('name')->get(['id', 'name']),
            'centers' => Center::query()->where('active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    private function eventTypes(): array
    {
        return ['event' => 'فعالية', 'exam' => 'اختبار', 'meeting' => 'اجتماع', 'activity' => 'نشاط', 'revision' => 'مراجعة', 'course' => 'دورة', 'halaqa' => 'حلقة'];
    }
}
