# 02 - Modelos y Relaciones de Eloquent

> **Propósito**: Este documento detalla cada uno de los modelos de Eloquent del sistema, sus atributos principales y, más importante, las relaciones que definen cómo se conectan entre sí. Es el complemento orientado a la aplicación del esquema de la base de datos.

---

## Modelo: `User`

- **Tabla**: `users`
- **Descripción**: Representa a un usuario en el sistema, con su rol, estado y datos de autenticación.

#### Atributos Llenables (`fillable`)
- `role`, `name`, `email`, `password`, `sso_uid`, `status`, `responsible_email`, `aspired_role`, `solvent_until`, `auth_code`, `auth_code_expires_at`, `auth_code_attempts`, `last_code_sent_at`

#### Casts
- `role` → `UserRole::class` (Enum)
- `status` → `UserStatus::class` (Enum)
- `aspired_role` → `AspiredRole::class` (Enum)
- `email_verified_at` → `datetime`
- `password` → `hashed`
- `solvent_until` → `date`

#### Relaciones
- `reservations()`: `HasMany` → `Reservation` (Un usuario tiene muchas reservas).
- `contributions()`: `HasMany` → `Contribution` (Un usuario tiene muchos aportes).
- `sentInvitations()`: `HasMany` → `Invitation` (Un profesor tiene muchas invitaciones enviadas).
- `ledAcademies()`: `HasMany` → `Academy` (Un instructor lidera muchas academias).
- `notifications()`: `HasMany` → `Notification` (Un usuario tiene muchas notificaciones).
- `conversationsAsUserOne()` / `conversationsAsUserTwo()`: `HasMany` → `Conversation`.
- `sentMessages()` / `receivedMessages()`: `HasMany` → `ConversationMessage`.
- `blocksCreated()` / `blocksReceived()`: `HasMany` → `UserBlock`.

---

## Modelo: `Area`

- **Tabla**: `areas`
- **Descripción**: Un espacio físico dentro de la Casa del Profesor.

#### Atributos Llenables
- `name`, `slug`, `description`, `capacity`, `is_reservable`, `is_active`

#### Casts
- `is_reservable` → `boolean`
- `is_active` → `boolean`

#### Relaciones
- `areaSchedules()`: `HasMany` → `AreaSchedule` (Un área tiene muchos horarios de apertura).
- `academySchedules()`: `HasMany` → `AcademySchedule` (En un área se imparten muchas clases de academias).
- `reservations()`: `HasMany` → `Reservation` (Un área tiene muchas reservas).
- `entityFiles()`: `MorphMany` → `EntityFile` (Relación polimórfica para archivos/imágenes).

---

## Modelo: `Academy`

- **Tabla**: `academies`
- **Descripción**: Una academia o escuela deportiva/cultural.

#### Atributos Llenables
- `name`, `description`, `lead_instructor_id`, `status`

#### Casts
- `status` → `AcademyStatus::class` (Enum)

#### Relaciones
- `leadInstructor()`: `BelongsTo` → `User` (Una academia pertenece a un instructor líder).
- `academySchedules()`: `HasMany` → `AcademySchedule` (Una academia tiene muchos horarios de clase).
- `academyStudents()`: `HasMany` → `AcademyStudent` (Una academia tiene muchos estudiantes externos).
- `entityFiles()`: `MorphMany` → `EntityFile` (Relación polimófirca para archivos/imágenes).

---

## Modelo: `Reservation`

- **Tabla**: `reservations`
- **Descripción**: Una reserva de un `Area` por parte de un `User`.

#### Atributos Llenables
- `requester_id`, `area_id`, `starts_at`, `ends_at`, `status`, `title`, `notes`, `decision_reason`, `approved_by`, `reviewed_at`

#### Casts
- `status` → `ReservationStatus::class` (Enum)
- `starts_at` → `datetime`
- `ends_at` → `datetime`

#### Relaciones
- `requester()`: `BelongsTo` → `User` (La reserva pertenece a un usuario solicitante).
- `area()`: `BelongsTo` → `Area` (La reserva pertenece a un área).
- `approver()`: `BelongsTo` → `User` (La reserva fue aprobada por un administrador).

---

## Modelo: `Invitation`

- **Tabla**: `invitations`
- **Descripción**: Una invitación de un profesor a un tercero para que se una al sistema.

#### Atributos Llenables
- `inviter_user_id`, `name`, `email`, `token`, `status`, `expires_at`, `message`, `reviewed_by`, `reviewed_at`, `rejection_reason`

#### Casts
- `status` → `InvitationStatus::class` (Enum)
- `expires_at` → `datetime`

#### Relaciones
- `inviterUser()`: `BelongsTo` → `User` (La invitación fue creada por un usuario).
- `reviewedBy()`: `BelongsTo` → `User` (La invitación fue revisada por un administrador).

---

## Modelo: `File`

- **Tabla**: `files`
- **Descripción**: Representa un archivo físico almacenado en un disco (ej. R2) y sus metadatos.

#### Atributos Llenables
- `title`, `original_filename`, `file_path`, `mime_type`, `file_size`, `file_hash`, `file_type`, `storage_disk`, `metadata`, `visibility`, `uploaded_by`, `description`

#### Casts
- `visibility` → `DocumentVisibility::class` (Enum)
- `metadata` → `array`

#### Relaciones
- `uploadedBy()`: `BelongsTo` → `User` (El archivo fue subido por un usuario).

---

## Modelo: `EntityFile`

- **Tabla**: `entity_files`
- **Descripción**: Tabla pivote polimórfica que conecta un `File` con cualquier otra entidad (un `Area`, una `Academy`, etc.).

#### Atributos Llenables
- `entity_type`, `entity_id`, `file_id`, `sort_order`, `caption`, `is_cover`

#### Casts
- `is_cover` → `boolean`

#### Relaciones
- `entity()`: `MorphTo` (Puede pertenecer a `Area`, `Academy`, etc.).
- `file()`: `BelongsTo` → `File` (La entrada está asociada a un archivo).

---

## Modelos del Módulo de Chat

- **`Conversation`**: Conecta a `user_one` y `user_two`.
  - `messages()`: `HasMany` → `ConversationMessage`
  - `reads()`: `HasMany` → `ConversationRead`
- **`ConversationMessage`**: Un mensaje individual.
  - `conversation()`: `BelongsTo` → `Conversation`
  - `sender()`: `BelongsTo` → `User`
  - `receiver()`: `BelongsTo` → `User`
- **`ConversationRead`**: El estado de lectura de un usuario en una conversación.
  - `conversation()`: `BelongsTo` → `Conversation`
  - `user()`: `BelongsTo` → `User`
- **`UserBlock`**: Un registro de un usuario bloqueando a otro.
  - `blocker()`: `BelongsTo` → `User` (Quien bloquea).
  - `blocked()`: `BelongsTo` → `User` (Quien es bloqueado).

---

## Otros Modelos

- **`AreaSchedule`**: Horario de un `Area`.
  - `area()`: `BelongsTo` → `Area`
- **`AcademySchedule`**: Horario de una `Academy`.
  - `academy()`: `BelongsTo` → `Academy`
  - `area()`: `BelongsTo` → `Area`
- **`AcademyStudent`**: Estudiante externo de una `Academy`.
  - `academy()`: `BelongsTo` → `Academy`
- **`Contribution`**: Aporte económico de un `User`.
  - `user()`: `BelongsTo` → `User`
- **`Notification`**: Notificación para un usuario o rol.
- **`AuditLog`**: Registro de auditoría.
  - `user()`: `BelongsTo` → `User` (Usuario que ejecuta la acción).
