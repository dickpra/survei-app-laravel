<x-filament-panels::page>
    <style>
        .scrollable-answers { max-height: 300px; overflow-y: auto; background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 1rem; }
        .chart-container { display: flex; flex-direction: column; md:flex-direction: row; align-items: center; gap: 1.5rem; }
        .chart-canvas-container { flex-shrink: 0; width: 100%; min-height: 320px; md:width: 50%; height: 20rem; }
        .custom-legend-container { width: 100%; md:width: 50%; }
        .legend-list { list-style: none; padding: 0; }
        .legend-item { display: flex; align-items: center; margin-bottom: 0.75rem; font-size: 0.875rem; }
        .legend-color-box { width: 16px; height: 16px; margin-right: 12px; border-radius: 4px; flex-shrink: 0; }
        .legend-label { flex-grow: 1; color: #374151; }
        .legend-value { font-weight: 600; color: #111827; margin-left: 8px; }
        .legend-percent { font-size: 0.8rem; color: #6b7280; margin-left: 4px; }
    </style>

    <div class="space-y-6">
        <x-filament::card>
            <div class="text-center">
                <h2 class="text-xl font-bold tracking-tight">Hasil untuk: {{ $this->record->title }}</h2>
                <p class="text-gray-500">Total Responden yang Mengisi:
                    <span class="font-bold text-primary-600 text-2xl">{{ $totalResponses }}</span>
                </p>
            </div>
        </x-filament::card>

        @foreach($results as $questionContent => $result)
            <x-filament::card class="space-y-4">
                
                @if($result['type'] === 'agregat' && !empty($result['answers']))
                    <div class="flex justify-between items-center mb-4 flex-wrap gap-2">
                        <h3 class="font-bold text-lg">{{ $loop->iteration }}. {{ $result['content'] }}</h3>
                        <div class="flex space-x-1 rounded-lg bg-gray-100 p-1">
                            <button onclick="changeChartType('chart-{{ $loop->index }}', 'bar')" class="px-3 py-1 text-sm font-medium text-gray-700 rounded-md hover:bg-white focus:outline-none focus:bg-white focus:ring-2 focus:ring-primary-500">Bar</button>
                            <button onclick="changeChartType('chart-{{ $loop->index }}', 'line')" class="px-3 py-1 text-sm font-medium text-gray-700 rounded-md hover:bg-white focus:outline-none focus:bg-white focus:ring-2 focus:ring-primary-500">Line</button>
                            <button onclick="changeChartType('chart-{{ $loop->index }}', 'doughnut')" class="px-3 py-1 text-sm font-medium text-gray-700 rounded-md hover:bg-white focus:outline-none focus:bg-white focus:ring-2 focus:ring-primary-500">Doughnut</button>
                            <button onclick="changeChartType('chart-{{ $loop->index }}', 'pie')" class="px-3 py-1 text-sm font-medium text-gray-700 rounded-md hover:bg-white focus:outline-none focus:bg-white focus:ring-2 focus:ring-primary-500">Pie</button>
                        </div>
                    </div>
                    
                    <div class="chart-container">
                        <div class="chart-canvas-container" wire:ignore>
                            <canvas id="chart-{{ $loop->index }}"></canvas>
                        </div>
                        <div class="custom-legend-container" id="legend-container-{{ $loop->index }}">
                            {{-- Legenda akan di-render oleh JavaScript di sini --}}
                        </div>
                    </div>

                @elseif($result['type'] === 'isian pendek' && !empty($result['answers']))
                    <h3 class="font-bold text-lg">{{ $loop->iteration }}. {{ $result['content'] }}</h3>
                    <div class="scrollable-answers">
                        <ul class="list-disc list-inside">
                            @foreach($result['answers'] as $answer)
                                <li class="border-b border-gray-200 py-1">{{ $answer }}</li>
                            @endforeach
                        </ul>
                    </div>
                @else
                    <h3 class="font-bold text-lg">{{ $loop->iteration }}. {{ $result['content'] }}</h3>
                    <p class="text-gray-500 text-center py-4">Belum ada jawaban untuk pertanyaan ini.</p>
                @endif
            </x-filament::card>
        @endforeach
    </div>

    @push('scripts')
        {{-- Pastikan Chart.js dimuat --}}
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                window.myCharts = {};
                const resultsData = @json($results);

                // Menggunakan Object.entries untuk mendapatkan index dan value
                Object.entries(resultsData).forEach(([content, result], index) => {
                    if (result.type === 'agregat' && Object.keys(result.answers).length > 0) {
                        createChart(`chart-${index}`, 'bar', result.answers);
                    }
                });
            });

            function createChart(canvasId, type, answersData) {
                const canvasElement = document.getElementById(canvasId);
                if (canvasElement) {
                    const ctx = canvasElement.getContext('2d');
                    window.myCharts[canvasId] = new Chart(ctx, {
                        type: type,
                        data: {
                            labels: Object.keys(answersData),
                            datasets: [{
                                label: 'Jumlah Jawaban',
                                data: Object.values(answersData),
                                backgroundColor: ['#36A2EB', '#FF6384', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', '#FFCD56'],
                            }]
                        },
                        options: {
                            responsive: true, maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: {
                                y: { display: (type === 'bar' || type === 'line'), beginAtZero: true },
                                x: { display: (type === 'bar' || type === 'line') }
                            }
                        }
                    });
                    renderCustomLegend(canvasId, window.myCharts[canvasId]);
                }
            }

            function changeChartType(canvasId, newType) {
                const chartInstance = window.myCharts[canvasId];
                if (chartInstance) {
                    const answersData = chartInstance.data.datasets[0].data.reduce((acc, data, index) => {
                        const label = chartInstance.data.labels[index];
                        acc[label] = data;
                        return acc;
                    }, {});
                    chartInstance.destroy();
                    createChart(canvasId, newType, answersData);
                }
            }
            
            function renderCustomLegend(canvasId, chartInstance) {
                const legendContainer = document.getElementById(`legend-container-${canvasId.split('-')[1]}`);
                if (!legendContainer) return;

                const { labels, datasets } = chartInstance.data;
                const total = datasets[0].data.reduce((sum, value) => sum + value, 0);

                let html = '<ul class="legend-list">';
                labels.forEach((label, index) => {
                    const value = datasets[0].data[index];
                    const color = datasets[0].backgroundColor[index % datasets[0].backgroundColor.length];
                    const isLikert = !isNaN(label);
                    const labelText = isLikert ? `Responden yang memberi nilai <strong>${label}</strong>` : `Responden yang memilih '<strong>${label}</strong>'`;

                    html += `
                        <li class="legend-item">
                            <span class="legend-color-box" style="background-color: ${color}"></span>
                            <span class="legend-label">${labelText}</span>
                            <span class="legend-value">${value}</span>
                            <span class="legend-percent">(${(total > 0 ? (value / total) * 100 : 0).toFixed(1)}%)</span>
                        </li>
                    `;
                });
                html += '</ul>';
                legendContainer.innerHTML = html;
            }
        </script>
    @endpush
</x-filament-panels::page>