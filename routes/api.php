<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PointsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// User Balance API
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user/balance', function (Request $request) {
        $user = $request->user();
        return response()->json([
            'success' => true,
            'balance' => $user->points_balance,
            'formatted_balance' => number_format($user->points_balance, 8),
        ]);
    });
});

// Points API Routes
Route::prefix('points')->middleware('auth:sanctum')->group(function () {
    Route::get('/balance', [PointsController::class, 'balance']);
    Route::get('/history', [PointsController::class, 'history']);
    Route::get('/stats', [PointsController::class, 'stats']);
});
