<div class="md:max-w-3xl">
    <x-page-header title="Planos" eyebrow="Ciclos de 4 semanas dos seus alunos">
        <a href="{{ route('trainer.plans.create') }}" wire:navigate class="btn-red">+ Novo plano</a>
    </x-page-header>

    <div class="flex flex-col gap-2.5">
        @forelse ($this->plans as $plan)
            @php
                $published = $plan->status === \App\Enums\PlanStatus::Published;
                $km = $plan->weeks->sum(fn ($w) => $w->plannedKm());
            @endphp
            <a href="{{ route('trainer.plans.edit', $plan) }}" wire:navigate wire:key="plan-{{ $plan->id }}"
               class="card flex items-center gap-3.5 transition hover:ring-2 hover:ring-line-strong">
                <div class="flex size-11 shrink-0 items-center justify-center rounded-full bg-ink text-sm font-bold text-white">{{ $plan->student->initials }}</div>
                <div class="flex min-w-0 flex-1 flex-col gap-0.5">
                    <span class="truncate text-[15px] font-bold">{{ $plan->title }} · {{ $plan->student->first_name }}</span>
                    <span class="text-[13px] text-muted">{{ $plan->periodLabel() }} · {{ rtrim(rtrim(number_format($km, 1, ',', ''), '0'), ',') }} km planejados</span>
                </div>
                <span @class(['chip', 'bg-success-soft text-success' => $published, 'bg-surface text-neutral-700' => ! $published])>
                    {{ $published ? 'Publicado' : 'Rascunho' }}
                </span>
            </a>
        @empty
            <div class="flex flex-col items-center gap-2 rounded-[20px] border-2 border-dashed border-[#CFCFCF] p-6 text-center">
                <x-icon name="calendar-plan" class="size-8 text-muted" />
                <span class="text-sm font-bold">Nenhum plano ainda</span>
                <a href="{{ route('trainer.plans.create') }}" wire:navigate class="btn-red mt-1">Criar o primeiro plano</a>
            </div>
        @endforelse
    </div>
</div>
