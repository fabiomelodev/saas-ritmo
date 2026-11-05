{{-- Sino com contador de avisos não lidos: <x-notification-bell /> ou <x-notification-bell dark /> --}}
@props(['dark' => false])

@php
    $unread = auth()->user()->unreadNotifications()->count();
@endphp

<a href="{{ route('notifications') }}" wire:navigate
   aria-label="Notificações{{ $unread ? ', '.$unread.' não lidas' : '' }}"
   {{ $attributes->class([
       'relative flex size-11 shrink-0 items-center justify-center rounded-full',
       'bg-ink-3 text-white hover:bg-ink-2' => $dark,
       'border border-[#E2E2E2] bg-white text-ink' => ! $dark,
   ]) }}>
    <svg aria-hidden="true" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
    @if ($unread)
        <span class="absolute -right-0.5 -top-0.5 min-w-[18px] rounded-full bg-brand px-1 text-center text-[10px] font-bold leading-[18px] text-white">{{ min($unread, 99) }}</span>
    @endif
</a>
