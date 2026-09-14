# دليل الإنتاج والتشغيل

هذا الدليل هو قائمة التشغيل المعتمدة للنظام. لا تنسخ `.env` المحلي إلى الخادم؛ ابدأ من `.env.production.example` وأدخل الأسرار عبر مدير أسرار أو صلاحيات ملف مقيدة.

## المتطلبات

- PHP 8.2 أو أحدث، Composer 2، MySQL 8، وNode.js 20 أو أحدث للبناء.
- خادم ويب يوجّه الجذر إلى `public/` فقط، مع HTTPS صالح وإعادة توجيه HTTP إلى HTTPS.
- مستخدم MySQL مستقل للتطبيق بأقل الصلاحيات اللازمة، ولا يُستخدم حساب `root`.
- مساحة دائمة ومحمية لمجلد `storage/app/private`، ومساحة دائمة لـ`storage/app/public`.

## إعداد البيئة

القيم الإلزامية قبل الفتح للجمهور:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://algofran-center.tech
SESSION_SECURE_COOKIE=true
LOG_CHANNEL=daily
LOG_LEVEL=warning
QUEUE_CONNECTION=database
DB_QUEUE_RETRY_AFTER=960
SANCTUM_EXPIRATION=43200
BROADCAST_CONNECTION=null
TWO_FACTOR_AUTH_ENABLED=false
```

أنشئ `APP_KEY` مرة واحدة واحفظه في مدير الأسرار والنسخة الاحتياطية الآمنة. لا تغيّره بعد وجود بيانات أو جلسات مشفرة. اترك متغيرات `INITIAL_ADMIN_*` فارغة بعد إنشاء المدير الأول، ولا تشغّل `DemoDataSeeder` في الإنتاج.

## بوابة جاهزية الإنتاج

شغّل الفحص الآلي قبل تحويل حركة المستخدمين إلى الإصدار:

```bash
php artisan system:production-check --profile=shared --document-root=/absolute/path/to/public_html
```

يفشل الأمر برمز خروج غير صفري إذا كانت البيئة غير آمنة أو قاعدة البيانات غير متاحة أو توجد ترحيلات معلّقة أو إعدادات تشغيل ناقصة. لا يطبع الأمر قيم الأسرار. للاستهلاك من CI يمكن استخدام `--json`.

استخدم `--profile=vps` على VPS بعد إعداد Reverb، و`--profile=shared` على الاستضافة المشتركة مع جذر الويب الفعلي. المصادقة الثنائية معطلة حاليًا بطلب المستخدم؛ يشترط الفحص تفعيلها للحسابات الحساسة فقط عند ضبط `TWO_FACTOR_AUTH_ENABLED=true`. يرفض الفحص كلمات مرور العرض المعروفة حتى لو تغيّر البريد.

يفحص موازن الحمل نقطتين منفصلتين:

- `GET /up` لفحص أن عملية PHP والتطبيق يعملان.
- `GET /ready` لفحص قاعدة البيانات والكاش ومسارات الكتابة؛ يعيد `200` عند الجاهزية و`503` عند تعذرها، دون كشف تفاصيل البنية الداخلية.

## النشر

استخدم سكربت النشر المناسب من نسخة إصدار موثوقة. يجري تفعيل الصيانة قبل تغيير الملفات والاعتماديات، ولا تُرفع إلا بعد نجاح البناء والترحيل وفحص الجاهزية. **عند الفشل تبقى الصيانة مفعّلة** لأن الاعتماديات أو المخطط قد تكون جزئية؛ راجع السبب وأكمل النشر أو استعد نسخة سليمة قبل `php artisan up`.

### النشر من GitHub إلى Hostinger (shared أو VPS)

الملف `.github/workflows/production.yml` ينفذ مرحلتين منفصلتين:

1. عند كل `push` أو `pull_request` يشغّل اختبارات Laravel ويبني أصول Vite، ولا يتصل بالخادم.
2. ينفذ نشر SSH فقط بعد نجاح الفحوص، وعند التشغيل اليدوي مع اختيار `deploy_to_production`، أو عند تفعيل النشر الآلي صراحةً.

أنشئ GitHub Environment باسم `production`، وفعّل موافقة يدوية عليه إن كانت متاحة، ثم عرّف الأسرار التالية داخله دون وضعها في ملفات المشروع:

| الاسم | الغرض |
| --- | --- |
| `PRODUCTION_SSH_HOST` | اسم خادم Hostinger أو عنوانه |
| `PRODUCTION_SSH_USER` | مستخدم نشر محدود الصلاحيات |
| `PRODUCTION_SSH_PRIVATE_KEY` | المفتاح الخاص لمستخدم النشر |
| `PRODUCTION_SSH_KNOWN_HOSTS` | بصمة الخادم الموثوقة من `ssh-keyscan` بعد التحقق منها |
| `PRODUCTION_PROJECT_PATH` | المسار المطلق للمشروع، مثل `/var/www/alquran` |
| `PRODUCTION_SSH_PORT` | منفذ SSH؛ اختياري والقيمة الافتراضية 22 |
| `PRODUCTION_PUBLIC_PATH` | جذر `public_html` الحقيقي؛ إلزامي لمسار shared |

وعرّف المتغيرات التالية؛ اجعل `PRODUCTION_BRANCH` و`AUTO_DEPLOY_PRODUCTION` على مستوى Repository لقراءتهما في شرط الوظيفة، والباقي في Environment `production`:

| الاسم | القيمة |
| --- | --- |
| `PRODUCTION_URL` | `https://algofran-center.tech` |
| `PRODUCTION_BRANCH` | الفرع المعتمد؛ الفرع الحالي أثناء التجهيز هو `gofran/T1` |
| `AUTO_DEPLOY_PRODUCTION` | اتركه `false` أولًا، واجعله `true` فقط بعد نجاح النشر اليدوي |
| `PRODUCTION_HOSTING_PROFILE` | `shared` أو `vps` بحسب الباقة |

