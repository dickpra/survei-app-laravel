<?php

namespace App\Filament\User\Widgets;

use App\Models\QuestionnaireTemplate;
use App\Models\Response;
use App\Models\Survey;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class UserStatsOverview extends BaseWidget
{
    /**
     * [FIX] Hanya tampilkan widget ini jika user memiliki salah satu peran.
     */
    public static function canSee(): bool
    {
        $user = Auth::user();
        return $user && ($user->is_researcher || $user->is_instrument_creator);
    }

    protected function getStats(): array
    {
        $user = Auth::user();
        $stats = [];

        // --- Statistik untuk peran INSTRUMENT CREATOR ---
        if ($user->is_instrument_creator) {
            $stats[] = Stat::make('Total Template Dibuat', QuestionnaireTemplate::where('user_id', $user->id)->count())
                ->description('Jumlah template yang Anda rancang')
                ->descriptionIcon('heroicon-m-document-duplicate')
                ->color('success');
        }

        // --- Statistik untuk peran RESEARCHER ---
        if ($user->is_researcher) {
            $userSurveyIds = $user->surveys()->pluck('id');
            $totalResponses = Response::whereIn('survey_id', $userSurveyIds)->count();
            $mostPopularSurvey = Survey::withCount('responses')
                ->where('user_id', $user->id)
                ->orderByDesc('responses_count')
                ->first();

            $stats[] = Stat::make('Total Survei Anda', $user->surveys()->count())
                ->description('Jumlah survei yang telah Anda buat')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('info');

            $stats[] = Stat::make('Total Responden Terkumpul', $totalResponses)
                ->description('Dari semua survei Anda')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary');

            $stats[] = Stat::make('Survei Terpopuler', $mostPopularSurvey->title ?? 'Belum Ada')
                ->description($mostPopularSurvey ? ($mostPopularSurvey->responses_count . ' Responden') : 'Belum ada responden')
                ->descriptionIcon('heroicon-m-star')
                ->color('warning');
        }
        
        return $stats;
    }
}