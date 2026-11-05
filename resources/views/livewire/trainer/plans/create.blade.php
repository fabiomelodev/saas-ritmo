<form wire:submit="create">
    <x-page-header title="Novo plano" eyebrow="Planos" :back="route('trainer.plans.index')" />

    @if ($this->students->isEmpty())
        <div class="card flex flex-col items-start gap-3">
            <p class="text-sm">Você ainda não tem alunos. Envie seu link de convite primeiro.</p>
            <a href="{{ route('trainer.students') }}" wire:navigate class="btn-dark">Ver meu link</a>
        </div>
    @else
        <div class="flex flex-wrap items-start gap-6">
            <div class="flex min-w-0 flex-[999_1_520px] flex-col gap-5">
                <section class="card flex flex-wrap gap-3.5 p-5">
                    <div class="flex flex-[2_1_220px] flex-col gap-1.5">
                        <label for="studentId" class="field-label">Aluno</label>
                        <select id="studentId" wire:model.live="studentId" class="input">
                            @foreach ($this->students as $student)
                                <option value="{{ $student->id }}">{{ $student->name }}</option>
                            @endforeach
                        </select>
                        @error('studentId') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="flex flex-[1_1_160px] flex-col gap-1.5">
                        <label for="startsOn" class="field-label">Começa na segunda</label>
                        <input id="startsOn" type="date" wire:model.live="startsOn" class="input">
                        @error('startsOn') <span class="field-error">{{ $message }}</span> @enderror
                        @if ($this->firstMonday && $this->firstMonday->toDateString() !== $startsOn)
                            <span class="text-xs font-semibold text-neutral-700">Ajustado para segunda, {{ $this->firstMonday->format('d/m') }}.</span>
                        @endif
                        @if ($this->firstMonday && $this->firstMonday->lt(today()))
                            <span class="text-xs font-semibold text-brand-dark">Começa no passado: os dias antes de hoje não poderão ser registrados pelo aluno.</span>
                        @endif
                    </div>
                    <div class="flex flex-[2_1_220px] flex-col gap-1.5">
                        <label for="title" class="field-label">Título</label>
                        <input id="title" wire:model="title" class="input">
                        @error('title') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                </section>

                <fieldset class="flex flex-col gap-2.5">
                    <legend class="mb-2.5 text-base font-extrabold">Ponto de partida</legend>
                    @foreach ([
                        'duplicate' => ['Duplicar o plano anterior', $this->previousPlan ? 'Copia as 4 semanas de “'.$this->previousPlan->title.'”, com fichas e observações.' : 'Este aluno ainda não tem plano anterior.'],
                        'blank' => ['Começar do zero', 'Grade de 4 semanas com todos os dias em descanso.'],
                    ] as $value => [$label, $hint])
                        @php($disabled = $value === 'duplicate' && ! $this->previousPlan)
                        <label @class([
                            'flex cursor-pointer items-start gap-3.5 rounded-[18px] border-2 bg-white p-4',
                            'border-brand' => $base === $value,
                            'border-line' => $base !== $value,
                            'cursor-not-allowed opacity-50' => $disabled,
                        ])>
                            <input type="radio" wire:model.live="base" value="{{ $value }}" class="mt-0.5 size-5 accent-brand" @disabled($disabled)>
                            <span class="flex flex-col gap-1">
                                <span class="flex flex-wrap items-center gap-2 text-[15px] font-bold">
                                    {{ $label }}
                                    @if ($value === 'duplicate' && $this->previousPlan)
                                        <span class="chip bg-brand-soft text-[11px] text-brand-dark">Recomendado</span>
                                    @endif
                                </span>
                                <span class="text-[13px] text-neutral-600">{{ $hint }}</span>
                            </span>
                        </label>
                    @endforeach
                </fieldset>

                <section class="card flex flex-col gap-3.5 p-5">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <span class="text-base font-extrabold">Metas semanais (km)</span>
                        <label class="flex cursor-pointer items-center gap-2 text-[13px] font-semibold">
                            <input type="checkbox" wire:model.live="increase" class="size-[18px] accent-brand">
                            Aumentar todas em
                            <input aria-label="Percentual de aumento" type="number" min="1" max="100" wire:model.live.debounce.400ms="increasePercent" class="input h-9 w-16 text-center">
                            %
                        </label>
                    </div>
                    <div class="grid grid-cols-2 gap-2.5 md:grid-cols-4">
                        @foreach ($goals as $n => $goal)
                            <div class="flex flex-col gap-1.5">
                                <label for="goal-{{ $n }}" class="flex flex-col text-xs font-bold text-muted">
                                    SEMANA {{ $n }}
                                    <span class="font-semibold normal-case text-neutral-500">{{ $this->weekRanges[$n] ?? '' }}</span>
                                </label>
                                <input id="goal-{{ $n }}" type="text" inputmode="decimal" wire:model.live.debounce.400ms="goals.{{ $n }}" placeholder="—" class="input h-12 text-lg font-bold">
                                @if ($increase && $this->finalGoals[$n] !== null)
                                    <span class="text-xs font-semibold text-brand-dark">→ {{ str_replace('.', ',', $this->finalGoals[$n]) }} km</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    @error('goals.*') <span class="field-error">{{ $message }}</span> @enderror
                </section>
            </div>

            <aside class="flex flex-[1_1_280px] flex-col gap-4 rounded-[20px] bg-ink p-5 text-white">
                <span class="text-xs font-bold tracking-wider text-[#FF8A82]">RESUMO</span>
                <div class="flex flex-col gap-1">
                    <span class="display text-[30px]">{{ $title ?: 'Novo plano' }}</span>
                    <span class="text-[13px] text-neutral-300">
                        {{ $this->students->firstWhere('id', $studentId)?->name }} ·
                        {{ $base === 'duplicate' ? 'base: plano anterior' : 'do zero' }}
                    </span>
                </div>
                @if ($this->weekRanges)
                    <ol class="flex flex-col gap-1.5 rounded-[14px] bg-ink-2 p-3 text-[13px]" aria-label="Semanas do plano">
                        @foreach ($this->weekRanges as $n => $range)
                            <li class="flex items-center justify-between gap-3">
                                <span class="font-bold">Semana {{ $n }}</span>
                                <span class="text-neutral-300">{{ $range }}</span>
                            </li>
                        @endforeach
                    </ol>
                @endif
                <div class="rounded-[14px] bg-ink-2 p-3">
                    <span class="text-[11px] text-neutral-400">Volume do plano (metas)</span>
                    <span class="block text-xl font-extrabold">{{ str_replace('.', ',', (string) collect($this->finalGoals)->filter()->sum()) }} km</span>
                </div>
                <button type="submit" class="btn-primary h-[52px] text-[15px]" wire:loading.attr="disabled">Criar e abrir no editor</button>
                <span class="text-center text-xs text-neutral-400">O plano fica como rascunho até você publicar.</span>
            </aside>
        </div>
    @endif
</form>
