<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages;
use App\Filament\Resources\ActivityLogResource\RelationManagers;
use Rmsramos\Activitylog\Resources\ActivitylogResource as ActivityLog;


class ActivityLogResource extends ActivityLog
{

    protected static bool $isScopedToTenant = false;
    protected static ?string $model = ActivityLog::class;
    protected static ?string $navigationGroup = 'Admin';


    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
            'view' => Pages\ViewActivityLog::route('/{record}'),
        ];
    }
}
