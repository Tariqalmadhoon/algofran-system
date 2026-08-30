@props(['inline' => false, 'consume' => false])

@php
    $statusLabels = [
        'profile-updated' => 'تم حفظ بيانات الملف الشخصي بنجاح.',
        'password-updated' => 'تم تغيير كلمة المرور وإلغاء رموز الدخول القديمة.',
        'two-factor-setup-started' => 'تم بدء إعداد المصادقة الثنائية.',
        'two-factor-enabled' => 'تم تفعيل المصادقة الثنائية بنجاح.',
        'recovery-codes-regenerated' => 'تم إنشاء رموز استعادة جديدة.',
        'two-factor-disabled' => 'تم تعطيل المصادقة الثنائية.',
        'other-sessions-revoked' => 'تم إنهاء جميع الجلسات الأخرى.',
    ];
    $feedbackMessages = collect([
        'success' => session('success'),
        'error' => session('error'),
        'warning' => session('warning'),
        'info' => session('info'),
        'status' => session('status'),
    ])->filter(fn ($message) => filled($message))->map(function ($message, $key) use ($statusLabels) {
        return [
            'type' => $key === 'status' ? 'success' : $key,
            'message' => $key === 'status' ? ($statusLabels[$message] ?? $message) : $message,
        ];
    })->values();

    if ($consume) {
        session()->forget(['success', 'error', 'warning', 'info', 'status']);
    }
@endphp

@if($feedbackMessages->isNotEmpty())
    <div @class([
        'space-y-3',
        'mb-6' => $inline,
        'fixed left-4 top-24 z-[85] w-[min(25rem,calc(100vw-2rem))]' => ! $inline,
    ]) data-feedback-stack>
        @foreach($feedbackMessages as $feedback)
            <x-feedback-alert :type="$feedback['type']" :message="$feedback['message']" :duration="$feedback['type'] === 'error' ? 9000 : 6200" />
        @endforeach
    </div>
@endif
