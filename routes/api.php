<?php declare(strict_types=1);

use App\Http\Controllers\Api\V1\AcademyController;
use App\Http\Controllers\Api\V1\AcademyStudentController;
use App\Http\Controllers\Api\V1\AreaController;
use App\Http\Controllers\Api\V1\AuditLogController;
use App\Http\Controllers\Api\V1\AuthenticationController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Chat\ChatController;
use App\Http\Controllers\Api\V1\Chat\UserBlockController;
use App\Http\Controllers\Api\V1\InvitationController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\PasswordResetController;
use App\Http\Controllers\Api\V1\ReservationController;
use App\Http\Controllers\Api\V1\SetPasswordController;
use App\Http\Controllers\Api\V1\TestEmailController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\UploadController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Authentication routes
    Route::post('/auth/register', RegisterController::class);
    Route::post('/auth/set-password', [SetPasswordController::class, 'setPassword']);
    Route::post('/auth/forgot-password', [PasswordResetController::class, 'forgotPassword']);
    Route::post('/auth/reset-password', [PasswordResetController::class, 'resetPassword']);
    Route::post('/login', [AuthenticationController::class, 'login']);
    
    // Test email routes (temporary for debugging)
    Route::get('/test-email/config', [TestEmailController::class, 'testConfig']);
    Route::post('/test-email/send', [TestEmailController::class, 'testEmail']);
    
    // Public routes (no authentication required)
    Route::get('/areas', [AreaController::class, 'index']);
    Route::get('/areas/{area}', [AreaController::class, 'show']);
    Route::get('/academies', [AcademyController::class, 'index']);
    Route::get('/academies/{academy}', [AcademyController::class, 'show']);
    
    
    // File upload public routes (only public files)
    Route::get('/uploads/public', [UploadController::class, 'publicIndex']);
    
    // Public availability endpoint
    Route::get('/reservations/availability', [ReservationController::class, 'availability']);
    
    // Protected routes (authentication required)
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/users/{user}', [UserController::class, 'show']);
        Route::post('/logout', [AuthenticationController::class, 'logout']);
        
        // Notifications routes (authenticated users)
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::get('/notifications/unread', [NotificationController::class, 'unread']);
        Route::get('/notifications/count', [NotificationController::class, 'count']);
        Route::put('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::put('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
        
        // Invitations routes (authenticated users can create, admin can manage)
        Route::post('/invitations', [InvitationController::class, 'store']);
        
        // Reservations routes (authenticated users: profesor, estudiante, invitado with status solvente)
        Route::get('/reservations', [ReservationController::class, 'index']);
        Route::post('/reservations', [ReservationController::class, 'store']);
        Route::put('/reservations/{id}', [ReservationController::class, 'update']);
        Route::post('/reservations/{id}/cancel', [ReservationController::class, 'cancel']);
        
        // File upload protected routes
        Route::get('/uploads', [UploadController::class, 'index']);
        Route::get('/uploads/{id}', [UploadController::class, 'show']);
        Route::post('/uploads', [UploadController::class, 'store']);
        Route::delete('/uploads/{id}', [UploadController::class, 'destroy']);
        Route::post('/uploads/presign', [UploadController::class, 'presign']);
        Route::put('/users/me', [UserController::class, 'updateMe']);
        
        // Users listing (administrador and profesor only)
        Route::middleware('role:administrador,profesor')->group(function () {
            Route::get('/users', [UserController::class, 'index']);
            Route::get('/invitations', [InvitationController::class, 'index']);
        });
        
        // Academy students management (instructor and admin only)
        Route::middleware('role:administrador,instructor')->prefix('academies/{academy}')->group(function () {
            Route::get('/students', [AcademyStudentController::class, 'index']);
            Route::post('/students', [AcademyStudentController::class, 'store']);
            Route::put('/students/{student}', [AcademyStudentController::class, 'update']);
            Route::delete('/students/{student}', [AcademyStudentController::class, 'destroy']);
        });
        
        // Admin-only routes (authentication + admin role required)
        Route::middleware('admin')->group(function () {
            // Users CRUD
            Route::post('/users', [UserController::class, 'store']);
            // Update user status and role, if user status is solvente, set role to profesor|estudiante
            Route::put('/users/{user}', [UserController::class, 'update']);
            Route::delete('/users/{user}', [UserController::class, 'destroy']);
            Route::post('/users/{user}/invite', [UserController::class, 'invite']);
            
            // Admin specific routes
            Route::get('/admin/pending-registrations', [UserController::class, 'pendingRegistrations']);
            
            // Invitations management (admin only)
            Route::get('/invitations/pending', [InvitationController::class, 'pending']);
            Route::put('/invitations/{id}/approve', [InvitationController::class, 'approve']);
            Route::put('/invitations/{id}/reject', [InvitationController::class, 'reject']);
            
            // Areas CRUD
            Route::post('/areas', [AreaController::class, 'store']);
            Route::post('/areas/{area}', [AreaController::class, 'update']);
            Route::post('/areas/{area}/test', [AreaController::class, 'testUpdate']);
            Route::delete('/areas/{area}', [AreaController::class, 'destroy']);
            
            // Academies CRUD
            Route::post('/academies', [AcademyController::class, 'store']);
            Route::post('/academies/{academy}', [AcademyController::class, 'update']);
            Route::delete('/academies/{academy}', [AcademyController::class, 'destroy']);
            
            // Reservations management (admin only)
            Route::post('/reservations/{id}/approve', [ReservationController::class, 'approve']);
            Route::post('/reservations/{id}/reject', [ReservationController::class, 'reject']);
            
            // Audit logs (admin only)
            Route::get('/audit-logs', [AuditLogController::class, 'index']);
            Route::get('/audit-logs/{auditLog}', [AuditLogController::class, 'show']);
        });
    });

    // Chat routes (authenticated users only)
    Route::middleware('auth:sanctum')->prefix('chat')->group(function () {
        // User search for starting conversations
        Route::get('/users/search', [ChatController::class, 'searchUsers']);
        
        // Conversation management
        Route::post('/conversations', [ChatController::class, 'createConversation']);
        Route::get('/conversations', [ChatController::class, 'listConversations']);
        Route::get('/conversations/{conversationId}/messages', [ChatController::class, 'getMessages']);
        Route::post('/conversations/{conversationId}/messages', [ChatController::class, 'sendMessage']);
        Route::post('/conversations/{conversationId}/read', [ChatController::class, 'markAsRead']);
        
        // Unread summary for polling
        Route::get('/unread/summary', [ChatController::class, 'getUnreadSummary']);
        
        // User blocking
        Route::get('/blocks', [UserBlockController::class, 'index']);
        Route::post('/blocks', [UserBlockController::class, 'store']);
        Route::delete('/blocks/{blockedUserId}', [UserBlockController::class, 'destroy']);
    });
});

// Legacy route for backward compatibility
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
