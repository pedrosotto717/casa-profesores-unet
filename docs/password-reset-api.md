# API de Recuperación de Contraseña - CPU UNET

## Resumen

Este documento describe los endpoints de recuperación de contraseña implementados en el sistema de la Casa del Profesor Universitario. El sistema utiliza códigos de 6 dígitos enviados por email para permitir a los usuarios restablecer sus contraseñas de forma segura.

## Endpoints

### 1. Solicitar Código de Recuperación

**Endpoint:** `POST /api/v1/auth/forgot-password`

**Descripción:** Solicita un código de recuperación de 6 dígitos que será enviado al email del usuario.

**Autenticación:** No requerida (endpoint público)

**Body:**
```json
{
  "email": "usuario@unet.edu.ve"
}
```

**Validaciones:**
- Email requerido y con formato válido
- Throttling: máximo 3 solicitudes por hora por IP
- Cooldown: 1 minuto entre solicitudes por email

**Respuesta Exitosa (200):**
```json
{
  "success": true,
  "message": "Si el email existe en nuestro sistema, hemos enviado un código de recuperación.",
  "meta": {
    "version": "v1"
  }
}
```

**Respuesta de Error (422):**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": [
      "Demasiados intentos. Intenta de nuevo en 1800 segundos."
    ]
  }
}
```

### 2. Restablecer Contraseña

**Endpoint:** `POST /api/v1/auth/reset-password`

**Descripción:** Restablece la contraseña del usuario usando el código de verificación enviado por email.

**Autenticación:** No requerida (endpoint público)

**Body:**
```json
{
  "email": "usuario@unet.edu.ve",
  "code": "123456",
  "password": "nueva_contraseña_segura",
  "password_confirmation": "nueva_contraseña_segura"
}
```

**Validaciones:**
- Email requerido y con formato válido
- Código requerido, exactamente 6 dígitos numéricos
- Contraseña requerida, mínimo 8 caracteres
- Confirmación de contraseña debe coincidir
- Código debe ser válido y no expirado
- Máximo 5 intentos por código

**Respuesta Exitosa (200):**
```json
{
  "success": true,
  "message": "Contraseña restablecida exitosamente. Inicia sesión con tu nueva contraseña.",
  "meta": {
    "version": "v1"
  }
}
```

**Respuesta de Error (400):**
```json
{
  "success": false,
  "message": "El código de verificación es inválido."
}
```

**Respuesta de Error (422):**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "code": [
      "Código incorrecto. Te quedan 3 intentos."
    ]
  }
}
```

## Características de Seguridad

### Códigos de Verificación
- **Formato:** 6 dígitos numéricos (000000-999999)
- **Almacenamiento:** Hash SHA-256 en base de datos
- **TTL:** 15 minutos de validez
- **Intentos:** Máximo 5 intentos por código

### Throttling y Rate Limiting
- **Por Email:** 1 solicitud por minuto
- **Por IP:** 3 solicitudes por hora
- **Cooldown:** 60 segundos entre solicitudes del mismo email

### Validaciones de Usuario
- Usuarios con estado `rechazado` no pueden restablecer contraseña
- Se revocan todos los tokens activos al restablecer contraseña
- Respuestas genéricas para no revelar existencia de emails

### Auditoría
Se registran los siguientes eventos en `audit_logs`:
- `password_reset_requested`: Solicitud de código de recuperación
- `password_reset_completed`: Restablecimiento exitoso de contraseña

## Flujo de Uso

1. **Usuario solicita código:**
   ```bash
   curl -X POST http://localhost:8000/api/v1/auth/forgot-password \
     -H "Content-Type: application/json" \
     -d '{"email": "usuario@unet.edu.ve"}'
   ```

2. **Usuario recibe email con código de 6 dígitos**

3. **Usuario restablece contraseña:**
   ```bash
   curl -X POST http://localhost:8000/api/v1/auth/reset-password \
     -H "Content-Type: application/json" \
     -d '{
       "email": "usuario@unet.edu.ve",
       "code": "123456",
       "password": "nueva_contraseña",
       "password_confirmation": "nueva_contraseña"
     }'
   ```

4. **Usuario inicia sesión con nueva contraseña**

## Emails

### Template de Código de Recuperación
- **Asunto:** "Código de recuperación de contraseña - Casa del Profesor Universitario"
- **Contenido:** Código destacado, instrucciones de uso, advertencias de seguridad
- **Validez:** 15 minutos
- **Branding:** Institucional con logo y colores de la UNET

## Consideraciones Técnicas

### Base de Datos
- Se reutilizan los campos `auth_code` y `auth_code_expires_at` existentes
- Se agregaron campos `auth_code_attempts` y `last_code_sent_at`
- Los códigos se almacenan hasheados para seguridad

### Logging
- Se registran todos los intentos de recuperación
- Se logean errores de envío de email
- Se registran eventos de auditoría completos

### Manejo de Errores
- Respuestas genéricas para no revelar información sensible
- Logging detallado para debugging
- Transacciones de base de datos para consistencia

## Testing

### Casos de Prueba Recomendados
1. Solicitar código con email válido → 200 + email enviado
2. Solicitar código con email inexistente → 200 (mismo mensaje)
3. Restablecer con código correcto → 200 + contraseña cambiada
4. Restablecer con código incorrecto → 422 + contador de intentos
5. Restablecer con código expirado → 422 + mensaje de expiración
6. Exceder límite de intentos → 422 + código invalidado
7. Throttling funcionando → 422 + mensaje de rate limit
8. Usuario rechazado → 422 + mensaje de cuenta rechazada

### Datos de Prueba
```json
{
  "email": "test@unet.edu.ve",
  "code": "123456",
  "password": "password123",
  "password_confirmation": "password123"
}
```

## Integración con Frontend

### Página de Solicitud de Código
- Formulario simple con campo email
- Indicador de loading durante solicitud
- Mensaje de éxito genérico
- Manejo de errores de validación

### Página de Restablecimiento
- Formulario con email, código y nueva contraseña
- Contador de intentos restantes
- Validación en tiempo real
- Redirección a login tras éxito

## Monitoreo y Alertas

### Métricas Importantes
- Tasa de éxito de solicitudes de código
- Tasa de éxito de restablecimientos
- Número de intentos fallidos por usuario
- Tiempo promedio de uso de códigos

### Alertas Recomendadas
- Alto número de intentos fallidos
- Errores de envío de email
- Patrones de abuso o ataques
- Fallos en el sistema de auditoría
