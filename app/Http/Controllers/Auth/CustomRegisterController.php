<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Filament\Notifications\Notification; // <-- [PENTING] Tambahkan ini

class CustomRegisterController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create()
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        // Set this to `true` if new accounts require admin approval.
        // Set to `false` if accounts should be active immediately.
        $requiresApproval = true;

        // --- Custom Validation for Existing Email ---
        $existingUser = User::where('email', $request->email)->first();
        if ($existingUser) {
            $roles = [];
            if ($existingUser->is_researcher) $roles[] = 'Researcher';
            if ($existingUser->is_instrument_creator) $roles[] = 'Instrument Creator';
            $roleText = implode(' & ', $roles);

            throw ValidationException::withMessages([
                'email' => "Anda sudah terdaftar sebagai {$roleText}. Silakan login, atau ajukan peran lain di halaman profil Anda.",
            ]);
        }
        // --- End of Custom Validation ---

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'string', 'in:researcher,instrument_creator'],
        ]);
        
        $roleField = 'is_' . $request->role;
        
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            $roleField => true,
            'approved_at' => $requiresApproval ? null : now(),
        ]);

        event(new Registered($user));

        // --- Session Flash Logic for Pop-up Modal ---
        if ($requiresApproval) {
            session()->flash('registration_success', [
                'title' => 'Registrasi Berhasil!',
                'body' => 'Akun Anda telah dibuat. Mohon tunggu persetujuan dari Admin untuk bisa login.',
            ]);
        } else {
            session()->flash('registration_success', [
                'title' => 'Registrasi Selesai!',
                'body' => 'Akun Anda telah berhasil dibuat. Silakan login dengan akun Anda.',
            ]);
        }

        return redirect()->route('login');
    }
}