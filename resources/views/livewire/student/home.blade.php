@php $user = auth()->user(); @endphp

<div class="flex flex-col gap-5">
    <x-flash />

    <div class="flex items-center gap-3">
        <div class="flex size-11 items-center justify-center rounded-full bg-ink text-[15px] font-bold text-white">{{ $user->initials }}</div>
        <div class="flex flex-1 flex-col gap-0.5">
            <span class="text-[13px] text-muted">{{ ucfirst(now()->translatedFormat('l, j \d\e F')) }}</span>
            <span class="text-lg font-bold md:text-2xl">Olá, {{ $user->first_name }}</span>
        </div>
        {{-- No desktop, o sino fica na barra superior; sair está em Perfil --}}
        <x-notification-bell class="md:hidden" />
    </div>

    @if (! $this->plan)
        {{-- Aguardando plano --}}
        <div class="flex flex-col gap-5 md:mx-auto md:w-full md:max-w-2xl">
        <div class="flex flex-col gap-4 rounded-3xl bg-ink p-[22px] text-white">
            <div class="flex size-[52px] items-center justify-center rounded-2xl bg-brand">
                <x-icon name="calendar-plan" class="size-[26px]" />
            </div>
            <div class="flex flex-col gap-2">
                <h1 class="display text-[32px] leading-[1.05]">Seu plano está sendo montado</h1>
                <p class="text-sm leading-relaxed text-neutral-300">
                    {{ $trainer?->name ?? 'Seu treinador' }} já recebeu seu cadastro. Ele aparece aqui assim que for publicado.
                </p>
            </div>
        </div>

        <div class="card flex flex-col gap-1">
            @foreach ([['Conta criada', true], ['Perfil completo', true], ['Plano de treino', false]] as [$step, $done])
                <div class="flex min-h-12 items-center gap-3">
                    @if ($done)
                        <span class="flex size-7 items-center justify-center rounded-full bg-success-soft text-success"><x-icon name="check" class="size-3.5" stroke-width="3" /></span>
                    @else
                        <span class="size-7 rounded-full border-2 border-dashed border-brand"></span>
                    @endif
                    <span class="flex flex-col">
                        <span class="text-[15px] font-semibold">{{ $step }}</span>
                        @unless ($done)<span class="text-[13px] text-muted">Aguardando o treinador</span>@endunless
                    </span>
                </div>
            @endforeach
        </div>

        @if ($trainer?->whatsapp)
            <a href="https://wa.me/55{{ preg_replace('/\D/', '', $trainer->whatsapp) }}" target="_blank" rel="noopener" class="card flex items-center gap-3.5">
                <span class="flex size-11 items-center justify-center rounded-[14px] bg-surface"><x-icon name="chat" /></span>
                <span class="flex flex-1 flex-col"><span class="text-[15px] font-bold">Falar com o treinador</span><span class="text-[13px] text-muted">Abre o WhatsApp</span></span>
                <x-icon name="chevron" class="size-[18px] text-muted" />
            </a>
        @endif
        </div>
    @else
        @php
            $session = $this->today;
            $status = $session?->status();
            $progress = $this->progress;
        @endphp

        {{-- Desktop: semana, treino de hoje e pendência à esquerda; progresso e próximos à direita --}}
        <div class="flex flex-col gap-5 md:grid md:grid-cols-[minmax(0,1.5fr)_minmax(300px,1fr)] md:items-start md:gap-6">
        <div class="flex flex-col gap-5">

        {{-- Semana em dias --}}
        <div class="flex flex-col gap-2.5">
            <div class="flex items-baseline justify-between">
                <span class="flex flex-wrap items-baseline gap-x-2">
                    <span class="font-display text-xl font-bold uppercase tracking-wide">Semana {{ $this->week->number }} de {{ $this->plan->weeks->count() }}</span>
                    <span class="text-[13px] font-semibold text-muted">{{ $this->week->rangeLabel() }}</span>
                </span>
                <a href="{{ route('student.plan') }}" wire:navigate class="text-[13px] font-semibold text-muted">{{ $this->plan->title }}</a>
            </div>
            <div class="grid grid-cols-7 gap-1.5">
                @foreach ($this->week->sessions as $day)
                    @php $st = $day->status(); @endphp
                    <div @class([
                        'flex flex-col items-center gap-1 rounded-xl py-2',
                        'bg-brand text-white' => $day->date()->isToday(),
                        'bg-white' => ! $day->date()->isToday(),
                    ])>
                        <span @class(['text-[11px] font-semibold', 'text-muted' => ! $day->date()->isToday()])>{{ Str::upper(Str::substr($day->date()->translatedFormat('D'), 0, 3)) }}</span>
                        <span class="text-base font-bold">{{ $day->date()->day }}</span>
                        @if ($st === 'done')
                            <x-icon name="check" class="size-3.5 text-success" stroke-width="3" />
                        @elseif ($st === 'pending')
                            <span class="my-[3px] size-2 rounded-full bg-brand" title="Registro pendente"></span>
                        @elseif ($day->date()->isToday())
                            <span class="text-[9px] font-bold tracking-wide">HOJE</span>
                        @else
                            <span class="my-[3px] size-2"></span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Treino de hoje --}}
        <div class="flex flex-col gap-4 rounded-3xl bg-ink p-[22px] text-white">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold tracking-wider text-[#FF8A82]">TREINO DE HOJE</span>
                @if ($session)
                    <span class="chip bg-ink-3 font-normal text-neutral-300">{{ $session->type->label() }}</span>
                @endif
            </div>
            <div class="flex flex-col gap-1.5">
                <h1 class="display text-[40px] md:text-[52px]">{{ $session?->title ?? 'Descanso' }}</h1>
                @if ($session && ! $session->isRest())
                    <span class="text-sm text-neutral-300">{{ $session->summary() }}</span>
                @endif
            </div>
            @if ($session?->notes)
                <p class="rounded-xl bg-ink-2 px-3 py-2.5 text-sm text-neutral-200">{{ $session->notes }}</p>
            @endif

            @if ($session && ! $session->isRest())
                @if ($status === 'done')
                    <a href="{{ route('student.session', $session) }}" wire:navigate class="flex h-[52px] items-center justify-center gap-2 rounded-2xl bg-ink-3 font-bold">
                        <x-icon name="check" class="size-5 text-[#7BD389]" stroke-width="3" /> Treino concluído · ver registro
                    </a>
                @else
                    <a href="{{ route('student.session', $session) }}" wire:navigate class="flex h-[52px] items-center justify-center gap-2 rounded-2xl bg-brand text-base font-bold transition hover:bg-brand-dark">
                        {{ $session->type->value === 'strength' ? ($status === 'in_progress' ? 'Continuar treino' : 'Começar treino') : 'Registrar corrida' }}
                        <x-icon name="arrow" class="size-[18px]" stroke-width="2.5" />
                    </a>
                @endif
            @else
                <p class="text-sm text-neutral-300">Dia de recuperação. Aproveite para descansar.</p>
            @endif
        </div>

        {{-- Pendência --}}
        @if ($pending = $this->pending)
            <div class="flex items-center gap-3 rounded-[20px] border border-brand-line bg-white p-4">
                <div class="flex size-11 shrink-0 items-center justify-center rounded-[14px] bg-brand-soft text-brand">
                    <svg aria-hidden="true" class="size-[22px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                </div>
                <div class="flex min-w-0 flex-1 flex-col gap-0.5">
                    <span class="text-sm font-bold">{{ $pending->date()->isYesterday() ? 'Ontem' : ucfirst($pending->date()->translatedFormat('l')) }}: {{ $pending->title }}</span>
                    <span class="text-[13px] text-muted">Conte ao treinador como foi</span>
                </div>
                <a href="{{ route('student.session', $pending) }}" wire:navigate class="rounded-xl bg-ink px-3.5 py-2.5 text-[13px] font-bold text-white">Registrar</a>
            </div>
        @endif


        {{-- Resposta do treinador --}}
        @if ($reply = $this->latestReply)
            <a href="{{ route('student.session', $reply->log->session) }}" wire:navigate class="card flex items-start gap-3 transition hover:ring-2 hover:ring-line-strong">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand text-[13px] font-bold text-white">{{ $reply->author->initials }}</span>
                <span class="flex min-w-0 flex-1 flex-col gap-1">
                    <span class="text-xs font-bold tracking-wider text-brand-dark">{{ Str::upper($reply->author->first_name) }} RESPONDEU · {{ Str::upper($reply->log->session->title) }}</span>
                    <span class="line-clamp-2 text-sm">{{ $reply->body }}</span>
                    <span class="text-xs text-muted">{{ $reply->created_at->diffForHumans() }}</span>
                </span>
                <x-icon name="chevron" class="mt-1 size-[18px] text-muted" />
            </a>
        @endif
        </div>

        <div class="flex flex-col gap-5 md:sticky md:top-6">
        {{-- Progresso da semana --}}
        @if ($progress['total'])
            <div class="card flex flex-col gap-3">
                <div class="flex items-baseline justify-between">
                    <span class="text-sm font-bold">Progresso da semana</span>
                    <span class="text-[13px] text-muted">
                        @if ($progress['goal'])
                            {{ rtrim(rtrim(number_format($progress['km'], 1, ',', ''), '0'), ',') }} de {{ rtrim(rtrim(number_format($progress['goal'], 1, ',', ''), '0'), ',') }} km
                        @endif
                    </span>
                </div>
                <div class="grid gap-1" style="grid-template-columns: repeat({{ $progress['total'] }}, minmax(0, 1fr))">
                    @foreach ($progress['states'] as $state)
                        <div @class(['h-2 rounded', 'bg-brand' => $state === 'done', 'bg-line' => $state !== 'done'])></div>
                    @endforeach
                </div>
                <span class="text-[13px] text-muted">{{ $progress['done'] }} de {{ $progress['total'] }} treinos concluídos</span>
            </div>
        @endif

        {{-- Próximos --}}
        @if ($this->upcoming->isNotEmpty())
            <div class="flex flex-col gap-2.5">
                <span class="font-display text-xl font-bold uppercase tracking-wide">Próximos</span>
                @foreach ($this->upcoming as $item)
                    <x-session-row :session="$item" :status="$item->status()" wire:key="up-{{ $item->id }}" />
                @endforeach
            </div>
        @endif
        </div>
        </div>
    @endif
</div>
