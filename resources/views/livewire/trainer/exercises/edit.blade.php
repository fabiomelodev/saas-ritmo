@php($exercise = $this->exercise)

<form wire:submit="save">
    <x-page-header title="Editar exercício" :eyebrow="$this->sheets->pluck('name')->join(', ') ?: 'Biblioteca de exercícios'" :back="route('trainer.sheets.index')">
        <button type="submit" class="btn-red" wire:loading.attr="disabled">Salvar exercício</button>
    </x-page-header>

    <x-flash />

    <div class="flex flex-wrap items-start gap-6">
        <div class="flex min-w-0 flex-[999_1_520px] flex-col gap-5">
            <section class="card flex flex-col gap-4 p-5">
                <h2 class="text-base font-extrabold">Informações</h2>
                <div class="flex flex-wrap gap-3">
                    <div class="flex flex-[2_1_240px] flex-col gap-1.5">
                        <label for="name" class="field-label">Nome do exercício</label>
                        <input id="name" wire:model="name" class="input">
                        @error('name') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="flex flex-[1_1_200px] flex-col gap-1.5">
                        <label for="category_id" class="field-label">Categoria</label>
                        @if ($creatingCategory)
                            <div class="flex gap-2">
                                <label for="newCategory" class="sr-only">Nome da nova categoria</label>
                                <input id="newCategory" wire:model="newCategory" wire:keydown.enter.prevent="addCategory" placeholder="Ex.: Glúteos" class="input flex-1" autofocus>
                                <button type="button" wire:click="addCategory" class="btn-dark px-3">Criar</button>
                                <button type="button" wire:click="$set('creatingCategory', false)" aria-label="Cancelar" class="btn-outline px-3">✕</button>
                            </div>
                            @error('newCategory') <span class="field-error">{{ $message }}</span> @enderror
                        @else
                            <select id="category_id" wire:model="category_id" class="input">
                                <option value="">Sem categoria</option>
                                @foreach ($this->categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                            <button type="button" wire:click="$set('creatingCategory', true)" class="self-start text-xs font-bold text-brand-dark">+ Nova categoria</button>
                        @endif
                        @error('category_id') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label for="instructions" class="field-label">Como executar</label>
                    <textarea id="instructions" wire:model="instructions" rows="3" placeholder="Descreva a execução, cuidados e erros comuns…"
                              class="rounded-xl border border-line-strong px-3 py-2.5 text-sm outline-none focus:border-ink"></textarea>
                </div>
            </section>

            <section class="card flex flex-col gap-3.5 p-5">
                <div class="flex items-center gap-2.5">
                    <svg aria-hidden="true" class="size-5 text-brand" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="4"/><path d="m10 9 5 3-5 3z"/></svg>
                    <h2 class="text-base font-extrabold">Vídeo do YouTube</h2>
                </div>
                <div class="flex flex-wrap gap-3">
                    <div class="flex flex-[3_1_280px] flex-col gap-1.5">
                        <label for="youtube_url" class="field-label">Link do vídeo</label>
                        <input id="youtube_url" type="url" wire:model.blur="youtube_url" class="input" placeholder="https://youtube.com/watch?v=…">
                        @error('youtube_url') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="flex flex-[1_1_140px] flex-col gap-1.5">
                        <label for="video_start" class="field-label">Começar em</label>
                        <input id="video_start" wire:model.blur="video_start" class="input" placeholder="0:42">
                        @error('video_start') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                </div>
                <p class="text-xs text-muted">Útil quando o exercício aparece no meio do vídeo. A prévia atualiza depois de salvar.</p>

                @if ($embed = $exercise->youtubeEmbedUrl())
                    <div class="aspect-video overflow-hidden rounded-[14px] bg-ink-2">
                        <iframe class="size-full" src="{{ $embed }}" title="Vídeo: {{ $exercise->name }}" loading="lazy"
                                allow="accelerometer; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                    </div>
                @endif
            </section>

            <section class="card flex flex-col gap-3.5 p-5">
                <div class="flex flex-wrap items-center justify-between gap-2.5">
                    <h2 class="text-base font-extrabold">Imagens</h2>
                    <span class="text-xs text-muted">Até {{ $this::MAX_IMAGES }} imagens · JPG, PNG ou WebP</span>
                </div>

                <div class="grid grid-cols-[repeat(auto-fill,minmax(150px,1fr))] gap-3">
                    @foreach ($exercise->images as $image)
                        <div wire:key="img-{{ $image->id }}" class="flex flex-col gap-1.5">
                            <div class="relative aspect-square overflow-hidden rounded-[14px] bg-line">
                                <img src="{{ Storage::disk('public')->url($image->path) }}" alt="{{ $image->caption ?: $exercise->name }}" class="size-full object-cover">
                                <button type="button" wire:click="removeImage({{ $image->id }})" wire:confirm="Remover esta imagem?"
                                        aria-label="Remover imagem" class="absolute right-1.5 top-1.5 flex size-8 items-center justify-center rounded-full bg-ink/80 text-white">✕</button>
                            </div>
                            <label for="cap-{{ $image->id }}" class="sr-only">Legenda da imagem</label>
                            <input id="cap-{{ $image->id }}" wire:model="captions.{{ $image->id }}" placeholder="Legenda" class="input h-9 text-[13px]">
                        </div>
                    @endforeach

                    @foreach ($photos as $index => $photo)
                        <div wire:key="new-{{ $index }}" class="relative aspect-square overflow-hidden rounded-[14px] bg-line">
                            @if (method_exists($photo, 'isPreviewable') && $photo->isPreviewable())
                                <img src="{{ $photo->temporaryUrl() }}" alt="Nova imagem" class="size-full object-cover">
                            @endif
                            <span class="absolute bottom-1.5 left-1.5 chip bg-white/90 text-[11px]">Nova</span>
                            <button type="button" wire:click="removePhoto({{ $index }})" aria-label="Descartar imagem" class="absolute right-1.5 top-1.5 flex size-8 items-center justify-center rounded-full bg-ink/80 text-white">✕</button>
                        </div>
                    @endforeach

                    @if ($exercise->images->count() + count($photos) < $this::MAX_IMAGES)
                        <label class="flex aspect-square cursor-pointer flex-col items-center justify-center gap-1.5 rounded-[14px] border-2 border-dashed border-[#BDBDBD] bg-[#FAFAFA] text-neutral-700">
                            <span class="text-2xl leading-none">+</span>
                            <span class="text-[13px] font-bold">Enviar imagem</span>
                            <input type="file" wire:model="photos" multiple accept="image/jpeg,image/png,image/webp" class="sr-only">
                        </label>
                    @endif
                </div>
                <div wire:loading wire:target="photos" class="text-xs text-muted">Enviando…</div>
                @error('photos') <span class="field-error">{{ $message }}</span> @enderror
                @error('photos.*') <span class="field-error">{{ $message }}</span> @enderror
            </section>
        </div>

        {{-- Como o aluno vê --}}
        <aside aria-label="Como o aluno vê" class="flex flex-[1_1_280px] flex-col gap-2.5">
            <span class="text-xs font-bold tracking-wider text-muted">COMO O ALUNO VÊ</span>
            <div class="flex flex-col gap-3 rounded-3xl border border-[#E2E2E2] bg-white p-4">
                <div class="flex aspect-video items-center justify-center rounded-[14px] bg-ink-2">
                    <span class="flex size-11 items-center justify-center rounded-full {{ $exercise->youtube_url ? 'bg-brand' : 'bg-ink-3' }}">
                        <svg aria-hidden="true" class="size-[18px]" viewBox="0 0 24 24" fill="#FFFFFF"><path d="M8 5v14l11-7z"/></svg>
                    </span>
                </div>
                <span class="display text-[26px]">{{ $name ?: 'Exercício' }}</span>
                @if ($exercise->images->isNotEmpty())
                    <div class="grid grid-cols-2 gap-2">
                        @foreach ($exercise->images->take(2) as $image)
                            <div class="flex flex-col gap-1">
                                <img src="{{ Storage::disk('public')->url($image->path) }}" alt="" class="aspect-square rounded-xl object-cover">
                                <span class="text-xs text-neutral-700">{{ $image->caption }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
                @if ($instructions)
                    <p class="text-[13px] leading-relaxed text-neutral-700">{{ $instructions }}</p>
                @endif
            </div>
        </aside>
    </div>
</form>
