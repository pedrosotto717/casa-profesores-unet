# Sistema Web para la Gestión de la Casa del Profesor Universitario de la UNET - Versión Actualizada

> **Propósito de este documento**  
> Documento actualizado que refleja los cambios realizados en el sistema, especialmente en roles de usuario, arquitectura frontend y funcionalidades implementadas. Basado en el changelog y la estructura actual del proyecto.

---

## 1) Descripción General

La **Casa del Profesor Universitario de la UNET** es un espacio institucional y **sin fines de lucro** dedicado al bienestar del personal docente y su núcleo familiar, con acceso controlado a invitados bajo responsabilidad del profesor. El sistema web integral centraliza:

- Control de acceso (SSO institucional + autenticación local de respaldo).  
- Gestión de usuarios, roles y **invitados**.  
- **Reservas** de espacios con calendario, disponibilidad y aprobación.  
- Registro y verificación de **aportes** (solvencia).  
- Publicación y consulta de **documentos institucionales** (reglamentos, actas, instructivos).  
- Comunicación y **notificaciones**.  
- Paneles de **métricas** para la administración.  

> Terminología legal clave: **aportes** o **aportes de mantenimiento** (no "alquiler", no "membresía"). La Casa del Profesor es parte de la UNET y mantiene su carácter institucional.

---

## 2) Arquitectura Tecnológica Actualizada

### 2.1 Backend (Laravel 12, PHP 8.3)
- **Patrón:** API RESTful (stateless), organizada por dominios (módulos) y servicios de aplicación.  
- **Autenticación:**  
  - **SSO/LDAP CETI** (autenticación centralizada para profesores y personal UNET).  
  - **Autenticación local de respaldo** con **Laravel Sanctum** (tokens SPA) para invitados u operadores sin credenciales institucionales.  
- **BD principal:** MySQL 8 (datos transaccionales).  
- **Cache:** Redis (tokens, sesiones SSO, catálogos, vistas de disponibilidad).  
- **Mail/Notifications:** Mailer + colas (Laravel Queue) para correos y notificaciones asincrónicas.  
- **Logging/Auditoría:** Monolog + tabla `audit_logs` (quién, qué, cuándo, antes/después).  
- **Versionado de documentos:** almacenamiento en S3/compatibles o filesystem; metadatos en BD.  
- **Seguridad:** validación y sanitización de entradas; RBAC; *rate limiting*; políticas de acceso por rol y por "solvencia".

### 2.2 Frontend (React + TypeScript)
- **Framework:** React con TypeScript (cambio desde Next.js)
- **Estilos:** Tailwind CSS.  
- **Estado:** React Query (datos remotos), Zustand/Context para UI local.  
- **Formularios:** React Hook Form + Zod (validación).  
- **Autenticación:** flujo SSO (redirecciones a CETI); fallback con formulario local; manejo de token Sanctum.  
- **Accesibilidad y UX:** componentes reutilizables, semántica ARIA, i18n ready.  

---

## 3) Sistema de Roles y Responsabilidades Actualizado

### 3.1 Roles Disponibles

| Rol | Descripción | Acceso Inicial |
|-----|-------------|----------------|
| **Usuario** | Rol base para todos los registros nuevos | ✅ Automático en registro |
| **Profesor** | Personal docente UNET con acceso completo | 🔄 Asignado por Admin |
| **Instructor** | Responsable de academias/escuelas | 🔄 Asignado por Admin |
| **Administrador** | Gestión completa del sistema | 🔄 Asignado manualmente |
| **Obrero** | Personal de mantenimiento UNET | 🔄 Asignado por Admin |
| **Estudiante** | Estudiantes UNET con acceso limitado | 🔄 Asignado por Admin |
| **Invitado** | Acceso temporal bajo responsabilidad de profesor | 🔄 Asignado en invitación |

### 3.2 Matriz de Permisos Actualizada

| Acción | Admin | Profesor | Instructor | Usuario | Obrero | Estudiante | Invitado |
|--------|:-----:|:--------:|:----------:|:-------:|:------:|:----------:|:--------:|
| **Configurar sistema** | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Crear/editar áreas** | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Publicar/archivar documentos** | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Registrar/validar aportes** | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Ver estado de solvencia propio** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Crear invitaciones** | ❌ | **✅*** | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Aprobar/denegar invitaciones** | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Solicitar reservas** | ❌ | **✅*** | **✅*** | **✅*** | ❌ | **✅*** | ❌ |
| **Aprobar/denegar reservas** | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Gestionar academias propias** | ❌ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ |
| **Cambiar roles de usuarios** | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |

\* Sujeto a **solvencia vigente**.

### 3.3 Flujo de Asignación de Roles

1. **Registro inicial:** Todos los usuarios se registran con rol **Usuario**
2. **Promoción de roles:** Solo **Administradores** pueden cambiar roles de usuarios
3. **Invitaciones:** 
   - Si se asigna rol específico → Usuario recibe ese rol
   - Si no se asigna rol → Usuario recibe rol **Invitado** por defecto
4. **Roles especiales:**
   - **Profesor:** Acceso SSO CETI + funcionalidades completas
   - **Instructor:** Gestión de academias específicas
   - **Administrador:** Control total del sistema

---

## 4) Lógica de Negocio Actualizada

