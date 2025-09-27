# Modelos y Datos Base del Sistema - Casa del Profesor UNET

> **Propósito de este documento**  
> Documento de referencia que contiene todos los modelos del sistema y los datos base que están registrados actualmente. Esta información es fundamental para el desarrollo de controladores y flujos de usuario.

---

## 1) Modelos del Sistema

### 1.1 User (Usuario)
**Tabla:** `users`
**Propósito:** Gestión de usuarios del sistema

**Campos principales:**
- `id` - Identificador único
- `name` - Nombre completo
- `email` - Correo electrónico (único)
- `password` - Contraseña hasheada
- `role` - Rol del usuario (enum: usuario, profesor, instructor, administrador, obrero, estudiante, invitado)
- `sso_uid` - ID del sistema SSO CETI (opcional)
- `is_solvent` - Estado de solvencia (boolean)
- `solvent_until` - Fecha hasta la cual está solvente
- `email_verified_at` - Fecha de verificación de email
- `created_at`, `updated_at`, `deleted_at` - Timestamps

**Relaciones:**
- `reservations()` - Reservas realizadas
- `contributions()` - Aportes realizados
- `academies()` - Academias que dirige (como instructor)
- `enrollments()` - Inscripciones en academias

### 1.2 Area (Área)
**Tabla:** `areas`
**Propósito:** Espacios físicos disponibles en la Casa del Profesor

**Campos principales:**
- `id` - Identificador único
- `name` - Nombre del área
- `slug` - URL amigable (único)
- `description` - Descripción del área
- `capacity` - Capacidad máxima (nullable)
- `hourly_rate` - Tarifa por hora (nullable)
- `is_active` - Estado activo/inactivo
- `created_at`, `updated_at` - Timestamps

**Relaciones:**
- `services()` - Servicios asociados
- `schedules()` - Horarios del área
- `reservations()` - Reservas del área

### 1.3 Service (Servicio)
**Tabla:** `services`
**Propósito:** Servicios ofrecidos en cada área

**Campos principales:**
- `id` - Identificador único
- `area_id` - ID del área (foreign key)
- `name` - Nombre del servicio
- `description` - Descripción del servicio
- `requires_reservation` - Requiere reserva (boolean)
- `hourly_rate` - Tarifa por hora (nullable, override del área)
- `is_active` - Estado activo/inactivo
- `created_at`, `updated_at` - Timestamps

**Relaciones:**
- `area()` - Área asociada
- `reservations()` - Reservas del servicio

### 1.4 Academy (Academia)
**Tabla:** `academies`
**Propósito:** Escuelas y academias institucionales

**Campos principales:**
- `id` - Identificador único
- `name` - Nombre de la academia
- `description` - Descripción de la academia
- `lead_instructor_id` - ID del instructor principal (foreign key)
- `status` - Estado (enum: activa, inactiva, suspendida)
- `created_at`, `updated_at` - Timestamps

**Relaciones:**
- `leadInstructor()` - Instructor principal
- `schedules()` - Horarios de la academia
- `enrollments()` - Inscripciones

### 1.5 Reservation (Reserva)
**Tabla:** `reservations`
**Propósito:** Reservas de áreas y servicios

**Campos principales:**
- `id` - Identificador único
- `area_id` - ID del área (foreign key)
- `service_id` - ID del servicio (foreign key, nullable)
- `user_id` - ID del usuario solicitante (foreign key)
- `status` - Estado (enum: pendiente, aprobada, rechazada, cancelada, completada, expirada)
- `starts_at` - Fecha y hora de inicio
- `ends_at` - Fecha y hora de fin
- `notes` - Notas adicionales
- `approved_by` - ID del administrador que aprobó (foreign key, nullable)
- `approved_at` - Fecha de aprobación (nullable)
- `created_at`, `updated_at` - Timestamps

**Relaciones:**
- `area()` - Área reservada
- `service()` - Servicio reservado
- `user()` - Usuario solicitante
- `approver()` - Administrador que aprobó

### 1.6 Contribution (Aporte)
**Tabla:** `contributions`
**Propósito:** Aportes de mantenimiento de usuarios

**Campos principales:**
- `id` - Identificador único
- `user_id` - ID del usuario (foreign key)
- `period` - Período del aporte (fecha)
- `amount` - Monto del aporte
- `status` - Estado (enum: pendiente, pagada, vencida, cancelada)
- `receipt_url` - URL del comprobante (nullable)
- `created_at`, `updated_at` - Timestamps

