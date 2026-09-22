<div class="mx-auto max-w-6xl space-y-6">
    <section class="relative overflow-hidden rounded-[2rem] bg-gradient-to-bl from-emerald-950 via-emerald-900 to-teal-700 px-6 py-8 text-white shadow-[0_28px_70px_-36px_rgba(6,78,59,.85)] sm:px-8">
        <div class="absolute -left-16 -top-20 size-64 rounded-full bg-emerald-300/15 blur-3xl" aria-hidden="true"></div>
        <div class="relative flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-2xl">
                <p class="text-xs font-black tracking-widest text-emerald-200">بوابة الإدارة الآمنة</p>
                <h1 class="mt-3 text-3xl font-black sm:text-4xl">توزيع تطبيق المحفّظ</h1>
                <p class="mt-3 text-sm leading-7 text-emerald-50/80">انشر الإصدار الموقّع من هنا مرة واحدة. يبقى APK خاصًا بالمركز، ويظهر زر التنزيل فقط في لوحة المحفّظ والحسابات الإدارية المصرح لها.</p>
            </div>
            <a href="{{ $teacherAppUrl }}" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-white px-5 py-3 text-sm font-black text-emerald-950 shadow-lg shadow-emerald-950/20 transition hover:-translate-y-0.5">
                فتح بوابة المحفّظ
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </a>
        </div>
    </section>

    <x-flash-messages inline consume />

    <section class="grid gap-6 lg:grid-cols-[1.1fr_.9fr]">
        <article class="panel">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="eyebrow">حالة النشر</p>
                    @if($release)
                        <h2 class="section-title mt-1">الإصدار الرسمي متاح</h2>
                        <p class="mt-2 text-sm leading-7 text-slate-600">الإصدار <span dir="ltr" class="font-black">{{ $release['version_name'] }}</span> · بناء <span dir="ltr" class="font-black">{{ $release['version_code'] }}</span> · {{ number_format($release['size_bytes'] / 1024 / 1024, 1) }} MB</p>
                    @else
                        <h2 class="section-title mt-1">لا يوجد إصدار منشور بعد</h2>
                        <p class="mt-2 text-sm leading-7 text-slate-600">ارفع حزمة الإصدار الثلاثية أدناه. لن يظهر ملف جزئي أو غير موثّق للمحفظين.</p>
                    @endif
                </div>
                <span class="inline-flex shrink-0 items-center gap-2 rounded-full px-3 py-1.5 text-xs font-black {{ $release ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}"><span class="size-2 rounded-full {{ $release ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>{{ $release ? 'متاح' : 'قيد التجهيز' }}</span>
            </div>

            @if($release)
                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ $downloadUrl }}" download class="btn-primary">تنزيل الإصدار الحالي</a>
                    <a href="{{ $teacherAppUrl }}" class="btn-secondary">فتح بوابة المحفّظ</a>
                </div>
                <details class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-xs text-slate-600">
                    <summary class="cursor-pointer font-bold text-slate-800">بصمة التحقق SHA-256</summary>
                    <code dir="ltr" class="mt-3 block break-all leading-6">{{ $release['sha256'] }}</code>
                </details>
            @endif
        </article>

        <aside class="panel border-emerald-100 bg-emerald-50/40">
            <p class="eyebrow">التحديث داخل التطبيق</p>
            <ol class="mt-4 space-y-4 text-sm leading-7 text-slate-700">
                <li class="flex gap-3"><span class="grid size-7 shrink-0 place-items-center rounded-lg bg-emerald-700 text-xs font-black text-white">1</span><span>ابنِ نسخة Android موقّعة بنفس مفتاح الإصدار وارفع رقم البناء.</span></li>
                <li class="flex gap-3"><span class="grid size-7 shrink-0 place-items-center rounded-lg bg-emerald-700 text-xs font-black text-white">2</span><span>ارفع ملفات <span dir="ltr" class="font-semibold">APK</span> و<span dir="ltr" class="font-semibold">.sha256</span> و<span dir="ltr" class="font-semibold">.json</span> معًا من النموذج.</span></li>
                <li class="flex gap-3"><span class="grid size-7 shrink-0 place-items-center rounded-lg bg-emerald-700 text-xs font-black text-white">3</span><span>يفحص التطبيق الإصدار عند الاتصال ويعرض التحديث للمحفظ. Android يطلب تأكيد التثبيت من المستخدم.</span></li>
            </ol>
        </aside>
    </section>

    <section class="panel">
        <div class="max-w-3xl">
            <p class="eyebrow">نشر إصدار جديد</p>
            <h2 class="section-title mt-1">ارفع الحزمة الموثّقة</h2>
            <p class="mt-2 text-sm leading-7 text-slate-600">الحد الأعلى للحزمة {{ $maxUploadMegabytes }} MB. يجب أن تتطابق بيانات JSON والبصمة مع ملف APK، وإلا لن يُنشر الإصدار.</p>
        </div>
        <form
            wire:submit="publish"
            class="mt-6 space-y-5"
            enctype="multipart/form-data"
            x-data="{
                apkUploading: false,
                apkFailed: false,
                apkReady: false,
                apkProgress: 0,
                apkSize: 0,
                apkStageError: '',
                chunkSize: 1024 * 1024,
                uploadUrl: @js(route('mobile.distribution.apk-chunks')),
                async selectApk(event) {
                    const file = event.target.files.length ? event.target.files[0] : null;
                    this.apkSize = file?.size || 0;
                    this.apkFailed = false;
                    this.apkReady = false;
                    this.apkProgress = 0;
                    this.apkStageError = '';
                    this.$wire.set('apkUploadToken', '');

                    if (! file) return;

                    if (file.size > 512 * 1024 * 1024) {
                        this.apkFailed = true;
                        this.apkStageError = 'حجم APK يتجاوز الحد الأعلى المسموح به (512 MB).';
                        return;
                    }

                    this.apkUploading = true;
                    const uploadId = window.crypto?.randomUUID ? window.crypto.randomUUID() : `${Date.now()}-${Math.random()}`;
                    const total = Math.ceil(file.size / this.chunkSize);

                    try {
                        for (let index = 0; index < total; index++) {
                            const start = index * this.chunkSize;
                            const chunk = file.slice(start, Math.min(file.size, start + this.chunkSize), 'application/octet-stream');
                            const response = await this.sendChunk(file, chunk, uploadId, index, total);

                            this.apkProgress = Math.round(((index + 1) / total) * 100);

                            if (index === total - 1) {
                                if (! response.upload_token) throw new Error('لم يؤكد الخادم اكتمال ملف APK.');
                                this.$wire.set('apkUploadToken', response.upload_token);
                            }
                        }

                        this.apkReady = true;
                    } catch (error) {
                        this.apkFailed = true;
                        this.apkStageError = error?.message || 'تعذّر رفع أحد أجزاء APK.';
                    } finally {
                        this.apkUploading = false;
                    }
                },
                sendChunk(file, chunk, uploadId, index, total) {
                    return new Promise((resolve, reject) => {
                        const request = new XMLHttpRequest();
                        const data = new FormData();
                        data.append('upload_id', uploadId);
                        data.append('index', index);
                        data.append('total', total);
                        data.append('total_size', file.size);
                        data.append('chunk', chunk, `release-part-${index + 1}.bin`);

                        request.open('POST', this.uploadUrl, true);
                        request.timeout = 180000;
                        request.setRequestHeader('Accept', 'application/json');
                        request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                        const csrf = document.querySelector('meta[name=csrf-token]')?.getAttribute('content');
                        if (csrf) request.setRequestHeader('X-CSRF-TOKEN', csrf);

                        request.upload.onprogress = (event) => {
                            if (! event.lengthComputable) return;
                            this.apkProgress = Math.round(((index + (event.loaded / event.total)) / total) * 100);
                        };
                        request.onload = () => {
                            let response = {};
                            try { response = JSON.parse(request.responseText || '{}'); } catch (_) {}

                            if (request.status >= 200 && request.status < 300) return resolve(response);
                            reject(new Error(response.message || response.errors?.chunk?.[0] || `رفض الخادم جزء الرفع رقم ${index + 1}.`));
                        };
                        request.onerror = () => reject(new Error(`تعذر الاتصال بالخادم أثناء الجزء ${index + 1} من ${total}.`));
                        request.ontimeout = () => reject(new Error(`انتهت مهلة الجزء ${index + 1} من ${total}.`));
                        request.send(data);
                    });
                },
                sizeLabel() { return this.apkSize ? (this.apkSize / 1024 / 1024).toFixed(1) + ' MB' : '' }
            }"
        >
            <div class="grid gap-4 md:grid-cols-3">
                <label><span class="form-label">رقم الإصدار</span><input wire:model="version" class="form-input" dir="ltr" placeholder="1.3.2"><small class="mt-1 block text-xs text-slate-500">صيغة: 1.3.2</small><x-input-error :messages="$errors->get('version')" /></label>
                <label><span class="form-label">رقم البناء</span><input wire:model="versionCode" type="number" min="1" class="form-input" dir="ltr"><x-input-error :messages="$errors->get('versionCode')" /></label>
                <label><span class="form-label">أقل بناء مدعوم</span><input wire:model="minimumVersionCode" type="number" min="1" class="form-input" dir="ltr"><x-input-error :messages="$errors->get('minimumVersionCode')" /></label>
            </div>
            <div class="grid gap-4 md:grid-cols-3">
                <label>
                    <span class="form-label">ملف التطبيق APK</span>
                    <input x-on:change="selectApk($event)" x-bind:disabled="apkUploading" type="file" accept=".apk,application/vnd.android.package-archive" class="form-input">
                    <div x-cloak x-show="apkUploading" class="mt-3 rounded-xl border border-emerald-200 bg-emerald-50 p-3" role="status" aria-live="polite">
                        <div class="flex items-center justify-between gap-3 text-xs font-black text-emerald-900"><span>يجري رفع APK على دفعات آمنة</span><span dir="ltr" x-text="apkProgress + '%'"></span></div>
                        <div class="mt-2 h-2 overflow-hidden rounded-full bg-emerald-100"><div class="h-full rounded-full bg-gradient-to-l from-emerald-500 to-teal-400 transition-[width] duration-200" :style="'width: ' + apkProgress + '%'" role="progressbar" aria-valuemin="0" aria-valuemax="100" :aria-valuenow="apkProgress"></div></div>
                        <p class="mt-2 text-xs text-emerald-800/80"><span x-text="sizeLabel()"></span><span x-show="apkProgress < 100"> · لا تغلق الصفحة حتى يصل العداد إلى 100%.</span></p>
                    </div>
                    <div x-cloak x-show="apkReady" class="mt-3 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-xs font-bold text-emerald-800">اكتمل نقل APK بأمان. يمكنك الآن التحقق من الإصدار ونشره.</div>
                    <div x-cloak x-show="apkFailed" class="mt-3 rounded-xl border border-rose-200 bg-rose-50 p-3 text-xs leading-6 text-rose-800" role="alert" x-text="apkStageError"></div>
                    <x-input-error :messages="$errors->get('apkUploadToken')" />
                </label>
                <label><span class="form-label">ملف البصمة SHA-256</span><input wire:model="checksum" type="file" accept=".sha256,text/plain" class="form-input"><x-input-error :messages="$errors->get('checksum')" /></label>
                <label><span class="form-label">ملف بيانات الإصدار JSON</span><input wire:model="manifest" type="file" accept="application/json,.json" class="form-input"><x-input-error :messages="$errors->get('manifest')" /></label>
            </div>
            <label class="block"><span class="form-label">ملاحظات التحديث للمحفظين</span><textarea wire:model="releaseNotes" rows="3" class="form-input" placeholder="تحسينات المزامنة وسهولة تسجيل الحفظ."></textarea><x-input-error :messages="$errors->get('releaseNotes')" /></label>
            <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="publish,checksum,manifest" x-bind:disabled="apkUploading"><span wire:loading.remove wire:target="publish">تحقق وانشر الإصدار</span><span wire:loading wire:target="publish">يجري التحقق والنشر…</span></button>
        </form>
    </section>
</div>
