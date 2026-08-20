# Al-Quran Center Management System
## Master Project Plan & Codex Execution Guide

> **Project path:** `D:\laravel projects\algofran-system`  
> **Backend:** Laravel 12  
> **PHP:** 8.2.x  
> **Database:** MySQL  
> **Frontend:** Blade + Livewire + Tailwind CSS + Alpine.js  
> **API:** Laravel Sanctum + `/api/v1`  
> **Real-time:** Laravel Reverb + Echo عند الحاجة  
> **Primary language:** Arabic RTL  
> **Future target:** Web + Mobile App using the same backend/API

---

# 1. Purpose of This File

هذا الملف هو المرجع الرئيسي للمشروع.

على Codex قراءته قبل أي عمل كبير في المشروع.

المطلوب هو بناء **نظام مؤسسي متكامل لإدارة مركز تحفيظ القرآن الكريم**، وليس مجرد CRUD بسيط للطلاب والمعلمين.

النظام يجب أن يدير دورة العمل كاملة:

**المركز → الفروع → الحلقات → المحفظون → الطلاب → الحفظ اليومي → المراجعة → التقييم → التحليل → التنبيهات → التقارير**

ويجب أن يكون النظام:

- احترافي.
- سريع.
- آمن.
- متجاوب مع الهاتف.
- عربي RTL.
- سهل الاستخدام للمحفظ.
- مناسب للإدارة.
- قابل للتوسع.
- جاهزاً لإضافة تطبيق جوال لاحقاً.
- مبنياً على API واضحة.
- من دون تكرار Business Logic بين Web وAPI.

---

# 2. Critical Codex Working Rules

## قاعدة العمل الأساسية

**لا تنفذ المشروع كاملاً دفعة واحدة.**

المشروع مقسم إلى مراحل رئيسية.

يتم تنفيذ **مرحلة واحدة فقط في كل مرة**.

بعد الانتهاء من المرحلة:

1. شغّل الاختبارات المرتبطة بها.
2. افحص الأخطاء.
3. أصلح المشاكل المكتشفة.
4. افحص `git diff`.
5. تأكد من عدم وجود ملفات مكررة أو حلول مؤقتة.
6. حدّث هذا الملف أو ملفات التوثيق إذا تغير قرار مهم.
7. اعرض تقريراً مختصراً بما تم إنجازه.
8. **توقف تماماً.**

لا تبدأ المرحلة التالية حتى يعطي المستخدم أمراً صريحاً مثل:

`START PHASE 2`

أو:

`CONTINUE TO NEXT PHASE`

---

# 3. Safety Rules

هذا المشروع يجب التعامل معه كأنه مشروع Production مهم.

## ممنوع

- إعادة إنشاء Laravel Project داخل المجلد.
- تغيير Laravel 12 إلى Laravel 13.
- تغيير PHP 8.2 إلى إصدار آخر.
- تغيير MySQL إلى Database Engine آخر.
- تشغيل `composer update` بشكل عام بدون ضرورة.
- حذف `composer.lock`.
- حذف `package-lock.json`.
- تغيير `.env` بالكامل.
- تغيير `APP_KEY` إذا كان موجوداً.
- إعادة كتابة Module كامل بدون سبب حقيقي.
- إنشاء نسخ من الملفات مثل:
  - `StudentControllerNew.php`
  - `StudentControllerV2.php`
  - `students_final.blade.php`
  - `students_backup.blade.php`
- إنشاء Service أو Action جديدة قبل البحث عن Implementation موجود.
- إنشاء Business Logic مكرر بين Controller وLivewire وAPI.
- تعديل ملفات لا علاقة لها بالمهمة الحالية.
- إضافة Packages عشوائية.

## أوامر قاعدة البيانات المحظورة على قاعدة المشروع

لا تستخدم:

```bash
php artisan migrate:fresh
php artisan migrate:refresh
php artisan migrate:reset
php artisan db:wipe
```

ولا تستخدم:

- DROP DATABASE
- TRUNCATE
- DROP TABLE

على قاعدة المشروع الحالية.

إذا احتاجت الاختبارات Fresh Database فيجب أن يكون ذلك على **Testing Database منفصلة ومؤكدة**.

---

# 4. Codex Operating Mode

في كل مهمة اتبع هذا التسلسل:

**INSPECT → UNDERSTAND → SEARCH EXISTING CODE → PLAN MINIMAL CHANGE → EDIT → TEST → REVIEW DIFF → REPORT → STOP**

قبل إنشاء أي:

- Model
- Controller
- Livewire Component
- Service
- Action
- DTO
- Enum
- Policy
- Migration
- Route
- Job
- Event
- Listener
- Notification

ابحث أولاً داخل المشروع لتتأكد من عدم وجود تنفيذ مماثل.

