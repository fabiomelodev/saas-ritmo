{{-- Troque o círculo pelo logotipo oficial do Google (Google Identity branding guidelines) --}}
@props(['href', 'label' => 'Continuar com Google'])

<a href="{{ $href }}" class="btn-secondary">
    <span class="flex size-6 items-center justify-center rounded-full border border-dashed border-muted text-[10px] font-bold text-muted">G</span>
    {{ $label }}
</a>
