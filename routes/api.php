<?php declare(strict_types=1);

use App\Http\Controllers\Api\V1\AuthenticationController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\EmailVerificationController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Authentication routes
    Route::post('/auth/register', RegisterController::class);
    Route::post('/login', [AuthenticationController::class, 'login']);
    
    // Email verification routes
    Route::post('/email/verify', [EmailVerificationController::class, 'verify']);
    
    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthenticationController::class, 'logout']);
        Route::apiResource('users', UserController::class)->only(['index', 'show']);
    });
});

// Legacy route for backward compatibility
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
