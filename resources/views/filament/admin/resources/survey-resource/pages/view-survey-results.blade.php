<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Header ringkas --}}
        <x-filament::card>
            <div class="text-center space-y-1">
                <h2 class="text-xl font-bold tracking-tight">Hasil untuk: {{ $this->record->title }}</h2>
                <p class="text-gray-500">
                    Total Responden:
                    <span class="font-bold text-primary-600 text-2xl">{{ $totalResponses ?? 0 }}</span>
                </p>
            </div>
        </x-filament::card>

        {{-- ========================= DEMOGRAFIS ========================= --}}
       {{-- ========================= DEMOGRAFIS (dengan kontainer & spacing) ========================= --}}
        @if (!empty($results['demographic']))
            <x-filament::card class="space-y-6">
                <h3 class="font-bold text-lg">
                    {{ $this->record->questionnaireTemplate->demographic_title ?? 'Demographic Data' }}
                </h3>

                <div class="space-y-6">
                    @foreach ($results['demographic'] as $question => $result)
                        @php
                            $type = $result['type'] ?? $result['tipe'] ?? '';
                            $isAggregate = in_array($type, ['aggregate','agregat']);
                            $chartIndex = "demo-{$loop->index}";
                            $labels = $isAggregate ? array_values(array_keys($result['answers'] ?? [])) : [];
                            $values = $isAggregate ? array_values($result['answers'] ?? []) : [];
                            $total  = $isAggregate ? array_sum($values) : 0;

                            $topLabel = null; $topCount = 0;
                            if ($isAggregate && $total > 0) {
                                foreach (($result['answers'] ?? []) as $lbl => $cnt) {
                                    if ($cnt > $topCount) { $topLabel = $lbl; $topCount = $cnt; }
                                }
                            }
                        @endphp

                        <div class="rounded-xl border border-gray-200 bg-white shadow-sm p-4 space-y-4">
                            <div class="flex justify-between items-center flex-wrap gap-2">
                                <h4 class="font-semibold">{{ $loop->iteration }}. {{ $question }}</h4>

                                @if ($isAggregate && !empty($labels))
                                    <div class="flex space-x-1 rounded-lg bg-gray-100 p-1">
                                        <button data-toggle="{{ $chartIndex }}" data-type="doughnut" class="chart-btn px-3 py-1 text-sm font-medium text-gray-700 rounded-md hover:bg-white">Doughnut</button>
                                        <button data-toggle="{{ $chartIndex }}" data-type="pie" class="chart-btn px-3 py-1 text-sm font-medium text-gray-700 rounded-md hover:bg-white">Pie</button>
                                        <button data-toggle="{{ $chartIndex }}" data-type="bar" class="chart-btn px-3 py-1 text-sm font-medium text-gray-700 rounded-md hover:bg-white">Bar</button>
                                        <button data-toggle="{{ $chartIndex }}" data-type="line" class="chart-btn px-3 py-1 text-sm font-medium text-gray-700 rounded-md hover:bg-white">Line</button>
                                    </div>
                                @endif
                            </div>

                            @if ($isAggregate && !empty($labels))
                                <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
                                    {{-- Chart + legend --}}
                                    <div class="md:col-span-3 space-y-4">
                                        <div class="w-full min-h-[20rem]" wire:ignore>
                                            <canvas
                                                id="{{ $chartIndex }}"
                                                data-labels='@json($labels)'
                                                data-values='@json($values)'
                                                data-seed="{{ $loop->index }}"
                                                data-manner="neutral"
                                            ></canvas>
                                        </div>
                                        <div id="legend-container-{{ $chartIndex }}"></div>
                                    </div>

                                    {{-- Info + Dropdown --}}
                                    <div class="md:col-span-2 space-y-4">
                                        <div class="grid grid-cols-2 gap-3">
                                            <div class="rounded-lg border border-gray-200 p-3">
                                                <div class="text-xs text-gray-500">Total</div>
                                                <div class="text-xl font-bold">{{ $total }}</div>
                                            </div>
                                            <div class="rounded-lg border border-gray-200 p-3">
                                                <div class="text-xs text-gray-500">Opsi Unik</div>
                                                <div class="text-xl font-bold">{{ count($labels) }}</div>
                                            </div>
                                            <div class="rounded-lg border border-gray-200 p-3 col-span-2">
                                                <div class="text-xs text-gray-500 mb-1">Opsi Teratas</div>
                                                <div class="text-sm">
                                                    @if($topLabel)
                                                        <span class="font-semibold">{{ $topLabel }}</span>
                                                        <span class="text-gray-500">— {{ $topCount }} ({{ $total ? number_format($topCount/$total*100,1) : 0 }}%)</span>
                                                    @else
                                                        <span class="text-gray-500">—</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        <details class="rounded-lg border border-gray-200 p-3 open:shadow-sm">
                                            <summary class="cursor-pointer list-none flex items-center justify-between">
                                                <span class="font-medium">Detail Per Opsi</span>
                                                <svg class="w-4 h-4 opacity-70" viewBox="0 0 24 24" fill="none"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                                            </summary>
                                            <ul class="mt-3 space-y-2 text-sm">
                                                @foreach ($labels as $i => $lbl)
                                                    @php
                                                        $v = $values[$i] ?? 0;
                                                        $pct = $total ? ($v/$total*100) : 0;
                                                    @endphp
                                                    <li class="flex items-center justify-between bg-gray-50 rounded-md px-3 py-2">
                                                        <span class="truncate">{{ $lbl }}</span>
                                                        <span class="font-semibold">{{ $v }}</span>
                                                        <span class="text-gray-500">({{ number_format($pct,1) }}%)</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </details>
                                    </div>
                                </div>
                            @else
                                {{-- ISIAN (free text) + pagination client-side --}}
                                <div
                                    x-data="answersPager({ items: @js(array_values($result['answers'] ?? [])), perPage: 5 })"
                                    x-cloak
                                    class="space-y-2"
                                >
                                    {{-- bar info + search + per-page --}}
                                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2 text-sm text-gray-600">
                                        <div>
                                            Menampilkan <span x-text="from"></span>–<span x-text="to"></span>
                                            dari <span x-text="total"></span> jawaban
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <label class="flex items-center gap-2">
                                                <span class="whitespace-nowrap">Cari</span>
                                                <input
                                                    type="text"
                                                    placeholder="ketik untuk cari…"
                                                    x-model.debounce.300ms="q"
                                                    class="rounded-md border-gray-300 text-sm"
                                                >
                                            </label>
                                            <label class="flex items-center gap-2">
                                                <span class="whitespace-nowrap">Per halaman</span>
                                                <select x-model.number="perPage" class="rounded-md border-gray-300 text-sm">
                                                    <option :value="5">5</option>
                                                    <option :value="10">10</option>
                                                    <option :value="20">20</option>
                                                    <option :value="50">50</option>
                                                </select>
                                            </label>
                                        </div>
                                    </div>

                                    {{-- list scrollable --}}
                                    <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-md max-h-72 overflow-y-auto">
                                        <template x-if="current.length === 0">
                                            <div class="text-sm text-gray-500">Tidak ada hasil.</div>
                                        </template>
                                        <ul class="list-disc pl-5 text-sm text-gray-700 dark:text-gray-200" x-show="current.length">
                                            <template x-for="(answer, idx) in current" :key="idx">
                                                <li class="py-1 border-b border-gray-200/70 last:border-none" x-text="answer"></li>
                                            </template>
                                        </ul>
                                    </div>

                                    {{-- pagination controls --}}
                                    <div class="flex items-center justify-between">
                                        <button @click="prev" :disabled="page === 1" class="px-3 py-1.5 text-sm rounded-md border border-gray-300 disabled:opacity-50">Prev</button>
                                        <div class="flex items-center gap-1">
                                            <template x-for="p in pages" :key="p">
                                                <button
                                                    @click="goto(p)"
                                                    class="px-2.5 py-1 text-sm rounded-md border"
                                                    :class="p === page ? 'bg-primary-600 text-white border-primary-600' : 'border-gray-300'"
                                                    x-text="p"
                                                ></button>
                                            </template>
                                        </div>
                                        <button @click="next" :disabled="page === pages" class="px-3 py-1.5 text-sm rounded-md border border-gray-300 disabled:opacity-50">Next</button>
                                    </div>
                                </div>

                            @endif
                        </div>
                    @endforeach
                </div>
            </x-filament::card>
        @endif



        {{-- ========================= LIKERT ========================= --}}
        {{-- ========================= LIKERT (kontainer + detail per opsi) ========================= --}}
        @if (!empty($results['likert']))
            <x-filament::card class="space-y-6">
                <h3 class="font-bold text-lg">
                    {{ $this->record->questionnaireTemplate->likert_title ?? 'Pertanyaan Skala Likert' }}
                </h3>

                <div class="space-y-6">
                    @foreach ($results['likert'] as $question => $result)
                        @if ($result)
                            @php
                            // Persiapan data & statistik
                            $origDist = $result['distribution'] ?? [];
                            $scale    = (int) ($result['scale'] ?? 5);
                            $manner   = strtolower($result['manner'] ?? 'positive');

                            // 1) Buat kerangka lengkap 1..scale agar tidak "kepotong"
                            $base = array_fill_keys(range(1, $scale), 0);
                            // normalisasi distribusi mentah ke kerangka lengkap (pastikan int)
                            $distRaw = $base;
                            foreach ($origDist as $s => $c) {
                                $s = (int) $s;
                                if ($s >= 1 && $s <= $scale) {
                                    $distRaw[$s] += (int) $c;
                                }
                            }

                            // 2) REMAP NILAI HANYA UNTUK TAMPILAN (label tetap 1..scale)
                            if ($manner === 'negative') {
                                $dist = $base; // siapkan kerangka tujuan 1..scale
                                foreach ($distRaw as $s => $c) {
                                    $dist[($scale + 1) - $s] += (int) $c; // pindah s -> (scale+1-s)
                                }
                            } else {
                                $dist = $distRaw;
                            }

                            // 3) Urut tampilan selalu 1..N
                            ksort($dist, SORT_NUMERIC);

                            // 4) Siapkan labels & values PERSIS sepanjang skala
                             $labels = [];
                            $values = [];
                            foreach (range(1, $scale) as $score) {
                                // angka yang DITAMPILKAN di label:
                                // - positive: 1..N
                                // - negative: dibalik → (scale + 1 - score)
                                $displayNumber = ($manner === 'negative') ? ($scale + 1 - $score) : $score;

                                // teks label tetap dari mapping POSITIF ($result['labels'][$score])
                                $labelText = $result['labels'][$score] ?? "Skor $score";

                                $labels[] = $labelText . " ($displayNumber)";
                                $values[] = (int) ($dist[$score] ?? 0);
                            }

                            $total = array_sum($values) ?: 0;

                            // 5) Statistik untuk tampilan (berdasarkan $dist yang SUDAH diremap)
                            $mean = $result['average_score'] ?? null; // backend sudah disesuaikan

                            // median
                            $median = null;
                            if ($total > 0) {
                                $mid = ($total + 1) / 2; $run = 0; $medScore = null;
                                foreach (range(1, $scale) as $s) {
                                    $run += (int) ($dist[$s] ?? 0);
                                    if ($run >= $mid) { $medScore = $s; break; }
                                }
                                $median = $medScore;
                            }

                            // mode
                            $modeScore = null;
                            if ($total > 0) {
                                $maxC = max($values);
                                foreach (range(1, $scale) as $s) {
                                    if ((int) ($dist[$s] ?? 0) === (int) $maxC) { $modeScore = $s; break; }
                                }
                            }

                            // Top/Bottom Box pakai dist yang SUDAH diremap (dinamis berdasar skala)
                            $top2 = $bottom2 = 0;
                            if ($total > 0) {
                                if ($scale >= 5) {
                                    $top2    = ( ( ($dist[$scale] ?? 0) + ($dist[$scale-1] ?? 0) ) / $total ) * 100;
                                    $bottom2 = ( ( ($dist[1] ?? 0)       + ($dist[2]       ?? 0) ) / $total ) * 100;
                                } else {
                                    // skala 3–4: pakai top-1 / bottom-1
                                    $top2    = ( ($dist[$scale] ?? 0) / $total ) * 100;
                                    $bottom2 = ( ($dist[1]      ?? 0) / $total ) * 100;
                                }
                            }

                            $chartIndex = "likert-{$loop->index}";
                        @endphp



                            <div class="rounded-xl border border-gray-200 bg-white shadow-sm p-4 space-y-4">
                                <div class="flex justify-between items-center flex-wrap gap-2">
                                    <h4 class="font-semibold flex items-center gap-2">
                                        <span>{{ $loop->iteration }}. {{ $question }}</span>
                                        @if ($manner === 'positive')
                                            <span class="bg-emerald-100 text-emerald-800 text-xs font-medium px-2.5 py-0.5 rounded-full">Positif</span>
                                        @else
                                            <span class="bg-rose-100 text-rose-800 text-xs font-medium px-2.5 py-0.5 rounded-full">Negatif</span>
                                        @endif
                                    </h4>

                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-sm font-semibold bg-primary-100 text-primary-800">
                                            Rata-rata (disesuaikan): {{ $mean ?? '-' }} @if($scale) / {{ $scale }} @endif
                                        </span>
                                        <div class="flex space-x-1 rounded-lg bg-gray-100 p-1">
                                            <button data-toggle="{{ $chartIndex }}" data-type="bar" class="chart-btn px-3 py-1 text-sm font-medium text-gray-700 rounded-md hover:bg-white">Bar</button>
                                            <button data-toggle="{{ $chartIndex }}" data-type="line" class="chart-btn px-3 py-1 text-sm font-medium text-gray-700 rounded-md hover:bg-white">Line</button>
                                            <button data-toggle="{{ $chartIndex }}" data-type="pie" class="chart-btn px-3 py-1 text-sm font-medium text-gray-700 rounded-md hover:bg-white">Pie</button>
                                            <button data-toggle="{{ $chartIndex }}" data-type="doughnut" class="chart-btn px-3 py-1 text-sm font-medium text-gray-700 rounded-md hover:bg-white">Doughnut</button>
                                        </div>
                                    </div>
                                </div>

                                @if (!empty($labels))
                                    <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
                                        {{-- Chart + legend --}}
                                        <div class="md:col-span-3 space-y-4">
                                            <div class="w-full min-h-[20rem]" wire:ignore>
                                               <canvas
    id="{{ $chartIndex }}"
    data-labels='@json($labels)'
    data-values='@json($values)'
    data-seed="{{ 100 + $loop->index }}"
    data-manner="{{ $manner }}"
