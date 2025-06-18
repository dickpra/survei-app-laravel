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
                                                'isian pendek' => 'Isian Pendek',
                                                'pilihan ganda' => 'Pilihan Ganda (Radio)',
                                                'dropdown' => 'Dropdown',
                                                'skala likert' => 'Skala Likert (1-5)',
                                            ])->required()->live(),
                                        Forms\Components\TagsInput::make('options')
                                            ->label('Pilihan Jawaban')
                                            ->visible(fn ($get) => in_array($get('type'), ['pilihan ganda', 'dropdown'])),
                                    ])->addActionLabel('Tambah Pertanyaan'),
                            ])
                    ])->columnSpanFull()->addActionLabel('Tambah Blok Bagian Baru'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Judul Template')
                    ->searchable(),
                Tables\Columns\TextColumn::make('questions_count')
                    ->counts('questions')
                    ->label('Jumlah Pertanyaan'),
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