<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\SurveyResource\Pages;
use App\Models\Survey;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;


class SurveyResource extends Resource
{
    protected static ?string $model = Survey::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Judul Survei')->searchable(),
                Tables\Columns\TextColumn::make('user.name')->label('Dibuat oleh')->searchable(),
                Tables\Columns\TextColumn::make('unique_code')->label('Kode Unik'),
                Tables\Columns\TextColumn::make('responses_count')->counts('responses')->label('Jumlah Responden'),
                Tables\Columns\IconColumn::make('is_public')
                    ->label('Public')
                    ->boolean()
                    ->tooltip(fn (Survey $record): string => $record->is_public ? 'Klik untuk Unpublish' : 'Klik untuk Publish')
                    ->action(function (Survey $record) {
                        // This action toggles the boolean state
                        $record->is_public = !$record->is_public;
                        $record->save();

                        // Send a success notification
                        Notification::make()
                            ->title('Status Publikasi Diperbarui')
                            ->body($record->is_public ? 'Survei sekarang ditampilkan di dasbor publik.' : 'Survei sekarang disembunyikan dari dasbor publik.')
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                // Tambahkan tombol aksi untuk melihat hasil
                Tables\Actions\Action::make('Lihat Hasil')
                    ->icon('heroicon-o-chart-bar')
                    ->url(fn (Survey $record): string => static::getUrl('view-survey-results', ['record' => $record])),
                    
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSurveys::route('/'),
            // Daftarkan halaman baru di sini
            'view-survey-results' => Pages\ViewSurveyResults::route('/{record}/results'),
        ];
    }
}