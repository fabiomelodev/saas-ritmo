@php
    $student = $this->student;
    $archived = $student->pivot->status === 'archived';
    $summary = $this->summary;
    $plan = $this->plan;
    $fmt = fn ($v) => rtrim(rtrim(number_format((float) $v, 1, ',', ''), '0'), ',') ?: '0';
    $maxKm = max(collect($summary['weeks'])->max(fn ($w) => max($w['goal'], $w['done'])) ?? 0, 1);
    $records = $student->personalRecords()->orderBy('distance_km')->get();
@endphp

<div>
    <x-flash />

    {{-- Cabeçalho --}}
    <div class="-mx-5 -mt-6 mb-5 flex flex-col gap-4 bg-ink px-5 pb-5 pt-5 text-white md:mx-0 md:mt-0 md:rounded-[24px] md:p-6">
        <div class="flex items-center justify-between">
            <a href="{{ route('trainer.students') }}" wire:navigate aria-label="Voltar para alunos" class="flex size-11 items-center justify-center rounded-full bg-ink-2">
                <x-icon name="back" class="size-5" stroke-width="2.5" />
            </a>
            @if ($archived)
                <span class="chip bg-ink-3 text-neutral-300">Arquivado</span>
            @endif
        </div>
        <div class="flex flex-wrap items-center gap-4">
            <div class="flex size-[60px] shrink-0 items-center justify-center rounded-full bg-brand text-xl font-extrabold">{{ $student->initials }}</div>
            <div class="flex min-w-0 flex-1 flex-col gap-1">
                <h1 class="text-lg font-extrabold leading-tight md:text-2xl">{{ $student->name }}</h1>
                <span class="text-[13px] text-neutral-300">
                    {{ collect([
                        $student->age ? $student->age.' anos' : null,
                        $student->sex?->label(),
                        'aluno desde '.$student->pivot->created_at->translatedFormat('M/y'),
                    ])->filter()->join(' · ') }}
                </span>
            </div>
            <div class="grid w-full grid-cols-2 gap-2 md:w-auto">
                @if ($student->whatsapp)
                    <a href="https://wa.me/55{{ preg_replace('/\D/', '', $student->whatsapp) }}" target="_blank" rel="noopener" class="btn-red">WhatsApp</a>
                @endif
                <a href="{{ $plan ? route('trainer.plans.edit', $plan) : route('trainer.plans.create', ['aluno' => $student->id]) }}" wire:navigate
                   class="inline-flex h-11 items-center justify-center rounded-xl bg-ink-3 px-4 text-sm font-bold text-white hover:bg-ink-2">
                    {{ $plan ? 'Abrir plano' : 'Criar plano' }}
                </a>
            </div>
        </div>
    </div>

    <div class="flex flex-col gap-5 md:grid md:grid-cols-[minmax(0,1.4fr)_minmax(300px,1fr)] md:items-start md:gap-6">
        <div class="flex flex-col gap-5">
            {{-- Plano atual --}}
            <section class="card flex flex-col gap-3">
                <div class="flex items-baseline justify-between">
                    <h2 class="text-sm font-bold">{{ $plan?->title ?? 'Sem plano ainda' }}</h2>
                    @if ($plan)
                        <span class="text-[13px] text-muted">{{ $plan->status->value === 'published' ? 'Publicado' : 'Rascunho' }}</span>
                    @endif
                </div>

                @if ($plan)
                    <div class="grid h-24 grid-cols-4 items-end gap-3" role="img"
                         aria-label="Volume por semana: {{ collect($summary['weeks'])->map(fn ($w) => 'semana '.$w['number'].' '.$fmt($w['done']).' de '.$fmt($w['goal']).' km')->join(', ') }}">
                        @foreach ($summary['weeks'] as $w)
                            <div class="flex h-full flex-col items-center justify-end gap-1">
                                <div class="relative w-full rounded-t-lg border-2 border-b-0 border-dashed border-[#BDBDBD]" style="height: {{ max($w['goal'] / $maxKm * 100, 4) }}%">
                                    <div class="absolute inset-x-0 bottom-0 rounded-t-md bg-brand" style="height: {{ $w['goal'] ? min($w['done'] / $w['goal'] * 100, 100) : 0 }}%"></div>
                                </div>
                                <span class="text-[11px] font-semibold text-muted">S{{ $w['number'] }}</span>
                            </div>
                        @endforeach
                    </div>
                    <div class="grid grid-cols-3 gap-2 border-t border-[#EEEEEE] pt-3">
                        <div class="flex flex-col"><span class="text-[11px] text-muted">Km no mês</span><span class="text-[17px] font-extrabold">{{ $fmt($summary['km']) }} / {{ $fmt($summary['goal']) }}</span></div>
                        <div class="flex flex-col"><span class="text-[11px] text-muted">Treinos</span><span class="text-[17px] font-extrabold">{{ $summary['done'] }} / {{ $summary['trainings'] }}</span></div>
                        <div class="flex flex-col"><span class="text-[11px] text-muted">PSE média</span><span class="text-[17px] font-extrabold">{{ $summary['rpe'] ? str_replace('.', ',', $summary['rpe']) : '—' }}</span></div>
                    </div>
                @else
                    <a href="{{ route('trainer.plans.create', ['aluno' => $student->id]) }}" wire:navigate class="btn-red self-start">+ Criar o primeiro plano</a>
                @endif
            </section>

            {{-- Últimos registros --}}
            <section class="flex flex-col gap-2.5">
                <div class="flex items-baseline justify-between">
                    <h2 class="font-display text-xl font-bold uppercase tracking-wide">Últimos registros</h2>
                    <a href="{{ route('trainer.logs', ['aluno' => $student->id, 'filtro' => 'all']) }}" wire:navigate class="text-[13px] font-semibold text-brand-dark">Ver todos →</a>
                </div>
                @forelse ($this->logs as $log)
                    <x-log-card :log="$log" :show-student="false" wire:key="plog-{{ $log->id }}">
                        <a href="{{ route('trainer.logs', ['aluno' => $student->id, 'filtro' => 'all']) }}" wire:navigate class="self-start text-[13px] font-bold text-brand-dark">Responder →</a>
                    </x-log-card>
                @empty
                    <p class="card text-sm text-muted">Nenhum treino registrado ainda.</p>
                @endforelse
            </section>
        </div>

        <div class="flex flex-col gap-5">
            {{-- Dados para o treino --}}
            <form wire:submit="save" class="card flex flex-col gap-3.5">
                <h2 class="text-sm font-bold">Dados para o treino</h2>
                <div class="grid grid-cols-2 gap-2 text-sm">
                    <div class="flex flex-col rounded-xl bg-surface px-3 py-2"><span class="text-[11px] text-muted">Nascimento</span><span class="font-semibold">{{ $student->birth_date?->format('d/m/Y') ?? '—' }}</span></div>
                    <div class="flex flex-col rounded-xl bg-surface px-3 py-2"><span class="text-[11px] text-muted">WhatsApp</span><span class="font-semibold">{{ $student->whatsapp ?? '—' }}</span></div>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label for="goal" class="field-label">Objetivo</label>
                    <input id="goal" wire:model="goal" placeholder="Ex.: Fortalecer e ganhar condicionamento" class="input">
                </div>
                <div class="flex flex-col gap-1.5">
                    <label for="level" class="field-label">Nível</label>
                    <input id="level" wire:model="level" list="levels" placeholder="Iniciante" class="input">
                    <datalist id="levels">
                        @foreach ($levels as $l)<option value="{{ $l }}"></option>@endforeach
                    </datalist>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label for="notes" class="field-label">Anotações privadas</label>
                    <textarea id="notes" wire:model="notes" rows="4" placeholder="Só você vê: lesões, preferências, histórico…"
                              class="resize-y rounded-xl border border-line-strong px-3 py-2.5 text-sm outline-none focus:border-ink"></textarea>
                </div>
                @error('goal') <span class="field-error">{{ $message }}</span> @enderror
                <button type="submit" class="btn-dark" wire:loading.attr="disabled">Salvar</button>
            </form>

            {{-- Recordes --}}
            <section class="card flex flex-col gap-2.5">
                <h2 class="text-sm font-bold">Recordes</h2>
                @forelse ($records as $record)
                    <div class="flex items-center justify-between rounded-xl bg-surface px-3 py-2.5">
                        <span class="text-xs font-bold text-brand-dark">{{ Str::upper($record->label) }}</span>
                        <span class="flex flex-col items-end">
                            <span class="text-base font-extrabold">{{ $record->timeLabel() }}</span>
                            <span class="text-[11px] text-muted">{{ $record->paceLabel() }}{{ $record->achieved_on ? ' · '.$record->achieved_on->format('d/m/y') : '' }}</span>
                        </span>
                    </div>
                @empty
                    <p class="text-sm text-muted">O aluno ainda não cadastrou recordes.</p>
                @endforelse
            </section>

            {{-- Vínculo --}}
            @if ($archived)
                <button type="button" wire:click="restore" class="btn-outline">Reativar aluno</button>
            @else
                <div class="flex flex-col gap-1">
                    <button type="button" wire:click="archive" wire:confirm="Arquivar {{ $student->first_name }}? Ele perde o acesso ao plano; o histórico fica guardado."
                            class="btn-outline text-brand-dark">Arquivar aluno</button>
                    <span class="text-center text-xs text-muted">Perde o acesso ao plano; o histórico fica guardado.</span>
                </div>
            @endif
        </div>
    </div>
</div>
