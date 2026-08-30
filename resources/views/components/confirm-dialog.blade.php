<div
    x-data="{
        open: false,
        busy: false,
        options: {},
        show(detail) {
            this.options = {
                title: detail.title || 'تأكيد الإجراء',
                message: detail.message || 'هل تريد المتابعة؟',
                confirmLabel: detail.confirmLabel || 'تأكيد',
                cancelLabel: detail.cancelLabel || 'تراجع',
                tone: detail.tone || 'danger',
                action: detail.action,
            };
            this.busy = false;
            this.open = true;
            this.$nextTick(() => this.$refs.confirmButton?.focus());
        },
        confirm() {
            if (this.busy) return;
            this.busy = true;
            const action = this.options.action;
            this.open = false;
            this.$nextTick(() => action?.());
        }
    }"
    @app:confirm.window="show($event.detail)"
    @keydown.escape.window="if (open && !busy) open = false"
    x-cloak
>
    <div x-show="open" x-transition.opacity.duration.200ms class="fixed inset-0 z-[90] bg-slate-950/55 backdrop-blur-sm" @click="if (!busy) open = false"></div>
    <div x-show="open" class="fixed inset-0 z-[91] grid place-items-center p-4" role="dialog" aria-modal="true" :aria-label="options.title">
        <div
            x-show="open"
            x-transition:enter="transition duration-300 ease-out"
            x-transition:enter-start="translate-y-5 scale-[.97] opacity-0"
            x-transition:enter-end="translate-y-0 scale-100 opacity-100"
            x-transition:leave="transition duration-200 ease-in"
            x-transition:leave-start="translate-y-0 scale-100 opacity-100"
            x-transition:leave-end="translate-y-3 scale-[.98] opacity-0"
            @click.stop
            class="relative w-full max-w-md overflow-hidden rounded-[2rem] border border-white/70 bg-white p-6 shadow-[0_35px_100px_-35px_rgba(2,32,27,.65)] sm:p-7"
        >
            <div class="flex items-start gap-4">
                <span class="grid size-13 shrink-0 place-items-center rounded-2xl" :class="options.tone === 'danger' ? 'bg-rose-50 text-rose-600' : 'bg-amber-50 text-amber-600'">
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3 3 7v5c0 4.4 3.7 7.8 9 9 5.3-1.2 9-4.6 9-9V7l-9-4Z"/><path d="M12 8v5m0 3h.01"/></svg>
                </span>
                <div class="min-w-0 flex-1"><h2 class="text-lg font-black text-emerald-950" x-text="options.title"></h2><p class="mt-2 text-sm leading-7 text-slate-500" x-text="options.message"></p></div>
            </div>
            <div class="mt-7 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <button type="button" class="btn-secondary" @click="open = false" :disabled="busy" x-text="options.cancelLabel"></button>
                <button x-ref="confirmButton" type="button" class="inline-flex items-center justify-center rounded-xl px-5 py-2.5 text-sm font-black text-white shadow-sm disabled:opacity-60" :class="options.tone === 'danger' ? 'bg-rose-600 hover:bg-rose-700' : 'bg-amber-600 hover:bg-amber-700'" @click="confirm()" :disabled="busy" x-text="options.confirmLabel"></button>
            </div>
        </div>
    </div>
</div>
