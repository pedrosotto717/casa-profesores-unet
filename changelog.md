
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

### [2025-01-27 17:00:00] - FIX: Corrección de nombres de índices únicos en migraciones
*   **Acción:** Se corrigieron los nombres de índices únicos que eran demasiado largos para MySQL, especificando nombres personalizados más cortos.
*   **Archivos Modificados:**
    *   `UPDATE: database/migrations/2025_09_16_134820_create_area_schedules_table.php` - Añadido nombre personalizado 'area_schedules_unique' para índice único
    *   `UPDATE: database/migrations/2025_09_16_134831_create_academy_schedules_table.php` - Añadido nombre personalizado 'academy_schedules_unique' para índice único
    *   `UPDATE: database/migrations/2025_09_16_134836_create_academy_enrollments_table.php` - Añadido nombre personalizado 'academy_enrollments_unique' para índice único
    *   `UPDATE: database/migrations/2025_09_16_134847_create_contributions_table.php` - Añadido nombre personalizado 'contributions_unique' para índice único

### [2025-01-27 17:30:00] - FEAT: Complete user registration system implementation
*   **Action:** Implemented a complete kit for user registration with layered architecture, initial admin seeder and CLI commands for role management.
*   **Files Modified:**
    *   `CREATE: app/Http/Requests/Auth/RegisterUserRequest.php` - Form Request for registration validation
    *   `CREATE: app/Services/UserService.php` - Service with business logic for user registration and promotion
    *   `CREATE: app/Http/Controllers/Api/V1/Auth/RegisterController.php` - Controller for registration endpoint
    *   `CREATE: database/seeders/InitialAdminSeeder.php` - Seeder to create first administrator
    *   `CREATE: app/Console/Commands/UserPromote.php` - CLI command for role promotion
    *   `CREATE: app/Http/Middleware/EnsureRole.php` - Middleware for role-based access control
    *   `UPDATE: routes/api.php` - Added POST /api/v1/auth/register endpoint
    *   `UPDATE: config/app.php` - Added configuration variables for initial administrator
    *   `UPDATE: bootstrap/app.php` - Registered 'role' middleware for access control

### [2025-01-27 17:45:00] - REFACTOR: Convert all comments and code to English
*   **Action:** Updated all comments, documentation strings and error messages to English for consistency and best practices.
*   **Files Modified:**
    *   `UPDATE: app/Http/Requests/Auth/RegisterUserRequest.php` - Added English PHPDoc comments
    *   `UPDATE: app/Services/UserService.php` - Converted comments and error messages to English
    *   `UPDATE: app/Http/Controllers/Api/V1/Auth/RegisterController.php` - Added English PHPDoc comments
    *   `UPDATE: database/seeders/InitialAdminSeeder.php` - Converted comments to English
    *   `UPDATE: app/Console/Commands/UserPromote.php` - Converted comments and messages to English
    *   `UPDATE: app/Http/Middleware/EnsureRole.php` - Added English PHPDoc comments and fixed typo

### [2025-01-27 18:00:00] - REFACTOR: Use UserRole enum instead of hardcoded role values
*   **Action:** Replaced hardcoded role arrays with UserRole enum values to eliminate code duplication and ensure consistency.
*   **Files Modified:**
    *   `UPDATE: app/Services/UserService.php` - Use UserRole::Docente for default role and array_column(UserRole::cases(), 'value') for validation
    *   `UPDATE: database/seeders/InitialAdminSeeder.php` - Use UserRole::Docente and UserRole::Administrador enum values

### [2025-01-27 18:15:00] - CHORE: Update DatabaseSeeder and disable test user creation
*   **Action:** Updated DatabaseSeeder to call InitialAdminSeeder and commented out test user creation for cleaner production setup.
*   **Files Modified:**
    *   `UPDATE: database/seeders/DatabaseSeeder.php` - Added call to InitialAdminSeeder and commented out test user factory

