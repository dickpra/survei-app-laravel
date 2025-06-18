<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\User;
use App\Models\Survey;
use App\Models\Response;

class PlatformStats extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Pengguna Terdaftar', User::count())
                ->description('Jumlah semua pengguna yang membuat survei')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),
            Stat::make('Total Survei Dibuat', Survey::count())
                ->description('Jumlah semua survei di platform')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('info'),
            Stat::make('Total Jawaban Terkumpul', Response::count())
                ->description('Jumlah semua responden yang telah mengisi')
                ->descriptionIcon('heroicon-m-pencil-square')
                ->color('warning'),
        ];
    }
}