{{-- Página de texto legal: @include('legal.page', ['title' => …, 'sections' => [...]]) --}}
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#111111">
    <title>{{ $title }} · {{ config('app.name') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@700;800&family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>
<body class="bg-surface font-sans text-ink antialiased">
    <header class="bg-ink px-6 py-4 text-white">
        <div class="mx-auto flex max-w-3xl items-center justify-between">
            <a href="{{ route('home') }}"><x-logo /></a>
            <a href="{{ url()->previous() }}" class="text-sm font-semibold text-neutral-300 hover:text-white">← Voltar</a>
        </div>
    </header>

    <main class="mx-auto flex max-w-3xl flex-col gap-6 px-6 py-8">
        <div class="flex flex-col gap-2">
            <h1 class="display text-[40px]">{{ $title }}</h1>
            <p class="text-sm text-muted">Última atualização: {{ $updated }}</p>
        </div>

        {{-- Rascunho: o texto definitivo deve ser revisado por um advogado antes de lançar --}}
        <div role="note" class="rounded-2xl border border-brand-line bg-brand-soft px-4 py-3 text-sm font-semibold text-brand-dark">
            Modelo provisório. Revise este texto com um advogado antes de abrir a plataforma ao público.
        </div>

        @foreach ($sections as $heading => $paragraphs)
            <section class="card flex flex-col gap-2.5 p-5">
                <h2 class="text-lg font-extrabold">{{ $loop->iteration }}. {{ $heading }}</h2>
                @foreach ((array) $paragraphs as $paragraph)
                    <p class="text-[15px] leading-relaxed text-neutral-700">{{ $paragraph }}</p>
                @endforeach
            </section>
        @endforeach
    </main>
</body>
</html>
