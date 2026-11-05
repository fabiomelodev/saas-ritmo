@php
    $session = $this->session;
    $log = $session->log;
    $isRun = $session->type->value === 'run';
    $status = $session->status();
    $canLog = $session->canBeLogged();
    $date = $session->date();
@endphp

{{--
    Celular: uma coluna (cabeçalho escuro, conteúdo, PSE, comentário, botões fixos no rodapé).
    Desktop: conteúdo do treino à esquerda; à direita, painel fixo com cabeçalho, PSE, comentário e botões.
    Os wrappers usam "contents" no celular para os itens seguirem a ordem (order-*) de uma coluna só.
--}}
<div class="flex flex-col gap-4 md:grid md:grid-cols-[minmax(0,1.5fr)_minmax(340px,1fr)] md:items-start md:gap-8">
    {{-- Painel (cabeçalho + registro) --}}
    <aside aria-label="Registro do treino" class="contents md:sticky md:top-6 md:col-start-2 md:row-start-1 md:flex md:flex-col md:gap-4">
        <div class="order-1 -mx-5 -mt-6 flex flex-col gap-4 bg-ink px-5 pb-5 pt-5 text-white md:order-none md:m-0 md:rounded-[24px] md:p-6">
            <div class="flex items-center justify-between">
                <a href="{{ $date->isToday() ? route('student.home') : route('student.plan', ['semana' => $session->week->number]) }}" wire:navigate aria-label="Voltar"
                   class="flex size-11 items-center justify-center rounded-full bg-ink-2">
                    <x-icon name="back" class="size-5" stroke-width="2.5" />
                </a>
                <span class="text-[13px] text-neutral-400">{{ ucfirst($date->translatedFormat('l, j/m')) }} · Semana {{ $session->week->number }}</span>
                <span class="w-11"></span>
            </div>
            <div class="flex flex-col gap-1.5">
                <span class="text-xs font-bold tracking-wider text-[#FF8A82]">{{ Str::upper($session->type->label()) }}</span>
                <h1 class="display text-[36px]">{{ $session->title }}</h1>
                @if ($isRun)
                    <span class="text-sm text-neutral-300">Planejado: {{ $session->summary() }}{{ $session->duration && $session->paceLabel() ? ' · '.$session->duration : '' }}</span>
                @elseif ($this->sheet)
                    <span class="text-sm text-neutral-300">Exercícios em blocos: faça os do mesmo bloco em sequência e repita as séries</span>
                @endif
            </div>
            @if ($session->notes)
                <p class="rounded-xl bg-ink-2 px-3 py-2.5 text-sm text-neutral-200">{{ $session->notes }}</p>
            @endif

            @if (! $isRun && $this->sheet)
                @php $total = count($this->itemIds); @endphp
                <div class="flex flex-col gap-1.5">
                    <div class="flex justify-between text-xs text-neutral-400"><span>Progresso</span><span>{{ count($checked) }} de {{ $total }}</span></div>
                    <div class="h-1.5 rounded bg-ink-3"><div class="h-1.5 rounded bg-brand transition-all" style="width: {{ $total ? round(count($checked) / $total * 100) : 0 }}%"></div></div>
                </div>
            @endif
        </div>

        @if ($canLog)
            {{-- Esforço percebido --}}
            <div class="order-4 flex flex-col gap-2 md:order-none md:rounded-[20px] md:bg-white md:p-4">
                <div class="flex items-baseline justify-between">
                    <span class="field-label">Esforço percebido (PSE){{ $isRun ? '' : ' · opcional' }}</span>
                    <span class="text-[13px] text-muted">{{ $rpe ? $rpe.' · '.$effortLabels[$rpe] : '' }}</span>
                </div>
                <div role="radiogroup" aria-label="Esforço percebido de 1 a 10" class="grid grid-cols-10 gap-1">
                    @foreach (range(1, 10) as $value)
                        <button type="button" role="radio" aria-checked="{{ $rpe === $value ? 'true' : 'false' }}" wire:click="$set('rpe', {{ $value }})"
                                @class([
                                    'h-11 rounded-[10px] text-sm font-bold',
                                    'bg-brand text-white' => $rpe === $value,
                                    'border border-line-strong bg-white' => $rpe !== $value,
                                ])>{{ $value }}</button>
                    @endforeach
                </div>
                <div class="flex justify-between text-[11px] text-muted"><span>Muito leve</span><span>Máximo</span></div>
                @error('rpe') <span class="field-error">{{ $message }}</span> @enderror
            </div>

            <div class="order-5 flex flex-col gap-1.5 md:order-none">
                <label for="comment" class="field-label">Comentário para o treinador</label>
                <textarea id="comment" wire:model="comment" rows="3" placeholder="Ex.: senti a panturrilha no final, consegui conversar o tempo todo…"
                          class="resize-none rounded-[14px] border border-line-strong bg-white px-3.5 py-3 text-[15px] outline-none focus:border-ink"></textarea>
                @error('comment') <span class="field-error">{{ $message }}</span> @enderror
            </div>

            {{-- Ações --}}
            {{-- Rodapé fixo no celular; no desktop fica no painel --}}
            <div class="sticky bottom-0 order-6 -mx-5 mt-2 flex gap-2.5 border-t border-line bg-white px-5 pb-[max(20px,env(safe-area-inset-bottom))] pt-3 md:static md:order-none md:m-0 md:border-0 md:bg-transparent md:p-0">
                @if ($status !== 'done')
                    <button type="button" wire:click="skip" wire:confirm="Marcar este treino como não feito?" class="flex h-[54px] flex-1 items-center justify-center rounded-2xl border border-line-strong font-bold">Não fiz</button>
                @endif
                <button type="button" wire:click="complete" wire:loading.attr="disabled" class="btn-primary flex-[2]">
                    {{ $status === 'done' ? 'Salvar alterações' : ($isRun ? 'Salvar e concluir' : 'Concluir treino') }}
                </button>
            </div>
        @endif
    </aside>

    {{-- Conteúdo do treino --}}
    <div class="contents md:col-start-1 md:row-start-1 md:flex md:flex-col md:gap-4">
        @if ($status === 'done' || $status === 'skipped')
            <div role="status" @class([
                'order-2 md:order-none',
                'rounded-2xl px-4 py-3 text-sm font-semibold',
                'bg-success-soft text-success' => $status === 'done',
                'bg-white text-neutral-700' => $status === 'skipped',
            ])>
                {{ $status === 'done' ? 'Treino registrado em '.$log->completed_at->format('d/m \à\s H:i').'. Você pode ajustar e salvar de novo.' : 'Marcado como “não fiz”.' }}
            </div>
        @endif

        @unless ($canLog)
            <div class="order-2 rounded-2xl bg-white px-4 py-3 text-sm text-neutral-700 md:order-none">Este treino é para {{ $date->format('d/m') }}. O registro libera no dia.</div>
        @endunless

        <div class="order-3 flex flex-col gap-4 md:order-none">
            @if ($isRun)
                {{-- Registro da corrida --}}
                <h2 class="font-display text-xl font-bold uppercase tracking-wide">Como foi?</h2>

                <div class="grid grid-cols-2 gap-2.5">
                    <div class="flex flex-col gap-1.5">
                        <label for="distance" class="field-label">Distância (km)</label>
                        <input id="distance" wire:model.live.debounce.400ms="distance" inputmode="decimal" placeholder="5,0" @disabled(! $canLog)
                               class="h-14 min-w-0 rounded-[14px] border border-line-strong bg-white px-3.5 text-[22px] font-bold outline-none focus:border-ink">
                        @error('distance') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label for="duration" class="field-label">Tempo</label>
                        <input id="duration" wire:model.live.debounce.400ms="duration" inputmode="numeric" placeholder="43:00" @disabled(! $canLog)
                               class="h-14 min-w-0 rounded-[14px] border border-line-strong bg-white px-3.5 text-[22px] font-bold outline-none focus:border-ink">
                        @error('duration') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="flex items-center justify-between rounded-[18px] bg-ink px-4 py-3.5 text-white">
                    <div class="flex flex-col gap-0.5"><span class="text-xs text-neutral-400">Ritmo</span><span class="text-[22px] font-bold">{{ $this->pace ? $this->pace['pace'].' min/km' : '—' }}</span></div>
                    <div class="flex flex-col items-end gap-0.5"><span class="text-xs text-neutral-400">Velocidade</span><span class="text-[22px] font-bold">{{ $this->pace ? $this->pace['speed'].' km/h' : '—' }}</span></div>
                </div>
            @elseif ($this->sheet)
                {{-- Ficha de força --}}
                @foreach ($this->sheet->blocks as $block)
                    @php $isMobility = $block->section === 'mobility'; @endphp
                    <section wire:key="block-{{ $block->id }}" class="card flex flex-col gap-1.5 py-3.5">
                        <div class="flex items-center justify-between pb-1">
                            <span @class(['text-xs font-bold tracking-wider', 'text-brand-dark' => ! $isMobility, 'text-success' => $isMobility])>
                                {{ $isMobility ? 'MOBILIDADE' : 'BLOCO '.$loop->iteration }}
                            </span>
                            @if (! $isMobility && $block->sets)
                                <span class="chip bg-surface">{{ $block->sets }} {{ Str::plural('série', $block->sets) }}</span>
                            @endif
                        </div>

                        @foreach ($block->items as $item)
                            @php
                                $done = in_array($item->id, $checked, true);
                                $ex = $item->exercise;
                                $hasMedia = $ex && (filled($ex->youtube_url) || $ex->images->isNotEmpty());
                            @endphp
                            <div wire:key="item-{{ $item->id }}" class="flex items-center gap-3">
                                <label class="flex min-h-14 flex-1 cursor-pointer items-center gap-3">
                                    <input type="checkbox" wire:click="toggle({{ $item->id }})" @checked($done) @disabled(! $canLog) class="size-[22px] shrink-0 accent-brand">
                                    <span class="flex min-w-0 flex-col">
                                        <span @class(['text-[15px] font-semibold', 'text-muted line-through' => $done])>{{ $ex?->name }}</span>
                                        <span class="text-[13px] text-muted">{{ collect([$item->prescription, $item->notes])->filter()->join(' · ') }}</span>
                                    </span>
                                </label>
                                <a href="{{ route('student.exercise', [$session, $item]) }}" wire:navigate
                                   aria-label="{{ $hasMedia ? 'Ver vídeo e fotos' : 'Ver detalhes' }}: {{ $ex?->name }}"
                                   @class([
                                       'relative flex size-14 shrink-0 items-center justify-center rounded-[14px]',
                                       'bg-ink-3' => $hasMedia,
                                       'bg-surface' => ! $hasMedia,
                                   ])>
                                    @if ($hasMedia)
                                        @if ($ex->images->isNotEmpty())
                                            <img src="{{ Storage::disk('public')->url($ex->images->first()->path) }}" alt="" class="absolute inset-0 size-full rounded-[14px] object-cover opacity-60">
                                        @endif
                                        <span class="relative flex size-[26px] items-center justify-center rounded-full bg-brand">
                                            <svg aria-hidden="true" class="size-3" viewBox="0 0 24 24" fill="#FFFFFF"><path d="M8 5v14l11-7z"/></svg>
                                        </span>
                                    @else
                                        <x-icon name="chevron" class="size-[18px] text-muted" />
                                    @endif
                                </a>
                            </div>
                        @endforeach
                    </section>
                @endforeach
            @else
                <div class="card text-sm text-neutral-700">O treinador ainda não definiu a ficha deste dia.</div>
            @endif
        </div>

        {{-- Conversa com o treinador (depois do registro) --}}
        @if ($log && in_array($log->status, ['done', 'skipped'], true))
            <section aria-label="Conversa com o treinador" class="card order-3 flex flex-col gap-3 md:order-none">
                <h2 class="text-sm font-bold">Conversa com o treinador</h2>
                @if ($log->comment)
                    <div class="flex flex-row-reverse items-start gap-2">
                        <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-ink text-[10px] font-bold text-white">{{ auth()->user()->initials }}</span>
                        <p class="max-w-[85%] rounded-[14px_4px_14px_14px] bg-brand-soft px-3 py-2.5 text-sm">{{ $log->comment }}</p>
                    </div>
                @endif
                @foreach ($this->replies as $reply)
                    @php
                        $mine = $reply->user_id === auth()->id();
                    @endphp
                    <div wire:key="r-{{ $reply->id }}" @class(['flex items-start gap-2', 'flex-row-reverse' => $mine])>
                        <span @class(['flex size-7 shrink-0 items-center justify-center rounded-full text-[10px] font-bold text-white', 'bg-ink' => $mine, 'bg-brand' => ! $mine])>{{ $reply->author->initials }}</span>
                        <div @class(['flex max-w-[85%] flex-col gap-0.5', 'items-end' => $mine])>
                            <p @class(['px-3 py-2.5 text-sm', 'rounded-[14px_4px_14px_14px] bg-brand-soft' => $mine, 'rounded-[4px_14px_14px_14px] bg-surface' => ! $mine])>{{ $reply->body }}</p>
                            <span class="text-[11px] text-muted">{{ $mine ? 'Você' : $reply->author->first_name }} · {{ $reply->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                @endforeach
                @if (! $log->comment && $this->replies->isEmpty())
                    <p class="text-sm text-muted">Seu treinador vê este registro. Quer contar mais alguma coisa?</p>
                @endif
                <form wire:submit="sendMessage" class="flex items-end gap-2">
                    <label for="chat" class="sr-only">Mensagem para o treinador</label>
                    <textarea id="chat" wire:model="chat" rows="1" placeholder="Escreva para o treinador…"
                              class="min-h-11 min-w-0 flex-1 resize-y rounded-xl border border-line-strong px-3 py-2.5 text-sm outline-none focus:border-ink"></textarea>
                    <button type="submit" aria-label="Enviar mensagem" class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-brand text-white">
                        <svg aria-hidden="true" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2 11 13"/><path d="M22 2 15 22l-4-9-9-4z"/></svg>
                    </button>
                </form>
                @error('chat') <span class="field-error">{{ $message }}</span> @enderror
            </section>
        @endif
    </div>
</div>
