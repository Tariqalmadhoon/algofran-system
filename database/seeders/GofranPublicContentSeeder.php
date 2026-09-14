<?php

namespace Database\Seeders;

use App\Models\CmsContent;
use App\Models\CmsMedia;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class GofranPublicContentSeeder extends Seeder
{
    public function run(): void
    {
        $author = User::query()->where('email', 'admin1@gofran.com')->first();

        $mediaItems = [
            'quran-semester' => [
                'path' => 'cms/gofran/quran-semester.svg',
                'title' => 'رحلة الحفظ والإتقان',
                'alt_text' => 'مصحف مفتوح تحيط به زخارف إسلامية خضراء وذهبية',
                'caption' => 'بيئة تعليمية ملهمة تجمع بين الحفظ والإتقان والرعاية.',
            ],
            'teacher-meeting' => [
                'path' => 'cms/gofran/teacher-meeting.svg',
                'title' => 'تطوير أداء المحفّظين',
                'alt_text' => 'مجلس تعليمي لمناقشة خطط متابعة طلاب القرآن',
                'caption' => 'لقاءات تطويرية مستمرة لرفع جودة المتابعة والتعليم.',
            ],
            'student-activity' => [
                'path' => 'cms/gofran/student-activity.svg',
                'title' => 'أنشطة طلاب الحلقات',
                'alt_text' => 'طلاب في نشاط قرآني تربوي تفاعلي',
                'caption' => 'أنشطة تبني الدافعية وتعزز صلة الطالب بالقرآن الكريم.',
            ],
            'quran-competition' => [
                'path' => 'cms/gofran/quran-competition.svg',
                'title' => 'مسابقة التلاوة والإتقان',
                'alt_text' => 'وسام ذهبي ومصحف يرمزان إلى التميز في التلاوة',
                'caption' => 'نحتفي بالاجتهاد ونحوّل كل إنجاز إلى دافع للمواصلة.',
            ],
        ];

        $this->publishSeedMediaFiles($mediaItems);

        $media = collect($mediaItems)->mapWithKeys(function (array $item, string $key) use ($author): array {
            $record = CmsMedia::withTrashed()->firstOrNew(['disk' => 'public', 'path' => $item['path']]);
            $record->fill($item + [
                'original_name' => basename($item['path']),
                'mime_type' => 'image/svg+xml',
                'size' => Storage::disk('public')->exists($item['path']) ? Storage::disk('public')->size($item['path']) : 0,
                'kind' => 'image',
                'is_gallery' => true,
                'sort_order' => 0,
                'uploaded_by' => $author?->id,
            ]);
            $record->deleted_at = null;
            $record->save();

            return [$key => $record];
        });

        $items = [
            [
                'type' => 'news', 'slug' => 'gofran-new-quran-semester',
                'title' => 'انطلاق الفصل القرآني الجديد في مركز الغفران',
                'excerpt' => 'استقبل المركز طلابه بخطط حفظ ومراجعة فردية، ومتابعة يومية تربط الإنجاز بالتشجيع المستمر.',
                'body' => "انطلقت حلقات مركز الغفران لتحفيظ القرآن الكريم ضمن خطة تعليمية متجددة تراعي مستوى كل طالب وطاقته اليومية.\n\nتتضمن الخطة مسارًا واضحًا للحفظ الجديد، ومراجعة قريبة وبعيدة، وتقييمًا مستمرًا يساعد المحفّظ والأسرة على متابعة التقدم بصورة دقيقة.\n\nونسأل الله أن يجعل هذا الفصل بداية مباركة، وأن يفتح على طلابنا بالثبات والإتقان والعمل بكتاب الله.",
                'featured_media_id' => $media['quran-semester']->id, 'featured' => true, 'published_at' => now()->subDays(2),
            ],
            [
                'type' => 'news', 'slug' => 'gofran-teacher-development-meeting',
                'title' => 'لقاء تطويري للمحفّظين حول جودة المتابعة اليومية',
                'excerpt' => 'ناقش فريق التعليم أدوات التقييم العملي وآليات بناء خطة مناسبة لكل طالب داخل الحلقة.',
                'body' => "عقد مركز الغفران لقاءً تطويريًا للمحفّظين، ركّز على توحيد معايير تقييم الحفظ والمراجعة، وتحويل السجل اليومي إلى أداة عملية لاتخاذ القرار.\n\nتخلل اللقاء عرض حالات تعليمية ومناقشة أفضل طرق التحفيز، مع التأكيد على التواصل الإيجابي مع الأسرة ومراعاة الفروق الفردية بين الطلاب.",
                'featured_media_id' => $media['teacher-meeting']->id, 'featured' => false, 'published_at' => now()->subDays(6),
            ],
            [
                'type' => 'news', 'slug' => 'gofran-recitation-progress',
                'title' => 'تقدم مميز لطلاب الحلقات في مسار التلاوة والإتقان',
                'excerpt' => 'نتائج المتابعة الأخيرة تعكس تطورًا واضحًا في جودة التلاوة وثبات المحفوظ لدى الطلاب.',
                'body' => "أظهرت المتابعة الدورية تقدمًا مميزًا لدى مجموعة من طلاب الحلقات في ضبط التلاوة وثبات المراجعة.\n\nويأتي هذا التقدم ثمرة للمواظبة اليومية، وتكامل دور المحفّظ مع الأسرة، واعتماد أهداف صغيرة قابلة للقياس تبني إنجازًا متراكمًا ومستدامًا.",
                'featured_media_id' => $media['quran-competition']->id, 'featured' => false, 'published_at' => now()->subDays(10),
            ],
            [
                'type' => 'activity', 'slug' => 'gofran-quran-motivation-day',
                'title' => 'اليوم التحفيزي لطلاب حلقات القرآن',
                'excerpt' => 'مسابقات تربوية وفقرات تفاعلية صنعت يومًا مليئًا بالحماس وروح الفريق.',
                'body' => "نظم مركز الغفران يومًا تحفيزيًا لطلاب الحلقات، جمع بين مسابقات التلاوة والمراجعة، وفقرات تربوية خفيفة تعزز روح التعاون والانتماء للحلقة.\n\nشارك الطلاب بحماس في التحديات الفردية والجماعية، واختتم النشاط بتكريم المشاركين وتوجيه رسائل تشجيعية تساعدهم على مواصلة رحلتهم مع القرآن.",
                'featured_media_id' => $media['student-activity']->id, 'featured' => true, 'published_at' => now()->subDays(3),
            ],
            [
                'type' => 'activity', 'slug' => 'gofran-recitation-competition',
                'title' => 'مسابقة التلاوة والإتقان بين طلاب المركز',
                'excerpt' => 'محطة تنافسية هادفة أظهرت جمال الأداء وأثمرت دافعية جديدة للمراجعة.',
                'body' => "أقيمت مسابقة التلاوة والإتقان في أجواء قرآنية مميزة، وتنافس الطلاب في جودة الحفظ وسلامة التلاوة وحسن الأداء.\n\nراعى التقييم المراحل العمرية ومستوى كل مجموعة، وحصل جميع المشاركين على تغذية راجعة واضحة تساعدهم في تطوير أدائهم خلال الأسابيع القادمة.",
                'featured_media_id' => $media['quran-competition']->id, 'featured' => false, 'published_at' => now()->subDays(8),
            ],
            [
                'type' => 'activity', 'slug' => 'gofran-family-partnership-meeting',
                'title' => 'لقاء الشراكة التربوية مع أولياء الأمور',
                'excerpt' => 'لقاء عملي لتقريب الأسرة من خطة الطالب وتوضيح أساليب المتابعة المنزلية الفعالة.',
                'body' => "التقى فريق المركز بأولياء الأمور ضمن لقاء الشراكة التربوية، وعرض آلية قراءة سجل الطالب والاستفادة من ملاحظات المحفّظ اليومية.\n\nكما ناقش اللقاء طرق تهيئة وقت هادئ للمراجعة في المنزل، وأساليب التشجيع المتوازن التي تعزز الاستمرارية دون ضغط على الطالب.",
                'featured_media_id' => $media['teacher-meeting']->id, 'featured' => false, 'published_at' => now()->subDays(12),
            ],
        ];

        foreach ($items as $index => $item) {
            $record = CmsContent::withTrashed()->firstOrNew(['slug' => $item['slug']]);
            $record->fill($item + [
                'status' => 'published',
                'meta_title' => $item['title'],
                'meta_description' => $item['excerpt'],
                'sort_order' => $index,
                'created_by' => $record->created_by ?: $author?->id,
                'updated_by' => $author?->id,
            ]);
            $record->deleted_at = null;
            $record->save();
        }

        $this->command?->info('Gofran public news, activities, and media are ready.');
    }

    /**
     * Keep CMS seed records and their public media inseparable. The local
     * storage directory is intentionally ignored by Git, so a fresh checkout
     * must obtain these curated SVG assets from versioned source files.
     *
     * @param array<string, array{path:string}> $mediaItems
     */
    private function publishSeedMediaFiles(array $mediaItems): void
    {
        $disk = Storage::disk('public');

        foreach ($mediaItems as $item) {
            if ($disk->exists($item['path'])) {
                continue;
            }

            $asset = database_path('seeders/assets/gofran/'.basename($item['path']));
            if (! File::isFile($asset) || ! $disk->put($item['path'], File::get($asset))) {
                throw new RuntimeException("Unable to publish the required CMS seed asset [{$item['path']}].");
            }
        }
    }
}
