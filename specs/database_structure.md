# Especificación de Base de Datos (Markdown) – Casa del Profesor UNET

> **Objetivo**: documento legible por un modelo de IA para generar **migraciones de Laravel 12** (MySQL 8). Nombres de **tablas y columnas en inglés**. Valores de algunos `ENUM` (roles, estados) están en **español** como se usará en la interfaz. Incluir **soft deletes** (`deleted_at`) donde se indica. Todas las tablas con `created_at` y `updated_at` por defecto.

## 0. Convenciones globales

* **DB**: MySQL 8, charset `utf8mb4`, collation `utf8mb4_unicode_ci`.
* **PK**: `id` autoincrement `BIGINT` sin signo.
* **FK**: `foreignId()->constrained()` con `onDelete('restrict')` salvo que se especifique.
* **Money**: `DECIMAL(10,2)`.
* **Fechas**: `DATETIME` (UTC), o `DATE` donde aplique.
* **Soft Deletes**: usar `deleted_at` cuando se marque.
* **Índices**: crear índices y únicos indicados abajo.
* **Naming**: snake\_case para columnas; singular para modelos, plural para tablas.

> Nota: Usar principalmente las convenciones propias de Laravel para nombres de tablas y columnas.

## 1) Enumeraciones de referencia (valores sugeridos)

* **user.role**: `['docente','administrador','obrero','estudiante','invitado']`
* **invitations.status**: `['pendiente','aceptada','rechazada','expirada','revocada']`
* **reservations.status**: `['pendiente','aprobada','rechazada','cancelada','completada','expirada']`
* **academies.status**: `['activa','cerrada','cancelada']`
* **enrollments.status**: `['pendiente','confirmada','anulada']`
* **documents.visibility**: `['publico','interno','solo_admin']`
* **contributions.status**: `['pendiente','pagado','vencido']`

> Nota: los `ENUM` pueden implementarse con columnas `ENUM` nativas o con `VARCHAR` + validación a nivel de aplicación. De preferencia usar `ENUM` nativos al crear migraciones con `enum([...])`.

---

## 2) Tablas

### 2.1 `users`

Usuarios del sistema. **Roles** almacenados directamente en esta tabla (no hay tabla `roles`).

Campos:

* `id` BIGINT UNSIGNED PK
* `role` ENUM (ver §1) **index**
* `name` VARCHAR(150)
* `email` VARCHAR(180) **unique**
* `password` VARCHAR(255) **nullable** (SSO users pueden no tener password local)
* `sso_uid` VARCHAR(191) **nullable**, **unique** (cuando exista integración CETI/SSO)
* `is_solvent` BOOLEAN **default false** (cache práctico de solvencia)
* `solvent_until` DATE **nullable** (fecha hasta la cual está solvente)
* `remember_token` VARCHAR(100) **nullable**
* `created_at`/`updated_at`
* `deleted_at` (soft delete)

Notas:

* `is_solvent` y `solvent_until` se **derivan** de `contributions`; mantenerlos sincronizados vía eventos/Jobs.

---

### 2.2 `invitations`

Invitaciones que un **docente** crea para terceros. Al aceptar, se puede crear el usuario `invitado`.

Campos:

* `id` PK
* `inviter_user_id` FK → `users.id` (quien invita, típicamente `docente`) **index**
* `invitee_user_id` FK → `users.id` **nullable** (si ya existe el usuario invitado)
* `email` VARCHAR(180) (correo del invitado) **index**
* `token` CHAR(64) **unique** (seguro, no predecible)
* `status` ENUM (ver §1) **default 'pendiente'**
* `expires_at` DATETIME **nullable** (ej. +90 días)
* `message` TEXT **nullable** (nota opcional del docente)
* `reviewed_by` FK → `users.id` **nullable** (admin que aprobó/rechazó)
* `reviewed_at` DATETIME **nullable**
* `created_at`/`updated_at`

Restricciones/Índices:

* FK `inviter_user_id` `onDelete('restrict')`; `invitee_user_id` `onDelete('set null')`.

---

### 2.3 `areas`

Instalaciones/espacios físicos (piscina, salones, canchas, etc.).

Campos:

* `id` PK
* `name` VARCHAR(150) **unique**
* `slug` VARCHAR(180) **unique** (para URL)
* `description` TEXT **nullable**
* `capacity` INT **nullable** (aforo orientativo)
* `hourly_rate` DECIMAL(10,2) **nullable** (tarifa por hora por **área**; un `service` puede **sobrescribir**)
* `is_active` BOOLEAN **default true**
* `created_at`/`updated_at`
* `deleted_at`

