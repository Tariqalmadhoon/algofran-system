<?php

use App\Models\Student;
use App\Services\StudentAchievementEngine;
use App\Services\StudentAlertEngine;
use App\Services\StudentProgressService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('academic:refresh {--student=}', function (
    StudentProgressService $progress,
    StudentAlertEngine $alerts,
    StudentAchievementEngine $achievements,
) {
    $query = Student::query()->where('status', 'active');
    if ($studentId = $this->option('student')) {
        $query->whereKey($studentId);
    }

    $count = 0;
    $query->chunkById(100, function ($students) use ($progress, $alerts, $achievements, &$count) {
        foreach ($students as $student) {
            $snapshot = $progress->snapshot($student);
            $alerts->evaluate($student->refresh(), $snapshot);
            $achievements->evaluate($student, $snapshot);
            $count++;
        }
    });

    $this->info("Refreshed academic analytics for {$count} students.");
})->purpose('Rebuild student progress snapshots, smart alerts, and Quran milestones');

Schedule::command('academic:refresh')->dailyAt('01:15')->onOneServer()->withoutOverlapping();
Schedule::command('sanctum:prune-expired --hours=24')->dailyAt('02:00')->onOneServer()->withoutOverlapping();
Schedule::command('queue:prune-failed --hours=168')->dailyAt('02:15')->onOneServer()->withoutOverlapping();
Schedule::command('queue:prune-batches --hours=168 --unfinished=168 --cancelled=168')->dailyAt('02:30')->onOneServer()->withoutOverlapping();
Schedule::command('queue:monitor database:default --max=100')->everyFiveMinutes()->onOneServer()->withoutOverlapping();
