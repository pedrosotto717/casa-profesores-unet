# Reglas de Negocio Específicas – Sistema de Gestión CPU-UNET

> **Propósito:** Este documento resume las reglas de negocio clave del sistema, enfocándose en roles, permisos y acceso a funcionalidades. Sirve como una guía rápida para entender las restricciones y lógicas implementadas.

---

## 1. Reglas por Rol de Usuario

El acceso a las funcionalidades está estrictamente controlado por el rol asignado a cada usuario.

| Rol | Descripción y Reglas Clave |
| :--- | :--- |
| **Administrador** | - **Control total:** Puede gestionar todas las entidades (usuarios, áreas, academias, etc.).<br>- **Único rol que puede:** aprobar/rechazar registros, invitaciones y reservas. Asignar roles a otros usuarios. |
| **Profesor** | - **Acciones principales:** Puede solicitar reservas e iniciar invitaciones para terceros.<br>- **Requisito:** Debe tener estado `solvente` para poder realizar estas acciones. |
| **Estudiante** | - **Acciones principales:** Puede solicitar reservas.<br>- **Requisito:** Debe tener estado `solvente`. Su registro inicial requiere la validación de un profesor responsable. |
| **Instructor** | - **Acceso limitado:** Solo puede gestionar la información y los estudiantes de las academias que lidera. |
| **Invitado** | - **Acceso restringido:** No puede solicitar reservas ni iniciar invitaciones. Su acceso es temporal y está vinculado a un profesor responsable. |
| **Obrero** | - Rol con acceso limitado a funciones operativas (a definir). No participa en los flujos principales de reservas o invitaciones. |
| **Usuario** | - **Rol de transición:** Es el estado inicial de un usuario tras el auto-registro. **No puede iniciar sesión** ni realizar ninguna acción hasta ser aprobado por un administrador. |

## 2. Reglas de Acceso y Estados

El acceso a las funcionalidades no solo depende del rol, sino también del estado (`status`) de la cuenta del usuario.

- **Estado `aprobacion_pendiente`**
  - Asignado por defecto a todos los usuarios que se registran a través del formulario público.
  - **Regla:** El usuario no puede iniciar sesión en el sistema.

- **Estado `solvente`**
  - El usuario está al día con sus aportes.
  - **Regla:** Tiene acceso completo a todas las funcionalidades permitidas por su rol (ej. un `profesor` solvente puede reservar e invitar).

- **Estado `insolvente`**
  - El usuario puede iniciar sesión, pero no está al día con sus aportes.
  - **Regla:** Tiene bloqueadas las acciones clave como crear nuevas reservas o enviar invitaciones.

- **Estado `rechazado`**
  - La solicitud de registro del usuario fue rechazada por un administrador.
  - **Regla:** El usuario no puede iniciar sesión. Este estado es final.

## 3. Reglas por Flujo y Endpoint

#### 3.1. Auto-Registro (`POST /api/v1/auth/register`)
- **Regla 1:** El correo electrónico del aspirante debe ser institucional (terminar en `@unet.edu.ve`).
- **Regla 2:** Si el rol aspirado es `estudiante`, es **obligatorio** proporcionar el correo de un `profesor` responsable.
- **Resultado:** Se crea una cuenta con rol `usuario` y estado `aprobacion_pendiente`.

#### 3.2. Creación de Invitaciones (`POST /api/v1/invitations`)
- **Regla 1:** Solo los usuarios con rol `profesor` y estado `solvente` pueden ejecutar esta acción.
- **Regla 2:** La invitación creada queda en estado `pendiente` y debe ser aprobada por un `administrador`.

#### 3.3. Creación de Reservas (`POST /api/v1/reservations`)
- **Regla 1:** Solo usuarios con un rol habilitado (`profesor`, `estudiante`, etc.) y estado `solvente` pueden solicitar una reserva.
- **Regla 2:** El sistema debe validar que el horario solicitado no se solape con reservas ya aprobadas o con los horarios de las academias.
- **Regla 3:** La reserva se crea con estado `pendiente` y requiere aprobación de un `administrador`.

#### 3.4. Aprobaciones (Endpoints de Administrador)
- **Aprobación de Usuarios:** Al cambiar el estado de un usuario de `aprobacion_pendiente` a `solvente` o `insolvente`, el sistema automáticamente le asigna el rol al que aspiraba (`profesor` o `estudiante`).
- **Aprobación de Reservas/Invitaciones:** Solo los administradores pueden cambiar el estado de `pendiente` a `aprobada` o `rechazada`.
