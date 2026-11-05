{{--
    Registro do aluno (planejado × realizado, PSE, comentário e conversa).
    <x-log-card :log="$log" :show-student="true">…ações…</x-log-card>
--}}
@props(['log', 'showStudent' => true, 'hardRpe' => 8])

@php
    $session = $log->session;
    $skipped = $log->status === 'skipped';
    $pace = $log->paceMinPerKm();
    $speed = $log->speedKmh();
    $isNew = $log->seen_by_trainer_at === null;
@endphp

<article {{ $attributes->class(['card flex flex-col gap-3', 'ring-2 ring-brand' => $isNew && auth()->user()->isTrainer()]) }}>
    <div class="flex items-center gap-2.5">
        @if ($showStudent)
            <a href="{{ route('trainer.students.show', $log->student) }}" wire:navigate
               class="flex size-10 shrink-0 items-center justify-center rounded-full bg-ink text-[13px] font-bold text-white">{{ $log->student->initials }}</a>
        @endif
        <div class="flex min-w-0 flex-1 flex-col gap-0.5">
            <span class="truncate text-[15px] font-bold">
                {{ $showStudent ? $log->student->first_name.' · ' : '' }}{{ $session->title }}
            </span>
            <span class="text-xs text-muted">
                {{ ucfirst($session->date()->translatedFormat('D, j/m')) }} · Semana {{ $session->week->number }}
                @if ($log->completed_at) · registrado {{ $log->completed_at->diffForHumans() }} @endif
            </span>
        </div>
        @if ($skipped)
            <span class="chip bg-surface text-neutral-700">Não fez</span>
        @elseif ($isNew && auth()->user()->isTrainer())
            <span class="chip bg-brand text-white">Novo</span>
        @endif
    </div>

    @unless ($skipped)
        @if ($session->type->value === 'run')
            <div class="grid grid-cols-2 gap-2">
                <div class="flex flex-col gap-0.5 rounded-xl bg-surface px-3 py-2.5">
                    <span class="text-[11px] text-muted">Planejado</span>
                    <span class="text-sm font-bold">{{ $session->summary() }}</span>
                </div>
                <div class="flex flex-col gap-0.5 rounded-xl bg-ink px-3 py-2.5 text-white">
                    <span class="text-[11px] text-neutral-400">Realizado</span>
                    <span class="text-sm font-bold">
                        {{ collect([
                            $log->distance_km ? rtrim(rtrim(number_format($log->distance_km, 2, ',', ''), '0'), ',').' km' : null,
                            \App\Support\Effort::formatDuration($log->duration_seconds),
                            $pace ? $pace.' min/km' : null,
                        ])->filter()->join(' · ') ?: '—' }}
                    </span>
                    @if ($speed)<span class="text-[11px] text-neutral-400">{{ number_format($speed, 1, ',', '') }} km/h</span>@endif
                </div>
            </div>
        @else
            @php
                $total = $session->workoutSheet?->blocks()->withCount('items')->get()->sum('items_count') ?? 0;
                $done = count($log->checked_items ?? []);
            @endphp
            <div class="flex items-center justify-between rounded-xl bg-surface px-3 py-2.5 text-sm">
                <span>{{ $session->workoutSheet?->name ?? 'Ficha' }}</span>
                <span class="font-bold">{{ $done }} de {{ $total }} exercícios</span>
            </div>
        @endif

        @if ($log->rpe)
            <div class="flex items-center gap-2">
                <span class="text-[13px] font-semibold">PSE</span>
                <div class="h-2 flex-1 rounded bg-line" role="img" aria-label="Esforço {{ $log->rpe }} de 10">
                    <div @class(['h-2 rounded', 'bg-brand' => $log->rpe >= $hardRpe, 'bg-ink' => $log->rpe < $hardRpe]) style="width: {{ $log->rpe * 10 }}%"></div>
                </div>
                <span @class(['text-[13px] font-bold', 'text-brand-dark' => $log->rpe >= $hardRpe])>{{ $log->rpe }}/10 · {{ \App\Support\Effort::label($log->rpe) }}</span>
            </div>
        @endif
    @endunless

    {{-- Conversa --}}
    @if ($log->comment || $log->replies->isNotEmpty())
        <div class="flex flex-col gap-2">
            @if ($log->comment)
                <div class="flex items-start gap-2">
                    <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-ink text-[10px] font-bold text-white">{{ $log->student->initials }}</span>
                    <p class="rounded-[4px_14px_14px_14px] bg-surface px-3 py-2.5 text-sm leading-relaxed">{{ $log->comment }}</p>
                </div>
            @endif
            @foreach ($log->replies as $reply)
                @php
                    $mine = $reply->user_id === auth()->id();
                    $fromTrainer = $reply->author->isTrainer();
                @endphp
                <div wire:key="reply-{{ $reply->id }}" @class(['flex items-start gap-2', 'flex-row-reverse' => $mine])>
                    <span @class(['flex size-7 shrink-0 items-center justify-center rounded-full text-[10px] font-bold text-white', 'bg-brand' => $fromTrainer, 'bg-ink' => ! $fromTrainer])>{{ $reply->author->initials }}</span>
                    <div @class(['flex max-w-[85%] flex-col gap-0.5', 'items-end' => $mine])>
                        <p @class([
                            'px-3 py-2.5 text-sm leading-relaxed',
                            'rounded-[14px_4px_14px_14px] bg-brand-soft text-ink' => $mine,
                            'rounded-[4px_14px_14px_14px] bg-surface' => ! $mine,
                        ])>{{ $reply->body }}</p>
                        <span class="text-[11px] text-muted">{{ $reply->created_at->diffForHumans() }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{ $slot }}
</article>
