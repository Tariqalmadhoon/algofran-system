@props([
    'type' => 'info',
    'title' => null,
    'message' => null,
    'duration' => 6200,
])

@php
    $styles = match ($type) {
        'success' => ['border-emerald-200/80', 'text-emerald-700', 'bg-emerald-50', 'bg-emerald-500', 'تمت العملية'],
        'error' => ['border-rose-200/80', 'text-rose-700', 'bg-rose-50', 'bg-rose-500', 'تعذّر إكمال العملية'],
        'warning' => ['border-amber-200/80', 'text-amber-700', 'bg-amber-50', 'bg-amber-500', 'تنبيه مهم'],
        default => ['border-sky-200/80', 'text-sky-700', 'bg-sky-50', 'bg-sky-500', 'للعلم'],
    };
    [$border, $text, $iconBackground, $accent, $defaultTitle] = $styles;
@endphp

<div
    x-data="{
        show: false,
        remaining: {{ (int) $duration }},
        timer: null,
        startedAt: null,
        start() {
            if (this.remaining <= 0) return;
            this.startedAt = Date.now();
            this.timer = setTimeout(() => this.close(), this.remaining);
        },
        pause() {
            if (!this.timer) return;
            clearTimeout(this.timer);
            this.timer = null;
            this.remaining = Math.max(0, this.remaining - (Date.now() - this.startedAt));
        },
        close() {
            clearTimeout(this.timer);
            this.timer = null;
            this.show = false;
        }
    }"
    x-init="$nextTick(() => { show = true; start(); })"
    x-show="show"
    x-cloak
    x-transition:enter="transition duration-500 ease-out"
    x-transition:enter-start="translate-x-6 opacity-0"
    x-transition:enter-end="translate-x-0 opacity-100"
    x-transition:leave="transition duration-250 ease-in"
    x-transition:leave-start="translate-x-0 opacity-100"
    x-transition:leave-end="translate-x-5 opacity-0"
    @mouseenter="pause()"
    @mouseleave="start()"
    class="feedback-alert relative isolate w-full overflow-hidden rounded-3xl border {{ $border }} bg-white shadow-[0_24px_70px_-35px_rgba(15,23,42,.35)]"
    role="{{ $type === 'error' ? 'alert' : 'status' }}"
    aria-live="{{ $type === 'error' ? 'assertive' : 'polite' }}"
>
    <span aria-hidden="true" class="absolute inset-y-0 right-0 w-1.5 {{ $accent }}"></span>
    <div class="flex items-start gap-3.5 p-4 pr-5">
        <span class="grid size-11 shrink-0 place-items-center rounded-2xl {{ $iconBackground }} {{ $text }}">
            @if($type === 'success')
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 12 4 4L19 6"/></svg>
            @elseif($type === 'error')
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v6m0 4h.01"/></svg>
            @elseif($type === 'warning')
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3 2.8 20h18.4L12 3Z"/><path d="M12 9v4m0 3h.01"/></svg>
            @else
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 11v6m0-10h.01"/></svg>
            @endif
        </span>
        <div class="min-w-0 flex-1 pt-0.5">
            <p class="text-sm font-black text-slate-900">{{ $title ?: $defaultTitle }}</p>
            <div class="mt-1 text-xs font-medium leading-6 text-slate-600">
                @if($message !== null){{ $message }}@else{{ $slot }}@endif
            </div>
        </div>
        <button type="button" @click="close()" class="grid size-8 shrink-0 place-items-center rounded-xl text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="إغلاق التنبيه">
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 6 12 12M18 6 6 18"/></svg>
        </button>
    </div>
    @if((int) $duration > 0)
        <span aria-hidden="true" class="feedback-progress absolute inset-x-0 bottom-0 h-0.5 {{ $accent }}" style="animation-duration: {{ (int) $duration }}ms"></span>
    @endif
</div>
