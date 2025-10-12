# Documentación de API: Áreas y Academias

## 1. Resumen General

Este documento detalla los endpoints de la API REST para la gestión de **Áreas** y **Academias**. Está diseñado para guiar al equipo de frontend en la implementación de las interfaces de creación, visualización, edición y eliminación de estas entidades.

### Autenticación

- **Endpoints Públicos (`GET`)**: No requieren autenticación. Cualquiera puede listar y ver los detalles de áreas y academias.
- **Endpoints de Administración (`POST`, `PUT`, `DELETE`)**: Requieren autenticación como **Administrador**. Las peticiones deben incluir un token de Sanctum en la cabecera `Authorization`:
  ```
  Authorization: Bearer {your_admin_token}
  ```

### Gestión de Archivos (Imágenes)

La subida de imágenes se maneja a través de peticiones `multipart/form-data`.

- **Para añadir/reemplazar imágenes**: Incluir los archivos en un campo `images[]`.
  ```
  // Ejemplo en un formulario HTML
  <input type="file" name="images[]" multiple>
  ```
- **Para eliminar imágenes existentes**: Incluir un array de IDs de archivo en el campo `remove_file_ids[]`.
  ```json
  {
    "name": "Nuevo nombre",
    "remove_file_ids": [1, 5, 10]
  }
  ```
- **Formato de respuesta de imagen**: Las imágenes asociadas a una entidad se devuelven en un formato estandarizado.
  ```json
  {
    "id": 1,
    "url": "https://pub-...r2.dev/abc123.jpg",
    "title": "gimnasio-vista.jpg",
    "caption": null,
    "is_cover": true,
    "sort_order": 1,
    "file_size": 1024000,
    "mime_type": "image/jpeg"
  }
  ```

---

## 2. API de Áreas

Endpoints para gestionar las áreas físicas de la institución.

### Listar Áreas

Recupera una lista paginada de todas las áreas.

- **Endpoint**: `GET /api/v1/areas`
- **Autenticación**: No requerida.
- **Parámetros de Query**:
  - `search` (string): Busca áreas por nombre.
  - `is_active` (boolean): Filtra por estado activo (`true` o `false`).
  - `is_reservable` (boolean): Filtra por si el área es reservable (`true` o `false`).
  - `page` (integer): Número de página para la paginación.
- **Respuesta Exitosa (200 OK)**:
  ```json
  {
    "data": [
      {
        "id": 1,
        "name": "Gimnasio Principal",
        "slug": "gimnasio-principal",
        "description": "Gimnasio equipado con máquinas de última generación.",
        "capacity": 50,
        "is_reservable": true,
        "is_active": true,
        "images": [ /* array de objetos de imagen */ ],
        "schedules": [
          {
            "id": 1,
            "day_of_week": 1,
            "start_time": "06:00",
            "end_time": "22:00",
            "is_open": true
          }
        ],
        "created_at": "2025-01-27T23:30:00Z",
        "updated_at": "2025-01-27T23:30:00Z"
      }
    ],
    "links": { /* ... */ },
    "meta": { /* ... */ }
  }
  ```

### Obtener un Área Específica

Recupera los detalles de un área por su ID.

- **Endpoint**: `GET /api/v1/areas/{id}`
- **Autenticación**: No requerida.
- **Respuesta Exitosa (200 OK)**:
  ```json
  {
    "data": {
      "id": 1,
      "name": "Gimnasio Principal",
      "slug": "gimnasio-principal",
      "description": "Gimnasio equipado con máquinas de última generación.",
      "capacity": 50,
      "is_reservable": true,
      "is_active": true,
      "images": [ /* array de objetos de imagen */ ],
      "schedules": [ /* array de horarios */ ]
    }
  }
  ```

### Crear un Área

Crea una nueva área. Requiere permisos de administrador.

- **Endpoint**: `POST /api/v1/areas`
- **Autenticación**: Administrador.
- **Tipo de Petición**: `multipart/form-data`
- **Cuerpo de la Petición**:
  ```json
  {
    "name": "Gimnasio Principal",
    "slug": "gimnasio-principal", // Opcional, se autogenera si no se envía
    "description": "Gimnasio equipado con máquinas de última generación.",
    "capacity": 50,
    "is_reservable": true,
    "is_active": true,
    "images": [ /* array de objetos File de JS */ ],
    "schedules": [
      {
        "day_of_week": 1, // Lunes
        "start_time": "06:00",
        "end_time": "22:00",
        "is_open": true
      }
    ]
  }
  ```
- **Respuesta Exitosa (201 Created)**: Devuelve el objeto del área recién creada (ver "Obtener un Área Específica").

### Actualizar un Área

Actualiza los datos de un área existente. Requiere permisos de administrador.

