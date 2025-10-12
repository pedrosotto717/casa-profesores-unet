# Backend Files Recognition - Repositorio Institucional

> **Fecha de auditoría:** 11 de octubre de 2025  
> **Fase:** Descubrimiento sin escritura de código  
> **Objetivo:** Inventariar endpoints y parámetros para gestión de archivos, verificar reglas de visibilidad y permisos, y proponer cambios mínimos para un repositorio institucional.

---

## 1. Resumen ejecutivo

**Estado actual:**
El sistema cuenta con una infraestructura **robusta** de gestión de archivos con Cloudflare R2, incluyendo:
- Tabla `files` consolidada con campo `visibility` (`publico`, `privado`, `restringido`) y `file_type` (`document`, `image`, `receipt`, `other`)
- Tabla `documents` **redundante y vacía** (sin FK a `files`, solo guarda `file_url` como string)
- Sistema de subida/eliminación/presign funcional en `/api/v1/uploads/*`
- Integración con Areas/Academies mediante `entity_files` (solo imágenes)
- Auditoría parcial (solo PDF/DOC/DOCX en subidas/borrados)

**Brechas principales:**
1. **No existe endpoint público** para listar documentos institucionales con `visibility=publico`
2. **Filtrado por `visibility`** no implementado en el endpoint `/api/v1/uploads` (lista TODO)
3. **Tabla `documents`** obsoleta y sin uso real; genera confusión
4. Endpoint `/api/v1/uploads` es **público** (GET sin auth), contradiciendo la documentación
5. **No hay validación** de roles ≠ `usuario` para lectura interna
6. **Deduplicación** comentada en docs pero **no implementada** (solo calcula hash sin comparar)

**Impacto para repositorio institucional:**
- **Crítico:** Falta endpoint público exclusivo para documentos (PDF/DOC/DOCX) con `visibility=publico`
- **Alto:** Exposición de todos los archivos (incluidas imágenes internas) en GET público
- **Medio:** Tabla `documents` debe deprecarse para consolidar en `files`

---

## 2. Inventario de endpoints existentes

| Método | Ruta | Auth | Roles | Descripción |
|--------|------|------|-------|-------------|
| **GET** | `/api/v1/uploads` | ❌ No | Público | Lista **todos** los archivos (incluye imágenes). Paginado. Filtro `file_type` |
| **GET** | `/api/v1/uploads/{id}` | ❌ No | Público | Detalle de un archivo específico |
| **POST** | `/api/v1/uploads` | ✅ Sí | Autenticado (cualquiera) | Subir archivo. Validación: max 10MB |
| **DELETE** | `/api/v1/uploads/{id}` | ✅ Sí | Propietario o admin | Eliminar archivo de R2 y DB |
| **POST** | `/api/v1/uploads/presign` | ✅ Sí | Autenticado | URL presignada PUT (5 min) para subida directa |
| **POST** | `/api/v1/areas` | ✅ Sí | `admin` | Crear área con imágenes (`images[]`) y schedules |
| **PUT** | `/api/v1/areas/{id}` | ✅ Sí | `admin` | Actualizar área con `images[]` y `remove_file_ids[]` |
| **DELETE** | `/api/v1/areas/{id}` | ✅ Sí | `admin` | Eliminar área y sus imágenes asociadas |
| **POST** | `/api/v1/academies` | ✅ Sí | `admin` | Crear academia con imágenes y schedules |
| **PUT** | `/api/v1/academies/{id}` | ✅ Sí | `admin` | Actualizar academia con `images[]` y `remove_file_ids[]` |
| **DELETE** | `/api/v1/academies/{id}` | ✅ Sí | `admin` | Eliminar academia y sus imágenes asociadas |

---

## 3. Parámetros por endpoint

### 3.1. `GET /api/v1/uploads` (Público)

| Campo | Envío | Tipo | Reglas | Notas |
|-------|-------|------|--------|-------|
| `file_type` | query | string | opcional | Valores: `document`, `image`, `receipt`, `other` |
| `per_page` | query | int | opcional, default=15 | Paginación |

**Response:**
```json
{
  "success": true,
  "data": [FileResource],
  "meta": {
    "pagination": {
      "current_page": 1,
      "last_page": 5,
      "per_page": 15,
      "total": 73
    }
  }
}
```

**⚠️ Problema detectado:** No filtra por `visibility`, expone archivos `privado` y `restringido`.

---

### 3.2. `GET /api/v1/uploads/{id}` (Público)

