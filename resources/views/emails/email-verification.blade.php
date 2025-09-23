<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación de Correo Electrónico</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #1e40af;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background-color: #f8fafc;
            padding: 30px;
            border-radius: 0 0 8px 8px;
        }
        .button {
            display: inline-block;
            background-color: #1e40af;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Casa del Profesor Universitario UNET</h1>
    </div>
    
    <div class="content">
        <h2>¡Bienvenido, {{ $user->name }}!</h2>
        
        <p>Gracias por registrarte en el sistema de gestión de la Casa del Profesor Universitario de la UNET.</p>
        
        <p>Para completar tu registro y activar tu cuenta, por favor verifica tu dirección de correo electrónico haciendo clic en el siguiente enlace:</p>
        
        <div style="text-align: center;">
            <a href="{{ $verificationUrl }}" class="button">Verificar Correo Electrónico</a>
        </div>
        
        <p>Si el botón no funciona, puedes copiar y pegar el siguiente enlace en tu navegador:</p>
        <p style="word-break: break-all; background-color: #e5e7eb; padding: 10px; border-radius: 4px;">
            {{ $verificationUrl }}
        </p>
        
        <p><strong>Importante:</strong> Este enlace expirará en 24 horas por motivos de seguridad.</p>
        
        <p>Si no solicitaste este registro, puedes ignorar este correo.</p>
        
        <p>Saludos cordiales,<br>
        <strong>Equipo de la Casa del Profesor UNET</strong></p>
    </div>
    
    <div class="footer">
        <p>Este es un correo automático, por favor no respondas a este mensaje.</p>
        <p>Universidad Nacional Experimental del Táchira (UNET)</p>
    </div>
</body>
</html>
