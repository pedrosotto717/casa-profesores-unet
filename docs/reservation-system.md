# Sistema de Reservas - CPU UNET

## Resumen Ejecutivo

Este documento describe la implementación completa del sistema de reservas para la Casa del Profesor Universitario (CPU) de la UNET. El sistema permite a usuarios autorizados reservar áreas específicas con un flujo de aprobación administrativa y gestión de conflictos con academias.

## Arquitectura del Sistema

### Conceptos Clave

- **Áreas Reservables**: Solo las áreas marcadas con `is_reservable = true` pueden ser reservadas
- **Estados de Reserva**: `pendiente`, `aprobada`, `rechazada`, `cancelada`, `completada`, `expirada`
- **Intervalos Semi-Abiertos**: Las reservas usan intervalos `[start, end)` para evitar conflictos en los bordes
- **Anticolisión**: Sistema que previene solapamientos con reservas aprobadas y sesiones de academias

### Roles y Permisos

| Rol | Puede Reservar | Requisitos |
|-----|----------------|------------|
| `profesor` | ✅ | Status `solvente` |
| `estudiante` | ✅ | Status `solvente` + profesor responsable activo |
| `invitado` | ✅ | Status `solvente` + profesor responsable activo |
| `administrador` | ✅ | Status `solvente` |
| `instructor` | ❌ | Solo gestiona su academia |
| `obrero` | ❌ | Acceso operativo limitado |
| `usuario` | ❌ | Estado transitorio |

## Estructura de Base de Datos

### Tabla `reservations`

```sql
CREATE TABLE reservations (
    id BIGINT PRIMARY KEY,
    requester_id BIGINT, -- Usuario que solicita la reserva
    area_id BIGINT, -- Área a reservar
    starts_at TIMESTAMP, -- Inicio de la reserva
    ends_at TIMESTAMP, -- Fin de la reserva
    status ENUM('pendiente', 'aprobada', 'rechazada', 'cancelada', 'completada', 'expirada') DEFAULT 'pendiente',
    title VARCHAR(180) NULL, -- Título/resumen del evento
    notes TEXT NULL, -- Descripción detallada
    decision_reason TEXT NULL, -- Razón del rechazo/aprobación
    approved_by BIGINT NULL, -- Admin que revisó la reserva
    reviewed_at TIMESTAMP NULL, -- Fecha de revisión
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL, -- Soft deletes
    
    -- Índices para performance
    INDEX idx_area_starts (area_id, starts_at, ends_at),
    INDEX idx_requester (requester_id)
);
```

## API Endpoints

### 1. Crear Reserva

**POST** `/api/v1/reservations`

**Autenticación**: Requerida (roles: profesor, estudiante, invitado, administrador con status solvente)

**Payload**:
```json
{
  "area_id": 1,
  "starts_at": "2024-02-15T10:00:00Z",
  "ends_at": "2024-02-15T12:00:00Z",
  "title": "Reunión de trabajo",
  "notes": "Reunión semanal del equipo de desarrollo"
}
```

**Validaciones**:
- Área debe ser reservable (`is_reservable = true`)
- Usuario debe tener rol permitido (profesor, estudiante, invitado o administrador) y status `solvente`
- Para estudiantes e invitados: profesor responsable debe existir y estar activo
- Ventanas de tiempo: anticipación mínima, horizonte máximo, duración máxima
- Anticolisión: no puede solaparse con reservas aprobadas o sesiones de academias

**Respuestas**:
- `201 Created`: Reserva creada exitosamente
- `400/422`: Errores de validación
- `403`: Permisos insuficientes o usuario insolvente
- `404`: Área no encontrada
- `409`: Conflicto de horarios

### 2. Listar Reservas

**GET** `/api/v1/reservations`

**Autenticación**: Requerida
- **Usuarios**: Ven solo sus propias reservas
- **Administradores**: Ven todas las reservas con filtros

**Query Parameters** (solo para administradores):
- `user_id` (int): Filtrar por usuario
- `area_id` (int): Filtrar por área
- `status` (string): Filtrar por estado
- `from` (datetime): Fecha de inicio del rango
- `to` (datetime): Fecha de fin del rango
- `search` (string): Búsqueda por nombre de usuario o área
- `page` (int): Página para paginación
- `per_page` (int): Elementos por página

**Nota**: Los usuarios no administradores solo pueden ver sus propias reservas y no pueden usar filtros adicionales.

**Respuesta**:
```json
{
  "data": [
    {
      "id": 1,
      "user": {
        "id": 1,
        "name": "Juan Pérez",
        "email": "juan.perez@unet.edu.ve",
        "role": "profesor",
        "role_label": "Profesor"
      },
      "area": {
        "id": 1,
        "name": "Piscina",
        "capacity": 20,
        "is_reservable": true
      },
      "starts_at": "2024-02-15T10:00:00Z",
      "ends_at": "2024-02-15T12:00:00Z",
      "status": "pendiente",
      "status_label": "Pendiente",
      "note": "Reunión de trabajo",
      "duration_minutes": 120,
      "can_be_canceled": true,
      "created_at": "2024-02-10T08:00:00Z"
    }
  ],
  "links": { ... },
  "meta": { ... }
}
```