**Relaciones:**
- `user()` - Usuario que realizó el aporte

### 1.7 Document (Documento)
**Tabla:** `documents`
**Propósito:** Documentos institucionales

**Campos principales:**
- `id` - Identificador único
- `title` - Título del documento
- `description` - Descripción
- `file_url` - URL del archivo
- `visibility` - Visibilidad (enum: publico, privado, restringido)
- `uploaded_by` - ID del usuario que subió (foreign key)
- `created_at`, `updated_at` - Timestamps

**Relaciones:**
- `uploader()` - Usuario que subió el documento

### 1.8 File (Archivo)
**Tabla:** `files`
**Propósito:** Gestión genérica de archivos subidos (imágenes, documentos, comprobantes, etc.)

**Campos principales:**
- `id` - Identificador único
- `title` - Título del archivo
- `original_filename` - Nombre original del archivo
- `file_path` - Ruta del archivo en R2
- `mime_type` - Tipo MIME del archivo
- `file_size` - Tamaño en bytes
- `file_hash` - Hash SHA-256 para deduplicación
- `file_type` - Tipo de archivo (enum: document, image, receipt, other)
- `storage_disk` - Disco de almacenamiento (r2)
- `metadata` - Metadatos adicionales (JSON)
- `visibility` - Visibilidad (enum: publico, privado, restringido)
- `uploaded_by` - ID del usuario que subió (foreign key)
- `description` - Descripción opcional
- `created_at`, `updated_at`, `deleted_at` - Timestamps

**Relaciones:**
- `uploadedBy()` - Usuario que subió el archivo

---

## 2) Datos Base Registrados

### 2.1 Áreas Disponibles

| Área | Capacidad | Descripción |
|------|-----------|-------------|
| **Piscina** | Variable | Área acuática para recreación y deportes. Requiere traje de baño y ducha previa. |
| **Salón Orquídea (Restaurant)** | Variable | Restaurante con acceso público. Tiempo máximo de permanencia: 150 minutos. |
| **Salón Primavera** | 100 | Terraza techada para eventos y celebraciones. |
| **Salón Pradera** | 150 | Terraza techada para eventos y celebraciones. |
| **Auditorio Paramillo** | 100 | Auditorio para presentaciones y eventos formales. |
| **Kiosco Tuquerena** | 30 | Kiosco para eventos al aire libre. |
| **Kiosco Morusca** | 30 | Kiosco para eventos al aire libre. |
| **Sauna** | 6 | Área de relajación con turnos de 15 minutos. Exclusivo para docentes. |
| **Cancha de usos múltiples** | Variable | Cancha deportiva para múltiples actividades. |
| **Cancha de bolas criollas** | Variable | Cancha especializada para bolas criollas. |
| **Parque infantil** | Variable | Área de recreación para menores de 10 años. Requiere supervisión adulta. |
| **Mesa de pool (billar)** | 4 | Mesa de billar para recreación. |
| **Mesa de ping pong** | 4 | Mesa de ping pong para recreación. |
| **Peluquería (con previa cita)** | 2 | Servicio de peluquería con cita previa. |

### 2.2 Servicios Disponibles

**Servicios de Reserva:**
- Reserva Piscina
- Reserva Salón Primavera
- Reserva Salón Pradera
- Reserva Auditorio Paramillo
- Reserva Kiosco Tuquerena
- Reserva Kiosco Morusca
- Reserva Sauna
- Reserva Cancha de usos múltiples
- Reserva Cancha de bolas criollas
- Reserva Mesa de pool (billar)
- Reserva Mesa de ping pong
- Reserva Peluquería (con previa cita)

**Servicios No Reservables:**
- Salón Orquídea (Restaurant) - Acceso público
- Parque infantil - Uso libre con reglas

### 2.3 Academias Disponibles

| Academia | Estado | Descripción |
|----------|--------|-------------|
| **Escuela de natación** | Activa | Academia de natación institucional |
| **Karate** | Activa | Academia de karate |
| **Yoga** | Activa | Academia de yoga |
| **Bailoterapia** | Activa | Academia de bailoterapia |
| **Nado sincronizado** | Activa | Academia de nado sincronizado |
| **Tareas dirigidas** | Activa | Academia de tareas dirigidas |

### 2.4 Documentos Institucionales

| Documento | Visibilidad | Descripción |
|-----------|-------------|-------------|
| **Reglamento CPU – 2017 (PDF)** | Público | Reglamento de Uso de la Casa del Profesor Universitario - Febrero 2017 |

