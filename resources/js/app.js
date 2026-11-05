import './push';

/*
 * PWA: registra o service worker e guarda o convite de instalação
 * (usado pelo cartão "Instale o Ritmo", componente x-install-app).
 */
if ('serviceWorker' in navigator && window.isSecureContext) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {});
    });
}

window.ritmoInstallPrompt = null;

window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    window.ritmoInstallPrompt = event;
    window.dispatchEvent(new CustomEvent('ritmo-install-available'));
});

window.addEventListener('appinstalled', () => {
    window.ritmoInstallPrompt = null;
});
