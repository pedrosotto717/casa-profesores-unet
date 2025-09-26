<?php declare(strict_types=1);

use App\Http\Controllers\Api\V1\AuthenticationController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\UploadController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Authentication routes
    Route::post('/auth/register', RegisterController::class);
    Route::post('/login', [AuthenticationController::class, 'login']);
    
    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthenticationController::class, 'logout']);
        Route::apiResource('users', UserController::class)->only(['index', 'show']);
        
        // File upload routes
        Route::get('/uploads', [UploadController::class, 'index']);
        Route::post('/uploads', [UploadController::class, 'store']);
        Route::get('/uploads/{id}', [UploadController::class, 'show']);
        Route::delete('/uploads/{id}', [UploadController::class, 'destroy']);
        Route::post('/uploads/presign', [UploadController::class, 'presign']);
    });
});

// Legacy route for backward compatibility
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
