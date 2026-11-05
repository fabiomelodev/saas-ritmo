{{-- Cabeçalho de página: <x-page-header title="Fichas de força" :back="route(...)" eyebrow="…">ações</x-page-header> --}}
@props(['title', 'eyebrow' => null, 'back' => null])

<div class="mb-5 flex flex-wrap items-end justify-between gap-4">
    <div class="flex items-center gap-3">
        @if ($back)
            <a href="{{ $back }}" wire:navigate aria-label="Voltar"
               class="flex size-11 shrink-0 items-center justify-center rounded-full border border-[#E2E2E2] bg-white">
                <x-icon name="back" class="size-5" stroke-width="2.5" />
            </a>
        @endif
        <div class="flex flex-col gap-1">
            @if ($eyebrow)
                <span class="text-[13px] text-muted">{{ $eyebrow }}</span>
            @endif
            <h1 class="display text-[34px] md:text-[40px]">{{ $title }}</h1>
        </div>
    </div>
    @if ($slot->isNotEmpty())
        <div class="flex flex-wrap gap-2">{{ $slot }}</div>
    @endif
</div>
