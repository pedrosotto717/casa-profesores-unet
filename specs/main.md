# main.md

# Sistema Web para la Gestión de la Casa del Profesor Universitario de la UNET

> **Propósito de este documento**  
> Servir como **base de conocimiento** del proyecto (visión funcional y lógica de negocio) para guiar el diseño, la implementación y las decisiones operativas. Aquí no listamos requisitos exhaustivos ni contratos de API; esos quedarán en archivos separados (`backend-requirements.md`, `frontend-requirements.md`, etc.).

---

## 1) Descripción General

La **Casa del Profesor Universitario de la UNET** es un espacio institucional y **sin fines de lucro** dedicado al bienestar del personal docente y su núcleo familiar, con acceso controlado a invitados bajo responsabilidad del profesor. Hoy su operación depende de procesos **manuales** (registro en cuadernos, planificación en papel, cobros en efectivo), lo que dificulta el control de acceso, la trazabilidad de **aportes** (no “membresías”) y la eficiencia administrativa.

El proyecto plantea un **sistema web integral** con **backend en Laravel 12 (PHP 8.3)** y **frontend en Next.js (React + TypeScript)** para centralizar:

- Control de acceso (SSO institucional + autenticación local de respaldo).  
- Gestión de usuarios, roles y **invitados**.  
- **Reservas** de espacios con calendario, disponibilidad y aprobación.  
- Registro y verificación de **aportes** (solvencia).  
- Publicación y consulta de **documentos institucionales** (reglamentos, actas, instructivos).  
- Comunicación y **notificaciones**.  
- Paneles de **métricas** para la administración.  

> Terminología legal clave: **aportes** o **aportes de mantenimiento** (no “alquiler”, no “membresía”). La Casa del Profesor es parte de la UNET y mantiene su carácter institucional.

---

## 2) Alcance y objetivos sintetizados

- **Alcance funcional:** landing informativa pública; panel privado por rol; módulos de reservas, aportes, invitados, academias/escuelas, documentos y métricas.  
- **Objetivo general:** implementar un sistema que **centralice y automatice** procesos de gestión de usuarios, control de acceso, reservas, aportes y comunicación.  
- **Impacto esperado:** eficiencia operativa, trazabilidad, transparencia, mejor experiencia de usuario y soporte a la toma de decisiones.

---

## 3) Arquitectura Tecnológica

### 3.1 Backend (Laravel 12, PHP 8.3)
- **Patrón:** API RESTful (stateless), organizada por dominios (módulos) y servicios de aplicación.  
- **Autenticación:**  
  - **SSO/LDAP CETI** (autenticación centralizada para profesores y personal UNET).  
  - **Autenticación local de respaldo** con **Laravel Sanctum** (tokens SPA) para invitados u operadores sin credenciales institucionales.  
- **BD principal:** MySQL 8 (datos transaccionales).  
- **Cache:** Redis (tokens, sesiones SSO, catálogos, vistas de disponibilidad).  
- **Mail/Notifications:** Mailer + colas (Laravel Queue) para correos y notificaciones asincrónicas.  
- **Logging/Auditoría:** Monolog + tabla `audit_logs` (quién, qué, cuándo, antes/después).  
- **Versionado de documentos:** almacenamiento en S3/compatibles o filesystem; metadatos en BD.  
- **Seguridad:** validación y sanitización de entradas; RBAC; *rate limiting*; políticas de acceso por rol y por “solvencia”.

### 3.2 Frontend (Next.js + React + TypeScript)
- **App Router** (Next 13+): rutas servidor/cliente, *server actions* cuando aplique.  
- **Estilos:** Tailwind CSS.  
- **Estado:** React Query (datos remotos), Zustand/Context para UI local.  
- **Formularios:** React Hook Form + Zod (validación).  
- **Autenticación:** flujo SSO (redirecciones a CETI); fallback con formulario local; manejo de token Sanctum.  
- **Accesibilidad y UX:** componentes reutilizables, semántica ARIA, i18n ready.  


## 4) Lógica de Negocio (lenguaje natural)

### 4.1 Roles y responsabilidades
- **Root**: creación inicial de áreas, configuración global, alta de administradores.  
- **Administrador**: aprueba reservas, gestiona documentos y academias, define horarios, registra aportes manuales, gestiona usuarios.  
- **Profesor (Docente)**: inicia sesión por **SSO CETI**, visualiza su **estado de solvencia**, **registra invitados** (quedando responsable), solicita reservas y consulta documentos/reglamentos.  
- **Invitado**: acceso limitado (según invitación aprobada); visualiza su condición de acceso y notificaciones; no interactúa con módulos administrativos.  
- **Instructor**: gestiona su **escuela/academia** (programas, horarios, lista de alumnos); puede registrar asistencia y proponer actividades.  