></canvas>


                                            </div>
                                            <div id="legend-container-{{ $chartIndex }}"></div>
                                        </div>

                                        {{-- Ringkasan + Detail Per Opsi --}}
                                        <div class="md:col-span-2 space-y-4">
                                            <div class="grid grid-cols-2 gap-3">
                                                <div class="rounded-lg border border-gray-200 p-3">
                                                    <div class="text-xs text-gray-500">Total</div>
                                                    <div class="text-xl font-bold">{{ $total }}</div>
                                                </div>
                                                <div class="rounded-lg border border-gray-200 p-3">
                                                    <div class="text-xs text-gray-500">Mean</div>
                                                    <div class="text-xl font-bold">{{ $mean ?? '-' }}</div>
                                                </div>
                                                <div class="rounded-lg border border-gray-200 p-3">
                                                    <div class="text-xs text-gray-500">Median</div>
                                                    <div class="text-xl font-bold">{{ $median ?? '-' }}</div>
                                                </div>
                                                <div class="rounded-lg border border-gray-200 p-3">
                                                    <div class="text-xs text-gray-500">Modus</div>
                                                    <div class="text-xl font-bold">{{ $modeScore !== null ? ($result['labels'][$modeScore] ?? $modeScore) : '-' }}</div>
                                                </div>
                                                <div class="rounded-lg border border-gray-200 p-3 col-span-2">
                                                    <div class="text-xs text-gray-500">Top Box</div>
                                                    <div class="text-lg font-semibold">
                                                        {{ number_format($top2, 1) }}% <span class="text-xs text-gray-500">(@if($scale>=5) Top-2 @else Top-1 @endif)</span>
                                                    </div>
                                                    <div class="mt-2 h-2 w-full bg-gray-100 rounded">
                                                        <div class="h-2 {{ $manner === 'negative' ? 'bg-rose-500' : 'bg-emerald-500' }} rounded" style="width: {{ max(0,min(100,$top2)) }}%"></div>
                                                    </div>
                                                </div>
                                                <div class="rounded-lg border border-gray-200 p-3 col-span-2">
                                                    <div class="text-xs text-gray-500">Bottom Box</div>
                                                    <div class="text-lg font-semibold">
                                                        {{ number_format($bottom2, 1) }}% <span class="text-xs text-gray-500">(@if($scale>=5) Bottom-2 @else Bottom-1 @endif)</span>
                                                    </div>
                                                    <div class="mt-2 h-2 w-full bg-gray-100 rounded">
                                                        <div class="h-2 {{ $manner === 'negative' ? 'bg-rose-500' : 'bg-emerald-500' }} rounded" style="width: {{ max(0,min(100,$bottom2)) }}%"></div>
                                                    </div>
                                                </div>
                                            </div>

                                            {{-- Detail Per Opsi (dropdown, sama seperti Demographic) --}}
                                            <details class="rounded-lg border border-gray-200 p-3 open:shadow-sm">
                                                <summary class="cursor-pointer list-none flex items-center justify-between">
                                                    <span class="font-medium">Detail Per Opsi</span>
                                                    <svg class="w-4 h-4 opacity-70" viewBox="0 0 24 24" fill="none"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                                                </summary>
                                                <ul class="mt-3 space-y-2 text-sm">
                                                    @foreach ($labels as $i => $lbl)
                                                        @php
                                                            $v = $values[$i] ?? 0;
                                                            $pct = $total ? ($v/$total*100) : 0;
                                                        @endphp
                                                        <li class="flex items-center justify-between bg-gray-50 rounded-md px-3 py-2">
                                                            <span class="flex-1 truncate">{{ $lbl }}</span>
                                                            <span class="w-16 text-right font-semibold">{{ $v }}</span>
                                                            <span class="w-16 text-right text-gray-500">({{ number_format($pct,1) }}%)</span>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </details>
                                        </div>
                                    </div>
                                @else
                                    <p class="text-gray-500 text-center py-4">Belum ada jawaban untuk pertanyaan ini.</p>
                                @endif
                            </div>
                        @endif
                    @endforeach
                </div>
            </x-filament::card>
        @endif


        @if (empty($results['demographic']) && empty($results['likert']))
            <x-filament::card>
                <p class="text-center text-gray-500">Belum ada responden yang mengisi survei ini.</p>
            </x-filament::card>
        @endif
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>
        <script>
            Chart.register(ChartDataLabels);
            window.myCharts = {};

            document.addEventListener('DOMContentLoaded', () => {
                document.querySelectorAll('canvas[id][data-labels][data-values]').forEach((cv) => {
                    const id = cv.id;
                    const labels = JSON.parse(cv.getAttribute('data-labels') || '[]');
                    const values = JSON.parse(cv.getAttribute('data-values') || '[]');
                    const seed = parseInt(cv.getAttribute('data-seed') || '0', 10);
                    const manner = (cv.getAttribute('data-manner') || 'positive').toLowerCase();
                    const defaultType = id.startsWith('likert-') ? 'bar' : 'pie';

                    createChart(id, defaultType, labels, values, seed, manner);
                    setActiveButton(id, defaultType);
                });

                document.querySelectorAll('.chart-btn').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const canvasId = btn.getAttribute('data-toggle');
                        const newType  = btn.getAttribute('data-type');
                        changeChartType(canvasId, newType);
                        setActiveButton(canvasId, newType);
                    });
                });
            });

            function setActiveButton(canvasId, type) {
                document.querySelectorAll(`.chart-btn[data-toggle="${canvasId}"]`).forEach(b => {
                    const active = b.getAttribute('data-type') === type;
                    b.classList.toggle('bg-white', active);
                    b.classList.toggle('ring-2', active);
                    b.classList.toggle('ring-primary-500', active);
                    b.classList.toggle('text-primary-700', active);
                });
            }

            function palette(n, seed = 0) {
                const arr = [];
                for (let i = 0; i < n; i++) {
                    const hue = Math.round(((360 / Math.max(3, n)) * (i + seed)) % 360);
                    arr.push(`hsl(${hue} 70% 55% / 0.85)`);
                }
                return arr;
            }

            function primaryFromSeed(seed = 0, manner = 'positive') {
                // manner: positive ≈ emerald/teal; negative ≈ rose/red; neutral ≈ indigo
                const baseHue = manner === 'negative' ? 355 : manner === 'neutral' ? 230 : 160;
                const hue = (baseHue + (seed * 11)) % 360;
                return {
                    area:  `hsl(${hue} 80% 55% / 0.18)`,
                    fill:  `hsl(${hue} 80% 55% / 0.65)`,
                    stroke:`hsl(${hue} 85% 45% / 1)`,
                    hover: `hsl(${hue} 85% 50% / 0.95)`
                };
            }

            function createChart(canvasId, type, labels, values, seed = 0, manner = 'positive') {
                const el = document.getElementById(canvasId);
                if (!el) return;

                const ctx = el.getContext('2d');
                const colors = palette(labels.length, seed);
                const prim = primaryFromSeed(seed, manner);

                const common = {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        datalabels: {
                            formatter: (value, context) => {
                                const total = context.chart.data.datasets[0].data.reduce((s, v) => s + v, 0);
                                const pct = total ? (value / total * 100) : 0;
                                if ((type === 'pie' || type === 'doughnut') && pct < 5) return null;
                                const label = context.chart.data.labels[context.dataIndex];
                                return `${label}\n${value} (${pct.toFixed(1)}%)`;
                            },
                            color: '#fff',
                            font: { weight: 'bold', size: 11 },
                            align: 'center',
                            anchor: 'center',
                            textAlign: 'center',
                            clamp: true
                        },
                        tooltip: {
                            callbacks: {
                                label: (ctx) => {
                                    const total = ctx.dataset.data.reduce((s, v) => s + v, 0);
                                    const pct = total ? (ctx.parsed / total * 100) : 0;
                                    return ` ${ctx.label}: ${ctx.formattedValue} (${pct.toFixed(1)}%)`;
                                }
                            }
                        }
                    },
                    scales: (type === 'bar' || type === 'line') ? {
                        x: { grid: { color: 'rgba(0,0,0,0.06)' }, ticks: { color: '#4b5563' }, beginAtZero: true },
                        y: { grid: { color: 'rgba(0,0,0,0.06)' }, ticks: { color: '#4b5563' }, beginAtZero: true }
                    } : {}
                };

                const datasetBase = {
                    label: 'Jumlah Jawaban',
                    data: values,
                    borderWidth: 2,
                };

                if (type === 'bar') {
                    datasetBase.backgroundColor = prim.fill;
                    datasetBase.borderColor = prim.stroke;
                    datasetBase.hoverBackgroundColor = prim.hover;
                    datasetBase.barThickness = 22;
                    datasetBase.borderRadius = 8;
                } else if (type === 'line') {
                    datasetBase.backgroundColor = 'transparent';
                    datasetBase.borderColor = prim.stroke;
                    datasetBase.pointBackgroundColor = prim.stroke;
                    datasetBase.pointBorderColor = '#fff';
                    datasetBase.pointHoverRadius = 4;
                    datasetBase.pointRadius = 3;
                    datasetBase.tension = 0.35;
                    datasetBase.fill = { target: 'origin', above: prim.area, below: prim.area };
                } else {
                    // Pie/Doughnut
                    datasetBase.backgroundColor = colors;
                    datasetBase.borderColor = colors.map(c => c.replace('/ 0.85', '/ 1'));
                    datasetBase.borderWidth = 1;
                }

                const chart = new Chart(ctx, { type, data: { labels, datasets: [datasetBase] }, options: common });
                window.myCharts[canvasId] = chart;
                renderCustomLegend(canvasId, chart);
            }

            function changeChartType(canvasId, newType) {
                const chart = window.myCharts[canvasId];
                if (!chart) return;
                const labels = chart.data.labels.slice();
                const values = chart.data.datasets[0].data.slice();
                const el = document.getElementById(canvasId);
                const seed = parseInt(el?.getAttribute('data-seed') || '0', 10);
                const manner = (el?.getAttribute('data-manner') || 'positive').toLowerCase();
                chart.destroy();
                createChart(canvasId, newType, labels, values, seed, manner);
            }

            function renderCustomLegend(canvasId, chartInstance) {
                const container = document.getElementById('legend-container-' + canvasId);
                if (!container) return;

                const labels = chartInstance.data.labels;
                const values = chartInstance.data.datasets[0].data;
                const bg = chartInstance.data.datasets[0].backgroundColor;
                const total = values.reduce((s, v) => s + v, 0);

                let html = '<ul class="list-none p-0 space-y-2">';
                labels.forEach((label, i) => {
                    const value = values[i] ?? 0;
                    const pct = total ? (value / total * 100) : 0;
                    const type = chartInstance.config.type;
                    let color = Array.isArray(bg)
                        ? bg[i % bg.length]
                        : (type === 'line' ? chartInstance.data.datasets[0].borderColor : chartInstance.data.datasets[0].backgroundColor);

                    html += `
                        <li class="flex items-center justify-between bg-gray-50 rounded-md px-3 py-2 text-sm gap-2">
                            <span class="flex items-center gap-2 min-w-0 flex-1">
                                <span class="w-3.5 h-3.5 rounded flex-shrink-0" style="background:${color}"></span>
                                <span class="truncate">${label}</span>
                            </span>
                            <span class="w-16 text-right font-semibold">${value}</span>
                            <span class="w-16 text-right text-gray-500">(${pct.toFixed(1)}%)</span>
                        </li>
                    `;
                });
                html += '</ul>';

                container.innerHTML = html;
            }
        </script>
    @endpush
    @push('scripts')
        <script>
            window.answersPager = function ({ items = [], perPage = 10 } = {}) {
                const toArray = (x) => Array.isArray(x) ? x : (x ? Object.values(x) : []);
                return {
                    raw: toArray(items),
                    q: '',
                    page: 1,
                    perPage: Number(perPage) || 10,

                    get filtered() {
                        if (!this.q) return this.raw;
                        const q = this.q.toLowerCase();
                        return this.raw.filter(v => String(v).toLowerCase().includes(q));
                    },

                    get total() { return this.filtered.length },
                    get pages() { return Math.max(1, Math.ceil(this.total / this.perPage)) },
                    get from()  { return this.total ? ((this.page - 1) * this.perPage) + 1 : 0 },
                    get to()    { return Math.min(this.total, this.page * this.perPage) },

                    get current() {
                        const start = (this.page - 1) * this.perPage;
                        return this.filtered.slice(start, start + this.perPage);
                    },

                    next() { if (this.page < this.pages) this.page++ },
                    prev() { if (this.page > 1) this.page-- },
                    goto(p) { if (p >= 1 && p <= this.pages) this.page = p },

                    init() {
                        this.$watch('perPage', () => { this.page = 1 });
                        this.$watch('q', () => { this.page = 1 });
                    }
                }
            };
            </script>

        @endpush

</x-filament-panels::page>
