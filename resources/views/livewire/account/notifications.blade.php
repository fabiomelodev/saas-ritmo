<div class="md:max-w-2xl">
    <x-page-header title="Notificações" :eyebrow="$this->unreadCount ? $this->unreadCount.' não lidas' : 'Tudo em dia'">
        @if ($this->unreadCount)
            <button type="button" wire:click="markAllRead" class="btn-outline">Marcar todas como lidas</button>
        @endif
    </x-page-header>

    <div class="flex flex-col gap-2.5">
        @forelse ($this->notifications as $notification)
            @php
                $data = $notification->data;
                $unread = $notification->read_at === null;
            @endphp
            <a href="{{ route('notifications.open', $notification->id) }}" wire:key="n-{{ $notification->id }}"
               @class([
                   'flex items-start gap-3 rounded-[18px] border-2 bg-white px-4 py-3.5 transition hover:border-line-strong',
                   'border-brand-line' => $unread,
                   'border-transparent' => ! $unread,
               ])>
                <span @class([
                    'flex size-10 shrink-0 items-center justify-center rounded-xl',
                    'bg-brand text-white' => $unread,
                    'bg-surface text-neutral-700' => ! $unread,
                ])>
                    <x-icon :name="$data['icon'] ?? 'bolt'" class="size-5" />
                </span>
                <span class="flex min-w-0 flex-1 flex-col gap-0.5">
                    <span @class(['text-sm', 'font-bold' => $unread, 'font-semibold' => ! $unread])>{{ $data['title'] ?? 'Aviso' }}</span>
                    @if (! empty($data['body']))
                        <span class="line-clamp-2 text-[13px] text-neutral-600">{{ $data['body'] }}</span>
                    @endif
                    <span class="text-xs text-muted">{{ $notification->created_at->diffForHumans() }}</span>
                </span>
                @if ($unread)
                    <span class="mt-1.5 size-2 shrink-0 rounded-full bg-brand" aria-label="Não lida"></span>
                @endif
            </a>
        @empty
            <div class="flex flex-col items-center gap-2 rounded-[20px] border-2 border-dashed border-[#CFCFCF] p-6 text-center">
                <svg aria-hidden="true" class="size-8 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                <span class="text-sm font-bold">Nenhuma notificação ainda</span>
            </div>
        @endforelse

        <div>{{ $this->notifications->links() }}</div>
    </div>

    <section aria-labelledby="push-title" class="mt-6 flex flex-col gap-2">
        <h2 id="push-title" class="text-xs font-bold tracking-wider text-muted">NO CELULAR</h2>
        <x-push-toggle :vapid-key="$vapidKey" :devices="$this->pushDevices" />
    </section>

    <section aria-labelledby="prefs-title" class="mt-6 flex flex-col gap-2">
        <h2 id="prefs-title" class="text-xs font-bold tracking-wider text-muted">ME AVISAR QUANDO</h2>
        <div class="card flex flex-col px-4 py-1">
            @foreach ($options as $key => [$label, $hint])
                <label class="flex min-h-[60px] cursor-pointer items-center justify-between gap-3 border-b border-[#EEEEEE] last:border-0">
                    <span class="flex flex-col gap-0.5">
                        <span class="text-sm font-semibold">{{ $label }}</span>
                        @if ($hint)<span class="text-xs text-muted">{{ $hint }}</span>@endif
                    </span>
                    <input type="checkbox" wire:model.live="prefs.{{ $key }}" class="size-[22px] shrink-0 accent-brand">
                </label>
            @endforeach
        </div>
        <p class="text-xs leading-relaxed text-muted">Vale para o sino e para o celular.</p>
    </section>
</div>