القاعدة:

> **Extend existing code when possible. Do not duplicate it.**

---

# 5. Token and Work Efficiency

لا تقم بفحص المشروع بالكامل في كل مرة.

لا تقرأ:

- `vendor/`
- `node_modules/`
- `storage/logs/`

إلا عند الحاجة المباشرة.

استخدم البحث الموجه.

في بداية المرحلة اقرأ:

- هذا الملف.
- الملفات المتعلقة بالمرحلة فقط.
- `composer.json`
- `package.json` عند الحاجة.
- migrations ذات العلاقة.
- Models/Actions/Components ذات العلاقة.

يمكن إنشاء ملف مختصر:

`docs/CODEX_STATE.md`

يحتوي:

- المرحلة الحالية.
- المراحل المكتملة.
- Architecture الحالية.
- Packages المهمة.
- الجداول المهمة.
- آخر Tests ناجحة.
- القرارات المهمة.
- العمل المتبقي.

الهدف هو منع إعادة اكتشاف المشروع من الصفر في كل جلسة.

---

# 6. Approved Technical Stack

## Backend

- Laravel 12.x
- PHP 8.2.x
- MySQL
- Eloquent ORM
- Laravel Events
- Laravel Listeners
- Laravel Queues
- Laravel Scheduler
- Laravel Notifications
- Laravel Policies
- Laravel Form Requests
- Laravel API Resources
- Laravel Sanctum

## Frontend

استخدم:

- Blade
- Livewire
- Tailwind CSS
- Alpine.js

يمكن استخدام مكتبة UI مناسبة متوافقة مع المشروع إذا كانت مفيدة فعلاً، لكن لا تثبت مكتبات كثيرة بلا حاجة.

لا تستخدم:

- React
- Vue
- jQuery
- Bootstrap

إلا إذا طلب المستخدم تغيير القرار لاحقاً.

## Real-time

عندما يكون هناك تحديث حقيقي بين مستخدمين مختلفين استخدم:

- Laravel Reverb
- Laravel Echo
- WebSockets

مثال:

- ظهور تنبيه للمدير عند تسجيل حالة خطرة.
- ظهور Notification فورية.
- اكتمال Export كبير.
- تحديث حالة Report.

أما التفاعل داخل الصفحة نفسها فيستخدم Livewire ولا يحتاج WebSocket بالضرورة.

---

# 7. Architecture

استخدم **Modular Monolith**.

لا تستخدم Microservices.

Business Logic لا توضع داخل Controllers أو Livewire Components.

استخدم عند الحاجة:

- Actions
- Services
- Enums
- DTOs
- Policies
- Events
- Listeners
- Jobs
- Query classes
- API Resources

مثال:

```text
RecordStudentRecitationAction
CalculateStudentProgressAction
AssignStudentToHalaqaAction
GenerateStudentAlertAction
GenerateStudentReportAction
```

Web وAPI يجب أن يستخدما نفس Business Logic.

---

# 8. Main System Structure

الهيكل الإداري الأساسي:

```text
Center
  └── Branch
       └── Halaqa
            └── Teacher
                 └── Students
```

يجب دعم أكثر من فرع حتى لو كان المركز حالياً فرعاً واحداً.

---

# 9. Users, Roles and Permissions

الأدوار المبدئية:

- Super Admin
- Center Manager
- Academic Supervisor
- Registrar
- Teacher / Muhafiz
- Guardian
- Student
- Website Editor
- Report Viewer

لا تعتمد على Role وحده.

أنشئ Permissions دقيقة مثل:

```text
students.view
students.create
students.update
students.archive
students.export

halaqas.view
halaqas.manage

recitations.view
recitations.create
recitations.update

attendance.manage

courses.manage
certificates.manage

reports.view
reports.export

alerts.view
alerts.manage

website.manage

users.manage
roles.manage

settings.manage
audit.view

guardian.private-data.view
```

Backend Authorization إلزامي عبر Policies/Permissions.

إخفاء الزر في الواجهة ليس حماية.

---

# 10. Student Management

ملف الطالب يجب أن يدعم على الأقل:

- Student number.
- First name.
- Father name.
- Grandfather name.
- Family name.
- Full four-part name.
- National/identity number.
- Birth date.
- Personal photo.
- Contact number إذا وجد.
- Registration date.
- Status.
- Current Halaqa.
- Current Teacher.
- Initial memorization baseline.
- Notes.
- Created by.
- Updated by.

الحالات:

```text
active
suspended
withdrawn
graduated
archived
```

يفضل استخدام Enum.

لا تخزن `memorized_parts` كقيمة يدوية تكون هي Source of Truth.

