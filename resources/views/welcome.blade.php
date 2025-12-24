{{-- resources/views/welcome.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Platform Survei Online</title>

  {{-- Tailwind + Font --}}
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.bunny.net/css?family=be-vietnam-pro:400,600,700,800&display=swap" rel="stylesheet"/>

  {{-- AOS (animate on scroll) + Feather Icons --}}
  {{-- <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet"> --}}
  <script defer src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
  <script defer src="https://unpkg.com/feather-icons"></script>

  <style>
    :root{
      --primary: #2563eb;
      --primary-700: #1d4ed8;
      --primary-100: #dbeafe;
      --accent: #0ea5e9;

      --bg-page: #f5f7ff;
      --bg-section: #eef4ff;
      --bg-card: #ffffff;

      --text-strong: #0f172a;
      --text: #1f2937;
      --text-muted: #64748b;

      --border: #e2e8f0;
      --ring: rgba(37,99,235,.35);
    }
    .dark{
      --primary: #5c99e4;
      --primary-700: #2872e9;
      --primary-100: #1e293b;
      --accent: #2a98c7;

      --bg-page: #0b1220;
      --bg-section: #0f172a;
      --bg-card: #111827;

      --text-strong: #e5e7eb;
      --text: #cbd5e1;
      --text-muted: #94a3b8;

      --border: #1f2937;
      --ring: rgba(96,165,250,.35);
    }

    *,*::before,*::after{ box-sizing:border-box }
    html{ scroll-behavior:smooth }
    body{
      margin:0; font-family:'Be Vietnam Pro',system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;
      background:var(--bg-page); color:var(--text);
      transition: background-color .2s, color .2s;
    }
/* --- Responsiveness & header polish --- */
  section[id]{ scroll-margin-top: 96px }         /* biar anchor gak ketutupan sticky header */
  .header{
    /* transparan elegan + blur (fallback aman) */
    background: color-mix(in oklab, var(--bg-card) 88%, transparent);
    -webkit-backdrop-filter: saturate(180%) blur(10px);
    backdrop-filter: saturate(180%) blur(10px);
  }
  /* fokus ring yang konsisten untuk aksesibilitas */
  a:focus-visible, button:focus-visible, select:focus-visible{
    outline: none;
    box-shadow: 0 0 0 4px var(--ring);
    border-color: var(--primary);
  }
  /* perbaiki wrapping nav di layar sempit */
  @media (max-width: 640px){
    .nav-wrap{flex-direction:column;align-items:flex-start;gap:.5rem}
  }
    .brand-shadow{ box-shadow: 0 2px 6px rgba(0,0,0,.06) }
    .card{ background:var(--bg-card); border:1px solid var(--border); border-radius:1rem; box-shadow:0 4px 14px rgba(0,0,0,.04) }
    .chip{ display:inline-flex; align-items:center; gap:.5rem; padding:.375rem .625rem; border-radius:.625rem; background:var(--primary-100); color:var(--text) }
    .cta{ background:var(--primary); color:#fff; transition: background-color .2s, transform .06s }
    .cta:hover{ background:var(--primary-700) }
    .cta:active{ transform: translateY(1px) }
    .btn-dark{ background:#1f2937; color:#fff } .btn-dark:hover{ background:#111827 }
    .btn-light{ background:#fff; border:2px solid #1f2937; color:#1f2937 } .btn-light:hover{ background:#1f2937; color:#fff }

    .header{ background:var(--bg-card); border-bottom:1px solid var(--border) }
    .nav-link{ color:var(--text-muted); font-weight:600 }
    .nav-link:hover{ color:var(--primary) }
    .nav-link-active{ color:var(--primary); font-weight:800 }

    .hero{
      background:
        radial-gradient(900px 350px at 110% -30%, rgba(37,99,235,.12), transparent 60%),
        radial-gradient(700px 300px at -10% 0%, rgba(14,165,233,.12), transparent 60%),
        linear-gradient(180deg, var(--bg-section) 0%, var(--bg-page) 100%);
      border:1px solid var(--border);
      border-radius:1rem;
    }

    .title-underline{ display:inline-block; position:relative; padding-bottom:.35rem }
    .title-underline::after{
      content:""; position:absolute; left:0; right:0; bottom:0; height:3px; border-radius:999px;
      background:linear-gradient(90deg, var(--primary), var(--accent)); opacity:.9;
    }

    .input, .select{
      background:#fff; color:var(--text); border:1px solid var(--border); border-radius:.75rem; padding:0.9rem 1rem;
      outline:none; transition: box-shadow .15s, border-color .15s, background-color .2s, color .2s;
    }
    .input:focus, .select:focus{ border-color:var(--primary); box-shadow: 0 0 0 4px var(--ring) }
    .dark .input, .dark .select{ background:#0b1220; color:var(--text); border-color:var(--border) }
    .dark .input::placeholder{ color:#64748b }

    .footer-grad-line{ height:4px; width:100%; background:linear-gradient(90deg,var(--primary),var(--accent)); opacity:.9 }

    .toggle{
      display:inline-flex; align-items:center; gap:.5rem; padding:.5rem .75rem; border:1px solid var(--border);
      border-radius:.5rem; background:var(--bg-page); color:var(--text);
    }
    .toggle:hover{ filter:brightness(1.05) }

    @media (max-width: 640px){ .nav-wrap{flex-direction:column;align-items:flex-start;gap:.5rem} }
  </style>
</head>
<body>
@php
  /** @var \App\Models\DashboardSetting|null $settings */
  $settings = $settings ?? \App\Models\DashboardSetting::first();
@endphp

{{-- =================== HEADER =================== --}}
<header class="header brand-shadow sticky top-0 z-50">
  <div class="max-w-7xl mx-auto px-4 py-3 flex items-center gap-3">
    <!-- Mobile: Hamburger -->
    <button id="mobileMenuBtn"
            class="lg:hidden inline-flex items-center justify-center p-2 rounded-md border"
            aria-expanded="false" aria-controls="mobileNav" aria-label="Toggle navigation"
            style="border-color:var(--border); background:var(--bg-page); color:var(--text)">
      <i data-feather="menu" aria-hidden="true"></i>
    </button>

    <!-- Brand -->
    <a href="#dashboard" class="flex items-center gap-2 shrink-0">
      <img src="{{ asset('img/umit-logo.png') }}" alt="Logo UMIT" class="h-9 w-auto">
    </a>

    <!-- Desktop Nav -->
    <nav id="main-nav" class="hidden lg:flex flex-1 justify-center">
      <div class="flex items-center gap-6 text-sm">
        <a href="#dashboard" class="nav-link">{{ __('Dashboard') }}</a>
        <a href="#about" class="nav-link">{{ __('About Me') }}</a>
        <a href="#credit" class="nav-link">{{ __('Credit') }}</a>
        <a href="#guidebook" class="nav-link">{{ __('Guidebook') }}</a>
        <a href="#metodologi" class="nav-link">{{ __('Metodologi') }}</a>
      </div>
    </nav>

    <!-- Desktop Right -->
    <div class="hidden sm:flex items-center gap-2">
      @auth('user')
        <a href="{{ url('/user') }}" class="btn-dark px-3 py-2 rounded-lg">Dashboard User</a>
      @elseif(Auth::guard('admin')->check())
        <a href="{{ url('/admin') }}" class="btn-dark px-3 py-2 rounded-lg">Dashboard Admin</a>
      @else
        <a href="{{ url('/login') }}" class="px-3 py-2 rounded-lg" style="color:var(--text)">Login</a>
        <a href="{{ url('/register') }}" class="btn-light px-3 py-2 rounded-lg">{{ __('Daftar Gratis') }}</a>
      @endauth>

      {{-- <button id="themeToggle" type="button" class="toggle">
        <span id="themeIcon" aria-hidden="true">🌙</span>
        <span id="themeText" class="text-sm">Dark</span>
      </button> --}}

      <div class="hidden md:flex items-center">
        @livewire('language-switcher')
      </div>
    </div>
  </div>

  <!-- Mobile Drawer -->
  <div id="mobileNav" class="lg:hidden border-t" style="border-color:var(--border)" hidden>
    <nav class="max-w-7xl mx-auto px-4 py-3">
      <div class="flex flex-col gap-2">
        <a href="#dashboard" class="nav-link py-2">{{ __('Dashboard') }}</a>
        <a href="#about" class="nav-link py-2">{{ __('About Me') }}</a>
        <a href="#credit" class="nav-link py-2">{{ __('Credit') }}</a>
        <a href="#guidebook" class="nav-link py-2">{{ __('Guidebook') }}</a>
        <a href="#metodologi" class="nav-link py-2">{{ __('Metodologi') }}</a>
      </div>

      <div class="mt-3 pt-3 border-t flex items-center gap-2"
           style="border-color:var(--border)">
        @auth('user')
          <a href="{{ url('/user') }}" class="btn-dark px-3 py-2 rounded-lg flex-1 text-center">Dashboard User</a>
        @elseif(Auth::guard('admin')->check())
          <a href="{{ url('/admin') }}" class="btn-dark px-3 py-2 rounded-lg flex-1 text-center">Dashboard Admin</a>
        @else
          <a href="{{ url('/login') }}" class="px-3 py-2 rounded-lg flex-1 text-center" style="border:1px solid var(--border); background:var(--bg-page); color:var(--text)">Login</a>
          <a href="{{ url('/register') }}" class="cta px-3 py-2 rounded-lg flex-1 text-center">{{ __('Daftar Gratis') }}</a>
        @endauth
      </div>

      <div class="mt-3">
        @livewire('language-switcher')
      </div>
    </nav>
  </div>
</header>


<main class="max-w-7xl mx-auto px-4">
  {{-- =================== HERO / DASHBOARD =================== --}}
  <section id="dashboard" class="hero mt-6 p-6 md:p-10" data-aos="fade-up">
    <div class="text-center max-w-4xl mx-auto">
      <h1 class="text-3xl md:text-5xl font-extrabold leading-tight" style="color:var(--text-strong)">
        {!! nl2br(e($settings?->hero_title ?? 'Platform Survei Modern untuk Semua Kebutuhan Anda')) !!}
      </h1>
      <p class="mt-3 text-base md:text-lg" style="color:var(--text)">
        {{ $settings?->hero_subtitle ?? 'Buat, sebarkan, dan analisis survei dengan mudah. Dapatkan wawasan berharga dari responden Anda.' }}
      </p>
      <div class="mt-6">
        <a href="{{ url('/register') }}" class="cta inline-block px-6 py-3 rounded-lg font-bold">{{ __('Mulai Sekarang') }} →</a>
      </div>
    </div>

    {{-- Search + Filter --}}
    <form action="{{ route('home') }}" method="GET" class="card mt-8 p-4 md:p-5" data-aos="fade-up" data-aos-delay="100">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
        <div class="md:col-span-2 flex">
          <label class="sr-only" for="search">Cari Survei</label>
          <input
            type="search" id="search" name="search" value="{{ request('search') }}"
            placeholder="{{ __('Ketik judul survei...') }}"
            class="input w-full rounded-l-xl"
          >
          <button type="submit" class="px-6 rounded-r-xl text-sm font-semibold cta border-l-0">{{ __('Cari') }}</button>
        </div>
        <div>
          <label class="sr-only" for="template_id">{{ __('Filter Kuesioner') }}</label>
          <select
            id="template_id" name="template_id"
            class="select w-full rounded-xl"
            onchange="this.form.submit()"
          >
            <option value="">{{ __('Semua Kuesioner') }}</option>
            @foreach ($templates as $id => $title)
              <option value="{{ $id }}" @selected(request('template_id') == $id)>{{ $title }}</option>
            @endforeach
          </select>
        </div>
      </div>
    </form>

    {{-- Stats dinamis --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
      <div class="card p-5 text-center" data-aos="fade-up" data-aos-delay="150">
        <div class="text-2xl md:text-3xl font-extrabold" style="color:var(--primary)">{{ number_format($templates->count()) }}</div>
        <div class="text-sm md:text-base" style="color:var(--text-muted)">{{ __('Template Tersedia') }}</div>
      </div>
      <div class="card p-5 text-center" data-aos="fade-up" data-aos-delay="200">
        <div class="text-2xl md:text-3xl font-extrabold" style="color:var(--primary)">{{ number_format($surveys->total()) }}</div>
        <div class="text-sm md:text-base" style="color:var(--text-muted)">{{ __('Survei Dipublikasi') }}</div>
      </div>
      <div class="card p-5 text-center" data-aos="fade-up" data-aos-delay="250">
        <div class="text-2xl md:text-3xl font-extrabold" style="color:var(--primary)">
          {{ number_format(\App\Models\Survey::where('is_public', true)->withCount('responses')->get()->sum('responses_count')) }}
        </div>
        <div class="text-sm md:text-base" style="color:var(--text-muted)">{{ __('Jawaban Terkumpul') }}</div>
      </div>
    </div>

    {{-- Grid Survei --}}
    <div class="mt-8">
      <div class="flex items-end justify-between gap-3 mb-3">
        <h2 class="text-xl md:text-2xl font-extrabold title-underline" style="color:var(--text-strong)">{{ __('Hasil Survei Publik') }}</h2>
        <span class="chip text-sm">
          <i data-feather="layers" aria-hidden="true"></i>
          {{ number_format($surveys->total()) }} survei
        </span>
      </div>
      <p class="mb-6" style="color:var(--text-muted)">{{ __('Gunakan filter di atas untuk mempersempit hasil.') }}</p>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse ($surveys as $survey)
          <article class="card p-6 hover:shadow-xl transition-shadow duration-300 flex flex-col" data-aos="fade-up">
            <h3 class="text-lg md:text-xl font-bold" style="color:var(--text-strong)">{{ $survey->title }}</h3>
            <p class="text-sm mt-2" style="color:var(--text-muted)">{{ __('Dipublikasikan pada:') }} {{ $survey->created_at->format('d M Y') }}</p>
            <div class="mt-4 pt-4 border-t" style="border-color:var(--border)">
              <div class="flex items-center justify-between">
                <div class="text-center">
                  <span class="text-2xl font-extrabold" style="color:var(--primary)">{{ number_format($survey->responses_count) }}</span>
                  <span class="block text-sm" style="color:var(--text-muted)">{{ __('Responden') }}</span>
                </div>
                <a href="{{ route('public.survey.results', $survey) }}"
                   class="inline-block px-4 py-2 rounded-lg text-sm font-semibold"
                   style="background:var(--primary-100); color:var(--primary)">
                  {{ __('Lihat Detail') }} &rarr;
                </a>
              </div>
            </div>
          </article>
        @empty
          <div class="col-span-full card p-10 text-center">
            <p style="color:var(--text-muted)" class="text-lg">{{ __('Tidak ada hasil survei yang cocok.') }}</p>
            <a href="{{ route('home') }}" style="color:var(--primary)" class="mt-2 inline-block font-semibold hover:underline">{{ __('Tampilkan semua survei') }}</a>
          </div>
        @endforelse
      </div>

      <div class="mt-8">
        {{ $surveys->withQueryString()->links('pagination::tailwind') }}
      </div>
    </div>
  </section>

  {{-- =================== ABOUT =================== --}}
  <section id="about" class="mt-10" data-aos="fade-up">
    <h2 class="text-2xl md:text-3xl font-extrabold title-underline" style="color:var(--text-strong)">About Me</h2>
    <div class="card mt-4 p-6">
      @if(is_array($settings?->about_me) && count($settings->about_me))
        <div class="space-y-6">
          @foreach($settings->about_me as $block)
            @include('partials._builder-block', ['block' => $block])
          @endforeach
        </div>
      @else
        <p style="color:var(--text-muted)">{{ __('Belum ada konten.') }}</p>
      @endif
    </div>
  </section>

  {{-- =================== CREDIT =================== --}}
  <section id="credit" class="mt-10" data-aos="fade-up">
    <h2 class="text-2xl md:text-3xl font-extrabold title-underline" style="color:var(--text-strong)">{{ __('Credit') }}</h2>
    <div class="card mt-4 p-6">
      @if(is_array($settings?->credit) && count($settings->credit))
        <div class="space-y-6">
          @foreach($settings->credit as $block)
            @include('partials._builder-block', ['block' => $block])
          @endforeach
        </div>
      @else
        <p style="color:var(--text-muted)">{{ __('Belum ada konten.') }}</p>
      @endif
    </div>
  </section>

  {{-- =================== GUIDEBOOK =================== --}}
  <section id="guidebook" class="mt-10" data-aos="fade-up">
    <h2 class="text-2xl md:text-3xl font-extrabold title-underline" style="color:var(--text-strong)">{{ __('Guidebook') }}</h2>
    <div class="card mt-4 p-6">
      @if(is_array($settings?->guidebook) && count($settings->guidebook))
        <div class="space-y-6">
          @foreach($settings->guidebook as $block)
            @include('partials._builder-block', ['block' => $block])
          @endforeach
        </div>
      @else
        <p style="color:var(--text-muted)">Belum ada konten.</p>
      @endif
    </div>
  </section>

  {{-- =================== METODOLOGI =================== --}}
  <section id="metodologi" class="mt-10 mb-14" data-aos="fade-up">
    <h2 class="text-2xl md:text-3xl font-extrabold title-underline" style="color:var(--text-strong)">{{ __('Metodologi') }}</h2>
    <div class="card mt-4 p-6">
      @if(is_array($settings?->metodologi) && count($settings->metodologi))
        <div class="space-y-6">
          @foreach($settings->metodologi as $block)
            @include('partials._builder-block', ['block' => $block])
          @endforeach
        </div>
      @else
        <p style="color:var(--text-muted)">{{ __('Belum ada konten.') }}</p>
      @endif
    </div>
  </section>
</main>

{{-- =================== FOOTER =================== --}}
<footer class="pt-6 border-t" style="background:var(--bg-card); border-color:var(--border)">
  <div class="footer-grad-line"></div>
  <div class="max-w-7xl mx-auto px-4 py-10">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
      <div>
         <div class="flex items-center space-x-2">
            <img src="{{ asset('img/umit-logo.png') }}" alt="Logo" class=" h-10 w-auto">
            {{-- <span class="text-xl font-bold text-primary-700 dark:text-accent-500">UMIX</span> --}}
    </div>
        <p class="text-sm" style="color:var(--text-muted)">
            {{ __('Rancang instrumen penelitian secara sistematis. Analisis data yang dihasilkan untuk memperoleh wawasan mendalam dan mendukung pengambilan keputusan yang berbasis bukti.') }}
      </div>

      <div>
        <h4 class="text-sm font-bold mb-3" style="color:var(--text-strong)">{{ __('Navigasi') }}</h4>
        <ul class="space-y-2 text-sm">
          <li><a href="#dashboard" style="color:var(--text-muted)" class="hover:underline">{{ __('Dashboard') }}</a></li>
          <li><a href="#about" style="color:var(--text-muted)" class="hover:underline">{{ __('About Me') }}</a></li>
          <li><a href="#credit" style="color:var(--text-muted)" class="hover:underline">{{ __('Credit') }}</a></li>
          <li><a href="#guidebook" style="color:var(--text-muted)" class="hover:underline">{{ __('Guidebook') }}</a></li>
          <li><a href="#metodologi" style="color:var(--text-muted)" class="hover:underline">{{ __('Metodologi') }}</a></li>
        </ul>
      </div>

      <div>
        <h4 class="text-sm font-bold mb-3" style="color:var(--text-strong)">{{ __('Kontak') }}</h4>
            <ul class="space-y-2 text-sm" style="color:var(--text-muted)">
                @if($settings?->contact_email)
                    <li class="flex items-center gap-2">
                        <i data-feather="mail" aria-hidden="true" class="w-4 h-4"></i>
                        <span>{{ $settings->contact_email }}</span>
                    </li>
                @endif
                @if($settings?->contact_phone)
                    <li class="flex items-center gap-2">
                        <i data-feather="phone" aria-hidden="true" class="w-4 h-4"></i>
                        <span>{{ $settings->contact_phone }}</span>
                    </li>
                @endif
            </ul>

        <div class="flex items-center gap-2 mt-3">
          <a href="#" class="w-9 h-9 rounded-md inline-flex items-center justify-center" style="background:var(--bg-section); color:var(--text-muted)">
            <i data-feather="facebook" aria-hidden="true"></i>
          </a>
          <a href="#" class="w-9 h-9 rounded-md inline-flex items-center justify-center" style="background:var(--bg-section); color:var(--text-muted)">
            <i data-feather="twitter" aria-hidden="true"></i>
          </a>
          <a href="#" class="w-9 h-9 rounded-md inline-flex items-center justify-center" style="background:var(--bg-section); color:var(--text-muted)">
            <i data-feather="instagram" aria-hidden="true"></i>
          </a>
        </div>
      </div>

      <div>
        <h4 class="text-sm font-bold mb-3" style="color:var(--text-strong)">{{ __('Aksi Cepat') }}</h4>
        <div class="flex flex-col gap-2">
            <div class="mt-4">
            {{-- <h4 class="text-sm font-bold mb-2" style="color:var(--text-strong)">{{ __('Tema') }}</h4> --}}
            <button id="themeToggle" type="button" class="toggle">
                <span id="themeIcon" aria-hidden="true">🌙</span>
                <span id="themeText" class="text-sm">Dark</span>
            </button>
            </div>

          <a href="{{ url('/register') }}" class="cta px-4 py-2 rounded-lg text-sm font-semibold text-center">{{ __('Buat Akun') }}</a>
          <a href="{{ url('/login') }}" class="px-4 py-2 rounded-lg text-sm font-semibold text-center" style="border:1px solid var(--border); background:var(--bg-page); color:var(--text)">{{ __('Masuk') }}</a>
        </div>
      </div>
    </div>

    <div class="border-t mt-8 pt-6 text-center text-sm" style="border-color:var(--border); color:var(--text-muted)">
      © {{ date('Y') }} UMIT. All rights reserved.
    </div>
  </div>
</footer>

{{-- =================== Scripts =================== --}}

<script>
  // Init libraries
  document.addEventListener('DOMContentLoaded', () => {
    AOS.init({ once:true, duration:700, easing:'ease-out' });
    feather.replace({ 'stroke-width': 1.5 });
  });

  // Dark mode toggle
  (function(){
    const html = document.documentElement;
    const btn  = document.getElementById('themeToggle');
    const icon = document.getElementById('themeIcon');
    const text = document.getElementById('themeText');

    const saved = localStorage.getItem('theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const useDark = saved ? (saved === 'dark') : prefersDark;

    html.classList.toggle('dark', useDark);
    icon.textContent = useDark ? '☀️' : '🌙';
    text.textContent = useDark ? 'Light' : 'Dark';

    btn?.addEventListener('click', () => {
      const nowDark = !html.classList.contains('dark');
      html.classList.toggle('dark', nowDark);
      localStorage.setItem('theme', nowDark ? 'dark' : 'light');
      icon.textContent = nowDark ? '☀️' : '🌙';
      text.textContent = nowDark ? 'Light' : 'Dark';
    });
  })();

  // Mobile menu toggle
  (function(){
    const btn = document.getElementById('mobileMenuBtn');
    const panel = document.getElementById('mobileNav');
    if(!btn || !panel) return;

    const setIcon = (open) => {
      btn.innerHTML = open ? '<i data-feather="x" aria-hidden="true"></i>' : '<i data-feather="menu" aria-hidden="true"></i>';
      feather.replace({ 'stroke-width': 1.5 });
      btn.setAttribute('aria-expanded', open.toString());
    };

    const openMenu  = () => { panel.hidden = false; setIcon(true); };
    const closeMenu = () => { panel.hidden = true; setIcon(false); };

    btn.addEventListener('click', () => panel.hidden ? openMenu() : closeMenu());

    // tutup saat klik link di drawer
    panel.querySelectorAll('a[href^="#"]').forEach(a => {
      a.addEventListener('click', () => closeMenu());
    });

    // tutup saat esc
    document.addEventListener('keydown', (e) => {
      if(e.key === 'Escape' && !panel.hidden) closeMenu();
    });
  })();

  // Highlight active nav on scroll (tetap, sedikit diperkuat)
  document.addEventListener('DOMContentLoaded', () => {
    const sections = document.querySelectorAll('section[id]');
    const links = document.querySelectorAll('#main-nav .nav-link');
    if (!sections.length || !links.length) return;

    const io = new IntersectionObserver(entries => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          links.forEach(a => a.classList.remove('nav-link-active'));
          const active = document.querySelector(`#main-nav a[href="#${entry.target.id}"]`);
          active && active.classList.add('nav-link-active');
        }
      });
    }, { rootMargin: '-50% 0px -40% 0px', threshold: 0 });

    sections.forEach(s => io.observe(s));
  });

//   // Highlight active nav on scroll
//   document.addEventListener('DOMContentLoaded', () => {
//     const sections = document.querySelectorAll('section[id]');
//     const links = document.querySelectorAll('#main-nav .nav-link');
//     if (!sections.length || !links.length) return;

//     const io = new IntersectionObserver(entries => {
//       entries.forEach(entry => {
//         if (entry.isIntersecting) {
//           links.forEach(a => a.classList.remove('nav-link-active'));
//           const active = document.querySelector(`#main-nav a[href="#${entry.target.id}"]`);
//           active && active.classList.add('nav-link-active');
//         }
//       });
//     }, { rootMargin: '-50% 0px -40% 0px', threshold: 0 });

//     sections.forEach(s => io.observe(s));
//   });
</script>

</body>
</html>
