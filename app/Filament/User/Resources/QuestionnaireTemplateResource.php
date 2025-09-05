<?php

namespace App\Filament\User\Resources;

use App\Filament\User\Resources\QuestionnaireTemplateResource\Pages;
use App\Models\QuestionnaireTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;


class QuestionnaireTemplateResource extends Resource
{
    protected static ?string $model = QuestionnaireTemplate::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';
    protected static ?string $navigationGroup = 'Instrument Creator';

    public static function canViewAny(): bool
    {
        // Tampilkan jika admin ATAU jika user adalah instrument creator
        return Auth::guard('admin')->check() || 
            (auth()->user() && auth()->user()->is_instrument_creator);
    }

    /**
     * [FIX] Menambahkan method untuk mengontrol visibilitas tombol "Create".
     */
    public static function canCreate(): bool
    {
        // Izinkan pembuatan jika login sebagai admin ATAU jika user adalah instrument creator.
        return Auth::guard('admin')->check() || (Auth::guard('user')->user() && Auth::guard('user')->user()->is_instrument_creator);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Schema form Anda sudah benar, tidak perlu diubah.
                Forms\Components\Section::make('Informasi Dasar')
                    ->schema([
                        Forms\Components\TextInput::make('title')->label('Judul Template')->required(),
                        Forms\Components\Textarea::make('description')->label('Deskripsi'),
                    ])->collapsible(),

                Forms\Components\Section::make('Pertanyaan Demografi')
                     ->schema([
                        Forms\Components\TextInput::make('demographic_title')->label('Judul Bagian Demografi')->required()->default('Demographic Data'),
                        Forms\Components\Repeater::make('demographic_questions')
                            ->label('Daftar Pertanyaan Demografi')
                            // Label item dinamis berbasis UID
                            ->itemLabel(function (array $state, Forms\Get $get): ?string {
                                $uids  = collect($get('demographic_questions') ?? [])->pluck('_uid');
                                $index = $uids->search($state['_uid'] ?? null, true);
                                return 'Pertanyaan Demografi #' . (($index === false ? 0 : $index) + 1);
                            })
                            ->reorderable()
                            ->schema([
                                // UID tersembunyi agar bisa dihitung indeksnya
                                Forms\Components\Hidden::make('_uid')
                                    ->dehydrated(false) // tidak disimpan ke DB
                                    ->default(fn () => (string) Str::uuid())
                                    ->afterStateHydrated(function ($component, $state, callable $set) {
                                        if (blank($state)) {
                                            $set($component->getStatePath(), (string) Str::uuid());
                                        }
                                    }),

                                Forms\Components\Textarea::make('question_text')->label('Isi Pertanyaan')->required(),
                                Forms\Components\Select::make('type')->label('Tipe Pertanyaan')
                                    ->options(['isian' => 'Isian Singkat', 'dropdown' => 'Dropdown'])
                                    ->required()->live(),
                                Forms\Components\TagsInput::make('options')->label('Pilihan Jawaban (untuk Dropdown)')
                                    ->helperText('Tekan Enter setelah mengetik satu pilihan.')
                                    ->visible(fn ($get) => $get('type') === 'dropdown'),
                            ])
                            ->addActionLabel('Tambah Pertanyaan Demografi')
                            ->defaultItems(1),

                    ])->collapsible(),

                Forms\Components\Section::make('Item Statement (Skala Likert)')
                    ->schema([
                        Forms\Components\TextInput::make('likert_title')->label('Judul Bagian Skala Likert')->required()->default('Item Statements'),
                        Forms\Components\Repeater::make('likert_questions')
                            ->label('Daftar Pernyataan Skala Likert')
                            ->itemLabel(function (array $state, Forms\Get $get): ?string {
                                $uids  = collect($get('likert_questions') ?? [])->pluck('_uid');
                                $index = $uids->search($state['_uid'] ?? null, true);
                                return 'Pernyataan #' . (($index === false ? 0 : $index) + 1);
                            })
                            ->reorderable()
                            ->schema([
                                Forms\Components\Hidden::make('_uid')
                                    ->dehydrated(false)
                                    ->default(fn () => (string) Str::uuid())
                                    ->afterStateHydrated(function ($component, $state, callable $set) {
                                        if (blank($state)) {
                                            $set($component->getStatePath(), (string) Str::uuid());
                                        }
                                    }),

                                Forms\Components\Textarea::make('question_text')->label('Isi Pernyataan')->required(),
                                Forms\Components\Select::make('likert_scale')->label('Skala Likert')
                                    ->options([
                                        '3' => 'Skala 1-3', '4' => 'Skala 1-4', '5' => 'Skala 1-5',
                                        '6' => 'Skala 1-6', '7' => 'Skala 1-7',
                                    ])->required()->default('5'),
                                Forms\Components\Radio::make('manner')->label('Arah Pernyataan (Manner)')
                                    ->options(['positive' => 'Positif', 'negative' => 'Negatif'])
                                    ->required()->default('positive'),
                            ])
                            ->addActionLabel('Tambah Pernyataan')
                            ->defaultItems(1),
                    ])->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('Judul Template')->searchable()->sortable(),

                // --- [BAGIAN YANG DIPERBARUI] ---
                TextColumn::make('pembuat') // Beri nama custom agar tidak bentrok dengan relasi
                    ->label('Pembuat')
                    ->getStateUsing(function (QuestionnaireTemplate $record): string {
                        // Jika user_id ada, tampilkan nama user dari relasi.
                        // Jika tidak ada, tampilkan teks "Admin".
                        return $record->user?->name ?? 'Admin';
                    })
                    ->sortable()
                    ->searchable(query: function ($query, $search) {
                        // Membuat pencarian custom agar bisa mencari nama user atau "Admin"
                        return $query->whereHas('user', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        })->orWhere(function($q) use ($search) {
                            if (strtolower($search) === 'admin') {
                                $q->whereNull('user_id');
                            }
                        });
                    })
                    ->visible(fn () => Auth::guard('admin')->check()),
                // --- [AKHIR BAGIAN YANG DIPERBARUI] ---

