# 01 - Esquema de la Base de Datos

> **Propósito**: Este documento describe la estructura actual y final de la base de datos del sistema, generada a partir del análisis de todas las migraciones del proyecto. Sirve como la fuente de verdad para el desarrollo y consultas.

## Convenciones

- **Motor de BD**: MySQL 8
- **Juego de Caracteres**: `utf8mb4` con colación `utf8mb4_unicode_ci`
- **Claves Primarias (PK)**: `id` como `BIGINT UNSIGNED AUTO_INCREMENT`.
- **Claves Foráneas (FK)**: Nombradas como `table_id`, con restricciones.
- **Timestamps**: `created_at` y `updated_at` en todas las tablas principales. Se utiliza `deleted_at` para Soft Deletes.
- **Nomenclatura**: Tablas en plural, columnas en `snake_case`.

---

## Diagrama de Tablas

### 1. Usuarios y Acceso

- `users`
- `password_reset_tokens`
- `personal_access_tokens`
- `sessions`
- `invitations`

### 2. Entidades Principales

- `areas`
- `area_schedules`
- `academies`
- `academy_schedules`
- `academy_students`

### 3. Sistema de Reservas

- `reservations`

### 4. Gestión Financiera

- `contributions`

### 5. Sistema de Archivos

- `files`
- `entity_files` (tabla polimórfica)

### 6. Comunicación y Auditoría

- `notifications`
- `conversations`
- `conversation_messages`
- `conversation_reads`
- `user_blocks`
- `audit_logs`

### 7. Tablas del Framework

- `cache`
- `cache_locks`
- `jobs`
- `job_batches`
- `failed_jobs`

---

## Estructura Detallada de Tablas

### `users`

Almacena todos los usuarios del sistema, sus roles y estados.

| Columna | Tipo | Atributos | Descripción |
| :--- | :--- | :--- | :--- |
| `id` | `BIGINT` | PK, Unsigned | Identificador único. |
| `role` | `ENUM` | | Rol del usuario. Valores: `usuario`, `profesor`, `instructor`, `administrador`, `obrero`, `estudiante`, `invitado`. |
| `status` | `ENUM` | | Estado de la cuenta. Valores: `aprobacion_pendiente`, `solvente`, `insolvente`, `rechazado`. |
| `name` | `VARCHAR(150)` | | Nombre completo del usuario. |
| `email` | `VARCHAR(180)` | Unique | Correo electrónico. |
| `email_verified_at` | `TIMESTAMP` | Nullable | Fecha de verificación de email. |
| `password` | `VARCHAR(255)` | Nullable | Contraseña hasheada. Nulo para usuarios SSO. |
| `responsible_email` | `VARCHAR(180)` | Nullable | Email del profesor responsable (para estudiantes). |
| `aspired_role` | `ENUM` | Nullable | Rol al que aspira un usuario en auto-registro. Valores: `profesor`, `estudiante`. |
| `solvent_until` | `DATE` | Nullable | Fecha de vigencia de la solvencia. |
| `auth_code` | `VARCHAR(64)` | Nullable | Código para reseteo de contraseña o primera contraseña. |
| `auth_code_expires_at` | `TIMESTAMP` | Nullable | Expiración del `auth_code`. |
| `auth_code_attempts` | `TINYINT` | Default 0 | Intentos fallidos de usar `auth_code`. |
| `last_code_sent_at` | `TIMESTAMP` | Nullable | Última vez que se envió un código. |
| `remember_token` | `VARCHAR(100)` | Nullable | Token de "recordarme". |
| `sso_uid` | `VARCHAR(191)` | Nullable, Unique | ID para Single Sign-On (CETI). |
| `created_at`, `updated_at` | `TIMESTAMP` | | Timestamps de creación/actualización. |
| `deleted_at` | `TIMESTAMP` | Nullable | Para Soft Deletes. |

---

### `areas`

Define los espacios físicos de la Casa del Profesor.

| Columna | Tipo | Atributos | Descripción |
| :--- | :--- | :--- | :--- |
| `id` | `BIGINT` | PK, Unsigned | Identificador único. |
| `name` | `VARCHAR(150)` | Unique | Nombre del área. |
| `slug` | `VARCHAR(180)` | Unique | URL amigable. |
| `description` | `TEXT` | Nullable | Descripción detallada. |
| `capacity` | `INT` | Nullable | Aforo máximo. |
| `is_reservable` | `BOOLEAN` | Nullable | Indica si el área se puede reservar. |
| `is_active` | `BOOLEAN` | Default `true` | Indica si el área está operativa. |
| `created_at`, `updated_at` | `TIMESTAMP` | | Timestamps. |
| `deleted_at` | `TIMESTAMP` | Nullable | Para Soft Deletes. |

**Nota:** La tabla `services` fue eliminada. La lógica de reserva ahora depende directamente de `areas` y su campo `is_reservable`.

---

### `area_schedules`

Horarios de apertura recurrentes para cada área.