> **Principio rector**: la **solvencia** (aportes al día) del profesor habilita funciones sensibles (reservar, invitar).

### 4.2 Dominio “Usuarios y Acceso”
- **Onboarding (Profesor):** inicia sesión con **SSO CETI** → si no existe, se crea perfil básico a partir del *claim* institucional; se asigna rol **Profesor**.  
- **Onboarding (Invitado):** el profesor ingresa correo y datos mínimos → se genera “solicitud de invitación” → el **Administrador** aprueba/deniega. Aprobado: se crea usuario **Invitado** con vigencia (fecha/uso) y permisos limitados.  
- **Autenticación local** (respaldo): para invitados/operadores sin credenciales CETI; **Sanctum** emite token SPA.  
- **Autorización (RBAC):** políticas por rol; reglas transversales por **solvencia** (ej. “reservar” exige solvencia vigente).

### 4.3 Dominio “Instalaciones y Servicios”
- La Casa del Profesor ofrece **áreas** (piscina, salón de eventos, canchas, áreas verdes) que se operan como **servicios** reservables.  
- Un **Área** define: nombre, aforo, horarios base, reglas (ej. anticipación mínima), y si admite uso exclusivo o compartido.

### 4.4 Dominio “Reservas”
- **Estados:** `borrador` → `solicitada` → `aprobada`/`rechazada` → `ejecutada`/`no_presentado` → `cerrada`.  
- **Reglas clave:**  
  1) Toda **solicitud** verifica **disponibilidad** en calendario centralizado.  
  2) Antes de **solicitar**, el sistema valida **solvencia** del profesor.  
  3) **Aprobación** por **Administrador** (puede requerir aporte/anticipo si la política lo define).  
  4) Conflictos (doble reserva) se bloquean por regla de exclusividad o aforo.  
  5) Asienta **traza** (quién cambió estado, cuándo, y motivo).

### 4.5 Dominio “Aportes y Solvencia”
- **Aporte**: contribución periódica de mantenimiento (mensual u otra periodicidad configurada).  
- **Solvencia**: condición booleana con vigencia (ej. mes corriente); habilita **reservar** o **invitar**.  
- **Cobro y registro**: inicialmente manual (efectivo o referencia externa); el **Administrador** registra el pago y adjunta comprobante.  
- **Auditoría**: todo aporte crea entradas en `contributions` + `audit_logs`; reportes por período.  
- **Política de acceso**: si no hay solvencia → el sistema **limita** acciones (ver, pero no reservar/invitar).

### 4.6 Dominio “Académias/Escuelas”
- **Escuela** (natación, karate, etc.): entidad propia, adscrita a Casa del Profesor.  
- **Instructor**: asocia programas, grupos y horarios; registra **asistencia**.  
- **Inscripción**: profesor o invitado participa según reglas (aforo, edad, solvencia).  
- **Horarios**: integrados al calendario central; evitan colisiones con reservas de área.

### 4.7 Dominio “Documentos”
- **Ciclo de vida**: `borrador` → `revisión` → `publicado` → `archivado`.  
- **Metadatos**: tipo (reglamento, acta, instructivo), versión, fecha, firmantes.  
- **Acceso**: público (si corresponde) o restringido por rol.  
- **Búsqueda**: por título, tipo, etiquetas y fecha.  
- **Trazabilidad**: histórico de cambios y descargas.

### 4.8 Notificaciones y comunicación
- **Eventos que notifican**: aprobación/denegación de reserva; vencimiento de solvencia; publicación de documento; mensajes administrativos.  
- **Canales**: correo electrónico; a futuro, push web.  
- **Plantillas**: mantenidas por el administrador (variables, pie institucional).

### 4.9 Métricas y paneles
- Indicadores clave: usuarios activos, tasa de solvencia, uso por área, ocupación semanal, participación por academia, reservas por estado.  
- Perfiles de visualización: administrador (global), instructor (su escuela), profesor (sus datos).

---

## 5) Reglas de negocio (resumen)

