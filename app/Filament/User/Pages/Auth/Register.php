<?php
namespace App\Filament\User\Pages\Auth;

use Filament\Pages\Auth\Register as BaseRegister;
use Illuminate\Support\Facades\Auth;

class Register extends BaseRegister
{
    // Override method ini agar tidak otomatis login setelah registrasi
    protected function getRedirectUrl(): ?string
    {
        // Langsung logout user yang baru mendaftar
        Auth::logout();

        // Arahkan ke halaman login dengan pesan sukses
        $this->sendNotification();

        return route('filament.user.auth.login');
    }

    protected function sendNotification(): void
    {
        session()->flash('notification', [
            'title' => 'Registrasi Berhasil!',
            'body' => 'Akun Anda telah dibuat dan sedang menunggu persetujuan dari Admin. Silakan coba login kembali nanti.',
            'type' => 'success',
        ]);
    }
}