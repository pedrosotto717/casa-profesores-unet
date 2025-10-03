<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\SendPulseService;
use Illuminate\Http\JsonResponse;

final class TestEmailController extends Controller
{
    public function __construct(
        private readonly SendPulseService $sendPulseService
    ) {}

    /**
     * Test SendPulse configuration
     */
    public function testConfig(): JsonResponse
    {
        $config = $this->sendPulseService->testConfiguration();
        
        return response()->json([
            'success' => true,
            'data' => $config,
            'message' => 'Configuración de SendPulse verificada',
        ]);
    }

    /**
     * Test email sending with minimal data
     */
    public function testEmail(): JsonResponse
    {
        try {
            $result = $this->sendPulseService->sendBasic(
                [['email' => 'test@example.com', 'name' => 'Test User']],
                'Test Email - CPU UNET',
                '<h1>Test Email</h1><p>This is a test email from CPU UNET system.</p>',
                'Test Email - This is a test email from CPU UNET system.'
            );

            return response()->json([
                'success' => $result['ok'],
                'data' => $result,
                'message' => $result['ok'] ? 'Email de prueba enviado exitosamente' : 'Error al enviar email de prueba',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Error al enviar email de prueba',
            ], 500);
        }
    }
}

