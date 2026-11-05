<div class="flex min-h-dvh flex-col">
    <div class="px-5 pt-5">
        <a href="{{ route('login') }}" wire:navigate aria-label="Voltar para entrar" class="flex size-11 items-center justify-center rounded-full bg-surface">
            <x-icon name="back" class="size-5" stroke-width="2.5" />
        </a>
    </div>

    <form wire:submit="send" class="flex flex-1 flex-col gap-[18px] p-6">
        <div class="flex size-14 items-center justify-center rounded-[18px] bg-brand-soft text-brand">
            <svg aria-hidden="true" class="size-[26px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
        </div>
        <div class="flex flex-col gap-2">
            <h1 class="display text-[36px]">Recuperar senha</h1>
            <p class="text-sm leading-relaxed text-neutral-600">Informe o e-mail da sua conta. Enviaremos um link para você criar uma nova senha.</p>
        </div>

        <x-field name="email" label="E-mail" type="email" wire:model="email" autocomplete="email" placeholder="voce@email.com" />

        <button type="submit" class="btn-primary" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="send">{{ $sent ? 'Reenviar link' : 'Enviar link' }}</span>
            <span wire:loading wire:target="send">Enviando…</span>
        </button>

        @if ($sent)
            <div role="status" class="flex items-start gap-3 rounded-2xl bg-success-soft px-4 py-3.5">
                <x-icon name="check" class="mt-0.5 size-5 shrink-0 text-success" stroke-width="2.5" />
                <span class="flex flex-col gap-1">
                    <span class="text-sm font-bold text-success">Pronto!</span>
                    <span class="text-[13px] leading-relaxed text-[#2F4F37]">Se houver uma conta com esse e-mail, o link chega em instantes. Confira também o spam. Ele vale por 60 minutos.</span>
                </span>
            </div>
        @endif

        <div class="flex-1"></div>
        <p class="text-center text-xs text-muted">Entrou com Google? Use “Entrar com Google”, sem senha.</p>
    </form>
</div>
