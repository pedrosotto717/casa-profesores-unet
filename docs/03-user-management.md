# 03 - Gestión de Usuarios, Roles y Permisos

> **Propósito**: Este documento unifica toda la lógica de negocio, flujos y reglas relacionadas con la creación, autenticación y gestión de usuarios, sus roles y sus estados. Reemplaza y consolida la información de `specs/business_rules.md` y `docs/user-registration-and-invitation-system.md`.

---

## 1. Roles y Estados

El sistema de permisos se basa en una combinación de **Rol** y **Estado**.

### Roles de Usuario (`role`)

| Rol | Descripción | Vía de Creación |
| :--- | :--- | :--- |
| **`administrador`** | Control total del sistema. | Asignación manual por otro admin. |
| **`profesor`** | Docente de la UNET. Puede reservar e invitar. | Auto-registro + Aprobación de Admin. |
| **`estudiante`** | Alumno de la UNET. Puede reservar. | Auto-registro + Aprobación de Admin. |
| **`invitado`** | Familiar o amigo de un profesor. Acceso limitado. | A través de una invitación aprobada. |
| **`instructor`** | Experto externo que dirige academias. | Creación directa por Admin. |
| **`obrero`** | Personal de mantenimiento y operaciones. | Creación directa por Admin. |
| **`usuario`** | **Rol de transición.** Asignado en el auto-registro. **No puede iniciar sesión.** |

### Estados de Usuario (`status`)

| Estado | Descripción | Implicaciones de Acceso |
| :--- | :--- | :--- |
| **`aprobacion_pendiente`** | Estado inicial tras el auto-registro. | **No puede iniciar sesión.** La cuenta espera revisión de un administrador. |
| **`solvente`** | Usuario activo y al día con sus aportes. | **Acceso completo** a las funcionalidades de su rol (reservar, invitar, etc.). |
| **`insolvente`** | Usuario activo pero con aportes vencidos. | Puede iniciar sesión y usar funciones básicas, pero **no puede crear reservas ni enviar invitaciones**. |
| **`rechazado`** | La solicitud de registro fue denegada. | **No puede iniciar sesión.** Este estado es final. |

---

## 2. Flujos de Creación de Usuarios

Existen tres maneras principales en las que un usuario puede ser creado en el sistema.

### Flujo 1: Auto-Registro (Para Profesores y Estudiantes)

Este flujo está diseñado para miembros de la comunidad UNET con correos institucionales.

1.  **Endpoint**: `POST /api/v1/auth/register`
2.  **Acceso**: Público.
3.  **Payload Requerido**:
    - `name` (string)
    - `email` (string, **debe** terminar en `@unet.edu.ve`)
    - `password` (string, min: 8 caracteres)
    - `aspired_role` (enum: `profesor` o `estudiante`)
    - `responsible_email` (string, opcional, requerido si `aspired_role` es `estudiante`)

4.  **Lógica de Negocio**:
    - El sistema crea un `User` con `role: 'usuario'` y `status: 'aprobacion_pendiente'`.
    - Los campos `aspired_role` y `responsible_email` se guardan para la revisión del administrador.
    - Se envía una notificación a todos los administradores sobre la nueva solicitud.
    - **El usuario no recibe un token y no puede iniciar sesión.**

5.  **Aprobación (Admin)**:
    - Un administrador revisa la solicitud (ej. `GET /api/v1/admin/pending-registrations`).
    - El admin aprueba cambiando el estado a `solvente` o `insolvente` (`PUT /api/v1/users/{id}`).
    - **Automatización**: Al cambiar el `status`, el sistema promueve al usuario a su `aspired_role` (`profesor` o `estudiante`) y limpia los campos temporales.
    - El usuario recibe una notificación de que su cuenta está activa.

### Flujo 2: Sistema de Invitaciones (Para Invitados)

Permite a los profesores (`role: 'profesor'`) traer a terceros al sistema.

1.  **Creación de Invitación**:
    - **Endpoint**: `POST /api/v1/invitations`
    - **Permisos**: Solo para `profesor` con `status: 'solvente'`.
    - **Payload**: `name` (string), `email` (string, no institucional), `message` (string, opcional).
    - **Lógica**: Se crea un registro en `invitations` con estado `pendiente` y un token único. Se notifica a los administradores.

2.  **Aprobación de Invitación (Admin)**:
    - **Endpoint**: `PUT /api/v1/invitations/{id}/approve`
    - **Lógica**: Un administrador aprueba la invitación.
    - **Automatización**: El sistema crea un nuevo `User` con:
        - `role: 'invitado'`
        - `status: 'solvente'`
        - `responsible_email`: el email del profesor que lo invitó.
    - Se envía una notificación al nuevo usuario para que establezca su contraseña (`POST /api/v1/auth/set-password`) y otra al profesor que lo invitó.

### Flujo 3: Creación Directa por Administrador

Para roles que no encajan en los flujos anteriores (Instructores, Obreros, o incluso otros Admins).

1.  **Endpoint**: `POST /api/v1/users`
2.  **Permisos**: Solo para `administrador`.
3.  **Payload**: `name`, `email`, `password`, `role`, `status`.
4.  **Lógica**: El usuario se crea directamente con el rol y estado especificados, sin necesidad de un flujo de aprobación. La cuenta queda activa de inmediato.

---

## 3. Gestión de Estado y Permisos

### Cambio de Estado

Un administrador puede cambiar el estado de cualquier usuario en cualquier momento a través del endpoint `PUT /api/v1/users/{id}` o usando el comando de Artisan.

- **Comando Artisan**: `php artisan user:change-status <email> <status>`
  - **Propósito**: Facilita cambios de estado rápidos desde la terminal, especialmente para aprobar usuarios o actualizar su solvencia.
  - **Auditoría**: La acción queda registrada en `audit_logs`.

### Matriz de Permisos Clave

| Acción | Rol Requerido | Estado Requerido | Endpoint Asociado |
| :--- | :--- | :--- | :--- |
| **Crear Reserva** | `profesor`, `estudiante`, `invitado` | `solvente` | `POST /api/v1/reservations` |
| **Enviar Invitación** | `profesor` | `solvente` | `POST /api/v1/invitations` |
| **Gestionar Alumnos** | `instructor`, `administrador` | `solvente` | `POST /api/v1/academies/{id}/students` |
| **Aprobar/Rechazar** | `administrador` | (Cualquiera) | Endpoints en `middleware('admin')` |

---

## 4. Autenticación y Contraseña

- **Login**: `POST /api/v1/login`.
- **Logout**: `POST /api/v1/logout` (requiere autenticación).
- **Olvido de Contraseña**: `POST /api/v1/auth/forgot-password`. Envía un código de 6 dígitos al email del usuario.
- **Reseteo de Contraseña**: `POST /api/v1/auth/reset-password`. Requiere `email`, `code` y la nueva `password`.
- **Establecer Contraseña (para invitados)**: `POST /api/v1/auth/set-password`. Permite a un usuario creado por un admin o por invitación establecer su contraseña por primera vez usando un token seguro.
