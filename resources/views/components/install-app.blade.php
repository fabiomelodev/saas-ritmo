{{--
    Cartão "Instalar o app" (PWA).
    - Android/Chrome/Edge: usa o evento beforeinstallprompt capturado em resources/js/app.js
    - iPhone (Safari): mostra o passo a passo (Compartilhar › Adicionar à Tela de Início)
    - Já instalado (modo standalone): não aparece
--}}
<div x-data="{
        installed: window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true,
        canPrompt: !! window.ritmoInstallPrompt,
        ios: /iphone|ipad|ipod/i.test(navigator.userAgent),
        async install() {
            const prompt = window.ritmoInstallPrompt;
            if (! prompt) return;
            prompt.prompt();
            const { outcome } = await prompt.userChoice;
            if (outcome === 'accepted') { this.installed = true }
            window.ritmoInstallPrompt = null;
            this.canPrompt = false;
        },
     }"
     x-on:ritmo-install-available.window="canPrompt = true"
     x-show="! installed && (canPrompt || ios)" x-cloak
     {{ $attributes->class('card flex items-start gap-3.5') }}>
    <span class="flex size-11 shrink-0 items-center justify-center rounded-[14px] bg-brand text-white">
        <x-icon name="bolt" class="size-[22px]" stroke-width="2.5" />
    </span>
    <div class="flex min-w-0 flex-1 flex-col gap-1.5">
        <span class="text-[15px] font-bold">Instale o {{ config('app.name') }} no celular</span>
        <span class="text-[13px] leading-relaxed text-neutral-600" x-show="canPrompt">Abre em tela cheia, como um aplicativo, direto da tela inicial.</span>
        <span class="text-[13px] leading-relaxed text-neutral-600" x-show="! canPrompt && ios">
            No Safari, toque em <strong>Compartilhar</strong> (o quadrado com a seta) e depois em <strong>Adicionar à Tela de Início</strong>.
        </span>
        <button type="button" x-show="canPrompt" x-on:click="install()" class="btn-red mt-1 self-start">Instalar</button>
    </div>
</div>
