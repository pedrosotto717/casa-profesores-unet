<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuditLogResource;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class AuditLogController extends Controller
{
    /**
     * Display a listing of audit logs with filters.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = AuditLog::with('user:id,name,role')
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->filled('entity_type')) {
            $query->where('entity_type', $request->string('entity_type'));
        }

        if ($request->filled('entity_id')) {
            $query->where('entity_id', $request->integer('entity_id'));
        }

        if ($request->filled('action')) {
            $query->where('action', $request->string('action'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->date('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->date('to'));
        }

        if ($request->filled('q')) {
            $searchTerm = $request->string('q');
            $query->where(function ($q) use ($searchTerm) {
                $q->whereJsonContains('before', $searchTerm)
                  ->orWhereJsonContains('after', $searchTerm);
            });
        }

        $perPage = min($request->integer('per_page', 15), 100);
        
        return AuditLogResource::collection($query->paginate($perPage));
    }

    /**
     * Display the specified audit log.
     */
    public function show(AuditLog $auditLog): AuditLogResource
    {
        $auditLog->load('user:id,name,role');
        
        return new AuditLogResource($auditLog);
    }
}