تقدم الطالب يجب أن يحسب من سجلات الحفظ الفعلية مع Baseline واضح.

---

# 11. Guardians

ولي الأمر Entity مستقل.

البيانات:

- Full name.
- Identity number.
- Relationship.
- Phone.
- Alternative phone.
- Email optional.
- Private identity document.
- Notes.

العلاقة يجب أن تسمح:

- Guardian لديه عدة طلاب.
- الطالب يمكن أن يكون له أكثر من Guardian مستقبلاً.

لا تكرر بيانات ولي الأمر لكل طالب.

---

# 12. Private Files

الملفات الحساسة مثل:

- هوية الطالب.
- هوية ولي الأمر.
- الصور الخاصة.
- الشهادات الخاصة.
- التقارير الداخلية.

لا تخزنها في Public Storage.

يجب تطبيق Authorization قبل الوصول إليها.

لا تعرض Storage Path مباشرة للمستخدم.

---

# 13. Halaqas

كل حلقة تحتوي مثلاً:

- Center.
- Branch.
- Name.
- Code.
- Primary teacher.
- Assistant teacher optional.
- Program.
- Capacity.
- Room.
- Status.
- Start date.
- Weekly schedule.
- Notes.

يجب الاحتفاظ بتاريخ انتقال الطالب بين الحلقات.

لا يكفي تغيير `current_halaqa_id`.

يجب وجود Enrollment History.

---

# 14. Quran Reference Data

هذه البيانات مرجعية وحساسة ويجب التعامل معها بدقة.

يجب أن يملك النظام مرجعاً للسور والآيات:

- 114 سورة.
- Surah number.
- Surah name.
- Ayah number.
- Global ordering إذا اعتمد.
- Juz.
- Hizb عند توفر بيانات موثوقة.
- Page عند توفر بيانات موثوقة.

لا تسمح للمستخدم بإدخال آية غير موجودة.

لا تسمح بأن تكون نهاية نطاق التسميع قبل بدايته.

اختيار الحفظ والمراجعة يجب أن يتم من Quran Reference Data وليس نصاً يدوياً فقط.

---

# 15. Student Initial Memorization Baseline

قد يدخل الطالب إلى المركز وهو يحفظ مسبقاً.

يجب إنشاء مفهوم:

`Initial Memorization Baseline`

بحيث يتم تسجيل ما كان يحفظه قبل الالتحاق.

لا تعامل Baseline على أنه جلسة قام بها داخل المركز.

---

# 16. Daily Hifz and Recitation Engine

هذا هو قلب التطبيق.

يجب أن يستطيع المحفظ تسجيل جلسة كل طالب يومياً.

كل Daily Record مرتبط بـ:

- Student.
- Teacher.
- Halaqa.
- Date.
- Attendance.
- General evaluation.
- Notes.

وتحته يمكن إضافة أكثر من Recitation Item.

أنواع التسميع:

```text
new_memorization
recent_revision
old_revision
recitation
exam
tajweed
```

كل Recitation Item يدعم:

- Type.
- Start Surah.
- Start Ayah.
- End Surah.
- End Ayah.
- Evaluation.
- Notes.
- Memorization errors.
- Tajweed errors.
- Hesitation count.
- Teacher prompt count.

يمكن في نفس اليوم تسجيل:

- حفظ جديد.
- مراجعة قريبة.
- مراجعة قديمة.

---

# 17. Evaluation

القيم الأساسية:

```text
poor
good
very_good
excellent
```

واجهة المستخدم بالعربية:

- ضعيف.
- جيد.
- جيد جداً.
- ممتاز.

يمكن ربط كل قيمة بدرجة رقمية للتحليل.

لا تجعل Business Logic يعتمد على النص العربي.

---

# 18. Attendance

الحالات:

```text
present
absent
excused
late
```

تسجل حسب:

- Student.
- Halaqa.
- Date.
- Recorder.

---

# 19. Student Timeline

كل طالب يجب أن يملك Timeline واضحة تعرض:

- الحضور.
- الحفظ الجديد.
- المراجعة.
- التقييم.
- الأخطاء.
- ملاحظات المحفظ.
- الإنجازات.
- الدورات.
- الشهادات.

مع Filters حسب:

- التاريخ.
- السورة.
- نوع التسميع.
- التقييم.

---

# 20. Student Progress Engine

يجب حساب مؤشرات الطالب من البيانات الحقيقية:

- آخر موضع حفظ.
- عدد الآيات المحفوظة.
- نسبة تقريبية من القرآن.
- السور المكتملة.
- الأجزاء المكتملة.
- آخر مراجعة.
- عدد جلسات الحفظ.
- عدد جلسات المراجعة.
- متوسط التقييم.
- اتجاه الأداء.
- الحضور.

