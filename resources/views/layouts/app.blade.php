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
    <body class="bg-background text-on-background font-sans text-body-md antialiased selection:bg-primary-container selection:text-on-primary-container">
        <x-app-nav />

        <div class="md:ml-64 min-h-screen flex flex-col pb-24 md:pb-0">
            <header class="hidden md:flex justify-between items-center w-full px-6 py-4 bg-surface sticky top-0 z-30 bg-opacity-80 backdrop-blur-md border-b border-white/5">
                {{ $header ?? '' }}
            </header>

            <header class="md:hidden flex items-center justify-between p-4 bg-surface sticky top-0 z-30 border-b border-outline-variant">
                {{ $mobileHeader ?? $header ?? '' }}
            </header>

            <main class="flex-1 relative">
                {{ $slot }}
            </main>

            <footer class="hidden md:block shrink-0">
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
        </div>

        <flux:toast />
        <livewire:new-deck-modal />

        @livewireScripts
        @fluxScripts
    </body>
</html>
