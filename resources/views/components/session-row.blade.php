{{-- Linha de um dia do plano: <x-session-row :session="$s" :status="$s->status()" /> --}}
@props(['session', 'status', 'showDate' => true])

@php
    $type = $session->type->value;
    $href = $type === 'rest' ? null : route('student.session', $session);

    [$badge, $badgeClass] = match ($status) {
        'done' => ['Feito ✓', 'bg-success-soft text-success border-success-soft'],
        'skipped' => ['Não fez', 'bg-white text-neutral-600 border-[#BDBDBD]'],
        'in_progress' => ['Em andamento', 'bg-brand-soft text-brand-dark border-brand-line'],
        'today' => ['Hoje', 'bg-brand text-white border-brand'],
        'pending' => ['Registrar', 'bg-brand-soft text-brand-dark border-brand-line'],
        'rest' => ['Descanso', 'bg-transparent text-neutral-600 border-[#BDBDBD]'],
        default => match ($type) {
            'run' => ['Corrida', 'bg-white text-brand-dark border-brand'],
            default => ['Força', 'bg-white text-ink border-ink'],
        },
    };

    $date = $session->date();
@endphp

<{{ $href ? 'a' : 'div' }} @if($href) href="{{ $href }}" wire:navigate @endif
    {{ $attributes->class([
        'flex min-h-12 items-center gap-3.5 rounded-[18px] border-2 px-3.5 py-3 text-ink',
        'bg-[#EAEAEA] border-transparent' => $type === 'rest',
        'bg-white border-brand' => $type !== 'rest' && $status === 'today',
        'bg-white border-transparent' => $type !== 'rest' && $status !== 'today',
    ]) }}>
    @if ($showDate)
        <div class="flex w-10 shrink-0 flex-col items-center">
            <span class="text-[11px] font-bold text-muted">{{ Str::upper(Str::substr($date->translatedFormat('D'), 0, 3)) }}</span>
            <span class="text-lg font-extrabold">{{ $date->day }}</span>
        </div>
    @endif
    <div class="flex min-w-0 flex-1 flex-col gap-0.5">
        <span class="text-[15px] font-bold">{{ $session->title }}</span>
        <span class="truncate text-[13px] text-neutral-600">{{ $session->summary() }}</span>
    </div>
    <span class="whitespace-nowrap rounded-full border px-2.5 py-1 text-[11px] font-bold {{ $badgeClass }}">{{ $badge }}</span>
</{{ $href ? 'a' : 'div' }}>
