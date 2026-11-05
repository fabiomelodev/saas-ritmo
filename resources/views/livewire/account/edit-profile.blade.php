@php
    $user = auth()->user();
    $back = $isTrainer ? route('trainer.account') : route('student.profile');
@endphp

<div class="md:max-w-3xl">
    <x-page-header :title="$isTrainer ? 'Editar perfil' : 'Editar dados'" :back="$back" />
    <x-flash />

    <div class="flex flex-col gap-5 md:grid md:grid-cols-[minmax(0,1.4fr)_minmax(260px,1fr)] md:items-start md:gap-6">
        <form wire:submit="save" class="flex flex-col gap-4">
            @if ($isTrainer)
                {{-- Prévia do cartão que os alunos veem no convite --}}
                <div class="flex flex-col gap-2">
                    <span class="text-xs font-bold tracking-wider text-muted">COMO SEUS ALUNOS VEEM</span>
                    <div class="flex items-center gap-3 rounded-[18px] bg-ink p-3.5 text-white">
                        <div class="flex size-12 shrink-0 items-center justify-center rounded-full bg-brand font-bold">{{ $user->initials }}</div>
                        <div class="flex min-w-0 flex-col gap-0.5">
                            <span class="text-xs text-neutral-400">Você foi convidado por</span>
                            <span class="text-base font-bold">{{ $name ?: $user->name }}</span>
                            <span class="text-xs text-neutral-400">{{ collect([$cref ? 'CREF '.$cref : null, $city ? $city.($state ? '/'.Str::upper($state) : '') : null])->filter()->join(' · ') ?: 'Complete CREF e cidade para aparecer aqui' }}</span>
                        </div>
                    </div>
                </div>
            @endif

            <div class="card flex flex-col gap-3.5">
                <x-field name="name" label="Nome completo" wire:model.live.debounce.400ms="name" autocomplete="name" />
                <x-field name="email" label="E-mail" type="email" wire:model="email" autocomplete="email"
                         hint="Se mudar, use o novo e-mail para entrar" />
                <x-field name="whatsapp" label="WhatsApp" type="tel" wire:model="whatsapp" autocomplete="tel"
                         :hint="$isTrainer ? 'Usado no botão “Falar com o treinador” dos alunos' : null" />

                @if ($isTrainer)
                    <x-field name="cref" label="CREF" wire:model.live.debounce.400ms="cref" placeholder="000000-G/UF" :optional="true" />
                    <div class="grid grid-cols-[3fr_1fr] gap-2.5">
                        <x-field name="city" label="Cidade" wire:model.live.debounce.400ms="city" />
                        <x-field name="state" label="UF" wire:model.live.debounce.400ms="state" maxlength="2" />
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label for="bio" class="field-label">Apresentação curta <span class="font-normal text-muted">(opcional)</span></label>
                        <textarea id="bio" wire:model="bio" rows="3" class="rounded-[14px] border border-line-strong bg-surface px-3.5 py-3 text-[15px] outline-none focus:border-ink"></textarea>
                        @error('bio') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                @else
                    <x-field name="birth_date" label="Data de nascimento" type="date" wire:model="birth_date" :max="now()->toDateString()" />
                    <fieldset class="flex flex-col gap-2">
                        <legend class="field-label mb-2">Sexo</legend>
                        <div class="grid grid-cols-2 gap-2.5">
                            @foreach (\App\Enums\Sex::cases() as $option)
                                <label @class([
                                    'flex h-12 cursor-pointer items-center gap-2.5 rounded-xl border-2 px-3 text-sm',
                                    'border-brand bg-brand-soft font-bold' => $sex === $option->value,
                                    'border-line-strong bg-white font-semibold' => $sex !== $option->value,
                                ])>
                                    <input type="radio" wire:model.live="sex" value="{{ $option->value }}" class="size-[18px] accent-brand">{{ $option->label() }}
                                </label>
                            @endforeach
                        </div>
                        @error('sex') <span class="field-error">{{ $message }}</span> @enderror
                    </fieldset>
                @endif
            </div>

            <div class="flex gap-2.5">
                <a href="{{ $back }}" wire:navigate class="btn-outline h-[54px] flex-1 rounded-2xl">Cancelar</a>
                <button type="submit" class="btn-primary flex-[2]" wire:loading.attr="disabled">Salvar alterações</button>
            </div>
        </form>

        {{-- Formas de entrar --}}
        <section aria-labelledby="login-title" class="flex flex-col gap-2">
            <h2 id="login-title" class="text-xs font-bold tracking-wider text-muted">FORMAS DE ENTRAR</h2>
            <div class="card flex flex-col gap-4">
                <div class="flex items-center justify-between gap-3">
                    <span class="flex flex-col">
                        <span class="text-sm font-semibold">Google</span>
                        <span @class(['text-xs', 'font-semibold text-success' => $hasGoogle, 'text-muted' => ! $hasGoogle])>{{ $hasGoogle ? 'Conectado' : 'Não conectado' }}</span>
                    </span>
                    @unless ($hasGoogle)
                        <span class="text-xs text-muted">Entre com Google usando o mesmo e-mail para conectar</span>
                    @endunless
                </div>

                <form wire:submit="updatePassword" class="flex flex-col gap-3 border-t border-[#EEEEEE] pt-4">
                    <span class="flex flex-col">
                        <span class="text-sm font-semibold">E-mail e senha</span>
                        <span class="text-xs text-muted">{{ $hasPassword ? 'Altere sua senha' : 'Sem senha: crie uma para entrar também com e-mail' }}</span>
                    </span>
                    @if ($hasPassword)
                        <x-field name="current_password" label="Senha atual" type="password" wire:model="current_password" autocomplete="current-password" />
                    @endif
                    <x-field name="password" label="Nova senha" type="password" wire:model="password" autocomplete="new-password" placeholder="Mínimo 8 caracteres" />
                    <x-field name="password_confirmation" label="Repita a nova senha" type="password" wire:model="password_confirmation" autocomplete="new-password" />
                    <button type="submit" class="btn-dark">{{ $hasPassword ? 'Alterar senha' : 'Criar senha' }}</button>
                </form>
            </div>
        </section>
    </div>
</div>