لا تستخدم قيم Hardcoded.

يمكن استخدام Progress/Snapshot tables لتحسين الأداء مع بقاء سجلات التسميع Source of Truth.

---

# 21. Smart Student Score

أنشئ Score واضحاً وقابلاً للتفسير من:

`0 - 100`

العوامل يمكن أن تشمل:

- Recitation quality.
- Revision adherence.
- Attendance.
- Target achievement.
- Improvement trend.

الأوزان يجب أن تكون قابلة للتعديل من Settings أو Configuration.

عند انخفاض Score يجب أن يستطيع النظام شرح السبب.

---

# 22. Alerts Engine

النظام يحتاج تنبيهات ذكية وقابلة للضبط.

أمثلة:

- تقييم ضعيف مرتين متتاليتين.
- ثلاثة تقييمات ضعيفة ضمن آخر خمس جلسات.
- انخفاض متوسط المستوى.
- كثرة الغياب.
- تأخر في المراجعات.
- عدم تسجيل حفظ لفترة.
- عدم قيام المحفظ بتسجيل سجلات طلاب الحلقة.

Alert يجب أن يحتوي:

- Type.
- Severity.
- Reason.
- Related student.
- Related teacher optional.
- Related halaqa optional.
- Generated at.
- Status.
- Resolved at.
- Resolved by.

Severity:

```text
info
warning
critical
```

---

# 23. Courses, Certificates and Achievements

## Courses

- Name.
- Description.
- Instructor.
- Dates.
- Hours.
- Status.
- Student enrollment.
- Completion status.
- Result.

## Certificates

- Student.
- Course optional.
- Certificate name.
- Issuer.
- Certificate number.
- Issue date.
- Expiry date optional.
- Grade optional.
- Private file.
- Notes.

## Achievements

أمثلة:

- إكمال سورة.
- إكمال جزء.
- إكمال خمسة أجزاء.
- إكمال عشرة أجزاء.
- نصف القرآن.
- ختم القرآن.
- مسابقة.
- تكريم.
- دورة.
- إنجاز يدوي.

---

# 24. Dashboards

## Management Dashboard

تعرض:

- الطلاب الفعالون.
- عدد المحفظين.
- عدد الحلقات.
- الحضور اليوم.
- الغياب.
- جلسات التسميع اليوم.
- الحفظ الجديد.
- المراجعات.
- الطلاب المحتاجون للمتابعة.
- التنبيهات.
- اتجاه الأداء الأسبوعي والشهري.
- مستويات الطلاب.
- أداء الحلقات.

Filters:

- Branch.
- Halaqa.
- Teacher.
- Student.
- Date Range.
- Program.

## Teacher Dashboard

المحفظ يرى:

- حلقة اليوم.
- الطلاب.
- الحضور.
- الغياب.
- المراجعات المطلوبة.
- الطلاب المحتاجون للمتابعة.
- زر واضح لبدء تسجيل جلسات اليوم.

## Supervisor Dashboard

تعرض:

- الطلاب المتراجعين.
- الحلقات التي تحتاج متابعة.
- مراجعات متأخرة.
- سجلات ناقصة.
- متوسط الأداء.
- تنبيهات أكاديمية.

---

# 25. UI / UX

التصميم يجب أن يكون:

- Modern.
- Professional.
- Clean.
- Arabic RTL First.
- Responsive.
- Mobile friendly.
- سريع للمحفظ أثناء تسجيل الطلاب.

استخدم:

- Sidebar.
- Topbar.
- Breadcrumbs.
- Cards.
- Tabs.
- Drawers.
- Modals.
- Search.
- Filters.
- Pagination.
- Skeleton loaders.
- Empty states.
- Toast notifications.
- Confirmation dialogs.
- Responsive tables/cards.

لا تستخدم Animations ثقيلة.

استخدم Transitions خفيفة:

- fade.
- slide.
- collapse.
- modal transition.
- counters.
- hover interactions.

---

# 26. Calendar

يجب وجود Calendar يومي/أسبوعي/شهري عند الحاجة.

يعرض:

- الحلقات.
- المواعيد.
- الدورات.
- الاختبارات.
- الاجتماعات.
- الأنشطة.
- المراجعات المستحقة.

---

# 27. Notifications and Real-Time

دعم:

- Database notifications.
- Email architecture.
- Push لاحقاً.
- SMS/WhatsApp لاحقاً.

Real-time عند الحاجة عبر Reverb/Echo.

لا تربط Business Logic بمزود خارجي مباشرة.

---

# 28. Reports

يجب وجود Report Center.

التقارير الأساسية:

- Student Report.
- Halaqa Report.
- Teacher Report.
- Attendance Report.
- Memorization Report.
- Revision Report.
- Evaluation Report.
- Alerts Report.
- Courses Report.
- Certificates Report.
- Management Summary.

