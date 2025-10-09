# Documentación del Proyecto: CPU-Backend

Este documento describe la estructura general del proyecto, las convenciones de código y los endpoints de la API disponibles.

## 1. Estructura del Proyecto

El proyecto sigue una estructura estándar de Laravel, organizada de la siguiente manera:

- **`/app`**: Contiene el núcleo de la aplicación.
  - **`/Console`**: Comandos de Artisan.
  - **`/Enums`**: Enumeraciones de PHP para estados y roles, promoviendo la consistencia de datos.
  - **`/Http`**: Controladores, Middleware y Requests de validación.
  - **`/Mail`**: Clases para el envío de correos electrónicos.
  - **`/Models`**: Modelos de Eloquent que interactúan con la base de datos.
  - **`/Providers`**: Proveedores de servicios de Laravel.
  - **`/Services`**: Lógica de negocio abstraída de los controladores.
  - **`/Support`**: Clases de soporte y utilidades, como la integración con R2 Storage.

- **`/bootstrap`**: Scripts de arranque de la aplicación.

- **`/config`**: Archivos de configuración para la aplicación, base de datos, servicios, etc.

- **`/database`**: Migraciones, factories y seeders para la base de datos.

- **`/docs`**: Documentación funcional y de la API en formato Markdown.

- **`/public`**: Punto de entrada de la aplicación (`index.php`) y assets públicos.

- **`/resources`**: Vistas (Blade), assets de frontend (CSS, JS) y archivos de lenguaje.

- **`/routes`**: Definición de las rutas de la aplicación.
  - `api.php`: Rutas para la API.
  - `web.php`: Rutas web.

- **`/storage`**: Almacenamiento de logs, archivos cacheados y subidas de la aplicación.

- **`/tests`**: Pruebas unitarias y de características (feature).

- **`/vendor`**: Dependencias de Composer.

## 2. Estructura y Convenciones de Código

El proyecto emplea patrones de diseño que separan responsabilidades para mantener un código limpio y escalable:

- **Service Layer**: La lógica de negocio compleja se encuentra en clases dentro de `app/Services`. Esto permite que los controladores (`app/Http/Controllers`) sean delgados y se centren en manejar la solicitud y la respuesta HTTP.

- **Form Requests**: La validación de las solicitudes se gestiona en clases específicas dentro de `app/Http/Requests`. Esto evita la saturación de los controladores con lógica de validación.

- **Enums (Enumeraciones)**: Se utilizan enumeraciones (`app/Enums`) para definir conjuntos de valores constantes, como roles de usuario (`UserRole`) o estados de reservación (`ReservationStatus`). Esto mejora la legibilidad y previene errores por valores incorrectos.

- **Modelos de Eloquent**: Los modelos en `app/Models` definen las relaciones y la interacción con las tablas de la base de datos, siguiendo las convenciones de Laravel.

- **Inyección de Dependencias**: Se utiliza la inyección de dependencias para gestionar las clases de servicio y otras dependencias, facilitando las pruebas y la mantenibilidad.

## 3. Endpoints de la API (v1)

Todos los endpoints están prefijados con `/api/v1`.

### Rutas Públicas (Sin Autenticación)

- **Autenticación y Registro**
  - `POST /auth/register`: Registro de un nuevo usuario.
  - `POST /auth/set-password`: Establecimiento de contraseña para un nuevo usuario.
  - `POST /auth/forgot-password`: Solicitud de restablecimiento de contraseña.
  - `POST /auth/reset-password`: Restablecimiento de la contraseña con un token.
  - `POST /login`: Inicio de sesión.

- **Consulta de Datos**
  - `GET /areas`: Lista todas las áreas.
  - `GET /areas/{area}`: Muestra un área específica.
  - `GET /academies`: Lista todas las academias.
  - `GET /academies/{academy}`: Muestra una academia específica.
  - `GET /users`: Lista los usuarios.
  - `GET /users/{user}`: Muestra un usuario específico.
  - `GET /reservations/availability`: Consulta la disponibilidad para reservaciones.

- **Archivos**
  - `GET /uploads`: Lista los archivos subidos (públicos).
  - `GET /uploads/{id}`: Muestra un archivo específico.

### Rutas Protegidas (Requieren Autenticación)

- **Autenticación**
  - `POST /logout`: Cierra la sesión del usuario.

- **Notificaciones**
  - `GET /notifications`: Lista las notificaciones del usuario.
  - `GET /notifications/unread`: Lista las notificaciones no leídas.
  - `GET /notifications/count`: Obtiene el número de notificaciones no leídas.
  - `PUT /notifications/{id}/read`: Marca una notificación como leída.
  - `PUT /notifications/read-all`: Marca todas las notificaciones como leídas.

- **Invitaciones**
  - `POST /invitations`: Crea una nueva invitación (para usuarios autenticados).

- **Reservaciones**
  - `GET /reservations`: Lista las reservaciones del usuario.
  - `POST /reservations`: Crea una nueva reservación.
  - `PUT /reservations/{id}`: Actualiza una reservación.
  - `POST /reservations/{id}/cancel`: Cancela una reservación.

- **Archivos**
  - `POST /uploads`: Sube un nuevo archivo.
  - `DELETE /uploads/{id}`: Elimina un archivo.
  - `POST /uploads/presign`: Genera una URL pre-firmada para subida de archivos.

### Rutas de Administrador (Requieren Rol de Admin)

- **Gestión de Usuarios**
  - `POST /users`: Crea un nuevo usuario.
  - `PUT /users/{user}`: Actualiza un usuario (rol, estado, etc.).
  - `DELETE /users/{user}`: Elimina un usuario.
  - `POST /users/{user}/invite`: Envía una invitación a un usuario.
  - `GET /admin/pending-registrations`: Lista los registros pendientes de aprobación.

- **Gestión de Invitaciones**
  - `GET /invitations`: Lista todas las invitaciones.
  - `GET /invitations/pending`: Lista las invitaciones pendientes.
  - `PUT /invitations/{id}/approve`: Aprueba una invitación.
  - `PUT /invitations/{id}/reject`: Rechaza una invitación.

- **Gestión de Áreas (CRUD)**
  - `POST /areas`: Crea un área.
  - `PUT /areas/{area}`: Actualiza un área.
  - `DELETE /areas/{area}`: Elimina un área.

- **Gestión de Academias (CRUD)**
  - `POST /academies`: Crea una academia.
  - `PUT /academies/{academy}`: Actualiza una academia.
  - `DELETE /academies/{academy}`: Elimina una academia.

- **Gestión de Reservaciones**
  - `POST /reservations/{id}/approve`: Aprueba una reservación.
  - `POST /reservations/{id}/reject`: Rechaza una reservación.

- **Logs de Auditoría**
  - `GET /audit-logs`: Lista todos los logs de auditoría.
  - `GET /audit-logs/{auditLog}`: Muestra un log de auditoría específico.