| Columna | Tipo | Atributos | Descripción |
| :--- | :--- | :--- | :--- |
| `id` | `BIGINT` | PK, Unsigned | Identificador único. |
| `area_id` | `BIGINT` | FK → `areas.id` | Área asociada. |
| `day_of_week` | `TINYINT` | | Día de la semana (1=Lunes, 7=Domingo). |
| `start_time` | `TIME` | | Hora de apertura. |
| `end_time` | `TIME` | | Hora de cierre. |
| `is_open` | `BOOLEAN` | Default `true` | Indica si está abierto en ese horario. |
| `created_at`, `updated_at` | `TIMESTAMP` | | Timestamps. |

---

### `academies`

Academias o escuelas que operan en la institución.

| Columna | Tipo | Atributos | Descripción |
| :--- | :--- | :--- | :--- |
| `id` | `BIGINT` | PK, Unsigned | Identificador único. |
| `name` | `VARCHAR(150)` | Unique | Nombre de la academia. |
| `description` | `TEXT` | Nullable | Descripción. |
| `lead_instructor_id` | `BIGINT` | FK → `users.id` | Usuario (instructor) que lidera la academia. |
| `status` | `ENUM` | | Estado. Valores: `activa`, `cerrada`, `cancelada`. |
| `created_at`, `updated_at` | `TIMESTAMP` | | Timestamps. |
| `deleted_at` | `TIMESTAMP` | Nullable | Para Soft Deletes. |

---

### `academy_schedules`

Horarios recurrentes de las academias en áreas específicas.

| Columna | Tipo | Atributos | Descripción |
| :--- | :--- | :--- | :--- |
| `id` | `BIGINT` | PK, Unsigned | Identificador único. |
| `academy_id` | `BIGINT` | FK → `academies.id` | Academia asociada. |
| `area_id` | `BIGINT` | FK → `areas.id` | Área donde se realiza la actividad. |
| `day_of_week` | `TINYINT` | | Día de la semana (1=Lunes, 7=Domingo). |
| `start_time` | `TIME` | | Hora de inicio. |
| `end_time` | `TIME` | | Hora de fin. |
| `capacity` | `INT` | Nullable | Cupo para ese horario. |
| `created_at`, `updated_at` | `TIMESTAMP` | | Timestamps. |

---

### `academy_students`

Gestiona una lista de estudiantes externos (no registrados como usuarios) para cada academia.

| Columna | Tipo | Atributos | Descripción |
| :--- | :--- | :--- | :--- |
| `id` | `BIGINT` | PK, Unsigned | Identificador único. |
| `academy_id` | `BIGINT` | FK → `academies.id` | Academia a la que pertenece el estudiante. |
| `name` | `VARCHAR(200)` | | Nombre completo del estudiante. |
| `age` | `INT` | Unsigned | Edad del estudiante. |
| `status` | `ENUM` | | Estado de pago. Valores: `solvente`, `insolvente`. |
| `created_at`, `updated_at` | `TIMESTAMP` | | Timestamps. |

**Nota:** Esta tabla reemplazó a `academy_enrollments` para simplificar la gestión de alumnos que no necesitan acceso al sistema.

---

### `reservations`

Reservas de áreas realizadas por los usuarios.

| Columna | Tipo | Atributos | Descripción |
| :--- | :--- | :--- | :--- |
| `id` | `BIGINT` | PK, Unsigned | Identificador único. |
| `requester_id` | `BIGINT` | FK → `users.id` | Usuario que solicita la reserva. |
| `area_id` | `BIGINT` | FK → `areas.id` | Área reservada. |
| `starts_at` | `DATETIME` | | Inicio de la reserva. |
| `ends_at` | `DATETIME` | | Fin de la reserva. |
| `status` | `ENUM` | | Estado. Valores: `pendiente`, `aprobada`, `rechazada`, `cancelada`, `completada`, `expirada`. |
| `title` | `VARCHAR(180)` | Nullable | Título o motivo de la reserva. |
| `notes` | `TEXT` | Nullable | Notas adicionales. |
| `decision_reason` | `TEXT` | Nullable | Razón de aprobación/rechazo del admin. |
| `approved_by` | `BIGINT` | FK → `users.id`, Nullable | Admin que gestionó la solicitud. |
| `reviewed_at` | `DATETIME` | Nullable | Fecha de la gestión. |
| `created_at`, `updated_at` | `TIMESTAMP` | | Timestamps. |
| `deleted_at` | `TIMESTAMP` | Nullable | Para Soft Deletes. |

---

### `invitations`

Invitaciones generadas por profesores para terceros.