                // --- [BAGIAN YANG DIPERBARUI] ---
                BadgeColumn::make('status') // Kita beri nama kolom 'status' agar lebih generik
                    ->label('Status')
                    ->getStateUsing(function (QuestionnaireTemplate $record): string {
                        // Logika untuk menampilkan label baru
                        return $record->published_at ? 'Approved' : 'Not Approved';
                    })
                    ->colors([
                        // Warna disesuaikan dengan label baru
                        'success' => 'Approved',
                        'warning' => 'Not Approved',
                    ]),
                // --- [AKHIR BAGIAN YANG DIPERBARUI] ---

                TextColumn::make('published_at')->label('Tgl. Publikasi')->dateTime('d M Y')->sortable(),
            ])
            ->actions([
                EditAction::make()
                    ->visible(function (QuestionnaireTemplate $record): bool {
                        return Auth::guard('admin')->check() || $record->user_id === Auth::guard('user')->id();
                    }),
                Tables\Actions\DeleteAction::make()
                     ->visible(function (QuestionnaireTemplate $record): bool {
                        return Auth::guard('admin')->check() || $record->user_id === Auth::guard('user')->id();
                    }),

                Action::make('publish')
                    ->label('Publish')->icon('heroicon-o-check-circle')->color('success')->requiresConfirmation()
                    ->action(fn (QuestionnaireTemplate $record) => $record->update(['published_at' => now()]))
                    ->visible(fn (QuestionnaireTemplate $record): bool =>
                        is_null($record->published_at) && Auth::guard('admin')->check()
                    ),

                Action::make('unpublish')
                    ->label('Unpublish')->icon('heroicon-o-x-circle')->color('danger')->requiresConfirmation()
                    ->action(fn (QuestionnaireTemplate $record) => $record->update(['published_at' => null]))
                    ->visible(fn (QuestionnaireTemplate $record): bool =>
                        !is_null($record->published_at) && Auth::guard('admin')->check()
                    ),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
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