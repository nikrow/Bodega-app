<?php

namespace App\Filament\Resources\ParcelResource\Api\Handlers;

use App\Filament\Resources\ParcelResource;
use Illuminate\Http\Request;
use Rupadana\ApiService\Http\Handlers;

class DeleteHandler extends Handlers
{
    public static string|null $uri = '/{id}';
    public static string|null $resource = ParcelResource::class;

    public static function getMethod()
    {
        return Handlers::DELETE;
    }

    public function handler(Request $request)
    {
        $id = $request->route('id');

        $model = static::getModel()::find($id);

        if (!$model) return static::sendNotFoundResponse();

        $model->delete();

        return static::sendSuccessResponse($model, "Successfully Delete Resource");
    }

    public static function getModel()
    {
        return static::$resource::getModel();
    }
}
