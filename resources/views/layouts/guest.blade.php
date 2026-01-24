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
                --surface: #f8fafc;
            }

            body {
                font-family: "Manrope", system-ui, -apple-system, sans-serif;
                background: radial-gradient(circle at top right, #dbeafe, transparent 50%),
                            #f8fafc;
                color: var(--dark);
            }

            .auth-card {
                border-radius: 1rem;
                background: #ffffff;
                box-shadow: 0 20px 60px rgba(15, 23, 42, 0.15);
            }
        </style>
    </head>
    <body>
        <div class="min-vh-100 d-flex align-items-center py-5">
            <div class="container">
                <div class="text-center mb-4">
                    <a href="{{ url('/') }}" class="text-decoration-none fw-bold text-primary fs-3">Tele-Fleet</a>
                </div>
                <div class="row justify-content-center">
                    <div class="col-md-6 col-lg-5">
                        <div class="auth-card p-4">
                            {{ $slot }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>
