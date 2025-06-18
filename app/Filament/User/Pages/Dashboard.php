<?php

namespace App\Filament\User\Pages;

use Filament\Pages\Dashboard as BasePage;
use App\Filament\User\Widgets\UserStatsOverview;
use App\Filament\User\Widgets\RecentSurveys;

class Dashboard extends BasePage
{
    /**
     * @return array<class-string<Widget> | WidgetConfiguration>
     */
    public function getWidgets(): array
    {
        return [
            UserStatsOverview::class,
            RecentSurveys::class,
        ];
    }

    /**
     * Mengatur layout kolom untuk widget.
     */
    public function getColumns(): int | string | array
    {
        return 2;
    }
}