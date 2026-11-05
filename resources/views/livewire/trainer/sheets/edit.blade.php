<form wire:submit="save" class="md:max-w-3xl">
    <x-page-header :title="$name ?: 'Nova ficha'" eyebrow="Fichas de força" :back="route('trainer.sheets.index')">
        <button type="submit" class="btn-red" wire:loading.attr="disabled">Salvar ficha</button>
    </x-page-header>

    <x-flash />

    <div class="card mb-4 flex flex-col gap-1.5">
        <label for="name" class="field-label">Nome da ficha</label>
        <input id="name" wire:model="name" class="input" placeholder="Treino A">
        @error('name') <span class="field-error">{{ $message }}</span> @enderror
    </div>

    <datalist id="exercise-library">
        @foreach ($this->library as $exerciseName)
            <option value="{{ $exerciseName }}"></option>
        @endforeach
    </datalist>

    <p class="mb-3 text-[13px] text-neutral-600">
        Cada bloco agrupa exercícios feitos em sequência, que dividem as mesmas séries (como os pares da planilha).
    </p>

    <div class="flex flex-col gap-3.5">
        @foreach ($blocks as $b => $block)
            @php($isMobility = $block['section'] === 'mobility')
            <section wire:key="block-{{ $b }}" class="card flex flex-col gap-3">
                <div class="flex flex-wrap items-center gap-2">
                    <span @class(['text-xs font-bold tracking-wider', 'text-brand-dark' => ! $isMobility, 'text-success' => $isMobility])>
                        {{ $isMobility ? 'MOBILIDADE' : 'BLOCO '.($b + 1) }}
                    </span>
                    <div class="ml-auto flex items-center gap-2">
                        @unless ($isMobility)
                            <label for="sets-{{ $b }}" class="text-xs font-semibold text-neutral-600">Séries</label>
                            <input id="sets-{{ $b }}" type="number" min="1" max="20" wire:model="blocks.{{ $b }}.sets" class="input h-9 w-16 text-center">
                        @endunless
                        <button type="button" wire:click="moveBlock({{ $b }}, -1)" aria-label="Subir bloco" class="flex size-9 items-center justify-center rounded-lg border border-line-strong bg-white" @disabled($loop->first)>↑</button>
                        <button type="button" wire:click="moveBlock({{ $b }}, 1)" aria-label="Descer bloco" class="flex size-9 items-center justify-center rounded-lg border border-line-strong bg-white" @disabled($loop->last)>↓</button>
                        <button type="button" wire:click="removeBlock({{ $b }})" wire:confirm="Remover este bloco?" aria-label="Remover bloco" class="flex size-9 items-center justify-center rounded-lg border border-line-strong bg-white text-brand-dark">✕</button>
                    </div>
                </div>

                @foreach ($block['items'] as $i => $item)
                    @php($exerciseId = filled($item['exercise_name']) ? $this->exerciseIdFor($item['exercise_name']) : null)
                    <div wire:key="item-{{ $b }}-{{ $i }}" class="grid grid-cols-[minmax(0,1fr)_96px_auto] items-start gap-2 rounded-xl bg-surface p-2.5 md:grid-cols-[minmax(0,2fr)_110px_minmax(0,1fr)_auto]">
                        <div class="flex flex-col gap-1">
                            <label for="ex-{{ $b }}-{{ $i }}" class="sr-only">Exercício</label>
                            <input id="ex-{{ $b }}-{{ $i }}" list="exercise-library" wire:model.blur="blocks.{{ $b }}.items.{{ $i }}.exercise_name"
                                   placeholder="Exercício" class="input">
                            @if ($exerciseId)
                                <a href="{{ route('trainer.exercises.edit', $exerciseId) }}" wire:navigate class="text-xs font-semibold text-brand-dark">Vídeo e fotos →</a>
                            @endif
                        </div>
                        <div>
                            <label for="rx-{{ $b }}-{{ $i }}" class="sr-only">Repetições ou tempo</label>
                            <input id="rx-{{ $b }}-{{ $i }}" wire:model="blocks.{{ $b }}.items.{{ $i }}.prescription" placeholder="{{ $isMobility ? '10x' : '12' }}" class="input">
                        </div>
                        <div class="col-span-3 row-start-2 md:col-span-1 md:row-start-auto">
                            <label for="nt-{{ $b }}-{{ $i }}" class="sr-only">Observação</label>
                            <input id="nt-{{ $b }}-{{ $i }}" wire:model="blocks.{{ $b }}.items.{{ $i }}.notes" placeholder="Obs.: deitada, pesado…" class="input">
                        </div>
                        <button type="button" wire:click="removeItem({{ $b }}, {{ $i }})" aria-label="Remover exercício" class="col-start-3 row-start-1 flex size-11 items-center justify-center rounded-xl text-neutral-500 hover:text-brand-dark md:col-start-auto">✕</button>
                    </div>
                @endforeach

                <button type="button" wire:click="addItem({{ $b }})" class="self-start text-[13px] font-bold text-brand-dark">+ Exercício neste bloco</button>
            </section>
        @endforeach
    </div>

    @error('blocks.*') <p class="field-error mt-2">{{ $message }}</p> @enderror

    <div class="mt-4 flex flex-wrap gap-2">
        <button type="button" wire:click="addBlock('strength')" class="btn-outline">+ Bloco de força</button>
        <button type="button" wire:click="addBlock('mobility')" class="btn-outline">+ Mobilidade</button>
        <button type="submit" class="btn-red ml-auto" wire:loading.attr="disabled">Salvar ficha</button>
    </div>
</form>
