<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CalendarEventResource;
use App\Services\CalendarFeedService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CalendarController extends Controller
{
    public function index(Request $request, CalendarFeedService $calendar): AnonymousResourceCollection
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'type' => ['nullable', Rule::in(['event', 'exam', 'meeting', 'activity', 'revision', 'course', 'halaqa'])],
            'halaqa_id' => ['nullable', 'integer'],
        ]);
        $from = $request->filled('from') ? Carbon::parse($request->string('from'))->startOfDay() : today()->startOfMonth();
        $to = $request->filled('to') ? Carbon::parse($request->string('to'))->endOfDay() : today()->endOfMonth();
        if ($from->diffInDays($to) > 366) {
            throw ValidationException::withMessages(['to' => 'يجب ألا يتجاوز النطاق الزمني سنة واحدة.']);
        }

        return CalendarEventResource::collection($calendar->events($request->user(), $from, $to, $request->string('type')->toString() ?: null, $request->integer('halaqa_id') ?: null));
    }
}
