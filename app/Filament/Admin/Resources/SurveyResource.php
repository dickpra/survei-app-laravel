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

    public static function getNavigationGroup(): ?string
    {
        return __('Survey Management');
    }

    // Judul Halaman (title)
    public static function getPluralModelLabel(): string
    {
        return __('Surveys');
    }

    // Label Navigasi (sidebar)
    public static function getNavigationLabel(): string
    {
        return __('Surveys');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->label(__('Judul Survei'))->searchable(),
                Tables\Columns\TextColumn::make('user.name')->label(__('Dibuat oleh'))->searchable(),
                Tables\Columns\TextColumn::make('unique_code')->label(__('Kode Unik')),
                Tables\Columns\TextColumn::make('responses_count')->counts('responses')->label(__('Jumlah Responden')),
                Tables\Columns\IconColumn::make('is_public')
                    ->label(__('Public'))
                    ->boolean()
                    ->tooltip(fn (Survey $record): string => $record->is_public ? __('Klik untuk Unpublish') : __('Klik untuk Publish'))
                    ->action(function (Survey $record) {
                        // This action toggles the boolean state
                        $record->is_public = !$record->is_public;
                        $record->save();

                        // Send a success notification
                        Notification::make()
                            ->title(__('Status Publikasi Diperbarui'))
                            ->body($record->is_public ? __('Survei sekarang ditampilkan di dasbor publik.') : __('Survei sekarang disembunyikan dari dasbor publik.'))
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                // Tambahkan tombol aksi untuk melihat hasil
                Tables\Actions\Action::make(__('Lihat Hasil'))
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