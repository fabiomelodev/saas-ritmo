<div class="flex flex-col gap-4 md:max-w-2xl">
    <x-flash />

    <div class="flex items-center justify-between">
        <h1 class="display text-[34px] md:text-[40px]">Perfil</h1>
        <x-notification-bell />
    </div>

    <div class="card flex items-center gap-3.5">
        <div class="flex size-[60px] shrink-0 items-center justify-center rounded-full bg-ink text-xl font-extrabold text-white">{{ $user->initials }}</div>
        <div class="flex min-w-0 flex-1 flex-col gap-0.5">
            <span class="text-base font-extrabold leading-tight">{{ $user->name }}</span>
            <span class="truncate text-[13px] text-muted">{{ $user->email }}</span>
        </div>
        <a href="{{ route('account.edit') }}" wire:navigate class="text-[13px] font-bold text-brand-dark">Editar</a>
    </div>

    @if ($trainer)
        <div class="flex items-center gap-3 rounded-[20px] bg-ink px-4 py-3.5 text-white">
            <div class="flex size-11 shrink-0 items-center justify-center rounded-full bg-brand font-bold">{{ $trainer->initials }}</div>
            <div class="flex min-w-0 flex-1 flex-col gap-0.5">
                <span class="text-xs text-neutral-400">Seu treinador</span>
                <span class="truncate text-[15px] font-bold">{{ $trainer->name }}</span>
            </div>
            @if ($trainer->whatsapp)
                <a href="https://wa.me/55{{ preg_replace('/\D/', '', $trainer->whatsapp) }}" target="_blank" rel="noopener"
                   class="flex h-10 items-center rounded-[10px] bg-ink-3 px-3 text-[13px] font-bold">WhatsApp</a>
            @endif
        </div>
    @endif

    <section class="flex flex-col gap-2">
        <h2 class="text-xs font-bold tracking-wider text-muted">MEUS DADOS</h2>
        <div class="card flex flex-col px-4 py-1">
            @foreach ([
                ['WhatsApp', $user->whatsapp ?: '—'],
                ['Nascimento', $user->birth_date?->format('d/m/Y') ?? '—'],
                ['Sexo', $user->sex?->label() ?? '—'],
            ] as [$label, $value])
                <div class="flex min-h-[52px] items-center justify-between gap-3 border-b border-[#EEEEEE] last:border-0">
                    <span class="text-sm text-neutral-600">{{ $label }}</span>
                    <span class="text-sm font-semibold">{{ $value }}</span>
                </div>
            @endforeach
        </div>
    </section>

    <section class="flex flex-col gap-2">
        <h2 class="text-xs font-bold tracking-wider text-muted">PREFERÊNCIAS</h2>
        <div class="card flex flex-col px-4 py-1">
            <a href="{{ route('notifications') }}" wire:navigate class="flex min-h-[52px] items-center justify-between border-b border-[#EEEEEE] text-sm font-semibold">Notificações <x-icon name="chevron" class="size-[18px] text-muted" /></a>
            <a href="{{ route('account.privacy') }}" wire:navigate class="flex min-h-[52px] items-center justify-between text-sm font-semibold">Privacidade e termos <x-icon name="chevron" class="size-[18px] text-muted" /></a>
        </div>
    </section>

    <x-install-app />

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="btn-secondary h-[50px]">Sair da conta</button>
    </form>
</div>
