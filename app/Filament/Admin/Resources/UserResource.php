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
    protected static ?string $navigationGroup = 'Manajemen Akses';

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
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->helperText('Saat mengedit, biarkan kosong jika tidak ingin mengubah password.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nama')->searchable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\TextColumn::make('approved_at')
                    ->label('Status Akun')
                    ->getStateUsing(fn (User $record): string => $record->approved_at ? 'Disetujui' : 'Menunggu')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Disetujui' ? 'success' : 'warning'),
                Tables\Columns\TextColumn::make('created_at')->label('Tanggal Registrasi')->dateTime('d M Y'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Action::make('approve')
                    ->label('Approve Account')->icon('heroicon-o-check-circle')->color('success')->requiresConfirmation()
                    ->action(fn (User $record) => $record->update(['approved_at' => now()]))
                    ->visible(fn (User $record): bool => is_null($record->approved_at)),
                Action::make('unapprove')
                    ->label('Un-approve Account')->icon('heroicon-o-no-symbol')->color('danger')->requiresConfirmation()
                    ->action(fn (User $record) => $record->update(['approved_at' => null]))
                    ->visible(fn (User $record): bool => !is_null($record->approved_at)),
                Action::make('approveCreator')
                    ->label('Approve as Creator')->icon('heroicon-o-check-circle')->color('success')->requiresConfirmation()
                    ->action(function (User $record) {
                        $record->is_instrument_creator = true;
                        $record->requested_creator_at = null;
                        $record->save();
                        Notification::make()->title('Pengajuan Peran Disetujui!')->body('Selamat, Anda sekarang memiliki akses sebagai Instrument Creator.')->success()->sendToDatabase($record);
                    })
                    ->visible(fn (User $record) => !$record->is_instrument_creator && !is_null($record->requested_creator_at)),
                Action::make('approveResearcher')
                    ->label('Approve as Researcher')->icon('heroicon-o-check-circle')->color('success')->requiresConfirmation()
                    ->action(function (User $record) {
                        $record->is_researcher = true;
                        $record->requested_researcher_at = null;
                        $record->save();
                        Notification::make()->title('Pengajuan Peran Disetujui!')->body('Selamat, Anda sekarang memiliki akses sebagai Researcher.')->success()->sendToDatabase($record);
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
            Section::make('Informasi Akun')
                ->columns(2)
                ->schema([
                    TextEntry::make('name')->label('Nama Lengkap'),
                    TextEntry::make('email'),
                    TextEntry::make('created_at')->label('Tanggal Bergabung')->dateTime('d F Y H:i'),
                    TextEntry::make('approved_at')->label('Tanggal Disetujui')->dateTime('d F Y H:i')->placeholder('Belum disetujui.'),
                    TextEntry::make('approved_at')
                        ->label('Status Akun')
                        ->badge() // <-- Ini sudah benar
                        ->getStateUsing(fn (User $record): string => $record->approved_at ? 'Disetujui' : 'Menunggu')
                        ->color(fn (string $state): string => $state === 'Disetujui' ? 'success' : 'warning'),
                ]),

            Section::make('Hak Akses & Peran')
                ->columns(2)
                ->schema([
                    // --- [FIX] Menggunakan TextEntry yang di-style sebagai badge ---
                    TextEntry::make('is_researcher')
                        ->label('Peran Researcher')
                        ->getStateUsing(fn (User $record): string => 'Aktif')
                        ->badge() // <-- Ubah menjadi badge
                        ->color('primary')
                        ->visible(fn (User $record): bool => $record->is_researcher),
                    
                    // --- [FIX] Menggunakan TextEntry yang di-style sebagai badge ---
                    TextEntry::make('is_instrument_creator')
                        ->label('Peran Instrument Creator')
                        ->getStateUsing(fn (User $record): string => 'Aktif')
                        ->badge() // <-- Ubah menjadi badge
                        ->color('success')
                        ->visible(fn (User $record): bool => $record->is_instrument_creator),
                ]),

                Section::make('Pengajuan Peran Tertunda')
                    ->columns(2)
                    ->schema([
                        // Info jika ada pengajuan peran Researcher
                        Infolists\Components\TextEntry::make('requested_researcher_at')
                            ->label('Pengajuan Researcher')
                            ->dateTime('d F Y H:i')
                            ->visible(fn (User $record): bool => !is_null($record->requested_researcher_at)),
                        
                        // Info jika ada pengajuan peran Creator
                        Infolists\Components\TextEntry::make('requested_creator_at')
                            ->label('Pengajuan Creator')
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