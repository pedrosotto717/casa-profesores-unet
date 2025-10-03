# Comando para Cambiar Estatus de Usuario

## Descripción

El comando `user:change-status` permite a los administradores cambiar el estatus de un usuario utilizando únicamente su dirección de correo electrónico y el nuevo estatus deseado.

## Sintaxis

```bash
php artisan user:change-status <email> <status> [--admin-email=ADMIN_EMAIL]
```

## Parámetros

### Argumentos Requeridos

- **`email`**: La dirección de correo electrónico del usuario a actualizar
- **`status`**: El nuevo estatus del usuario. Valores válidos:
  - `aprobacion_pendiente`: Usuario pendiente de aprobación
  - `solvente`: Usuario activo y al día con aportes
  - `insolvente`: Usuario activo pero no al día con aportes

### Opciones

- **`--admin-email`**: (Opcional) Email del administrador que realiza la acción para auditoría

## Ejemplos de Uso

### 1. Cambiar estatus básico
```bash
php artisan user:change-status juan.perez@unet.edu.ve solvente
```

### 2. Cambiar estatus con admin específico
```bash
php artisan user:change-status maria.garcia@unet.edu.ve insolvente --admin-email=admin@unet.edu.ve
```

### 3. Aprobar usuario pendiente
```bash
php artisan user:change-status estudiante@unet.edu.ve solvente
```

## Funcionalidades

### ✅ Validación Automática
- Verifica que el email del usuario exista en el sistema
- Valida que el estatus sea uno de los valores permitidos
- Confirma la acción antes de ejecutarla

### ✅ Auto-Aprobación de Roles
Cuando se cambia el estatus de un usuario de `aprobacion_pendiente` a `solvente` o `insolvente`, el sistema automáticamente:
- Promueve al usuario a su rol aspirado (`aspired_role`)
- Limpia el campo `aspired_role`
- Registra la acción en auditoría

### ✅ Auditoría Completa
- Registra todos los cambios en `audit_logs`
- Incluye información del administrador que realiza la acción
- Mantiene trazabilidad completa de cambios de estatus

### ✅ Transacciones de Base de Datos
- Utiliza transacciones para garantizar integridad de datos
- Rollback automático en caso de errores

## Flujo de Ejecución

1. **Validación**: Verifica email y estatus
2. **Búsqueda**: Localiza el usuario en la base de datos
3. **Confirmación**: Solicita confirmación del administrador
4. **Actualización**: Utiliza `UserService` para el cambio
5. **Auditoría**: Registra la acción en logs
6. **Notificación**: Muestra resultado de la operación

## Casos de Uso Comunes

### Aprobar Registro de Estudiante
```bash
# Usuario se registró como estudiante y está pendiente
php artisan user:change-status estudiante@unet.edu.ve solvente
# Resultado: Usuario promovido a rol 'estudiante' automáticamente
```

### Aprobar Registro de Profesor
```bash
# Usuario se registró como profesor y está pendiente
php artisan user:change-status profesor@unet.edu.ve insolvente
# Resultado: Usuario promovido a rol 'profesor' automáticamente
```

### Cambiar Solvencia
```bash
# Usuario solvente que no ha pagado aportes
php artisan user:change-status usuario@unet.edu.ve insolvente
```

### Reactivar Usuario
```bash
# Usuario que pagó sus aportes
php artisan user:change-status usuario@unet.edu.ve solvente
```

## Códigos de Salida

- **0 (SUCCESS)**: Operación completada exitosamente
- **1 (FAILURE)**: Error en la operación (usuario no encontrado, validación fallida, etc.)

## Integración con el Sistema

El comando utiliza el `UserService` existente, por lo que:
- Mantiene consistencia con la lógica de negocio
- Aprovecha todas las validaciones y reglas implementadas
- Genera notificaciones automáticas cuando corresponde
- Respeta las políticas de auditoría del sistema

## Seguridad

- Requiere confirmación explícita del administrador
- Registra la acción en auditoría con información del admin
- Utiliza transacciones para prevenir estados inconsistentes
- Valida todos los parámetros de entrada

## Troubleshooting

### Usuario no encontrado
```
❌ User with email 'usuario@example.com' not found.
```
**Solución**: Verificar que el email esté correctamente escrito y que el usuario exista en el sistema.

### Estatus inválido
```
❌ Validation error: Invalid status. Valid options are: aprobacion_pendiente, solvente, insolvente
```
**Solución**: Usar uno de los estatus válidos listados en el mensaje de error.

### Error de base de datos
```
❌ An error occurred: [mensaje de error específico]
```
**Solución**: Revisar logs de Laravel para detalles específicos del error.
