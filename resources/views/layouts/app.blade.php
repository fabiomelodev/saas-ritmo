<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#111111">
    {{-- PWA: instalar na tela inicial --}}
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" type="image/png" sizes="32x32" href="/icons/favicon-32.png">
    <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="{{ config('app.name') }}">
    <title>{{ isset($title) ? $title.' · ' : '' }}{{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700;800&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-dvh bg-surface font-sans text-ink antialiased">
    @php($isTrainer = auth()->user()?->isTrainer())
    @php($hideNav = $hideNav ?? false)

    {{--
        Celular: coluna de app com barra inferior.
        Desktop: barra superior + página larga (treinador e aluno).
        Telas de execução (treino, registro) escondem a barra inferior com ['hideNav' => true].
    --}}
    @auth
        <x-topbar />
    @endauth

    <div class="mx-auto flex min-h-dvh w-full max-w-md flex-col bg-surface md:min-h-0 md:max-w-6xl">
        <main @class(['flex-1 px-5 pt-6 md:px-8 md:pb-12 md:pt-8', 'pb-28' => ! $hideNav, 'pb-8' => $hideNav])>
            {{ $slot }}
        </main>

        @if (auth()->check() && ! $hideNav)
            <x-bottom-nav />
        @endif
    </div>
</body>
</html>
