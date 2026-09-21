<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Contracts\View\View;

class StudentController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Student::class);

        return view('students.index');
    }

    public function show(Student $student): View
    {
        $this->authorize('view', $student);

        return view('students.show', compact('student'));
    }

    public function trash(): View
    {
        $this->authorize('viewTrash', Student::class);

        return view('students.trash');
    }
}
