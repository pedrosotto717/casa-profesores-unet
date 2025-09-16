# Flujos de usuario (tentativos) — Casa del Profesor UNET

> **Nota**: Estos flujos describen el comportamiento esperado en base a la lógica de negocio definida en `main.md` y al esquema `database_structure.md`. Son **tentativos** y podrán ajustarse durante el desarrollo y validación con los actores de la UNET. &#x20;

---

## 0) Supuestos y reglas transversales

* **Autenticación**

  * Docentes/personal UNET: **SSO/LDAP CETI**.
  * Invitados/operadores sin SSO: autenticación local (Laravel **Sanctum**).
  * El **rol** condiciona permisos (RBAC).&#x20;

* **Solvencia**

  * Para **reservar** áreas o **invitar** terceros, el profesor debe estar **solvente** (aportes al día).
  * La solvencia se deriva de `contributions` y se cachea en `users.is_solvent / solvent_until`.&#x20;

* **Estados y trazabilidad**

  * Reservas: `pendiente | aprobada | rechazada | cancelada | completada | expirada`.
  * Invitaciones: `pendiente | aceptada | rechazada | expirada | revocada`.
  * Auditoría en `audit_logs` para cambios de estado.&#x20;

---

## 1) Flujo — **Administrador: ingresar al panel y operaciones base**

### 1.1 Ingreso al **Panel Administrativo**

**Precondiciones**

* Usuario con rol **administrador** (o **root**) creado previamente.
* Opción 1: SSO CETI. Opción 2: login local (Sanctum).&#x20;

**Pasos**

1. Ir a `/dashboard/admin` → redirección al flujo de **SSO CETI** si no hay sesión.
2. Tras autenticación, el backend emite sesión/token para el panel.
3. Se muestra el **Home Admin** con accesos a Áreas, Servicios, Academias, Reservas, Aportes, Documentos, Usuarios.

**Postcondiciones**

* Sesión activa con permisos RBAC de **administrador**.
* Eventos de acceso registrados (logs de aplicación).

---

### 1.2 Registrar una **Área** (`areas`)

**Precondiciones**

* Rol: **administrador** (o **root**).
* Conocer: nombre, descripción, capacidad, tarifa opcional (`hourly_rate`).&#x20;

**Pasos**

1. Navegar a **Áreas** → **Nueva Área**.
2. Completar: `name`, `slug` (autogenerable), `description`, `capacity`, `hourly_rate` (opcional), `is_active`.
3. Guardar.
4. (Opcional) Definir **horario base** en `area_schedules` (día de semana, rangos de hora, abierto/cerrado).&#x20;

**Postcondiciones**

* Registro en `areas` (y `area_schedules` si aplica).
* El área ya es visible para asociar **servicios** y para reservas (según disponibilidad).&#x20;

**Reglas**

* `name` y `slug` son **únicos**.
* `is_active` permite activar/desactivar el uso del área.&#x20;

---

### 1.3 Registrar un **Servicio** (`services`) vinculado a un Área

**Precondiciones**

* Área existente y **activa**.
* Rol: **administrador**.&#x20;

**Pasos**

1. Entrar a **Servicios** → **Nuevo Servicio**.
2. Seleccionar `area_id`, definir `name`, `description`, `requires_reservation` (true por defecto), `hourly_rate` (opcional), `is_active`.
3. Guardar.

**Postcondiciones**

* Servicio creado y listo para ser ofrecido sobre el área.
* Si se fija `hourly_rate` en el servicio, **tiene prioridad** sobre la tarifa del área.&#x20;

---

### 1.4 Registrar una **Academia** (`academies`) y sus horarios (`academy_schedules`)

**Precondiciones**

* Rol: **administrador**.
* Definir **instructor** (usuario) y nombre único.&#x20;

**Pasos**

1. **Academias** → **Nueva Academia** → completar `name`, `description`, `lead_instructor_id`, `status = activa`.
2. Guardar.
3. En **Horarios de Academia**, crear bloques recurrentes: `day_of_week`, `start_time`, `end_time`, `area_id` donde se dicta, `capacity` opcional.
4. Guardar.

**Postcondiciones**

* Academia registrada como **propia** de la Casa del Profesor.
* Horarios integrados al **calendario central** para evitar colisiones con reservas de área.&#x20;

**Reglas**

* Únicos recomendados por academia: (`day_of_week`, `start_time`, `end_time`).
* No permitir inscripciones si `capacity` está completo.&#x20;

---

## 2) Flujo — **Profesor: autenticar, invitar y reservar**

### 2.1 Autenticación del **Profesor** (SSO CETI)

**Precondiciones**

* Profesor con cuenta institucional activa (CETI).
* Perfil se crea/actualiza al primer login (onboarding).&#x20;

**Pasos**

