<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class TeacherDailyController extends Controller
{
    public function __invoke(): View
    {
        Gate::authorize('recitations.create');

        abort_unless(auth()->user()->teacherProfile?->active, 403, 'لا يوجد ملف محفظ فعال لهذا الحساب.');

        return view('teacher.daily');
    }
}
