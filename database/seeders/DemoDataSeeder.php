<?php

namespace Database\Seeders;

use App\Actions\Recitations\RecordStudentDailyRecordAction;
use App\Actions\Students\CreateStudentAction;
use App\Actions\Students\RecordInitialBaselineAction;
use App\Models\Branch;
use App\Models\CalendarEvent;
use App\Models\Center;
use App\Models\Certificate;
use App\Models\CmsContent;
use App\Models\CmsMedia;
use App\Models\Course;
use App\Models\Halaqa;
use App\Models\HalaqaSchedule;
use App\Models\HalaqaTeacherAssignment;
use App\Models\Student;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Notifications\SystemNotification;
use App\Services\AcademicRecordsService;
use App\Services\StudentAchievementEngine;
use App\Services\StudentAlertEngine;
use App\Services\StudentProgressService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DemoDataSeeder extends Seeder
{
    public function run(
        CreateStudentAction $createStudent,
        RecordInitialBaselineAction $recordBaseline,
        RecordStudentDailyRecordAction $recordDaily,
        AcademicRecordsService $academic,
        StudentProgressService $progress,
        StudentAlertEngine $alerts,
        StudentAchievementEngine $achievements,
    ): void {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Demo data may only be seeded in local or testing environments.');
        }

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@alquran.local'],
            ['name' => 'مدير النظام التجريبي', 'password' => Hash::make('DemoAdmin123!'), 'active' => true, 'email_verified_at' => now(), 'locale' => 'ar'],
        );
        $admin->syncRoles(['super-admin']);
        $teacherUser = User::query()->updateOrCreate(
            ['email' => 'teacher@alquran.local'],
            ['name' => 'المحفظ أحمد الخطيب', 'password' => Hash::make('DemoTeacher123!'), 'active' => true, 'email_verified_at' => now(), 'locale' => 'ar'],
        );
        $teacherUser->syncRoles(['teacher']);

        $center = Center::query()->updateOrCreate(['code' => 'MAIN'], ['name' => 'مركز الغفران لتحفيظ القرآن الكريم', 'phone' => '0599000000', 'address' => 'الفرع الرئيس', 'active' => true]);
        $branch = Branch::query()->updateOrCreate(['center_id' => $center->id, 'code' => 'B1'], ['name' => 'الفرع الرئيس', 'phone' => '0599000001', 'active' => true]);
        $teacher = TeacherProfile::query()->updateOrCreate(
            ['user_id' => $teacherUser->id],
            ['center_id' => $center->id, 'branch_id' => $branch->id, 'employee_number' => 'T-DEMO-001', 'specialization' => 'حفظ وتجويد', 'hired_at' => today()->subYears(2), 'active' => true],
        );
        $halaqa = Halaqa::query()->updateOrCreate(
            ['branch_id' => $branch->id, 'code' => 'H-DEMO-1'],
            ['center_id' => $center->id, 'primary_teacher_id' => $teacher->id, 'name' => 'حلقة الإتقان', 'program' => 'برنامج الحفظ المكثف', 'capacity' => 20, 'room' => 'قاعة 1', 'active' => true, 'start_date' => today()->subYear()],
        );
        HalaqaTeacherAssignment::query()->firstOrCreate(
            ['halaqa_id' => $halaqa->id, 'teacher_profile_id' => $teacher->id, 'starts_at' => today()->subYear()->toDateString()],
            ['role' => 'primary', 'assigned_by' => $admin->id],
        );
        foreach ([0, 2, 4] as $weekday) {
            HalaqaSchedule::query()->firstOrCreate(
                ['halaqa_id' => $halaqa->id, 'weekday' => $weekday, 'starts_at' => '16:00'],
                ['ends_at' => '18:00', 'room' => $halaqa->room],
            );
        }

        $demoEvents = [
            ['type' => 'meeting', 'title' => 'الاجتماع الأكاديمي الأسبوعي', 'starts_at' => now()->startOfWeek()->addDays(2)->setTime(11, 0), 'ends_at' => now()->startOfWeek()->addDays(2)->setTime(12, 0), 'location' => 'قاعة الاجتماعات'],
            ['type' => 'exam', 'title' => 'اختبار متابعة الحفظ', 'starts_at' => now()->addDays(3)->setTime(16, 30), 'ends_at' => now()->addDays(3)->setTime(18, 0), 'location' => $halaqa->room],
            ['type' => 'activity', 'title' => 'لقاء تحفيزي للطلاب', 'starts_at' => now()->addDays(6)->setTime(17, 0), 'ends_at' => now()->addDays(6)->setTime(18, 30), 'location' => 'القاعة الرئيسية'],
        ];
        foreach ($demoEvents as $eventData) {
            CalendarEvent::query()->updateOrCreate(
                ['title' => $eventData['title']],
                $eventData + ['center_id' => $center->id, 'branch_id' => $branch->id, 'halaqa_id' => $halaqa->id, 'privacy' => 'internal', 'created_by' => $admin->id],
            );
        }
        if ($admin->notifications()->count() === 0) {
            $admin->notify(new SystemNotification('مرحبًا بك في مركز الإشعارات', 'سيظهر هنا اكتمال التقارير والمواعيد والتحديثات المهمة.', route('notifications.index'), 'success'));
        }

        $names = [
            ['محمود', 'خالد', 'عبدالله', 'النجار'],
            ['يوسف', 'أحمد', 'محمد', 'الخطيب'],
            ['إبراهيم', 'حسن', 'علي', 'القدرة'],
            ['عبدالرحمن', 'محمود', 'سليم', 'العمري'],
            ['معاذ', 'طارق', 'إسماعيل', 'البرعي'],
            ['أنس', 'وليد', 'مصطفى', 'الحسن'],
            ['حمزة', 'سامي', 'إبراهيم', 'ناصر'],
            ['زيد', 'عمر', 'خليل', 'شاهين'],
        ];

        foreach ($names as $index => [$first, $father, $grandfather, $family]) {
            $number = 'DEMO-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT);
            $student = Student::query()->firstWhere('student_number', $number);
            if (! $student) {
                $student = $createStudent->execute([
                    'student_number' => $number,
                    'first_name' => $first,
                    'father_name' => $father,
                    'grandfather_name' => $grandfather,
                    'family_name' => $family,
                    'identity_number' => null,
                    'birth_date' => today()->subYears(11 + ($index % 4))->toDateString(),
                    'contact_phone' => '0599'.str_pad((string) (100000 + $index), 6, '0', STR_PAD_LEFT),
                    'registration_date' => today()->subMonths(3)->toDateString(),
                    'status' => 'active',
                    'halaqa_id' => $halaqa->id,
                    'notes' => 'بيانات تجريبية محلية لعرض النظام.',
                ], $admin);
            }

            if (! $student->baselines()->exists()) {
                $recordBaseline->execute($student, 1, 7, today()->subMonths(3)->toDateString(), $admin, 'يحفظ سورة الفاتحة قبل الالتحاق.');
            }

            for ($daysAgo = 6; $daysAgo >= 0; $daysAgo--) {
                $date = today()->subDays($daysAgo)->toDateString();
                if ($student->dailyRecords()->whereDate('record_date', $date)->exists()) {
                    continue;
                }

                $isAtRiskStudent = $index === 0;
                $isAbsent = $isAtRiskStudent && in_array($daysAgo, [6, 4, 2], true);
                $isPoor = $isAtRiskStudent && in_array($daysAgo, [1, 0], true);
                $type = $daysAgo % 2 === 0 ? 'new_memorization' : 'recent_revision';
                $start = $type === 'new_memorization' ? 8 + (6 - $daysAgo) * 2 : 1;
                $end = $type === 'new_memorization' ? min(22, $start + 2) : 7;
                $evaluation = $isPoor ? 'poor' : match (($index + $daysAgo) % 3) {
                    0 => 'excellent', 1 => 'very_good', default => 'good',
                };
                $items = $isAbsent ? [] : [[
                    'enabled' => true,
                    'type' => $type,
                    'start_ayah_id' => $start,
                    'end_ayah_id' => $end,
                    'evaluation' => $evaluation,
                    'memorization_errors' => $evaluation === 'poor' ? 6 : ($index + $daysAgo) % 3,
                    'tajweed_errors' => $evaluation === 'poor' ? 4 : ($index + $daysAgo) % 2,
                    'hesitation_count' => $evaluation === 'poor' ? 5 : 1,
                    'teacher_prompt_count' => $evaluation === 'poor' ? 3 : 0,
                    'notes' => $isPoor ? 'يحتاج إلى مراجعة مكثفة.' : null,
                ]];
                $recordDaily->execute($student, $halaqa, $teacher, [
                    'record_date' => $date,
                    'attendance_status' => $isAbsent ? 'absent' : ($daysAgo === 3 ? 'late' : 'present'),
                    'attendance_notes' => $isAbsent ? 'غياب تجريبي للمتابعة.' : null,
                    'general_evaluation' => $isAbsent ? '' : $evaluation,
                    'notes' => null,
                    'items' => $items,
                ], $teacherUser);
            }

            $snapshot = $progress->snapshot($student);
            $alerts->evaluate($student->refresh(), $snapshot);
            $achievements->evaluate($student, $snapshot);
        }

        $course = Course::query()->firstWhere('name', 'دورة أحكام التجويد التطبيقية');
        if (! $course) {
            $course = $academic->createCourse([
                'center_id' => $center->id,
                'branch_id' => $branch->id,
                'instructor_id' => $teacher->id,
                'name' => 'دورة أحكام التجويد التطبيقية',
                'description' => 'دورة عملية من عشرين ساعة.',
                'starts_at' => today()->subMonths(2)->toDateString(),
                'ends_at' => today()->subMonth()->toDateString(),
                'hours' => 20,
                'status' => 'completed',
            ], $admin);
        }
        foreach (Student::query()->where('student_number', 'like', 'DEMO-%')->take(4)->get() as $index => $student) {
            if (! $course->enrollments()->where('student_id', $student->id)->exists()) {
                $academic->enrollStudent($course, $student, [
                    'enrolled_at' => $course->starts_at->toDateString(),
                    'status' => 'completed',
                    'result' => 88 + $index,
                    'grade' => 'ممتاز',
                    'completed_at' => $course->ends_at->toDateString(),
                    'notes' => null,
                ], $admin);
            }
        }
        $firstStudent = Student::query()->where('student_number', 'DEMO-001')->firstOrFail();
        if (! Certificate::query()->where('certificate_number', 'DEMO-CERT-001')->exists()) {
            $academic->issueCertificate($firstStudent, [
                'course_id' => $course->id,
                'name' => 'شهادة إتمام دورة التجويد',
                'issuer' => $center->name,
                'certificate_number' => 'DEMO-CERT-001',
                'issued_at' => $course->ends_at->toDateString(),
                'expires_at' => null,
                'grade' => 'ممتاز',
                'notes' => 'شهادة تجريبية دون ملف مرفق.',
            ], $admin);
        }
        if (! $firstStudent->achievements()->where('title', 'المركز الأول في مسابقة التلاوة')->exists()) {
            $academic->recordAchievement($firstStudent, [
                'type' => 'competition',
                'title' => 'المركز الأول في مسابقة التلاوة',
                'description' => 'إنجاز تجريبي لإظهار السجل الأكاديمي.',
                'achieved_at' => today()->subWeeks(2)->toDateString(),
                'issuer' => $center->name,
                'metadata' => ['demo' => true],
            ], $admin);
        }

        foreach ([
            ['name' => 'برنامج الحفظ المتدرج', 'description' => 'مسار متدرج لحفظ القرآن الكريم مع مراجعة دورية وخطة متابعة فردية.', 'starts_at' => today()->subWeek(), 'ends_at' => today()->addMonths(6), 'hours' => 120, 'status' => 'active'],
            ['name' => 'دورة التلاوة والتجويد', 'description' => 'دورة تأسيسية عملية في مخارج الحروف وأحكام التلاوة.', 'starts_at' => today()->addWeeks(2), 'ends_at' => today()->addMonths(2), 'hours' => 30, 'status' => 'planned'],
        ] as $program) {
            Course::query()->updateOrCreate(['center_id' => $center->id, 'name' => $program['name']], $program + ['branch_id' => $branch->id, 'instructor_id' => $teacher->id, 'created_by' => $admin->id]);
        }

        $media = collect([
            ['path' => 'cms/demo/quran-learning.svg', 'title' => 'رحلة تعلم القرآن', 'alt_text' => 'تصميم رمزي لمصحف مفتوح', 'caption' => 'بيئة تعليمية ملهمة لطلاب القرآن'],
            ['path' => 'cms/demo/student-circle.svg', 'title' => 'حلقة الإتقان', 'alt_text' => 'تصميم رمزي لحلقة قرآنية', 'caption' => 'متابعة يومية ورعاية تربوية متكاملة'],
            ['path' => 'cms/demo/achievement-star.svg', 'title' => 'محطات الإنجاز', 'alt_text' => 'تصميم رمزي للإنجاز القرآني', 'caption' => 'نحتفي بكل خطوة في رحلة الحفظ'],
        ])->map(fn (array $item) => CmsMedia::query()->updateOrCreate(['path' => $item['path']], $item + ['disk' => 'public', 'original_name' => basename($item['path']), 'mime_type' => 'image/svg+xml', 'size' => 0, 'kind' => 'image', 'is_gallery' => true, 'uploaded_by' => $admin->id]));

        $content = [
            ['type' => 'page', 'slug' => 'about', 'title' => 'عن مركز الغفران لتحفيظ القرآن الكريم', 'excerpt' => 'مؤسسة تعليمية تربوية تجمع بين الإتقان والرعاية والنمو.', 'body' => "نعمل على تعليم القرآن الكريم حفظًا وتلاوةً وتجويدًا، ضمن منهجية واضحة تراعي الفروق الفردية وتربط الطالب بمعلمه وأسرته.\n\nنؤمن بأن الجودة تبدأ من المتابعة الدقيقة، وتكتمل بالقدوة والرعاية التربوية.", 'featured_media_id' => $media[0]->id],
            ['type' => 'page', 'slug' => 'programs', 'title' => 'برامجنا القرآنية', 'excerpt' => 'مسارات متنوعة في الحفظ والمراجعة والتجويد تناسب المراحل المختلفة.', 'body' => 'صُممت البرامج لتجمع بين الخطة الواضحة والتقييم المستمر والرعاية الفردية.', 'featured_media_id' => $media[1]->id],
            ['type' => 'announcement', 'slug' => 'registration-open', 'title' => 'فتح باب التسجيل في حلقات الفصل الجديد', 'excerpt' => 'التسجيل متاح الآن للمستويات التأسيسية والمتقدمة.', 'body' => 'تواصل معنا لمعرفة الحلقة والبرنامج الأنسب.', 'featured' => true],
            ['type' => 'news', 'slug' => 'new-quran-semester', 'title' => 'انطلاق الفصل القرآني الجديد', 'excerpt' => 'استقبل المركز طلابه بخطط حفظ ومراجعة محدثة.', 'body' => 'بدأت الحلقات القرآنية فصلها الجديد وسط أجواء إيمانية وتربوية، مع اعتماد خطط فردية تراعي مستوى كل طالب.', 'featured_media_id' => $media[0]->id, 'featured' => true],
            ['type' => 'news', 'slug' => 'teacher-development-meeting', 'title' => 'لقاء تطويري للمحفّظين', 'excerpt' => 'لقاء لمناقشة أدوات المتابعة ورفع جودة التعليم.', 'body' => 'ناقش فريق التعليم أفضل الممارسات في التقييم والتحفيز والتواصل مع الأسرة.', 'featured_media_id' => $media[1]->id],
            ['type' => 'news', 'slug' => 'tajweed-course-completed', 'title' => 'ختام دورة أحكام التجويد', 'excerpt' => 'أنهى طلاب الدورة ساعاتها التطبيقية بنجاح.', 'body' => 'تضمن البرنامج تدريبًا عمليًا وتقييمات مرحلية وشهادات إتمام للمجتازين.', 'featured_media_id' => $media[2]->id],
            ['type' => 'activity', 'slug' => 'quran-motivation-day', 'title' => 'اليوم التحفيزي لطلاب الحلقات', 'excerpt' => 'أنشطة تربوية ومسابقات تعزز الصلة بالقرآن.', 'body' => 'اجتمع الطلاب في برنامج تفاعلي تضمن مسابقات في التلاوة والمراجعة وفقرات تربوية.', 'featured_media_id' => $media[1]->id, 'featured' => true],
            ['type' => 'activity', 'slug' => 'parents-meeting', 'title' => 'اللقاء التربوي مع أولياء الأمور', 'excerpt' => 'شراكة متكاملة لدعم رحلة الطالب.', 'body' => 'عرض الفريق آليات المتابعة المنزلية وناقش تقارير التقدم مع أولياء الأمور.', 'featured_media_id' => $media[0]->id],
            ['type' => 'achievement', 'slug' => 'first-quran-juz', 'title' => 'طلاب يتمون الجزء الأول', 'excerpt' => 'محطة مباركة في رحلة الحفظ المتقن.', 'body' => 'نبارك لطلابنا إتمام الجزء الأول بعد خطة حفظ ومراجعة منتظمة.', 'featured_media_id' => $media[2]->id],
            ['type' => 'achievement', 'slug' => 'recitation-competition-win', 'title' => 'تميّز في مسابقة التلاوة', 'excerpt' => 'حقق أحد طلاب المركز مركزًا متقدمًا.', 'body' => 'ثمرة الاجتهاد والمتابعة والتدريب المستمر على جودة التلاوة.', 'featured_media_id' => $media[2]->id, 'featured' => true],
        ];
        foreach ($content as $index => $item) {
            CmsContent::query()->updateOrCreate(['slug' => $item['slug']], $item + ['status' => 'published', 'published_at' => now()->subDays(max(1, 12 - $index)), 'meta_title' => $item['title'], 'meta_description' => $item['excerpt'], 'sort_order' => $index, 'created_by' => $admin->id, 'updated_by' => $admin->id]);
        }
    }
}
