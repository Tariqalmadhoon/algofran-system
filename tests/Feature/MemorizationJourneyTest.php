<?php

namespace Tests\Feature;

use App\Actions\Recitations\RecordStudentDailyRecordAction;
use App\Livewire\StudentProfile;
use App\Models\Branch;
use App\Models\Center;
use App\Models\Halaqa;
use App\Models\QuranAyah;
use App\Models\Student;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Services\MemorizationJourneyService;
use Database\Seeders\AcademicSettingsSeeder;
use Database\Seeders\QuranReferenceSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MemorizationJourneyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(QuranReferenceSeeder::class);
        $this->seed(AcademicSettingsSeeder::class);
    }

    public function test_reverse_journey_counts_completed_juz_at_quran_boundaries_and_displays_encouragement(): void
    {
        [$teacherUser, $teacher, $halaqa, $student] = $this->learningContext();

        $this->recordFullSurah($student, $halaqa, $teacher, $teacherUser, 58, today()->subDay()->toDateString());

        $snapshot = $student->progressSnapshots()->latest('as_of_date')->firstOrFail();
        $journey = app(MemorizationJourneyService::class)->calculate($student);
        $this->assertSame(3, $snapshot->completed_juz);
        $this->assertSame(3, $journey['completed_juz']);
        $this->assertSame(27, $journey['next_juz']);
        $this->assertSame('المجادلة', $journey['frontier_surah_name']);
        $this->assertSame(1, $journey['frontier_ayah_number']);
        $this->assertSame('ثلاثة أجزاء بإتقان', $journey['encouragement_title']);

        Livewire::actingAs($teacherUser)
            ->test(StudentProfile::class, ['student' => $student->fresh()])
            ->assertSee('المحفوظ حسب مسار الناس ← الفاتحة')
            ->assertSee('ثلاثة أجزاء بإتقان')
            ->assertSee('الهدف التالي: إكمال الجزء 27');

        $this->recordFullSurah($student, $halaqa, $teacher, $teacherUser, 46, today()->toDateString());

        $snapshot = $student->progressSnapshots()->latest('as_of_date')->firstOrFail();
        $journey = app(MemorizationJourneyService::class)->calculate($student);
        $this->assertSame(5, $snapshot->completed_juz);
        $this->assertSame(5, $journey['completed_juz']);
        $this->assertSame(25, $journey['next_juz']);
        $this->assertSame('الأحقاف', $journey['frontier_surah_name']);
        $this->assertSame('خمسة أجزاء مباركة', $journey['encouragement_title']);
        $this->assertDatabaseHas('achievements', [
            'student_id' => $student->id,
            'type' => 'juz_completion',
            'title' => 'إكمال 5 جزء',
        ]);
    }

    public function test_juz_is_not_completed_until_its_first_ayah_is_reached(): void
    {
        [$teacherUser, $teacher, $halaqa, $student] = $this->learningContext();
        $start = QuranAyah::query()->where('surah_id', 58)->where('ayah_number', 2)->firstOrFail();
        $end = QuranAyah::query()->where('surah_id', 58)->orderByDesc('ayah_number')->firstOrFail();

        $this->recordRange($student, $halaqa, $teacher, $teacherUser, $start->id, $end->id, today()->toDateString());

        $journey = app(MemorizationJourneyService::class)->calculate($student);
        $this->assertSame(2, $journey['completed_juz']);
        $this->assertSame(28, $journey['next_juz']);
        $this->assertLessThan(100, $journey['current_juz_progress']);
    }

    /** @return array{User, TeacherProfile, Halaqa, Student} */
    private function learningContext(): array
    {
        $center = Center::query()->create(['name' => 'مركز الغفران لتحفيظ القرآن الكريم', 'code' => 'GF']);
        $branch = Branch::query()->create(['center_id' => $center->id, 'name' => 'السجل الداخلي', 'code' => 'SYSTEM']);
        $teacherUser = User::factory()->create(['name' => 'المحفّظ أحمد']);
        $teacherUser->assignRole('teacher');
        $teacher = TeacherProfile::query()->create([
            'user_id' => $teacherUser->id,
            'center_id' => $center->id,
            'branch_id' => $branch->id,
            'employee_number' => 'T-JOURNEY',
            'active' => true,
        ]);
        $halaqa = Halaqa::query()->create([
            'center_id' => $center->id,
            'branch_id' => $branch->id,
            'primary_teacher_id' => $teacher->id,
            'name' => 'حلقة الإتقان',
            'code' => 'H-JOURNEY',
            'capacity' => 30,
            'active' => true,
        ]);
        $halaqa->teacherAssignments()->create([
            'teacher_profile_id' => $teacher->id,
            'role' => 'primary',
            'starts_at' => today()->subYear()->toDateString(),
            'assigned_by' => $teacherUser->id,
        ]);
        $student = Student::query()->create([
            'student_number' => 'ST-JOURNEY',
            'first_name' => 'طالب',
            'father_name' => 'رحلة',
            'grandfather_name' => 'الحفظ',
            'family_name' => 'الغفران',
            'full_name' => 'طالب رحلة الحفظ الغفران',
            'registration_date' => today()->subYear()->toDateString(),
            'status' => 'active',
            'current_halaqa_id' => $halaqa->id,
            'created_by' => $teacherUser->id,
            'updated_by' => $teacherUser->id,
        ]);
        $student->enrollments()->create([
            'halaqa_id' => $halaqa->id,
            'starts_at' => today()->subYear()->toDateString(),
            'reason' => 'التحاق',
            'created_by' => $teacherUser->id,
        ]);

        return [$teacherUser, $teacher, $halaqa, $student];
    }

    private function recordFullSurah(Student $student, Halaqa $halaqa, TeacherProfile $teacher, User $actor, int $surahId, string $date): void
    {
        $start = QuranAyah::query()->where('surah_id', $surahId)->orderBy('ayah_number')->firstOrFail();
        $end = QuranAyah::query()->where('surah_id', $surahId)->orderByDesc('ayah_number')->firstOrFail();
        $this->recordRange($student, $halaqa, $teacher, $actor, $start->id, $end->id, $date);
    }

    private function recordRange(Student $student, Halaqa $halaqa, TeacherProfile $teacher, User $actor, int $startAyahId, int $endAyahId, string $date): void
    {
        app(RecordStudentDailyRecordAction::class)->execute($student, $halaqa, $teacher, [
            'record_date' => $date,
            'attendance_status' => 'present',
            'general_evaluation' => 'excellent',
            'attendance_notes' => null,
            'notes' => null,
            'items' => [[
                'enabled' => true,
                'type' => 'new_memorization',
                'start_ayah_id' => $startAyahId,
                'end_ayah_id' => $endAyahId,
                'evaluation' => 'excellent',
                'notes' => null,
                'memorization_errors' => 0,
                'tajweed_errors' => 0,
                'hesitation_count' => 0,
                'teacher_prompt_count' => 0,
            ]],
        ], $actor);
    }
}