### 4.1 Dominio "Usuarios y Acceso"
- **Onboarding (Usuario):** Registro local → rol **Usuario** por defecto → acceso limitado
- **Onboarding (Profesor):** SSO CETI → rol **Profesor** → acceso completo
- **Onboarding (Invitado):** Invitación por profesor → rol **Invitado** → acceso temporal
- **Promoción de roles:** Administrador puede cambiar roles desde dashboard
- **Autenticación local** (respaldo): para usuarios sin credenciales CETI; **Sanctum** emite token SPA

### 4.2 Dominio "Instalaciones y Servicios"
- **Áreas disponibles:** Piscina, Salones (Orquídea, Primavera, Pradera), Auditorio, Kioscos, Sauna, Canchas, Parque infantil, Mesas de recreación, Peluquería
- **Servicios:** Cada área reservable tiene un servicio "Reserva [Área]"
- **Áreas no reservables:** Restaurant (acceso público), Parque infantil (uso libre)

### 4.3 Dominio "Academias/Escuelas"
- **Academias disponibles:** Natación, Karate, Yoga, Bailoterapia, Nado sincronizado, Tareas dirigidas
- **Instructor:** Gestiona su academia específica
- **Inscripción:** Usuarios pueden inscribirse según reglas de aforo y solvencia

### 4.4 Dominio "Reservas"
- **Estados:** `borrador` → `solicitada` → `aprobada`/`rechazada` → `ejecutada`/`no_presentado` → `cerrada`
- **Reglas clave:**  
  1) Verificación de **disponibilidad** en calendario centralizado
  2) Validación de **solvencia** del usuario
  3) **Aprobación** por **Administrador**
  4) Prevención de conflictos por exclusividad o aforo

### 4.5 Dominio "Aportes y Solvencia"
- **Aporte**: contribución periódica de mantenimiento
- **Solvencia**: condición booleana con vigencia que habilita **reservar** o **invitar**
- **Registro**: Manual por **Administrador** con comprobante
- **Auditoría**: Registro completo en `contributions` + `audit_logs`

---

## 5) Estructura de Datos Actualizada

### 5.1 Modelos Principales
- **users**: id, nombre, email, rol, origen_auth (CETI/local), estado, creado_en
- **areas**: id, nombre, descripción, capacidad, tarifa_hora, activo
- **services**: id, area_id, nombre, descripción, requiere_reserva, tarifa_hora, activo
- **academies**: id, nombre, descripción, instructor_principal_id, estado
- **reservations**: id, area_id, solicitante_id, estado, fecha_inicio/fin, notas
- **contributions**: id, user_id, periodo, monto, comprobante_url, estado, fecha
- **documents**: id, tipo, titulo, version, estado, file_url, metadatos
- **audit_logs**: id, actor_id, entidad, entidad_id, accion, before, after, fecha

### 5.2 Enums Disponibles
- **UserRole**: usuario, profesor, instructor, administrador, obrero, estudiante, invitado
- **ReservationStatus**: pendiente, aprobada, rechazada, cancelada, completada, expirada
- **InvitationStatus**: pendiente, aceptada, rechazada, expirada, revocada
- **AcademyStatus**: activa, inactiva, suspendida
- **ContributionStatus**: pendiente, pagada, vencida, cancelada
- **DocumentVisibility**: publico, privado, restringido

---

## 6) Flujos de Usuario Actualizados

### 6.1 Registro e Ingreso
1. **Usuario nuevo:** Registro → rol **Usuario** → acceso limitado
2. **Profesor:** SSO CETI → rol **Profesor** → acceso completo
3. **Invitado:** Invitación → rol **Invitado** → acceso temporal

### 6.2 Gestión de Roles
1. **Administrador** accede al dashboard
2. Selecciona usuario a modificar
3. Cambia rol según necesidades
4. Sistema actualiza permisos automáticamente

### 6.3 Reservas
1. Usuario explora áreas disponibles
2. Selecciona servicio y fecha
3. Sistema verifica solvencia y disponibilidad
4. Crea solicitud → **Administrador** aprueba/deniega
5. Notificación al usuario

### 6.4 Academias
1. **Instructor** gestiona su academia
2. Define horarios y grupos
3. Usuarios se inscriben según disponibilidad
4. Registro de asistencia y actividades

---

## 7) Consideraciones de Implementación

### 7.1 Cambios Realizados
- ✅ **Eliminado sistema de verificación de email** del registro
- ✅ **Actualizado sistema de roles** con 7 roles específicos
- ✅ **Cambiado frontend** de Next.js a React + TypeScript
- ✅ **Implementado sistema de seeders** para datos base
- ✅ **Configurado Laravel Sanctum** para autenticación

### 7.2 Próximos Pasos
1. **Controladores por dominio:** Areas, Services, Academies, Reservations
2. **Flujos completos:** Reserva → Servicio → Área
3. **Dashboard administrativo:** Gestión de roles y aprobaciones
4. **Sistema de notificaciones:** Aprobaciones, recordatorios, vencimientos

---

## 8) Archivos de Referencia

- **Changelog:** `changelog.md` - Historial completo de cambios
- **UserRole Enum:** `app/Enums/UserRole.php` - Roles disponibles
- **Seeders:** `database/seeders/` - Datos base del sistema
- **Especificaciones originales:** `specs/main.md` - Documento base

---

*Documento actualizado el 27 de enero de 2025 - Refleja el estado actual del sistema después de las modificaciones implementadas.*
