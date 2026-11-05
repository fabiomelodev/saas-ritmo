<div class="flex min-h-dvh flex-col">
    <div class="px-6 pt-6">
        <div class="mb-3 flex items-center justify-between">
            <x-logo />
            <span class="text-xs font-bold text-muted">ETAPA 2 DE 2</span>
        </div>
        <div class="grid grid-cols-2 gap-1.5">
            <div class="h-1.5 rounded bg-brand"></div>
            <div class="h-1.5 rounded bg-brand"></div>
        </div>
    </div>

    <form wire:submit="save" class="flex flex-1 flex-col gap-4 p-6">
        <div class="flex flex-col gap-2">
            <h1 class="display text-[34px] leading-[1.05]">
                {{ $isTrainer ? 'Seu perfil profissional' : 'Complete seu perfil' }}
            </h1>
            <p class="text-sm leading-relaxed text-neutral-600">
                {{ $isTrainer
                    ? 'É o que seus alunos veem no convite e no app.'
                    : 'Com sua idade e sexo, o treinador monta treinos mais adequados para você.' }}
            </p>
        </div>

        <x-field name="whatsapp" label="WhatsApp" type="tel" wire:model="whatsapp" autocomplete="tel" placeholder="(00) 00000-0000"
                 :hint="$isTrainer ? 'Usado no botão “Falar com o treinador” dos alunos' : 'Para o treinador falar com você'" />

        @if ($isTrainer)
            <x-field name="cref" label="CREF" wire:model="cref" placeholder="000000-G/UF" :optional="true" />
            <div class="grid grid-cols-[3fr_1fr] gap-2.5">
                <x-field name="city" label="Cidade" wire:model="city" placeholder="Sua cidade" />
                <x-field name="state" label="UF" wire:model="state" maxlength="2" placeholder="SP" />
            </div>
            <div class="flex flex-col gap-1.5">
                <label for="bio" class="field-label">Apresentação curta <span class="font-normal text-muted">(opcional)</span></label>
                <textarea id="bio" wire:model="bio" rows="3" placeholder="Ex.: Treinador de corrida de rua, foco em iniciantes…"
                          class="rounded-[14px] border border-line-strong bg-surface px-3.5 py-3 text-[15px] outline-none focus:border-ink"></textarea>
                @error('bio') <span class="field-error">{{ $message }}</span> @enderror
            </div>
        @else
            <x-field name="birth_date" label="Data de nascimento" type="date" wire:model="birth_date" :max="now()->toDateString()" />

            <fieldset class="flex flex-col gap-2">
                <legend class="field-label mb-2">Sexo</legend>
                <div class="grid grid-cols-2 gap-2.5">
                    @foreach (\App\Enums\Sex::cases() as $option)
                        <label @class([
                            'flex h-[54px] cursor-pointer items-center gap-2.5 rounded-[14px] border-2 px-3.5 text-[15px]',
                            'border-brand bg-brand-soft font-bold' => $sex === $option->value,
                            'border-line-strong bg-white font-semibold' => $sex !== $option->value,
                        ])>
                            <input type="radio" wire:model.live="sex" value="{{ $option->value }}" class="size-5 accent-brand">
                            {{ $option->label() }}
                        </label>
                    @endforeach
                </div>
                @error('sex') <span class="field-error">{{ $message }}</span> @enderror
            </fieldset>

            <p class="rounded-[14px] bg-surface px-3.5 py-3 text-xs leading-relaxed text-neutral-600">
                Esses dados ficam visíveis só para você e seu treinador.
            </p>
        @endif

        <div class="flex flex-col gap-1">
            <label class="flex cursor-pointer items-start gap-2.5">
                <input type="checkbox" wire:model="terms" class="mt-0.5 size-5 shrink-0 accent-brand">
                <span class="text-[13px] leading-relaxed text-neutral-700">
                    Li e aceito os <a href="{{ route('legal.terms') }}" target="_blank" class="font-semibold text-brand-dark underline">Termos de uso</a> e a
                    <a href="{{ route('legal.privacy') }}" target="_blank" class="font-semibold text-brand-dark underline">Política de privacidade</a>{{ $isTrainer ? ', e me comprometo a usar os dados dos alunos só para os treinos' : '' }}.
                </span>
            </label>
            @error('terms') <span class="field-error">{{ $message }}</span> @enderror
        </div>

        <div class="flex-1"></div>

        <button type="submit" class="btn-primary" wire:loading.attr="disabled">
            {{ $isTrainer ? 'Concluir e ver meu link' : 'Concluir cadastro' }}
        </button>
    </form>
</div>
