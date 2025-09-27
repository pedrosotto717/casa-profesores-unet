<?php declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

final class AdminOnly
{
    /**
     * Handle an incoming request.
     * 
     * Ensures that only users with 'administrador' role can access protected endpoints.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'message' => 'Authentication required.',
                'error' => 'UNAUTHENTICATED'
            ], 401);
        }

        if ($user->role !== UserRole::Administrador) {
            return response()->json([
                'message' => 'Access denied. Administrator privileges required.',
                'error' => 'INSUFFICIENT_PRIVILEGES'
            ], 403);
        }
        
        return $next($request);
    }
}

