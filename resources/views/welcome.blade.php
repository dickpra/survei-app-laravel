<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Platform Survei Online</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=be-vietnam-pro:400,600,700,800&display=swap" rel="stylesheet" />
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --gray-800: #1a202c;
            --gray-600: #4a5568;
            --gray-400: #718096;
            --bg-light: #f8fafc;
            --bg-white: #ffffff;
        }

        *, *::before, *::after { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Be Vietnam Pro', sans-serif;
            background-color: var(--bg-light);
            color: var(--gray-800);
        }

        .main-container {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        header {
            padding: 1.5rem 2.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: var(--bg-white);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        }

        .logo {
            font-weight: 800;
            font-size: 1.5rem;
            color: var(--primary);
        }

        .nav-links a {
            margin-left: 1rem;
            font-weight: 600;
            text-decoration: none;
            padding: 0.6rem 1.2rem;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
        }

        /* Tombol Login - Gelap */
        .button-dark {
            background-color: var(--gray-800);
            color: #ffffff;
        }

        .button-dark:hover {
            background-color: #000000;
        }

        /* Tombol Daftar Gratis - Terang */
        .button-light {
            background-color: #ffffff;
            color: var(--gray-800);
            border: 2px solid var(--gray-800);
        }

        .button-light:hover {
            background-color: var(--gray-800);
            color: #ffffff;
        }

        .hero {
            text-align: center;
            padding: 6rem 1rem;
            background: linear-gradient(180deg, var(--bg-light) 0%, #eef2ff 100%);
        }

        .hero h1 {
            font-size: 3.5rem;
            font-weight: 800;
            color: #111827;
            max-width: 800px;
            margin: 0 auto 1.5rem;
        }

        .hero h1 .highlight {
            color: var(--primary);
        }

        .hero p {
            font-size: 1.25rem;
            color: var(--gray-600);
            max-width: 700px;
            margin: 0 auto 2.5rem;
        }

        /* Tombol “Mulai Sekarang” – Tetap seperti versi lama */
        .hero .cta-button {
            background-color: var(--primary);
            color: white;
            padding: 1rem 2rem;
            font-size: 1.25rem;
            border-radius: 0.5rem;
            font-weight: 700;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }

        .hero .cta-button:hover {
            background-color: var(--primary-dark);
        }

        .stats-section {
            padding: 4rem 1rem;
            background-color: var(--bg-white);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: auto;
        }

        .stat-card {
            background-color: var(--bg-light);
            border: 1px solid #eef2ff;
            padding: 2rem;
            border-radius: 0.75rem;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-4px);
        }

        .stat-card .number {
            font-size: 2.75rem;
            font-weight: 800;
            color: var(--primary);
        }

        .stat-card .label {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--gray-600);
            margin-top: 0.5rem;
        }

        footer {
            text-align: center;
            padding: 2rem;
            color: var(--gray-400);
            font-size: 0.95rem;
            background-color: var(--bg-white);
            border-top: 1px solid #e2e8f0;
        }
        @media (max-width: 1024px) {
            header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .nav-links a {
                margin-left: 0;
                margin-right: 0.5rem;
            }

            .hero h1 {
                font-size: 2.5rem;
                padding: 0 1rem;
            }

            .hero p {
                font-size: 1.1rem;
            }
        }

        /* Responsif untuk layar HP */
        @media (max-width: 640px) {
            header {
                padding: 1rem;
            }

            .logo {
                font-size: 1.25rem;
            }

            .nav-links {
                display: flex;
                flex-direction: column;
                width: 100%;
            }

            .nav-links a {
                margin: 0.25rem 0;
                width: 100%;
                text-align: center;
            }

            .hero {
                padding: 4rem 1rem;
            }

            .hero h1 {
                font-size: 2rem;
            }

            .hero p {
                font-size: 1rem;
            }

            .hero .cta-button {
                display: inline-block;
                padding: 0.8rem 1.5rem;
                font-size: 1rem;
            }

            .stats-section {
                padding: 2rem 1rem;
            }

            .stat-card {
                padding: 1.5rem 1rem;
            }

            .stat-card .number {
                font-size: 2rem;
            }

            .stat-card .label {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <header>
            <div class="logo">UMIT</div>
            <nav class="nav-links">
            @auth('user')
                <a href="{{ url('/user') }}" class="button-dark">Dashboard User</a>
            @elseif(Auth::guard('admin')->check())
                <a href="{{ url('/admin') }}" class="button-dark">Dashboard Admin</a>
            @else
                <a href="{{ url('/login') }}" class="button-dark">Login</a>
                <a href="{{ route('filament.user.auth.register') }}" class="button-light">Daftar Gratis</a>
            @endauth
            </nav>
        </header>

        <main>
            <section class="hero">
                <h1>Platform Survei <span class="highlight">Modern</span> untuk Semua Kebutuhan Anda</h1>
                <p>Buat, sebarkan, dan analisis survei dengan mudah. Dapatkan wawasan berharga dari responden Anda dengan antarmuka yang intuitif dan hasil yang powerful.</p>
                <a href="{{ route('filament.user.auth.register') }}" class="cta-button">Mulai Sekarang →</a>
            </section>

            <section class="stats-section">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="number">{{ number_format($totalTemplates) }}</div>
                        <div class="label">Template Tersedia</div>
                    </div>
                    <div class="stat-card">
                        <div class="number">{{ number_format($totalSurveys) }}</div>
                        <div class="label">Survei Telah Dibuat</div>
                    </div>
                    <div class="stat-card">
                        <div class="number">{{ number_format($totalResponses) }}</div>
                        <div class="label">Jawaban Terkumpul</div>
                    </div>
                </div>
            </section>
        </main>

        <footer>
            © {{ date('Y') }} UMIT. All rights reserved.
        </footer>
    </div>
</body>
</html>
