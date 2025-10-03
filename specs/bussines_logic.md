Claro que sí. Basado en toda nuestra discusión y las últimas precisiones, he creado el documento `business_logic.md`.

Este archivo está diseñado para ser la **fuente de verdad definitiva** sobre las reglas de negocio del sistema, consolidando toda la lógica en un solo lugar de manera estructurada y clara para el equipo de desarrollo.

---

### `business_logic.md`

```markdown
# Lógica de Negocio Consolidada – Sistema de Gestión CPU-UNET

> **Propósito de este Documento**
> Este archivo sirve como la fuente única y definitiva de verdad (Single Source of Truth) para la lógica de negocio del sistema de la Casa del Profesor Universitario. Consolida todas las reglas, flujos y definiciones acordadas, y debe ser utilizado como guía principal para el desarrollo del backend y frontend. Este documento prevalece sobre cualquier especificación anterior.

---

## 1. Usuarios, Roles y Permisos

### 1.1. Definición de Roles del Sistema

El sistema opera con siete roles claramente definidos. El acceso a las funcionalidades está estrictamente controlado por el rol asignado.

| Rol             | Descripción Clave                                                                                               | Vía de Creación Típica                                     |
| :-------------- | :-------------------------------------------------------------------------------------------------------------- | :--------------------------------------------------------- |
| **Administrador** | Control total sobre el sistema. Aprueba registros, gestiona entidades (áreas, academias) y asigna roles.        | Asignación manual o mediante Seeder/CLI.                   |
| **Profesor**      | Docente de la UNET. Es el único rol que puede **iniciar invitaciones**. Puede solicitar reservas.                | Auto-registro (aprobado por Admin) o creación directa.     |
| **Estudiante**    | Alumno de la UNET. Puede solicitar reservas. Requiere un profesor responsable para su registro.                  | Auto-registro (aprobado por Admin) o creación directa.     |
| **Instructor**    | Experto externo que dirige una academia (ej. natación). Gestiona únicamente su academia.                         | Creación directa por un Administrador.                     |
| **Obrero**        | Personal de mantenimiento de la UNET con acceso limitado a funciones operativas.                                | Creación directa por un Administrador.                     |
| **Invitado**      | Familiar o amigo de un profesor. Acceso temporal y limitado. **No puede solicitar reservas**.                      | Creado a través del flujo de **Invitación** (Profesor + Admin). |
| **Usuario**       | **Rol de transición**. Estado inicial tras el auto-registro. **No puede iniciar sesión ni realizar acciones**. | Auto-registro.                                             |

### 1.2. Ciclo de Vida y Estados del Usuario

Para gestionar el acceso y la solvencia, la cuenta de un usuario se rige por un único campo `status`.

*   **`aprobacion_pendiente`**: Estado por defecto de cualquier cuenta creada vía **auto-registro**. El usuario no puede iniciar sesión.
*   **`solvente`**: El usuario está activo y al día con sus aportes. Tiene acceso completo a las funciones de su rol (ej. reservar, invitar).
*   **`insolvente`**: El usuario está activo pero no al día con sus aportes. Puede iniciar sesión, pero tiene bloqueadas las acciones clave como reservar o invitar.
*   **`rechazado`**: El usuario ha sido rechazado por un administrador. No puede iniciar sesión ni realizar acciones. Este estado es final y no permite transiciones a otros estados.

### 1.3. Vías de Creación de Usuarios

Existen tres y solo tres maneras en que un usuario puede ser creado en el sistema:

#### 1. Creación Directa por Administrador
*   **Flujo:** Un administrador utiliza el panel de gestión para crear una cuenta de usuario.
*   **Reglas:** Puede asignar cualquier rol y estado (`solvente`/`insolvente`) directamente. Es el método para registrar `Instructores` y `Obreros`.

#### 2. Creación por Invitación
*   **Flujo:** Un `Profesor` solvente envía una invitación a un externo. Un `Administrador` la aprueba.
*   **Resultado:** El sistema crea una cuenta con el **rol `Invitado`**. El profesor que inició la invitación queda registrado como su responsable.

#### 3. Creación por Auto-Registro (La Vía Más Compleja)
*   **Flujo:** Un aspirante a `Profesor` o `Estudiante` utiliza el formulario de registro público.
*   **Reglas y Validaciones Inquebrantables:**
    1.  **Rol Aspirado:** El usuario debe seleccionar si aspira a ser "Profesor" o "Estudiante". Este dato se guarda en el campo `aspired_role`.
    2.  **Correo Institucional:** El correo electrónico proporcionado por el aspirante **debe** terminar en `@unet.edu.ve`.
    3.  **Profesor Responsable (Condicional):**
        *   Si el `aspired_role` es **"Estudiante"**, es **obligatorio** proporcionar el correo de un "Profesor Responsable".
        *   Este correo de responsable también debe ser institucional (`@unet.edu.ve`).
        *   Si el `aspired_role` es "Profesor", no se requiere un responsable.
*   **Resultado:** Se crea una cuenta de usuario con los siguientes valores por defecto:
    *   `role`: `usuario`
    *   `status`: `aprobacion_pendiente`
    *   `aspired_role`: (El seleccionado en el formulario)
    *   `responsible_email`: (El correo del profesor, si aplica)

### 1.4. Flujo de Aprobación Administrativa
Este es el proceso obligatorio para activar las cuentas creadas por auto-registro.

1.  **Notificación:** El sistema alerta a los administradores sobre una nueva solicitud de registro.
2.  **Revisión:** El administrador accede a la lista de usuarios pendientes, donde debe poder ver el rol aspirado y el correo del responsable.
3.  **Decisión:**
    *   **Aprobar:** El administrador cambia el `status` a `insolvente` (estado activo inicial por defecto) y actualiza el `role` del usuario al valor guardado en `aspired_role` (`profesor` o `estudiante`).
    *   **Rechazar:** El administrador cambia el `status` a `rechazado`. El usuario mantiene el rol `usuario` y el `aspired_role` original. No puede iniciar sesión.
4.  **Activación:** Una vez aprobado, el usuario puede iniciar sesión por primera vez. Si es rechazado, el usuario queda bloqueado permanentemente.

---

## 2. Lógica de Reservas e Instalaciones

### 2.1. Gestión de Áreas
*   El concepto de "Servicios" ha sido **eliminado**. La lógica se simplifica en "Áreas".
*   Cada `Area` en la base de datos tiene una bandera booleana `is_reservable` que determina si puede ser solicitada por los usuarios.
*   Áreas como el Restaurante o el Parque Infantil tendrán esta bandera en `false`.

### 2.2. Flujo de Solicitud y Aprobación de Reservas

1.  **Requisito Previo:** Solo los usuarios con un rol habilitado (ej. `Profesor`, `Estudiante`) y con `status: 'solvente'` pueden iniciar una solicitud de reserva.
2.  **Solicitud:** El usuario selecciona un área reservable, fecha y hora en el calendario de disponibilidad.
3.  **Creación de Reserva:** El sistema crea un registro de reserva con el estado `pendiente`.
4.  **Aprobación Administrativa:** Un `Administrador` revisa la solicitud. Puede aprobarla o rechazarla.
5.  **Confirmación:** El estado de la reserva se actualiza a `aprobada` o `rechazada`, y se notifica al usuario.

### 2.3. Estados de una Reserva

Toda reserva pasará por un ciclo de vida definido por los siguientes estados:
*   `pendiente`
*   `aprobada`
*   `rechazada`
*   `cancelada`
*   `completada`
*   `expirada`
```