### 3. Actualizar Reserva

**PUT** `/api/v1/reservations/{id}`

**Autenticación**: Requerida (solo el dueño de la reserva)

**Restricciones**: Solo reservas con status `pendiente`

**Payload**:
```json
{
  "starts_at": "2024-02-15T11:00:00Z",
  "ends_at": "2024-02-15T13:00:00Z",
  "note": "Nota actualizada"
}
```

**Respuestas**:
- `200 OK`: Reserva actualizada
- `403`: No es el dueño o reserva no pendiente
- `409`: Conflicto de horarios

### 4. Cancelar Reserva

**POST** `/api/v1/reservations/{id}/cancel`

**Autenticación**: Requerida (dueño o administrador)

**Payload**:
```json
{
  "reason": "Cambio de planes"
}
```

**Reglas**:
- Dueño: puede cancelar `pendiente`; si `aprobada`, solo si faltan ≥ 24 horas
- Administrador: puede cancelar siempre

**Respuestas**:
- `200 OK`: Reserva cancelada
- `403`: No autorizado o no se puede cancelar

### 5. Aprobar Reserva (Admin)

**POST** `/api/v1/reservations/{id}/approve`

**Autenticación**: Requerida (solo administradores)

**Proceso**: Revalida anticolisión en transacción antes de aprobar

**Respuestas**:
- `200 OK`: Reserva aprobada
- `404`: Reserva no encontrada
- `409`: Conflicto detectado durante revalidación

### 6. Rechazar Reserva (Admin)

**POST** `/api/v1/reservations/{id}/reject`

**Autenticación**: Requerida (solo administradores)

**Payload**:
```json
{
  "reason": "Área no disponible en ese horario"
}
```

**Respuestas**:
- `200 OK`: Reserva rechazada
- `404`: Reserva no encontrada

### 7. Disponibilidad de Área (Público)

**GET** `/api/v1/reservations/availability`

**Autenticación**: No requerida (endpoint público)

**Query Parameters**:
- `area_id` (int, requerido): ID del área
- `from` (date, opcional): Fecha de inicio (default: hoy)
- `to` (date, opcional): Fecha de fin (default: from + 30 días)
- `slot_minutes` (int, opcional): Tamaño de slot en minutos (15-480)

**Respuesta**:
```json
{
  "success": true,
  "data": {
    "area": {
      "id": 1,
      "name": "Piscina",
      "is_reservable": true
    },
    "range": {
      "from": "2024-02-15T00:00:00Z",
      "to": "2024-03-16T00:00:00Z"
    },
    "operating_hours": [
      {
        "date": "2024-02-15",
        "windows": [
          {
            "start": "2024-02-15T06:00:00Z",
            "end": "2024-02-15T22:00:00Z"
          }
        ]
      }
    ],
    "blocks": [
      {
        "start": "2024-02-15T09:00:00Z",
        "end": "2024-02-15T12:00:00Z",
        "kind": "academy",
        "ref": 1
      },
      {
        "start": "2024-02-15T14:00:00Z",
        "end": "2024-02-15T16:00:00Z",
        "kind": "reservation",
        "ref": 5
      }
    ],
    "free": [
      {
        "start": "2024-02-15T06:00:00Z",
        "end": "2024-02-15T09:00:00Z"
      },
      {
        "start": "2024-02-15T12:00:00Z",
        "end": "2024-02-15T14:00:00Z"
      },
      {
        "start": "2024-02-15T16:00:00Z",
        "end": "2024-02-15T22:00:00Z"
      }
    ],
    "generated_at": "2024-02-10T10:30:00Z"
  }
}
```

## Lógica de Negocio

### Validaciones de Tiempo

1. **Anticipación Mínima**: 2 horas por defecto (configurable)
2. **Horizonte Máximo**: 30 días por defecto (configurable)
3. **Duración Máxima**: 8 horas por defecto (configurable)

### Sistema de Anticolisión

El sistema previene conflictos verificando:

1. **Reservas Aprobadas**: Intervalos `[starts_at, ends_at)` que se solapan
2. **Sesiones de Academias**: Horarios de academias en la misma área
3. **Horarios Operativos**: Ventanas de apertura del área

### Cálculo de Disponibilidad

1. **Horarios Operativos**: Se obtienen de `area_schedules` por día de la semana
2. **Bloqueos**: Se combinan reservas aprobadas y sesiones de academias
3. **Slots Libres**: Se calculan restando bloqueos de horarios operativos
4. **Discretización**: Opcional por `slot_minutes` para slots uniformes

