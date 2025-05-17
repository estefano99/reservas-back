<?php

use App\Http\Controllers\ReservationController;
use App\Http\Middleware\IsAdmin;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {

    // Rutas para usuarios
    Route::get('/reservations', [ReservationController::class, 'index']);
    Route::post('/reservations', [ReservationController::class, 'store']);
    Route::delete('/reservations/{reservation}', [ReservationController::class, 'destroy']);
    Route::middleware('auth:sanctum')->get('/reservations/by-space', [ReservationController::class, 'availableSlots']);

    // Rutas exclusivas para admin
    Route::middleware([IsAdmin::class])->group(function () {
        Route::get('/admin/reservations', [ReservationController::class, 'getPending']);
        Route::patch('/admin/reservations/{reservation}/status', [ReservationController::class, 'updateStatus']);
    });
});