---

# 29. Excel Exports

Excel Feature أساسية.

يجب دعم Export مع Filters.

مثل:

- Students.
- Halaqas.
- Attendance.
- Memorization.
- Revisions.
- Evaluations.
- Courses.
- Certificates.
- Alerts.

التقارير الكبيرة يجب أن تعمل عبر Queue.

الحالات:

```text
preparing
ready
failed
```

ويجب أن يكون Download خاضعاً للصلاحيات.

---

# 30. Uploaded Reports and Documents

الإدارة تستطيع رفع:

- PDF.
- Excel.
- Word.
- Images.

مع:

- Title.
- Type.
- Period.
- Uploader.
- Privacy level.
- Notes.
- File.

---

# 31. Public Website

المشروع يحتوي موقعاً عاماً منفصلاً بصرياً عن لوحة الإدارة.

الصفحات:

- Home.
- About.
- Programs.
- Activities.
- News.
- Achievements.
- Gallery.
- Contact.
- FAQ عند الحاجة.

لا تعرض بيانات الطلاب الخاصة.

---

# 32. CMS

الإدارة تستطيع إدارة:

- الصفحات.
- الأخبار.
- النشاطات.
- الصور.
- الإعلانات.
- الإنجازات العامة.

الحالات:

```text
draft
published
archived
```

---

# 33. API and Mobile Readiness

يجب إنشاء API versioned:

```text
/api/v1
```

باستخدام Laravel Sanctum.

أمثلة:

```text
/api/v1/me
/api/v1/halaqas
/api/v1/halaqas/{halaqa}/students
/api/v1/students/{student}
/api/v1/students/{student}/progress
/api/v1/students/{student}/recitations
/api/v1/daily-records
/api/v1/attendance
/api/v1/calendar
/api/v1/alerts
/api/v1/courses
/api/v1/certificates
```

استخدم:

- Form Requests.
- Policies.
- API Resources.
- Pagination.
- Filters.
- Unified error format.
- Proper HTTP status codes.

Web وAPI يشتركان في نفس Action/Service layer.

الهدف أن نتمكن لاحقاً من بناء Flutter أو React Native بدون إعادة كتابة Backend.

---

# 34. Audit Log

Audit Log إلزامي للعمليات الحساسة.

أمثلة:

- إنشاء طالب.
- تعديل طالب.
- نقل الطالب.
- تعديل تقييم.
- تسجيل تسميع.
- أرشفة سجل.
- تصدير Excel.
- تغيير صلاحية.
- رفع تقرير.

حيثما كان مناسباً سجل:

- User.
- Action.
- Resource.
- Resource ID.
- Old values.
- New values.
- IP.
- User agent.
- Timestamp.

لا تسجل Passwords أو Secrets.

---

# 35. Security

يجب تطبيق:

- Authentication.
- Authorization.
- CSRF protection.
- XSS protection.
- SQL injection protection via Laravel/Eloquent practices.
- Mass assignment protection.
- Rate limiting.
- Secure file uploads.
- MIME validation.
- File size limits.
- Private storage.
- Session security.
- Password change.
- Forgot/reset password.
- 2FA للإدارة عندما يتم تفعيله.
- API token security.

---

# 36. Database Rules

كل Migration يجب أن تراجع:

- Foreign keys.
- Indexes.
- Unique constraints.
- Nullable logic.
- Cascade/restrict behavior.
- Timestamps.
- Soft deletes عندما تكون مناسبة.

لا تستخدم JSON لتخزين بيانات Relational بدون سبب قوي.

استخدم Transactions للعمليات المركبة.

لا تعدل Migration قديمة مطبقة إذا كان ذلك يمكن أن يكسر بيئة قائمة.

أنشئ Migration جديدة للتغييرات اللاحقة.

---

# 37. Testing Rules

لا تعتبر Feature مكتملة بدون Tests مناسبة.

استخدم:

- Unit Tests.
- Feature Tests.
- Authorization Tests.
- API Tests.
- Validation Tests.

السيناريو الحرج:

```text
Teacher Login
→ يرى حلقته فقط
→ يفتح طالباً من حلقته
→ يسجل الحضور
→ يسجل حفظاً جديداً
→ يسجل مراجعة
→ يختار التقييم
→ يحفظ
→ يظهر السجل في Timeline
→ يتحدث Progress
→ يتم إنشاء Alert إذا تحققت Rule
→ المدير يستطيع رؤيتها
→ المحفظ لا يستطيع تعديل طالب خارج حلقته
```

---

# 38. Performance

تجنب:

- N+1 queries.
- Unbounded queries.
- تحميل آلاف السجلات في الواجهة.
- Filter كل البيانات في Frontend.
- حساب تاريخ النظام بالكامل مع كل Refresh.

استخدم:

- Eager loading.
- Pagination.
- Indexes.
- Server-side filters.
- Queues.
- Cache عند الحاجة.
- Summary/Snapshot data عندما يكون ذلك مفيداً.

---

# 39. Project Phases

المراحل مقصودة أن تكون **كبيرة نسبياً** حتى لا يتحول المشروع إلى عشرات الخطوات الصغيرة.

لا تقسّمها إلى Phases إضافية بدون حاجة حقيقية.

---

## PHASE 1 — Foundation, Authentication and Organization

نفذ في هذه المرحلة:

### Project Foundation

- فحص Laravel/PHP/MySQL configuration.
- تجهيز `.env.example` المناسب دون الكتابة فوق `.env`.
- تجهيز RTL.
- تجهيز Layout الرئيسي.
- Livewire.
- Tailwind.
- Alpine.js.
- UI foundation.
- Database conventions.
- Coding conventions.

### Authentication

- Login.
- Logout.
- Forgot Password.
- Reset Password.
- Change Password.
- Profile.

### Roles and Permissions

- Roles.
- Permissions.
- Policies foundation.

### Organization

- Centers.
- Branches.
- Staff.
- Teachers.
- Halaqas.
- Halaqa schedules.
- Teacher assignments.
- Enrollment history foundation.

### System Foundation

- Settings.
- Audit log foundation.
- Private storage foundation.
- Base notifications architecture.

### Expected Result

في نهاية المرحلة يجب أن يكون لدينا:

- نظام دخول كامل.
- Dashboard shell.
- RTL responsive layout.
- صلاحيات أساسية.
- إدارة المركز والفروع والحلقات والمحفظين.
- قاعدة Architecture يمكن البناء عليها.

**توقف بعد المرحلة ولا تبدأ PHASE 2.**

---

## PHASE 2 — Students, Guardians and Quran Daily Tracking

هذه أهم مرحلة Functional في المشروع.

### Students

- Student profile.
- Student status.
- Personal photo.
- Identity data.
- Search.
- Filters.
- Pagination.
- Halaqa enrollment.
- Enrollment history.

### Guardians

- Guardian records.
- Student/Guardian relationship.
- Guardian identity.
- Contact information.
- Private documents.

### Quran Reference Engine

- Surahs.
- Ayahs.
- Quran reference data.
- Juz/Page metadata إذا كان المصدر المعتمد متوفراً.
- Quran picker components.
- Validation.
- Initial memorization baseline.

### Daily Hifz

- Daily student records.
- Attendance.
- New memorization.
- Recent revision.
- Old revision.
- Recitation.
- Exam.
- Tajweed.
- Evaluation.
- Error counters.
- Notes.

### Teacher UX

إنشاء شاشة سريعة للمحفظ:

```text
حلقتي
→ طلاب اليوم
→ اختيار الطالب
→ الحضور
→ الحفظ الجديد
→ المراجعة
→ التقييم
→ حفظ
```

بدون Page Reload كامل.

### Student Timeline

إظهار التاريخ الكامل لسجلات الطالب.

### Expected Result

في نهاية المرحلة يجب أن يستطيع المحفظ فعلياً إدارة حلقته وتسجيل حفظ ومراجعة كل طالب بشكل يومي ودقيق.

**توقف بعد المرحلة ولا تبدأ PHASE 3.**

---

## PHASE 3 — Progress, Academic Modules, Dashboards and Smart Alerts

### Progress Engine

- Last memorized position.
- Memorized ayahs.
- Surah completion.
- Juz completion.
- Revision statistics.
- Evaluation averages.
- Attendance statistics.
- Progress snapshots.
- Student score 0-100.

### Courses

- Courses.
- Enrollments.
- Completion.
- Results.

### Certificates

- Certificates.
- Files.
- Issuer.
- Dates.
- Grades.

### Achievements

- Quran achievements.
- Competitions.
- Awards.
- Manual achievements.

### Dashboards

- Management Dashboard.
- Supervisor Dashboard.
- Teacher Dashboard.
- Student analytics.

### Charts and Filters

- Date range.
- Branch.
- Halaqa.
- Teacher.
- Student.
- Program.

### Smart Alerts

- Performance decline.
- Repeated poor evaluations.
- Frequent absence.
- Revision delay.
- Missing records.
- Alert workflow.
- Resolve workflow.

### Expected Result

في نهاية المرحلة يصبح النظام قادراً على تحويل البيانات اليومية إلى معلومات إدارية واضحة وتنبيهات مفيدة.

**توقف بعد المرحلة ولا تبدأ PHASE 4.**

