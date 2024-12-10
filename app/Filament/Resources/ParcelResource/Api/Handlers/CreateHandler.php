<?php

namespace App\Filament\Resources\ParcelResource\Api\Handlers;

use App\Filament\Resources\ParcelResource;
use Illuminate\Http\Request;
use Rupadana\ApiService\Http\Handlers;

class CreateHandler extends Handlers
{
    public static string|null $uri = '/';
    public static string|null $resource = ParcelResource::class;

    public static function getMethod()
    {
        return Handlers::POST;
    }

    public function handler(Request $request)
    {
        $model = new (static::getModel());

        $model->fill($request->all());

        $model->save();

        return static::sendSuccessResponse($model, "Successfully Create Resource");
    }

    public static function getModel()
    {
        return static::$resource::getModel();
    }
}
