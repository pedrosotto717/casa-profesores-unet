# Sistema de Notificaciones y Correos Electrónicos

## Resumen

Este documento describe el sistema completo de notificaciones in-sistema y el plan para implementar el envío de correos electrónicos en el sistema de gestión de la Casa del Profesor Universitario.

## Sistema de Notificaciones In-Sistema

### Arquitectura

El sistema utiliza la tabla `notifications` para almacenar notificaciones que pueden ser dirigidas a:
- **Usuarios específicos** (`target_type = 'user'`, `target_id = user_id`)
- **Roles específicos** (`target_type = 'role'`, `target_id = role_name`)

### Flujos de Notificación Implementados

#### 1. Sistema de Invitaciones
- ✅ **Nueva invitación creada**: Notifica a todos los administradores
- ✅ **Invitación aprobada**: Notifica al usuario que envió la invitación
- ✅ **Invitación rechazada**: Notifica al usuario que envió la invitación con razón

#### 2. Sistema de Auto-Registro
- ✅ **Nueva solicitud de registro**: Notifica a todos los administradores
- ✅ **Usuario aprobado**: Notifica al usuario que su cuenta fue aprobada

### Tipos de Notificación

| Tipo | Descripción | Datos Adicionales |
|------|-------------|-------------------|
| `invitation_pending` | Nueva invitación pendiente | `invitation_id`, `inviter_name`, `invitee_email`, `invitee_name` |
| `invitation_approved` | Invitación aprobada | `invitee_name`, `invitee_email` |
| `invitation_rejected` | Invitación rechazada | `invitee_name`, `invitee_email`, `rejection_reason` |
| `registration_pending` | Nueva solicitud de registro | `user_id`, `user_name`, `user_email`, `aspired_role`, `responsible_email` |
| `user_approved` | Usuario aprobado | `user_name`, `user_role` |

## Plan de Implementación de Correos Electrónicos

### TODOs Identificados

#### 1. Flujo de Auto-Registro
**Ubicación**: `app/Services/UserService.php` - método `register()`
```php
// TODO: Send email to the user confirming registration and explaining approval process
// This should be implemented in a future iteration
```
**Contenido del correo**:
- Confirmación de registro exitoso
- Explicación del proceso de aprobación
- Tiempo estimado de respuesta
- Información de contacto para consultas

#### 2. Flujo de Invitaciones - Creación
**Ubicación**: `app/Services/InvitationService.php` - método `createInvitation()`
```php
// TODO: Send email to the person being invited with invitation details and link to accept
// This should be implemented in a future iteration
// Email should include: inviter name, invitation message, acceptance link, expiration date
```
**Contenido del correo**:
- Nombre del invitador
- Mensaje personalizado de invitación
- Enlace para aceptar la invitación
- Fecha de expiración (30 días)
- Información sobre la institución

#### 3. Flujo de Invitaciones - Aprobación
**Ubicación**: `app/Services/InvitationService.php` - método `approveInvitation()`
```php
// TODO: Send email to the person being invited notifying them of account creation
// This should be implemented in a future iteration
// Email should include: account details, login instructions, system welcome message
```
**Contenido del correo**:
- Confirmación de creación de cuenta
- Detalles de la cuenta (email, rol)
- Instrucciones de inicio de sesión
- Mensaje de bienvenida al sistema
- Enlaces útiles y recursos

#### 4. Flujo de Auto-Registro - Aprobación
**Ubicación**: `app/Services/UserService.php` - método `handleStatusChangeNotifications()`
```php
// TODO: Send email to the user notifying them of approval
// This should be implemented in a future iteration
```
**Contenido del correo**:
- Felicitaciones por la aprobación
- Detalles del rol asignado
- Instrucciones de inicio de sesión
- Información sobre funcionalidades disponibles
- Próximos pasos recomendados

### Estructura de Implementación Sugerida

#### 1. Mailable Classes
Crear clases Mailable para cada tipo de correo:
- `RegistrationConfirmationMail`
- `InvitationMail`
- `AccountCreatedMail`
- `AccountApprovedMail`

#### 2. Email Templates
Crear templates Blade en `resources/views/emails/`:
- `registration-confirmation.blade.php`
- `invitation.blade.php`
- `account-created.blade.php`
- `account-approved.blade.php`

#### 3. Configuración de Cola
Implementar envío asíncrono usando Laravel Queue para:
- Mejorar rendimiento
- Manejar fallos de envío
- Reintentos automáticos

#### 4. Logging y Monitoreo
- Registrar intentos de envío
- Monitorear tasas de entrega
- Alertas por fallos recurrentes

### Consideraciones Técnicas

#### 1. Configuración de Email
- Usar Resend como proveedor principal
- Configurar templates HTML responsivos
- Incluir versión de texto plano

#### 2. Personalización
- Usar datos del usuario para personalizar mensajes
- Incluir branding institucional
- Mantener tono profesional y acogedor

#### 3. Seguridad
- Validar todos los datos antes del envío
- No incluir información sensible en correos
- Usar tokens seguros para enlaces

#### 4. Cumplimiento
- Incluir opción de desuscripción
- Cumplir con regulaciones de privacidad
- Mantener registro de consentimientos

## Estado Actual

### ✅ Implementado
- Sistema completo de notificaciones in-sistema
- Notificaciones para todos los flujos de usuario
- API endpoints para gestión de notificaciones
- Audit logging para todas las acciones

### 🔄 Pendiente de Implementación
- Envío de correos electrónicos
- Templates de correo
- Sistema de colas para envío asíncrono
- Monitoreo y logging de correos

## Próximos Pasos

1. **Fase 1**: Implementar Mailable classes básicas
2. **Fase 2**: Crear templates de correo con branding institucional
3. **Fase 3**: Configurar sistema de colas
4. **Fase 4**: Implementar monitoreo y logging
5. **Fase 5**: Testing y optimización

## Referencias

- [Laravel Mail Documentation](https://laravel.com/docs/mail)
- [Resend Documentation](https://resend.com/docs)
- [Laravel Queue Documentation](https://laravel.com/docs/queues)
