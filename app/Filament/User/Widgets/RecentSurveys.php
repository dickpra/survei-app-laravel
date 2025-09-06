<?php

namespace App\Filament\User\Widgets;

use App\Models\Survey;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use App\Filament\User\Resources\SurveyResource; // <-- TAMBAHKAN INI

class RecentSurveys extends BaseWidget
{
    protected static ?int $sort = 2; // Atur urutan widget, 2 berarti di bawah stats overview

    protected int | string | array $columnSpan = 'full'; // Agar widget ini memakai lebar penuh
    
    public static function canView(): bool
{
    $u = Auth::user();
    return $u && (bool) $u->is_researcher;
}


    public function table(Table $table): Table
    {
        return $table
            ->query(
                // Ambil 5 survei terbaru HANYA milik user ini
                Survey::query()->where('user_id', Auth::id())->latest()->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label(__('Judul Survei')),
                Tables\Columns\TextColumn::make('responses_count')
                    ->counts('responses')
                    ->label(__('Jumlah Responden')),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Dibuat Pada'))
                    ->since(),
            ])
            ->actions([
                // PERBAIKAN ADA DI BARIS '->url' DI BAWAH INI
                Tables\Actions\Action::make(__('Lihat Hasil'))
                    ->icon('heroicon-o-chart-bar')
                    ->url(fn (Survey $record): string => SurveyResource::getUrl('view-survey-results', ['record' => $record])),
            ]);
    }
}