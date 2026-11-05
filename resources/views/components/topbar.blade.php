@php
    $user = auth()->user();
    $items = \App\Support\Navigation::for($user);
@endphp

{{-- Barra superior no desktop, para treinador e aluno (no celular vale a barra inferior) --}}
<header class="hidden flex-wrap items-center gap-x-7 gap-y-2 bg-ink px-8 py-3 text-white md:flex">
    <a href="{{ route('home') }}" wire:navigate><x-logo /></a>
    <nav aria-label="Navegação principal" class="flex flex-1 flex-wrap gap-1">
        @foreach ($items as $item)
            @php
                $active = \App\Support\Navigation::isActive($item);
            @endphp
            <a href="{{ $item['route'] ? route($item['route']) : '#' }}" @if ($item['route']) wire:navigate @endif
               @class([
                   'flex items-center gap-2 rounded-[10px] px-3.5 py-2.5 text-sm',
                   'bg-ink-3 font-bold text-white' => $active,
                   'font-semibold text-neutral-300 hover:text-white' => ! $active,
                   'pointer-events-none opacity-50' => ! $item['route'],
               ])
               @if ($active) aria-current="page" @endif
               @unless ($item['route']) aria-disabled="true" title="Em breve" @endunless>
                <x-icon :name="$item['icon']" class="size-[18px]" />
                {{ $item['label'] }}
                @if (($item['badge'] ?? 0) > 0)
                    <span class="min-w-[20px] rounded-full bg-brand px-1.5 text-center text-[11px] font-bold leading-5 text-white" aria-label="{{ $item['badge'] }} novos">{{ min($item['badge'], 99) }}</span>
                @endif
            </a>
        @endforeach
    </nav>
    <div class="flex items-center gap-2.5">
        <x-notification-bell :dark="true" class="size-9!" />
        <span class="text-sm text-neutral-300">{{ $user->name }}</span>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" title="Sair" aria-label="Sair da conta"
                    class="flex size-9 items-center justify-center rounded-full bg-brand text-[13px] font-bold">{{ $user->initials }}</button>
        </form>
    </div>
</header>
