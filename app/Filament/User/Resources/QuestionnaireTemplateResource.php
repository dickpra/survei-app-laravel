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
    // protected static ?string $navigationGroup = 'Instrument Creator';





public static function getNavigationGroup(): ?string
    {
        return __('Instrument Creator');
    }

    // Judul Halaman (title)
    public static function getPluralModelLabel(): string
    {
        return __('Template Kuesioner');
    }

    // Label Navigasi (sidebar)
    public static function getNavigationLabel(): string
    {
        return __('Template Kuesioner');
    }


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
                Forms\Components\Section::make(__('Informasi Dasar'))
                    ->schema([
                        Forms\Components\TextInput::make('title')->label(__('Judul Template'))->required(),
                        Forms\Components\Textarea::make('description')->label(__('Deskripsi')),
                    ])->collapsible(),

                Forms\Components\Section::make(__('Pertanyaan Demografi'))
                     ->schema([
                        Forms\Components\TextInput::make('demographic_title')->label(__('Judul Bagian Demografi'))->required()->default('Demographic Data'),
                        
                        Forms\Components\Repeater::make('demographic_questions')
                    ->label(__('Daftar Pertanyaan Demografi'))
                    ->itemLabel(function (array $state, Forms\Get $get): ?string {
                        $items = $get('demographic_questions') ?? [];
                        
                        // Gunakan values() untuk mereset urutan, lalu ambil _uid
                        $uids = collect($items)->values()->pluck('_uid');
                        
                        // Cari UID state saat ini
                        $index = $uids->search($state['_uid'] ?? null);
                        
                        return __('Pertanyaan Demografi #') . (($index === false ? 0 : $index) + 1);
                    })
                    ->reorderable()
                    ->schema([
                        // [PERBAIKAN UTAMA DI SINI]
                        Forms\Components\Hidden::make('_uid')
                            ->default(fn () => (string) Str::uuid())
                            // HAPUS atau KOMENTARI baris ini: ->dehydrated(false) 
                            // Agar UID tersimpan ke DB dan bisa dibaca saat reload
                            ->afterStateHydrated(function ($component, $state, callable $set) {
                                // Generate UID baru hanya jika benar-benar kosong
                                if (blank($state)) {
                                    $set($component->getStatePath(), (string) Str::uuid());
                                }
                            }),

                                Forms\Components\Textarea::make('question_text')
                                    ->label(__('Isi Pertanyaan'))
                                    ->required()
                                    ->live(onBlur: true), 

                                Forms\Components\Select::make('type')
                                    ->label(__('Tipe Pertanyaan'))
                                    ->options((['isian' => __('Isian Singkat'), 'dropdown' => __('Dropdown')]))
                                    ->required()
                                    ->live(),

                                Forms\Components\Placeholder::make('country_notice')
                                    ->label(__('Auto-Detect: Negara'))
                                    ->content(__('Sistem mendeteksi pertanyaan tentang "Negara". Daftar seluruh negara akan dimuat otomatis di halaman survei. Anda tidak perlu mengisi pilihan manual.'))
                                    ->visible(function (Forms\Get $get) {
                                        $text = strtolower($get('question_text') ?? '');
                                        $type = $get('type');
                                        $isCountry = Str::contains($text, ['negara', 'country', 'origin', 'nasional', 'nationality']);
                                        return $type === 'dropdown' && $isCountry;
                                    }),

                                Forms\Components\TagsInput::make('options')
                                    ->label(__('Pilihan Jawaban (untuk Dropdown)'))
                                    ->helperText(__('Tekan Enter setelah mengetik satu pilihan.'))
                                    ->visible(function (Forms\Get $get) {
                                        $text = strtolower($get('question_text') ?? '');
                                        $type = $get('type');
                                        $isCountry = Str::contains($text, ['negara', 'country', 'origin', 'nasional', 'nationality']);
                                        return $type === 'dropdown' && !$isCountry;
                                    }),
                            ])
                            ->addActionLabel(__('Tambah Pertanyaan Demografi'))
                            ->defaultItems(1),

                    ])->collapsible(),

                Forms\Components\Section::make(__('Item Statement (Skala Likert)'))
                    ->schema([
                        Forms\Components\TextInput::make('likert_title')->label(__('Judul Bagian Skala Likert'))->required()->default(__('Item Statements')),
                        
                        Forms\Components\Repeater::make('likert_questions')
                    ->label(__('Daftar Pernyataan Skala Likert'))
                    ->itemLabel(function (array $state, Forms\Get $get): ?string {
                        $items = $get('likert_questions') ?? [];
                        $uids  = collect($items)->values()->pluck('_uid');
                        $index = $uids->search($state['_uid'] ?? null);
                        return (__('Pertanyaan #')) . (($index === false ? 0 : $index) + 1);
                    })
                    ->reorderable()
                    ->schema([
                        // [PERBAIKAN UTAMA DI SINI]
                        Forms\Components\Hidden::make('_uid')
                            ->default(fn () => (string) Str::uuid())
                            // HAPUS atau KOMENTARI baris ini: ->dehydrated(false)
                            ->afterStateHydrated(function ($component, $state, callable $set) {
                                if (blank($state)) {
                                    $set($component->getStatePath(), (string) Str::uuid());
                                }
                            }),

                                Forms\Components\Textarea::make('question_text')->label(__('Isi Pertanyaan'))->required(),
                                Forms\Components\Select::make('likert_scale')->label(__('Skala Likert'))
                                    ->options([
                                        '3' => __('Skala 1-3'), 
                                        '4' => __('Skala 1-4'), 
                                        '5' => __('Skala 1-5'),
                                        '6' => __('Skala 1-6'), 
                                        '7' => __('Skala 1-7'),
                                    ])->required()->default('5'),
                                Forms\Components\Radio::make('manner')->label(__('Arah Pertanyaan (Manner)'))
                                    ->options([
                                        'positive' => __('Positif'), 
                                        'negative' => __('Negatif')
                                    ])
                                    ->required()->default('positive'),
                            ])
                            ->addActionLabel(__('Tambah Pertanyaan'))
                            ->defaultItems(1),
                    ])->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label(__('Judul Template'))->searchable()->sortable(),

                // --- [BAGIAN YANG DIPERBARUI] ---
                TextColumn::make('pembuat') // Beri nama custom agar tidak bentrok dengan relasi
                    ->label(__('Pembuat'))
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

                TextColumn::make('published_at')->label(__('Tgl. Publikasi'))->dateTime('d M Y')->sortable(),
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