<?php

namespace App\Filament\User\Widgets;

use App\Models\Survey;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class RecentSurveys extends BaseWidget
{
    protected static ?int $sort = 2; // Atur urutan widget, 2 berarti di bawah stats overview

    protected int | string | array $columnSpan = 'full'; // Agar widget ini memakai lebar penuh

    public function table(Table $table): Table
    {
        return $table
            ->query(
                // Ambil 5 survei terbaru HANYA milik user ini
                Survey::query()->where('user_id', Auth::id())->latest()->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Judul Survei'),
                Tables\Columns\TextColumn::make('responses_count')
                    ->counts('responses')
                    ->label('Jumlah Responden'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->since(),
            ])
            ->actions([
                // Tambahkan tombol untuk langsung ke halaman hasil
                Tables\Actions\Action::make('Lihat Hasil')
                    ->icon('heroicon-o-chart-bar')
                    ->url(fn (Survey $record): string => url("/user/surveys/{$record->id}/results")),
            ]);
    }
}