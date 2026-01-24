<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Tele-Fleet') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700&display=swap" rel="stylesheet" />
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

        <style>
            :root {
                --primary: #3b82f6;
                --secondary: #6b7280;
                --dark: #0f172a;
                --muted: #475569;
                --surface: #f8fafc;
            }

            body {
                font-family: "Manrope", system-ui, -apple-system, sans-serif;
                background: radial-gradient(circle at top right, #dbeafe, transparent 55%),
                            radial-gradient(circle at 20% 20%, #eff6ff, transparent 50%),
                            #f8fafc;
                color: var(--dark);
            }

            .hero-card {
                background: linear-gradient(145deg, #ffffff 0%, #f1f5f9 100%);
                border-radius: 1.5rem;
                box-shadow: 0 30px 80px rgba(15, 23, 42, 0.12);
            }

            .metric-card {
                border-radius: 1rem;
                background: #ffffff;
                box-shadow: 0 20px 50px rgba(15, 23, 42, 0.08);
            }

            .feature-card {
                border-radius: 1rem;
                background: #ffffff;
                border: 1px solid rgba(148, 163, 184, 0.25);
                transition: transform 0.25s ease, box-shadow 0.25s ease;
            }

            .feature-card:hover {
                transform: translateY(-4px);
                box-shadow: 0 18px 40px rgba(15, 23, 42, 0.12);
            }

            .hero-pill {
                display: inline-flex;
                gap: 0.5rem;
                align-items: center;
                background: rgba(59, 130, 246, 0.12);
                color: #1d4ed8;
                padding: 0.4rem 0.9rem;
                border-radius: 999px;
                font-weight: 600;
                font-size: 0.9rem;
            }
        </style>
    </head>
    <body>
        <nav class="navbar navbar-expand-lg bg-transparent py-3">
            <div class="container">
                <a class="navbar-brand fw-bold text-primary" href="{{ url('/') }}">Tele-Fleet</a>
                <div class="ms-auto">
                    @auth
                        <a class="btn btn-outline-primary" href="{{ route('dashboard') }}">Dashboard</a>
                    @else
                        <a class="btn btn-primary" href="{{ route('login') }}">Sign In</a>
                    @endauth
                </div>
            </div>
        </nav>

        <header class="container py-5">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <span class="hero-pill mb-3">Single-company fleet control</span>
                    <h1 class="display-5 fw-bold mb-3">Tele-Fleet brings every vehicle, driver, and trip into one command view.</h1>
                    <p class="lead text-muted">
                        Plan, approve, assign, and audit trips across branches with a workflow-first system built for professional fleet teams.
                    </p>
                    <div class="d-flex gap-3 mt-4">
                        @auth
                            <a class="btn btn-primary btn-lg" href="{{ route('dashboard') }}">Open Dashboard</a>
                        @else
                            <a class="btn btn-primary btn-lg" href="{{ route('login') }}">Sign In</a>
                        @endauth
                        <a class="btn btn-outline-secondary btn-lg" href="{{ route('login') }}">View Demo</a>
                    </div>
                    <div class="mt-4 text-muted small">
                        Built for structured approvals, compliance-ready logs, and real-time fleet visibility.
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="hero-card p-4">
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="metric-card p-3">
                                    <div class="text-muted small">Active Vehicles</div>
                                    <div class="fs-2 fw-bold">128</div>
                                    <div class="text-success small">+12 this month</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="metric-card p-3">
                                    <div class="text-muted small">Trips Today</div>
                                    <div class="fs-2 fw-bold">46</div>
                                    <div class="text-primary small">14 pending approvals</div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="metric-card p-4">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <div class="text-muted small">Branch Coverage</div>
                                            <div class="fs-4 fw-bold">7 live branches</div>
                                        </div>
                                        <div class="text-end">
                                            <div class="text-muted small">Compliance</div>
                                            <div class="fs-4 fw-bold">98% logs captured</div>
                                        </div>
                                    </div>
                                    <div class="progress mt-3" role="progressbar" aria-label="Compliance" aria-valuenow="98" aria-valuemin="0" aria-valuemax="100">
                                        <div class="progress-bar bg-primary" style="width: 98%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <section class="container pb-5">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card p-4 h-100">
                        <h4 class="fw-semibold">Branch-aware control</h4>
                        <p class="text-muted mb-0">Segment fleets per branch, with centralized oversight for approvals and reporting.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card p-4 h-100">
                        <h4 class="fw-semibold">Operational workflow</h4>
                        <p class="text-muted mb-0">Trip requests move from approval to assignment with full audit history.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card p-4 h-100">
                        <h4 class="fw-semibold">Compliance ready</h4>
                        <p class="text-muted mb-0">Logbook entries, driver licensing, and vehicle renewals tracked in one place.</p>
                    </div>
                </div>
            </div>
        </section>

        <footer class="border-top py-4 bg-white">
            <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center text-muted small gap-2">
                <span>&copy; {{ now()->year }} Tele-Fleet. All rights reserved.</span>
                <span>Secure fleet management for modern operations.</span>
            </div>
        </footer>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>