### [2025-01-27 18:30:00] - FEAT: Create idempotent seeders for base catalogs
*   **Action:** Created comprehensive seeders for areas, services, academies and documents based on database_structure.md and cpu_reglamento_negocio.md specifications.
*   **Files Modified:**
    *   `CREATE: database/seeders/AreasSeeder.php` - Seeds all areas from specs with proper slugs and placeholders
    *   `CREATE: database/seeders/ServicesSeeder.php` - Creates "Reserva [Área]" services only for reservable areas (excludes Restaurant and Parque infantil per reglamento)
    *   `CREATE: database/seeders/AcademiesSeeder.php` - Seeds institutional academies (natación, karate, yoga, etc.)
    *   `CREATE: database/seeders/DocumentsSeeder.php` - Creates institutional document placeholder
    *   `UPDATE: database/seeders/DatabaseSeeder.php` - Added calls to all new seeders in proper order for foreign key dependencies

### [2025-01-27 18:45:00] - ENHANCE: Add capacities and descriptions to AreasSeeder
*   **Action:** Enhanced AreasSeeder with detailed capacity and description data extracted from cpu_reglamento_negocio.md specifications.
*   **Files Modified:**
    *   `UPDATE: database/seeders/AreasSeeder.php` - Added associative array with capacities and descriptions for all areas based on reglamento data

### [2025-01-27 19:00:00] - FEAT: Complete email verification system implementation
*   **Action:** Implemented a complete email verification system for user registration with secure token-based verification, email notifications, and API endpoints.
*   **Files Modified:**
    *   `CREATE: database/migrations/2025_01_27_190000_add_email_verification_token_to_users_table.php` - Added email_verification_token field to users table
    *   `CREATE: app/Mail/EmailVerificationNotification.php` - Mailable class for sending verification emails with queue support
    *   `CREATE: resources/views/emails/email-verification.blade.php` - HTML email template for verification with institutional branding
    *   `CREATE: app/Http/Controllers/Api/V1/EmailVerificationController.php` - Controller for email verification endpoint
    *   `UPDATE: app/Models/User.php` - Added email_verification_token to fillable, added hasVerifiedEmail(), markEmailAsVerified(), and generateEmailVerificationToken() methods
    *   `UPDATE: app/Services/UserService.php` - Enhanced register() method to send verification email, added sendEmailVerification() and verifyEmail() methods
    *   `UPDATE: routes/api.php` - Added POST /api/v1/email/verify route for email verification

### [2025-01-27 19:15:00] - REFACTOR: Remove resend email verification functionality
*   **Action:** Removed the resend email verification functionality to simplify the system and keep only essential verification features.
*   **Files Modified:**
    *   `UPDATE: app/Http/Controllers/Api/V1/EmailVerificationController.php` - Removed resend() method
    *   `UPDATE: app/Services/UserService.php` - Removed resendEmailVerification() method
    *   `UPDATE: routes/api.php` - Removed POST /api/v1/email/resend route

### [2025-01-27 19:30:00] - CONFIG: Configure Resend email service integration
*   **Action:** Configured Laravel to use Resend as the default email service and created a test command for email verification.
*   **Files Modified:**
    *   `UPDATE: config/mail.php` - Changed default mailer from 'log' to 'resend'
    *   `CREATE: app/Console/Commands/TestEmailCommand.php` - Command to test email sending with Resend service

### [2025-01-27 19:45:00] - REFACTOR: Simplify email verification to use direct Resend approach
*   **Action:** Simplified email verification implementation to use Resend directly without unnecessary route dependencies and URL generation complexity.
*   **Files Modified:**
    *   `UPDATE: app/Services/UserService.php` - Simplified sendEmailVerification() method to use Resend::emails()->send() directly
    *   `UPDATE: app/Mail/EmailVerificationNotification.php` - Added render() method for Resend compatibility
    *   `UPDATE: app/Console/Commands/TestEmailCommand.php` - Updated to use Resend directly instead of Mail facade

### [2025-01-27 20:00:00] - FIX: Correct Resend 'from' field format
*   **Action:** Fixed the 'from' field format in Resend email sending to use only the email address instead of the combined name and email format.
*   **Files Modified:**
    *   `UPDATE: app/Services/UserService.php` - Changed 'from' field from 'Name <email>' to just 'email' format
    *   `UPDATE: app/Console/Commands/TestEmailCommand.php` - Updated 'from' field format for Resend compatibility

