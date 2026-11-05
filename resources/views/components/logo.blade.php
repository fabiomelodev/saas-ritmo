@props(['size' => 'md'])

<div {{ $attributes->merge(['class' => 'flex items-center gap-2']) }}>
    <div @class([
        'flex items-center justify-center rounded-[10px] bg-brand text-white',
        'size-8' => $size === 'md',
        'size-9' => $size === 'lg',
    ])>
        <x-icon name="bolt" class="size-[18px]" stroke-width="2.5" />
    </div>
    <span @class([
        'font-display font-extrabold tracking-wider',
        'text-2xl' => $size === 'md',
        'text-[28px]' => $size === 'lg',
    ])>{{ Str::upper(config('app.name')) }}</span>
</div>
