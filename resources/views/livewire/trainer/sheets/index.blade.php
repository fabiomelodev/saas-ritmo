<div>
    <x-page-header title="Fichas de força" eyebrow="Monte uma vez e use no plano de qualquer aluno" />

    <div class="grid gap-5 md:grid-cols-[repeat(auto-fill,minmax(320px,1fr))]">
        @foreach ($this->sheets as $sheet)
            @php
                $items = $sheet->blocks->flatMap->items;
                $strength = $sheet->blocks->where('section', 'strength');
                $mobilityCount = $sheet->blocks->where('section', 'mobility')->flatMap->items->count();
                $mediaCount = $withMedia($sheet);
            @endphp
            <article wire:key="sheet-{{ $sheet->id }}" class="card flex flex-col gap-3.5 p-5">
                <div class="flex items-start justify-between gap-2.5">
                    <div class="flex flex-col gap-1">
                        <h2 class="display text-[28px]">{{ $sheet->name }}</h2>
                        <span class="text-[13px] text-muted">{{ $items->count() - $mobilityCount }} exercícios · {{ $mobilityCount }} de mobilidade</span>
                    </div>
                    <span class="chip bg-ink text-white">Força</span>
                </div>

                <ol class="flex flex-col gap-2">
                    @forelse ($strength->take(4) as $block)
                        <li class="flex justify-between gap-2.5 rounded-[10px] bg-surface px-2.5 py-2 text-sm">
                            <span>{{ $block->items->map(fn ($i) => $i->exercise?->name)->filter()->join(' + ') ?: 'Bloco vazio' }}</span>
                            <span class="whitespace-nowrap text-muted">{{ $block->sets ? $block->sets.' séries' : '' }}</span>
                        </li>
                    @empty
                        <li class="rounded-[10px] bg-surface px-2.5 py-2 text-sm text-muted">Nenhum exercício ainda</li>
                    @endforelse
                </ol>

                @if ($items->count())
                    <div class="flex flex-col gap-1.5">
                        <div class="flex justify-between text-xs text-neutral-600"><span>Exercícios com vídeo ou foto</span><span class="font-bold">{{ $mediaCount }} de {{ $items->count() }}</span></div>
                        <div class="h-1.5 rounded bg-line"><div class="h-1.5 rounded bg-brand" style="width: {{ round($mediaCount / max($items->count(), 1) * 100) }}%"></div></div>
                    </div>
                @endif

                <div class="mt-auto flex flex-wrap items-center justify-end gap-2 border-t border-[#EEEEEE] pt-3">
                    <button type="button" wire:click="delete({{ $sheet->id }})" wire:confirm="Excluir a ficha {{ $sheet->name }}? Os dias do plano que usam ela ficam sem ficha."
                            class="mr-auto text-[13px] font-semibold text-brand-dark">Excluir</button>
                    <button type="button" wire:click="duplicate({{ $sheet->id }})" class="btn-outline h-10 px-3 text-[13px]">Duplicar</button>
                    <a href="{{ route('trainer.sheets.edit', $sheet) }}" wire:navigate class="btn-dark h-10 px-3.5 text-[13px]">Editar</a>
                </div>
            </article>
        @endforeach

        <form wire:submit="create" class="flex min-h-[240px] flex-col items-center justify-center gap-3 rounded-[20px] border-2 border-dashed border-[#BDBDBD] p-5 text-center">
            <x-icon name="dumbbell" class="size-8 text-neutral-500" />
            <span class="text-base font-bold">Criar nova ficha</span>
            <label for="newName" class="sr-only">Nome da ficha</label>
            <input id="newName" wire:model="newName" placeholder="Ex.: Treino C, Core, Pré-prova" class="input max-w-64 text-center">
            @error('newName') <span class="field-error">{{ $message }}</span> @enderror
            <button type="submit" class="btn-red">+ Criar ficha</button>
        </form>
    </div>

    {{-- Biblioteca: a mídia de um exercício vale para todas as fichas --}}
    <section aria-label="Biblioteca de exercícios" class="card mt-6 flex flex-col gap-3.5 p-5">
        <div class="flex flex-wrap items-center justify-between gap-2.5">
            <div class="flex flex-col gap-0.5">
                <h2 class="text-lg font-extrabold">Biblioteca de exercícios</h2>
                <span class="text-[13px] text-muted">{{ $this->totalExercises }} exercícios · o vídeo e as fotos cadastrados aqui valem em todas as fichas</span>
            </div>
            <a href="{{ route('trainer.categories') }}" wire:navigate class="btn-outline h-10 px-3 text-[13px]">Gerenciar categorias</a>
        </div>

        {{-- Filtros --}}
        <div class="flex flex-wrap items-end gap-2.5">
            <div class="flex min-w-0 flex-[2_1_200px] flex-col gap-1">
                <label for="search" class="text-xs font-semibold text-neutral-600">Buscar</label>
                <input id="search" type="search" wire:model.live.debounce.300ms="search" placeholder="Nome do exercício" class="input">
            </div>
            <div class="flex min-w-0 flex-[1_1_170px] flex-col gap-1">
                <label for="category" class="text-xs font-semibold text-neutral-600">Categoria</label>
                <select id="category" wire:model.live="category" class="input">
                    <option value="">Todas</option>
                    @foreach ($this->categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }} ({{ $cat->exercises_count }})</option>
                    @endforeach
                    <option value="none">Sem categoria</option>
                </select>
            </div>
            <div role="radiogroup" aria-label="Filtrar por mídia" class="flex gap-1 rounded-xl bg-surface p-1">
                @foreach (['' => 'Todos', 'with' => 'Com mídia', 'without' => 'Sem mídia'] as $value => $label)
                    <label @class([
                        'flex h-9 cursor-pointer items-center rounded-[9px] px-3 text-[13px] font-bold',
                        'bg-ink text-white' => $media === $value,
                        'text-neutral-700' => $media !== $value,
                    ])>
                        <input type="radio" wire:model.live="media" value="{{ $value }}" class="sr-only">{{ $label }}
                    </label>
                @endforeach
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 text-[13px]">
            <span class="text-neutral-600">{{ $this->exercises->count() }} {{ Str::plural('resultado', $this->exercises->count()) }}</span>
            @if ($this->withoutMediaCount && $media !== 'without')
                <button type="button" wire:click="$set('media', 'without')" class="chip bg-brand-soft text-brand-dark">
                    {{ $this->withoutMediaCount }} sem vídeo ou foto · ver
                </button>
            @endif
            @if ($this->hasFilters())
                <button type="button" wire:click="clearFilters" class="font-semibold text-brand-dark">Limpar filtros</button>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[560px] border-collapse text-sm">
                <thead>
                    <tr class="text-left text-xs text-muted">
                        <th class="border-b border-[#EEEEEE] px-2 py-2.5 font-bold">EXERCÍCIO</th>
                        <th class="border-b border-[#EEEEEE] px-2 py-2.5 font-bold">CATEGORIA</th>
                        <th class="border-b border-[#EEEEEE] px-2 py-2.5 font-bold">MÍDIA</th>
                        <th class="border-b border-[#EEEEEE] px-2 py-2.5"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->exercises as $exercise)
                        @php($hasMedia = filled($exercise->youtube_url) || $exercise->images_count)
                        <tr wire:key="ex-{{ $exercise->id }}">
                            <td class="border-b border-[#F2F2F2] px-2 py-3 font-semibold">{{ $exercise->name }}</td>
                            <td class="border-b border-[#F2F2F2] px-2 py-3 text-neutral-600">{{ $exercise->category?->name ?? '—' }}</td>
                            <td class="border-b border-[#F2F2F2] px-2 py-3">
                                @if ($hasMedia)
                                    <span class="chip bg-success-soft text-success">{{ collect([filled($exercise->youtube_url) ? 'Vídeo' : null, $exercise->images_count ? $exercise->images_count.' '.Str::plural('foto', $exercise->images_count) : null])->filter()->join(' · ') }}</span>
                                @else
                                    <span class="chip bg-brand-soft text-brand-dark">Sem mídia</span>
                                @endif
                            </td>
                            <td class="border-b border-[#F2F2F2] px-2 py-3 text-right">
                                <a href="{{ route('trainer.exercises.edit', $exercise) }}" wire:navigate class="text-[13px] font-bold text-brand-dark">{{ $hasMedia ? 'Editar' : 'Adicionar mídia' }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-2 py-4 text-muted">
                            {{ $this->hasFilters() ? 'Nenhum exercício com esses filtros.' : 'Os exercícios aparecem aqui quando você os adiciona a uma ficha.' }}
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