### [2025-01-27 20:15:00] - REFACTOR: Switch from Resend facade to Mail facade for email sending
*   **Action:** Changed email sending implementation to use Laravel's Mail facade instead of Resend facade directly, maintaining existing try-catch blocks and logging for testing purposes.
*   **Files Modified:**
    *   `UPDATE: app/Services/UserService.php` - Replaced Resend::emails()->send() with Mail::to()->send() while keeping existing logging and error handling
    *   `UPDATE: app/Console/Commands/TestEmailCommand.php` - Updated to use Mail facade instead of Resend facade

### [2025-09-23 22:31:00] - REFACTOR: Update user roles enum and database schema
*   **Action:** Updated user roles system to change 'Docente' to 'Profesor', added new 'Instructor' role for academy instructors, and updated default registration role to 'Usuario'.
*   **Files Modified:**
    *   `UPDATE: app/Enums/UserRole.php` - Changed Docente to Profesor, added Instructor role, maintained Usuario as basic role
    *   `CREATE: database/migrations/2025_09_23_223125_update_user_roles_enum.php` - Migration to update users table enum with new roles and default value
    *   `UPDATE: app/Services/UserService.php` - Changed default registration role from UserRole::Docente to UserRole::Usuario

### [2025-09-23 22:40:00] - FIX: Resolve Resend email configuration error
*   **Action:** Fixed the Resend email service configuration error that was causing null values in X-Resend-Email headers. Switched from Resend transport to SMTP configuration for better compatibility.
*   **Files Modified:**
    *   `UPDATE: .env` - Fixed duplicate mail configuration entries, switched to SMTP configuration for Resend service
    *   `UPDATE: config/mail.php` - Added key configuration to resend transport settings
    *   `UPDATE: app/Mail/EmailVerificationNotification.php` - Temporarily removed ShouldQueue interface for testing, then restored it

### [2025-09-23 23:15:00] - FIX: Fix null header values in Resend email service
*   **Action:** Fixed the "Headers::addTextHeader(): Argument #2 must be of type string, null given" error by adding default values to mail configuration and improving error handling in email sending.
*   **Files Modified:**
    *   `UPDATE: config/mail.php` - Added default values for from.address, from.name, and resend.key to prevent null values
    *   `UPDATE: app/Mail/EmailVerificationNotification.php` - Added explicit from configuration in constructor to ensure values are set
    *   `UPDATE: app/Services/UserService.php` - Enhanced error logging and added configuration validation in sendEmailVerification() method

### [2025-09-24 10:05:00] - FIX: Simplify email verification mailable build process
*   **Action:** Simplified the email verification mailable to use the classic `build()` method, ensuring consistent from/subject/view configuration and avoiding duplicated rendering logic that could trigger null header values with Resend.
*   **Files Modified:**
    *   `UPDATE: app/Mail/EmailVerificationNotification.php`

### [2025-01-27 20:30:00] - REMOVE: Complete email verification system removal
*   **Action:** Completely removed all email verification functionality from the user registration system as requested. This includes all related files, methods, routes, and database migrations.
*   **Files Modified:**
    *   `UPDATE: app/Services/UserService.php` - Removed email verification logic from register() method, deleted sendEmailVerification() and verifyEmail() methods
    *   `DELETE: app/Mail/EmailVerificationNotification.php` - Deleted mailable class
    *   `DELETE: app/Http/Controllers/Api/V1/EmailVerificationController.php` - Deleted controller
    *   `UPDATE: routes/api.php` - Removed email verification routes and imports
    *   `UPDATE: app/Models/User.php` - Removed email_verification_token from fillable, deleted hasVerifiedEmail(), markEmailAsVerified(), and generateEmailVerificationToken() methods
    *   `DELETE: database/migrations/2025_01_27_190000_add_email_verification_token_to_users_table.php` - Deleted migration
    *   `DELETE: resources/views/emails/email-verification.blade.php` - Deleted email template

