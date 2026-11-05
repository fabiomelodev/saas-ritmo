{{-- Campo com rótulo + erro: <x-field name="email" label="E-mail" type="email" wire:model="email" /> --}}
@props(['name', 'label', 'type' => 'text', 'hint' => null, 'optional' => false])

<div class="flex flex-col gap-1.5">
    <label for="{{ $name }}" class="field-label">
        {{ $label }}
        @if ($optional)
            <span class="font-normal text-muted">(opcional)</span>
        @endif
    </label>
    <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}"
           {{ $attributes->merge(['class' => 'field-input']) }}
           @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror>
    @if ($hint)
        <span class="text-xs text-muted">{{ $hint }}</span>
    @endif
    @error($name)
        <span id="{{ $name }}-error" class="field-error">{{ $message }}</span>
    @enderror
</div>
