@php
    $item = $this->item;
    $exercise = $item->exercise;
    $block = $item->block;
    $isMobility = $block->section === 'mobility';
    $partners = $block->items->where('id', '!=', $item->id);
    $embed = $exercise->youtubeEmbedUrl();
    $canLog = $this->session->canBeLogged();
@endphp

{{--
    Celular: uma coluna (vídeo, título, séries, fotos, instruções, botão fixo no rodapé).
    Desktop: vídeo + fotos + instruções à esquerda; painel fixo à direita com séries,
    ação e a lista de exercícios da ficha.
    Os wrappers usam "contents" no celular para os itens seguirem a ordem (order-*) de uma coluna só.
--}}
<div class="flex flex-col gap-[18px] md:grid md:grid-cols-[minmax(0,1.65fr)_minmax(320px,1fr)] md:items-start md:gap-x-8 md:gap-y-6">
    {{-- Desktop: navegação acima das colunas --}}
    <nav aria-label="Navegação" class="hidden items-center gap-3 md:col-span-2 md:flex">
        <a href="{{ route('student.session', $this->session) }}" wire:navigate class="btn-outline">
            <x-icon name="back" class="size-4" stroke-width="2.5" /> Voltar para a ficha
        </a>
        <span class="text-sm text-muted">
            {{ $this->session->title }} · {{ ucfirst($this->session->date()->translatedFormat('l, j/m')) }}
        </span>
    </nav>

    {{-- Coluna principal --}}
    <div class="contents md:col-start-1 md:flex md:flex-col md:gap-6">
        {{-- Vídeo --}}
        <div class="relative order-1 -mx-5 -mt-6 bg-ink md:order-none md:mx-0 md:mt-0 md:overflow-hidden md:rounded-[20px]">
            <a href="{{ route('student.session', $this->session) }}" wire:navigate aria-label="Voltar para a ficha"
               class="absolute left-4 top-4 z-10 flex size-11 items-center justify-center rounded-full bg-ink-3/90 text-white md:hidden">
                <x-icon name="back" class="size-5" stroke-width="2.5" />
            </a>
            @if ($embed)
                <div class="aspect-video w-full">
                    <iframe class="size-full" src="{{ $embed }}" title="Vídeo: {{ $exercise->name }}"
                            allow="accelerometer; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                </div>
            @else
                <div class="flex aspect-[16/7] w-full items-end px-5 pb-4 md:aspect-[16/6] md:items-center md:justify-center">
                    <span class="text-xs text-neutral-400 md:text-sm">Sem vídeo para este exercício</span>
                </div>
            @endif
        </div>

        @if ($exercise->images->isNotEmpty())
            @php
                $photos = $exercise->images->map(fn ($image) => [
                    'src' => Storage::disk('public')->url($image->path),
                    'caption' => $image->caption,
                    'alt' => $image->caption ?: $exercise->name,
                ])->values();
            @endphp

        {{-- Galeria: toque na foto abre o modal ampliado (Alpine, já incluso no Livewire) --}}
        <div class="order-5 flex flex-col gap-2.5 md:order-none"
             x-data="{
                 open: false,
                 index: 0,
                 photos: @js($photos),
                 startX: null,
                 get current() { return this.photos[this.index] },
                 show(i) { this.index = i; this.open = true },
                 close() { this.open = false },
                 next() { this.index = (this.index + 1) % this.photos.length },
                 prev() { this.index = (this.index - 1 + this.photos.length) % this.photos.length },
                 swipeStart(e) { this.startX = e.changedTouches[0].clientX },
                 swipeEnd(e) {
                     if (this.startX === null) return;
                     const dx = e.changedTouches[0].clientX - this.startX;
                     if (Math.abs(dx) > 50) { dx < 0 ? this.next() : this.prev() }
                     this.startX = null;
                 },
             }"
             x-on:keydown.escape.window="open && close()"
             x-on:keydown.arrow-right.window="open && next()"
             x-on:keydown.arrow-left.window="open && prev()">
            <div class="flex items-baseline justify-between">
                <span class="font-display text-xl font-bold uppercase tracking-wide">Fotos</span>
                <span class="text-xs text-muted md:hidden">{{ $exercise->images->count() > 1 ? 'Deslize para ver · toque para ampliar' : 'Toque para ampliar' }}</span>
                <span class="hidden text-xs text-muted md:inline">Clique para ampliar</span>
            </div>
            {{-- Celular: carrossel lateral · Desktop: grade --}}
            <div class="-mr-5 flex snap-x gap-2.5 overflow-x-auto pr-5 md:mr-0 md:grid md:grid-cols-3 md:gap-4 md:overflow-visible md:pr-0">
                @foreach ($photos as $i => $photo)
                    <figure class="m-0 flex w-[220px] shrink-0 snap-start flex-col gap-1.5 md:w-auto">
                        <button type="button" x-on:click="show({{ $i }})" aria-label="Ampliar foto {{ $i + 1 }}: {{ $photo['alt'] }}"
                                class="group relative block overflow-hidden rounded-[18px] focus:outline-none focus-visible:ring-4 focus-visible:ring-brand/40">
                            <img src="{{ $photo['src'] }}" alt="{{ $photo['alt'] }}" class="size-[220px] object-cover transition group-hover:scale-[1.03] md:aspect-square md:size-auto md:w-full">
                            <span aria-hidden="true" class="absolute bottom-2 right-2 flex size-8 items-center justify-center rounded-full bg-ink/70 text-white">
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/></svg>
                            </span>
                        </button>
                        @if ($photo['caption'])<figcaption class="text-[13px] text-neutral-700">{{ $photo['caption'] }}</figcaption>@endif
                    </figure>
                @endforeach
            </div>

            {{-- Modal: vai para o <body> para ficar acima do rodapé fixo --}}
            <template x-teleport="body">
                <div x-show="open" x-cloak
                     x-transition.opacity.duration.150ms
                     x-trap.noscroll.inert="open"
                     role="dialog" aria-modal="true" aria-label="Fotos: {{ $exercise->name }}"
                     class="fixed inset-0 z-50 flex flex-col bg-black/95 text-white"
                     x-on:touchstart.passive="swipeStart($event)"
                     x-on:touchend.passive="swipeEnd($event)">
                    <div class="flex items-center justify-between px-4 pb-2 pt-[max(16px,env(safe-area-inset-top))]">
                        <span class="text-sm font-semibold text-neutral-300" x-text="photos.length > 1 ? (index + 1) + ' de ' + photos.length : ''"></span>
                        <button type="button" x-on:click="close()" aria-label="Fechar"
                                class="flex size-11 items-center justify-center rounded-full bg-white/15 hover:bg-white/25">
                            <svg aria-hidden="true" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                        </button>
                    </div>

                    {{-- Tocar fora da foto também fecha --}}
                    <div class="relative flex min-h-0 flex-1 items-center justify-center px-4" x-on:click.self="close()">
                        <img x-bind:src="current.src" x-bind:alt="current.alt" class="max-h-full max-w-full select-none rounded-xl object-contain">

                        <template x-if="photos.length > 1">
                            <div>
                                <button type="button" x-on:click="prev()" aria-label="Foto anterior"
                                        class="absolute left-2 top-1/2 flex size-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/15 hover:bg-white/25">
                                    <svg aria-hidden="true" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                                </button>
                                <button type="button" x-on:click="next()" aria-label="Próxima foto"
                                        class="absolute right-2 top-1/2 flex size-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/15 hover:bg-white/25">
                                    <svg aria-hidden="true" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                                </button>
                            </div>
                        </template>
                    </div>

                    <div class="flex flex-col items-center gap-3 px-5 pb-[max(24px,env(safe-area-inset-bottom))] pt-3">
                        <p class="min-h-5 text-center text-[15px]" x-text="current.caption || ''"></p>
                        <div class="flex gap-1.5" x-show="photos.length > 1">
                            <template x-for="(photo, i) in photos" x-bind:key="i">
                                <button type="button" x-on:click="index = i" x-bind:aria-label="'Ir para a foto ' + (i + 1)"
                                        x-bind:aria-current="i === index ? 'true' : 'false'"
                                        class="h-2 rounded-full transition-all"
                                        x-bind:class="i === index ? 'w-6 bg-brand' : 'w-2 bg-white/40'"></button>
                            </template>
                        </div>
                    </div>
                </div>
            </template>
        </div>
        @endif

        @if ($exercise->instructions)
            <div class="card order-6 flex flex-col gap-1.5 md:order-none md:p-5">
                <span class="text-sm font-bold md:text-base">Como executar</span>
                <p class="whitespace-pre-line text-sm leading-relaxed text-neutral-700 md:text-[15px]">{{ $exercise->instructions }}</p>
            </div>
        @endif
    </div>

    {{-- Painel lateral (desktop) / bloco abaixo do vídeo (celular) --}}
    <aside aria-label="Detalhes do exercício" class="contents md:sticky md:top-6 md:col-start-2 md:row-start-2 md:flex md:flex-col md:gap-4">
        <div class="order-2 flex flex-col gap-2 md:order-none">
            <span class="text-xs font-bold tracking-wider text-brand-dark">
                {{ $isMobility ? 'MOBILIDADE' : 'BLOCO' }}{{ $partners->isNotEmpty() && ! $isMobility ? ' · JUNTO COM '.Str::upper($partners->map(fn ($p) => $p->exercise->name)->join(' E ')) : '' }}
            </span>
            <h1 class="display text-[38px] md:text-[44px]">{{ $exercise->name }}</h1>
        </div>

        <div class="order-3 grid grid-cols-2 gap-2.5 md:order-none">
            @unless ($isMobility)
                <div class="flex flex-col gap-0.5 rounded-2xl bg-white px-3.5 py-3"><span class="text-xs text-muted">Séries</span><span class="text-[22px] font-extrabold">{{ $block->sets ?: '—' }}</span></div>
            @endunless
            <div @class(['flex flex-col gap-0.5 rounded-2xl bg-white px-3.5 py-3', 'col-span-2' => $isMobility])>
                <span class="text-xs text-muted">Repetições / tempo</span>
                <span class="text-[22px] font-extrabold">{{ $item->prescription ?: '—' }}</span>
            </div>
        </div>

        @if ($item->notes)
            <p class="order-4 rounded-2xl bg-brand-soft px-4 py-3 text-sm font-semibold text-brand-dark md:order-none">{{ $item->notes }}</p>
        @endif

        {{-- Ação: rodapé fixo no celular, dentro do painel no desktop --}}
        <div class="sticky bottom-0 order-7 -mx-5 mt-2 flex flex-col gap-2 border-t border-line bg-white px-5 pb-[max(20px,env(safe-area-inset-bottom))] pt-3 md:static md:order-none md:mx-0 md:mt-0 md:rounded-[20px] md:border-0 md:p-4">
            @if ($this->next)
                <span class="text-center text-xs text-muted">Próximo: {{ $this->next->exercise->name }}</span>
            @endif
            @if ($this->isDone)
                <a href="{{ $this->next ? route('student.exercise', [$this->session, $this->next]) : route('student.session', $this->session) }}" wire:navigate class="btn-primary bg-ink hover:bg-ink-3">
                    Feito ✓ · {{ $this->next ? 'ir para o próximo' : 'voltar para a ficha' }}
                </a>
            @elseif ($canLog)
                <button type="button" wire:click="markDone" class="btn-primary">Marcar como feito</button>
            @else
                <a href="{{ route('student.session', $this->session) }}" wire:navigate class="btn-secondary">Voltar para a ficha</a>
            @endif
        </div>

        {{-- Desktop: todos os exercícios da ficha, para navegar sem voltar --}}
        <nav aria-label="Exercícios da ficha" class="card hidden flex-col gap-1 p-3 md:flex">
            <span class="px-2 pb-1 pt-1 text-xs font-bold tracking-wider text-muted">{{ Str::upper($this->session->workoutSheet->name) }} · {{ count($this->checkedIds) }} DE {{ $this->sequence->count() }}</span>
            @foreach ($this->sequence as $step)
                @php
                    $isCurrent = $step->id === $item->id;
                    $stepDone = in_array($step->id, $this->checkedIds, true);
                @endphp
                <a href="{{ route('student.exercise', [$this->session, $step]) }}" wire:navigate wire:key="step-{{ $step->id }}"
                   @if ($isCurrent) aria-current="page" @endif
                   @class([
                       'flex items-center gap-3 rounded-xl px-2 py-2 text-sm',
                       'bg-brand-soft font-bold text-brand-dark' => $isCurrent,
                       'hover:bg-surface' => ! $isCurrent,
                   ])>
                    @if ($stepDone)
                        <span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-success-soft text-success"><x-icon name="check" class="size-3.5" stroke-width="3" /></span>
                    @else
                        <span @class(['size-6 shrink-0 rounded-full border-2', 'border-brand' => $isCurrent, 'border-line-strong' => ! $isCurrent])></span>
                    @endif
                    <span @class(['min-w-0 flex-1 truncate', 'text-muted line-through' => $stepDone && ! $isCurrent])>{{ $step->exercise->name }}</span>
                    @if ($step->prescription)
                        <span class="whitespace-nowrap text-xs text-muted">{{ $step->prescription }}</span>
                    @endif
                </a>
            @endforeach
        </nav>
    </aside>
</div>
