<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\QuestionnaireTemplateResource\Pages;
use App\Models\QuestionnaireTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Builder;

class QuestionnaireTemplateResource extends Resource
{
    protected static ?string $model = QuestionnaireTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->label('Judul Template')
                    ->required()->columnSpanFull(),
                Forms\Components\Textarea::make('description')
                    ->label('Deskripsi')->columnSpanFull(),
                
                Builder::make('content_blocks')
                    ->label('Blok Konten Survei')
                    // TAMBAHKAN HELPER TEXT DI SINI
                    ->helperText('Gunakan "Tambah Blok Bagian" untuk membuat grup pertanyaan. Anda bisa membuat beberapa blok, misalnya satu untuk data demografi dan satu lagi untuk pertanyaan inti.')
                    ->blocks([
                        Builder\Block::make('section_block')
                            ->label('Blok Bagian (Section)')
                            ->schema([
                                Forms\Components\TextInput::make('section_title')
                                    ->label('Judul Bagian')
                                    ->required(),
                                Forms\Components\Repeater::make('questions')
                                    ->label('Pertanyaan di dalam bagian ini')
                                    ->schema([
                                        // ... skema pertanyaan Anda tetap sama ...
                                        Forms\Components\Textarea::make('content')
                                            ->label('Isi Pertanyaan')->required(),
                                        Forms\Components\Select::make('type')
                                            ->label('Tipe Pertanyaan')
                                            ->options([
                                                'isian pendek' => 'Isian',
                                                'pilihan ganda' => 'Pilihan Ganda (Radio)',
                                                'dropdown' => 'Dropdown',
                                                'skala likert' => 'Skala Likert (1-5)',
                                            ])->required()->live(),
                                        // Forms\Components\TagsInput::make('options')
                                        //     ->label('Pilihan Jawaban')
                                        //     ->visible(fn ($get) => in_array($get('type'), ['pilihan ganda', 'dropdown'])),
                                        Forms\Components\TagsInput::make('options')
                                            ->label('Pilihan Jawaban / Label Skala Likert')
                                            // Ubah kondisi visible menjadi seperti ini
                                            ->visible(fn ($get) => in_array($get('type'), ['pilihan ganda', 'dropdown', 'skala likert']))
                                            ->helperText('Untuk Skala Likert, isi label urut dari nilai terendah ke tertinggi. Contoh: Sangat Buruk, Buruk, Cukup, Baik, Sangat Baik'),
                                    ])->addActionLabel('Tambah Pertanyaan'),
                            ])
                    ])->columnSpanFull()->addActionLabel('Tambah Blok Bagian Baru'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Judul Template')
                    ->searchable(),

                // --- PERBAIKAN ADA DI SINI ---
                Tables\Columns\TextColumn::make('questions_count')
                    ->label('Jumlah Pertanyaan')
                    ->getStateUsing(function (QuestionnaireTemplate $record): int {
                        // Cek jika content_blocks kosong atau bukan array
                        if (empty($record->content_blocks) || !is_array($record->content_blocks)) {
                            return 0;
                        }
                        
                        // Gunakan collection untuk menghitung total pertanyaan dari semua blok
                        return collect($record->content_blocks)
                            ->pluck('data.questions') // Ambil semua array 'questions'
                            ->flatten(1) // Gabungkan menjadi satu array besar
                            ->count();   // Hitung totalnya
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuestionnaireTemplates::route('/'),
            'create' => Pages\CreateQuestionnaireTemplate::route('/create'),
            'edit' => Pages\EditQuestionnaireTemplate::route('/{record}/edit'),
        ];
    }
}