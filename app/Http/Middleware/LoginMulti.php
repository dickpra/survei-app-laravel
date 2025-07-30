<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use App\Providers\Filament\AssessorPanelProvider;
use App\Providers\Filament\AdministratorPanelProvider;

class LoginMulti
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Cek apakah pengguna sudah login sebagai 'admin' atau 'administrator'
        // Sesuaikan 'administrator' dengan ID panel admin Anda
        if (Auth::guard('admin')->check()) {
            // Arahkan ke rute dashboard panel admin
            return redirect()->route('filament.admin.pages.dashboard');
        }

        // Cek apakah pengguna sudah login sebagai 'user'
        if (Auth::guard('user')->check()) {
            // Arahkan ke rute dashboard panel asesor
            return redirect()->route('filament.user.pages.dashboard');
        }

        // Jika tidak ada yang login, izinkan untuk melanjutkan ke halaman yang dituju (halaman login)
        return $next($request);
    }
}
