<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'Mgawi Inventory') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.jsx'])
        @endif
    </head>
    <body>
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            <div id="root"></div>
        @else
            <main style="min-height:100vh;display:grid;place-items:center;font-family:Arial,sans-serif;background:#f6f7f2;color:#1f2933">
                <section style="max-width:520px;padding:28px;border:1px solid #d9dfd2;border-radius:8px;background:#fff">
                    <h1 style="margin:0 0 8px;font-size:24px">Mgawi Inventory API</h1>
                    <p style="margin:0;color:#6b7280">Backend is running.</p>
                </section>
            </main>
        @endif
    </body>
</html>
