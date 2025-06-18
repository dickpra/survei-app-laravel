<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Dashboard as BasePage;
use App\Filament\Admin\Widgets\PlatformStats;
use App\Filament\Admin\Widgets\SurveysByMonthChart;

class Dashboard extends BasePage
{
    /**
     * @return array<class-string<Widget> | WidgetConfiguration>
     */
    public function getWidgets(): array
    {
        return [
            PlatformStats::class,
            SurveysByMonthChart::class,
        ];
    }
}