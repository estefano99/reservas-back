<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SpaceController;
use App\Http\Middleware\IsAdmin;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/spaces', [SpaceController::class, 'index']);

    Route::middleware([IsAdmin::class])->group(function () {
        Route::post('/spaces', [SpaceController::class, 'store']);
        Route::put('/spaces/{space}', [SpaceController::class, 'update']);
        Route::delete('/spaces/{space}', [SpaceController::class, 'destroy']);
    });
});
