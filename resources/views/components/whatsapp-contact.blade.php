@props([
    'phone' => null,
    'label' => 'مراسلة عبر واتساب',
])

@php($whatsappUrl = app(\App\Services\WhatsAppLinkService::class)->conversationUrl($phone))

@if($whatsappUrl)
    <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer" data-no-navigation-feedback title="{{ $label }}" aria-label="{{ $label }}" {{ $attributes->merge(['class' => 'inline-flex items-center justify-center gap-1.5 rounded-xl bg-[#25D366] px-3 py-2 text-xs font-black text-emerald-950 shadow-sm shadow-emerald-950/10 transition duration-200 hover:-translate-y-0.5 hover:bg-[#52e58b] hover:shadow-md focus:outline-none focus:ring-2 focus:ring-[#25D366] focus:ring-offset-2']) }}>
        <svg class="size-4 shrink-0" viewBox="0 0 32 32" fill="currentColor" aria-hidden="true">
            <path d="M16 2.7a13.1 13.1 0 0 0-11.2 19.8L3 29.3l7-1.8A13.15 13.15 0 1 0 16 2.7Zm0 23.9a10.75 10.75 0 0 1-5.49-1.5l-.4-.24-4.15 1.08 1.1-4.04-.27-.42A10.78 10.78 0 1 1 16 26.6Zm5.91-8.08c-.32-.16-1.86-.92-2.15-1.02-.29-.11-.5-.16-.71.16-.21.31-.81 1.02-.99 1.23-.18.21-.37.24-.69.08a8.77 8.77 0 0 1-2.58-1.6 9.65 9.65 0 0 1-1.79-2.23c-.18-.31-.02-.49.14-.64.14-.14.32-.37.48-.55.16-.19.21-.32.32-.53.1-.21.05-.39-.03-.55-.08-.16-.71-1.71-.97-2.34-.26-.62-.52-.54-.71-.55l-.61-.01c-.21 0-.55.08-.84.39-.29.32-1.1 1.08-1.1 2.63s1.13 3.05 1.29 3.26c.16.21 2.22 3.39 5.37 4.75.75.33 1.34.52 1.8.67.76.24 1.46.21 2.01.13.61-.09 1.86-.76 2.12-1.5.26-.74.26-1.37.18-1.5-.07-.13-.28-.21-.6-.37Z"/>
        </svg>
        <span>{{ $label }}</span>
    </a>
@endif