| Campo | Envío | Tipo | Reglas | Notas |
|-------|-------|------|--------|-------|
| `id` | path | int | required | ID del archivo |

**Response:** `FileResource` completo (incluye `uploaded_by` con email/nombre del usuario).

**⚠️ Problema detectado:** Expone información del uploader en archivos públicos; para repositorio institucional debería ser opcional.

---

### 3.3. `POST /api/v1/uploads` (Autenticado)

| Campo | Envío | Tipo | Reglas | Notas |
|-------|-------|------|--------|-------|
| `file` | form-data | file | required, max:10240 (10MB) | Archivo a subir |
| `title` | form-data | string | opcional, max:200 | Título custom (default: nombre original) |
| `description` | form-data | string | opcional, max:1000 | Descripción |
| `file_type` | form-data | enum | opcional | Valores: `document`, `image`, `receipt`, `other`. Auto-detecta si no se pasa |

**Response:** `FileResource` con status 201.

**⚠️ Problema detectado:**
- No valida MIME types (permite subir cualquier archivo)
- No permite especificar `visibility` (siempre es `publico` por defecto según migración)
- Cualquier usuario autenticado puede subir; debería ser **solo admin** para documentos institucionales

---

### 3.4. `DELETE /api/v1/uploads/{id}` (Autenticado)

| Campo | Envío | Tipo | Reglas | Notas |
|-------|-------|------|--------|-------|
| `id` | path | int | required | ID del archivo |

**Autorización:**
- Propietario del archivo (`uploaded_by === auth()->user()->id`)
- O usuario con policy `delete` (no implementada aún)

**Response:** `{ success: true, message: "File deleted successfully" }`

---

### 3.5. `POST /api/v1/uploads/presign` (Autenticado)

| Campo | Envío | Tipo | Reglas | Notas |
|-------|-------|------|--------|-------|
| `filename` | body (json) | string | required, max:255 | Nombre del archivo |
| `content_type` | body (json) | string | required, max:100 | MIME type del archivo |

**MIME types permitidos:**
- `image/jpeg`, `image/png`, `image/gif`, `image/webp`
- `application/pdf`
- `text/plain`
- `application/msword`, `application/vnd.openxmlformats-officedocument.wordprocessingml.document`

**Response:**
```json
{
  "success": true,
  "data": {
    "url": "https://...",
    "method": "PUT",
    "headers": { "Content-Type": "..." },
    "key": "uploads/2025/10/11/filename.ext",
    "expires_in": 300
  }
}
```

**⚠️ Problema detectado:** No crea registro en DB; el frontend debe llamar posteriormente a otro endpoint para registrar el archivo subido (no documentado ni implementado).

---

### 3.6. `POST /api/v1/areas` (Admin only)

| Campo | Envío | Tipo | Reglas | Notas |
|-------|-------|------|--------|-------|
| `name` | form-data | string | required, max:150, unique | Nombre del área |
| `slug` | form-data | string | opcional, max:180, unique | Auto-generado si no se pasa |
| `description` | form-data | text | opcional | Descripción |
| `capacity` | form-data | int | opcional, min:1 | Aforo |
| `is_reservable` | form-data | boolean | opcional | Si acepta reservas |
| `is_active` | form-data | boolean | opcional | Si está activa |
| `images[]` | form-data | file[] | opcional, max:10 items | Archivos de imagen (JPEG/PNG/GIF/WebP), max 10MB c/u |
| `schedules[]` | form-data | array | opcional | Horarios de disponibilidad |

**Validación de imágenes:**
- `mimes:jpeg,png,jpg,gif,webp`
- `max:10240` (10MB por imagen)
- Se suben a R2 con `file_type=image` y `visibility=publico`
- Se relacionan mediante `entity_files` (polimórfica)

---

### 3.7. `PUT /api/v1/areas/{id}` (Admin only)

| Campo | Envío | Tipo | Reglas | Notas |
|-------|-------|------|--------|-------|
| *(campos de POST)* | form-data | — | — | Mismos campos que POST |
| `remove_file_ids[]` | form-data | int[] | opcional | IDs de archivos a eliminar (existe validación `exists:files,id`) |

**Lógica de eliminación:**
- Elimina relación `entity_files`
- Si el archivo ya no tiene referencias en otras entidades → elimina de R2 y DB
- Si tiene referencias → solo elimina la relación

---

### 3.8. Academies endpoints (similares a Areas)

Misma estructura que Areas para `POST /api/v1/academies` y `PUT /api/v1/academies/{id}`.

