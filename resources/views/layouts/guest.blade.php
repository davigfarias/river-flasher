<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $title ?? config('app.name') }}</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen flex flex-col bg-background text-on-background font-sans text-body-md antialiased selection:bg-primary-container selection:text-on-primary-container">
        <div class="fixed inset-0 overflow-hidden pointer-events-none">
            <div class="absolute -top-40 -left-40 w-96 h-96 bg-primary-container opacity-5 rounded-full blur-[100px]"></div>
            <div class="absolute -bottom-40 -right-40 w-96 h-96 bg-secondary-container opacity-5 rounded-full blur-[100px]"></div>
        </div>

        <main class="relative z-10 flex-1 flex items-center justify-center p-4 md:p-12">
            {{ $slot }}
        </main>

        <footer class="shrink-0">
    <div class="mx-auto flex items-center justify-center gap-2 px-6 py-4 text-sm text-on-surface-variant">

        <span>Desenvolvido orgulhosamente em</span>

        <span class="flex items-center gap-1 font-semibold text-red-500">
                <img
                    src="https://upload.wikimedia.org/wikipedia/commons/9/9a/Laravel.svg"
                    alt="Laravel"
                    class="h-4 w-4"
                >
                Laravel
            </span>

        <span>por</span>

        <a
            href="https://github.com/DaveFarias"
            target="_blank"
            rel="noopener noreferrer"
            class="flex items-center gap-1 font-semibold hover:opacity-80"
        >
            <img
                src="https://uxwing.com/wp-content/themes/uxwing/download/brands-and-social-media/github-white-icon.png"
                alt="GitHub"
                class="h-4 w-4"
            >
            Dave Farias
        </a>

    </div>
</footer>

        <flux:toast />

        @livewireScripts
        @fluxScripts
    </body>
</html>
