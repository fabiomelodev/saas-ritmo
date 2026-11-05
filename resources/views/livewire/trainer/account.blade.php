@php
    $profile = $this->profile;
    $url = $profile->inviteUrl();
@endphp

<div class="flex flex-col gap-4 md:max-w-2xl">
    <x-flash />

    <div class="flex items-center justify-between">
        <h1 class="display text-[34px] md:text-[40px]">Conta</h1>
        <x-notification-bell />
    </div>

    <div class="card flex items-center gap-3.5">
        <div class="flex size-14 shrink-0 items-center justify-center rounded-full bg-brand text-lg font-extrabold text-white">{{ $user->initials }}</div>
        <div class="flex min-w-0 flex-1 flex-col gap-0.5">
            <span class="text-base font-extrabold">{{ $user->name }}</span>
            <span class="text-[13px] text-muted">
                {{ collect([$profile->cref ? 'CREF '.$profile->cref : null, $profile->city ? $profile->city.($profile->state ? '/'.$profile->state : '') : null])->filter()->join(' · ') ?: $user->email }}
            </span>
        </div>
        <a href="{{ route('account.edit') }}" wire:navigate class="text-[13px] font-bold text-brand-dark">Editar</a>
    </div>

    {{-- Link de convite --}}
    <section class="flex flex-col gap-2">
        <h2 class="text-xs font-bold tracking-wider text-muted">LINK DE CONVITE</h2>
        <div class="card flex flex-col gap-3" x-data="{ copied: false }">
            <label class="flex cursor-pointer items-center justify-between gap-3">
                <span class="flex flex-col gap-0.5">
                    <span class="text-sm font-bold">Aceitando novos alunos</span>
                    <span class="text-xs text-muted">Desligado, o link mostra “convite indisponível”</span>
                </span>
                <input type="checkbox" wire:click="toggleInvites" @checked($profile->accepting_invites) class="size-[22px] shrink-0 accent-brand">
            </label>
            <div class="flex items-center gap-2 rounded-xl bg-surface py-1.5 pl-3 pr-1.5">
                <span class="flex-1 truncate text-[13px] font-semibold">{{ Str::after($url, '://') }}</span>
                <span @class(['chip text-[11px]', 'bg-success-soft text-success' => $profile->accepting_invites, 'bg-white text-neutral-600' => ! $profile->accepting_invites])>
                    {{ $profile->accepting_invites ? 'Ativo' : 'Pausado' }}
                </span>
                <button type="button" x-on:click="navigator.clipboard.writeText(@js($url)); copied = true; setTimeout(() => copied = false, 2000)"
                        class="h-9 rounded-lg bg-white px-3 text-xs font-bold text-brand-dark" x-text="copied ? 'Copiado!' : 'Copiar'">Copiar</button>
            </div>
            <button type="button" wire:click="regenerateInvite" wire:confirm="Gerar um novo link? O link atual para de funcionar; quem já é aluno continua vinculado."
                    class="btn-outline">Gerar novo link</button>
            <span class="text-xs leading-relaxed text-muted">Use se o link foi compartilhado com quem não devia. Quem já é seu aluno continua vinculado.</span>
        </div>
    </section>

    <section class="flex flex-col gap-2">
        <h2 class="text-xs font-bold tracking-wider text-muted">ALUNOS</h2>
        <div class="card flex flex-col px-4 py-1">
            <a href="{{ route('trainer.students') }}" wire:navigate class="flex min-h-[52px] items-center justify-between border-b border-[#EEEEEE] text-sm font-semibold">Ativos <span class="text-muted">{{ $this->counts['active'] }}</span></a>
            <a href="{{ route('trainer.students', ['mostrar' => 'archived']) }}" wire:navigate class="flex min-h-[52px] items-center justify-between text-sm">
                <span class="flex flex-col"><span class="font-semibold">Arquivados</span><span class="text-xs text-muted">Sem acesso ao plano; histórico guardado</span></span>
                <span class="text-muted">{{ $this->counts['archived'] }}</span>
            </a>
        </div>
    </section>

    <section class="flex flex-col gap-2">
        <h2 class="text-xs font-bold tracking-wider text-muted">CONTA</h2>
        <div class="card flex flex-col px-4 py-1">
            <a href="{{ route('notifications') }}" wire:navigate class="flex min-h-[52px] items-center justify-between border-b border-[#EEEEEE] text-sm font-semibold">Notificações <x-icon name="chevron" class="size-[18px] text-muted" /></a>
            <a href="{{ route('trainer.categories') }}" wire:navigate class="flex min-h-[52px] items-center justify-between border-b border-[#EEEEEE] text-sm font-semibold">Categorias de exercício <x-icon name="chevron" class="size-[18px] text-muted" /></a>
            <a href="{{ route('account.privacy') }}" wire:navigate class="flex min-h-[52px] items-center justify-between text-sm font-semibold">Privacidade e termos <x-icon name="chevron" class="size-[18px] text-muted" /></a>
        </div>
    </section>

    <x-install-app />

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="btn-secondary h-[50px]">Sair da conta</button>
    </form>
</div>
