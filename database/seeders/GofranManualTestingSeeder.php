<?php

namespace Database\Seeders;

use App\Actions\Guardians\CreateGuardianAction;
use App\Actions\Students\CreateStudentAction;
use App\Models\Branch;
use App\Models\Center;
use App\Models\Halaqa;
use App\Models\HalaqaSchedule;
use App\Models\StaffProfile;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Services\OrganizationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Spatie\Permission\PermissionRegistrar;

class GofranManualTestingSeeder extends Seeder
{
    private const PASSWORD = '123456789';

    public function run(
        OrganizationService $organization,
        CreateStudentAction $createStudent,
        CreateGuardianAction $createGuardian,
    ): void {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('The Gofran manual-testing reset may only run in local or testing environments.');
        }

        $this->cleanOperationalData();
        $this->call([
            RolesAndPermissionsSeeder::class,
            QuranReferenceSeeder::class,
            AcademicSettingsSeeder::class,
        ]);

        $center = Center::query()->create([
            'name' => 'مركز الغفران لتحفيظ القرآن الكريم',
            'code' => 'CTR-001',
            'phone' => '0599000000',
            'email' => 'info@gofran.com',
            'address' => 'المركز الرئيس',
            'active' => true,
        ]);
        $branch = Branch::query()->create([
            'center_id' => $center->id,
            'name' => 'السجل الداخلي للمركز',
            'code' => 'SYSTEM',
            'active' => true,
        ]);

        $manager = $this->createUser('مدير مركز الغفران', 'admin1@gofran.com');
        $manager->syncRoles(['super-admin', 'center-manager', 'teacher']);
        $teacherUser = $this->createUser('محفّظ حلقة النور', 'teacher1@gofran.com');
        $teacherUser->syncRoles(['teacher']);

        StaffProfile::query()->create([
            'user_id' => $manager->id,
            'center_id' => $center->id,
            'branch_id' => $branch->id,
            'employee_number' => 'STF-0001',
            'job_title' => 'مدير مركز الغفران',
            'hired_at' => today()->subYear(),
            'active' => true,
        ]);
        $managerTeacher = TeacherProfile::query()->create([
            'user_id' => $manager->id,
            'center_id' => $center->id,
            'branch_id' => $branch->id,
            'employee_number' => 'TCH-0001',
            'specialization' => 'حفظ وتجويد القرآن الكريم',
            'hired_at' => today()->subYear(),
            'active' => true,
        ]);
        $teacher = TeacherProfile::query()->create([
            'user_id' => $teacherUser->id,
            'center_id' => $center->id,
            'branch_id' => $branch->id,
            'employee_number' => 'TCH-0002',
            'specialization' => 'حفظ وتجويد القرآن الكريم',
            'hired_at' => today()->subMonths(8),
            'active' => true,
        ]);

        $managerHalaqa = $this->createHalaqa($center, $branch, $managerTeacher, 'حلقة الإتقان', 'HLQ-001');
        $teacherHalaqa = $this->createHalaqa($center, $branch, $teacher, 'حلقة النور', 'HLQ-002');

        $organization->assignTeacher([
            'halaqa_id' => $managerHalaqa->id,
            'teacher_profile_id' => $managerTeacher->id,
            'role' => 'primary',
            'starts_at' => today()->subMonth()->toDateString(),
            'assigned_by' => $manager->id,
        ]);
        $organization->assignTeacher([
            'halaqa_id' => $teacherHalaqa->id,
            'teacher_profile_id' => $teacher->id,
            'role' => 'primary',
            'starts_at' => today()->subMonth()->toDateString(),
            'assigned_by' => $manager->id,
        ]);

        foreach ([$managerHalaqa, $teacherHalaqa] as $halaqa) {
            foreach ([0, 2, 4] as $weekday) {
                HalaqaSchedule::query()->create([
                    'halaqa_id' => $halaqa->id,
                    'weekday' => $weekday,
                    'starts_at' => '16:00',
                    'ends_at' => '18:00',
                ]);
            }
        }

