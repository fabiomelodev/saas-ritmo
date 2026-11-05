<div class="flex flex-col gap-4">
    @if (! $this->plan)
        <h1 class="display text-[34px]">Plano</h1>
        <div class="card text-sm text-neutral-700">Seu treinador ainda não publicou um plano. Ele aparece aqui assim que estiver pronto.</div>
    @else
        @php
            $plan = $this->plan;
            $week = $this->selectedWeek;
            $start = $plan->starts_on->copy()->addDays(($week->number - 1) * 7);
            $pivot = $plan->trainer->students()->whereKey(auth()->id())->first()?->pivot;
        @endphp

        <div class="flex flex-col gap-1">
            @if ($pivot?->goal || $pivot?->level)
                <span class="text-[13px] text-muted">{{ collect([$pivot->goal, $pivot->level])->filter()->join(' · ') }}</span>
            @endif
            <h1 class="display text-[34px]">{{ $plan->title }}</h1>
        </div>

        {{-- Celular: abas de semana + lista dos dias --}}
        <div class="flex flex-col gap-4 md:hidden">
        <div role="tablist" aria-label="Semanas do plano" class="grid gap-1.5 rounded-[14px] bg-white p-1" style="grid-template-columns: repeat({{ $plan->weeks->count() }}, minmax(0, 1fr))">
            @foreach ($plan->weeks as $w)
                <button type="button" role="tab" aria-selected="{{ $w->number === $week->number ? 'true' : 'false' }}"
                        wire:click="$set('week', {{ $w->number }})"
                        @class([
                            'flex h-12 flex-col items-center justify-center rounded-[10px] text-sm font-bold leading-tight',
                            'bg-ink text-white' => $w->number === $week->number,
                            'text-neutral-700' => $w->number !== $week->number,
                        ])>
                    Sem {{ $w->number }}
                    <span @class(['text-[10px] font-semibold', 'text-neutral-300' => $w->number === $week->number, 'text-muted' => $w->number !== $week->number])>{{ $w->rangeLabel() }}</span>
                </button>
            @endforeach
        </div>

        <div class="flex items-baseline justify-between">
            <span class="text-[15px] font-bold">{{ $start->format('d/m') }} a {{ $start->copy()->addDays(6)->format('d/m') }}</span>
            <span class="text-[13px] text-muted">
                {{ $week->goal_km ? 'Meta '.rtrim(rtrim(number_format($week->goal_km, 1, ',', ''), '0'), ',').' km · ' : '' }}{{ $week->sessions->reject->isRest()->count() }} treinos
            </span>
        </div>

        <div class="flex flex-col gap-2">
            @foreach ($week->sessions as $session)
                <x-session-row :session="$session" :status="$session->status()" wire:key="day-{{ $session->id }}" />
            @endforeach
        </div>
        </div>

        {{-- Desktop: o mês inteiro numa grade semanas × dias --}}
        <section aria-label="Calendário do plano" class="card hidden p-4 md:block">
            <div class="grid grid-cols-[72px_repeat(7,minmax(0,1fr))_92px] gap-2 pb-2 text-xs font-bold tracking-wider text-muted">
                <span></span>
                @foreach (['SEG', 'TER', 'QUA', 'QUI', 'SEX', 'SÁB', 'DOM'] as $dayName)
                    <span class="text-center">{{ $dayName }}</span>
                @endforeach
                <span class="text-right">KM</span>
            </div>

            <div class="flex flex-col gap-2">
                @foreach ($plan->weeks as $w)
                    @php
                        $weekStart = $plan->starts_on->copy()->addDays(($w->number - 1) * 7);
                        $doneKm = $w->sessions->sum(fn ($s) => $s->log?->status === 'done' ? (float) $s->log->distance_km : 0);
                        $isCurrentWeek = now()->betweenIncluded($weekStart->copy()->startOfDay(), $weekStart->copy()->addDays(6)->endOfDay());
                    @endphp
                    <div wire:key="cal-week-{{ $w->id }}" @class([
                        'grid grid-cols-[72px_repeat(7,minmax(0,1fr))_92px] gap-2 rounded-2xl p-1',
                        'bg-brand-soft/50' => $isCurrentWeek,
                    ])>
                        <div class="flex flex-col justify-center pl-1">
                            <span class="font-display text-[22px] font-extrabold leading-none">S{{ $w->number }}</span>
                            <span class="text-[11px] text-muted">{{ $weekStart->format('d/m') }}</span>
                        </div>

                        @foreach ($w->sessions as $s)
                            @php
                                $st = $s->status();
                                $isRest = $s->isRest();
                                $date = $s->date();
                                [$badge, $badgeClass] = match ($st) {
                                    'done' => ['Feito ✓', 'bg-success-soft text-success'],
                                    'skipped' => ['Não fez', 'bg-surface text-neutral-600'],
                                    'in_progress' => ['Em andamento', 'bg-brand-soft text-brand-dark'],
                                    'today' => ['Hoje', 'bg-brand text-white'],
                                    'pending' => ['Registrar', 'bg-brand-soft text-brand-dark'],
                                    default => [null, null],
                                };
                            @endphp
                            <{{ $isRest ? 'div' : 'a' }} @unless ($isRest) href="{{ route('student.session', $s) }}" wire:navigate @endunless
                                wire:key="cal-{{ $s->id }}"
                                title="{{ ucfirst($date->translatedFormat('l, j/m')) }}"
                                @class([
                                    'flex min-h-[108px] flex-col gap-1.5 rounded-[14px] border-2 p-2.5 text-left',
                                    'border-transparent bg-[#EDEDED]' => $isRest,
                                    'border-brand bg-white' => ! $isRest && $date->isToday(),
                                    'border-line bg-white transition hover:border-line-strong hover:shadow-sm' => ! $isRest && ! $date->isToday(),
                                ])>
                                <div class="flex items-center justify-between gap-1">
                                    <span @class(['text-xs font-bold', 'text-brand-dark' => $date->isToday(), 'text-muted' => ! $date->isToday()])>{{ $date->day }}</span>
                                    <span @class([
                                        'size-2 rounded-full',
                                        'bg-brand' => $s->type->value === 'run',
                                        'bg-ink' => $s->type->value === 'strength',
                                        'bg-[#BDBDBD]' => $isRest,
                                    ]) title="{{ $s->type->label() }}"></span>
                                </div>
                                <span class="text-[13px] font-bold leading-tight">{{ $s->title }}</span>
                                <span class="text-xs leading-snug text-neutral-600">{{ $s->summary() }}</span>
                                @if ($badge)
                                    <span class="mt-auto self-start rounded-full px-2 py-0.5 text-[10px] font-bold {{ $badgeClass }}">{{ $badge }}</span>
                                @endif
                            </{{ $isRest ? 'div' : 'a' }}>
                        @endforeach

                        <div class="flex flex-col items-end justify-center gap-0.5 pr-1">
                            <span class="text-base font-extrabold">{{ rtrim(rtrim(number_format($doneKm, 1, ',', ''), '0'), ',') ?: '0' }}</span>
                            <span class="text-[11px] text-muted">de {{ rtrim(rtrim(number_format($w->goal_km ?? $w->plannedKm(), 1, ',', ''), '0'), ',') ?: '0' }} km</span>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-3 flex flex-wrap gap-4 border-t border-[#EEEEEE] pt-3 text-xs text-neutral-700">
                <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-brand"></span>Corrida</span>
                <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-ink"></span>Força + mobilidade</span>
                <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-[#BDBDBD]"></span>Descanso</span>
                <span class="ml-auto text-muted">Clique num dia para abrir o treino</span>
            </div>
        </section>
    @endif
</div>