---

## 4. Modelos y DB (estado actual)

### 4.1. Tabla `files` ✅ (Principal)

| Campo | Tipo | Notas |
|-------|------|-------|
| `id` | bigint PK | — |
| `title` | varchar(200) | Título del archivo |
| `original_filename` | varchar(255) | Nombre original |
| `file_path` | varchar(255) | Ruta en R2 |
| `mime_type` | varchar(100) | Tipo MIME |
| `file_size` | bigint | Tamaño en bytes |
| `file_hash` | varchar(64), nullable | SHA-256 para deduplicación (no implementada) |
| `file_type` | enum | `document`, `image`, `receipt`, `other` |
| `storage_disk` | varchar(50) | Default: `r2` |
| `metadata` | json, nullable | Metadatos adicionales (IP, user agent, etc.) |
| `visibility` | enum | **`publico`, `privado`, `restringido`** (default: `publico`) |
| `uploaded_by` | FK users | Usuario que subió el archivo |
| `description` | text, nullable | Descripción opcional |
| `created_at`, `updated_at` | timestamps | — |
| `deleted_at` | timestamp, nullable | Soft delete |

**Índices:**
- `file_hash`, `file_type`, `storage_disk`, `uploaded_by`
- Compuestos: `(uploaded_by, file_type)`, `(file_type, visibility)`

**Relaciones:**
- `uploadedBy()` → `User`
- `entityFiles()` → `EntityFile` (polimórfica inversa)

**Scopes:**
- `ofType(string)` → filtra por `file_type`
- `onDisk(string)` → filtra por `storage_disk`
- `byUser(?int)` → filtra por `uploaded_by` (null = públicos)
- `withVisibility(string)` → filtra por `visibility` ✅ **Existe pero NO se usa en endpoints**

---

### 4.2. Tabla `documents` ⚠️ (Obsoleta)

| Campo | Tipo | Notas |
|-------|------|-------|
| `id` | bigint PK | — |
| `title` | varchar(200) | — |
| `file_url` | varchar(255) | Ruta como string (sin FK a `files`) |
| `visibility` | enum | `publico`, `interno`, `solo_admin` (diferentes valores a `files`) |
| `uploaded_by` | FK users | — |
| `description` | text, nullable | — |
| `created_at`, `updated_at` | timestamps | — |
| `deleted_at` | timestamp, nullable | Soft delete |

**⚠️ Problemas críticos:**
- No tiene FK a `files` → duplica lógica de almacenamiento
- Usa enum `visibility` con valores distintos (`interno`, `solo_admin` vs `privado`, `restringido`)
- **No se usa en ningún endpoint ni controlador**
- Tiene seeder (`DocumentsSeeder`) que crea un registro con `file_url = 'TBD'` (placeholder sin archivo real)

**Recomendación:** **Deprecar y eliminar tabla `documents`**. Consolidar todo en `files`.

---

### 4.3. Tabla `entity_files` ✅ (Relación polimórfica)

| Campo | Tipo | Notas |
|-------|------|-------|
| `id` | bigint PK | — |
| `entity_type` | varchar(50) | `Area`, `Academy` (puede extenderse) |
| `entity_id` | bigint | ID de la entidad |
| `file_id` | FK files, cascade | Archivo relacionado |
| `sort_order` | int | Orden de visualización |
| `caption` | varchar(255), nullable | Leyenda de la imagen |
| `is_cover` | boolean | Si es imagen de portada |

**Índices:**
- `(entity_type, entity_id)`, `(entity_type, entity_id, sort_order)`, `(entity_type, entity_id, is_cover)`
- **Unique:** `(entity_type, entity_id, file_id)` → previene duplicados

**Uso actual:** Solo para imágenes de Areas y Academies.

---

### 4.4. Tabla `audit_logs` ✅

| Campo | Tipo | Notas |
|-------|------|-------|
| `id` | bigint PK | — |
| `user_id` | FK users, nullable | Usuario que realizó la acción |
| `entity_type` | varchar(120) | Tipo de entidad (`File`, `Area`, `Academy`, etc.) |
| `entity_id` | bigint, nullable | ID de la entidad |
| `action` | varchar(120) | `file_uploaded`, `file_deleted`, `area_created`, etc. |
| `before` | json, nullable | Estado anterior |
| `after` | json, nullable | Estado posterior |
| `created_at`, `updated_at` | timestamps | — |