---

## 3) Enums y Estados

### 3.1 UserRole (Roles de Usuario)
```php
enum UserRole: string
{
    case Usuario = 'usuario';           // Rol base
    case Profesor = 'profesor';         // Personal docente UNET
    case Instructor = 'instructor';     // Responsable de academias
    case Administrador = 'administrador'; // Gestión del sistema
    case Obrero = 'obrero';             // Personal de mantenimiento
    case Estudiante = 'estudiante';     // Estudiantes UNET
    case Invitado = 'invitado';         // Acceso temporal
}
```

### 3.2 ReservationStatus (Estados de Reserva)
```php
enum ReservationStatus: string
{
    case Pendiente = 'pendiente';
    case Aprobada = 'aprobada';
    case Rechazada = 'rechazada';
    case Cancelada = 'cancelada';
    case Completada = 'completada';
    case Expirada = 'expirada';
}
```

### 3.3 AcademyStatus (Estados de Academia)
```php
enum AcademyStatus: string
{
    case Activa = 'activa';
    case Inactiva = 'inactiva';
    case Suspendida = 'suspendida';
}
```

### 3.4 ContributionStatus (Estados de Aporte)
```php
enum ContributionStatus: string
{
    case Pendiente = 'pendiente';
    case Pagada = 'pagada';
    case Vencida = 'vencida';
    case Cancelada = 'cancelada';
}
```

### 3.5 DocumentVisibility (Visibilidad de Documentos)
```php
enum DocumentVisibility: string
{
    case Publico = 'publico';
    case Privado = 'privado';
    case Restringido = 'restringido';
}
```

---

## 4) Flujos de Dependencia

### 4.1 Jerarquía de Creación
```
Usuario → Área → Servicio → Reserva
Usuario → Academia → Horario → Inscripción
Usuario → Aporte → Solvencia
```

### 4.2 Reglas de Negocio
1. **Reservas:** Requieren Área + Servicio + Usuario solvente
2. **Academias:** Requieren Instructor + Horarios + Área
3. **Aportes:** Requieren Usuario + Administrador para aprobación
4. **Documentos:** Requieren Usuario con permisos de subida
5. **Archivos:** Requieren Usuario autenticado, deduplicación por hash SHA-256

### 4.3 Validaciones
- **Áreas:** Nombre único, slug único
- **Servicios:** Un servicio por área (Reserva [Área])
- **Academias:** Nombre único, instructor válido
- **Reservas:** No solapamiento de horarios, usuario solvente
- **Aportes:** Un aporte por usuario por período
- **Archivos:** Tipos MIME permitidos, tamaño máximo 10MB, deduplicación por hash

---

## 5) Próximos Controladores a Implementar

### 5.1 AreasController
- `index()` - Listar áreas disponibles
- `show()` - Mostrar detalles del área
- `store()` - Crear nueva área (Admin)
- `update()` - Actualizar área (Admin)
- `destroy()` - Eliminar área (Admin)

### 5.2 ServicesController
- `index()` - Listar servicios por área
- `show()` - Mostrar detalles del servicio
- `store()` - Crear nuevo servicio (Admin)
- `update()` - Actualizar servicio (Admin)

### 5.3 AcademiesController
- `index()` - Listar academias
- `show()` - Mostrar detalles de academia
- `store()` - Crear academia (Admin)
- `update()` - Actualizar academia (Instructor/Admin)
- `enrollments()` - Gestionar inscripciones

### 5.4 ReservationsController
- `index()` - Listar reservas del usuario
- `store()` - Crear nueva reserva
- `show()` - Mostrar detalles de reserva
- `update()` - Actualizar reserva
- `cancel()` - Cancelar reserva
- `approve()` - Aprobar reserva (Admin)
- `reject()` - Rechazar reserva (Admin)

### 5.5 ContributionsController
- `index()` - Listar aportes del usuario
- `store()` - Registrar aporte (Admin)
- `show()` - Mostrar detalles de aporte
- `update()` - Actualizar estado de aporte (Admin)

### 5.6 UploadController (Implementado)
- `index()` - Listar archivos del usuario
- `store()` - Subir archivo con metadatos
- `show()` - Mostrar detalles del archivo
- `destroy()` - Eliminar archivo y registro
- `presign()` - Generar URL presignada para subida directa

---

*Documento creado el 27 de enero de 2025 - Información base para desarrollo de controladores y flujos de usuario.*