### Reglas de Cancelación

- **Reservas Pendientes**: Usuario puede cancelar siempre
- **Reservas Aprobadas**: Usuario puede cancelar solo si faltan ≥ 24 horas
- **Administradores**: Pueden cancelar cualquier reserva en cualquier momento

## Configuración

### Archivo `config/reservations.php`

```php
return [
    'min_advance_hours' => 2,        // Anticipación mínima
    'max_advance_days' => 30,        // Horizonte máximo
    'max_duration_hours' => 8,       // Duración máxima
    'cancel_before_hours' => 24,     // Horas antes para cancelar
    'default_slot_minutes' => 60,    // Slot por defecto
    'min_slot_minutes' => 15,        // Slot mínimo
    'max_slot_minutes' => 480,       // Slot máximo (8 horas)
];
```

### Variables de Entorno

```env
RESERVATION_MIN_ADVANCE_HOURS=2
RESERVATION_MAX_ADVANCE_DAYS=30
RESERVATION_MAX_DURATION_HOURS=8
RESERVATION_CANCEL_BEFORE_HOURS=24
RESERVATION_DEFAULT_SLOT_MINUTES=60
RESERVATION_MIN_SLOT_MINUTES=15
RESERVATION_MAX_SLOT_MINUTES=480
```

## Notificaciones

### Tipos de Notificación

- `reservation_pending`: Nueva solicitud de reserva (para admins)
- `reservation_approved`: Reserva aprobada (para usuario)
- `reservation_rejected`: Reserva rechazada (para usuario)
- `reservation_canceled`: Reserva cancelada (para usuario)

### Flujo de Notificaciones

1. **Creación**: Se notifica a todos los administradores
2. **Aprobación**: Se notifica al usuario solicitante
3. **Rechazo**: Se notifica al usuario con la razón
4. **Cancelación**: Se notifica al usuario si es cancelada por admin

## Auditoría

### Acciones Auditadas

- `reservation_created`: Creación de reserva
- `reservation_updated`: Actualización de reserva
- `reservation_approved`: Aprobación de reserva
- `reservation_rejected`: Rechazo de reserva
- `reservation_canceled`: Cancelación de reserva

### Datos de Auditoría

Cada acción registra:
- Usuario que realizó la acción
- Usuario afectado (dueño de la reserva)
- Datos antes y después del cambio
- Timestamp y metadatos de la petición

## Consideraciones de Performance

### Índices de Base de Datos

- `(area_id, starts_at)`: Para consultas por área y tiempo
- `(user_id, status)`: Para consultas por usuario y estado
- `(status, starts_at)`: Para consultas administrativas
- `(area_id, status, starts_at, ends_at)`: Para validación de conflictos

### Optimizaciones

- **Eager Loading**: Carga de relaciones `user`, `area`, `reviewer`
- **Paginación**: Listas paginadas para grandes volúmenes
- **Cache**: Disponibilidad calculada con cache (futuro)
- **Transacciones**: Operaciones críticas en transacciones DB

## Testing

### Casos de Prueba Críticos

1. **Creación de Reserva**:
   - Usuario con rol permitido y status solvente
   - Área reservable disponible
   - Sin conflictos de horario
   - Ventanas de tiempo válidas

2. **Anticolisión**:
   - Conflicto con reserva aprobada
   - Conflicto con sesión de academia
   - Intervalos que se tocan en los bordes

3. **Cancelación**:
   - Usuario cancela reserva pendiente
   - Usuario cancela reserva aprobada dentro del plazo
   - Admin cancela cualquier reserva

4. **Disponibilidad**:
   - Cálculo correcto de slots libres
   - Combinación de horarios operativos y bloqueos
   - Discretización por slots

## Roadmap y Mejoras Futuras

### Fase 1: Funcionalidades Básicas
- [x] CRUD de reservas
- [x] Sistema de aprobación
- [x] Anticolisión básica
- [x] Disponibilidad pública

### Fase 2: Mejoras de UX
- [ ] Notificaciones por email
- [ ] WebSockets para tiempo real
- [ ] Calendario visual de disponibilidad
- [ ] Recordatorios automáticos

### Fase 3: Funcionalidades Avanzadas
- [ ] Reservas recurrentes
- [ ] Lista de espera
- [ ] Integración con sistema de pagos
- [ ] Reportes y analytics

## Conclusión

El sistema de reservas de la CPU UNET proporciona una solución robusta y escalable para la gestión de reservas de áreas. La implementación sigue las mejores prácticas de Laravel con separación clara de responsabilidades, validaciones exhaustivas y un sistema de auditoría completo.

La arquitectura modular permite futuras extensiones sin afectar la funcionalidad existente, mientras que las validaciones estrictas y el sistema de anticolisión garantizan la integridad de los datos y la disponibilidad real de las áreas.
