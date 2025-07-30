<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Models\User; // Import model Assessor

class UnifiedLoginController extends Controller
{
    /**
     * Menampilkan halaman form login.
     */
    public function create()
    {
        return view('auth.login');
    }

    /**
     * Memproses upaya login dari pengguna.
     */
    public function store(Request $request)
    {
        // Validasi input dasar
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // --- INI LOGIKA UTAMA YANG SUDAH DIPERBAIKI ---

        // 1. Coba login sebagai Admin (menggunakan guard 'admin')
        if (Auth::guard('admin')->attempt($credentials)) {
            $request->session()->regenerate();
            
            // Berikan path panel admin sebagai fallback
            return redirect()->intended(config('filament.panels.admin.path', '/administrator'));
        }

        // 2. Jika gagal sebagai admin, cek dulu apakah user ini adalah user yang belum disetujui
        $user = User::where('email', $credentials['email'])->first();
        // if ($user && $user->status !== 'approved') {
        //     throw ValidationException::withMessages([
        //         'email' => __('Akun Anda sedang menunggu persetujuan admin atau telah ditolak.'),
        //     ]);
        // }
        if ($user && $user->status == 'pending') {
            // Jika ya, langsung lemparkan pesan error spesifik kita.
            // Proses autentikasi berhenti di sini dan tidak akan melanjutkan ke pengecekan password.
            throw ValidationException::withMessages([
                'data.email' => __('Akun Anda sedang menunggu persetujuan admin'),
            ]);
        }if ($user && $user->status == 'rejected') {
            // Jika tidak, atau jika asesornya sudah 'approved', maka kita melanjutkan ke proses
            // pengecekan password.
            throw ValidationException::withMessages([
                'data.email' => __('Akun Anda ditolak admin'),
            ]);
        }

        // 3. Jika bukan masalah status, coba login sebagai User (menggunakan guard 'user')
        if (Auth::guard('user')->attempt($credentials)) {
            $request->session()->regenerate();

            // Berikan path panel user sebagai fallback
            return redirect()->intended(config('filament.panels.user.path', '/user'));
        }

        // 4. Jika semua upaya gagal, lemparkan error kredensial standar
        throw ValidationException::withMessages([
            'email' => __('These credentials do not match our records.'),
        ]);
    }
}