<?php

namespace App\Services;

use Sendpulse\RestApi\ApiClient;
use Sendpulse\RestApi\Storage\FileStorage;
use Throwable;
use Illuminate\Support\Facades\Log;

class SendPulseService
{
    private ?ApiClient $client = null;

    public function __construct(?string $apiUserId = null, ?string $apiSecret = null)
    {
        $apiUserId = $apiUserId ?? env('SENDPULSE_API_USER_ID');
        $apiSecret = $apiSecret ?? env('SENDPULSE_API_SECRET');

        // Only initialize if credentials are provided
        if (!$apiUserId || !$apiSecret) {
            Log::warning('SendPulse credentials not configured. Service will be disabled.');
            return;
        }

        // Token cache en storage/app/sendpulse (carpeta debe existir)
        $storagePath = storage_path('app/sendpulse');
        if (!is_dir($storagePath)) {
            @mkdir($storagePath, 0775, true);
        }
        
        try {
            $this->client = new ApiClient($apiUserId, $apiSecret, new FileStorage($storagePath));
        } catch (\Exception $e) {
            Log::error('Failed to initialize SendPulse client: ' . $e->getMessage());
        }
    }

    /**
     * Envío transaccional básico con HTML y texto alternativo.
     * $to: [['email' => 'user@example.com', 'name' => 'User Name']]
     */
    public function sendBasic(array $to, string $subject, string $html, ?string $text = null, ?array $opts = []): array
    {
        // Check if client is initialized
        if (!$this->client) {
            Log::warning('SendPulse client not initialized. Email not sent.');
            return ['ok' => false, 'error' => 'SendPulse service not configured'];
        }

        $fromEmail = $opts['from_email'] ?? env('MAIL_FROM_ADDRESS', 'pedro.soto@unet.edu.ve');
        $fromName  = $opts['from_name']  ?? env('MAIL_FROM_NAME', 'CPU UNET');

        // Validar campos obligatorios
        if (empty($fromEmail) || empty($fromName) || empty($subject) || empty($html) || empty($to)) {
            Log::error('sendpulse.validation.error', [
                'fromEmail' => $fromEmail,
                'fromName' => $fromName,
                'subject' => $subject,
                'html_length' => strlen($html),
                'to_count' => count($to),
            ]);
            return ['ok' => false, 'error' => 'Campos obligatorios faltantes para envío de email'];
        }

        // Validar formato de email del remitente
        if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
            Log::error('sendpulse.validation.error', ['message' => 'Email del remitente inválido', 'email' => $fromEmail]);
            return ['ok' => false, 'error' => 'Email del remitente inválido'];
        }

        // Validar destinatarios
        foreach ($to as $recipient) {
            if (!isset($recipient['email']) || !filter_var($recipient['email'], FILTER_VALIDATE_EMAIL)) {
                Log::error('sendpulse.validation.error', ['message' => 'Email del destinatario inválido', 'recipient' => $recipient]);
                return ['ok' => false, 'error' => 'Email del destinatario inválido'];
            }
        }

        // Use the working "simple" format with base64-encoded HTML
        $payload = [
            'email' => [
                'subject' => $subject,
                'html' => base64_encode($html),
                'text' => $text ?? strip_tags($html),
                'from' => [
                    'email' => $fromEmail,
                    'name' => $fromName
                ],
                'to' => $to
            ]
        ];