        Auth::setUser($manager);
        try {
            $students = [
                [$managerHalaqa, 'GF-STU-001', 'أحمد', 'محمود', 'عبدالله', 'الغفران'],
                [$managerHalaqa, 'GF-STU-002', 'يوسف', 'خالد', 'محمد', 'النجار'],
                [$teacherHalaqa, 'GF-STU-003', 'إبراهيم', 'حسن', 'علي', 'البرعي'],
                [$teacherHalaqa, 'GF-STU-004', 'معاذ', 'طارق', 'إسماعيل', 'العمري'],
            ];

            foreach ($students as $index => [$halaqa, $number, $first, $father, $grandfather, $family]) {
                $student = $createStudent->execute([
                    'student_number' => $number,
                    'first_name' => $first,
                    'father_name' => $father,
                    'grandfather_name' => $grandfather,
                    'family_name' => $family,
                    'identity_number' => 'GF-ID-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                    'birth_date' => today()->subYears(11 + $index)->toDateString(),
                    'contact_phone' => '05990000'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                    'registration_date' => today()->subMonth()->toDateString(),
                    'status' => 'active',
                    'halaqa_id' => $halaqa->id,
                    'notes' => 'ملف مهيأ للاختبار اليدوي دون سجلات تسميع سابقة.',
                ], $manager);

                $createGuardian->execute($student, [
                    'full_name' => 'ولي أمر '.$student->first_name.' '.$student->family_name,
                    'identity_number' => 'GF-GDN-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                    'phone' => '05690000'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                    'alternative_phone' => null,
                    'email' => null,
                    'relationship' => 'father',
                    'is_primary' => true,
                    'can_receive_notifications' => true,
                    'notes' => 'بيانات اختبار يدوي لمركز الغفران.',
                ], $manager);
            }
        } finally {
            Auth::forgetUser();
        }

        $this->call(GofranPublicContentSeeder::class);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->command?->info('Gofran manual-testing dataset is ready: one center, two Halaqas, two login accounts, and four students.');
    }

    private function createUser(string $name, string $email): User
    {
        return User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make(self::PASSWORD),
            'active' => true,
            'archived_at' => null,
            'email_verified_at' => now(),
            'locale' => 'ar',
        ]);
    }

    private function createHalaqa(Center $center, Branch $branch, TeacherProfile $teacher, string $name, string $code): Halaqa
    {
        return Halaqa::query()->create([
            'center_id' => $center->id,
            'branch_id' => $branch->id,
            'primary_teacher_id' => $teacher->id,
            'name' => $name,
            'code' => $code,
            'capacity' => 20,
            'active' => true,
            'start_date' => today()->subMonth(),
        ]);
    }

    private function cleanOperationalData(): void
    {
        // Public CMS content and Quran references intentionally remain. Their
        // previous author links are nulled before login accounts are reset.
        if (Schema::hasTable('cms_contents')) {
            DB::table('cms_contents')->update(['created_by' => null, 'updated_by' => null]);
        }
        if (Schema::hasTable('cms_media')) {
            DB::table('cms_media')->update(['uploaded_by' => null]);
        }

        $tables = [
            'student_alerts', 'achievements', 'certificates', 'course_enrollments', 'courses',
            'student_progress_snapshots', 'student_timeline_events', 'recitation_items', 'daily_records',
            'attendances', 'student_memorization_baselines', 'halaqa_enrollments', 'guardian_student',
            'guardians', 'students', 'calendar_events', 'report_exports', 'uploaded_reports',
            'halaqa_schedules', 'halaqa_teacher_assignments', 'halaqas', 'teacher_profiles',
            'staff_profiles', 'branches', 'centers', 'notifications', 'personal_access_tokens',
            'audit_logs', 'private_files', 'contact_messages', 'model_has_permissions', 'model_has_roles',
            'sessions', 'password_reset_tokens', 'users', 'number_sequences', 'jobs', 'job_batches',
            'failed_jobs',
        ];

        Schema::disableForeignKeyConstraints();
        try {
            foreach ($tables as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->truncate();
                }
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        Cache::flush();
    }
}
