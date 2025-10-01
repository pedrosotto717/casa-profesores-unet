<?php declare(strict_types=1);

use App\Http\Controllers\Api\V1\AcademyController;
use App\Http\Controllers\Api\V1\AreaController;
use App\Http\Controllers\Api\V1\AuthenticationController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\InvitationController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\UploadController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Authentication routes
    Route::post('/auth/register', RegisterController::class);
    Route::post('/login', [AuthenticationController::class, 'login']);
    
    // Public routes (no authentication required)
    Route::get('/areas', [AreaController::class, 'index']);
    Route::get('/areas/{area}', [AreaController::class, 'show']);
    Route::get('/academies', [AcademyController::class, 'index']);
    Route::get('/academies/{academy}', [AcademyController::class, 'show']);
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{user}', [UserController::class, 'show']);
    
    // File upload public routes
    Route::get('/uploads', [UploadController::class, 'index']);
    Route::get('/uploads/{id}', [UploadController::class, 'show']);
    
    // Protected routes (authentication required)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthenticationController::class, 'logout']);
        
        // Notifications routes (authenticated users)
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::get('/notifications/unread', [NotificationController::class, 'unread']);
        Route::get('/notifications/count', [NotificationController::class, 'count']);
        Route::put('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::put('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
        
        // Invitations routes (authenticated users can create, admin can manage)
        Route::post('/invitations', [InvitationController::class, 'store']);
        
        // File upload protected routes
        Route::post('/uploads', [UploadController::class, 'store']);
        Route::delete('/uploads/{id}', [UploadController::class, 'destroy']);
        Route::post('/uploads/presign', [UploadController::class, 'presign']);
        
        // Admin-only routes (authentication + admin role required)
        Route::middleware('admin')->group(function () {
            // Users CRUD
            Route::post('/users', [UserController::class, 'store']);
            Route::put('/users/{user}', [UserController::class, 'update']);
            Route::delete('/users/{user}', [UserController::class, 'destroy']);
            Route::post('/users/{user}/invite', [UserController::class, 'invite']);
            
            // Admin specific routes
            Route::get('/admin/pending-registrations', [UserController::class, 'pendingRegistrations']);
            
            // Invitations management (admin only)
            Route::get('/invitations', [InvitationController::class, 'index']);
            Route::get('/invitations/pending', [InvitationController::class, 'pending']);
            Route::put('/invitations/{id}/approve', [InvitationController::class, 'approve']);
            Route::put('/invitations/{id}/reject', [InvitationController::class, 'reject']);
            
            // Areas CRUD
            Route::post('/areas', [AreaController::class, 'store']);
            Route::put('/areas/{area}', [AreaController::class, 'update']);
            Route::delete('/areas/{area}', [AreaController::class, 'destroy']);
            
            // Academies CRUD
            Route::post('/academies', [AcademyController::class, 'store']);
            Route::put('/academies/{academy}', [AcademyController::class, 'update']);
            Route::delete('/academies/{academy}', [AcademyController::class, 'destroy']);
        });
    });
});

// Legacy route for backward compatibility
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
