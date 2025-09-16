
# Changelog del Proyecto

Este archivo es un registro cronológico de todos los cambios realizados en el sistema de archivos por el agente de IA. Su propósito principal es servir como una fuente rápida y confiable para la **recuperación de contexto** en caso de que la sesión de chat se interrumpa o el contexto se pierda.

**Instrucción para el Agente de IA:** Antes de realizar cualquier acción, revisa las últimas entradas de este `changelog`. Después de cada operación de creación, modificación o eliminación de archivos, **DEBES** añadir una nueva entrada al final de este documento siguiendo el formato especificado en las `.cursor-rules.json`.

---

### [2025-09-16 10:00:00] - CHORE: Inicialización de reglas y changelog
*   **Acción:** Se crearon los archivos `.cursor-rules.json` y `changelog.md` para establecer las directrices de desarrollo y el registro de cambios para el trabajo agéntico de IA.
*   **Archivos Modificados:**
    *   `CREATE: changelog.md`
    *   `CREATE: prompt.md`

### [2025-01-27 15:30:00] - FEAT: Configuración inicial de Laravel Sanctum
*   **Acción:** Se configuró Laravel Sanctum para autenticación local y se añadió el trait HasApiTokens al modelo User.
*   **Archivos Modificados:**
    *   `UPDATE: app/Models/User.php` - Añadido trait HasApiTokens y declare(strict_types=1)

### [2025-01-27 16:00:00] - FEAT: Creación de migraciones de base de datos
*   **Acción:** Se crearon todas las migraciones de base de datos según las especificaciones de database_structure.md, incluyendo users, areas, services, academies, reservations, contributions, documents y audit_logs.
*   **Archivos Modificados:**
    *   `UPDATE: database/migrations/0001_01_01_000000_create_users_table.php` - Modificada para incluir role, sso_uid, is_solvent, solvent_until y soft deletes
    *   `CREATE: database/migrations/2025_09_16_134809_create_areas_table.php`
    *   `CREATE: database/migrations/2025_09_16_134820_create_area_schedules_table.php`
    *   `CREATE: database/migrations/2025_09_16_134824_create_services_table.php`
    *   `CREATE: database/migrations/2025_09_16_134828_create_academies_table.php`
    *   `CREATE: database/migrations/2025_09_16_134831_create_academy_schedules_table.php`
    *   `CREATE: database/migrations/2025_09_16_134836_create_academy_enrollments_table.php`
    *   `CREATE: database/migrations/2025_09_16_134840_create_invitations_table.php`
    *   `CREATE: database/migrations/2025_09_16_134844_create_reservations_table.php`
    *   `CREATE: database/migrations/2025_09_16_134847_create_contributions_table.php`
    *   `CREATE: database/migrations/2025_09_16_134853_create_documents_table.php`
    *   `CREATE: database/migrations/2025_09_16_134856_create_audit_logs_table.php`

### [2025-01-27 16:15:00] - FEAT: Creación de enumeraciones PHP (Enums)
*   **Acción:** Se crearon todas las enumeraciones PHP para estados y roles según las especificaciones de database_structure.md.
*   **Archivos Modificados:**
    *   `CREATE: app/Enums/UserRole.php`
    *   `CREATE: app/Enums/InvitationStatus.php`
    *   `CREATE: app/Enums/ReservationStatus.php`
    *   `CREATE: app/Enums/AcademyStatus.php`
    *   `CREATE: app/Enums/EnrollmentStatus.php`
    *   `CREATE: app/Enums/DocumentVisibility.php`
    *   `CREATE: app/Enums/ContributionStatus.php`

### [2025-01-27 16:30:00] - FEAT: Creación de modelos Eloquent con relaciones y casts
*   **Acción:** Se crearon todos los modelos Eloquent con sus relaciones, casts y propiedades fillable según las especificaciones de database_structure.md.
*   **Archivos Modificados:**
    *   `UPDATE: app/Models/User.php` - Añadido SoftDeletes, casts para role, is_solvent, solvent_until y fillable actualizado
    *   `CREATE: app/Models/Area.php`
    *   `CREATE: app/Models/AreaSchedule.php`
    *   `CREATE: app/Models/Service.php`
    *   `CREATE: app/Models/Academy.php`
    *   `CREATE: app/Models/AcademySchedule.php`
    *   `CREATE: app/Models/AcademyEnrollment.php`
    *   `CREATE: app/Models/Invitation.php`
    *   `CREATE: app/Models/Reservation.php`
    *   `CREATE: app/Models/Contribution.php`
    *   `CREATE: app/Models/Document.php`
    *   `CREATE: app/Models/AuditLog.php`

### [2025-01-27 16:45:00] - FEAT: Creación de controladores y rutas principales de la API
*   **Acción:** Se crearon los controladores AuthenticationController y UserController con sus respectivas rutas API versionadas bajo /api/v1/.
*   **Archivos Modificados:**
    *   `CREATE: app/Http/Controllers/Api/V1/AuthenticationController.php` - Manejo de login/logout con Sanctum
    *   `CREATE: app/Http/Controllers/Api/V1/UserController.php` - CRUD básico de usuarios
    *   `UPDATE: routes/api.php` - Rutas versionadas con prefijo /api/v1/ y middleware de autenticación

### [2025-01-27 15:45:00] - REFACTOR: Modificación de reglas de tipado estricto
*   **Acción:** Se eliminó la obligatoriedad de usar `declare(strict_types=1);` en todos los archivos PHP, manteniendo solo la recomendación de tipos explícitos en parámetros y retornos.
*   **Archivos Modificados:**
    *   `UPDATE: specs/development_rules.md` - Modificado principio rector de tipado estricto y ejemplos de código
    *   `UPDATE: .cursor/rules/laravel-project-rules.mdc` - Modificado principio rector de tipado estricto

