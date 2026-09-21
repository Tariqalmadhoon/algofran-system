<?php

namespace App\Livewire;

use App\Actions\Students\PermanentlyDeleteStudentAction;
use App\Actions\Students\RestoreStudentAction;
use App\Models\Student;
use App\Services\StudentVisibilityService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class StudentTrash extends Component
{
    use WithPagination;

    public string $search = '';

    public function mount(): void
    {
        Gate::authorize('viewTrash', Student::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function restoreStudent(int $studentId, RestoreStudentAction $restoreStudent): void
    {
        $student = Student::onlyTrashed()->findOrFail($studentId);
        Gate::authorize('restore', $student);

        $restoreStudent->execute($student, auth()->user());
        $this->resetPage();
        session()->flash('success', 'استُعيد ملف الطالب إلى قائمة الطلاب. يلزم إلحاقه بحلقة من ملفه عند الحاجة.');
    }

    public function permanentlyDeleteStudent(int $studentId, PermanentlyDeleteStudentAction $deleteStudent): void
    {
        $student = Student::onlyTrashed()->findOrFail($studentId);
        Gate::authorize('forceDelete', $student);

        $deleteStudent->execute($student, auth()->user());
        $this->resetPage();
        session()->flash('success', 'حُذف ملف الطالب نهائيًا مع البيانات المرتبطة به.');
    }

    public function render(StudentVisibilityService $visibility): View
    {
        $students = $visibility->trashedQueryFor(auth()->user())
            ->with(['photo:id'])
            ->when($this->search, function ($query) {
                $term = '%'.trim($this->search).'%';
                $query->where(function ($search) use ($term) {
                    $search->where('full_name', 'like', $term)
                        ->orWhere('student_number', 'like', $term)
                        ->orWhere('identity_number', 'like', $term);
                });
            })
            ->latest('deleted_at')
            ->paginate(15);

        return view('livewire.student-trash', ['students' => $students]);
    }
}
