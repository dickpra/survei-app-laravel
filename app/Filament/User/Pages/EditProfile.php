<?php

namespace App\Filament\User\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password; // <-- [TAMBAHKAN] Import class Password

class EditProfile extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';
    protected static string $view = 'filament.user.pages.edit-profile';
    // protected static ?string $navigationLabel = 'Profil Saya';
    // protected static ?string $title = 'Profil Saya';
    // protected static ?string $navigationGroup = 'Profile';





    public static function getNavigationGroup(): ?string
        {
            return __('Profile');
        }

        // Judul Halaman (title)
        public static function getPluralModelLabel(): string
        {
            return __('Profil Saya');
        }

        // Label Navigasi (sidebar)
        public static function getNavigationLabel(): string
        {
            return __('Profil Saya');
        }


    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(auth()->user()->toArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('Informasi Profil'))
                    ->schema([
                        TextInput::make('name')->label(__('Nama'))->required(),
                        TextInput::make('email')->label(__('Alamat Email'))->email()->required()->unique(ignoreRecord: true),
                    ]),
                Section::make(__('Ubah Password'))
                    ->description(__('Kosongkan jika Anda tidak ingin mengubah password.')) // [FIX] Tambahkan deskripsi
                    ->schema([
                        TextInput::make('password')
                            ->label(__('Password Baru'))
                            ->password()
                            ->revealable()
                            // [FIX] Gunakan aturan validasi yang lebih fleksibel
                            ->rule(Password::default()->sometimes()) 
                            ->dehydrated(fn ($state) => filled($state)), // Hanya simpan jika diisi
                        TextInput::make('password_confirmation')
                            ->label(__('Konfirmasi Password Baru'))
                            ->password()
                            ->revealable()
                            ->same('password')
                            ->requiredWith('password'), // Hanya wajib jika password diisi
                    ]),
            ])
            ->statePath('data');
    }

    public function updateProfile(): void
    {
        $validatedData = $this->form->getState();
        $user = auth()->user();

        $user->update([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
        ]);

        // [FIX] Cek apakah password baru diisi sebelum meng-update
        if (!empty($validatedData['password'])) {
            $user->update([
                'password' => Hash::make($validatedData['password']),
            ]);
        }
        
        // [FIX] Cara yang benar untuk me-reset state array
        $this->data['password'] = null;
        $this->data['password_confirmation'] = null;

        Notification::make()->title(__('Profil berhasil diperbarui!'))->success()->send();
    }

    /**
     * Method untuk menampilkan tombol aksi di halaman.
     */
    protected function getHeaderActions(): array
    {
        $user = auth()->user();

        return [
            Action::make('requestCreatorRole')
                ->label(fn (): string => $user->requested_creator_at ? __('Pengajuan Creator Terkirim') : __('Ajukan Akses Instrument Creator'))
                ->color('primary')
                ->icon('heroicon-o-sparkles')
                // Panggil method yang spesifik untuk creator
                ->action('requestCreatorAccess')
                ->visible(fn (): bool => !$user->is_instrument_creator)
                ->disabled(fn (): bool => !is_null($user->requested_creator_at)),

            Action::make('requestResearcherRole')
                ->label(fn (): string => $user->requested_researcher_at ? __('Pengajuan Researcher Terkirim') : __('Ajukan Akses Researcher'))
                ->color('primary')
                ->icon('heroicon-o-beaker')
                // Panggil method yang spesifik untuk researcher
                ->action('requestResearcherAccess')
                ->visible(fn (): bool => !$user->is_researcher)
                ->disabled(fn (): bool => !is_null($user->requested_researcher_at)),
        ];
    }

    /**
     * [FIX UTAMA] Method yang dipanggil oleh tombol 'requestCreatorRole'.
     */
    public function requestCreatorAccess(): void
    {
        $this->handleRoleRequest('creator');
    }

    /**
     * [FIX UTAMA] Method yang dipanggil oleh tombol 'requestResearcherRole'.
     */
    public function requestResearcherAccess(): void
    {
        $this->handleRoleRequest('researcher');
    }

    /**
     * [FIX UTAMA] Ganti nama method menjadi private untuk logika internal.
     */
    private function handleRoleRequest(string $role): void
    {
        $user = auth()->user();
        $requestField = ($role === 'creator') ? 'requested_creator_at' : 'requested_researcher_at';
        $roleName = ($role === 'creator') ? 'Instrument Creator' : 'Researcher';

        // Update status pengajuan di database
        $user->update([$requestField => now()]);
        
        // Kirim notifikasi ke admin
        $admins = Admin::all(); 
        if ($admins->isNotEmpty()) {
            Notification::make()
                ->title(__('Permohonan Akses Baru'))
                ->body(__('Pengguna :name mengajukan permohonan untuk menjadi :role.', ['name' => $user->name, 'role' => $roleName]))
                ->sendToDatabase($admins);
        }

        // Beri notifikasi ke user
        Notification::make()
            ->title(__('Pengajuan Terkirim!'))
            ->body(__('Permohonan Anda untuk menjadi :role telah dikirim dan akan direview oleh Admin.', ['role' => $roleName]))
            ->success()
            ->send();
    }
}