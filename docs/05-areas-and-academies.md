# 05 - Gestión de Áreas y Academias

> **Propósito**: Este documento describe la API para la gestión de las entidades `Area` y `Academy`, incluyendo sus horarios e imágenes asociadas. Consolida y actualiza la información de `docs/crud-api-documentation.md`.

---

## 1. Arquitectura y Conceptos Clave

- **Módulo `Services` Eliminado**: La lógica de negocio se ha simplificado. La capacidad de un área para ser reservada ahora se controla directamente con el flag booleano `is_reservable` en el modelo `Area`.
- **Gestión de Horarios**: Cada entidad (`Area` y `Academy`) tiene su propia tabla de horarios recurrentes (`area_schedules` y `academy_schedules`) para definir su disponibilidad o sus bloques de actividad.
- **Gestión de Imágenes (Polimorfismo)**: El sistema utiliza una relación polimórfica para asociar imágenes con áreas y academias. 
  - La tabla `files` almacena los metadatos de cada archivo subido a Cloudflare R2.
  - La tabla `entity_files` actúa como pivote, conectando un `file_id` con cualquier entidad (`entity_id` y `entity_type`).

---

## 2. Gestión de Áreas (`Area`)

Las áreas son los espacios físicos de la institución.

### Endpoints de la API

- **`GET /api/v1/areas`** (Público): Lista todas las áreas activas.
- **`GET /api/v1/areas/{id}`** (Público): Muestra un área específica con sus horarios e imágenes.
- **`POST /api/v1/areas`** (Admin): Crea una nueva área.
- **`POST /api/v1/areas/{id}`** (Admin): Actualiza un área existente. **Nota**: Se usa `POST` con `_method: 'PUT'` para manejar `multipart/form-data`.
- **`DELETE /api/v1/areas/{id}`** (Admin): Elimina un área (Soft Delete).

### Formato de Petición (Crear/Actualizar)

Las peticiones de creación y actualización se envían como `multipart/form-data` para poder incluir la carga de imágenes.

- **Campos Principales**:
  - `name` (string): Nombre del área.
  - `description` (string): Descripción.
  - `capacity` (integer): Aforo.
  - `is_reservable` (boolean): `1` o `0`. Indica si se puede reservar.
  - `is_active` (boolean): `1` o `0`. Visibilidad pública.

- **Gestión de Horarios**:
  - `schedules` (array): Un array de objetos de horario.
  - `schedules[0][day_of_week]` (integer): Día de la semana (1-7).
  - `schedules[0][start_time]` (string): Hora de inicio (`HH:MM`).
  - `schedules[0][end_time]` (string): Hora de fin (`HH:MM`).

- **Gestión de Imágenes**:
  - `images[]` (array de `File`): Nuevas imágenes a subir.
  - `remove_file_ids[]` (array de `integer`): IDs de imágenes existentes a desvincular.

### Ejemplo de Respuesta (`GET /api/v1/areas/{id}`)

```json
{
  "data": {
    "id": 1,
    "name": "Piscina",
    "slug": "piscina",
    "description": "Piscina semiolímpica para natación y recreación.",
    "capacity": 50,
    "is_reservable": true,
    "is_active": true,
    "images": [
      {
        "id": 12,
        "url": "https://r2.dev/piscina.jpg",
        "caption": "Vista principal de la piscina",
        "is_cover": true
      }
    ],
    "schedules": [
      {
        "day_of_week": 2, // Martes
        "start_time": "09:00",
        "end_time": "17:00",
        "is_open": true
      }
    ]
  }
}
```

---

## 3. Gestión de Academias (`Academy`)

Las academias son las escuelas deportivas o culturales que operan en las instalaciones.

### Endpoints de la API

- **`GET /api/v1/academies`** (Público): Lista todas las academias activas.
- **`GET /api/v1/academies/{id}`** (Público): Muestra una academia específica.
- **`POST /api/v1/academies`** (Admin): Crea una nueva academia.
- **`POST /api/v1/academies/{id}`** (Admin): Actualiza una academia.
- **`DELETE /api/v1/academies/{id}`** (Admin): Elimina una academia (Soft Delete).

### Formato de Petición (Crear/Actualizar)

Similar a las áreas, se usa `multipart/form-data`.

- **Campos Principales**:
  - `name` (string): Nombre de la academia.
  - `description` (string): Descripción.
  - `lead_instructor_id` (integer): ID del usuario instructor.
  - `status` (enum): `activa`, `cerrada`, `cancelada`.

- **Gestión de Horarios**:
  - `schedules` (array): Un array de objetos de horario de clases.
  - `schedules[0][area_id]` (integer): ID del área donde se imparte la clase.
  - `schedules[0][day_of_week]` (integer): Día de la semana (1-7).
  - `schedules[0][start_time]` (string): Hora de inicio (`HH:MM`).
  - `schedules[0][end_time]` (string): Hora de fin (`HH:MM`).
  - `schedules[0][capacity]` (integer): Cupo de la clase.

- **Gestión de Imágenes**: Idéntico al de `Areas` (`images[]`, `remove_file_ids[]`).

### Gestión de Estudiantes de Academia

Para gestionar la lista de estudiantes (externos) de una academia, se usan los siguientes endpoints anidados:

- **Base URL**: `/api/v1/academies/{academy_id}/students`
- **Permisos**: `instructor` de la academia o `administrador`.

- **`GET /`**: Lista los estudiantes de la academia.
- **`POST /`**: Añade un nuevo estudiante.
- **`PUT /{student_id}`**: Actualiza los datos de un estudiante.
- **`DELETE /{student_id}`**: Elimina un estudiante de la lista.

---

## 4. Sistema de Archivos y Almacenamiento

- **Almacenamiento**: Cloudflare R2, configurado como un disco S3-compatible en `config/filesystems.php`.
- **Deduplicación**: Al subir un archivo, el sistema calcula su hash SHA-256. Si un archivo con el mismo hash ya existe en la tabla `files`, se reutiliza el registro existente para evitar duplicados. Esto ahorra espacio y mantiene la integridad.
- **Helper `R2Storage`**: La clase `App\Support\R2Storage` centraliza toda la lógica de subida, eliminación y generación de URLs, asegurando que las operaciones con R2 sean consistentes en todo el sistema.
- **Borrado**: Un archivo físico solo se elimina de R2 si ninguna entidad en `entity_files` lo está referenciando. Esto previene que se borren archivos que están en uso por más de una entidad.