| Columna | Tipo | Atributos | Descripción |
| :--- | :--- | :--- | :--- |
| `id` | `BIGINT` | PK, Unsigned | Identificador único. |
| `inviter_user_id` | `BIGINT` | FK → `users.id` | Usuario (profesor) que invita. |
| `name` | `VARCHAR(255)` | | Nombre del invitado. |
| `email` | `VARCHAR(180)` | | Email del invitado. |
| `message` | `TEXT` | Nullable | Mensaje personalizado. |
| `token` | `CHAR(64)` | Unique | Token único para aceptar la invitación. |
| `status` | `ENUM` | | Estado. Valores: `pendiente`, `aceptada`, `rechazada`, `expirada`, `revocada`. |
| `expires_at` | `DATETIME` | Nullable | Fecha de expiración del token. |
| `reviewed_by` | `BIGINT` | FK → `users.id`, Nullable | Admin que gestionó la invitación. |
| `reviewed_at` | `DATETIME` | Nullable | Fecha de la gestión. |
| `rejection_reason` | `TEXT` | Nullable | Razón del rechazo. |
| `created_at`, `updated_at` | `TIMESTAMP` | | Timestamps. |

---

### `files`

Almacén central de metadatos de archivos subidos al sistema (ej. Cloudflare R2).

| Columna | Tipo | Atributos | Descripción |
| :--- | :--- | :--- | :--- |
| `id` | `BIGINT` | PK, Unsigned | Identificador único. |
| `title` | `VARCHAR(200)` | | Título del archivo. |
| `original_filename` | `VARCHAR(255)` | | Nombre original del archivo. |
| `file_path` | `VARCHAR(255)` | | Ruta en el disco de almacenamiento (ej. R2). |
| `mime_type` | `VARCHAR(100)` | | Tipo MIME. |
| `file_size` | `BIGINT` | Unsigned | Tamaño en bytes. |
| `file_hash` | `VARCHAR(64)` | Nullable, Index | Hash SHA-256 para deduplicación. |
| `file_type` | `ENUM` | | Tipo de archivo. Valores: `document`, `image`, `receipt`, `other`. |
| `storage_disk` | `VARCHAR(50)` | | Disco de almacenamiento (ej. `r2`). |
| `metadata` | `JSON` | Nullable | Metadatos adicionales. |
| `visibility` | `ENUM` | | Visibilidad. Valores: `publico`, `privado`, `restringido`. |
| `uploaded_by` | `BIGINT` | FK → `users.id` | Usuario que subió el archivo. |
| `description` | `TEXT` | Nullable | Descripción. |
| `created_at`, `updated_at` | `TIMESTAMP` | | Timestamps. |
| `deleted_at` | `TIMESTAMP` | Nullable | Para Soft Deletes. |

---

### `entity_files`

Tabla polimórfica que asocia archivos de la tabla `files` con otras entidades (ej. un `Area` o `Academy`).

| Columna | Tipo | Atributos | Descripción |
| :--- | :--- | :--- | :--- |
| `id` | `BIGINT` | PK, Unsigned | Identificador único. |
| `entity_type` | `VARCHAR(50)` | | Nombre del modelo (ej. `App\Models\Area`). |
| `entity_id` | `BIGINT` | Unsigned | ID de la entidad. |
| `file_id` | `BIGINT` | FK → `files.id` | Archivo asociado. |
| `sort_order` | `INT` | Default 0 | Orden para galerías de imágenes. |
| `caption` | `VARCHAR(255)` | Nullable | Leyenda de la imagen. |
| `is_cover` | `BOOLEAN` | Default `false` | Indica si es la imagen de portada. |
| `created_at`, `updated_at` | `TIMESTAMP` | | Timestamps. |

---

### `audit_logs`

Bitácora de acciones importantes para trazabilidad y seguridad.

| Columna | Tipo | Atributos | Descripción |
| :--- | :--- | :--- | :--- |
| `id` | `BIGINT` | PK, Unsigned | Identificador único. |
| `user_id` | `BIGINT` | FK → `users.id`, Nullable | Usuario que realizó la acción (nulo si es sistema). |
| `entity_type` | `VARCHAR(120)` | | Nombre del modelo afectado. |
| `entity_id` | `BIGINT` | Nullable | ID del registro afectado. |
| `action` | `VARCHAR(120)` | | Acción realizada (ej. `user_created`). |
| `before` | `JSON` | Nullable | Estado del registro antes del cambio. |
| `after` | `JSON` | Nullable | Estado del registro después del cambio. |
| `created_at`, `updated_at` | `TIMESTAMP` | | Timestamps. |

---

### Tablas del Sistema de Chat

- **`conversations`**: Almacena las conversaciones entre dos usuarios (`user_one_id`, `user_two_id`).
- **`conversation_messages`**: Contiene cada mensaje (`body`) con su emisor (`sender_id`) y receptor (`receiver_id`).
- **`conversation_reads`**: Guarda el último mensaje leído (`last_read_message_id`) por cada usuario en una conversación.
- **`user_blocks`**: Registra los bloqueos entre usuarios (`blocker_id`, `blocked_id`).

---

### Otras Tablas

- **`notifications`**: Para notificaciones in-app dirigidas a usuarios o roles.
- **`contributions`**: Registros de aportes económicos de los usuarios.
- **Framework & Jobs**: `cache`, `jobs`, `failed_jobs`, etc., son tablas estándar de Laravel para su funcionamiento interno.
