@props(['size' => 'md'])

@php
    $sizeClasses = match ($size) {
        'xs' => 'size-12',
        'sm' => 'size-16',
        'lg' => 'size-32',
        default => 'size-20',
    };
@endphp

<span {{ $attributes->class("brand-logo relative grid shrink-0 place-items-center overflow-hidden rounded-full border-2 border-amber-400/45 bg-white shadow-lg shadow-emerald-950/15 ring-4 ring-white/20 {$sizeClasses}") }}>
    <img src="{{ asset(config('app.logo')) }}" alt="شعار {{ config('app.name') }}" class="brand-logo-image absolute top-0 h-auto w-[92%] max-w-none" loading="eager">
</span>
