# 07 - Resumen de Endpoints de la API

> **Propósito**: Este documento ofrece una vista rápida de los principales endpoints de la API v1, agrupados por recurso. Sirve como una guía de referencia para desarrolladores.

---

## Autenticación (`/auth`)

| Método | Endpoint | Permisos | Descripción |
| :--- | :--- | :--- | :--- |
| `POST` | `/login` | Público | Inicia sesión y retorna un token de Sanctum. |
| `POST` | `/logout` | Autenticado | Invalida el token actual del usuario. |
| `POST` | `/auth/register` | Público | Inicia el flujo de auto-registro para usuarios UNET. |
| `POST` | `/auth/forgot-password` | Público | Solicita un código de 6 dígitos para restablecer la contraseña. |
| `POST` | `/auth/reset-password` | Público | Restablece la contraseña usando el código de verificación. |
| `POST` | `/auth/set-password` | Público | Permite a un nuevo usuario (invitado/creado por admin) establecer su contraseña inicial. |

---

## Usuarios (`/users`)

| Método | Endpoint | Permisos | Descripción |
| :--- | :--- | :--- | :--- |
| `GET` | `/users` | `profesor`, `administrador` | Lista y filtra todos los usuarios del sistema. |
| `GET` | `/users/{id}` | Autenticado | Muestra el perfil de un usuario específico. |
| `PUT` | `/users/me` | Autenticado | Permite al usuario autenticado actualizar su propio perfil. |
| `POST` | `/users` | `administrador` | Crea un nuevo usuario directamente. |
| `PUT` | `/users/{id}` | `administrador` | Actualiza el rol, estado u otros datos de un usuario. |
| `DELETE` | `/users/{id}` | `administrador` | Elimina (Soft Delete) a un usuario. |
| `GET` | `/admin/pending-registrations` | `administrador` | Lista usuarios con estado `aprobacion_pendiente`. |

---

## Áreas (`/areas`)

| Método | Endpoint | Permisos | Descripción |
| :--- | :--- | :--- | :--- |
| `GET` | `/areas` | Público | Lista todas las áreas activas. |
| `GET` | `/areas/{id}` | Público | Muestra un área específica con sus horarios e imágenes. |
| `POST` | `/areas` | `administrador` | Crea una nueva área. |
| `POST` | `/areas/{id}` | `administrador` | Actualiza un área (usa `multipart/form-data`). |
| `DELETE` | `/areas/{id}` | `administrador` | Elimina un área. |

---

## Academias (`/academies`)

| Método | Endpoint | Permisos | Descripción |
| :--- | :--- | :--- | :--- |
| `GET` | `/academies` | Público | Lista todas las academias activas. |
| `GET` | `/academies/{id}` | Público | Muestra una academia específica. |
| `POST` | `/academies` | `administrador` | Crea una nueva academia. |
| `POST` | `/academies/{id}` | `administrador` | Actualiza una academia (usa `multipart/form-data`). |
| `DELETE` | `/academies/{id}` | `administrador` | Elimina una academia. |
| `GET` | `/academies/{id}/students` | `instructor`, `administrador` | Lista los estudiantes externos de una academia. |
| `POST` | `/academies/{id}/students` | `instructor`, `administrador` | Añade un estudiante externo a una academia. |

---

## Reservas (`/reservations`)

| Método | Endpoint | Permisos | Descripción |
| :--- | :--- | :--- | :--- |
| `GET` | `/reservations/availability` | Público | Consulta la disponibilidad de un área en un rango de fechas. |
| `GET` | `/reservations` | Autenticado | Lista las reservas (propias para usuarios, todas para admins). |
| `POST` | `/reservations` | Autenticado + `solvente` | Crea una nueva solicitud de reserva. |
| `PUT` | `/reservations/{id}` | Dueño de la reserva | Actualiza una reserva en estado `pendiente`. |
| `POST` | `/reservations/{id}/cancel` | Dueño o `administrador` | Cancela una reserva. |
| `POST` | `/reservations/{id}/approve` | `administrador` | Aprueba una solicitud de reserva. |
| `POST` | `/reservations/{id}/reject` | `administrador` | Rechaza una solicitud de reserva. |

---

## Invitaciones (`/invitations`)

| Método | Endpoint | Permisos | Descripción |
| :--- | :--- | :--- | :--- |
| `GET` | `/invitations` | `profesor`, `administrador` | Lista las invitaciones enviadas. |
| `POST` | `/invitations` | `profesor` + `solvente` | Crea y envía una nueva invitación. |
| `GET` | `/invitations/pending` | `administrador` | Lista todas las invitaciones pendientes de revisión. |
| `PUT` | `/invitations/{id}/approve` | `administrador` | Aprueba una invitación y crea la cuenta del invitado. |
| `PUT` | `/invitations/{id}/reject` | `administrador` | Rechaza una invitación. |

---

## Chat (`/chat`)

| Método | Endpoint | Permisos | Descripción |
| :--- | :--- | :--- | :--- |
| `GET` | `/chat/users/search` | Autenticado | Busca usuarios para iniciar un chat. |
| `GET` | `/chat/conversations` | Autenticado | Lista las conversaciones del usuario. |
| `POST` | `/chat/conversations` | Autenticado | Crea o recupera una conversación. |
| `GET` | `/chat/conversations/{id}/messages`| Autenticado | Obtiene los mensajes de una conversación. |
| `POST` | `/chat/conversations/{id}/messages`| Autenticado | Envía un mensaje. |
| `POST` | `/chat/conversations/{id}/read` | Autenticado | Marca mensajes como leídos. |
| `GET` | `/chat/unread/summary` | Autenticado | Obtiene un resumen de mensajes no leídos (para polling). |
| `GET` | `/chat/blocks` | Autenticado | Lista los usuarios bloqueados. |
| `POST` | `/chat/blocks` | Autenticado | Bloquea a un usuario. |
| `DELETE`| `/chat/blocks/{id}` | Autenticado | Desbloquea a un usuario. |

---

## Otros Endpoints

| Método | Endpoint | Permisos | Descripción |
| :--- | :--- | :--- | :--- |
| `GET` | `/notifications` | Autenticado | Lista las notificaciones del usuario. |
| `PUT` | `/notifications/{id}/read` | Autenticado | Marca una notificación como leída. |
| `GET` | `/audit-logs` | `administrador` | Accede a la bitácora de auditoría del sistema. |
| `POST` | `/uploads` | Autenticado | Sube un nuevo archivo al sistema. |