**Cobertura de auditoría para archivos:**
- ✅ `file_uploaded` para PDF/DOC/DOCX (en `R2Storage::logFileUpload`)
- ✅ `file_deleted` para PDF/DOC/DOCX (en `R2Storage::logFileDeletion`)
- ❌ **No auditada:** subida/borrado de imágenes
- ❌ **No auditada:** cambios en `visibility` o `file_type`

---

## 5. Matriz de acceso y visibilidad

| Caso | Endpoint | ¿Requiere login? | Rol | `visibility` | ¿Incluye imágenes? | Estado actual |
|------|----------|------------------|-----|--------------|-------------------|---------------|
| **Listado público documentos** | `GET /api/v1/files/public` | ❌ No | — | `publico` | ❌ Solo PDF/DOC/DOCX | ❌ **NO EXISTE** |
| **Listado interno todos** | `GET /api/v1/uploads` | ❌ No (⚠️) | — | **todos** (⚠️) | ✅ Sí | ⚠️ **PÚBLICO**, debería requerir auth + roles ≠ `usuario` |
| **Ver detalle archivo** | `GET /api/v1/uploads/{id}` | ❌ No (⚠️) | — | **todos** (⚠️) | ✅ Sí | ⚠️ **PÚBLICO**, debería respetar `visibility` |
| **Subir archivo** | `POST /api/v1/uploads` | ✅ Sí | **Cualquiera** (⚠️) | `publico` (hardcoded) | ✅ Sí | ⚠️ Debería ser **solo admin** para docs institucionales |
| **Eliminar archivo** | `DELETE /api/v1/uploads/{id}` | ✅ Sí | Propietario o admin | — | ✅ Sí | ✅ OK |
| **Presign URL** | `POST /api/v1/uploads/presign` | ✅ Sí | Cualquiera | — | ✅ Sí | ⚠️ No registra en DB post-upload |

---

## 6. Brechas y cambios mínimos (checklist)

### Crítico (MVP)
- [ ] **Crear endpoint público** `GET /api/v1/files/public` para listar **solo documentos** (`file_type=document`) con `visibility=publico`
  - Excluir imágenes (`file_type!=image`)
  - Paginación estándar
  - Sin auth requerida
  - Response: `FileResource` sin exponer `uploaded_by` (o solo nombre, sin email)

- [ ] **Proteger endpoints actuales** `/api/v1/uploads/*`:
  - `GET /api/v1/uploads` → requiere `auth:sanctum` + roles ≠ `usuario`
  - `GET /api/v1/uploads/{id}` → requiere `auth:sanctum` + verificar `visibility` (público sin auth, privado/restringido con auth)
  - `POST /api/v1/uploads` → solo `admin` para `file_type=document`

- [ ] **Validar MIME types** en `POST /api/v1/uploads`:
  - Para `file_type=document`: solo `application/pdf`, `application/msword`, `application/vnd.openxmlformats-officedocument.wordprocessingml.document`
  - Para `file_type=image`: `image/jpeg`, `image/png`, `image/gif`, `image/webp`
  - Rechazar otros MIME types con 422

- [ ] **Permitir especificar `visibility`** en `POST /api/v1/uploads`:
  - Nuevo campo `visibility` (opcional, default=`privado`)
  - Solo `admin` puede subir con `visibility=publico`

### Alto (Consolidación)
- [ ] **Deprecar tabla `documents`**:
  - Crear migración para eliminar tabla `documents`
  - Eliminar seeder `DocumentsSeeder`
  - Eliminar modelo `Document`
  - Actualizar enum `DocumentVisibility` para unificar valores con `files.visibility`

- [ ] **Implementar filtro por `visibility`** en `GET /api/v1/uploads`:
  - Nuevo query param `visibility` (opcional)
  - Usar scope `withVisibility()` existente

- [ ] **Auditoría completa**:
  - Extender logging de `R2Storage` para incluir **todas** las subidas/borrados (no solo PDF/DOC/DOCX)
  - Registrar cambios en `visibility` y `file_type` (observers en modelo `File`)

### Medio (Mejoras)
- [ ] **Implementar deduplicación real**:
  - Actualmente solo calcula `file_hash` pero no previene duplicados
  - Opción 1: Antes de subir, buscar archivo con mismo hash y retornar referencia existente
  - Opción 2: Documentar que deduplicación está deshabilitada por diseño

- [ ] **Completar flujo de presign**:
  - Endpoint `POST /api/v1/uploads/confirm` para registrar archivo subido vía presign
  - Parámetros: `key`, `file_size`, `mime_type`, `title`, `description`, `visibility`

