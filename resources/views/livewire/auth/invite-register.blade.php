<div class="flex min-h-dvh flex-col">
    @if (! $this->invite)
        {{-- Link trocado pelo treinador ou convites pausados --}}
        <div class="flex flex-1 flex-col items-start gap-5 p-6 pt-10">
            <x-logo />
            <h1 class="display text-[34px]">Convite indisponível</h1>
            <p class="text-sm leading-relaxed text-neutral-600">
                Este link não está mais ativo. Peça ao seu treinador um link novo.
            </p>
            <a href="{{ route('login') }}" wire:navigate class="btn-secondary">Já tenho conta</a>
        </div>
    @else
        @php($trainer = $this->invite->user)

        <div class="flex flex-col gap-4 rounded-b-[28px] bg-ink px-6 pb-6 pt-7 text-white">
            <div class="flex items-center justify-between">
                <x-logo />
                <span class="text-xs font-bold text-neutral-400">ETAPA 1 DE 2</span>
            </div>
            <div class="flex items-center gap-3 rounded-[18px] bg-ink-2 px-3.5 py-3">
                <div class="flex size-11 shrink-0 items-center justify-center rounded-full bg-brand font-bold">{{ $trainer->initials }}</div>
                <div class="flex flex-col">
                    <span class="text-xs text-neutral-400">Você foi convidado por</span>
                    <span class="text-base font-bold">{{ $trainer->name }}</span>
                    @if ($this->invite->cref || $this->invite->city)
                        <span class="text-xs text-neutral-400">
                            {{ collect([$this->invite->cref ? 'CREF '.$this->invite->cref : null, $this->invite->city ? $this->invite->city.'/'.$this->invite->state : null])->filter()->join(' · ') }}
                        </span>
                    @endif
                </div>
            </div>
            <h1 class="display text-[32px] leading-[1.05]">Crie sua conta e receba seu plano de treino</h1>
        </div>

        <form wire:submit="register" class="flex flex-1 flex-col gap-3.5 px-6 pb-6 pt-5">
            <x-google-button :href="route('auth.google', ['intent' => 'invite', 'code' => $code])" />
            <x-divider>ou com e-mail</x-divider>

            <x-field name="name" label="Nome completo" wire:model="name" autocomplete="name" placeholder="Seu nome" />
            <x-field name="email" label="E-mail" type="email" wire:model="email" autocomplete="email" placeholder="voce@email.com" />
            <x-field name="password" label="Senha" type="password" wire:model="password" autocomplete="new-password" placeholder="Mínimo 8 caracteres" />

            <div class="flex-1"></div>

            <button type="submit" class="btn-primary" wire:loading.attr="disabled">Continuar</button>
            <p class="text-center text-[13px] text-muted">Já tem conta? <a href="{{ route('login') }}" wire:navigate class="font-semibold text-brand-dark">Entrar</a></p>
        </form>
    @endif
</div>
