<?php

namespace App\Filament\User\Resources;


use App\Filament\User\Resources\SurveyResource\Pages;
use App\Models\QuestionnaireTemplate;
use App\Models\Survey;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
// use Filament\Resources\Pages\Action;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;


class SurveyResource extends Resource
{
    
    protected static ?string $model = Survey::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    /**
     * Memfilter data agar user hanya melihat survei miliknya.
     *
     * @return Builder
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }
    /**
     * Mendefinisikan form untuk membuat survei baru.
     *
     * @param Form $form
     * @return Form
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('questionnaire_template_id')
                    ->label('Pilih Template Kuesioner')
                    ->options(QuestionnaireTemplate::all()->pluck('title', 'id'))
                    ->required()
                    ->searchable(),
                Forms\Components\TextInput::make('title')
                    ->label('Judul Survei Anda')
                    ->helperText('Beri nama yang spesifik untuk survei Anda.')
                    ->required()
                    ->maxLength(255),
                
                // TAMBAHKAN TOGGLE DI SINI
                Forms\Components\Toggle::make('enforce_single_submission')
                    ->label('Hanya Boleh Diisi Sekali per Responden')
                    ->helperText('Jika aktif, responden akan dibatasi via cookie & IP address.')
                    ->default(true),
            ]);
    }

    /**
     * Mendefinisikan tabel untuk menampilkan daftar survei.
     *
     * @param Table $table
     * @return Table
     */
    public static function table(Table $table): Table
    {
        return $table
        ->query(
                // PASTIKAN ADA ->where('user_id', Auth::id()) DI SINI
                Survey::query()
                    ->where('user_id', Auth::id())
                    ->latest()
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Judul Survei')
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('unique_code')
                    ->label('Kode Unik (Bagikan ini)')
                    ->copyable()
                    // ->copyableState(fn (string $state): string => route('survey.show', ['unique_code' => $state]))
                    ->copyableState(function (Survey $record): string {
                        $shortDomain = config('app.short_domain');

                        // Jika domain pendek sudah diatur di .env, gunakan itu
                        // if ($shortDomain) {
                        //     // Gunakan https:// secara default untuk keamanan
                        //     return "https://{$shortDomain}/{$record->unique_code}";
                        // }

                        // Jika tidak, gunakan route standar yang lama sebagai fallback
                        return route('survey.show', ['unique_code' => $record->unique_code]);
                    })
                    ->copyMessage('Link survei berhasil disalin!')
                    ->badge(),
                Tables\Columns\TextColumn::make('questionnaireTemplate.title')
                    ->label('Template')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('responses_count')
                    ->counts('responses')
                    ->label('Jumlah Responden'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime('d M Y')
                    ->sortable(),
                    
            ])
            ->filters([
                //
            ])
            // ->actions([
            //     Tables\Actions\EditAction::make(),
            //     Tables\Actions\DeleteAction::make(),
            // ])
             ->actions([
                // PERBAIKAN ADA DI BARIS INI:
                Tables\Actions\Action::make('Lihat Hasil')
                    ->icon('heroicon-o-chart-bar')
                    ->url(fn (Survey $record): string => static::getUrl('view-survey-results', ['record' => $record])),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(), // Anda bisa menambahkan ini jika perlu
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Memodifikasi data sebelum disimpan ke database saat membuat record baru.
     *
     * @param array $data
     * @return array
     */
    protected static function mutateFormDataBeforeCreate(array $data): array
    {
        // Menambahkan ID user yang sedang login
        $data['user_id'] = auth()->id(); 
        
        // Menambahkan kode unik untuk survei
        $data['unique_code'] = 'SURV-' . strtoupper(Str::random(8)); 
        dd($data, auth()->user());
        return $data;
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSurveys::route('/'),
            'create' => Pages\CreateSurvey::route('/create'),
            'edit' => Pages\EditSurvey::route('/{record}/edit'),
            'view-survey-results' => Pages\ViewSurveyResults::route('/{record}/view-survey-results'),
        ];
    }
}