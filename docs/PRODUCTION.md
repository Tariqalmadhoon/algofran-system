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
APP_URL=https://your-domain.example
SESSION_SECURE_COOKIE=true
LOG_CHANNEL=daily
LOG_LEVEL=warning
QUEUE_CONNECTION=database
DB_QUEUE_RETRY_AFTER=960
SANCTUM_EXPIRATION=43200
BROADCAST_CONNECTION=reverb
```

أنشئ `APP_KEY` مرة واحدة واحفظه في مدير الأسرار والنسخة الاحتياطية الآمنة. لا تغيّره بعد وجود بيانات أو جلسات مشفرة. اترك متغيرات `INITIAL_ADMIN_*` فارغة بعد إنشاء المدير الأول، ولا تشغّل `DemoDataSeeder` في الإنتاج.

## بوابة جاهزية الإنتاج

شغّل الفحص الآلي قبل تحويل حركة المستخدمين إلى الإصدار:

```bash
php artisan system:production-check
```

يفشل الأمر برمز خروج غير صفري إذا كانت البيئة غير آمنة أو قاعدة البيانات غير متاحة أو توجد ترحيلات معلّقة أو إعدادات تشغيل ناقصة. لا يطبع الأمر قيم الأسرار. للاستهلاك من CI يمكن استخدام `--json`.

عند ترقية نظام قائم إلى PHASE 8 لأول مرة: طبّق الترحيل، افتح التطبيق مؤقتًا للمشرفين فقط، وأكمل 2FA لكل حساب حساس، ثم أعد تشغيل بوابة الإنتاج قبل إعادة حركة المستخدمين. يفشل الفحص ما دام هناك حساب حساس بلا 2FA مؤكدة.

يفحص موازن الحمل نقطتين منفصلتين:

- `GET /up` لفحص أن عملية PHP والتطبيق يعملان.
- `GET /ready` لفحص قاعدة البيانات والكاش ومسارات الكتابة؛ يعيد `200` عند الجاهزية و`503` عند تعذرها، دون كشف تفاصيل البنية الداخلية.

## النشر

استخدم سكربت النشر المناسب من نسخة إصدار موثوقة. السكربتان يبنيان الأصول، يفعّلان وضع الصيانة أثناء الترحيل، يحدّثان الكاش، يشغّلان بوابة الإنتاج، يعيدان تشغيل عمال الطابور، ويضمنان الخروج من وضع الصيانة عند الفشل.

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