1. Clic en **“Acceder con cuenta UNET (CETI)”**.
2. Completar login CETI → retorno con *claims* (email, id, nombre).
3. El backend crea/actualiza `users` y asigna rol **docente**.

**Postcondiciones**

* Acceso al **panel de profesor** (estado de solvencia, mis reservas, invitados, documentos).&#x20;

---

### 2.2 Registrar un **Invitado** (flujo de invitación `invitations`)

**Precondiciones**

* Profesor **solvente** (`users.is_solvent = true`).
* Conocer email y datos mínimos del invitado.&#x20;

**Pasos**

1. Panel **Profesor** → **Invitar** → ingresar `email`, mensaje opcional.
2. Crear **solicitud** (`status = pendiente`); se notifica a **Administrador**.
3. Administrador **aprueba/deniega** (`reviewed_by`, `reviewed_at`, `status`).
4. Si **aprueba**: se envía correo al invitado con token y **vigencia** (`expires_at`).
5. (Opcional) Si el invitado completa registro, se crea/relaciona `users` con rol **invitado**.

**Postcondiciones**

* Invitación con `status = aceptada` y traza en `audit_logs`.
* Invitado autorizado durante la vigencia; acceso **limitado**.&#x20;

**Reglas**

* Solo **administrador** puede cambiar a `aceptada/rechazada`.
* Todas las invitaciones están asociadas al **profesor responsable** (`inviter_user_id`).&#x20;

---

### 2.3 **Reservar** un Área (`reservations`)

**Precondiciones**

* Profesor **solvente** y autenticado.
* Área con disponibilidad según `area_schedules` y sin colisión con academias/eventos.&#x20;

**Pasos**

1. Panel **Profesor** → **Reservar** → seleccionar **Área** y rango de fecha/hora (`starts_at`, `ends_at`).
2. El sistema valida **solvencia** y **disponibilidad** (bloqueo de solapamientos).
3. Crear **solicitud** (`status = pendiente`).
4. **Administrador** revisa y **aprueba/deniega**.

   * Si aprueba: set `approved_by`, `reviewed_at`, `status = aprobada`.
   * (Opcional) requerir **anticipo** según política.
5. Notificación al profesor. El día del evento, marcar **asistencia/uso** y luego **cerrar**.

**Postcondiciones**

* Reserva con estado final `completada` (o `cancelada`/`no presentada` según reglas internas).
* Trazabilidad completa en `audit_logs`.&#x20;

**Reglas**

* Los cambios de estado los ejecuta **administración**; el sistema impide **dobles reservas** (colisiones por área/tiempo).
* La terminología en comprobantes/avisos usa **aportes**, no “alquiler”.&#x20;

---

## 3) Flujo — **Administrador: registrar Aportes** (`contributions`) y actualizar Solvencia

> *Este flujo no fue requerido explícitamente en tu lista, pero suele ser crítico para habilitar reservas/invitaciones; lo dejamos aquí para cerrar el ciclo funcional.*

**Precondiciones**

* Rol: **administrador**.
* Comprobante del aporte (si existe).&#x20;

**Pasos**

1. Panel **Admin** → **Aportes** → **Nuevo**.
2. Seleccionar `user_id`, `period` (convención: día 1 del mes), `amount`, `status`.
3. (Opcional) Adjuntar `receipt_url`; **Guardar**.
4. Observers/Jobs actualizan `users.is_solvent` y `solvent_until`.&#x20;

**Postcondiciones**

* Estado de **solvencia** del usuario actualizado; habilita acciones (reservar/invitar).
* Registro de auditoría del cambio.

---

## 4) Eventos, notificaciones y auditoría

* **Eventos que notifican**:

  * Invitación aprobada/denegada.
  * Reserva aprobada/denegada/cancelada.
  * Publicación de documento.
  * Vencimiento próximo de **solvencia**.&#x20;

* **Canais**: correo (colas/queue); a futuro, notificaciones push.

* **Auditoría**: cada transición relevante escribe en `audit_logs` (`entity_type`, `entity_id`, `action`, `user_id`, `created_at`).&#x20;

---

## 5) Criterios de aceptación por flujo (resumen)

* **Admin—Área/Servicio/Academia**

  * Crear, editar, activar/desactivar.
  * Horarios válidos, sin superposición inconsistente.
  * Datos persistidos en tablas correspondientes.

* **Profesor—Invitación**

  * Requiere solvencia vigente.
  * Estados y vigencia correctamente gestionados.
  * Aprobación exclusiva de **administrador**.

* **Profesor—Reserva**

  * Requiere solvencia vigente.
  * No permite solapamientos en el mismo `area_id`.
  * Estados y aprobaciones auditados.

---

### Siguiente paso sugerido

Si te parece, lo convierto en `flows.md` y lo sumo a tu base (junto con un **diagrama de estados** simple para reservas e invitaciones).
