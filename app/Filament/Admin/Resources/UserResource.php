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


class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Manajemen Akses';

    /**
     * PERUBAHAN 1: Membuat Form untuk Admin bisa mendaftarkan user baru
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(User::class, 'email', ignoreRecord: true),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    // Password hanya wajib diisi saat membuat user baru
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->helperText('Saat mengedit, biarkan kosong jika tidak ingin mengubah password.'),
            ]);
    }

    /**
     * PERUBAHAN 2: Menambahkan tombol 'View' dan filter status
     */
    public static function table(Table $table): Table
{
    return $table
        ->columns([
            Tables\Columns\TextColumn::make('name')->label('Nama')->searchable(),
            Tables\Columns\TextColumn::make('email')->searchable(),
            Tables\Columns\TextColumn::make('approved_at')
                ->label('Status')
                ->getStateUsing(fn (User $record): string => $record->approved_at ? 'Disetujui' : 'Menunggu')
                ->badge()
                ->color(fn (string $state): string => $state === 'Disetujui' ? 'success' : 'danger'),

            Tables\Columns\TextColumn::make('created_at')->label('Tanggal Registrasi')->dateTime('d M Y'),
        ])
        ->actions([
            Tables\Actions\ViewAction::make(),
            Tables\Actions\EditAction::make(),
            Tables\Actions\Action::make('approve')
                ->label('Approve')->icon('heroicon-o-check-circle')->color('success')->requiresConfirmation()
                ->action(fn (User $record) => $record->update(['approved_at' => now()]))
                ->visible(fn (User $record): bool => is_null($record->approved_at)), // Cek langsung
            
            Tables\Actions\Action::make('unapprove')
                ->label('Un-approve')->icon('heroicon-o-no-symbol')->color('danger')->requiresConfirmation()
                ->action(fn (User $record) => $record->update(['approved_at' => null]))
                ->visible(fn (User $record): bool => !is_null($record->approved_at)), // Cek langsung
        ]);
}

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\TextEntry::make('name')->label('Nama Lengkap'),
                Infolists\Components\TextEntry::make('email'),
                Infolists\Components\TextEntry::make('created_at')->label('Tanggal Bergabung')->dateTime('d F Y H:i'),
                // --- PERBAIKAN DI SINI ---
                Infolists\Components\TextEntry::make('is_approved') // Langsung panggil properti
                    ->label('Status Akun')
                    ->getStateUsing(fn (User $record): string => $record->approved_at ? 'Disetujui' : 'Menunggu')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Disetujui' ? 'success' : 'danger'),
                Infolists\Components\TextEntry::make('approved_at')
                    ->label('Tanggal Disetujui')->dateTime('d F Y H:i')->placeholder('Belum disetujui.'),
            ]);
    }

    public static function getPages(): array
    {
        // Aktifkan halaman 'create' dan 'view'
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}'),
        ];
    }
}