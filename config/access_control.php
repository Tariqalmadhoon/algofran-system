<?php

return [
    'roles' => [
        'super-admin' => [
            'label' => 'مدير النظام',
            'description' => 'تحكم كامل في النظام والحسابات والصلاحيات.',
            'tone' => 'violet',
        ],
        'center-manager' => [
            'label' => 'مدير مركز',
            'description' => 'إدارة المركز والحلقات والمتابعة التشغيلية.',
            'tone' => 'emerald',
        ],
        'teacher' => [
            'label' => 'محفّظ',
            'description' => 'الطلاب والتسجيل اليومي والتنبيهات والتصدير.',
            'tone' => 'teal',
        ],
        'academic-supervisor' => [
            'label' => 'مشرف أكاديمي',
            'description' => 'الإشراف على الأداء والبرامج والتقارير.',
            'tone' => 'sky',
        ],
        'registrar' => [
            'label' => 'مسجل',
            'description' => 'إدارة ملفات الطلاب وبيانات التسجيل.',
            'tone' => 'amber',
        ],
        'website-editor' => [
            'label' => 'محرر الموقع',
            'description' => 'إدارة محتوى الموقع العام فقط.',
            'tone' => 'rose',
        ],
        'report-viewer' => [
            'label' => 'عارض تقارير',
            'description' => 'استعراض التقارير وتصديرها دون تعديل البيانات.',
            'tone' => 'slate',
        ],
    ],

    'permission_groups' => [
        'الطلاب والحلقات' => [
            'students.view' => 'عرض الطلاب',
            'students.create' => 'إضافة الطلاب',
            'students.update' => 'تعديل الطلاب',
            'students.archive' => 'أرشفة الطلاب',
            'students.export' => 'تصدير الطلاب',
            'halaqas.view' => 'عرض الحلقات',
            'halaqas.manage' => 'إدارة الحلقات والإسناد',
        ],
        'التسميع والمتابعة' => [
            'recitations.view' => 'عرض سجلات التسميع',
            'recitations.create' => 'تسجيل التسميع اليومي',
            'recitations.update' => 'تعديل سجلات التسميع',
            'recitations.export' => 'تصدير سجلات الحفظ',
            'attendance.manage' => 'إدارة الحضور',
            'alerts.view' => 'عرض التنبيهات',
            'alerts.manage' => 'معالجة التنبيهات',
        ],
        'الأكاديمي والتقارير' => [
            'courses.manage' => 'إدارة البرامج والدورات',
            'certificates.manage' => 'إدارة الشهادات',
            'achievements.manage' => 'إدارة الإنجازات',
            'reports.view' => 'عرض التقارير',
            'reports.export' => 'تصدير التقارير',
            'reports.upload' => 'رفع التقارير',
            'calendar.view' => 'عرض التقويم',
            'calendar.manage' => 'إدارة التقويم',
        ],
        'النظام والمحتوى' => [
            'organization.view' => 'عرض الهيكلية',
            'organization.manage' => 'إدارة الهيكلية',
            'notifications.view' => 'عرض الإشعارات',
            'website.manage' => 'إدارة الموقع العام',
            'settings.manage' => 'إدارة إعدادات النظام',
            'audit.view' => 'عرض سجل التدقيق',
            'guardian.private-data.view' => 'عرض بيانات ولي الأمر الخاصة',
            'private-files.view' => 'عرض الملفات الخاصة',
        ],
    ],
];
