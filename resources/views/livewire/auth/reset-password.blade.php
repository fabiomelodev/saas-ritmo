<div class="flex min-h-dvh flex-col">
    <div class="flex flex-col gap-4 rounded-b-[28px] bg-ink px-6 pb-7 pt-9 text-white">
        <x-logo />
        <h1 class="display text-[36px]">Crie uma nova senha</h1>
    </div>

    <form wire:submit="save" class="flex flex-1 flex-col gap-3.5 p-6">
        <x-field name="email" label="E-mail" type="email" wire:model="email" autocomplete="email" />
        <x-field name="password" label="Nova senha" type="password" wire:model="password" autocomplete="new-password" placeholder="Mínimo 8 caracteres" />
        <x-field name="password_confirmation" label="Repita a nova senha" type="password" wire:model="password_confirmation" autocomplete="new-password" />

        <div class="flex-1"></div>
        <button type="submit" class="btn-primary" wire:loading.attr="disabled">Salvar e entrar</button>
        <p class="text-center text-[13px] text-muted">O link expirou? <a href="{{ route('password.request') }}" wire:navigate class="font-semibold text-brand-dark">Pedir outro</a></p>
    </form>
</div>
