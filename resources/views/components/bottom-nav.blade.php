@php
    $user = auth()->user();
    $items = \App\Support\Navigation::for($user);
@endphp

<nav aria-label="Navegação principal"
     {{-- no desktop vale a barra superior (x-topbar) --}}
     class="fixed inset-x-0 bottom-0 z-20 mx-auto grid w-full max-w-md border-t border-line bg-white px-2 pb-[max(20px,env(safe-area-inset-bottom))] pt-2 md:hidden"
     style="grid-template-columns: repeat({{ count($items) }}, minmax(0, 1fr))">
    @foreach ($items as $item)
        @php($active = \App\Support\Navigation::isActive($item))
        <a href="{{ $item['route'] ? route($item['route']) : '#' }}" @if($item['route']) wire:navigate @endif
           @class([
               'flex flex-col items-center gap-1 py-1.5 text-[11px]',
               'font-bold text-brand' => $active,
               'font-semibold text-muted' => ! $active,
               'opacity-50' => ! $item['route'],
           ])
           @if($active) aria-current="page" @endif
           @unless($item['route']) aria-disabled="true" title="Em breve" @endunless>
            <span class="relative">
                <x-icon :name="$item['icon']" class="size-6" />
                @if (($item['badge'] ?? 0) > 0)
                    <span class="absolute -right-2.5 -top-1.5 min-w-[18px] rounded-full bg-brand px-1 text-center text-[10px] font-bold leading-[18px] text-white">{{ min($item['badge'], 99) }}</span>
                @endif
            </span>
            {{ $item['label'] }}
            @if (($item['badge'] ?? 0) > 0)<span class="sr-only">({{ $item['badge'] }} novos)</span>@endif
        </a>
    @endforeach
</nav>
