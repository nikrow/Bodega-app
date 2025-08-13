<?php

namespace App\Providers\Filament;


use Filament\Pages;
use Filament\Panel;
use App\Models\Field;
use Filament\Widgets;
use Filament\PanelProvider;
use App\Filament\Pages\Backups;
use Filament\Navigation\MenuItem;
use Illuminate\Support\Facades\Auth;
use Filament\Navigation\NavigationGroup;
use Rupadana\ApiService\ApiServicePlugin;
use Filament\Http\Middleware\Authenticate;
use App\Filament\Widgets\ClimateStatsOverview;
use App\Filament\Resources\ActivityLogResource;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Swis\Filament\Backgrounds\ImageProviders\MyImages;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Swis\Filament\Backgrounds\FilamentBackgroundsPlugin;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Edwink\FilamentUserActivity\FilamentUserActivityPlugin;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use ShuvroRoy\FilamentSpatieLaravelBackup\FilamentSpatieLaravelBackupPlugin;


class BodegaPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('campo')
            ->path('/gestion')
            ->login()
            ->profile()
            ->colors([
                'primary' => ('#568203'),
                'secondary' => ('#2F0381'),
            ])
            ->databaseTransactions()
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->sidebarCollapsibleOnDesktop()
            ->tenant(field::class, slugAttribute: 'slug')
            ->brandLogo(secure_asset('/img/logovector2.svg'))
            ->favicon(secure_asset('/img/logovector2.svg'))
            ->default()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->navigationGroups([

                NavigationGroup::make()
                    ->label('Aplicaciones'),
                NavigationGroup::make()
                    ->label('Informes')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Bodega'),
                NavigationGroup::make()
                    ->label('Maquinaria')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Anexos')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Admin')
                    ->collapsed(),
            ])
            ->unsavedChangesAlerts()
            ->pages([
                Pages\Dashboard::class,
                
            ])
            ->plugins([
                FilamentBackgroundsPlugin::make()
                    ->remember(900)
                    ->imageProvider(
                        MyImages::make()
                            ->directory('/img/backgrounds')
                    ),
                FilamentSpatieLaravelBackupPlugin::make()
                    ->authorize(fn (): bool => Auth::user()->email === 'admin@admin.com')
                    ->usingPage(Backups::class)
                
            ])
            ->authGuard('web')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([

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
