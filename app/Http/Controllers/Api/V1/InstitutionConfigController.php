<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateInstitutionConfigRequest;
use App\Http\Resources\InstitutionConfigResource;
use App\Models\AuditLog;
use App\Services\InstitutionConfigService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class InstitutionConfigController extends Controller
{
    public function __construct(
        private InstitutionConfigService $institutionConfigService
    ) {}

    /**
     * Display institution configuration (public endpoint).
     */
    public function show(Request $request): JsonResponse
    {
        $config = $this->institutionConfigService->getConfig();

        return response()->json([
            'success' => true,
            'data' => new InstitutionConfigResource($config),
        ]);
    }

    /**
     * Update institution configuration (admin only).
     */
    public function update(UpdateInstitutionConfigRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $userId = $request->user()->id;
        
        // Log everything for debugging
        Log::info('InstitutionConfigController - update called', [
            'all_input' => $request->all(),
            'all_files' => array_keys($request->allFiles()),
            'has_member_images' => $request->has('member_images'),
            'validated' => $validated,
        ]);
        
        // Extract member images from request
        // Laravel converts member_images[uuid] to an array: member_images => ['uuid' => file]
        $memberImages = $request->file('member_images', []);
        
        Log::info('Extracted member images', [
            'member_images_keys' => array_keys($memberImages),
            'member_images_count' => count($memberImages),
        ]);

        // Get existing config for audit log
        $existingConfig = $this->institutionConfigService->getConfig();

        $config = $this->institutionConfigService->updateConfig(
            $validated,
            $memberImages,
            $userId
        );

        // Create audit log for configuration update
        AuditLog::create([
            'user_id' => $userId,
            'entity_type' => 'InstitutionConfig',
            'entity_id' => null, // Config is stored in settings table with key
            'action' => 'institution_config_updated',
            'before' => [
                'mission' => $existingConfig['mission'] ?? null,
                'vision' => $existingConfig['vision'] ?? null,
                'members_count' => count($existingConfig['members'] ?? []),
                'social_media' => $existingConfig['social_media'] ?? null,
            ],
            'after' => [
                'mission' => $config['mission'] ?? null,
                'vision' => $config['vision'] ?? null,
                'members_count' => count($config['members'] ?? []),
                'social_media' => $config['social_media'] ?? null,
                'updated_at' => now()->toISOString(),
                'user_agent' => $request->userAgent(),
                'ip_address' => $request->ip(),
            ],
        ]);

        return response()->json([
            'success' => true,
            'data' => new InstitutionConfigResource($config),
            'message' => 'Configuración institucional actualizada con éxito.',
        ]);
    }
}