- [ ] **Filtros avanzados** en `GET /api/v1/uploads` y `GET /api/v1/files/public`:
  - `q` (búsqueda por `title` o `description`)
  - `mime_type` (filtro por MIME exacto)
  - `uploaded_by` (solo para admin)
  - `date_from`, `date_to` (rango de `created_at`)

- [ ] **Policy para archivos**:
  - `FilePolicy` con métodos `view()`, `update()`, `delete()`
  - Lógica: admin = todos, usuario = solo propios + públicos

### Bajo (Cosmético)
- [ ] **Unificar naming**:
  - Rutas: `/api/v1/files/*` en lugar de `/api/v1/uploads/*` para consistencia con modelo
  - Mantener alias `/uploads` para backward compatibility

- [ ] **Resource condicional**:
  - `FileResource` con variante pública que oculta `uploaded_by.email` y `metadata`

- [ ] **Documentar `metadata`**:
  - Especificar estructura JSON en docs (`user_agent`, `ip_address`, `uploaded_via`, etc.)

---

## 7. Plan de migración/limpieza

### 7.1. Eliminar tabla `documents`

**Orden de ejecución:**
1. Verificar que no hay datos reales en `documents` (solo seed con `file_url='TBD'`)
2. Eliminar seeder `DocumentsSeeder.php`
3. Eliminar modelo `Document.php`
4. Crear migración `2025_10_12_000001_drop_documents_table.php`:
   ```php
   Schema::dropIfExists('documents');
   ```
5. Actualizar enum `DocumentVisibility` para eliminar `Interno` y `SoloAdmin`, dejar solo `Publico`, `Privado`, `Restringido`

**Notas:**
- Fecha sugerida: `2025_10_12_000001` (después de última migración `2025_09_26_221701_create_entity_files.php`)
- No requiere migración de datos (tabla vacía salvo seed)

### 7.2. Comando Artisan recomendado

**Cleanup de archivos huérfanos:**
```bash
php artisan files:cleanup-orphaned
```

Implementar como `App\Console\Commands\CleanupOrphanedFilesCommand`:
- Llama a `R2Storage::cleanupOrphanedFiles()`
- Reporta cantidad de archivos eliminados
- Ejecutar periódicamente (scheduler semanal)

---

## 8. Especificación de API objetivo (MVP, sin código)

### 8.1. Endpoint público para repositorio institucional

**`GET /api/v1/files/public`**
- **Autenticación:** No requerida
- **Propósito:** Listar documentos institucionales públicos (PDF/DOC/DOCX)
- **Filtros:**
  - `q` (búsqueda en `title` y `description`)
  - `per_page` (default: 15)
- **Criterios de inclusión:**
  - `file_type = 'document'`
  - `visibility = 'publico'`
  - `deleted_at IS NULL`
- **Response:** `FileResource` simplificado (sin email de `uploaded_by`)

---

### 8.2. Endpoint protegido para listado interno

**`GET /api/v1/files`** (renombrar de `/uploads`)
- **Autenticación:** `auth:sanctum`
- **Roles permitidos:** `profesor`, `instructor`, `administrador`, `obrero`, `estudiante`, `invitado` (todos excepto `usuario`)
- **Propósito:** Listar todos los archivos (documentos e imágenes)
- **Filtros:**
  - `file_type` (document, image, receipt, other)
  - `visibility` (publico, privado, restringido)
  - `q` (búsqueda)
  - `per_page`
- **Autorización:**
  - Admin: ve todo
  - Otros roles: solo archivos con `visibility=publico` o propios (`uploaded_by=auth()->id`)

---

### 8.3. Detalle de archivo con control de visibilidad

**`GET /api/v1/files/{id}`** (renombrar de `/uploads/{id}`)
- **Autenticación:** Condicional
  - Sin auth: solo si `visibility=publico`
  - Con auth: según `visibility` y ownership
- **Autorización:**
  - `publico`: todos
  - `privado`: solo uploader y admin
  - `restringido`: solo admin

---

### 8.4. Subir documento institucional

**`POST /api/v1/files`** (renombrar de `/uploads`)
- **Autenticación:** `auth:sanctum`
- **Roles permitidos:** `administrador` (solo admin puede subir documentos)
- **Validación:**
  - `file`: required, file, max:10240 (10MB)
  - `title`: nullable, string, max:200
  - `description`: nullable, string, max:1000
  - `file_type`: required, enum (document, image, receipt, other)
  - `visibility`: nullable, enum (publico, privado, restringido), default=privado
  - **MIME validation:**
    - Si `file_type=document`: solo PDF/DOC/DOCX
    - Si `file_type=image`: solo JPEG/PNG/GIF/WebP
