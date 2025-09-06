<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Infolists\Components\Section; // Import Section for Infolist
use Filament\Infolists\Components\TextEntry;


class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    // protected static ?string $navigationGroup = 'Manajemen Akses';

    public static function getNavigationGroup(): ?string
    {
        return __('Manajemen Akses');
    }

    // Judul Halaman (title)
    public static function getPluralModelLabel(): string
    {
        return __('Pengguna');
    }

    // Label Navigasi (sidebar)
    public static function getNavigationLabel(): string
    {
        return __('Pengguna');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('Nama Lengkap'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->label(__('Email'))
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(User::class, 'email', ignoreRecord: true),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->helperText(__('Saat mengedit, biarkan kosong jika tidak ingin mengubah password.')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label(__('Nama'))->searchable(),
                Tables\Columns\TextColumn::make('email')->label(__('Email'))->searchable(),
                Tables\Columns\TextColumn::make('approved_at')
                    ->label(__('Status Akun'))
                    ->getStateUsing(fn (User $record): string => $record->approved_at ? __('Disetujui') : __('Menunggu'))
                    ->badge()
                    ->color(fn (string $state): string => $state === __('Disetujui') ? 'success' : 'warning'),
                Tables\Columns\TextColumn::make('created_at')->label(__('Tanggal Registrasi'))->dateTime('d M Y'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Action::make('approve')
                    ->label(__('Approve Account'))->icon('heroicon-o-check-circle')->color('success')->requiresConfirmation()
                    ->action(fn (User $record) => $record->update(['approved_at' => now()]))
                    ->visible(fn (User $record): bool => is_null($record->approved_at)),
                Action::make('unapprove')
                    ->label(__('Un-approve Account'))->icon('heroicon-o-no-symbol')->color('danger')->requiresConfirmation()
                    ->action(fn (User $record) => $record->update(['approved_at' => null]))
                    ->visible(fn (User $record): bool => !is_null($record->approved_at)),
                Action::make('approveCreator')
                    ->label(__('Approve as Creator'))->icon('heroicon-o-check-circle')->color('success')->requiresConfirmation()
                    ->action(function (User $record) {
                        $record->is_instrument_creator = true;
                        $record->requested_creator_at = null;
                        $record->save();
                        Notification::make()->title(__('Pengajuan Peran Disetujui!'))->body(__('Selamat, Anda sekarang memiliki akses sebagai Instrument Creator.'))->success()->sendToDatabase($record);
                    })
                    ->visible(fn (User $record) => !$record->is_instrument_creator && !is_null($record->requested_creator_at)),
                Action::make('approveResearcher')
                    ->label(__('Approve as Researcher'))->icon('heroicon-o-check-circle')->color('success')->requiresConfirmation()
                    ->action(function (User $record) {
                        $record->is_researcher = true;
                        $record->requested_researcher_at = null;
                        $record->save();
                        Notification::make()->title(__('Pengajuan Peran Disetujui!'))->body(__('Selamat, Anda sekarang memiliki akses sebagai Researcher.'))->success()->sendToDatabase($record);
                    })
                    ->visible(fn (User $record) => !$record->is_researcher && !is_null($record->requested_researcher_at)),
            ]);
    }

    /**
     * [UPDATED] Menambahkan informasi Role ke Infolist
     */
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
            Section::make(__('Informasi Akun'))
                ->columns(2)
                ->schema([
                    TextEntry::make('name')->label(__('Nama Lengkap')),
                    TextEntry::make('email')->label(__('Email')),
                    TextEntry::make('created_at')->label(__('Tanggal Bergabung'))->dateTime('d F Y H:i'),
                    TextEntry::make('approved_at')->label(__('Tanggal Disetujui'))->dateTime('d F Y H:i')->placeholder(__('Belum disetujui.')),
                    TextEntry::make('approved_at')
                        ->label(__('Status Akun'))
                        ->badge() // <-- Ini sudah benar
                        ->getStateUsing(fn (User $record): string => $record->approved_at ? __('Disetujui') : __('Menunggu'))
                        ->color(fn (string $state): string => $state === 'Disetujui' ? 'success' : 'warning'),
                ]),

            Section::make(__('Hak Akses & Peran'))
                ->columns(2)
                ->schema([
                    // --- [FIX] Menggunakan TextEntry yang di-style sebagai badge ---
                    TextEntry::make('is_researcher')
                        ->label(__('Peran Researcher'))
                        ->getStateUsing(fn (User $record): string => 'Aktif')
                        ->badge() // <-- Ubah menjadi badge
                        ->color('primary')
                        ->visible(fn (User $record): bool => $record->is_researcher),
                    
                    // --- [FIX] Menggunakan TextEntry yang di-style sebagai badge ---
                    TextEntry::make('is_instrument_creator')
                        ->label(__('Peran Instrument Creator'))
                        ->getStateUsing(fn (User $record): string => 'Aktif')
                        ->badge() // <-- Ubah menjadi badge
                        ->color('success')
                        ->visible(fn (User $record): bool => $record->is_instrument_creator),
                ]),

                Section::make(__('Pengajuan Peran Tertunda'))
                    ->columns(2)
                    ->schema([
                        // Info jika ada pengajuan peran Researcher
                        Infolists\Components\TextEntry::make('requested_researcher_at')
                            ->label(__('Pengajuan Researcher'))
                            ->dateTime('d F Y H:i')
                            ->visible(fn (User $record): bool => !is_null($record->requested_researcher_at)),
                        
                        // Info jika ada pengajuan peran Creator
                        Infolists\Components\TextEntry::make('requested_creator_at')
                            ->label(__('Pengajuan Creator'))
                            ->dateTime('d F Y H:i')
                            ->visible(fn (User $record): bool => !is_null($record->requested_creator_at)),
                    ])
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'), // Make sure view page is registered
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}