- **Endpoint**: `PUT /api/v1/areas/{id}`
- **Autenticación**: Administrador.
- **Tipo de Petición**: `multipart/form-data` (si se envían imágenes) o `application/json`.
- **Cuerpo de la Petición**:
  ```json
  {
    "name": "Gimnasio Principal (Renovado)",
    "description": "Descripción actualizada.",
    "is_reservable": false,
    "images": [ /* array de nuevos archivos File */ ],
    "remove_file_ids": [1, 2], // IDs de imágenes a eliminar
    "schedules": [ /* array completo de los nuevos horarios */ ]
  }
  ```
- **Respuesta Exitosa (200 OK)**: Devuelve el objeto del área actualizada.

### Eliminar un Área

Elimina un área del sistema.

- **Endpoint**: `DELETE /api/v1/areas/{id}`
- **Autenticación**: Administrador.
- **Respuesta Exitosa (204 No Content)**: No devuelve contenido.

---

## 3. API de Academias

Endpoints para gestionar las academias ofrecidas en la institución.

### Listar Academias

Recupera una lista paginada de todas las academias.

- **Endpoint**: `GET /api/v1/academies`
- **Autenticación**: No requerida.
- **Parámetros de Query**:
  - `search` (string): Busca academias por nombre.
  - `status` (string): Filtra por estado (`activa`, `cerrada`, `cancelada`).
  - `lead_instructor_id` (integer): Filtra por el ID del instructor líder.
  - `page` (integer): Número de página.
- **Respuesta Exitosa (200 OK)**:
  ```json
  {
    "data": [
      {
        "id": 1,
        "name": "Escuela de Natación",
        "description": "Academia de natación para todas las edades.",
        "status": "activa",
        "lead_instructor": {
          "id": 1,
          "name": "Juan Pérez",
          "email": "juan.perez@unet.edu.ve"
        },
        "images": [ /* array de objetos de imagen */ ],
        "schedules": [
          {
            "id": 1,
            "area_id": 1,
            "area_name": "Piscina",
            "day_of_week": 1,
            "start_time": "16:00",
            "end_time": "18:00",
            "capacity": 20
          }
        ],
        "schedules_count": 1,
        "enrollments_count": 15,
        "created_at": "2025-01-27T23:30:00Z",
        "updated_at": "2025-01-27T23:30:00Z"
      }
    ],
    "links": { /* ... */ },
    "meta": { /* ... */ }
  }
  ```

### Obtener una Academia Específica

Recupera los detalles de una academia por su ID.

- **Endpoint**: `GET /api/v1/academies/{id}`
- **Autenticación**: No requerida.
- **Respuesta Exitosa (200 OK)**:
  ```json
  {
    "data": {
      "id": 1,
      "name": "Escuela de Natación",
      "description": "Academia de natación para todas las edades.",
      "status": "activa",
      "lead_instructor": { /* ... */ },
      "images": [ /* ... */ ],
      "schedules": [ /* ... */ ]
    }
  }
  ```

### Crear una Academia

Crea una nueva academia. Requiere permisos de administrador.

- **Endpoint**: `POST /api/v1/academies`
- **Autenticación**: Administrador.
- **Tipo de Petición**: `multipart/form-data`
- **Cuerpo de la Petición**:
  ```json
  {
    "name": "Escuela de Natación",
    "description": "Academia de natación para todas las edades.",
    "lead_instructor_id": 1, // ID de un usuario con rol instructor/profesor
    "status": "activa", // Opcional, por defecto 'activa'
    "images": [ /* array de objetos File de JS */ ],
    "schedules": [
      {
        "area_id": 1,
        "day_of_week": 1,
        "start_time": "16:00",
        "end_time": "18:00",
        "capacity": 20
      }
    ]
  }
  ```
- **Respuesta Exitosa (201 Created)**: Devuelve el objeto de la academia recién creada.

### Actualizar una Academia

Actualiza los datos de una academia existente. Requiere permisos de administrador.

- **Endpoint**: `PUT /api/v1/academies/{id}`
- **Autenticación**: Administrador.
- **Tipo de Petición**: `multipart/form-data` (si se envían imágenes) o `application/json`.
- **Cuerpo de la Petición**:
  ```json
  {
    "name": "Escuela de Natación Avanzada",
    "description": "Nueva descripción.",
    "lead_instructor_id": 2,
    "status": "cerrada",
    "images": [ /* array de nuevos archivos File */ ],
    "remove_file_ids": [3, 4], // IDs de imágenes a eliminar
    "schedules": [ /* array completo de los nuevos horarios */ ]
  }
  ```
- **Respuesta Exitosa (200 OK)**: Devuelve el objeto de la academia actualizada.

### Eliminar una Academia

Elimina una academia del sistema.

- **Endpoint**: `DELETE /api/v1/academies/{id}`
- **Autenticación**: Administrador.
- **Respuesta Exitosa (204 No Content)**: No devuelve contenido.
