@props(['name' => 'star'])

<svg {{ $attributes->merge(['class' => 'size-5']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($name)
        @case('quran')
            <path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H11v16H6.5A2.5 2.5 0 0 0 4 21.5v-16Z"/><path d="M20 5.5A2.5 2.5 0 0 0 17.5 3H13v16h4.5a2.5 2.5 0 0 1 2.5 2.5v-16Z"/><path d="M7 7h2m6 0h2M7 10h2m6 0h2"/>
            @break
        @case('crescent')
            <path d="M18.6 15.5A7.5 7.5 0 1 1 10 5a6 6 0 0 0 8.6 10.5Z"/><path d="m17.5 4 .65 1.35 1.35.65-1.35.65L17.5 8l-.65-1.35L15.5 6l1.35-.65L17.5 4Z"/>
            @break
        @case('mosque')
            <path d="M5 21v-9h14v9M3 21h18M8 12V8a4 4 0 0 1 8 0v4M12 2v2M7 21v-4a2 2 0 0 1 4 0v4m2 0v-4a2 2 0 0 1 4 0v4"/><path d="M3 12h18"/>
            @break
        @case('lantern')
            <path d="M9 4h6m-5-2h4m-6 5h8l2 11H6L8 7Z"/><path d="M9 18v3m6-3v3M9 11h6l-1 4h-4l-1-4Z"/>
            @break
        @case('certificate')
            <path d="M6 3h12v12H6z"/><path d="m9 8 2 2 4-4m-6 9-1 6 4-2 4 2-1-6"/>
            @break
        @default
            <path d="m12 3 1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9L12 3Z"/><path d="m19 16 .7 1.8 1.8.7-1.8.7L19 21l-.7-1.8-1.8-.7 1.8-.7L19 16Z"/>
    @endswitch
</svg>
