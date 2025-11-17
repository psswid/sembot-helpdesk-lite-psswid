<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ExternalUserController;

// Public auth endpoints
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected auth endpoints
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    // Kept for compatibility with default scaffolding
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});

// External users integration (JSONPlaceholder)
Route::get('/external-users', [ExternalUserController::class, 'index']);
Route::post('/external-users/sync', [ExternalUserController::class, 'sync'])
    ->middleware('auth:sanctum');
