@php
    $summary = $this->summary;
    $plan = $this->plan;
    $fmt = fn ($v) => rtrim(rtrim(number_format((float) $v, 1, ',', ''), '0'), ',') ?: '0';
    $maxKm = max(collect($summary['weeks'])->max(fn ($w) => max($w['goal'], $w['done'])) ?? 0, 1);
@endphp

<div class="flex flex-col gap-5">
    <div class="flex flex-col gap-1">
        <span class="text-[13px] text-muted">{{ $plan?->title ?? 'Sem plano publicado' }}</span>
        <h1 class="display text-[34px] md:text-[40px]">Evolução</h1>
    </div>

    <div class="flex flex-col gap-5 md:grid md:grid-cols-[minmax(0,1.4fr)_minmax(300px,1fr)] md:items-start md:gap-6">
        <div class="flex flex-col gap-5">
            {{-- Volume semanal --}}
            <section class="card flex flex-col gap-3.5">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex flex-col gap-0.5">
                        <h2 class="text-sm font-bold">Volume semanal</h2>
                        <span class="text-[13px] text-muted">Realizado × meta do treinador (km)</span>
                    </div>
                    <div class="flex flex-col items-end">
                        <span class="text-[22px] font-extrabold">{{ $fmt($summary['km']) }}<span class="text-sm font-semibold text-muted"> / {{ $fmt($summary['goal']) }} km</span></span>
                        <span class="text-xs text-muted">no plano</span>
                    </div>
                </div>

                @if ($summary['weeks'])
                    <div class="grid h-44 items-end gap-3.5 border-b border-line px-1.5" style="grid-template-columns: repeat({{ count($summary['weeks']) }}, minmax(0, 1fr))"
                         role="img" aria-label="{{ collect($summary['weeks'])->map(fn ($w) => 'Semana '.$w['number'].' ('.$w['range'].'): '.$fmt($w['done']).' de '.$fmt($w['goal']).' km')->join('; ') }}">
                        @foreach ($summary['weeks'] as $w)
                            <div class="flex h-full flex-col items-center justify-end gap-1">
                                <span @class(['text-xs', 'font-bold' => $w['done'] > 0, 'text-muted' => $w['done'] == 0])>
                                    {{ $w['done'] > 0 ? $fmt($w['done']).' / ' : '' }}{{ $fmt($w['goal']) }}
                                </span>
                                <div class="relative w-full rounded-t-[10px] border-2 border-b-0 border-dashed border-[#BDBDBD]" style="height: {{ max($w['goal'] / $maxKm * 85, 6) }}%">
                                    <div class="absolute inset-x-0 bottom-0 rounded-t-md bg-brand" style="height: {{ $w['goal'] ? min($w['done'] / $w['goal'] * 100, 100) : 0 }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="grid gap-3.5 px-1.5 text-center text-xs text-muted" style="grid-template-columns: repeat({{ count($summary['weeks']) }}, minmax(0, 1fr))">
                        @foreach ($summary['weeks'] as $w)
                            <span @class(['flex flex-col leading-tight', 'font-bold text-ink' => $w['number'] === $summary['currentWeek']])>
                                Sem {{ $w['number'] }}
                                <span class="text-[10px] font-normal text-muted">{{ $w['range'] }}</span>
                            </span>
                        @endforeach
                    </div>
                    <div class="flex gap-4 text-xs text-neutral-700">
                        <span class="flex items-center gap-1.5"><span class="size-3 rounded bg-brand"></span>Realizado</span>
                        <span class="flex items-center gap-1.5"><span class="size-2.5 rounded border-2 border-dashed border-[#BDBDBD]"></span>Meta do treinador</span>
                    </div>
                @else
                    <p class="text-sm text-muted">O gráfico aparece quando seu treinador publicar o plano.</p>
                @endif
            </section>

            <div class="grid grid-cols-2 gap-2.5">
                <div class="card flex flex-col gap-0.5 p-3.5"><span class="text-xs text-muted">Treinos concluídos</span><span class="text-[22px] font-extrabold">{{ $summary['done'] }}<span class="text-sm font-semibold text-muted"> / {{ $summary['trainings'] }}</span></span></div>
                <div class="card flex flex-col gap-0.5 p-3.5"><span class="text-xs text-muted">PSE média</span><span class="text-[22px] font-extrabold">{{ $summary['rpe'] ? str_replace('.', ',', $summary['rpe']) : '—' }}<span class="text-sm font-semibold text-muted"> {{ $summary['rpe'] ? \App\Support\Effort::label((int) round($summary['rpe'])) : '' }}</span></span></div>
            </div>

            {{-- Histórico --}}
            <section class="flex flex-col gap-2.5">
                <h2 class="font-display text-xl font-bold uppercase tracking-wide">Últimos treinos</h2>
                @forelse ($this->history as $log)
                    @php
                        $s = $log->session;
                        $trainerReplies = $log->replies->where('user_id', '!=', auth()->id())->count();
                    @endphp
                    <a href="{{ route('student.session', $s) }}" wire:navigate wire:key="h-{{ $log->id }}" class="card flex items-center gap-3 py-3 transition hover:ring-2 hover:ring-line-strong">
                        <div class="flex w-10 shrink-0 flex-col items-center">
                            <span class="text-[11px] font-bold text-muted">{{ Str::upper(Str::substr($s->date()->translatedFormat('D'), 0, 3)) }}</span>
                            <span class="text-lg font-extrabold">{{ $s->date()->day }}</span>
                        </div>
                        <div class="flex min-w-0 flex-1 flex-col gap-0.5">
                            <span class="truncate text-[15px] font-bold">{{ $s->title }}</span>
                            <span class="truncate text-[13px] text-neutral-600">
                                @if ($log->status === 'skipped')
                                    Não fez
                                @else
                                    {{ collect([
                                        $log->distance_km ? $fmt($log->distance_km).' km' : null,
                                        \App\Support\Effort::formatDuration($log->duration_seconds),
                                        $log->paceMinPerKm() ? $log->paceMinPerKm().' min/km' : null,
                                        $log->rpe ? 'PSE '.$log->rpe : null,
                                    ])->filter()->join(' · ') ?: 'Concluído' }}
                                @endif
                            </span>
                        </div>
                        @if ($trainerReplies)
                            <span class="chip bg-brand-soft text-[11px] text-brand-dark">{{ $trainerReplies }} {{ Str::plural('resposta', $trainerReplies) }}</span>
                        @endif
                    </a>
                @empty
                    <p class="card text-sm text-muted">Seus treinos registrados aparecem aqui.</p>
                @endforelse
            </section>
        </div>

        {{-- Recordes --}}
        <section aria-label="Recordes pessoais" class="flex flex-col gap-2.5 md:sticky md:top-6">
            <h2 class="font-display text-xl font-bold uppercase tracking-wide">Recordes pessoais</h2>

            <div class="grid grid-cols-2 gap-2.5">
                @forelse ($this->records as $record)
                    <div wire:key="pr-{{ $record->id }}" class="group relative flex flex-col gap-1 rounded-[18px] bg-ink p-3.5 text-white">
                        <span class="text-xs font-bold tracking-wider text-[#FF8A82]">{{ Str::upper($record->label) }}</span>
                        <span class="font-display text-[32px] font-extrabold leading-none">{{ $record->timeLabel() }}</span>
                        <span class="text-xs text-neutral-400">{{ str_replace(' min/km', '', $record->paceLabel()) }} min/km{{ $record->achieved_on ? ' · '.$record->achieved_on->format('d/m/y') : '' }}</span>
                        <button type="button" wire:click="deleteRecord({{ $record->id }})" wire:confirm="Excluir o recorde de {{ $record->label }}?"
                                aria-label="Excluir recorde de {{ $record->label }}"
                                class="absolute right-2 top-2 flex size-8 items-center justify-center rounded-full text-neutral-400 hover:bg-ink-3 hover:text-white">✕</button>
                    </div>
                @empty
                    <p class="col-span-2 rounded-[18px] border-2 border-dashed border-[#BDBDBD] p-4 text-sm text-neutral-600">
                        Cadastre seus melhores tempos. Isso ajuda o treinador a calibrar seus ritmos.
                    </p>
                @endforelse
            </div>

            @if ($adding)
                <form wire:submit="addRecord" class="card flex flex-col gap-3">
                    <div class="flex flex-col gap-1.5">
                        <label for="label" class="field-label">Prova</label>
                        <select id="label" wire:model.live="label" class="input">
                            @foreach ($distances as $name => $km)
                                <option value="{{ $name }}">{{ $name }}</option>
                            @endforeach
                            <option value="other">Outra distância</option>
                        </select>
                    </div>
                    @if ($label === 'other')
                        <div class="grid grid-cols-[2fr_1fr] gap-2">
                            <div class="flex flex-col gap-1.5">
                                <label for="customLabel" class="field-label">Nome</label>
                                <input id="customLabel" wire:model="customLabel" placeholder="Ex.: 15 km" class="input">
                                @error('customLabel') <span class="field-error">{{ $message }}</span> @enderror
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label for="customDistance" class="field-label">Km</label>
                                <input id="customDistance" wire:model="customDistance" inputmode="decimal" placeholder="15" class="input">
                                @error('customDistance') <span class="field-error">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    @endif
                    <div class="grid grid-cols-2 gap-2">
                        <div class="flex flex-col gap-1.5">
                            <label for="time" class="field-label">Tempo</label>
                            <input id="time" wire:model="time" inputmode="numeric" placeholder="34:30" class="input">
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label for="achievedOn" class="field-label">Data</label>
                            <input id="achievedOn" type="date" wire:model="achievedOn" max="{{ today()->toDateString() }}" class="input">
                        </div>
                    </div>
                    @error('time') <span class="field-error">{{ $message }}</span> @enderror
                    @error('achievedOn') <span class="field-error">{{ $message }}</span> @enderror
                    <div class="flex gap-2">
                        <button type="button" wire:click="$set('adding', false)" class="btn-outline flex-1">Cancelar</button>
                        <button type="submit" class="btn-red flex-1">Salvar recorde</button>
                    </div>
                </form>
            @else
                <button type="button" wire:click="$set('adding', true)" class="btn-secondary">+ Adicionar recorde</button>
            @endif
        </section>
    </div>
</div>
