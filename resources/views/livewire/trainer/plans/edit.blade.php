@php
    $plan = $this->plan;
    $published = $plan->status === \App\Enums\PlanStatus::Published;
    $sel = $this->selected;
    $monthTotal = $plan->weeks->sum(fn ($w) => $w->plannedKm());
    $monthGoal = $plan->weeks->sum('goal_km');
@endphp

<div>
    <div class="mb-5 flex flex-wrap items-end justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('trainer.plans.index') }}" wire:navigate aria-label="Voltar"
               class="flex size-11 shrink-0 items-center justify-center rounded-full border border-[#E2E2E2] bg-white">
                <x-icon name="back" class="size-5" stroke-width="2.5" />
            </a>
            <div class="flex flex-col gap-1.5">
                <div x-data="{ editing: @js($errors->has('startsOn')) }" x-on:start-date-changed.window="editing = false"
                     class="flex flex-wrap items-center gap-x-2 gap-y-1.5 text-[13px] text-muted">
                    <span>{{ $plan->student->name }} · {{ $plan->periodLabel() }}</span>
                    @unless ($published)
                        <button type="button" x-show="! editing" x-on:click="editing = true; $nextTick(() => $refs.start.focus())"
                                class="font-semibold text-brand-dark underline-offset-2 hover:underline">Alterar início</button>
                        <form x-show="editing" x-cloak wire:submit="changeStartDate" class="flex flex-wrap items-center gap-2">
                            <label for="startsOn" class="sr-only">Nova data de início</label>
                            <input id="startsOn" x-ref="start" type="date" wire:model="startsOn" class="input h-9 w-auto py-0 text-[13px]">
                            <button type="submit" class="btn-dark h-9 px-3 text-[13px]" wire:loading.attr="disabled">Salvar</button>
                            <button type="button" x-on:click="editing = false" class="text-[13px] font-semibold text-neutral-700">Cancelar</button>
                            <span class="w-full text-xs text-muted">O plano sempre começa na segunda da semana escolhida.</span>
                        </form>
                    @endunless
                    @error('startsOn') <span class="field-error w-full">{{ $message }}</span> @enderror
                </div>
                <label for="planTitle" class="sr-only">Título do plano</label>
                <input id="planTitle" wire:model="planTitle" wire:blur="saveTitle"
                       class="display w-full max-w-md bg-transparent text-[34px] outline-none focus:rounded-lg focus:bg-white md:text-[40px]">
                <div class="flex flex-wrap gap-1.5">
                    <span @class(['chip', 'bg-success-soft text-success' => $published, 'bg-white text-neutral-700 ring-1 ring-line' => ! $published])>
                        {{ $published ? 'Publicado para '.$plan->student->first_name : 'Rascunho: o aluno ainda não vê' }}
                    </span>
                </div>
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('trainer.plans.create', ['aluno' => $plan->student_id]) }}" wire:navigate class="btn-outline"
               title="Começa na segunda seguinte ao último plano de {{ $plan->student->first_name }}">+ Próximo ciclo</a>
            @if ($published)
                <button type="button" wire:click="unpublish" class="btn-outline">Voltar para rascunho</button>
            @else
                <button type="button" wire:click="deletePlan"
                        wire:confirm="Excluir o plano “{{ $plan->title }}”? As 4 semanas de treinos serão apagadas e não dá para desfazer."
                        class="btn-outline text-brand-dark">Excluir</button>
                <button type="button" wire:click="publish" wire:confirm="Publicar o plano para {{ $plan->student->first_name }}?" class="btn-red">Publicar</button>
            @endif
        </div>
    </div>

    <x-flash />

    <div class="flex flex-wrap items-start gap-6">
        {{-- Grade do plano (4 semanas, segunda a domingo) --}}
        <section aria-label="Grade do plano" class="card min-w-0 flex-[999_1_640px]">
            <div class="overflow-x-auto">
                <div class="flex min-w-[880px] flex-col gap-2">
                    <div class="grid grid-cols-[64px_repeat(7,minmax(0,1fr))_96px] gap-2 text-xs font-bold tracking-wider text-muted">
                        <span></span>
                        @foreach ($days as $d) <span class="text-center">{{ $d }}</span> @endforeach
                        <span class="text-right">VOLUME</span>
                    </div>

                    @foreach ($plan->weeks as $week)
                        @php
                            $planned = $week->plannedKm();
                            $diff = $week->goal_km !== null ? $planned - $week->goal_km : null;
                        @endphp
                        <div wire:key="week-{{ $week->id }}" class="grid grid-cols-[64px_repeat(7,minmax(0,1fr))_96px] gap-2">
                            <div class="flex flex-col justify-center">
                                <span class="font-display text-[22px] font-extrabold leading-none">S{{ $week->number }}</span>
                                <span class="text-[11px] leading-tight text-muted">{{ $week->setRelation('plan', $plan)->rangeLabel() }}</span>
                            </div>

                            @foreach ($week->sessions as $s)
                                @php
                                    $isSel = $s->id === $selectedId;
                                    $t = $s->type->value;
                                @endphp
                                <button type="button" wire:key="cell-{{ $s->id }}" wire:click="select({{ $s->id }})" aria-pressed="{{ $isSel ? 'true' : 'false' }}"
                                        @class([
                                            'flex min-h-[104px] cursor-pointer flex-col gap-1.5 rounded-[14px] border-2 p-2.5 text-left',
                                            'bg-[#EDEDED]' => $t === 'rest',
                                            'bg-white' => $t !== 'rest',
                                            'border-brand' => $isSel,
                                            'border-[#EDEDED]' => ! $isSel && $t === 'rest',
                                            'border-line hover:border-line-strong' => ! $isSel && $t !== 'rest',
                                        ])>
                                    <span @class([
                                        'self-start rounded-full border px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide',
                                        'border-brand bg-brand text-white' => $t === 'run',
                                        'border-ink bg-ink text-white' => $t === 'strength',
                                        'border-[#BDBDBD] text-neutral-600' => $t === 'rest',
                                    ])>{{ $s->type->label() }}</span>
                                    <span class="text-[13px] font-bold leading-tight">{{ $s->title }}</span>
                                    <span class="text-xs leading-snug text-neutral-600">
                                        @if ($t === 'run')
                                            {{ collect([$s->distance_km ? $fmt($s->distance_km).' km' : null, $s->paceLabel() ?? $s->duration])->filter()->join(' · ') ?: 'Livre' }}
                                        @elseif ($t === 'strength')
                                            {{ $s->workoutSheet?->name ?? 'Sem ficha' }}
                                        @else
                                            Recuperação
                                        @endif
                                    </span>
                                </button>
                            @endforeach

                            <div class="flex flex-col items-end justify-center gap-1">
                                <span class="text-lg font-extrabold">{{ $fmt($planned) }} km</span>
                                <label class="flex items-center gap-1 text-[11px] text-muted">
                                    meta
                                    <input type="text" inputmode="decimal" wire:model.blur="goals.{{ $week->id }}" aria-label="Meta da semana {{ $week->number }} em km"
                                           class="h-7 w-12 rounded-md border border-line-strong bg-white px-1 text-right text-xs font-bold text-ink">
                                </label>
                                @if ($diff !== null)
                                    <span @class(['text-[11px] font-bold', 'text-success' => abs($diff) < 0.01, 'text-brand-dark' => abs($diff) >= 0.01])>
                                        {{ abs($diff) < 0.01 ? 'Meta batida' : ($diff > 0 ? '+' : '').$fmt($diff).' km' }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="mt-3.5 flex flex-wrap justify-between gap-3 border-t border-[#EEEEEE] pt-3.5 text-xs text-neutral-700">
                <div class="flex flex-wrap gap-4">
                    <span class="flex items-center gap-1.5"><span class="size-3 rounded bg-brand"></span>Corrida</span>
                    <span class="flex items-center gap-1.5"><span class="size-3 rounded bg-ink"></span>Força + mobilidade</span>
                    <span class="flex items-center gap-1.5"><span class="size-3 rounded border border-[#BDBDBD] bg-[#EDEDED]"></span>Descanso</span>
                </div>
                <span class="font-bold text-ink">Total do mês: {{ $fmt($monthTotal) }} km{{ $monthGoal ? ' · meta '.$fmt($monthGoal).' km' : '' }}</span>
            </div>
        </section>

        {{-- Painel da sessão --}}
        @if ($sel)
            <aside aria-label="Editar sessão" class="card flex flex-[1_1_320px] flex-col gap-4 p-5">
                <div class="flex flex-col gap-0.5">
                    <span class="text-xs font-bold tracking-wider text-brand-dark">EDITAR SESSÃO</span>
                    <span class="text-xl font-extrabold">{{ $fullDays[$sel->day_of_week - 1] }}, {{ $sel->date()->format('d/m') }}</span>
                    <span class="text-xs text-muted">Semana {{ $sel->week->number }} · {{ $sel->week->rangeLabel() }}</span>
                </div>

                <div role="radiogroup" aria-label="Tipo de sessão" class="grid grid-cols-3 gap-1 rounded-xl bg-surface p-1">
                    @foreach (\App\Enums\SessionType::cases() as $option)
                        <label @class([
                            'flex h-10 cursor-pointer items-center justify-center rounded-[9px] text-[13px] font-bold',
                            'bg-ink text-white' => $type === $option->value,
                            'text-neutral-700' => $type !== $option->value,
                        ])>
                            <input type="radio" wire:model.live="type" value="{{ $option->value }}" class="sr-only">
                            {{ $option->label() }}
                        </label>
                    @endforeach
                </div>

                @if ($type === 'run')
                    <div class="flex flex-col gap-1.5">
                        <label for="title" class="field-label">Treino</label>
                        <input id="title" wire:model="title" list="run-types" class="input" placeholder="Ex.: Corrida Z2">
                        <datalist id="run-types">
                            @foreach (['Corrida Z2', 'Regenerativo', 'HIIT', 'Progressivo', 'Corrida em subida', 'Longão', 'Intervalado', 'Fartlek'] as $suggestion)
                                <option value="{{ $suggestion }}"></option>
                            @endforeach
                        </datalist>
                        @error('title') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-2.5">
                        <div class="flex flex-col gap-1.5">
                            <label for="distance_km" class="field-label">Distância (km)</label>
                            <input id="distance_km" wire:model="distance_km" inputmode="decimal" placeholder="Livre" class="input">
                            @error('distance_km') <span class="field-error">{{ $message }}</span> @enderror
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label for="duration" class="field-label">Tempo / intervalo</label>
                            <input id="duration" wire:model="duration" placeholder="Livre" class="input">
                        </div>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label for="pace" class="field-label">Ritmo</label>
                        <div class="flex gap-2">
                            <input id="pace" wire:model="pace" placeholder="Livre" class="input flex-1">
                            <div role="radiogroup" aria-label="Unidade de ritmo" class="flex gap-1 rounded-xl bg-surface p-1">
                                @foreach (['kmh' => 'km/h', 'minkm' => 'min/km'] as $unit => $unitLabel)
                                    <label @class([
                                        'flex h-9 cursor-pointer items-center rounded-[9px] px-2.5 text-xs font-bold',
                                        'bg-white text-ink shadow-sm' => $pace_unit === $unit,
                                        'text-muted' => $pace_unit !== $unit,
                                    ])>
                                        <input type="radio" wire:model.live="pace_unit" value="{{ $unit }}" class="sr-only">{{ $unitLabel }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @elseif ($type === 'strength')
                    <div class="flex flex-col gap-1.5">
                        <label for="workout_sheet_id" class="field-label">Ficha de força</label>
                        <select id="workout_sheet_id" wire:model.live="workout_sheet_id" class="input">
                            <option value="">Escolha…</option>
                            @foreach ($this->sheets as $sheet)
                                <option value="{{ $sheet->id }}">{{ $sheet->name }}</option>
                            @endforeach
                        </select>
                        @error('workout_sheet_id') <span class="field-error">{{ $message }}</span> @enderror
                        @if ($this->sheets->isEmpty())
                            <a href="{{ route('trainer.sheets.index') }}" wire:navigate class="text-[13px] font-bold text-brand-dark">Criar uma ficha primeiro →</a>
                        @elseif ($workout_sheet_id)
                            <a href="{{ route('trainer.sheets.edit', $workout_sheet_id) }}" wire:navigate class="text-[13px] font-bold text-brand-dark">Ver ficha completa →</a>
                        @endif
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label for="title" class="field-label">Nome no app do aluno</label>
                        <input id="title" wire:model="title" class="input">
                        @error('title') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                @else
                    <p class="rounded-[14px] bg-surface p-4 text-sm text-neutral-700">Dia de recuperação. O aluno vê “Descanso” no app.</p>
                @endif

                @if ($type !== 'rest')
                    <div class="flex flex-col gap-1.5">
                        <label for="notes" class="field-label">Observações para o aluno</label>
                        <textarea id="notes" wire:model="notes" rows="3" placeholder="Ex.: PSE controlada com talk test"
                                  class="rounded-xl border border-line-strong px-3 py-2.5 text-sm outline-none focus:border-ink"></textarea>
                    </div>
                @endif

                <div class="flex flex-col gap-2 border-t border-[#EEEEEE] pt-3">
                    <button type="button" wire:click="copyToAllWeeks" wire:confirm="Substituir este dia da semana em todas as semanas?" class="btn-outline">
                        Copiar para as outras semanas
                    </button>
                    <button type="button" wire:click="saveSession" class="btn-dark" wire:loading.attr="disabled">Salvar sessão</button>
                </div>
            </aside>
        @endif
    </div>
</div>
