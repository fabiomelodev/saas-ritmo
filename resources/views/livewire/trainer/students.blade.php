<div class="flex flex-col gap-[18px] md:max-w-2xl">
    <div class="flex items-center justify-between">
        <div class="flex flex-col gap-0.5">
            <span class="text-[13px] text-muted">{{ auth()->user()->name }}</span>
            <h1 class="display text-[34px]">Meus alunos</h1>
        </div>
        <x-notification-bell class="md:hidden" />
    </div>

    {{-- Link de convite --}}
    @php($url = $this->profile->inviteUrl())
    <div x-data="{ copied: false }" class="flex flex-col gap-3 rounded-[22px] bg-brand p-[18px] text-white">
        <div class="flex flex-col gap-1">
            <span class="text-base font-bold">Seu link de convite</span>
            <span class="text-[13px] text-[#FFE3E1]">Quem se cadastrar por ele já entra vinculado a você.</span>
        </div>
        <div class="flex items-center gap-2 rounded-[14px] bg-brand-dark py-1.5 pl-3.5 pr-1.5">
            <span class="flex-1 truncate text-sm font-semibold">{{ Str::after($url, '://') }}</span>
            <button type="button"
                    x-on:click="navigator.clipboard.writeText(@js($url)); copied = true; setTimeout(() => copied = false, 2000)"
                    class="flex h-10 items-center gap-1.5 rounded-[10px] bg-white px-3 text-[13px] font-bold text-brand-dark">
                <x-icon name="copy" class="size-4" />
                <span x-text="copied ? 'Copiado!' : 'Copiar'">Copiar</span>
            </button>
        </div>
        <a href="https://wa.me/?text={{ urlencode('Oi! Crie sua conta no '.config('app.name').' para receber seu plano de treino: '.$url) }}"
           target="_blank" rel="noopener"
           class="flex h-11 items-center justify-center rounded-xl bg-ink text-sm font-bold text-white">
            Enviar no WhatsApp
        </a>
    </div>

    <div class="grid grid-cols-3 gap-2">
        <div class="flex flex-col gap-0.5 rounded-2xl bg-white p-3">
            <span class="text-[11px] text-muted">Ativos</span>
            <span class="text-[22px] font-extrabold">{{ $this->students->count() }}</span>
        </div>
        <div class="flex flex-col gap-0.5 rounded-2xl bg-white p-3">
            <span class="text-[11px] text-muted">Registros hoje</span>
            <a href="{{ route('trainer.logs', ['filtro' => 'all']) }}" wire:navigate class="text-[22px] font-extrabold">{{ $this->logsToday }}</a>
        </div>
        <div class="flex flex-col gap-0.5 rounded-2xl bg-white p-3">
            <span class="text-[11px] text-muted">Sem plano</span>
            <span class="text-[22px] font-extrabold">
                {{ $this->students->filter(fn ($s) => ! $s->plans()->exists())->count() }}
            </span>
        </div>
    </div>

    <div class="flex flex-col gap-2.5">
        <div class="flex items-center justify-between gap-3">
            <h2 class="font-display text-xl font-bold uppercase tracking-wide">{{ $show === 'archived' ? 'Arquivados' : 'Alunos' }}</h2>
            <label for="busca" class="sr-only">Buscar aluno</label>
            <input id="busca" type="search" wire:model.live.debounce.300ms="search" placeholder="Buscar"
                   class="h-10 w-36 rounded-xl border border-line-strong bg-white px-3 text-sm outline-none focus:border-ink">
        </div>

        @forelse ($this->students as $student)
            @php($latestPlan = $student->plans()->where('trainer_id', auth()->id())->latest('starts_on')->first())
            <a href="{{ route('trainer.students.show', $student) }}" wire:navigate
               wire:key="student-{{ $student->id }}" class="card flex flex-col gap-3 transition hover:ring-2 hover:ring-line-strong">
                <div class="flex items-center gap-3">
                    <div class="flex size-11 shrink-0 items-center justify-center rounded-full bg-ink text-[15px] font-bold text-white">{{ $student->initials }}</div>
                    <div class="flex min-w-0 flex-1 flex-col">
                        <span class="truncate text-[15px] font-bold">{{ $student->name }}</span>
                        <span class="truncate text-[13px] text-muted">
                            {{ collect([$student->pivot->level, $student->pivot->goal, $student->age ? $student->age.' anos' : null, $student->sex?->label()])->filter()->join(' · ') ?: 'Perfil a completar' }}
                        </span>
                    </div>
                    <x-icon name="chevron" class="size-[18px] text-muted" stroke-width="2.5" />
                </div>
                <div class="flex items-center gap-2 rounded-xl bg-surface px-3 py-2.5 text-[13px]">
                    @if ($latestPlan)
                        <span class="flex-1">{{ $latestPlan->title }}</span>
                        <span @class(['chip text-[11px]', 'bg-success-soft text-success' => $latestPlan->status->value === 'published', 'bg-white text-neutral-700' => $latestPlan->status->value !== 'published'])>
                            {{ $latestPlan->status->value === 'published' ? 'Publicado' : 'Rascunho' }}
                        </span>
                    @else
                        <span class="flex-1 font-semibold text-brand-dark">Sem plano · criar plano →</span>
                    @endif
                </div>
            </a>
        @empty
            <div class="flex flex-col items-center gap-1.5 rounded-[20px] border-2 border-dashed border-[#CFCFCF] p-5 text-center">
                <x-icon name="users" class="size-7 text-muted" />
                <span class="text-sm font-bold">{{ $search ? 'Nenhum aluno encontrado' : 'Convide seus alunos' }}</span>
                <span class="text-[13px] text-muted">{{ $show === 'archived' ? 'Nenhum aluno arquivado.' : 'Compartilhe seu link e eles aparecem aqui automaticamente.' }}</span>
            </div>
        @endforelse

        @if ($this->archivedCount || $show === 'archived')
            <button type="button" wire:click="$set('show', '{{ $show === 'archived' ? 'active' : 'archived' }}')" class="self-start text-[13px] font-semibold text-muted hover:text-ink">
                {{ $show === 'archived' ? '← Voltar para os alunos ativos' : 'Ver arquivados ('.$this->archivedCount.')' }}
            </button>
        @endif
    </div>
</div>