        try {
            $res = $this->client->post('smtp/emails', $payload);
            return ['ok' => true, 'data' => $res];
        } catch (Throwable $e) {
            Log::error('sendpulse.send.error', [
                'message' => $e->getMessage(),
                'code'    => method_exists($e,'getCode') ? $e->getCode() : null,
                'payload' => $payload,
                'trace'   => $e->getTraceAsString(),
            ]);
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Envío usando template de SendPulse (con variables dinámicas)
     */
    public function sendWithTemplate(array $to, string $subject, int $templateId, array $vars, ?array $opts = []): array
    {
        $fromEmail = $opts['from_email'] ?? env('MAIL_FROM_ADDRESS');
        $fromName  = $opts['from_name']  ?? env('MAIL_FROM_NAME');

        $payload = [
            'email' => [
                'subject'  => $subject,
                'template' => [
                    'id'        => $templateId,
                    'variables' => $vars,
                ],
                'from' => ['name' => $fromName, 'email' => $fromEmail],
                'to'   => $to,
            ],
        ];

        try {
            $res = $this->client->post('smtp/emails', $payload);
            return ['ok' => true, 'data' => $res];
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send email using transactional emails API (alternative method)
     */
    public function sendTransactional(array $to, string $subject, string $html, ?string $text = null, ?array $opts = []): array
    {
        $fromEmail = $opts['from_email'] ?? env('MAIL_FROM_ADDRESS', 'pedro.soto@unet.edu.ve');
        $fromName  = $opts['from_name']  ?? env('MAIL_FROM_NAME', 'CPU UNET');

        // Validar campos obligatorios
        if (empty($fromEmail) || empty($fromName) || empty($subject) || empty($html) || empty($to)) {
            Log::error('sendpulse.validation.error', [
                'fromEmail' => $fromEmail,
                'fromName' => $fromName,
                'subject' => $subject,
                'html_length' => strlen($html),
                'to_count' => count($to),
            ]);
            return ['ok' => false, 'error' => 'Campos obligatorios faltantes para envío de email'];
        }

        // Use transactional emails endpoint instead
        $payload = [
            'subject' => $subject,
            'text' => $text ?? strip_tags($html),
            'html' => $html,
            'from' => [
                'name' => $fromName,
                'email' => $fromEmail
            ],
            'to' => array_map(function($recipient) {
                return [
                    'email' => $recipient['email'],
                    'name' => $recipient['name'] ?? ''
                ];
            }, $to)
        ];

        try {
            // Try the transactional emails endpoint
            $res = $this->client->post('emails', $payload);
            return ['ok' => true, 'data' => $res];
        } catch (Throwable $e) {
            Log::error('sendpulse.transactional.error', [
                'message' => $e->getMessage(),
                'code'    => method_exists($e,'getCode') ? $e->getCode() : null,
                'payload' => $payload,
                'trace'   => $e->getTraceAsString(),
            ]);
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send email using simple format (minimal payload)
     */
    public function sendSimple(array $to, string $subject, string $html, ?string $text = null, ?array $opts = []): array
    {
        $fromEmail = $opts['from_email'] ?? env('MAIL_FROM_ADDRESS', 'pedro.soto@unet.edu.ve');
        $fromName  = $opts['from_name']  ?? env('MAIL_FROM_NAME', 'CPU UNET');

        // Minimal payload structure
        $payload = [
            'email' => [
                'subject' => $subject,
                'html' => base64_encode($html),
                'text' => $text ?? strip_tags($html),
                'from' => [
                    'email' => $fromEmail,
                    'name' => $fromName
                ],
                'to' => $to
            ]
        ];

        try {
            $res = $this->client->post('smtp/emails', $payload);
            return ['ok' => true, 'data' => $res];
        } catch (Throwable $e) {
            Log::error('sendpulse.simple.error', [
                'message' => $e->getMessage(),
                'code'    => method_exists($e,'getCode') ? $e->getCode() : null,
                'payload' => $payload,
                'trace'   => $e->getTraceAsString(),
            ]);
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Test SendPulse authentication and account info
     */
    public function testAuthentication(): array
    {
        try {
            // Intentar obtener información de la cuenta usando un endpoint que sabemos que existe
            $res = $this->client->get('user/info');
            return ['ok' => true, 'data' => $res];
        } catch (Throwable $e) {
            Log::error('sendpulse.auth.error', [
                'message' => $e->getMessage(),
                'code'    => method_exists($e,'getCode') ? $e->getCode() : null,
                'trace'   => $e->getTraceAsString(),
            ]);
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Check account balance and limits
     */
    public function checkAccountLimits(): array
    {
        try {
            $balance = $this->client->get('balance');
            $senders = $this->client->get('senders');
            
            return [
                'ok' => true, 
                'data' => [
                    'balance' => $balance,
                    'senders' => $senders
                ]
            ];
        } catch (Throwable $e) {
            Log::error('sendpulse.limits.error', [
                'message' => $e->getMessage(),
                'code'    => method_exists($e,'getCode') ? $e->getCode() : null,
                'trace'   => $e->getTraceAsString(),
            ]);
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Test SendPulse configuration
     */
    public function testConfiguration(): array
    {
        $apiUserId = env('SENDPULSE_API_USER_ID');
        $apiSecret = env('SENDPULSE_API_SECRET');
        $fromEmail = env('MAIL_FROM_ADDRESS');
        $fromName = env('MAIL_FROM_NAME');

        return [
            'api_user_id_set' => !empty($apiUserId),
            'api_secret_set' => !empty($apiSecret),
            'from_email_set' => !empty($fromEmail),
            'from_name_set' => !empty($fromName),
            'from_email_valid' => !empty($fromEmail) && filter_var($fromEmail, FILTER_VALIDATE_EMAIL),
            'config' => [
                'api_user_id' => $apiUserId ? substr($apiUserId, 0, 8) . '...' : null,
                'api_secret' => $apiSecret ? substr($apiSecret, 0, 8) . '...' : null,
                'from_email' => $fromEmail,
                'from_name' => $fromName,
            ]
        ];
    }

    /**
     * Send account approved email to auto-registered user
     */
    public function sendAccountApprovedEmail(string $userEmail, string $userName, string $userRole): array
    {
        $subject = '¡Bienvenido a la Casa del Profesor Universitario!';
        $html = $this->generateAccountApprovedHtml($userName, $userRole, $userEmail);
        $text = $this->generateAccountApprovedText($userName, $userRole);

        return $this->sendBasic(
            [['email' => $userEmail, 'name' => $userName]],
            $subject,
            $html,
            $text
        );
    }

    /**
     * Send account rejection email to user.
     */
    public function sendAccountRejectedEmail(string $userEmail, string $userName): array
    {
        $subject = 'Solicitud de registro - Casa del Profesor Universitario';
        $html = $this->generateAccountRejectedHtml($userName);
        $text = $this->generateAccountRejectedText($userName);

        return $this->sendBasic(
            [['email' => $userEmail, 'name' => $userName]],
            $subject,
            $html,
            $text
        );
    }

    /**
     * Send invitation approved email with auth code
     */
    public function sendInvitationApprovedEmail(string $userEmail, string $userName, string $authCode): array
    {
        $subject = 'Tu cuenta en la Casa del Profesor Universitario está lista';
        $html = $this->generateInvitationApprovedHtml($userName, $authCode);
        $text = $this->generateInvitationApprovedText($userName, $authCode);

        return $this->sendBasic(
            [['email' => $userEmail, 'name' => $userName]],
            $subject,
            $html,
            $text
        );
    }

    /**
     * Generate HTML for account approved email
     */
    private function generateAccountApprovedHtml(string $userName, string $userRole, string $userEmail): string
    {
        $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
        
        return "
        <!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>¡Bienvenido a la Casa del Profesor Universitario!</title>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f4f4f4; }
                .container { background-color: #ffffff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .header { text-align: center; border-bottom: 3px solid #2c5aa0; padding-bottom: 20px; margin-bottom: 30px; }
                .logo { font-size: 24px; font-weight: bold; color: #2c5aa0; margin-bottom: 10px; }
                .subtitle { color: #666; font-size: 14px; }
                .highlight { background-color: #f8f9fa; padding: 15px; border-left: 4px solid #2c5aa0; margin: 20px 0; }
                .footer { border-top: 1px solid #eee; padding-top: 20px; text-align: center; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <div class='logo'>🏛️ Casa del Profesor Universitario</div>
                    <div class='subtitle'>Universidad Nacional Experimental del Táchira</div>
                </div>
                
                <h2>¡Felicidades, {$userName}!</h2>
                
                <p>Nos complace informarte que tu solicitud de registro en la <strong>Casa del Profesor Universitario</strong> ha sido <strong>aprobada</strong> por la administración.</p>
                
                <div class='highlight'>
                    <h3>🎉 ¡Tu cuenta está activa!</h3>
                    <p><strong>Rol asignado:</strong> " . ucfirst($userRole) . "</p>
                    <p><strong>Email:</strong> {$userEmail}</p>
                </div>
                
                <h3>Próximos pasos recomendados:</h3>
                <ol>
                    <li>Inicia sesión en el sistema</li>
                    <li>Completa tu perfil si es necesario</li>
                    <li>Explora las funcionalidades disponibles</li>
                    <li>Consulta el reglamento de uso</li>
                </ol>
                
                <p>Si tienes alguna pregunta o necesitas ayuda, no dudes en contactar a la administración.</p>
                
                <p>¡Bienvenido a la Casa del Profesor Universitario!</p>
                
                <p><strong>Equipo de Administración</strong><br>
                Casa del Profesor Universitario - UNET</p>
                
                <div class='footer'>
                    <p>Este correo fue enviado automáticamente por el sistema de la Casa del Profesor Universitario.</p>
                    <p>Universidad Nacional Experimental del Táchira (UNET)</p>
                    <p>Si tienes alguna consulta, contacta a la administración.</p>
                </div>
            </div>
        </body>
        </html>";
    }

    /**
     * Generate text version for account approved email
     */
    private function generateAccountApprovedText(string $userName, string $userRole): string
    {
        return "¡Felicidades, {$userName}!

Nos complace informarte que tu solicitud de registro en la Casa del Profesor Universitario ha sido aprobada por la administración.

🎉 ¡Tu cuenta está activa!
Rol asignado: " . ucfirst($userRole) . "

¿Qué puedes hacer ahora?
✅ Iniciar sesión en el sistema con tu email y contraseña
✅ Explorar las funcionalidades disponibles según tu rol
✅ Acceder a información sobre áreas, servicios y actividades
" . ($userRole === 'profesor' ? "✅ Invitar familiares y amigos al sistema\n✅ Solicitar reservas de áreas e instalaciones\n" : "") . "
" . ($userRole === 'estudiante' ? "✅ Solicitar reservas de áreas e instalaciones\n" : "") . "

Próximos pasos recomendados:
1. Inicia sesión en el sistema
2. Completa tu perfil si es necesario
3. Explora las funcionalidades disponibles
4. Consulta el reglamento de uso

Si tienes alguna pregunta o necesitas ayuda, no dudes en contactar a la administración.

¡Bienvenido a la Casa del Profesor Universitario!

Equipo de Administración
Casa del Profesor Universitario - UNET

---
Este correo fue enviado automáticamente por el sistema de la Casa del Profesor Universitario.
Universidad Nacional Experimental del Táchira (UNET)
Si tienes alguna consulta, contacta a la administración.";
    }

    /**
     * Generate HTML version for account rejected email
     */
    private function generateAccountRejectedHtml(string $userName): string
    {
        return "
        <!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Solicitud de registro - Casa del Profesor Universitario</title>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f4f4f4; }
                .container { background-color: #ffffff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .header { text-align: center; border-bottom: 3px solid #2c5aa0; padding-bottom: 20px; margin-bottom: 30px; }
                .logo { font-size: 24px; font-weight: bold; color: #2c5aa0; margin-bottom: 10px; }
                .subtitle { color: #666; font-size: 14px; }
                .highlight { background-color: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 20px 0; border-radius: 4px; }
                .footer { border-top: 1px solid #eee; padding-top: 20px; text-align: center; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <div class='logo'>🏛️ Casa del Profesor Universitario</div>
                    <div class='subtitle'>Universidad Nacional Experimental del Táchira</div>
                </div>
                
                <h2>Estimado/a {$userName},</h2>
                
                <p>Lamentamos informarte que tu solicitud de registro en la <strong>Casa del Profesor Universitario</strong> no ha sido aprobada en esta ocasión.</p>
                
                <div class='highlight'>
                    <h3>📋 Estado de tu solicitud</h3>
                    <p><strong>Resultado:</strong> No aprobada</p>
                    <p><strong>Fecha de revisión:</strong> " . now()->format('d/m/Y') . "</p>
                </div>
                
                <p>Si tienes alguna pregunta sobre esta decisión o deseas obtener más información sobre los criterios de admisión, te invitamos a contactar directamente con la administración de la Casa del Profesor Universitario.</p>
                
                <p>Agradecemos tu interés en formar parte de nuestra comunidad universitaria.</p>
                
                <p><strong>Equipo de Administración</strong><br>
                Casa del Profesor Universitario - UNET</p>
                
                <div class='footer'>
                    <p>Este correo fue enviado automáticamente por el sistema de la Casa del Profesor Universitario.</p>
                    <p>Universidad Nacional Experimental del Táchira (UNET)</p>
                    <p>Para consultas, contacta a la administración.</p>
                </div>
            </div>
        </body>
        </html>";
    }

    /**
     * Generate text version for account rejected email
     */
    private function generateAccountRejectedText(string $userName): string
    {
        return "Estimado/a {$userName},

Lamentamos informarte que tu solicitud de registro en la Casa del Profesor Universitario no ha sido aprobada en esta ocasión.

📋 Estado de tu solicitud:
• Resultado: No aprobada
• Fecha de revisión: " . now()->format('d/m/Y') . "

Si tienes alguna pregunta sobre esta decisión o deseas obtener más información sobre los criterios de admisión, te invitamos a contactar directamente con la administración de la Casa del Profesor Universitario.

Agradecemos tu interés en formar parte de nuestra comunidad universitaria.

Equipo de Administración
Casa del Profesor Universitario - UNET

---
Este correo fue enviado automáticamente por el sistema de la Casa del Profesor Universitario.
Universidad Nacional Experimental del Táchira (UNET)
Para consultas, contacta a la administración.";
    }

    /**
     * Generate HTML for invitation approved email
     */
    private function generateInvitationApprovedHtml(string $userName, string $authCode): string
    {
        $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
        $setPasswordUrl = $frontendUrl . '/set-password?code=' . $authCode;
        
        return "
        <!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Tu cuenta en la Casa del Profesor Universitario está lista</title>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f4f4f4; }
                .container { background-color: #ffffff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .header { text-align: center; border-bottom: 3px solid #2c5aa0; padding-bottom: 20px; margin-bottom: 30px; }
                .logo { font-size: 24px; font-weight: bold; color: #2c5aa0; margin-bottom: 10px; }
                .subtitle { color: #666; font-size: 14px; }
                .highlight { background-color: #f8f9fa; padding: 15px; border-left: 4px solid #2c5aa0; margin: 20px 0; }
                .button { display: inline-block; background-color: #2c5aa0; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 20px 0; }
                .button:hover { background-color: #1e3d6f; }
                .footer { border-top: 1px solid #eee; padding-top: 20px; text-align: center; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <div class='logo'>🏛️ Casa del Profesor Universitario</div>
                    <div class='subtitle'>Universidad Nacional Experimental del Táchira</div>
                </div>
                
                <h2>¡Hola, {$userName}!</h2>
                
                <p>Has sido <strong>invitado</strong> a formar parte de la <strong>Casa del Profesor Universitario</strong> de la Universidad Nacional Experimental del Táchira.</p>
                
                <div class='highlight'>
                    <h3>🎉 ¡Tu cuenta está lista!</h3>
                    <p><strong>Rol:</strong> Invitado</p>
                </div>
                
                <h3>Para completar tu registro:</h3>
                <p>Necesitas establecer una contraseña para poder acceder al sistema. Haz clic en el botón de abajo para continuar:</p>
                
                <div class='highlight'>
                    <h4>🔗 Enlace para establecer contraseña:</h4>
                    <p style='word-break: break-all; background-color: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; font-size: 12px;'>{$setPasswordUrl}</p>
                    <p><small>Si el botón no funciona, copia y pega este enlace en tu navegador.</small></p>
                </div>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$setPasswordUrl}' target='_blank' class='button'>Establecer Contraseña</a>
                </div>
                
                <div class='highlight'>
                    <h4>⚠️ Importante:</h4>
                    <ul>
                        <li>Este enlace es válido por <strong>30 días</strong></li>
                        <li>Una vez que establezcas tu contraseña, podrás iniciar sesión normalmente</li>
                        <li>Si no completas el proceso, tu cuenta será desactivada</li>
                    </ul>
                </div>
                
                <h3>¿Qué es la Casa del Profesor Universitario?</h3>
                <p>Es un espacio dedicado a la comunidad universitaria de la UNET, donde puedes:</p>
                <ul>
                    <li>🏊‍♂️ <strong>Disfrutar de la piscina</strong> y áreas recreativas</li>
                    <li>🏃‍♂️ <strong>Acceder a instalaciones deportivas</strong></li>
                    <li>🍽️ <strong>Utilizar el restaurante</strong> y áreas sociales</li>
                    <li>👨‍👩‍👧‍👦 <strong>Participar en actividades familiares</strong></li>
                    <li>📚 <strong>Acceder a información</strong> sobre servicios y eventos</li>
                </ul>
                
                <h3>¿Necesitas ayuda?</h3>
                <p>Si tienes problemas para establecer tu contraseña o tienes alguna pregunta, contacta a la administración.</p>
                
                <p>¡Esperamos verte pronto en la Casa del Profesor Universitario!</p>
                
                <p><strong>Equipo de Administración</strong><br>
                Casa del Profesor Universitario - UNET</p>
                
                <div class='footer'>
                    <p>Este correo fue enviado automáticamente por el sistema de la Casa del Profesor Universitario.</p>
                    <p>Universidad Nacional Experimental del Táchira (UNET)</p>
                    <p>Si tienes alguna consulta, contacta a la administración.</p>
                </div>
            </div>
        </body>
        </html>";
    }

    /**
     * Generate text version for invitation approved email
     */
    private function generateInvitationApprovedText(string $userName, string $authCode): string
    {
        $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
        $setPasswordUrl = $frontendUrl . '/set-password?code=' . $authCode;
        
        return "¡Hola, {$userName}!

Has sido invitado a formar parte de la Casa del Profesor Universitario de la Universidad Nacional Experimental del Táchira.

🎉 ¡Tu cuenta está lista!
Rol: Invitado

Para completar tu registro:
Necesitas establecer una contraseña para poder acceder al sistema. Visita el siguiente enlace:

{$setPasswordUrl}

⚠️ Importante:
- Este enlace es válido por 7 días
- Una vez que establezcas tu contraseña, podrás iniciar sesión normalmente
- Si no completas el proceso, tu cuenta será desactivada

¿Qué es la Casa del Profesor Universitario?
Es un espacio dedicado a la comunidad universitaria de la UNET, donde puedes:
🏊‍♂️ Disfrutar de la piscina y áreas recreativas
🏃‍♂️ Acceder a instalaciones deportivas
🍽️ Utilizar el restaurante y áreas sociales
👨‍👩‍👧‍👦 Participar en actividades familiares
📚 Acceder a información sobre servicios y eventos

¿Necesitas ayuda?
Si tienes problemas para establecer tu contraseña o tienes alguna pregunta, contacta a la administración.

¡Esperamos verte pronto en la Casa del Profesor Universitario!

Equipo de Administración
Casa del Profesor Universitario - UNET

---
Este correo fue enviado automáticamente por el sistema de la Casa del Profesor Universitario.
Universidad Nacional Experimental del Táchira (UNET)
Si tienes alguna consulta, contacta a la administración.";
    }

    /**
     * Send password reset code email
     */
    public function sendPasswordResetCodeEmail(string $userEmail, string $userName, string $code): array
    {
        $subject = 'Código de recuperación de contraseña - Casa del Profesor Universitario';
        $html = $this->generatePasswordResetCodeHtml($userName, $code);
        $text = $this->generatePasswordResetCodeText($userName, $code);

        return $this->sendBasic(
            [['email' => $userEmail, 'name' => $userName]],
            $subject,
            $html,
            $text
        );
    }

    /**
     * Generate HTML for password reset code email
     */
    private function generatePasswordResetCodeHtml(string $userName, string $code): string
    {
        return "
        <!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Código de recuperación de contraseña</title>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f4f4f4; }
                .container { background-color: #ffffff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .header { text-align: center; border-bottom: 3px solid #2c5aa0; padding-bottom: 20px; margin-bottom: 30px; }
                .logo { font-size: 24px; font-weight: bold; color: #2c5aa0; margin-bottom: 10px; }
                .subtitle { color: #666; font-size: 14px; }
                .code-container { text-align: center; margin: 30px 0; }
                .code { font-size: 36px; font-weight: bold; color: #2c5aa0; background-color: #f8f9fa; padding: 20px; border-radius: 10px; border: 2px dashed #2c5aa0; letter-spacing: 8px; font-family: 'Courier New', monospace; }
                .highlight { background-color: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 20px 0; border-radius: 4px; }
                .warning { background-color: #f8d7da; padding: 15px; border-left: 4px solid #dc3545; margin: 20px 0; border-radius: 4px; }
                .footer { border-top: 1px solid #eee; padding-top: 20px; text-align: center; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <div class='logo'>🏛️ Casa del Profesor Universitario</div>
                    <div class='subtitle'>Universidad Nacional Experimental del Táchira</div>
                </div>
                
                <h2>Hola, {$userName}</h2>
                
                <p>Hemos recibido una solicitud para restablecer la contraseña de tu cuenta en la <strong>Casa del Profesor Universitario</strong>.</p>
                
                <div class='code-container'>
                    <h3>Tu código de recuperación es:</h3>
                    <div class='code'>{$code}</div>
                </div>
                
                <div class='highlight'>
                    <h4>⏰ Importante:</h4>
                    <ul>
                        <li>Este código es válido por <strong>15 minutos</strong></li>
                        <li>Úsalo para restablecer tu contraseña</li>
                        <li>No compartas este código con nadie</li>
                    </ul>
                </div>
                
                <div class='warning'>
                    <h4>⚠️ Si no solicitaste este cambio:</h4>
                    <p>Si no fuiste tú quien solicitó el restablecimiento de contraseña, puedes ignorar este correo de forma segura. Tu cuenta permanecerá protegida.</p>
                </div>
                
                <h3>¿Cómo usar este código?</h3>
                <ol>
                    <li>Ve a la página de restablecimiento de contraseña</li>
                    <li>Ingresa tu email y este código</li>
                    <li>Crea una nueva contraseña segura</li>
                    <li>Inicia sesión con tu nueva contraseña</li>
                </ol>
                
                <p>Si tienes problemas o necesitas ayuda, contacta a la administración de la Casa del Profesor Universitario.</p>
                
                <p><strong>Equipo de Administración</strong><br>
                Casa del Profesor Universitario - UNET</p>
                
                <div class='footer'>
                    <p>Este correo fue enviado automáticamente por el sistema de la Casa del Profesor Universitario.</p>
                    <p>Universidad Nacional Experimental del Táchira (UNET)</p>
                    <p>Para consultas, contacta a la administración.</p>
                </div>
            </div>
        </body>
        </html>";
    }

    /**
     * Generate text version for password reset code email
     */
    private function generatePasswordResetCodeText(string $userName, string $code): string
    {
        return "Hola, {$userName},

Hemos recibido una solicitud para restablecer la contraseña de tu cuenta en la Casa del Profesor Universitario.

Tu código de recuperación es: {$code}

⏰ Importante:
- Este código es válido por 15 minutos
- Úsalo para restablecer tu contraseña
- No compartas este código con nadie

⚠️ Si no solicitaste este cambio:
Si no fuiste tú quien solicitó el restablecimiento de contraseña, puedes ignorar este correo de forma segura. Tu cuenta permanecerá protegida.

¿Cómo usar este código?
1. Ve a la página de restablecimiento de contraseña
2. Ingresa tu email y este código
3. Crea una nueva contraseña segura
4. Inicia sesión con tu nueva contraseña

Si tienes problemas o necesitas ayuda, contacta a la administración de la Casa del Profesor Universitario.

Equipo de Administración
Casa del Profesor Universitario - UNET

---
Este correo fue enviado automáticamente por el sistema de la Casa del Profesor Universitario.
Universidad Nacional Experimental del Táchira (UNET)
Para consultas, contacta a la administración.";
    }
}