1. **Solvencia obligatoria** para **reservar** o **invitar**.  
2. **Invitado** siempre asociado y **aprobado** por un **Administrador**.  
3. **Reserva** sujeta a disponibilidad real y **aprobación**; cada cambio se audita.  
4. **Documentos** institucionales pasan por **flujo de publicación** y control de versiones.  
5. **Academias** operan como **propias** de la Casa del Profesor (no “clubes” externos).  
6. Terminología y comprobantes usan el concepto **“aportes”** (nunca “alquiler”/“membresía”).

---

## 6) Modelado funcional (narrativas de uso)

### 6.1 Registro e ingreso (Profesor, SSO)
1) El profesor ingresa a la web → “Acceder con cuenta UNET (CETI)”.  
2) Tras autenticarse en CETI, el backend valida y crea/actualiza su perfil.  
3) Si su solvencia está vigente, el panel habilita “Reservar” e “Invitar”; si no, se muestra aviso y opciones para regularizar.

### 6.2 Invitación de terceros
1) Profesor registra datos del invitado y justifica la invitación.  
2) El Administrador revisa (capacidad, reglas, solvencia del profesor) → aprueba/deniega.  
3) Invitado recibe correo con indicaciones de acceso y vigencia de la invitación.

### 6.3 Reserva de un área
1) Profesor explora calendario, filtra área y fecha.  
2) Crea solicitud; el sistema verifica solvencia y disponibilidad.  
3) Administrador aprueba/deniega (opcional: requiere aporte adicional).  
4) Se notifica al profesor; el día del evento se marca asistencia/uso; la reserva se **cierra** y queda trazada.

### 6.4 Aporte y solvencia
1) Profesor realiza aporte (por ahora, registro manual del comprobante).  
2) Administrador valida y **marca solvencia** con vigencia.  
3) El sistema actualiza permisos funcionales (feature gating).

### 6.5 Gestión documental
1) Administrador sube documento (PDF), define tipo y metadatos.  
2) Pasa a **revisión** y luego a **publicado**.  
3) Los usuarios lo consultan por rol; se registran descargas.

---

## 7) Estructura de datos (vista de alto nivel)

- **users**: id, nombre, email, rol, origen_auth (CETI/local), estado, creado_en.  
- **profiles**: user_id, cedula, telefono, relación con UNET (docente/administrativo).  
- **roles**: id, nombre (root, admin, profesor, invitado, instructor).  
- **permissions** (si se requiere granularidad por acción).  
- **guests**: id, datos básicos, profesor_sponsor_id, estado (pendiente/aprobado/vencido).  
- **areas**: id, nombre, aforo, reglas, horarios_base.  
- **reservations**: id, area_id, solicitante_id, estado, fecha_inicio/fin, notas.  
- **contributions**: id, user_id, periodo, monto, comprobante_url, estado, fecha.  
- **solvency**: user_id, periodo, vigente_hasta.  
- **documents**: id, tipo, titulo, version, estado, file_url, metadatos.  
- **academies**: id, nombre, descripcion, instructor_id.  
- **academy_schedules**: academy_id, dia_semana, hora_inicio/fin, area_id.  
- **enrollments**: academy_id, user_id/invitado_id, estado, fecha.  
- **notifications**: id, user_id, tipo_evento, payload, enviado_en.  
- **audit_logs**: id, actor_id, entidad, entidad_id, accion, before, after, fecha.

> La granularidad exacta y claves foráneas se definirán en `backend-requirements.md` con migraciones y *seeders* iniciales (roles, áreas, políticas por defecto).

---

## 8) Seguridad, cumplimiento y auditoría

- **SSO/LDAP CETI** como principal para docentes/personal UNET.  
- **Sanctum** para autenticación local de respaldo y emisión de tokens a SPA.  
- **RBAC** con políticas por rol y restricciones por **solvencia**.  
- **Auditoría completa** de cambios sensibles (reservas, aportes, documentos, roles).  
- **Terminología** conforme a institución **sin fines de lucro**: **aportes** y **aportes de mantenimiento**.  
- **Resguardo documental**: versiones y trazabilidad (quién publica, quién descarga).

---

## 9) Frontend: vistas y navegación

### 9.1 Sitio público (Landing)
- **Inicio**: hero, propósito institucional, acceso rápido a “Instalaciones”, “Academias”, “Documentos”.  
- **Instalaciones**: ficha de cada área con fotos, reglas básicas, disponibilidad general.  
- **Academias**: natación, karate, etc., con horarios y responsables.  
- **Documentos**: reglamentos/instructivos de acceso público.  
- **Contacto**: datos y ubicación.

