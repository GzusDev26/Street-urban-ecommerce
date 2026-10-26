<!DOCTYPE html>
{{-- data-surface: misma regla que surfaceFor() en resources/js/theme.js --}}
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      data-surface="{{ str_starts_with($page['component'], 'Shop/') || $page['component'] === 'Auth/ActivateAccount' ? 'shop' : 'app' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        {{-- Aplica el modo oscuro antes de pintar para que no haya destello claro --}}
        <script>
            (function () {
                var saved = null;
                try { saved = localStorage.getItem('theme'); } catch (e) {}
                var dark = saved ? saved === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches;
                if (dark) document.documentElement.classList.add('dark');
            })();
        </script>

        <link rel="icon" type="image/png" href="/image/logo/favicon.png">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased bg-canvas text-fg min-h-screen">
        @inertia
    </body>
</html>
