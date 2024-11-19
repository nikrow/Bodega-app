<?php

namespace App\Providers\Filament;


use App\Enums\RoleType;
use App\Filament\Resources\ActivityLogResource;
use App\Models\Field;
use App\Models\User;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Rmsramos\Activitylog\ActivitylogPlugin;


class BodegaPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('campo')
            ->path('')
            ->login()
            ->colors([
                'primary' => ('#568203'),
                'secondary' => ('#2F0381'),
            ])
            ->spa()
            ->databaseTransactions()
            ->tenant(field::class, slugAttribute: 'slug')
            ->brandLogo(asset('/img/logovector2.svg'))
            ->favicon(asset('/img/logovector2.svg'))
            ->font('Manrope')
            ->default()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->navigationGroups([
                'Aplicaciones',
                'Informes',
                'Bodega',
                'Anexos',
            ])
            ->unsavedChangesAlerts()
            ->pages([
                Pages\Dashboard::class,

            ])
            ->plugins([
                ActivitylogPlugin::make()
                    ->resource(ActivityLogResource::class)
                    ->navigationGroup('Anexos')
                    ->label('Log')
                    ->pluralLabel('Logs'),
            ])
            ->authGuard('web')
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
                Authenticate::class,
            ]);
    }
}
