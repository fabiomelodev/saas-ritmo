<div class="flex min-h-dvh flex-col">
    <div class="flex flex-col gap-4 rounded-b-[28px] bg-ink px-6 pb-6 pt-7 text-white">
        <div class="flex items-center justify-between">
            <x-logo />
            <span class="chip bg-brand text-white">PARA TREINADORES</span>
        </div>
        <h1 class="display text-[32px] leading-[1.05]">Crie sua conta e organize seus alunos</h1>
        <ul class="flex flex-col gap-2 text-[13px] text-neutral-300">
            @foreach (['Convide alunos com um link', 'Monte planos mensais de corrida e força', 'Acompanhe o que cada aluno registrou'] as $benefit)
                <li class="flex items-center gap-2.5">
                    <x-icon name="check" class="size-4 text-[#FF8A82]" stroke-width="3" />
                    {{ $benefit }}
                </li>
            @endforeach
        </ul>
    </div>

    <form wire:submit="register" class="flex flex-1 flex-col gap-3 px-6 pb-6 pt-5">
        <x-google-button :href="route('auth.google', ['intent' => 'trainer'])" />
        <x-divider>ou com e-mail</x-divider>

        <x-field name="name" label="Nome completo" wire:model="name" autocomplete="name" placeholder="Seu nome" />
        <x-field name="email" label="E-mail" type="email" wire:model="email" autocomplete="email" placeholder="voce@email.com" />
        <x-field name="password" label="Senha" type="password" wire:model="password" autocomplete="new-password" placeholder="Mínimo 8 caracteres" />

        <div class="flex-1"></div>

        <button type="submit" class="btn-primary" wire:loading.attr="disabled">Criar conta de treinador</button>
        <p class="text-center text-[13px] text-muted">Já tem conta? <a href="{{ route('login') }}" wire:navigate class="font-semibold text-brand-dark">Entrar</a></p>
    </form>
</div>
