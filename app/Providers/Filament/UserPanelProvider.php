<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Filament\User\Pages\Dashboard;
use App\Filament\User\Pages\Auth\Login;
use App\Filament\User\Pages\Auth\LoginCustom;
use App\Filament\User\Pages\Auth\LoginCustomPage;
use App\Filament\User\Pages\Auth\Register;
use App\Filament\User\Pages\CustomLoginPage;
use Filament\Navigation\MenuItem; // <-- TAMBAHKAN USE STATEMENT INI



class UserPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->brandName('Platform Survei')
            ->id('user')
            ->path('user')
            ->profile()
            ->authGuard('user') 
            ->userMenuItems([
                MenuItem::make()
                    ->label('Profil Saya')
                    ->icon('heroicon-o-user-circle')
                    // getProfileUrl() akan otomatis mengarah ke /user/profile
                    ->url(fn (): string => filament()->getProfileUrl()),
            ])
            // ->registration()
            // ->login(Login::class)
            ->registration(Register::class)
            // ->login()
            ->colors([
                'primary' => Color::Pink,
            ])
            ->discoverResources(in: app_path('Filament/User/Resources'), for: 'App\\Filament\\User\\Resources')
            ->discoverPages(in: app_path('Filament/User/Pages'), for: 'App\\Filament\\User\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/User/Widgets'), for: 'App\\Filament\\User\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
