<x-public-shell title="تواصل معنا" description="تواصل مباشرة مع مركز الغفران لتحفيظ القرآن الكريم عبر واتساب، أو زر موقع المركز على الخريطة.">
    <section class="public-contact-hero relative isolate overflow-hidden bg-emerald-950 px-4 py-16 text-white sm:py-24">
        <div class="absolute inset-0 -z-10 opacity-30" style="background-image:radial-gradient(circle at 16% 20%,rgba(110,231,183,.45),transparent 18rem),radial-gradient(circle at 84% 82%,rgba(212,168,66,.34),transparent 23rem)"></div>
        <div class="absolute inset-0 -z-10 opacity-[.08]" style="background-image:linear-gradient(30deg,#fff 12%,transparent 12.5%,transparent 87%,#fff 87.5%,#fff),linear-gradient(150deg,#fff 12%,transparent 12.5%,transparent 87%,#fff 87.5%,#fff);background-size:42px 72px"></div>
        <div class="mx-auto max-w-5xl text-center" data-reveal>
            <span class="inline-flex items-center gap-2 rounded-full border border-emerald-300/30 bg-emerald-300/10 px-4 py-2 text-xs font-black text-emerald-100"><span class="size-2 animate-pulse rounded-full bg-emerald-300"></span> قناة التواصل المعتمدة</span>
            <h1 class="mt-5 text-4xl font-black tracking-tight sm:text-5xl">نتواصل معكم عبر واتساب</h1>
            <p class="mx-auto mt-5 max-w-2xl text-base leading-8 text-emerald-50/75">للاستفسار عن التسجيل أو البرامج أو مواعيد المركز، أرسل رسالتك مباشرة إلى فريق المركز. لا نطلب منك تعبئة نموذج طويل ولا نعرض بريدًا عامًا للتواصل.</p>
            <a href="{{ $contact['whatsapp_url'] }}" target="_blank" rel="noopener noreferrer" data-no-navigation-feedback class="public-whatsapp-cta mt-8 inline-flex min-h-14 items-center justify-center gap-3 rounded-2xl bg-[#25D366] px-6 py-3 text-base font-black text-emerald-950 shadow-[0_18px_46px_-18px_rgba(37,211,102,.75)] transition hover:-translate-y-1 hover:bg-[#52e58b]">
                <svg class="size-6" viewBox="0 0 32 32" fill="currentColor" aria-hidden="true"><path d="M16 2.7a13.1 13.1 0 0 0-11.2 19.8L3 29.3l7-1.8A13.15 13.15 0 1 0 16 2.7Zm0 23.9a10.75 10.75 0 0 1-5.49-1.5l-.4-.24-4.15 1.08 1.1-4.04-.27-.42A10.78 10.78 0 1 1 16 26.6Zm5.91-8.08c-.32-.16-1.86-.92-2.15-1.02-.29-.11-.5-.16-.71.16-.21.31-.81 1.02-.99 1.23-.18.21-.37.24-.69.08a8.77 8.77 0 0 1-2.58-1.6 9.65 9.65 0 0 1-1.79-2.23c-.18-.31-.02-.49.14-.64.14-.14.32-.37.48-.55.16-.19.21-.32.32-.53.1-.21.05-.39-.03-.55-.08-.16-.71-1.71-.97-2.34-.26-.62-.52-.54-.71-.55l-.61-.01c-.21 0-.55.08-.84.39-.29.32-1.1 1.08-1.1 2.63s1.13 3.05 1.29 3.26c.16.21 2.22 3.39 5.37 4.75.75.33 1.34.52 1.8.67.76.24 1.46.21 2.01.13.61-.09 1.86-.76 2.12-1.5.26-.74.26-1.37.18-1.5-.07-.13-.28-.21-.6-.37Z"/></svg>
                ابدأ محادثة الآن
            </a>
        </div>
    </section>

    <section class="mx-auto grid max-w-7xl gap-6 px-4 py-14 sm:px-6 lg:grid-cols-[.88fr_1.12fr] lg:px-8 lg:py-20">
        <article data-reveal="scale" class="public-contact-qr-card relative overflow-hidden rounded-[2rem] border border-emerald-100 bg-white p-6 shadow-[0_28px_70px_-46px_rgba(6,78,59,.75)] sm:p-8">
            <div class="absolute -left-14 -top-12 size-40 rounded-full bg-emerald-100/70 blur-2xl" aria-hidden="true"></div>
            <div class="relative">
                <p class="eyebrow">مسح فوري</p>
                <h2 class="section-title mt-1">افتح واتساب بكاميرا هاتفك</h2>
                <p class="mt-3 text-sm leading-7 text-slate-600">امسح الرمز ثم أرسل رسالتك. يفتح الرمز المحادثة الرسمية للمركز ويحتوي رسالة افتتاحية جاهزة.</p>
                <a href="{{ $contact['whatsapp_url'] }}" target="_blank" rel="noopener noreferrer" data-no-navigation-feedback class="whatsapp-qr mx-auto mt-7 block w-fit rounded-3xl border border-emerald-100 bg-white p-4 shadow-xl shadow-emerald-950/10 transition hover:-translate-y-1 hover:shadow-2xl hover:shadow-emerald-950/15" aria-label="فتح محادثة واتساب مباشرة">
                    {!! $contact['qr_svg'] !!}
                </a>
                <a href="{{ $contact['whatsapp_url'] }}" target="_blank" rel="noopener noreferrer" data-no-navigation-feedback class="mt-6 flex items-center justify-between gap-3 rounded-2xl border border-emerald-100 bg-emerald-50/70 p-4 text-emerald-950 transition hover:border-emerald-300 hover:bg-emerald-100/70">
                    <span><span class="block text-xs font-bold text-emerald-700">رقم واتساب الرسمي</span><span dir="ltr" class="mt-1 block text-base font-black">{{ $contact['whatsapp_display_number'] }}</span></span>
                    <span class="grid size-10 place-items-center rounded-xl bg-emerald-700 text-white" aria-hidden="true"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14m-6-6 6 6-6 6" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                </a>
            </div>
        </article>

        <article data-reveal="left" class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-[0_28px_70px_-46px_rgba(6,78,59,.75)]">
            <div class="flex flex-col gap-4 border-b border-slate-100 p-6 sm:flex-row sm:items-center sm:justify-between sm:p-8">
                <div><p class="eyebrow">موقع المركز</p><h2 class="section-title mt-1">اعثر علينا بسهولة</h2><p class="mt-2 text-sm leading-7 text-slate-600">الموقع المثبّت رسميًا لمركز الغفران لتحفيظ القرآن الكريم.</p></div>
                <a href="{{ $contact['map_url'] }}" target="_blank" rel="noopener noreferrer" class="btn-secondary shrink-0">فتح في خرائط Google</a>
            </div>
            <div class="public-contact-map relative min-h-80 bg-emerald-50">
                <iframe title="موقع مركز الغفران لتحفيظ القرآن الكريم" src="{{ $contact['map_embed_url'] }}" class="absolute inset-0 h-full w-full border-0" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
            </div>
            <div class="flex flex-wrap items-center gap-x-4 gap-y-2 bg-slate-50 px-6 py-4 text-xs text-slate-500 sm:px-8"><span class="inline-flex items-center gap-2 font-bold text-emerald-800"><span class="size-2 rounded-full bg-emerald-500"></span> إحداثيات الموقع معتمدة</span><span dir="ltr">{{ $contact['coordinates'] }}</span></div>
        </article>
    </section>
</x-public-shell>
