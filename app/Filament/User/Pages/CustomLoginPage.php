<?php

namespace App\Filament\User\Pages;

use App\Models\User;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Facades\Filament;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Models\Contracts\FilamentUser;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Validation\ValidationException;

class CustomLoginPage extends BaseLogin
{
    /**
     * Arahkan ke file view kustom kita.
     */
    protected static string $view = 'filament.user.pages.custom-login-page';

    /**
     * Mendefinisikan form login secara eksplisit untuk stabilitas.
     */
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getRememberFormComponent(),
            ])
            ->statePath('data');
    }

    /**
     * Menimpa method authenticate dengan logika lengkap kita.
     */
    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            Notification::make()
                ->title(__('filament-panels::pages/auth/login.messages.throttled.title', [
                    'seconds' => $exception->secondsUntilAvailable,
                    'minutes' => ceil($exception->secondsUntilAvailable / 60),
                ]))
                ->body(array_key_exists('body', __('filament-panels::pages/auth/login.messages.throttled') ?: []) ? __('filament-panels::pages/auth/login.messages.throttled.body', [
                    'seconds' => $exception->secondsUntilAvailable,
                    'minutes' => ceil($exception->secondsUntilAvailable / 60),
                ]) : null)
                ->danger()
                ->send();

            return null;
        }

        $data = $this->form->getState();

        // Coba login dengan kredensial yang diberikan
        if (!Filament::auth()->attempt($this->getCredentialsFromFormData($data), $data['remember'] ?? false)) {
            throw ValidationException::withMessages([
                'data.email' => __('filament-panels::pages/auth/login.messages.failed'),
            ]);
        }

        $user = Filament::auth()->user();

        // Pengecekan status persetujuan setelah kredensial dipastikan benar
        if ($user instanceof FilamentUser && !$user->is_approved) {
            Filament::auth()->logout();
            
            throw ValidationException::withMessages([
                'data.email' => __('Akun Anda sedang menunggu persetujuan Admin.'),
            ]);
        }

        session()->regenerate();

        return app(LoginResponse::class);
    }
}