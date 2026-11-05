<div class="flex min-h-dvh flex-col">
    <div class="flex flex-col gap-5 rounded-b-[28px] bg-ink px-6 pb-8 pt-10 text-white">
        <x-logo size="lg" />
        <div class="flex flex-col gap-1.5">
            <h1 class="display text-[40px]">Bem-vindo de volta</h1>
            <p class="text-sm text-neutral-300">Entre para ver seus treinos ou seus alunos</p>
        </div>
    </div>

    <form wire:submit="login" class="flex flex-1 flex-col gap-3.5 p-6">
        @if (session('status'))
            <div role="status" class="rounded-2xl bg-brand-soft px-4 py-3 text-sm font-semibold text-brand-dark">{{ session('status') }}</div>
        @endif

        <x-google-button :href="route('auth.google', ['intent' => 'login'])" label="Entrar com Google" />
        <x-divider>ou com e-mail</x-divider>

        <x-field name="email" label="E-mail" type="email" wire:model="email" autocomplete="email" placeholder="voce@email.com" />

        <div class="flex flex-col gap-1.5">
            <div class="flex items-baseline justify-between">
                <label for="password" class="field-label">Senha</label>
                <a href="{{ route('password.request') }}" wire:navigate class="text-[13px] font-semibold text-brand-dark">Esqueci minha senha</a>
            </div>
            <input id="password" type="password" wire:model="password" autocomplete="current-password" placeholder="Sua senha" class="field-input">
            @error('password') <span class="field-error">{{ $message }}</span> @enderror
        </div>

        <div class="flex-1"></div>

        <button type="submit" class="btn-primary" wire:loading.attr="disabled">
            <span wire:loading.remove>Entrar</span>
            <span wire:loading>Entrando…</span>
        </button>

        <div class="flex flex-col items-center gap-1 text-center text-[13px] text-muted">
            <span>É treinador e ainda não tem conta? <a href="{{ route('trainer.register') }}" wire:navigate class="font-semibold text-brand-dark">Criar conta</a></span>
            <span>Aluno: cadastre-se pelo link enviado pelo seu treinador.</span>
        </div>
    </form>
</div>