### 9.2 Panel privado (según rol)
- **Profesor**: estado de solvencia, mis reservas, **invitar** a terceros, mis documentos.  
- **Administrador**: consola de aprobación, gestión de áreas/horarios, **aportes** y solvencias, documentos (flujo completo), métricas.  
- **Instructor**: gestión de su academia, grupos y asistencia.

---

## 10) Integraciones y servicios

- **CETI/LDAP**: login institucional (SSO) con *claims* mínimos (email, id, nombre).  
- **Correo**: SMTP/servicio transaccional (plantillas parametrizadas).  
- **Captcha** en flujos públicos sensibles (spam).  
- **Futuro**: Chatbot (IA) entrenado con documentos institucionales y FAQs.

---

## 11) Operación, despliegue y calidad

- **Entornos**: dev, staging, prod.  
- **CI/CD**: build y tests automáticos; migraciones versionadas.  
- **Pruebas**: unitarias (PHPUnit, Vitest), de integración (Pest), E2E (Playwright/Cypress).  
- **Monitoreo**: logs centralizados, métricas de aplicación, alertas por colas fallidas.  
- **Backups**: base de datos y documentos; política de retención.

---

## 12) Matriz de permisos (resumen)

| Acción                               | Root | Admin | Profesor | Invitado | Instructor |
|--------------------------------------|:----:|:-----:|:--------:|:--------:|:----------:|
| Configurar sistema                   |  ✅  |  ❌   |    ❌    |    ❌    |     ❌     |
| Crear/editar áreas                   |  ✅  |  ✅   |    ❌    |    ❌    |     ❌     |
| Publicar/archivar documentos         |  ✅  |  ✅   |    ❌    |    ❌    |     ❌     |
| Registrar/validar aportes            |  ✅  |  ✅   |    ❌    |    ❌    |     ❌     |
| Ver estado de solvencia propio       |  ✅  |  ✅   |    ✅    |    ✅    |     ✅     |
| Crear invitaciones                   |  ❌  |  ❌   | **✅***  |    ❌    |     ❌     |
| Aprobar/denegar invitaciones         |  ❌  |  ✅   |    ❌    |    ❌    |     ❌     |
| Solicitar reservas                   |  ❌  |  ❌   | **✅***  |    ❌    |     ❌     |
| Aprobar/denegar reservas             |  ❌  |  ✅   |    ❌    |    ❌    |     ❌     |
| Gestionar academias propias          |  ❌  |  ✅   |    ❌    |    ❌    |     ✅     |

\* Sujeto a **solvencia vigente**.

---

## 13) Consideraciones de diseño

- **Evitar tecnicismos en la UI pública**; mantener lenguaje institucional.  
- **Estados vacíos** claros (sin reservas, sin documentos).  
- **Accesibilidad**: contraste adecuado, navegación con teclado, etiquetas correctas.  
- **Rendimiento**: *caching* de catálogos y vistas de calendario; *lazy-loading* de listados extensos.  
- **Escalabilidad**: módulos desacoplados; colas para tareas pesadas (PDFs, envíos).

---

## 14) Roadmap de alto nivel (iterativo)

1) **Fase 1 (MVP)**: landing pública, SSO CETI + Sanctum, perfil docente, publicación básica de documentos, reservas con aprobación, registro manual de aportes y solvencia.  
2) **Fase 2**: academias (instructor, horarios, asistencia), métricas y paneles; mejoras de notificaciones.  
3) **Fase 3**: chatbot, carnetización con QR, reportes avanzados, refuerzo de auditoría/descargas.

---

## 15) Glosario breve

- **Aporte**: contribución económica de mantenimiento.  
- **Solvencia**: condición vigente que habilita funciones sensibles (reservar/invitar).  
- **Invitado**: tercero autorizado bajo responsabilidad de un profesor.  
- **Academia/Escuela**: programa institucional (natación, karate, etc.).  
- **CETI**: centro de TI de la UNET para SSO/LDAP.

---

## 16) Archivos complementarios sugeridos

- `backend-requirements.md`: contratos de API, migraciones, modelos, políticas.  
- `frontend-requirements.md`: rutas, componentes, estados, contratos de datos.  
- `data-seed.md`: roles, áreas iniciales, políticas por defecto.  
- `operations.md`: despliegue, CI/CD, backups, monitoreo.

