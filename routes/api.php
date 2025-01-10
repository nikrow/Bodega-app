<?php
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// Rutas públicas (registro e inicio de sesión)
Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);

// Rutas protegidas usando Sanctum
Route::middleware('auth:sanctum')->group(function () {
Route::get('/user', [UserController::class, 'profile']);
Route::post('/logout', [UserController::class, 'logout']);
});
