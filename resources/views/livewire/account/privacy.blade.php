<div class="flex flex-col gap-4 md:max-w-2xl">
    <x-page-header title="Privacidade e termos" :back="$back" />

    <div class="flex flex-col gap-2 rounded-[20px] bg-ink p-[18px] text-white">
        <span class="display text-[28px] leading-[1.05]">Seus dados são seus</span>
        <span class="text-[13px] leading-relaxed text-neutral-300">
            {{ $user->isTrainer()
                ? 'Os dados dos seus alunos ficam visíveis só para você e para eles. Não vendemos nem compartilhamos com terceiros.'
                : 'Só você e seu treinador veem seus dados de treino. Não vendemos nem compartilhamos com terceiros.' }}
        </span>
    </div>

    <section class="flex flex-col gap-2">
        <h2 class="text-xs font-bold tracking-wider text-muted">O QUE GUARDAMOS</h2>
        <div class="card flex flex-col px-4 py-1">
            @foreach ($user->isTrainer()
                ? [['Cadastro', 'Nome, e-mail e WhatsApp'], ['Perfil profissional', 'CREF, cidade e apresentação, exibidos aos alunos'], ['Treinos', 'Planos, fichas, exercícios, fotos e respostas aos alunos']]
                : [['Cadastro', 'Nome, e-mail e WhatsApp'], ['Perfil para o treino', 'Data de nascimento e sexo, para personalizar o plano'], ['Treinos', 'Distância, tempo, ritmo, PSE, comentários e recordes']] as [$heading, $text])
                <div class="flex flex-col gap-0.5 border-b border-[#EEEEEE] py-3 last:border-0">
                    <span class="text-sm font-bold">{{ $heading }}</span>
                    <span class="text-[13px] text-neutral-600">{{ $text }}</span>
                </div>
            @endforeach
        </div>
    </section>

    <section class="flex flex-col gap-2">
        <h2 class="text-xs font-bold tracking-wider text-muted">DOCUMENTOS</h2>
        <div class="card flex flex-col px-4 py-1">
            <a href="{{ route('legal.terms') }}" target="_blank" class="flex min-h-[52px] items-center justify-between border-b border-[#EEEEEE] text-sm font-semibold">Termos de uso <x-icon name="chevron" class="size-[18px] text-muted" /></a>
            <a href="{{ route('legal.privacy') }}" target="_blank" class="flex min-h-[52px] items-center justify-between text-sm font-semibold">Política de privacidade <x-icon name="chevron" class="size-[18px] text-muted" /></a>
        </div>
        @if ($user->terms_accepted_at)
            <span class="text-xs text-muted">Aceitos em {{ $user->terms_accepted_at->format('d/m/Y \à\s H:i') }}</span>
        @endif
    </section>

    <section class="flex flex-col gap-2">
        <h2 class="text-xs font-bold tracking-wider text-muted">SEUS DIREITOS (LGPD)</h2>
        <a href="{{ route('account.export') }}" class="btn-secondary h-[50px]">Baixar uma cópia dos meus dados</a>
        <a href="{{ route('account.edit') }}" wire:navigate class="btn-secondary h-[50px]">Corrigir meus dados</a>

        @if (! $deleting)
            <button type="button" wire:click="$set('deleting', true)" class="flex h-[50px] items-center justify-center rounded-2xl border-2 border-brand bg-white text-sm font-bold text-brand-dark">
                Excluir minha conta
            </button>
            <span class="text-xs leading-relaxed text-muted">A exclusão apaga a conta e os treinos de forma definitiva. Pedimos confirmação antes.</span>
        @else
            <form wire:submit="deleteAccount" class="flex flex-col gap-3 rounded-[20px] border-2 border-brand bg-white p-4">
                <span class="text-sm font-bold text-brand-dark">Excluir conta definitivamente</span>
                <span class="text-[13px] leading-relaxed text-neutral-700">
                    {{ $user->isTrainer()
                        ? 'Seus planos, fichas, exercícios e o vínculo com todos os alunos serão apagados. Os alunos continuam com as contas deles, mas sem os seus planos.'
                        : 'Seus registros de treino, recordes e conversas serão apagados. Esta ação não pode ser desfeita.' }}
                </span>
                <x-field name="confirmation" label="Digite EXCLUIR para confirmar" wire:model="confirmation" autocomplete="off" />
                @if ($user->hasPassword())
                    <x-field name="password" label="Sua senha" type="password" wire:model="password" autocomplete="current-password" />
                @endif
                <div class="flex gap-2">
                    <button type="button" wire:click="$set('deleting', false)" class="btn-outline flex-1">Cancelar</button>
                    <button type="submit" class="btn-red flex-1" wire:loading.attr="disabled">Excluir conta</button>
                </div>
            </form>
        @endif
    </section>
</div>
