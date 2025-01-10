<?php

namespace App\Filament\Resources\ParcelResource\Api;

use App\Filament\Resources\ParcelResource;
use Rupadana\ApiService\ApiService;


class ParcelApiService extends ApiService
{
    protected static string|null $resource = ParcelResource::class;

    public static function handlers(): array
    {
        return [
            Handlers\CreateHandler::class,
            Handlers\UpdateHandler::class,
            Handlers\PaginationHandler::class,
            Handlers\DetailHandler::class
        ];

    }
}
