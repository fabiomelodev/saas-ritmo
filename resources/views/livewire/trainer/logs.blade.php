<div class="md:max-w-3xl">
    <x-page-header title="Registros" :eyebrow="$this->counts['pending'] ? $this->counts['pending'].' sem resposta' : 'Tudo respondido'">
        <button type="button" wire:click="markAllSeen" class="btn-outline">Marcar todos como vistos</button>
    </x-page-header>

    <div class="mb-4 flex flex-wrap items-center gap-2">
        <div role="tablist" aria-label="Filtrar registros" class="flex flex-wrap gap-2">
            @foreach ([
                'pending' => 'Sem resposta',
                'all' => 'Todos',
                'skipped' => 'Não fez',
                'hard' => 'PSE '.$hardRpe.'+',
            ] as $value => $label)
                <button type="button" role="tab" aria-selected="{{ $filter === $value ? 'true' : 'false' }}" wire:click="$set('filter', '{{ $value }}')"
                        @class([
                            'h-10 whitespace-nowrap rounded-full px-3.5 text-[13px] font-bold',
                            'bg-ink text-white' => $filter === $value,
                            'border border-line-strong bg-white' => $filter !== $value,
                        ])>
                    {{ $label }} · {{ $this->counts[$value] }}
                </button>
            @endforeach
        </div>
        <label for="student" class="sr-only">Filtrar por aluno</label>
        <select id="student" wire:model.live="student" class="input h-10 w-auto md:ml-auto">
            <option value="">Todos os alunos</option>
            @foreach ($this->students as $s)
                <option value="{{ $s->id }}">{{ $s->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="flex flex-col gap-3">
        @forelse ($this->logs as $log)
            <x-log-card :log="$log" :hard-rpe="$hardRpe" wire:key="log-{{ $log->id }}">
                <div class="flex flex-col gap-2 border-t border-[#EEEEEE] pt-3">
                    <div class="flex flex-wrap gap-1.5">
                        @foreach ($quickReplies as $quick)
                            <button type="button" wire:click="reply({{ $log->id }}, @js($quick))"
                                    class="h-9 rounded-full border border-line-strong bg-white px-3 text-[13px] font-semibold hover:bg-surface">{{ $quick }}</button>
                        @endforeach
                        @if (! $log->seen_by_trainer_at)
                            <button type="button" wire:click="markSeen({{ $log->id }})" class="ml-auto h-9 px-2 text-[13px] font-semibold text-muted hover:text-ink">Marcar como visto</button>
                        @endif
                    </div>
                    <form wire:submit="reply({{ $log->id }})" class="flex items-end gap-2">
                        <label for="draft-{{ $log->id }}" class="sr-only">Responder {{ $log->student->first_name }}</label>
                        <textarea id="draft-{{ $log->id }}" wire:model="drafts.{{ $log->id }}" rows="1" placeholder="Escreva uma resposta…"
                                  class="min-h-11 min-w-0 flex-1 resize-y rounded-xl border border-line-strong px-3 py-2.5 text-sm outline-none focus:border-ink"></textarea>
                        <button type="submit" aria-label="Enviar resposta" class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-brand text-white">
                            <svg aria-hidden="true" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2 11 13"/><path d="M22 2 15 22l-4-9-9-4z"/></svg>
                        </button>
                    </form>
                    @error('drafts.'.$log->id) <span class="field-error">{{ $message }}</span> @enderror
                </div>
            </x-log-card>
        @empty
            <div class="flex flex-col items-center gap-2 rounded-[20px] border-2 border-dashed border-[#CFCFCF] p-6 text-center">
                <x-icon name="chat" class="size-8 text-muted" />
                <span class="text-sm font-bold">{{ $filter === 'pending' ? 'Nenhum registro esperando resposta' : 'Nenhum registro aqui' }}</span>
                <span class="text-[13px] text-muted">Quando seus alunos registrarem treinos, eles aparecem nesta tela.</span>
            </div>
        @endforelse
    </div>

    <div class="mt-4">{{ $this->logs->links() }}</div>
</div>
