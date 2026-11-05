@props(['name', 'strokeWidth' => 2])

<svg {{ $attributes->merge(['class' => 'size-5']) }} aria-hidden="true" viewBox="0 0 24 24" fill="none"
     stroke="currentColor" stroke-width="{{ $strokeWidth }}" stroke-linecap="round" stroke-linejoin="round">
    @switch($name)
        @case('home')
            <path d="M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/>
            @break
        @case('calendar')
            <rect x="3" y="4" width="18" height="17" rx="2"/><path d="M3 9h18M8 2v4M16 2v4"/>
            @break
        @case('chart')
            <path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>
            @break
        @case('user')
            <circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>
            @break
        @case('users')
            <circle cx="9" cy="8" r="4"/><path d="M2 21a7 7 0 0 1 14 0M17 4a4 4 0 0 1 0 8M22 21a7 7 0 0 0-4-6.3"/>
            @break
        @case('chat')
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            @break
        @case('dumbbell')
            <path d="M6.5 6.5h11M6.5 17.5h11M3 9v6M21 9v6M6.5 4v16M17.5 4v16"/>
            @break
        @case('bolt')
            <path d="M13 4 7 13h5l-1 7 6-9h-5z"/>
            @break
        @case('back')
            <path d="m15 18-6-6 6-6"/>
            @break
        @case('chevron')
            <path d="m9 18 6-6-6-6"/>
            @break
        @case('check')
            <path d="M20 6 9 17l-5-5"/>
            @break
        @case('copy')
            <rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15V5a2 2 0 0 1 2-2h10"/>
            @break
        @case('arrow')
            <path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>
            @break
        @case('calendar-plan')
            <rect x="3" y="4" width="18" height="17" rx="2"/><path d="M3 9h18M8 2v4M16 2v4M8 14h3M8 17h6"/>
            @break
        @case('logout')
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>
            @break
    @endswitch
</svg>
