<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Survey;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class SurveysByMonthChart extends ChartWidget
{
    protected static string $color = 'info';

    public function getHeading(): string
    {
        return __('Survei Dibuat per Bulan');
    }

    protected function getData(): array
    {
        // Mengambil data tren survei per bulan selama 12 bulan terakhir
        $data = Trend::model(Survey::class)
            ->between(
                start: now()->subYear(),
                end: now(),
            )
            ->perMonth()
            ->count();

        return [
            'datasets' => [
                [
                    'label' => __('Survei Dibuat'),
                    'data' => $data->map(fn (TrendValue $value) => $value->aggregate),
                ],
            ],
            'labels' => $data->map(fn (TrendValue $value) => $value->date),
        ];
    }

    protected function getType(): string
    {
        return 'line'; // Grafik garis
    }
}