- **Response:** `FileResource` con status 201

---

### 8.5. Eliminar archivo

**`DELETE /api/v1/files/{id}`** (renombrar de `/uploads/{id}`)
- **Autenticación:** `auth:sanctum`
- **Autorización:** Admin o propietario del archivo
- **Response:** `{ success: true, message: "File deleted successfully" }`

---

### 8.6. Presign URL (sin cambios funcionales)

**`POST /api/v1/files/presign`** (renombrar de `/uploads/presign`)
- **Autenticación:** `auth:sanctum`
- **Roles permitidos:** Admin (para documentos institucionales)
- **Validación:** (sin cambios)
- **Response:** URL presignada PUT con 5 min expiración

**⚠️ Pendiente:** Endpoint complementario `POST /api/v1/files/confirm` para registrar archivo post-upload.

---

## 9. Preguntas abiertas

1. **¿Cuál es la diferencia funcional entre `privado` y `restringido`?**
   - Actualmente ambos valores existen en enum de `files.visibility`, pero no hay lógica que los diferencie
   - Propuesta: `privado` = solo uploader y admin; `restringido` = lista específica de roles (requiere tabla `file_permissions`)

2. **¿Se debe exponer `metadata` (IP, user agent) en respuestas públicas?**
   - Actualmente `FileResource` expone todo el JSON `metadata`
   - Propuesta: Ocultar en endpoint público, mostrar solo para admin en endpoint interno

3. **¿Paginación: usar `per_page` o `limit/offset`?**
   - Actualmente usa `per_page` (Laravel Paginator estándar)
   - Confirmar si está alineado con frontend

4. **¿Naming de `file_type=document` es correcto?**
   - El enum usa `document` (singular)
   - Confirmar terminología institucional: ¿"documentos" o "archivos institucionales"?

5. **¿La deduplicación debe implementarse o documentarse como "no implementada"?**
   - Existe cálculo de `file_hash` pero no se usa para prevenir duplicados
   - Confirmar si es un feature futuro o debe removerse el campo

---

## 10. Anexos

### 10.1. Divergencias entre documentación y código

| Documentación | Código real | Severidad |
|---------------|-------------|-----------|
| `GET /api/v1/uploads` requiere auth | Es público | **Alta** |
| Deduplicación automática por hash | Solo calcula hash, no deduplica | **Media** |
| Tabla `documents` con FK a `files` | `documents.file_url` es string sin FK | **Alta** |
| Auditoría en todas las operaciones | Solo PDF/DOC/DOCX subidas/borrados | **Media** |
| `visibility` funcional en queries | Scope existe pero no se usa | **Alta** |

### 10.2. Archivos clave revisados

**Migraciones:**
- `database/migrations/2025_09_26_000354_create_files_table.php`
- `database/migrations/2025_09_16_134853_create_documents_table.php`
- `database/migrations/2025_09_26_221701_create_entity_files.php`
- `database/migrations/2025_09_16_134856_create_audit_logs_table.php`

**Modelos:**
- `app/Models/File.php` (con scopes `ofType`, `byUser`, `withVisibility`)
- `app/Models/Document.php` (sin uso real)
- `app/Models/EntityFile.php` (polimórfica)

**Controladores:**
- `app/Http/Controllers/UploadController.php` (CRUD de archivos)
- `app/Http/Controllers/Api/V1/AreaController.php` (imágenes de áreas)
- `app/Http/Controllers/Api/V1/AcademyController.php` (imágenes de academias)

**Servicios:**
- `app/Services/AreaService.php` (gestión de imágenes)
- `app/Services/AcademyService.php` (gestión de imágenes)
- `app/Support/R2Storage.php` (helper centralizado)

**Resources:**
- `app/Http/Resources/FileResource.php`

**Enums:**
- `app/Enums/DocumentVisibility.php` (Publico, Interno, SoloAdmin) ⚠️ No alineado con `files.visibility`

**Seeders:**
- `database/seeders/DocumentsSeeder.php` (crea registro placeholder con `file_url='TBD'`)

**Rutas:**
- `routes/api.php` (líneas 40-73: endpoints de uploads y áreas/academias)

---

**Fin del documento de reconocimiento**  
*Generado por Cursor AI el 11 de octubre de 2025*

