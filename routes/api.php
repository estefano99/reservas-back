<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

require __DIR__ . '/auth/auth.php';
require __DIR__ . '/spaces/spaces.php';
require __DIR__ . '/reservations/reservations.php';

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    Log::info('User requested', ['user' => $request->user()]);
    return $request->user();
});
