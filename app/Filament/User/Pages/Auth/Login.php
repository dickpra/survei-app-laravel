<?php

namespace App\Filament\User\Pages\Auth;

use App\Models\User;
use Filament\Facades\Filament;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    /**
     * Menimpa method authenticate dengan logika custom kita.
     */
    public function authenticate(): ?LoginResponse
    {
        // Ambil data email dan password dari form
        $data = $this->form->getState();

        // Cari user di database berdasarkan email yang diinput
        $user = User::where('email', $data['email'])->first();

        // Lakukan pengecekan:
        // Apakah user-nya ada DAN apakah user tersebut tidak bisa mengakses panel (belum disetujui)?
        if ($user && !$user->canAccessPanel(Filament::getPanel('user'))) {
            // Jika ya, langsung lemparkan pesan error spesifik kita tanpa perlu cek password.
            throw ValidationException::withMessages([
                'data.email' => __('Akun Anda sedang menunggu persetujuan Admin.'),
            ]);
        }

        // Jika user tidak ada, atau jika dia sudah disetujui,
        // serahkan sisanya ke proses autentikasi standar Filament untuk mengecek password.
        return parent::authenticate();
    }
}