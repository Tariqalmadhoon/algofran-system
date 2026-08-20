# API v1

المسار الأساسي: `/api/v1`

تستخدم الواجهة Laravel Sanctum Bearer Tokens، وجميع الاستجابات بصيغة JSON. تُطبّق صلاحيات المستخدم ونطاق رؤية الطلاب نفسه المستخدم في لوحة النظام.

كل استجابة تحمل الترويسة `X-API-Version: 1`. يعرض `GET /api/v1/meta` حالة العقد وأدنى إصدار عميل مدعوم واتجاه الواجهة دون حاجة إلى مصادقة.

## المصادقة

### تسجيل الدخول

`POST /api/v1/auth/login`

```json
{
  "email": "user@example.com",
  "password": "secret",
  "device_name": "Android phone"
}
```

إذا لم يكن 2FA مفعّلًا للحساب غير الحساس، يعود الرمز مباشرة في `data.token` ووقت انتهائه في `data.expires_at`. المدة الافتراضية 30 يومًا، وإعادة الدخول باسم الجهاز نفسه تلغي رمز ذلك الجهاز السابق. أرسله بعد ذلك:

```http
Authorization: Bearer TOKEN
Accept: application/json
```

### تحدي المصادقة الثنائية

الحسابات ذات 2FA لا تحصل على Bearer token بعد كلمة المرور مباشرة. تعيد عملية الدخول HTTP `202` مع challenge token عشوائي صالح لخمس دقائق:

```json
{
  "data": {
    "two_factor_required": true,
    "challenge_token": "SHORT_LIVED_SECRET",
    "expires_in": 300
  }
}
```

أكمل التحدي عبر `POST /api/v1/auth/two-factor-challenge` بإرسال `challenge_token` ومعه إما `code` من تطبيق TOTP أو `recovery_code`. التحدي أحادي الاستخدام، ويُلغى بعد خمس محاولات فاشلة. رمز الاستعادة المستخدم يُستبدل تلقائيًا.

الأدوار الإدارية والحساسة التي لم تُعد 2FA تعيد `403 two_factor_setup_required`، ويجب إكمال الإعداد من بوابة الويب أولًا.

### المستخدم والخروج

- `GET /api/v1/user`
- `POST /api/v1/auth/logout` — يلغي رمز الجهاز الحالي فقط.

كل رمز يصدر بقدرة `mobile:read`. إضافة إلى القدرة، يفحص كل مسار صلاحية المستخدم الوظيفية؛ فمثلًا موارد الطلاب تتطلب `students.view` والتنبيهات تتطلب `alerts.view`. تغيير كلمة المرور أو تعطيل الحساب يسحب الرموز القديمة.

## الموارد

- `GET /api/v1/halaqas`
- `GET /api/v1/students`
- `GET /api/v1/students/{id}`
- `GET /api/v1/students/{id}/progress`
- `GET /api/v1/students/{id}/daily-records`
- `GET /api/v1/daily-records`
- `GET /api/v1/recitations`
- `GET /api/v1/attendance`
- `GET /api/v1/calendar`
- `GET /api/v1/alerts`
- `GET /api/v1/courses`
- `GET /api/v1/certificates`

## المرشحات والترقيم

- `per_page`: من 1 إلى 100، والافتراضي 15.
- الطلاب: `search`, `halaqa_id`, `status`.
- الحلقات: `search`, `active`.
- السجلات والحضور: `from`, `to`, `student_id`, `halaqa_id`.
- التسميع: `type`, `student_id`.
- التقويم: `from`, `to`, `type`, `halaqa_id`، والحد الأقصى للفترة سنة.
- التنبيهات: `status`, `severity`, `student_id`.
- الدورات: `search`, `status`.

النتائج المرقمة تعود بالمفاتيح `data`, `links`, `meta`.

## صيغة الأخطاء

```json
{
  "message": "لا تملك صلاحية الوصول.",
  "error": {
    "code": "forbidden"
  }
}
```

الرموز الأساسية: `unauthenticated`, `account_inactive`, `forbidden`, `not_found`, `validation_failed`, `invalid_credentials`, `two_factor_setup_required`, `invalid_two_factor_challenge`.

عند فشل التحقق، تظهر الحقول داخل `error.fields`. القيم غير المعروفة للحالات والأنواع والتواريخ غير الصالحة تعيد 422، والنطاق الزمني للتقويم لا يتجاوز سنة.

تخضع API لمحدد طلبات افتراضي مقداره 60 طلبًا في الدقيقة لكل مستخدم أو عنوان IP.
