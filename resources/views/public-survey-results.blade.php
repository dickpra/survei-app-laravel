<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Hasil Survei: {{ $survey->title }}</title>

    {{-- Tailwind & Fonts --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.bunny.net/css?family=be-vietnam-pro:400,600,700,800&display=swap" rel="stylesheet"/>

    {{-- Chart.js + Datalabels --}}
    <script defer src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0"></script>

    <style>
        :root{ --primary:#4f46e5; --primary-dark:#4338ca; --bg-soft:#eef2ff; }
        body{ font-family:'Be Vietnam Pro',system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif; }
        .brand-shadow{ box-shadow:0 2px 6px rgba(0,0,0,.05) }
        .hero-grad{ background:linear-gradient(180deg,#f8fafc 0%, #eef2ff 100%) }
        .soft-card{ background:#fff; border:1px solid #eef2ff; box-shadow:0 4px 12px rgba(0,0,0,.03) }
        .btn-primary{ background:var(--primary); color:#fff } .btn-primary:hover{ background:var(--primary-dark) }
        .btn-muted{ background:#f1f5f9; color:#374151 } .btn-muted:hover{ background:#e5e7eb }
        .tabular-nums{ font-variant-numeric: tabular-nums; }
        
        /* Tab Styles */
        .tab-btn { border-bottom: 2px solid transparent; color: #6b7280; }
        .tab-btn:hover { color: #374151; border-color: #d1d5db; }
        .tab-btn.active { border-color: var(--primary); color: var(--primary); font-weight: 700; }
        .tab-content { display: none; animation: fadeIn 0.3s ease-in-out; }
        .tab-content.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
        
        /* Table Styles */
        .data-table th { background-color: #f8fafc; font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; color: #475569; padding: 0.75rem 1rem; text-align: left; }
        .data-table td { padding: 0.75rem 1rem; border-top: 1px solid #f1f5f9; font-size: 0.875rem; color: #334155; }
        .data-table tr:hover td { background-color: #f8fafc; }
    </style>
</head>
<body class="bg-gray-50">

    {{-- ================= HEADER ================= --}}
    <header class="bg-white brand-shadow sticky top-0 z-50">
        <nav class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
            <a href="{{ route('home') }}" class="text-2xl font-extrabold text-indigo-600">UMIT</a>
            <a href="{{ route('home') }}" class="px-4 py-2 rounded-lg font-semibold text-sm text-gray-700 hover:bg-gray-100">&larr; Kembali ke Dasbor</a>
        </nav>
    </header>

    <main class="max-w-7xl mx-auto px-4 pb-12">
        {{-- ================= HERO ================= --}}
        <section class="hero-grad rounded-2xl mt-6 p-6 md:p-10 text-center soft-card border-0">
            <h1 class="text-3xl md:text-4xl font-extrabold text-gray-900 tracking-tight">{{ $survey->title }}</h1>
            <div class="mt-6 flex items-center justify-center gap-3 flex-wrap">
                <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-indigo-50 text-indigo-700 text-sm font-semibold">
                    Total Responden: <span class="tabular-nums font-bold">{{ number_format($totalResponses) }}</span>
                </span>
            </div>
        </section>

        <div class="flex flex-col lg:flex-row gap-8 mt-8">
            {{-- ================= SIDEBAR FILTER ================= --}}
            <aside class="w-full lg:w-1/4">
                <div class="sticky top-24 soft-card rounded-2xl p-5">
                    <h2 class="text-lg font-bold mb-4 border-b pb-2">Filter Hasil</h2>
                    <form action="{{ route('public.survey.results', $survey) }}" method="GET" class="space-y-4">
                        @forelse ($filters as $question => $options)
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">{{ $question }}</label>
                                <select name="filters[{{ $question }}]" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                    <option value="">Semua</option>
                                    @foreach ($options as $option)
                                        <option value="{{ $option }}" @selected(request('filters.'.$question) == $option)>{{ $option }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500">Tidak ada filter yang tersedia.</p>
                        @endforelse
                        <div class="pt-2 flex gap-2">
                            <button type="submit" class="btn-primary w-full px-4 py-2 rounded-lg font-semibold text-sm">Terapkan</button>
                            <a href="{{ route('public.survey.results', $survey) }}" class="btn-muted w-full text-center px-4 py-2 rounded-lg font-semibold text-sm">Reset</a>
                        </div>
                    </form>
                </div>
            </aside>

            {{-- ================= KONTEN TABS ================= --}}
            <div class="w-full lg:w-3/4">
                @if($totalResponses > 0)
                    
                    {{-- TAB NAVIGATION --}}
                    <div class="mb-6 border-b border-gray-200">
                        <nav class="-mb-px flex space-x-8 overflow-x-auto" aria-label="Tabs">
                            <button onclick="switchTab('demographic')" id="btn-demographic" class="tab-btn active whitespace-nowrap py-4 px-1 text-sm font-medium">
                                Demographic Data
                            </button>
                            <button onclick="switchTab('anova')" id="btn-anova" class="tab-btn whitespace-nowrap py-4 px-1 text-sm font-medium">
                                Analisis Varian (Tabel)
                            </button>
                            <button onclick="switchTab('desc')" id="btn-desc" class="tab-btn whitespace-nowrap py-4 px-1 text-sm font-medium">
                                Deskripsi Analisis (Grafik)
                            </button>
                        </nav>
                    </div>

                    {{-- TAB 1: DEMOGRAPHIC DATA --}}
                    {{-- TAB 1: DEMOGRAPHIC DATA --}}
                    {{-- TAB 1: DEMOGRAPHIC DATA (UPDATED) --}}
                    <div id="tab-demographic" class="tab-content active">
                        @if (!empty($results['demographic']))
                            <section class="soft-card rounded-2xl p-6">
                                <h3 class="font-bold text-xl mb-6 flex items-center gap-2 text-gray-800">
                                    <svg class="w-6 h-6 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                    {{ $survey->questionnaireTemplate->demographic_title ?? 'Demographic Data' }}
                                </h3>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    @foreach ($results['demographic'] as $question => $result)
                                        @php
                                            // Cek Tipe: Aggregate (Chart) atau List (Teks)
                                            $isAggregate = ($result['type'] ?? '') === 'aggregate';
                                            $chartId = 'chart-demo-'.$loop->index;
                                            $uniqueId = 'demo-block-'.$loop->index;
                                            
                                            // Siapkan data untuk JS
                                            $labels = $isAggregate ? array_keys($result['answers'] ?? []) : [];
                                            $values = $isAggregate ? array_values($result['answers'] ?? []) : [];
                                            
                                            // Untuk Text Answers: Ambil raw array
                                            $textAnswers = !$isAggregate ? ($result['answers'] ?? []) : [];
                                        @endphp

                                        <article class="rounded-xl border border-indigo-50 p-5 bg-white shadow-sm hover:shadow-md transition-shadow h-full flex flex-col">
                                            <header class="mb-4 text-center">
                                                <h4 class="font-semibold text-gray-900">{{ $question }}</h4>
                                            </header>

                                            <div class="flex-1 flex flex-col">
                                                {{-- KONDISI 1: TIPE CHART (Dropdown/Negara) --}}
                                                @if($isAggregate && !empty($labels))
                                                    <div class="h-56 relative mb-4">
                                                        <canvas id="{{ $chartId }}" 
                                                                data-labels='@json($labels)' 
                                                                data-values='@json($values)'></canvas>
                                                    </div>
                                                    <div class="mt-auto border-t border-gray-100 pt-3">
                                                        <ul class="space-y-1 text-sm max-h-48 overflow-y-auto">
                                                            @php $total = array_sum($values); @endphp
                                                            @foreach($labels as $i => $lbl)
                                                                @php
                                                                    $v = $values[$i] ?? 0;
                                                                    $pct = $total > 0 ? ($v/$total*100) : 0;
                                                                @endphp
                                                                <li class="flex items-center justify-between bg-gray-50 rounded px-3 py-2">
                                                                    <div class="flex items-center gap-2 overflow-hidden">
                                                                        <span class="w-2 h-2 rounded-full bg-indigo-400 shrink-0"></span>
                                                                        <span class="truncate text-gray-700 font-medium">{{ $lbl }}</span>
                                                                    </div>
                                                                    <div class="whitespace-nowrap ml-2">
                                                                        <span class="font-bold text-gray-900 tabular-nums">{{ $v }}</span>
                                                                        <span class="text-xs text-gray-500 tabular-nums ml-1">({{ number_format($pct, 1) }}%)</span>
                                                                    </div>
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    </div>
                                                
                                                {{-- KONDISI 2: TIPE TEXT (Isian Pendek) DENGAN PAGINATION & SEARCH --}}
                                                @else
                                                    <div id="{{ $uniqueId }}" class="text-manager flex flex-col h-full">
                                                        {{-- Toolbar: Search & Limit --}}
                                                        <div class="flex flex-col sm:flex-row gap-2 mb-3">
                                                            <input type="text" 
                                                                placeholder="Cari jawaban..." 
                                                                class="text-search w-full text-sm border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 px-3 py-2"
                                                                onkeyup="textManager('{{ $uniqueId }}').search(this.value)">
                                                            
                                                            <select class="text-limit text-sm border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 px-2 py-2"
                                                                    onchange="textManager('{{ $uniqueId }}').setLimit(this.value)">
                                                                <option value="10">10</option>
                                                                <option value="25">25</option>
                                                                <option value="50">50</option>
                                                                <option value="100">100</option>
                                                                <option value="all">Semua</option>
                                                            </select>
                                                        </div>

                                                        {{-- List Container --}}
                                                        <div class="flex-1 bg-gray-50 rounded-md border border-gray-100 overflow-hidden flex flex-col relative min-h-[200px]">
                                                            <ul class="text-list list-disc pl-8 pr-4 py-2 text-sm overflow-y-auto flex-1 space-y-1">
                                                                {{-- JS akan mengisi ini --}}
                                                            </ul>
                                                            {{-- Empty State --}}
                                                            <div class="text-empty hidden absolute inset-0 flex items-center justify-center text-gray-400 text-sm">
                                                                Tidak ada data ditemukan.
                                                            </div>
                                                        </div>

                                                        {{-- Pagination Controls --}}
                                                        <div class="text-pagination flex items-center justify-between mt-3 text-xs text-gray-500">
                                                            <span class="text-info">Menampilkan 0 data</span>
                                                            <div class="flex gap-1">
                                                                <button onclick="textManager('{{ $uniqueId }}').prev()" class="btn-prev px-2 py-1 bg-white border border-gray-300 rounded hover:bg-gray-100 disabled:opacity-50">&laquo;</button>
                                                                <button onclick="textManager('{{ $uniqueId }}').next()" class="btn-next px-2 py-1 bg-white border border-gray-300 rounded hover:bg-gray-100 disabled:opacity-50">&raquo;</button>
                                                            </div>
                                                        </div>
                                                        
                                                        {{-- Hidden Data Store (Raw JSON) --}}
                                                        <script type="application/json" class="raw-data">
                                                            @json($textAnswers)
                                                        </script>
                                                    </div>
                                                @endif
                                            </div>
                                        </article>
                                    @endforeach
                                </div>
                            </section>
                        @else
                            <div class="p-12 text-center text-gray-500 bg-white rounded-xl border border-dashed border-gray-300">
                                <p>Tidak ada data demografi yang tersedia.</p>
                            </div>
                        @endif
                    </div>

                    {{-- SCRIPT KHUSUS UNTUK HANDLE SEARCH & PAGINATION --}}
                    <script>
                        // Namespace global untuk Text Manager
                        window.textManagers = {};

                        class TextDataManager {
                            constructor(containerId) {
                                this.container = document.getElementById(containerId);
                                if (!this.container) return;

                                // Load Data
                                const rawScript = this.container.querySelector('.raw-data');
                                try {
                                    this.originalData = JSON.parse(rawScript.textContent);
                                } catch (e) { this.originalData = []; }

                                this.filteredData = [...this.originalData];
                                this.currentPage = 1;
                                this.itemsPerPage = 10;
                                this.searchQuery = '';

                                // Elements
                                this.listEl = this.container.querySelector('.text-list');
                                this.emptyEl = this.container.querySelector('.text-empty');
                                this.infoEl = this.container.querySelector('.text-info');
                                this.btnPrev = this.container.querySelector('.btn-prev');
                                this.btnNext = this.container.querySelector('.btn-next');

                                this.render();
                            }

                            search(query) {
                                this.searchQuery = query.toLowerCase();
                                this.currentPage = 1;
                                if (this.searchQuery === '') {
                                    this.filteredData = [...this.originalData];
                                } else {
                                    this.filteredData = this.originalData.filter(item => 
                                        String(item).toLowerCase().includes(this.searchQuery)
                                    );
                                }
                                this.render();
                            }

                            setLimit(limit) {
                                this.itemsPerPage = limit === 'all' ? this.filteredData.length : parseInt(limit);
                                this.currentPage = 1;
                                this.render();
                            }

                            prev() {
                                if (this.currentPage > 1) {
                                    this.currentPage--;
                                    this.render();
                                }
                            }

                            next() {
                                const maxPage = Math.ceil(this.filteredData.length / this.itemsPerPage);
                                if (this.currentPage < maxPage) {
                                    this.currentPage++;
                                    this.render();
                                }
                            }

                            render() {
                                // Calculate slice
                                const start = (this.currentPage - 1) * this.itemsPerPage;
                                const end = start + this.itemsPerPage;
                                const paginatedItems = this.filteredData.slice(start, end);
                                const total = this.filteredData.length;
                                const maxPage = Math.ceil(total / this.itemsPerPage) || 1;

                                // Update List HTML
                                this.listEl.innerHTML = '';
                                if (paginatedItems.length === 0) {
                                    this.emptyEl.classList.remove('hidden');
                                } else {
                                    this.emptyEl.classList.add('hidden');
                                    paginatedItems.forEach(item => {
                                        const li = document.createElement('li');
                                        li.className = 'py-1 border-b border-gray-200/50 last:border-none text-gray-600 break-words';
                                        li.textContent = item;
                                        this.listEl.appendChild(li);
                                    });
                                }

                                // Update Info Text
                                const showStart = total === 0 ? 0 : start + 1;
                                const showEnd = Math.min(end, total);
                                this.infoEl.textContent = `Data ${showStart} - ${showEnd} dari ${total}`;

                                // Update Buttons
                                this.btnPrev.disabled = this.currentPage === 1;
                                this.btnNext.disabled = this.currentPage >= maxPage;
                            }
                        }

                        // Helper Function Global
                        function textManager(id) {
                            if (!window.textManagers[id]) {
                                window.textManagers[id] = new TextDataManager(id);
                            }
                            return window.textManagers[id];
                        }

                        // Initialize all text managers on load
                        document.addEventListener('DOMContentLoaded', () => {
                            document.querySelectorAll('.text-manager').forEach(el => {
                                textManager(el.id);
                            });
                        });
                    </script>

                    {{-- TAB 2: ANALISIS VARIAN (TABEL) --}}
                    {{-- TAB 2: ANALISIS VARIAN (TABEL STATISTIK LENGKAP) --}}
                    <div id="tab-anova" class="tab-content">
                    @php
                        // =================================================================================
                        // 1. ENGINE STATISTIK (ANOVA & MATH HELPER)
                        // =================================================================================
                        
                        // A. Fungsi Standar Deviasi (Sample SD)
                        if (!function_exists('stats_sd')) {
                            function stats_sd($array) {
                                $n = count($array);
                                if ($n <= 1) return 0;
                                $mean = array_sum($array) / $n;
                                $carry = 0.0;
                                foreach ($array as $val) { $carry += pow($val - $mean, 2); }
                                return sqrt($carry / ($n - 1));
                            }
                        }

                        // B. Fungsi Menghitung ANOVA One-Way (F-Value & P-Value)
                        if (!function_exists('stats_anova')) {
                            function stats_anova($groupedData) {
                                // $groupedData format: ['Male' => [80, 90], 'Female' => [70, 75]]
                                
                                // 1. Flat data & Global Mean
                                $allValues = [];
                                foreach ($groupedData as $group) {
                                    foreach ($group as $v) { $allValues[] = $v; }
                                }
                                $N = count($allValues);
                                $k = count($groupedData); // Jumlah grup
                                
                                if ($N <= $k || $k < 2) return ['f' => 0, 'p' => 1]; // Tidak bisa dihitung

                                $grandMean = array_sum($allValues) / $N;

                                // 2. Sum of Squares Between (SSB)
                                $ssb = 0;
                                foreach ($groupedData as $group) {
                                    $n_i = count($group);
                                    if ($n_i == 0) continue;
                                    $mean_i = array_sum($group) / $n_i;
                                    $ssb += $n_i * pow($mean_i - $grandMean, 2);
                                }

                                // 3. Sum of Squares Within (SSW)
                                $ssw = 0;
                                foreach ($groupedData as $group) {
                                    $n_i = count($group);
                                    if ($n_i == 0) continue;
                                    $mean_i = array_sum($group) / $n_i;
                                    foreach ($group as $val) {
                                        $ssw += pow($val - $mean_i, 2);
                                    }
                                }

                                // 4. Degrees of Freedom
                                $df_between = $k - 1;
                                $df_within  = $N - $k;

                                // 5. Mean Squares
                                $ms_between = $ssb / $df_between;
                                $ms_within  = ($df_within > 0) ? ($ssw / $df_within) : 0;

                                // 6. F-Value
                                if ($ms_within == 0) return ['f' => 0, 'p' => 1];
                                $f = $ms_between / $ms_within;

                                // 7. P-Value Approximation (Simple logic for display)
                                // Note: Menghitung Exact P-value butuh fungsi Incomplete Beta. 
                                // Di sini kita pakai pendekatan sederhana atau library PHP jika ada.
                                // Jika server tidak punya stats extension, kita return F saja dan P null (atau estimasi kasar).
                                
                                $p = stats_f_probability($f, $df_between, $df_within);

                                return ['f' => $f, 'p' => $p];
                            }
                        }

                        // C. Helper Estimasi Probabilitas F (Logic Aproksimasi)
                        if (!function_exists('stats_f_probability')) {
                            function stats_f_probability($f, $d1, $d2) {
                                if ($f <= 0) return 1.0;
                                // Menggunakan pendekatan sederhana Paulson (1942) untuk normalisasi F ke Z-score
                                // Ini estimasi agar tidak perlu library berat.
                                // Ref: Approximations to the F-distribution
                                
                                $x = $d2 / ($d2 + $d1 * $f);
                                
                                // Jika d1 atau d2 kecil, estimasi ini kurang akurat tapi cukup untuk visualisasi web "Signifikan/Tidak"
                                // Untuk akurasi akademik 100% disarankan pakai SPSS, tapi ini "best effort" di PHP native.
                                
                                // Logika fallback sederhana: Semakin besar F, semakin kecil P
                                // Kita gunakan library 'stats_cdf_f' jika ada di server (jarang ada di shared hosting)
                                if (function_exists('stats_cdf_f')) {
                                    return 1 - stats_cdf_f($f, $d1, $d2, 1); 
                                }
                                
                                // Fallback manual (sangat kasar, hanya untuk indikator visual)
                                // Formula Log-Linear approximation untuk p-value < 0.05
                                // Disarankan memberi catatan kaki.
                                return null; // Return null agar di view kita bisa handle sebagai "See details"
                            }
                        }

                        // =================================================================================
                        // 2. PERSIAPAN DATA SURVEY
                        // =================================================================================
                        $responses = $responses;
                        
                        // Hitung Total Skor Likert per Responden
                        $respondentScores = [];
                        foreach ($responses as $resp) {
                            $totalScore = 0;
                            if(isset($resp->answers['likert'])) {
                                foreach($resp->answers['likert'] as $lAnswer) {
                                    $totalScore += (int)($lAnswer['answer'] ?? 0);
                                }
                            }
                            $respondentScores[$resp->id] = $totalScore;
                        }

                        // Daftar Pertanyaan Demografi
                        $demoQuestions = $survey->questionnaireTemplate->demographic_questions ?? [];
                        if(!empty($survey->questionnaireTemplate->origin_country_question)) {
                            array_unshift($demoQuestions, [
                                'question_text' => 'Asal Negara',
                                'type' => 'dropdown',
                                'options' => [],
                                'is_permanent' => true
                            ]);
                        }
                    @endphp

                    <div class="space-y-8">

                        {{-- ================================================================= --}}
                        {{-- TABEL 1: FREKUENSI (DEMOGRAPHIC DATA) --}}
                        {{-- ================================================================= --}}
                        <section class="soft-card rounded-2xl p-6 relative group">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="font-bold text-xl text-gray-800">Item Demographic Data</h3>
                                <button onclick="downloadCSV('table-demografis', 'Demographic_Data.csv')" class="text-sm bg-green-50 text-green-700 px-3 py-1.5 rounded-lg font-semibold hover:bg-green-100 border border-green-200 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                    Download CSV
                                </button>
                            </div>
                            
                            <div class="overflow-x-auto border border-gray-300 rounded-lg">
                                <table id="table-demografis" class="w-full text-sm text-left">
                                    <thead class="text-xs text-gray-700 uppercase bg-gray-100 border-b border-gray-300">
                                        <tr>
                                            <th class="px-6 py-3 border-r border-gray-300 w-3/4">Item Demographic Data</th>
                                            <th class="px-6 py-3 text-center border-r border-gray-300 w-1/6">N</th>
                                            <th class="px-6 py-3 text-center w-1/6">%</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach ($demoQuestions as $index => $question)
                                            @php
                                                // --- [SOLUSI] FILTER TIPE PERTANYAAN ---
                                                // Skip jika tipe 'isian' (kecuali permanen/negara)
                                                $qType = $question['type'] ?? 'isian';
                                                $isPerm = $question['is_permanent'] ?? false;
                                                
                                                if (!$isPerm && $qType !== 'dropdown') {
                                                    continue; // Skip pertanyaan ini, lanjut ke yang berikutnya
                                                }
                                                // ---------------------------------------

                                                $qTitle = $question['question_text'];
                                                $qKey   = isset($question['is_permanent']) ? 'origin_country' : $index;
                                                $counts = []; $totalN = 0;
                                                foreach ($responses as $resp) {
                                                    $ans = $resp->answers['demographic'][$qKey]['answer'] ?? null;
                                                    if ($ans) {
                                                        if (!isset($counts[$ans])) $counts[$ans] = 0;
                                                        $counts[$ans]++; $totalN++;
                                                    }
                                                }
                                                ksort($counts);
                                            @endphp
                                            <tr class="bg-gray-50 font-bold" data-csv-row>
                                                <td class="px-6 py-2 border-r border-gray-200" colspan="3">{{ $qTitle }}</td>
                                            </tr>
                                            @foreach ($counts as $label => $count)
                                                <tr class="hover:bg-gray-50">
                                                    <td class="px-6 py-2 border-r border-gray-200 pl-10">{{ $label }}</td>
                                                    <td class="px-6 py-2 border-r border-gray-200 text-center">{{ $count }}</td>
                                                    <td class="px-6 py-2 text-center">{{ $totalN > 0 ? number_format(($count/$totalN)*100, 1) : 0 }}</td>
                                                </tr>
                                            @endforeach
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </section>

                        {{-- ================================================================= --}}
                        {{-- TABEL 2: ANOVA (MEAN, SD, F, P) --}}
                        {{-- ================================================================= --}}
                        <section class="soft-card rounded-2xl p-6">
                            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4 gap-4">
                                <h3 class="font-bold text-xl text-gray-800 flex-1">
                                    {{ $survey->title }} based on demographic characteristics
                                </h3>
                                <button onclick="downloadCSV('table-anova', 'ANOVA_Results.csv')" class="shrink-0 text-sm bg-indigo-50 text-indigo-700 px-3 py-1.5 rounded-lg font-semibold hover:bg-indigo-100 border border-indigo-200 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                    Download CSV
                                </button>
                            </div>
                            
                            <div class="overflow-x-auto border border-gray-300 rounded-lg">
                                <table id="table-anova" class="w-full text-sm text-left">
                                    <thead class="text-xs text-gray-700 uppercase bg-gray-100 border-b border-gray-300">
                                        <tr>
                                            <th class="px-6 py-3 border-r border-gray-300 w-1/3">Demographic Characteristics</th>
                                            <th class="px-6 py-3 text-center border-r border-gray-300 w-1/6">M (Mean)</th>
                                            <th class="px-6 py-3 text-center border-r border-gray-300 w-1/6">SD</th>
                                            <th class="px-6 py-3 text-center border-r border-gray-300 w-1/6">F</th>
                                            <th class="px-6 py-3 text-center w-1/6">Sig. (P)</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach ($demoQuestions as $index => $question)
                                            @php
                                                // --- [SOLUSI] FILTER TIPE PERTANYAAN ---
                                                $qType = $question['type'] ?? 'isian';
                                                $isPerm = $question['is_permanent'] ?? false;
                                                
                                                if (!$isPerm && $qType !== 'dropdown') {
                                                    continue; // Skip pertanyaan ini
                                                }
                                                // ---------------------------------------

                                                $qTitle = $question['question_text'];
                                                $qKey   = isset($question['is_permanent']) ? 'origin_country' : $index;
                                                
                                                // Grouping Data
                                                $groupedScores = [];
                                                foreach ($responses as $resp) {
                                                    $ans = $resp->answers['demographic'][$qKey]['answer'] ?? null;
                                                    $score = $respondentScores[$resp->id] ?? 0;
                                                    if ($ans) {
                                                        if (!isset($groupedScores[$ans])) $groupedScores[$ans] = [];
                                                        $groupedScores[$ans][] = $score;
                                                    }
                                                }
                                                ksort($groupedScores);

                                                // HITUNG ANOVA (F & P)
                                                $stats = stats_anova($groupedScores);
                                                $fValue = $stats['f'];
                                                $pValue = $stats['p'];
                                                
                                                // Format P-value text
                                                $pText = ($pValue === null) ? '(*)' : number_format($pValue, 3);
                                                if ($pValue !== null && $pValue < 0.001) $pText = '< 0.001';
                                            @endphp

                                            {{-- HEADER ROW (Kategori + Hasil ANOVA) --}}
                                            <tr class="bg-gray-50 border-b border-gray-200">
                                                <td class="px-6 py-2 border-r border-gray-200 font-bold">{{ $qTitle }}</td>
                                                <td class="px-6 py-2 border-r border-gray-200 bg-gray-100"></td>
                                                <td class="px-6 py-2 border-r border-gray-200 bg-gray-100"></td>
                                                
                                                {{-- Menampilkan Hasil F dan P di baris header kategori --}}
                                                <td class="px-6 py-2 border-r border-gray-200 text-center font-bold text-gray-700">
                                                    {{ number_format($fValue, 3) }}
                                                </td>
                                                <td class="px-6 py-2 text-center font-bold {{ ($pValue !== null && $pValue < 0.05) ? 'text-green-600' : 'text-gray-500' }}">
                                                    {{ $pText }}
                                                </td>
                                            </tr>

                                            {{-- DATA ROWS (Opsi + Mean + SD) --}}
                                            @foreach ($groupedScores as $label => $scores)
                                                @php
                                                    $n = count($scores);
                                                    $mean = $n > 0 ? array_sum($scores) / $n : 0;
                                                    $sd = stats_sd($scores);
                                                @endphp
                                                <tr class="hover:bg-gray-50 border-b border-gray-100">
                                                    <td class="px-6 py-2 border-r border-gray-200 pl-10">{{ $label }}</td>
                                                    <td class="px-6 py-2 border-r border-gray-200 text-center font-medium">
                                                        {{ number_format($mean, 2) }}
                                                    </td>
                                                    <td class="px-6 py-2 border-r border-gray-200 text-center">
                                                        {{ number_format($sd, 3) }}
                                                    </td>
                                                    <td class="px-6 py-2 border-r border-gray-200 bg-gray-50"></td>
                                                    <td class="px-6 py-2 bg-gray-50"></td>
                                                </tr>
                                            @endforeach

                                        @endforeach
                                    </tbody>
                                </table>
                                <div class="p-3 text-xs text-gray-500 bg-gray-50 border-t border-gray-200 italic">
                                    * Catatan: Nilai P (*) menandakan perhitungan signifikansi memerlukan software statistik khusus. Jika angka P muncul, itu adalah estimasi. Nilai F dihitung menggunakan metode One-Way ANOVA.
                                </div>
                            </div>
                        </section>
                    </div>

                    {{-- Script Khusus Download CSV --}}
                    <script>
                    function downloadCSV(tableId, filename) {
                        var csv = [];
                        var rows = document.querySelectorAll('#' + tableId + ' tr');
                        
                        for (var i = 0; i < rows.length; i++) {
                            var row = [], cols = rows[i].querySelectorAll("td, th");
                            
                            for (var j = 0; j < cols.length; j++) {
                                // Bersihkan text dari enter/spasi berlebih
                                var data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, "").replace(/\s+/g, " ").trim();
                                // Escape kutip dua
                                data = data.replace(/"/g, '""');
                                // Masukkan ke row CSV
                                row.push('"' + data + '"');
                            }
                            csv.push(row.join(","));        
                        }

                        var csvFile = new Blob([csv.join("\n")], {type: "text/csv"});
                        var downloadLink = document.createElement("a");
                        downloadLink.download = filename;
                        downloadLink.href = window.URL.createObjectURL(csvFile);
                        downloadLink.style.display = "none";
                        document.body.appendChild(downloadLink);
                        downloadLink.click();
                    }
                    </script>
                </div>

                    {{-- TAB 3: DESKRIPSI ANALISIS (GRAFIK) --}}
                    <div id="tab-desc" class="tab-content">
                        @if (!empty($results['likert']))
                            <section class="soft-card rounded-2xl p-6">
                                <h3 class="font-bold text-xl mb-4 flex items-center gap-2">
                                    <svg class="w-6 h-6 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
                                    Deskripsi Analisis (Grafik)
                                </h3>

                                <div class="space-y-8">
                                    @foreach ($results['likert'] as $question => $result)
                                        @if($result)
                                            @php
                                                // Logika Data Chart (Sama seperti sebelumnya)
                                                $scale  = (int)($result['scale'] ?? 5);
                                                $manner = strtolower($result['manner'] ?? 'positive');
                                                $orig   = $result['distribution'] ?? [];
                                                $distRaw = array_fill_keys(range(1, $scale), 0);
                                                foreach ($orig as $s => $c) { $s=(int)$s; if ($s>=1 && $s<=$scale) $distRaw[$s] += (int)$c; }
                                                
                                                // Remap Negative Manner
                                                $dist = $distRaw;
                                                if ($manner === 'negative') {
                                                    $tmp = array_fill_keys(range(1, $scale), 0);
                                                    foreach ($distRaw as $s => $c) { $tmp[$scale + 1 - $s] += (int)$c; }
                                                    $dist = $tmp;
                                                }

                                                $labels = []; foreach (range(1, $scale) as $s) { $labels[] = ($result['labels'][$s] ?? "Skor $s"); }
                                                $values = []; foreach (range(1, $scale) as $s) { $values[] = (int)($dist[$s] ?? 0); }
                                                $total = array_sum($values);
                                                $mean  = $result['average_score'] ?? 0;
                                                $chartId = 'chart-likert-'.$loop->index;
                                            @endphp

                                            <article class="rounded-xl border border-gray-100 p-5 bg-white shadow-sm">
                                                <header class="mb-4">
                                                    <h4 class="font-semibold text-gray-900 text-lg">{{ $loop->iteration }}. {{ $question }}</h4>
                                                    <div class="flex gap-3 mt-2">
                                                        <span class="px-2 py-1 bg-indigo-50 text-indigo-700 text-xs font-bold rounded">Mean: {{ $mean }}</span>
                                                        <span class="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded">Manner: {{ ucfirst($manner) }}</span>
                                                    </div>
                                                </header>

                                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                                    {{-- Area Chart --}}
                                                    <div class="md:col-span-2 h-64 relative">
                                                        <canvas id="{{ $chartId }}" data-labels='@json($labels)' data-values='@json($values)'></canvas>
                                                    </div>

                                                    {{-- Summary Stats Kecil --}}
                                                    <div class="flex flex-col justify-center space-y-3">
                                                        @foreach ($labels as $i => $lbl)
                                                            @php $v=$values[$i]??0; $pct=$total?($v/$total*100):0; @endphp
                                                            <div class="text-sm">
                                                                <div class="flex justify-between mb-1">
                                                                    <span class="text-gray-600">{{ $lbl }}</span>
                                                                    <span class="font-bold tabular-nums">{{ round($pct) }}%</span>
                                                                </div>
                                                                <div class="w-full bg-gray-100 rounded-full h-2">
                                                                    <div class="bg-indigo-500 h-2 rounded-full" style="width: {{ $pct }}%"></div>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </article>
                                        @endif
                                    @endforeach
                                </div>
                            </section>
                        @endif
                    </div>

                @else
                    <div class="soft-card rounded-2xl p-12 text-center">
                        <p class="text-gray-600">Belum ada data respons untuk ditampilkan.</p>
                    </div>
                @endif
            </div>
        </div>
    </main>

    <footer class="text-center py-6 mt-8 bg-white border-t">
        <p class="text-gray-400">© {{ date('Y') }} UMIT. All rights reserved.</p>
    </footer>

    {{-- ================== SCRIPTS ================== --}}
    <script>
    // FUNGSI TABS
    function switchTab(tabName) {
        // Hide all content
        document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
        // Deactivate all buttons
        document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
        
        // Show selected
        document.getElementById('tab-' + tabName).classList.add('active');
        document.getElementById('btn-' + tabName).classList.add('active');
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (window.ChartDataLabels) { Chart.register(ChartDataLabels); }
        const qs = (sel, ctx=document) => Array.from(ctx.querySelectorAll(sel));
        const palette = (n, seed=0) => {
            const arr = []; for (let i=0;i<n;i++){ const hue = Math.round(((360/Math.max(3,n))*(i+seed))%360); arr.push(`hsl(${hue} 70% 55% / 0.85)`); }
            return arr;
        };

        // RENDER DONUT DEMOGRAFI
        qs('canvas[id^="chart-demo-"]').forEach((el, idx) => {
            const labels = JSON.parse(el.getAttribute('data-labels') || '[]');
            const values = JSON.parse(el.getAttribute('data-values') || '[]');
            const colors = palette(labels.length, idx);

            new Chart(el.getContext('2d'), {
                type: 'doughnut',
                data: { labels, datasets: [{ data: values, backgroundColor: colors, borderWidth: 1 }] },
                options: {
                    responsive:true, maintainAspectRatio:false,
                    plugins:{ legend:{position:'bottom'}, datalabels:{ color:'#fff', font:{weight:'bold'}, formatter:(v,ctx)=>{ const t=ctx.chart.data.datasets[0].data.reduce((s,x)=>s+x,0); return t?(v/t*100).toFixed(0)+'%':''; } } }
                }
            });
        });

        // RENDER BAR LIKERT (DESKRIPSI ANALISIS)
        qs('canvas[id^="chart-likert-"]').forEach((el, idx) => {
            const labels = JSON.parse(el.getAttribute('data-labels') || '[]');
            const values = JSON.parse(el.getAttribute('data-values') || '[]');

            new Chart(el.getContext('2d'), {
                type: 'bar',
                data: { labels, datasets: [{ label:'Responden', data: values, backgroundColor: '#6366f1', borderRadius:4 }] },
                options: {
                    indexAxis: 'y', responsive:true, maintainAspectRatio:false,
                    scales: { x:{beginAtZero:true, grid:{display:false}}, y:{grid:{display:false}} },
                    plugins:{ legend:{display:false}, datalabels:{ anchor:'end', align:'end', color:'#333', formatter:(v)=>v>0?v:'' } }
                }
            });
        });
    });
    </script>
</body>
</html>