النشر اليدوي والآلي مقيدان بـ`PRODUCTION_BRANCH`، وينشران **نفس SHA الذي اجتاز الاختبارات**، وليس أحدث commit وصل لاحقًا. يأخذ Workflow قفلًا قبل Git والصيانة وتغيير الاعتماديات، ويرفض الرجوع إلى commit أقدم أو الكتابة فوق تعديلات محلية متتبعة. لا تستخدم مفتاح SSH لحساب `root` ولا تنسخ `.env` عبر Workflow. مسار shared يستخدم أصول Vite المبنية في CI؛ لا يحتاج Node.js على الاستضافة.

تهيئة نسخة الخادم لأول مرة عملية يدوية واحدة:

```bash
git clone --branch gofran/T1 https://github.com/Tariqalmadhoon/algofran-system.git /var/www/alquran
cd /var/www/alquran
cp .env.production.example .env
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
php artisan key:generate
```

بعدها عدّل `.env` على الخادم، أنشئ قاعدة MySQL الفارغة، واضبط صلاحيات `storage/` و`bootstrap/cache/`. في التهيئة الأولى فقط، وبعد مراجعة القيم، شغّل:

```bash
php artisan migrate --force
php artisan db:seed --class=DatabaseSeeder --force
```

لا ينشئ `DatabaseSeeder` بيانات تجريبية في بيئة `production`؛ بل يضيف بيانات النظام المرجعية والصلاحيات وبيانات القرآن. إذا استُخدمت قيم `INITIAL_ADMIN_*` لإنشاء أول مدير، امسحها من `.env` مباشرة بعد نجاح الإنشاء. لا تشغّل `DemoDataSeeder` على الخادم.

يوجد نموذجان جاهزان للخادم في `deploy/nginx-site.conf.example` و`deploy/supervisor.conf.example`. استبدل جميع قيم `__PLACEHOLDER__`، واجعل شهادة TLS تشمل نطاق الموقع ونطاق Reverb، ثم اختبر إعداد Nginx باستخدام `nginx -t` قبل إعادة تحميله. لا تجعل جذر الموقع هو مجلد المشروع؛ يجب أن يكون `public/` فقط. بعد تثبيت إعداد Supervisor شغّل `supervisorctl reread` ثم `supervisorctl update` وتحقق من أن العامل وReverb في حالة `RUNNING`.

إذا كان المستودع خاصًا، امنح خادم الإنتاج مفتاح GitHub Deploy Key للقراءة فقط حتى يستطيع تنفيذ `git fetch`. هذا المفتاح منفصل عن مفتاح GitHub Actions المستخدم للدخول إلى الخادم، ولا يحتاج صلاحية كتابة للمستودع.

### المسار الاحتياطي: Hostinger Web / Business / Cloud

هذا المسار يشغّل الموقع وAPI والمزامنة، لكنه ليس مساويًا لمسار VPS الكامل:

- لا توجد عملية Supervisor دائمة؛ لذلك تُستهلك الطوابير دوريًا من Cron وقد يتأخر التصدير أو التنبيه حتى الدورة التالية.
- لا تشغّل Reverb كعملية WebSocket دائمة. استخدم `BROADCAST_CONNECTION=null`، وتبقى إشعارات قاعدة البيانات وpolling فعّالة دون كتابة محتوى الإشعار في سجل البث.
- التصديرات الكبيرة واستهلاك الذاكرة والعمليات الطويلة خاضعة لحدود الباقة. إذا توقفت المهام أو تراكم الطابور فالانتقال إلى VPS هو الحل التشغيلي، وليس زيادة عدد عمليات Cron بلا قياس.
- يشغّل السكربت `system:production-check --profile=shared --document-root=...` ويشترط قاعدة البيانات والترحيلات وحماية الجلسات والملفات وpolling. تسجيل المهام في Laravel لا يثبت تشغيل Cron؛ يجب فحصه فعليًا من hPanel.

في `.env` الخاص بهذه البيئة استخدم على الأقل:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://algofran-center.tech
SESSION_SECURE_COOKIE=true
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
DB_QUEUE_RETRY_AFTER=960
BROADCAST_CONNECTION=null
```

#### عزل جذر الويب

في خطط Web/Cloud لا يسمح hPanel بتغيير home directory للموقع. توصي Hostinger عند وجود Laravel داخل `public_html` بإعادة كتابة الطلبات إلى مجلد `public/`، لكن إبقاء المشروع و`.env` و`vendor` داخل جذر الويب لا يحقق معيار العزل المعتمد لهذا النظام. لا تستخدم هذا التركيب هنا.

المسار المقبول هو:

```text
/home/u12345678/domains/example.com/alquran-app/   # المشروع، خارج جذر الويب
/home/u12345678/domains/example.com/public_html/   # ملفات public فقط
```

انشر Git repository في `alquran-app` عبر SSH أو تكامل Git في hPanel، ثم تحقق يدويًا من مسار `public_html` وأنه يخص النطاق الصحيح وأنه لا يحتوي موقعًا آخر. أنشئ علامة الأمان مرة واحدة فقط:

```bash
touch /home/u12345678/domains/example.com/public_html/.alquran-public-root
```

بعد إعداد `.env` وقاعدة البيانات شغّل:

```bash
chmod +x deploy/deploy-shared.sh deploy/cron-shared-*.sh
./deploy/deploy-shared.sh \
  /home/u12345678/domains/example.com/alquran-app \
  /home/u12345678/domains/example.com/public_html
```

خذ نسخة احتياطية من `public_html` قبل التشغيل الأول وتأكد أنه مخصص لهذا النظام. السكربت يرفض وضع التطبيق داخله ويتطلب علامة الأمان، ثم ينسخ ملفات `public/` فقط دون حذف ملفات الرفع أو الأصول ذات الأسماء القديمة التي قد تستخدمها صفحات مفتوحة. يحافظ على `.well-known` و`.user.ini` وعلامة الأمان ورابط `storage`، وينشئ front controller للتطبيق الخارجي. عند عدم وجود Node.js مرر مسار `web-assets.tar` الناتج من CI كوسيط ثالث. يتطلب PHP وComposer وrsync وflock وsymbolic links؛ افحص توفرها في الباقة قبل اعتماد النشر، ولا تنقل `.env` أو كامل المشروع إلى `public_html` لتجاوز القيود.

المراجع الرسمية: [قدرات Web/Cloud والعمليات المجدولة](https://www.hostinger.com/support/which-server-capabilities-are-supported-at-hostinger/)، [قيود تغيير جذر الموقع والانتقال إلى VPS](https://www.hostinger.com/support/1583494-what-is-the-path-to-your-website-s-root-home-directory-and-how-to-change-it-in-hostinger/)، و[تكامل Git في hPanel](https://www.hostinger.com/support/1583302-how-to-deploy-a-git-repository-in-hostinger/).

#### Cron للمجدول والطابور

من hPanel افتح **Advanced → Cron Jobs** وأضف مهمتين من نوع Custom. تستعمل Hostinger التوقيت `UTC`، لكن تشغيل `schedule:run` كل دقيقة يترك Laravel يطبق منطقة `APP_TIMEZONE` على الأحداث:

```cron
* * * * * /bin/bash /home/u12345678/domains/example.com/alquran-app/deploy/cron-shared-schedule.sh /home/u12345678/domains/example.com/alquran-app
* * * * * /bin/bash /home/u12345678/domains/example.com/alquran-app/deploy/cron-shared-queue.sh /home/u12345678/domains/example.com/alquran-app
```

مهمة الطابور تستخدم `queue:work --stop-when-empty` وتنتهي عندما يفرغ الطابور، وتشترط `flock` لمنع التداخل والتزامن مع النشر. لا تضف `queue:listen` أو Reverb إلى Cron. إذا لم تسمح الباقة بالتشغيل كل دقيقة، استخدم أقصر فترة متاحة وسجّل أن الإشعارات والتصديرات قد تتأخر بقدرها. توثق Hostinger [إعداد Cron](https://www.hostinger.com/support/1583465-how-to-set-up-a-cron-job-at-hostinger/) و[عرض مخرجاته](https://www.hostinger.com/support/5647075-how-to-check-the-output-of-a-cron-job-at-hostinger/).

بعد أول نشر، وقبل فتح الموقع، تحقق من:

```bash
php artisan migrate:status
php artisan schedule:list
php artisan queue:monitor database:default --max=100
curl --fail https://algofran-center.tech/up
curl --fail https://algofran-center.tech/ready
```

ثم اختبر تسجيل الدخول، إرسال البريد، تسجيل سجل ومزامنته من الهاتف، تنفيذ تصدير صغير، وعرض ناتج مهمتي Cron من hPanel. احتفظ بـ`AUTO_DEPLOY_PRODUCTION=false` حتى نجاح أول نشر يدوي؛ بعدها يمكن تفعيله لمسار shared أو VPS بحسب `PRODUCTION_HOSTING_PROFILE`.

### تنفيذ سكربت VPS يدويًا

Linux:

```bash
chmod +x deploy/deploy.sh
./deploy/deploy.sh /var/www/alquran
```

Windows Server:

```powershell
powershell -ExecutionPolicy Bypass -File deploy/deploy.ps1 -ProjectDirectory "D:\sites\alquran"
```

وللتنفيذ اليدوي أو لفهم الخطوات التي تؤتمتها السكربتات:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
npm ci
npm run build
php artisan migrate --force
php artisan storage:link
php artisan optimize
php artisan system:production-check
php artisan queue:restart
php artisan reverb:restart
```

