<x-app-shell title="الملف الشخصي">
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
        <p class="eyebrow">الحساب</p>
        <h1 class="page-title">الملف الشخصي والأمان</h1>
        <p class="page-subtitle">حدّث بياناتك وكلمة المرور من مكان واحد.</p>
        </div>
        <a class="btn-secondary" href="{{ route('profile.security') }}">أمان الحساب والأجهزة</a>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="panel">
            <h2 class="section-title">البيانات الأساسية</h2>
            @if (session('status') === 'profile-updated')
                <p class="mt-3 rounded-xl bg-emerald-50 p-3 text-sm text-emerald-800">تم حفظ البيانات.</p>
            @endif
            <form method="POST" action="{{ route('profile.update') }}" class="mt-6 space-y-4">
                @csrf @method('PATCH')
                <div>
                    <label class="form-label" for="name">الاسم</label>
                    <input class="form-input" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                    <x-input-error :messages="$errors->get('name')" />
                </div>
                <div>
                    <label class="form-label" for="email">البريد الإلكتروني</label>
                    <input class="form-input" id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required>
                    <x-input-error :messages="$errors->get('email')" />
                </div>
                <div>
                    <label class="form-label" for="phone">رقم التواصل</label>
                    <input class="form-input" id="phone" name="phone" value="{{ old('phone', $user->phone) }}" dir="ltr">
                    <x-input-error :messages="$errors->get('phone')" />
                </div>
                <button class="btn-primary" type="submit">حفظ البيانات</button>
            </form>
        </section>

        <section class="panel">
            <h2 class="section-title">تغيير كلمة المرور</h2>
            <p class="mt-2 text-sm text-slate-500">استخدم 10 رموز على الأقل، مع حروف كبيرة وصغيرة ورقم ورمز خاص.</p>
            @if (session('status') === 'password-updated')
                <p class="mt-3 rounded-xl bg-emerald-50 p-3 text-sm text-emerald-800">تم تغيير كلمة المرور.</p>
            @endif
            <form method="POST" action="{{ route('profile.password') }}" class="mt-6 space-y-4">
                @csrf @method('PUT')
                <div>
                    <label class="form-label" for="current_password">كلمة المرور الحالية</label>
                    <input class="form-input" id="current_password" name="current_password" type="password" autocomplete="current-password" required>
                    <x-input-error :messages="$errors->updatePassword->get('current_password')" />
                </div>
                <div>
                    <label class="form-label" for="new_password">كلمة المرور الجديدة</label>
                    <input class="form-input" id="new_password" name="password" type="password" autocomplete="new-password" required>
                    <x-input-error :messages="$errors->updatePassword->get('password')" />
                </div>
                <div>
                    <label class="form-label" for="password_confirmation">تأكيد كلمة المرور</label>
                    <input class="form-input" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
                </div>
                <button class="btn-primary" type="submit">تغيير كلمة المرور</button>
            </form>
        </section>
    </div>
</x-app-shell>
