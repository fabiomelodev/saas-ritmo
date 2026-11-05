<div class="md:max-w-2xl">
    <x-page-header title="Categorias" eyebrow="Biblioteca de exercícios" :back="route('trainer.sheets.index')" />

    <div class="card flex flex-col gap-1 p-2">
        @foreach ($this->categories as $category)
            <div wire:key="cat-{{ $category->id }}" class="flex items-center gap-2 rounded-xl p-2 hover:bg-surface">
                <div class="flex min-w-0 flex-1 flex-col gap-0.5">
                    <label for="cat-{{ $category->id }}" class="sr-only">Nome da categoria</label>
                    <input id="cat-{{ $category->id }}" wire:model="names.{{ $category->id }}" wire:blur="rename({{ $category->id }})"
                           wire:keydown.enter.prevent="rename({{ $category->id }})" class="input h-10 border-transparent bg-transparent font-semibold hover:border-line-strong focus:bg-white">
                    @error('names.'.$category->id) <span class="field-error px-3">{{ $message }}</span> @enderror
                </div>
                <a href="{{ route('trainer.sheets.index', ['categoria' => $category->id]) }}" wire:navigate class="whitespace-nowrap text-xs font-semibold text-neutral-600 hover:text-ink">
                    {{ $category->exercises_count }} {{ Str::plural('exercício', $category->exercises_count) }}
                </a>
                <button type="button" wire:click="move({{ $category->id }}, -1)" aria-label="Subir {{ $category->name }}" class="flex size-9 items-center justify-center rounded-lg border border-line-strong bg-white" @disabled($loop->first)>↑</button>
                <button type="button" wire:click="move({{ $category->id }}, 1)" aria-label="Descer {{ $category->name }}" class="flex size-9 items-center justify-center rounded-lg border border-line-strong bg-white" @disabled($loop->last)>↓</button>
                <button type="button" wire:click="delete({{ $category->id }})"
                        wire:confirm="Excluir “{{ $category->name }}”?{{ $category->exercises_count ? ' Os '.$category->exercises_count.' exercícios dela ficam sem categoria.' : '' }}"
                        aria-label="Excluir {{ $category->name }}" class="flex size-9 items-center justify-center rounded-lg border border-line-strong bg-white text-brand-dark">✕</button>
            </div>
        @endforeach

        @if ($this->uncategorizedCount)
            <a href="{{ route('trainer.sheets.index', ['categoria' => 'none']) }}" wire:navigate class="flex items-center justify-between rounded-xl px-4 py-3 text-sm text-neutral-600 hover:bg-surface">
                <span class="italic">Sem categoria</span>
                <span class="text-xs font-semibold">{{ $this->uncategorizedCount }} {{ Str::plural('exercício', $this->uncategorizedCount) }}</span>
            </a>
        @endif
    </div>

    <form wire:submit="create" class="mt-4 flex flex-col gap-1.5">
        <label for="newName" class="field-label">Nova categoria</label>
        <div class="flex gap-2">
            <input id="newName" wire:model="newName" placeholder="Ex.: Glúteos, Panturrilha, Aquecimento" class="input flex-1">
            <button type="submit" class="btn-red">Adicionar</button>
        </div>
        @error('newName') <span class="field-error">{{ $message }}</span> @enderror
    </form>
</div>
