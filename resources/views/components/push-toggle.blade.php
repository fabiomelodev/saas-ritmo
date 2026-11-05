@props(['vapidKey' => null, 'devices' => 0])

{{--
    Ativar/desativar o push neste aparelho (lógica em resources/js/push.js).
    Precisa estar dentro de um componente Livewire com savePushSubscription,
    removePushSubscription e sendTestPush (App\Livewire\Account\Notifications).
--}}
<div x-data="ritmoPush(@js($vapidKey))" wire:ignore.self {{ $attributes->class('card flex flex-col gap-3 p-4') }}>
    <div class="flex items-start gap-3.5">
        <span class="flex size-11 shrink-0 items-center justify-center rounded-[14px] text-white transition"
              :class="state === 'on' ? 'bg-brand' : 'bg-ink'">
            <svg aria-hidden="true" class="size-[22px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
        </span>
        <div class="flex min-w-0 flex-1 flex-col gap-1">
            <span class="text-[15px] font-bold">Avisos com o app fechado</span>

            <span class="text-[13px] leading-relaxed text-neutral-600" x-show="state === 'loading'">Verificando este aparelho…</span>
            <span class="text-[13px] leading-relaxed text-neutral-600" x-show="state === 'off'" x-cloak>Receba lembretes de treino e respostas como notificação do celular.</span>
            <span class="text-[13px] leading-relaxed text-neutral-600" x-show="state === 'on'" x-cloak>
                <strong class="font-semibold text-ink">Ativado neste aparelho.</strong>
                @if ($devices > 1)
                    Você recebe em {{ $devices }} aparelhos.
                @endif
            </span>
            <span class="text-[13px] leading-relaxed text-neutral-600" x-show="state === 'install'" x-cloak>
                No iPhone, primeiro instale o app: toque em <strong class="font-semibold text-ink">Compartilhar</strong> › <strong class="font-semibold text-ink">Adicionar à Tela de Início</strong>, abra o {{ config('app.name') }} pelo ícone e volte aqui. Requer iOS 16.4 ou mais novo.
            </span>
            <span class="text-[13px] leading-relaxed text-neutral-600" x-show="state === 'denied'" x-cloak>
                As notificações estão bloqueadas para este site. Libere nas configurações do navegador (ícone ao lado do endereço) e recarregue a página.
            </span>
            <span class="text-[13px] leading-relaxed text-neutral-600" x-show="state === 'unsupported'" x-cloak>Este navegador não recebe notificações push. No celular, use o Chrome (Android) ou o app instalado (iPhone).</span>
            <span class="text-[13px] leading-relaxed text-neutral-600" x-show="state === 'insecure'" x-cloak>O push só funciona em endereço seguro (https).</span>
            <span class="text-[13px] leading-relaxed text-neutral-600" x-show="state === 'unconfigured'" x-cloak>O push ainda não foi configurado no servidor (chaves VAPID).</span>

            <span class="text-[13px] font-semibold text-brand-dark" x-show="error" x-text="error" x-cloak role="alert"></span>
            <span class="text-[13px] font-semibold text-success" x-show="tested" x-cloak role="status">Enviado! Deve chegar em alguns segundos.</span>
        </div>
    </div>

    <div class="flex flex-wrap gap-2" x-show="state === 'off' || state === 'on'" x-cloak>
        <button type="button" class="btn-red flex-1" x-show="state === 'off'" x-on:click="enable()" x-bind:disabled="busy">
            <span x-show="! busy">Ativar neste aparelho</span>
            <span x-show="busy">Ativando…</span>
        </button>
        <button type="button" class="btn-outline flex-1" x-show="state === 'on'" x-on:click="test()" x-bind:disabled="busy">Enviar teste</button>
        <button type="button" class="btn-outline flex-1" x-show="state === 'on'" x-on:click="disable()" x-bind:disabled="busy">Desativar</button>
    </div>
</div>