---

## PHASE 4 — Calendar, Notifications, Reports and Excel

### Calendar

- Daily view.
- Weekly view.
- Monthly view.
- Halaqa schedules.
- Courses.
- Exams.
- Events.
- Meetings.
- Revision-related events.

### Notifications

- Database notifications.
- Notification center.
- Unread counter.
- Real-time notifications عند الحاجة.
- Reverb/Echo integration عندما تكون مفيدة فعلاً.

### Reports

- Student reports.
- Halaqa reports.
- Teacher reports.
- Attendance.
- Memorization.
- Revision.
- Evaluations.
- Alerts.
- Courses.
- Certificates.
- Management summary.

### Excel

- Filtered exports.
- Multi-sheet exports عند الحاجة.
- Large queued exports.
- Export status.
- Secure downloads.

### Uploaded Reports

- رفع تقارير ومستندات.
- Privacy.
- Period.
- Type.
- Notes.

### Expected Result

في نهاية المرحلة يجب أن يكون لدى الإدارة مركز تقارير كامل وتقويم وإشعارات وتصديرات Excel احترافية.

**توقف بعد المرحلة ولا تبدأ PHASE 5.**

---

## PHASE 5 — Public Website, CMS and API V1

### Public Website

- Home.
- About.
- Programs.
- Activities.
- News.
- Achievements.
- Gallery.
- Contact.
- Responsive design.
- RTL.
- SEO foundation.

### CMS

- Pages.
- News.
- Activities.
- Media.
- Announcements.
- Public achievements.
- Draft/Published/Archived.

### API

إنشاء:

`/api/v1`

باستخدام Laravel Sanctum.

تشمل API الأساسية:

- Authentication.
- Current user.
- Halaqas.
- Students.
- Student progress.
- Daily records.
- Recitations.
- Attendance.
- Calendar.
- Alerts.
- Courses.
- Certificates.

### Mobile Readiness

- API Resources.
- Pagination.
- Filtering.
- Error format.
- Authorization.
- Shared Action/Service layer.

### Expected Result

في نهاية المرحلة يصبح لدينا موقع عام ولوحة CMS وAPI رسمية جاهزة لبناء تطبيق جوال فوقها.

**توقف بعد المرحلة ولا تبدأ PHASE 6.**

---

## PHASE 6 — Security, Performance and Final QA

هذه مرحلة تثبيت النظام قبل اعتباره جاهزاً.

### Security Review

راجع:

- Authentication.
- Authorization.
- IDOR.
- CSRF.
- XSS.
- File access.
- Sensitive documents.
- API tokens.
- Rate limits.
- Password/security rules.
- Audit coverage.

### Database Review

راجع:

- Indexes.
- Foreign keys.
- Unique constraints.
- Slow queries.
- N+1.
- Transaction coverage.

### Performance

- Dashboard queries.
- Cache.
- Queues.
- Reverb.
- Exports.
- Images.
- Pagination.
- Asset build.

### Responsive QA

اختبر:

- Mobile.
- Tablet.
- Laptop.
- Desktop.

### Roles QA

اختبر:

- Super Admin.
- Manager.
- Supervisor.
- Registrar.
- Teacher.
- Guardian إذا كانت واجهته متوفرة.
- Student إذا كانت واجهته متوفرة.
- Website Editor.
- Report Viewer.

### Critical Flow QA

اختبر النظام من التسجيل حتى التقرير.

### Production Readiness

- Production environment checklist.
- APP_DEBUG=false documentation.
- Queues.
- Scheduler.
- Reverb إذا مستخدم.
- Backup strategy.
- Restore strategy.
- Logs.
- Deployment documentation.

### Expected Result

في نهاية المرحلة يجب تقديم تقرير Final QA واضح بالمشاكل التي اكتشفت وتم إصلاحها وأي نقاط متبقية.

**لا تقم بأي تطوير إضافي بعد هذه المرحلة بدون طلب المستخدم.**

---

## PHASE 7 — Production Deployment and Operations Automation

هذه المرحلة أضيفت بطلب صريح من المستخدم بعد اكتمال الخطة الأساسية.

### Production Preflight

- أمر آمن لفحص إعدادات الإنتاج دون عرض الأسرار.
- التحقق من البيئة، HTTPS، `APP_DEBUG=false`، الاتصال بقاعدة البيانات، الترحيلات، الطابور، الكاش، الجلسات، البريد، التخزين والرموز.
- منع اعتماد بيئة تحتوي حسابات العرض التجريبية.

### Operational Readiness

- Readiness endpoint آمنة لموازن الحمل.
- أتمتة نشر قابلة للتكرار لـLinux وWindows.
- إعادة تشغيل عمال الطابور بعد النشر.
- التحقق من Scheduler وعمليات التنظيف والمراقبة.

