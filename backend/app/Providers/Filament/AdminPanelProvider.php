<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
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

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            // "Olvidé mi contraseña" (enlace firmado por correo) y página de perfil
            // para cambiar nombre, correo y contraseña sin tocar la base de datos.
            ->passwordReset()
            ->profile()
            ->brandName('AmePhia Admin')
            ->brandLogo(fn () => view('filament.admin.logo'))
            ->favicon(asset('favicon.png'))
            ->colors([
                'primary' => Color::Teal,
                'danger' => Color::Red,
                'gray' => Color::Slate,
                'info' => Color::Sky,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
            ])
            ->font('Inter')
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('Tenants')
                    ->icon('heroicon-o-building-office-2'),
                NavigationGroup::make()
                    ->label('Facturación')
                    ->icon('heroicon-o-banknotes'),
                NavigationGroup::make()
                    ->label('Árbitros')
                    ->icon('heroicon-o-flag'),
                NavigationGroup::make()
                    ->label('Integraciones')
                    ->icon('heroicon-o-puzzle-piece'),
                NavigationGroup::make()
                    ->label('Monitoreo')
                    ->icon('heroicon-o-chart-bar'),
                NavigationGroup::make()
                    ->label('Sistema')
                    ->icon('heroicon-o-cog-6-tooth'),
            ])
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth('full')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
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
                \App\Http\Middleware\AuthenticatePanel::class,
            ])
            ->databaseNotifications()
            ->spa();
    }
}