Índices:

* `is_active` index.

---

### 2.4 `area_schedules`

Horario base de disponibilidad semanal por **área** (no fechas específicas).

Campos:

* `id` PK
* `area_id` FK → `areas.id` **index**, `onDelete('cascade')`
* `day_of_week` TINYINT (0=domingo .. 6=sábado)
* `start_time` TIME
* `end_time` TIME
* `is_open` BOOLEAN **default true**
* `created_at`/`updated_at`

Restricciones:

* Único compuesto: (`area_id`,`day_of_week`,`start_time`,`end_time`).

---

### 2.5 `services`

Servicios ofrecidos **sobre un área** (p. ej., “Reserva de Salón Primavera”, “Uso de Piscina diurno”).

Campos:

* `id` PK
* `area_id` FK → `areas.id` **index**
* `name` VARCHAR(150)
* `description` TEXT **nullable**
* `requires_reservation` BOOLEAN **default true**
* `hourly_rate` DECIMAL(10,2) **nullable** (si está presente, **tiene prioridad** sobre `areas.hourly_rate`)
* `is_active` BOOLEAN **default true**
* `created_at`/`updated_at`
* `deleted_at`

Índices:

* (`area_id`,`is_active`)

---

### 2.6 `academies`

“Escuelas/Academias” institucionales (natación, karate, yoga, etc.).

Campos:

* `id` PK
* `name` VARCHAR(150) **unique**
* `description` TEXT **nullable**
* `lead_instructor_id` FK → `users.id` (usuario con rol apropiado) **index**
* `status` ENUM (ver §1) **default 'activa'**
* `created_at`/`updated_at`
* `deleted_at`

---

### 2.7 `academy_schedules`

Horarios **recurrentes** para academias (día/horas y el área que usan). Permite varios grupos/cupos.

Campos:

* `id` PK
* `academy_id` FK → `academies.id` **index**, `onDelete('cascade')`
* `area_id` FK → `areas.id` **index** (dónde se dicta)
* `day_of_week` TINYINT (0..6)
* `start_time` TIME
* `end_time` TIME
* `capacity` INT **nullable** (cupo máximo para ese bloque)
* `created_at`/`updated_at`

Restricciones:

* Único: (`academy_id`,`day_of_week`,`start_time`,`end_time`).

---

### 2.8 `academy_enrollments`

Inscripciones de usuarios a una **academia** (opcionalmente a un horario específico).

Campos:

* `id` PK
* `academy_id` FK → `academies.id` **index**
* `user_id` FK → `users.id` \*\*index\`
* `academy_schedule_id` FK → `academy_schedules.id` **nullable** (si se asigna franja concreta)
* `status` ENUM (ver §1) **default 'pendiente'**
* `notes` TEXT **nullable**
* `created_at`/`updated_at`

Restricciones:

* Único recomendado: (`academy_id`,`user_id`) para evitar duplicados generales.

---

### 2.9 `reservations`

Reservas de **áreas** por rango de horas (mismo día o varias horas contiguas).

Campos:

* `id` PK
* `requester_id` FK → `users.id` (quien solicita) **index**
* `area_id` FK → `areas.id` **index**
* `starts_at` DATETIME
* `ends_at` DATETIME
* `status` ENUM (ver §1) **default 'pendiente'**
* `approved_by` FK → `users.id` **nullable** (admin que aprueba/niega)
* `reviewed_at` DATETIME **nullable**
* `decision_reason` TEXT **nullable** (justificación admin)
* `title` VARCHAR(180) **nullable** (etiqueta/resumen del evento)
* `notes` TEXT **nullable**
* `created_at`/`updated_at`
* `deleted_at`

Reglas/Índices:

* Índice compuesto: (`area_id`,`starts_at`,`ends_at`).
* **Validar solapamientos** a nivel de aplicación/SQL (no hay check nativo simple en MySQL). Usar transacción + bloqueo o unique constraint por slot discretizado si se decide granularidad por bloque.

---

### 2.10 `contributions`

Registro de **aportes periódicos** (p. ej. mensual) por usuario y su estado.

Campos:

* `id` PK
* `user_id` FK → `users.id` **index**
* `period` DATE (convención: usar día 1 de cada mes, ej. `2025-07-01`)
* `amount` DECIMAL(10,2)
* `status` ENUM (ver §1) **default 'pendiente'**
* `paid_at` DATETIME **nullable**
* `receipt_url` VARCHAR(255) **nullable** (comprobante opcional)
* `created_at`/`updated_at`

Restricciones:

* Único compuesto: (`user_id`,`period`).

Notas:

* Jobs/Observers deben actualizar `users.is_solvent` y `users.solvent_until` cuando cambia un aporte.

---

### 2.11 `documents`

Documentación institucional (reglamentos, actas, instructivos, etc.). **Sencilla** como se solicitó.

Campos:

* `id` PK
* `title` VARCHAR(200)
* `file_url` VARCHAR(255) (ruta local o S3)
* `visibility` ENUM (ver §1) **default 'interno'**
* `uploaded_by` FK → `users.id` **index**
* `description` TEXT **nullable**
* `created_at`/`updated_at`
* `deleted_at`

---

### 2.12 `audit_logs`

Bitácora de acciones relevantes. Propósito: Registra cada cambio importante en el sistema, respondiendo a las preguntas: ¿quién?, ¿qué?, ¿cuándo? y ¿cómo cambió?

Campos:

* `id` PK
* `user_id` FK → `users.id` **nullable** (acciones del sistema pueden ser `NULL`)
* `entity_type` VARCHAR(120) (nombre de modelo: `Reservation`, `Contribution`, etc.)
* `entity_id` BIGINT **nullable**
* `action` VARCHAR(120) (ej. `created`,`updated`,`status_changed`)
* `created_at`

Índices:
* (`entity_type`,`entity_id`), `user_id`.

---

## 3) Datos semilla (seed) sugeridos

### 3.1 Áreas iniciales (`areas`)

* Piscina
* Salón Orquídea (Restaurant)
* Salón Primavera
* Salón Pradera
* Auditorio Paramillo
* Kiosco Tuquerena
* Kiosco Morusca
* Sauna
* Cancha de usos múltiples
* Cancha de bolas criollas
* Parque infantil
* Mesa de pool (billar)
* Mesa de ping pong
* Peluquería (con previa cita)

> Nota: *Piscina* es un área. Los siguientes son **programas** (no áreas): Escuela de natación, Karate, Yoga, Bailoterapia, Nado sincronizado, Tareas dirigidas.

### 3.2 Servicios iniciales (`services`)

Para cada **salón**/área que se preste, crear un servicio de “**Reserva de \[Área]**” con `requires_reservation=true` y `hourly_rate` si aplica. Ejemplos:

* Reserva Salón Orquídea → `area_id` = Salón Orquídea
* Reserva Salón Primavera → `area_id` = Salón Primavera
* Reserva Piscina (uso general) → `area_id` = Piscina

### 3.3 Academias (`academies`)

* Escuela de natación (lead\_instructor asignado)
* Karate
* Yoga
* Bailoterapia
* Nado sincronizado
* Tareas dirigidas

> Definir sus bloques en `academy_schedules` con `area_id` correspondiente (ej. Piscina para natación).

---

## 4) Orden recomendado de migraciones

1. `users`
2. `areas`
3. `area_schedules`
4. `services`
5. `academies`
6. `academy_schedules`
7. `academy_enrollments`
8. `invitations`
9. `reservations`
10. `contributions`
11. `documents`
12. `audit_logs`

---

## 5) Reglas de integridad y negocio (para validadores/policies)

* **Solvencia**: para crear `reservations` o `invitations` el `requester` debe tener `users.is_solvent = true` y `solvent_until >= TODAY`.
* **Aprobación**: `reservations.status` sólo puede pasar de `pendiente` a `aprobada`/`rechazada` por un `administrador`. Guardar `approved_by`, `reviewed_at`, `decision_reason`.
* **Conflictos**: al aprobar, validar que no exista solapamiento (`area_id`, rango `[starts_at, ends_at)`).
* **Tarifas**: `services.hourly_rate` (si no `NULL`) **tiene precedencia** sobre `areas.hourly_rate`.
* **Invitaciones**: sólo `docente` crea; sólo `administrador` cambia a `aceptada`/`rechazada`.
* **Academias**: no permitir inscripciones si `academy_schedules.capacity` alcanzado.
* **Auditoría**: registrar en `audit_logs` cambios de estado en `reservations`, `contributions`, `invitations` y publicaciones en `documents`.

---

## 6)
