<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ExternalUserController;
use App\Http\Controllers\Api\TicketController;
use App\Models\Ticket;

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

// Ticket authorization layer (policies) — to be used by EPIC 3 CRUD endpoints
Route::middleware('auth:sanctum')->prefix('tickets')->group(function () {
    // Index: list tickets with filters and pagination
    Route::get('/', [TicketController::class, 'index'])
        ->middleware('can:viewAny,'.Ticket::class);

    // Store: create new ticket
    Route::post('/', [TicketController::class, 'store'])
        ->middleware('can:create,'.Ticket::class);

    // Example policy smoke route (safe no-op) to validate wiring
    Route::get('/_policy-smoke', function () {
        return response()->json(['ok' => true]);
    })->middleware('can:viewAny,'.Ticket::class);

    // Role middleware smoke route: allow agents and admins only
    Route::get('/_agent-smoke', function () {
        return response()->json(['ok' => true, 'role' => 'agent|admin']);
    })->middleware('role:agent,admin');

    // When wiring CRUD, apply policy middleware like:
    // index:   ->middleware('can:viewAny,'.Ticket::class)
    // store:   ->middleware('can:create,'.Ticket::class)
    // show:    ->middleware('can:view,ticket')          // with {ticket} binding
    // update:  ->middleware('can:update,ticket')        // with {ticket} binding
    // delete:  ->middleware('can:delete,ticket')        // with {ticket} binding
});