اجعل `storage/` و`bootstrap/cache/` قابلين للكتابة بواسطة مستخدم PHP فقط. لا تستخدم `migrate:fresh` أو التراجع الجماعي في الإنتاج. عند فشل إصدار، أعد الكود السابق مع إبقاء ترحيلات قاعدة البيانات الأمامية، أو استعد لقطة قاعدة البيانات بعد قرار صريح ومدروس.

## عامل الطابور

شغّل العامل تحت Supervisor أو مدير عمليات مكافئ. إعداد نموذجي:

```ini
[program:alquran-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/alquran/artisan queue:work database --queue=default --sleep=3 --tries=2 --timeout=900 --max-time=3600
directory=/var/www/alquran
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/supervisor/alquran-worker.log
stopwaitsecs=960
```

قيمة `--timeout=900` أقل من `DB_QUEUE_RETRY_AFTER=960` لمنع تشغيل نسخة ثانية من مهمة التصدير قبل انتهاء الأولى. راقب `failed_jobs` وسجل التطبيق، ولا تستخدم `queue:retry all` قبل فهم سبب الفشل.

## Laravel Reverb

شغّل Reverb كعملية مستقلة خلف reverse proxy يدعم WebSocket وTLS. لا تعرض منفذ العملية الداخلي مباشرة للإنترنت:

```ini
[program:alquran-reverb]
command=php /var/www/alquran/artisan reverb:start --host=127.0.0.1 --port=8080
directory=/var/www/alquran
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=/var/log/supervisor/alquran-reverb.log
stopwaitsecs=20
```

عيّن `REVERB_APP_ID` و`REVERB_APP_KEY` و`REVERB_APP_SECRET` بقيم عشوائية، واجعل `REVERB_ALLOWED_ORIGINS` نطاق التطبيق فقط. تعبر مصادقة القنوات عبر جلسة المستخدم، ولا تسمح قناة `App.Models.User.{id}` إلا لصاحبها النشط والمستوفي لسياسة 2FA.

قيّد `CORS_ALLOWED_ORIGINS` إلى `APP_URL` أو قائمة PWA الموثوقة فقط؛ لا تستخدم `*` مع API الإنتاج.

تبقى جميع الإشعارات محفوظة في قاعدة البيانات ويستمر مركز الإشعارات في polling كل 30 ثانية؛ لذلك لا يؤدي تعطل WebSocket إلى فقد إشعار أو توقف العمل.

## المجدول

أضف مهمة Cron واحدة فقط:

