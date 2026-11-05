/*
 * Push no celular (Web Push), usado pelo componente x-push-toggle:
 *   <div x-data="ritmoPush('<chave VAPID pública>')">
 * A inscrição é feita aqui no navegador e salva no servidor pelos métodos
 * Livewire savePushSubscription / removePushSubscription (via $wire).
 */
const isIos = () => /iphone|ipad|ipod/i.test(navigator.userAgent)
    || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);

const isStandalone = () => window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;

/** Chave VAPID (base64url) → bytes, como o PushManager espera */
const keyToBytes = (base64) => {
    const padded = (base64 + '='.repeat((4 - (base64.length % 4)) % 4)).replace(/-/g, '+').replace(/_/g, '/');
    return Uint8Array.from(atob(padded), (char) => char.charCodeAt(0));
};

const contentEncoding = () => {
    const supported = window.PushManager?.supportedContentEncodings ?? ['aesgcm'];
    return supported.includes('aes128gcm') ? 'aes128gcm' : 'aesgcm';
};

window.ritmoPush = (vapidKey) => ({
    /** loading | unsupported | insecure | install | unconfigured | denied | off | on */
    state: 'loading',
    busy: false,
    tested: false,
    error: null,

    async init() {
        if (! vapidKey) return (this.state = 'unconfigured');
        if (! window.isSecureContext) return (this.state = 'insecure');
        // No iPhone, o push só existe com o app instalado na tela inicial (iOS 16.4+)
        if (isIos() && ! isStandalone()) return (this.state = 'install');
        if (! ('serviceWorker' in navigator) || ! ('PushManager' in window) || ! ('Notification' in window)) {
            return (this.state = 'unsupported');
        }
        if (Notification.permission === 'denied') return (this.state = 'denied');

        const registration = await navigator.serviceWorker.ready;
        const subscription = await registration.pushManager.getSubscription();

        if (subscription && Notification.permission === 'granted') {
            // Reenvia: se outra pessoa usava este aparelho, ele passa para a conta atual
            await this.save(subscription);
            this.state = 'on';
        } else {
            this.state = 'off';
        }
    },

    async enable() {
        this.busy = true;
        this.error = null;

        try {
            const permission = await Notification.requestPermission();
            if (permission !== 'granted') {
                this.state = permission === 'denied' ? 'denied' : 'off';
                return;
            }

            const registration = await navigator.serviceWorker.ready;
            const subscription = await registration.pushManager.getSubscription()
                ?? await registration.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: keyToBytes(vapidKey) });

            await this.save(subscription);
            this.state = 'on';
        } catch (error) {
            this.error = 'Não foi possível ativar neste aparelho. Tente de novo.';
            console.error(error);
        } finally {
            this.busy = false;
        }
    },

    async disable() {
        this.busy = true;

        try {
            const registration = await navigator.serviceWorker.ready;
            const subscription = await registration.pushManager.getSubscription();

            if (subscription) {
                await this.$wire.removePushSubscription(subscription.endpoint);
                await subscription.unsubscribe();
            }
            this.state = 'off';
            this.tested = false;
        } finally {
            this.busy = false;
        }
    },

    async test() {
        this.busy = true;
        try {
            await this.$wire.sendTestPush();
            this.tested = true;
        } finally {
            this.busy = false;
        }
    },

    save(subscription) {
        const { endpoint, keys } = subscription.toJSON();
        return this.$wire.savePushSubscription(endpoint, keys.p256dh, keys.auth, contentEncoding());
    },
});