### Backup Integrity

- إنشاء manifest مشفر تجزئيًا لملفات النسخ الاحتياطي.
- التحقق من checksum والحجم قبل الاستعادة.
- إبقاء إنشاء نسخة MySQL الفعلية خارج عملية PHP واستخدام منصة نسخ أو أداة MySQL الرسمية.

### Expected Result

يصبح إصدار النظام قابلًا للنشر والتشغيل المتكرر مع بوابة preflight واضحة، فحص readiness، وإجراءات نسخ واستعادة قابلة للتحقق.

**توقف بعد المرحلة ولا تبدأ PHASE 8.**

---

## PHASE 8 — Strong Identity, Real-time and Mobile Delivery

هذه المرحلة أضيفت بطلب صريح من المستخدم، ولا تبدأ إلا بعد اعتماد PHASE 7.

### Strong Identity

- 2FA للحسابات الإدارية والحساسة.
- Recovery codes وإدارة جلسات وأجهزة المستخدم.
- سياسات إعادة المصادقة للعمليات الحساسة.

### Real-time

- Laravel Reverb + Echo عندما تكون هناك قيمة فعلية بين مستخدمين مختلفين.
- إشعارات فورية للتنبيهات الحرجة واكتمال التصدير.
- Fallback واضح عند تعذر WebSocket.

### Mobile Delivery

- تثبيت عقد API v1 واختبارات التوافق.
- تحديد قرار التطبيق الأصلي أو PWA قبل التنفيذ.
- مصادقة آمنة للأجهزة، إدارة الرموز، وتجربة عربية RTL.

### Expected Result

هوية إدارية قوية وقناة تحديث فوري وأساس تسليم جوال واضح دون تكرار منطق الأعمال.

**توقف بعد المرحلة ولا تبدأ تطويرًا إضافيًا دون طلب المستخدم.**

---

# 40. Phase Acceptance Gate

قبل اعتبار أي Phase مكتملة تأكد من:

- Migrations الجديدة آمنة.
- لا يوجد destructive database operation.
- Routes تعمل.
- Authorization موجودة.
- Validation موجودة.
- Tests الخاصة بالمرحلة ناجحة.
- Build ناجح عند تعديل Frontend.
- لا توجد ملفات مكررة.
- لا توجد TODOs مرتبطة بمتطلبات المرحلة.
- لا توجد قيم Dashboard مزيفة.
- لا يوجد `dd()` أو `dump()` أو debug code.
- لا توجد تغييرات غير مرتبطة بالمرحلة.
- `git diff` تمت مراجعته.
- Documentation تم تحديثها عند الحاجة.

---

# 41. End-of-Phase Report

في نهاية كل Phase قدم تقريراً مختصراً فقط:

```text
PHASE COMPLETED

Phase:
[اسم ورقم المرحلة]

Implemented:
- ...
- ...

Database:
- migrations added
- migrations executed
- schema changes

Permissions:
- ...

Routes / API:
- ...

Tests:
- test commands
- passed / failed

Dependencies Added:
- ...
or None

Important Files Changed:
- ...

Security Checks:
- ...

Known Issues:
- ...

Not Implemented Because Outside Current Phase:
- ...

Next Recommended Phase:
PHASE X
```

بعد التقرير:

**STOP.**

لا تبدأ المرحلة التالية.

---

# 42. Starting the Project

في أول جلسة Codex:

1. اقرأ هذا الملف كاملاً.
2. افحص المجلد الحالي.
3. لا تفترض أنه فارغ.
4. افحص Git إن وجد.
5. افحص Laravel/PHP/Composer/Node/MySQL setup.
6. افحص `composer.json` و`package.json`.
7. افحص migrations والroutes والcode الحالي.
8. أنشئ `docs/CODEX_STATE.md` إذا لم يكن موجوداً.
9. نفذ **PHASE 1 فقط**.

الأمر الذي يبدأ العمل:

```text
START PHASE 1
```

بعد أن ينجح ويعرض النتيجة، المستخدم يراجعها بنفسه.

إذا وافق عليها يعطيه:

```text
START PHASE 2
```

وهكذا.

---

# 43. Final Principle

الأولوية دائماً:

1. Data Integrity.
2. Security.
3. Correct Business Logic.
4. Authorization.
5. No Duplication.
6. Maintainability.
7. Testability.
8. Performance.
9. User Experience.
10. Visual Polish.

لا تحاول إظهار التقدم عن طريق إنشاء ملفات كثيرة.

أفضل تنفيذ هو:

**أقل عدد ممكن من التغييرات التي تحقق الوظيفة بصورة صحيحة وآمنة وقابلة للصيانة.**
