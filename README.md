# نظام إدارة مركز القرآن الكريم

نظام مؤسسي عربي RTL مبني على Laravel 12 وLivewire وTailwind CSS. يضم المصادقة والهيكل التنظيمي وإدارة الطلاب والتسجيل القرآني اليومي، إضافة إلى محرك تقدم ودرجات، لوحات تحليل، تنبيهات ذكية، ودورات وشهادات وإنجازات.

## المتطلبات

- PHP 8.2 مع إضافات Laravel المعتادة و`pdo_mysql`.
- Composer 2.
- MySQL 8+.
- Node.js 20+ وnpm.

## الإعداد المحلي

```bash
composer install
copy .env.example .env
php artisan key:generate
```

أنشئ قاعدة MySQL فارغة ثم حدّث بيانات الاتصال في `.env`. لإنشاء أول مدير، عيّن `INITIAL_ADMIN_NAME` و`INITIAL_ADMIN_EMAIL` و`INITIAL_ADMIN_PASSWORD`، ثم شغّل:

```bash
php artisan migrate
php artisan db:seed
npm install
npm run build
php artisan serve
```

لا يوجد تسجيل عام للحسابات؛ ينشئ المدير الموظفين والمحفظين من شاشة الهيكل التنظيمي.

ينشئ `QuranReferenceSeeder` مرجعًا موضعيًا ثابتًا من 114 سورة و6236 آية، مع الجزء والحزب والصفحة، دون تخزين نص القرآن. بعد الدخول:

- شاشة «الطلاب» لإدارة الملف والصورة والهوية والحالة والبحث والتصفية وتاريخ الحلقة.
- ملف الطالب لربط أولياء الأمور والوثائق الخاصة وتسجيل المحفوظ السابق وعرض السجل الكامل.
- شاشة «التسجيل اليومي» للمحفظ لتسجيل الحضور وأنواع الحفظ والمراجعة والتقييم والأخطاء عبر Livewire.
- لوحة متابعة للإدارة والمشرف والمحفظ والطالب، مع فلاتر واتجاهات أسبوعية وشهرية.
- مركز تنبيهات بأسباب وأدلة ومسار متابعة وإغلاق.
- وحدات الدورات والنتائج والشهادات والإنجازات.

## البيانات التجريبية المحلية

يمكن تجهيز عرض محلي قابل للتكرار فقط عندما تكون `APP_ENV=local` أو `testing`:

```bash
php artisan db:seed --class="Database\Seeders\DemoDataSeeder"
php artisan academic:refresh
```

حسابات العرض المحلية:

- الإدارة: `admin@alquran.local` / `DemoAdmin123!`
- المحفظ: `teacher@alquran.local` / `DemoTeacher123!`

غيّر كلمات المرور واحذف بيانات العرض قبل استخدام قاعدة البيانات للإنتاج. لا يعمل Seeder التجريبي في بيئة production.

تعاد التحليلات والتنبيهات يوميًا عبر Laravel Scheduler، ويمكن تشغيلها يدويًا بالأمر `php artisan academic:refresh`.

## الموقع العام وCMS وAPI

- الموقع العام متاح من `/` ويشمل: عن المركز، البرامج، الأنشطة، الأخبار، الإنجازات، المعرض والتواصل.
- إدارة المحتوى متاحة لأصحاب صلاحية `website.manage` من `/cms`.
- خريطة الموقع في `/sitemap.xml` وتعليمات محركات البحث في `/robots.txt`.
- API الرسمية تبدأ من `/api/v1` وتستخدم Laravel Sanctum Bearer Tokens.
- الأدوار الحساسة تستخدم مصادقة TOTP ثنائية إلزامية، وتتوفر إدارة الأمان والجلسات من `/profile/security`.
- إشعارات اكتمال التصدير والتنبيهات الحرجة تصل عبر Reverb/Echo مع fallback مخزن في قاعدة البيانات.
- راجع [توثيق API v1](docs/API_V1.md) لقائمة المسارات والمرشحات وصيغة الأخطاء.
- راجع [قرار تسليم الجوال](docs/MOBILE_DELIVERY.md) لعقد التوافق وقرار PWA-first.
- راجع [دليل الإنتاج والتشغيل](docs/PRODUCTION.md) للنشر والطابور والمجدول والنسخ والاستعادة، و[تقرير Final QA](docs/FINAL_QA.md) لنتيجة مرحلة التثبيت.

بوابة التشغيل الخاصة بالإنتاج:

```bash
php artisan system:production-check
php artisan system:production-check --json
```

يوفر النظام `/up` لفحص liveness و`/ready` لفحص readiness. تتوفر أتمتة النشر في `deploy/deploy.sh` و`deploy/deploy.ps1`، وأوامر إنشاء manifest النسخ والتحقق منه موثقة في دليل الإنتاج.

## التحقق

```bash
php artisan test
vendor/bin/pint --test
npm run build
composer audit
npm audit --omit=dev
```

تعمل الاختبارات على SQLite مؤقتة في الذاكرة ومفصولة تمامًا عن قاعدة المشروع.

## الأمان والتشغيل

- الملفات الحساسة تستخدم قرص `private` خارج `public/`، والتنزيل يمر عبر الصلاحيات.
- عيّن `SESSION_SECURE_COOKIE=true` عند تشغيل HTTPS في الإنتاج.
- شغّل عامل الطوابير وLaravel Scheduler في بيئة الإنتاج عند إضافة المهام المجدولة والإشعارات المؤجلة.
- لا تستخدم أوامر مسح قاعدة البيانات على قاعدة المشروع.

راجع [الخطة الرئيسية](ALQURAN_SYSTEM_MASTER_PLAN.md) و[حالة تنفيذ Codex](docs/CODEX_STATE.md).
