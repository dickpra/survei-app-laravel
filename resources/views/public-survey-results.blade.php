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
        :root{
            --primary:#4f46e5; --primary-dark:#4338ca; --bg-soft:#eef2ff;
        }
        body{ font-family:'Be Vietnam Pro',system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif; }
        .brand-shadow{ box-shadow:0 2px 6px rgba(0,0,0,.05) }
        .hero-grad{ background:linear-gradient(180deg,#f8fafc 0%, #eef2ff 100%) }
        .soft-card{ background:#fff; border:1px solid #eef2ff; box-shadow:0 4px 12px rgba(0,0,0,.03) }
        .btn-primary{ background:var(--primary); color:#fff } .btn-primary:hover{ background:var(--primary-dark) }
        .btn-muted{ background:#f1f5f9; color:#374151 } .btn-muted:hover{ background:#e5e7eb }
        .tabular-nums{ font-variant-numeric: tabular-nums; }
    </style>
</head>
<body class="bg-gray-50">

    {{-- ================= HEADER ================= --}}
    <header class="bg-white brand-shadow">
        <nav class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
            <a href="{{ route('home') }}" class="text-2xl font-extrabold text-indigo-600">UMIT</a>
            <a href="{{ route('home') }}" class="px-4 py-2 rounded-lg font-semibold text-sm text-gray-700 hover:bg-gray-100">&larr; Kembali ke Dasbor</a>
        </nav>
    </header>

    <main class="max-w-7xl mx-auto px-4">
        {{-- ================= HERO ================= --}}
        <section class="hero-grad rounded-2xl mt-6 p-6 md:p-10 text-center soft-card border-0">
            <h1 class="text-3xl md:text-4xl font-extrabold text-gray-900 tracking-tight">{{ $survey->title }}</h1>
            <p class="mt-3 text-gray-600 max-w-2xl mx-auto">
                Ringkasan hasil survei publik. Gunakan filter untuk menyaring respon sesuai demografi yang tersedia.
            </p>
            <div class="mt-6 flex items-center justify-center gap-3 flex-wrap">
                <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-indigo-50 text-indigo-700 text-sm font-semibold">
                    Dibuat: <span class="tabular-nums">{{ $survey->created_at?->format('d M Y') }}</span>
                </span>
                <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-50 text-emerald-700 text-sm font-semibold">
                    Total Responden: <span class="tabular-nums font-bold">{{ number_format($totalResponses) }}</span>
                </span>
            </div>
        </section>

        <div class="flex flex-col lg:flex-row gap-8 mt-8">
            {{-- ================= SIDEBAR FILTER ================= --}}
            <aside class="w-full lg:w-1/4">
                <div class="sticky top-8 soft-card rounded-2xl p-5">
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

            {{-- ================= KONTEN HASIL ================= --}}
            <div class="w-full lg:w-3/4">
                @if($totalResponses > 0)

                    {{-- ============= DEMOGRAFIS ============= --}}
                    @if (!empty($results['demographic']))
                        <section class="soft-card rounded-2xl p-6 mb-8">
                            <h3 class="font-bold text-xl mb-4"> {{ $survey->questionnaireTemplate->demographic_title ?? 'Hasil Demografis' }} </h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                @foreach ($results['demographic'] as $question => $result)
                                    @php
                                        $isAggregate = ($result['type'] ?? '') === 'aggregate';
                                        $chartId = 'chart-demo-'.$loop->index;
                                        $labels = $isAggregate ? array_keys($result['answers'] ?? []) : [];
                                        $values = $isAggregate ? array_values($result['answers'] ?? []) : [];
                                        $total  = $isAggregate ? array_sum($values) : 0;
                                    @endphp

                                    <article class="rounded-xl border border-indigo-50 p-4 bg-white hover:shadow-md transition-shadow">
                                        <header class="flex items-start justify-between gap-3">
                                            <h4 class="font-semibold text-gray-900">{{ $question }}</h4>
                                            @if($isAggregate && $total > 0)
                                                <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full bg-slate-100 text-slate-700">
                                                    {{ count($labels) }} opsi
                                                </span>
                                            @endif
                                        </header>

                                        @if($isAggregate && !empty($labels))
                                            <div class="mt-3 grid grid-cols-1 gap-4">
                                                <div class="h-64 relative">
                                                    <canvas id="{{ $chartId }}"
                                                            data-labels='@json($labels)'
                                                            data-values='@json($values)'></canvas>
                                                </div>

                                                {{-- Legend kustom --}}
                                                <ul class="text-sm space-y-1">
                                                    @foreach($labels as $i => $lbl)
                                                        @php
                                                            $v = $values[$i] ?? 0;
                                                            $pct = $total ? ($v/$total*100) : 0;
                                                        @endphp
                                                        <li class="flex items-center justify-between bg-gray-50 rounded-md px-3 py-2">
                                                            <span class="truncate">{{ $lbl }}</span>
                                                            <span class="tabular-nums font-semibold">{{ $v }}</span>
                                                            <span class="tabular-nums text-gray-500">({{ number_format($pct,1) }}%)</span>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @else
                                            {{-- Free text: scrollable --}}
                                            <div class="mt-3">
                                                <ul class="list-disc pl-5 text-sm bg-gray-50 rounded-md p-3 h-64 overflow-y-auto">
                                                    @forelse(($result['answers'] ?? []) as $ans)
                                                        <li class="py-1 border-b border-gray-200/70 last:border-none">{{ $ans }}</li>
                                                    @empty
                                                        <li class="text-gray-500">Tidak ada jawaban.</li>
                                                    @endforelse
                                                </ul>
                                            </div>
                                        @endif
                                    </article>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    {{-- ============= LIKERT ============= --}}
                    @if (!empty($results['likert']))
                        <section class="soft-card rounded-2xl p-6">
                            <h3 class="font-bold text-xl mb-4">{{ $survey->questionnaireTemplate->likert_title ?? 'Hasil Skala Likert' }}</h3>

                            <div class="space-y-6">
                                @foreach ($results['likert'] as $question => $result)
                                    @if($result)
                                        @php
                                            // Ambil komponen utama
                                            $scale  = (int)($result['scale'] ?? 5);
                                            $manner = strtolower($result['manner'] ?? 'positive');
                                            $orig   = $result['distribution'] ?? [];

                                            // 1) Pastikan distribusi penuh 1..scale (jangan hilang slot)
                                            $distRaw = array_fill_keys(range(1, $scale), 0);
                                            foreach ($orig as $s => $c) {
                                                $s = (int)$s;
                                                if ($s>=1 && $s<=$scale) $distRaw[$s] += (int)$c;
                                            }

                                            // 2) REMAP NILAI untuk manner negatif (LABEL TETAP 1..scale)
                                            $dist = $distRaw;
                                            if ($manner === 'negative') {
                                                $tmp = array_fill_keys(range(1, $scale), 0);
                                                foreach ($distRaw as $s => $c) {
                                                    $tmp[$scale + 1 - $s] += (int)$c;
                                                }
                                                $dist = $tmp;
                                            }

                                            // 3) Susun labels 1..scale (TETAP sama untuk semua manner)
                                            $labels = [];
                                            foreach (range(1, $scale) as $s) {
                                                // label backend sudah siap untuk 1..N
                                                $labels[] = ($result['labels'][$s] ?? "Skor $s");

                                            }

                                            // 4) Nilai chart menurut dist tampilan
                                            $values = [];
                                            foreach (range(1, $scale) as $s) { $values[] = (int)($dist[$s] ?? 0); }

                                            // Statistik dasar
                                            $total = array_sum($values);
                                            $mean  = $result['average_score'] ?? null; // sudah disesuaikan di backend
                                            // median tampilan
                                            $median = null;
                                            if ($total>0) {
                                                $mid = ($total + 1) / 2; $run=0;
                                                foreach (range(1,$scale) as $s) { $run += $dist[$s]; if ($run >= $mid) { $median = $s; break; } }
                                            }
                                            // modus tampilan
                                            $mode = null;
                                            if ($total>0) {
                                                $mx = max($values);
                                                foreach (range(1,$scale) as $s) { if ($dist[$s] === $mx) { $mode = $s; break; } }
                                            }

                                            $chartId = 'chart-likert-'.$loop->index;
                                        @endphp

                                        <article class="rounded-xl border border-indigo-50 p-4 bg-white hover:shadow-md transition-shadow space-y-3">
                                            <header class="flex items-center justify-between">
                                                <h4 class="font-semibold text-gray-900">{{ $loop->iteration }}. {{ $question }}</h4>
                                                <div class="flex items-center gap-2">
                                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-indigo-50 text-indigo-700 text-xs font-semibold">
                                                        Mean: <span class="tabular-nums">{{ $mean ?? '-' }}</span>@if($scale)/{{ $scale }}@endif
                                                    </span>
                                                    <span class="hidden md:inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-slate-100 text-slate-700 text-xs">
                                                        Median: <span class="tabular-nums">{{ $median ?? '-' }}</span>
                                                    </span>
                                                    <span class="hidden md:inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-slate-100 text-slate-700 text-xs">
                                                        Modus: <span class="tabular-nums">{{ $mode !== null ? ($result['labels'][$mode] ?? $mode) : '-' }}</span>
                                                    </span>
                                                    <span class="hidden md:inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-slate-100 text-slate-700 text-xs">
                                                        Total: <span class="tabular-nums">{{ $total }}</span>
                                                    </span>
                                                </div>
                                            </header>

                                            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                                                {{-- Chart --}}
                                                <div class="md:col-span-3 h-72 relative">
                                                    <canvas id="{{ $chartId }}"
                                                            data-labels='@json($labels)'
                                                            data-values='@json($values)'></canvas>
                                                </div>

                                                {{-- Detail Per Opsi --}}
                                                <div class="md:col-span-2">
                                                    <details class="rounded-lg border border-gray-200 open:shadow-sm" open>
                                                        <summary class="cursor-pointer list-none flex items-center justify-between px-3 py-2">
                                                            <span class="font-medium">Detail Per Opsi</span>
                                                            <svg class="w-4 h-4 opacity-70" viewBox="0 0 24 24" fill="none"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                                                        </summary>
                                                        <ul class="px-3 pb-3 space-y-2 text-sm">
                                                            @foreach ($labels as $i => $lbl)
                                                                @php
                                                                    $v = $values[$i] ?? 0;
                                                                    $pct = $total ? ($v/$total*100) : 0;
                                                                @endphp
                                                                <li class="flex items-center justify-between bg-gray-50 rounded-md px-3 py-2">
                                                                    <span class="flex-1 truncate">{{ $lbl }}</span>
                                                                    <span class="w-16 text-right font-semibold tabular-nums">{{ $v }}</span>
                                                                    <span class="w-16 text-right text-gray-500 tabular-nums">({{ number_format($pct,1) }}%)</span>
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    </details>
                                                </div>
                                            </div>
                                        </article>
                                    @endif
                                @endforeach
                            </div>
                        </section>
                    @endif

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
    document.addEventListener('DOMContentLoaded', () => {
        // Register plugin
        if (window.ChartDataLabels) { Chart.register(ChartDataLabels); }

        const qs = (sel, ctx=document) => Array.from(ctx.querySelectorAll(sel));
        const palette = (n, seed=0) => {
            const arr = []; for (let i=0;i<n;i++){ const hue = Math.round(((360/Math.max(3,n))*(i+seed))%360); arr.push(`hsl(${hue} 70% 55% / 0.85)`); }
            return arr;
        };
        const prim = (h=230)=>({ area:`hsl(${h} 80% 55% / .18)`, stroke:`hsl(${h} 85% 45% / 1)`, fill:`hsl(${h} 80% 55% / .65)` });

        // DEMOGRAFIS (doughnut)
        qs('canvas[id^="chart-demo-"]').forEach((el, idx) => {
            const labels = JSON.parse(el.getAttribute('data-labels') || '[]');
            const values = JSON.parse(el.getAttribute('data-values') || '[]');
            const colors = palette(labels.length, idx);

            new Chart(el.getContext('2d'), {
                type: 'doughnut',
                data: { labels, datasets: [{ data: values, backgroundColor: colors, borderColor: colors.map(c=>c.replace('/ 0.85','/ 1')), borderWidth: 1 }] },
                options: {
                    responsive:true, maintainAspectRatio:false,
                    plugins:{
                        legend:{ display:false },
                        datalabels:{
                            formatter:(v,ctx)=>{ const t=ctx.chart.data.datasets[0].data.reduce((s,x)=>s+x,0); const p=t?(v/t*100):0; return p<7?null:`${v} (${p.toFixed(1)}%)`; },
                            color:'#fff', font:{weight:'bold',size:11}, align:'center', anchor:'center'
                        },
                        tooltip:{ callbacks:{ label:(c)=>{ const t=c.dataset.data.reduce((s,x)=>s+x,0); const p=t?(c.parsed/t*100):0; return ` ${c.label}: ${c.formattedValue} (${p.toFixed(1)}%)`; } } }
                    },
                    cutout:'60%'
                }
            });
        });

        // LIKERT (horizontal bar)
        qs('canvas[id^="chart-likert-"]').forEach((el, idx) => {
            const labels = JSON.parse(el.getAttribute('data-labels') || '[]');
            const values = JSON.parse(el.getAttribute('data-values') || '[]');
            const p = prim(230 - (idx*13)%80);

            new Chart(el.getContext('2d'), {
                type: 'bar',
                data: { labels, datasets: [{ data: values, label:'Jumlah Responden', backgroundColor:p.fill, borderColor:p.stroke, borderWidth:2, borderRadius:8, barThickness:20, hoverBackgroundColor:'hsl(230 85% 50% / .95)' }] },
                options: {
                    indexAxis:'y', responsive:true, maintainAspectRatio:false,
                    plugins:{
                        legend:{ display:false },
                        datalabels:{ formatter:(v)=> v>0? v : '', color:'#0f172a', font:{weight:'bold'}, anchor:'end', align:'right', clamp:true },
                        tooltip:{ callbacks:{ label:(c)=>{ const t=c.dataset.data.reduce((s,x)=>s+x,0); const p=t?(c.parsed.x/t*100):0; return ` ${c.formattedValue} responden (${p.toFixed(1)}%)`; } } }
                    },
                    scales:{
                        x:{ beginAtZero:true, grid:{ color:'rgba(0,0,0,.06)' }, ticks:{ color:'#4b5563' } },
                        y:{ grid:{ display:false }, ticks:{ color:'#1f2937' } }
                    }
                }
            });
        });
    });
    </script>
</body>
</html>