```cron
* * * * * cd /var/www/alquran && php artisan schedule:run >> /dev/null 2>&1
```

المجدول ينفّذ تحديث التحليلات، تنظيف رموز Sanctum والمهام الفاشلة والدفعات القديمة، ويراقب تراكم الطابور. جميع المهام الحساسة تستخدم قفلًا لمنع التداخل والتشغيل المكرر بين الخوادم.

## النسخ الاحتياطي والاستعادة

الحد الأدنى المعتمد:

- نسخة MySQL كاملة ومتسقة كل ليلة عبر خدمة النسخ المدارة أو `mysqldump --single-transaction`، مع تشفير أثناء النقل والتخزين.
- نسخ `storage/app/private` و`storage/app/public` ونسخة مشفرة من أسرار الإنتاج، وبالأخص `APP_KEY`، في نفس نافذة النسخ.
- احتفاظ 30 نسخة يومية و12 نسخة شهرية، ونسخة خارج الخادم/الحساب التشغيلي.
- فحص checksum والتنبيه عند فشل أي جزء من النسخ.
- تجربة استعادة ربع سنوية إلى بيئة معزولة: قاعدة البيانات أولًا، ثم الملفات، ثم الأسرار؛ بعدها `php artisan optimize` وفحص `/up` وتسجيل الدخول وتنزيل ملف خاص وتصدير تقرير.

بعد أن تنشئ أداة MySQL أو منصة النسخ الملفات الفعلية، أنشئ manifest يحتوي الحجم وSHA-256 لكل ملف:

```bash
php artisan system:backup-manifest backups/database.sql backups/private-storage.tar backups/public-storage.tar --output=backups/manifest.json
```

وقبل أي استعادة تحقّق من عدم فقد أو تعديل أي ملف:

```bash
php artisan system:backup-verify backups/manifest.json --directory=backups
```

يعيد أمر التحقق رمز خروج غير صفري عند اختلاف الحجم أو checksum. لا ينشئ التطبيق نسخة MySQL بنفسه؛ تبقى بيانات اعتماد النسخ وعملية التفريغ لدى خدمة النسخ المدارة أو أداة MySQL الرسمية.

الاستعادة لا تُنفّذ فوق الإنتاج مباشرة. أنشئ قاعدة ومجلدات معزولة، تحقّق من أحدث نقطة سليمة ومن تطابق الملفات مع سجلات `private_files`، ثم نفّذ التحويل بعد موافقة تشغيلية.

## المراقبة والسجلات

- افحص `GET /up` كـliveness و`GET /ready` كـreadiness من موازن الحمل كل دقيقة.
- راقب HTTP 5xx، زمن الاستجابة، مساحة القرص، اتصالات MySQL، `failed_jobs`، وحجم طابور `database:default`.
- يسجل النظام تجاوز ميزانية استعلامات قاعدة البيانات المحددة بـ`DB_QUERY_BUDGET_MS` دون تسجيل SQL أو معاملات قد تحتوي بيانات حساسة.
- استخدم تدوير السجلات واحتفاظًا مناسبًا للسياسة المحلية. لا ترسل ملفات الهوية أو محتوى التقارير إلى سجلات أو أدوات تتبع خارجية.
- راقب عملية Reverb وعدد اتصالات WebSocket وفشل مصادقة القنوات. اكتمال التصدير والتنبيهات الحرجة يبثان فوريًا مع fallback مخزن في قاعدة البيانات.

## فحص ما قبل الفتح

- [ ] `APP_ENV=production` و`APP_DEBUG=false` وHTTPS مفعّل.
- [ ] أسرار قوية، مستخدم DB محدود، وحسابات العرض غير موجودة.
- [ ] `php artisan migrate:status` لا يعرض ترحيلات معلّقة.
- [ ] `php artisan optimize` و`php artisan schedule:list` و`php artisan system:production-check` ناجحة.
- [ ] Supervisor والعامل والمجدول يعملون، ولا توجد مهام فاشلة غير مفهومة.
- [ ] Reverb يعمل عبر WSS، والنطاق المسموح مقيد، وتم اختبار fallback عند إيقافه.
- [ ] النسخة الاحتياطية الأولى مكتملة وتم اختبار الاستعادة.
- [ ] `/up` و`/ready` وتسجيل الدخول ومصفوفة الأدوار والملفات الخاصة والتصدير والبريد فُحصت على النطاق الفعلي.
- [ ] `composer audit` و`npm audit --omit=dev` و`php artisan test` ناجحة على الإصدار نفسه.
