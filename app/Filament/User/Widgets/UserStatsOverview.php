<?php

namespace App\Filament\User\Widgets;

use App\Models\Response;
use App\Models\Survey;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class UserStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        // Ambil ID semua survei milik user yang sedang login
        $userSurveyIds = Auth::user()->surveys()->pluck('id');

        // Hitung total responden dari semua survei tersebut
        $totalResponses = Response::whereIn('survey_id', $userSurveyIds)->count();

        // Survei dengan responden terbanyak
        $mostPopularSurvey = Survey::withCount('responses')
            ->where('user_id', Auth::id())
            ->orderByDesc('responses_count')
            ->first();

        return [
            Stat::make('Total Survei Anda', Auth::user()->surveys()->count())
                ->description('Jumlah survei yang telah Anda buat')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('success'),

            Stat::make('Total Responden Terkumpul', $totalResponses)
                ->description('Dari semua survei Anda')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),

            Stat::make('Survei Terpopuler', $mostPopularSurvey->title ?? 'Belum Ada')
                ->description($mostPopularSurvey ? ($mostPopularSurvey->responses_count . ' Responden') : 'Belum ada responden')
                ->descriptionIcon('heroicon-m-star')
                ->color('warning'),
        ];
    }
}