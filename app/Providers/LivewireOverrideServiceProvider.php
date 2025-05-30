<?php
namespace App\Providers;

use App\Http\Controllers\CustomLivewireController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class LivewireOverrideServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->overrideRoutes();
    }

    public function overrideRoutes(): void
    {
        Route::post('/livewire/upload-file', [CustomLivewireController::class, 'handle'])
            ->name('livewire.upload-file')
            ->middleware(['web']); 
    }
}