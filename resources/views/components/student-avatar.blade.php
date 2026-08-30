@props([
    'student',
    'size' => 'md',
    'badge' => null,
])

@php
    $sizes = [
        'xs' => 'size-8 rounded-lg text-xs',
        'sm' => 'size-10 rounded-xl text-sm',
        'md' => 'size-12 rounded-2xl text-base',
        'lg' => 'size-16 rounded-2xl text-xl',
        'xl' => 'size-24 rounded-3xl text-2xl',
        'hero' => 'size-32 rounded-[2rem] text-3xl',
    ];
    $photo = $student->photo;
    $initials = mb_substr($student->first_name ?: $student->full_name, 0, 1)
        .mb_substr($student->family_name ?: '', 0, 1);
@endphp

<span {{ $attributes->class([
    'relative inline-grid shrink-0 place-items-center overflow-hidden bg-gradient-to-br from-emerald-800 to-teal-600 font-black text-white shadow-sm',
    $sizes[$size] ?? $sizes['md'],
]) }}>
    @if($photo)
        <img
            src="{{ route('private-files.preview', $photo) }}"
            alt="صورة {{ $student->full_name }}"
            class="size-full object-cover"
            loading="lazy"
        >
    @else
        <span aria-hidden="true">{{ $initials }}</span>
    @endif

    @if($badge)
        <span class="absolute bottom-0 left-0 grid size-4 place-items-center rounded-tr-lg bg-emerald-500 text-[9px] text-white">{{ $badge }}</span>
    @endif
</span>
