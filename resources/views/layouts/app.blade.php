<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Tele-Fleet') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700&display=swap" rel="stylesheet" />
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

        <style>
            :root {
                --primary: #3b82f6;
                --secondary: #6b7280;
                --dark: #0f172a;
                --light: #f9fafb;
            }

            body {
                font-family: "Manrope", system-ui, -apple-system, sans-serif;
                background: #f4f6fb;
                color: var(--dark);
            }
        </style>
    </head>
    <body>
        @include('layouts.navigation')

        @isset($header)
            <header class="bg-white border-bottom py-4">
                <div class="container">
                    {{ $header }}
                </div>
            </header>
        @endisset

        <main class="py-5">
            <div class="container">
                {{ $slot }}
            </div>
        </main>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>
