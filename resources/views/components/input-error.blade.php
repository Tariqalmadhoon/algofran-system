@props(['messages'])
@if ($messages)
    <ul {{ $attributes->merge(['class' => 'mt-2 space-y-1.5 text-xs font-bold text-rose-600']) }} role="alert">
        @foreach ((array) $messages as $message)
            <li class="flex items-start gap-1.5"><span class="mt-1.5 size-1.5 shrink-0 rounded-full bg-rose-500"></span><span class="leading-5">{{ $message }}</span></li>
        @endforeach
    </ul>
@endif
