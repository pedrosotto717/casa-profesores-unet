# Sistema de Gestión de Archivos con Cloudflare R2 - UNET Casa del Profesor

## 1. Integración con Cloudflare R2

### Configuración R2
El sistema utiliza Cloudflare R2 como almacenamiento principal de archivos, configurado como disco S3-compatible en Laravel.

**Variables de entorno requeridas:**
```env
R2_ACCESS_KEY_ID=your_access_key_id
R2_SECRET_ACCESS_KEY=your_secret_access_key
R2_REGION=auto
R2_BUCKET=your_bucket_name
R2_ENDPOINT=https://<ACCOUNT_ID>.r2.cloudflarestorage.com
R2_PUBLIC_BASE_URL=https://pub-...r2.dev
AWS_USE_PATH_STYLE_ENDPOINT=false
```

**Configuración CORS para R2:**
```json
[{
  "AllowedOrigins": ["http://localhost:5173", "https://your-app.example.com"],
  "AllowedMethods": ["GET", "HEAD", "PUT", "POST"],
  "AllowedHeaders": ["Authorization", "Content-Type", "x-amz-acl", "x-amz-date", "x-amz-content-sha256", "x-amz-meta-*"],
  "ExposeHeaders": ["ETag"],
  "MaxAgeSeconds": 3000
}]
```

## 2. Arquitectura del Sistema

### Componentes Principales
- **Modelo File**: Gestiona todos los archivos subidos
- **R2Storage Helper**: Operaciones centralizadas de almacenamiento
- **UploadController**: Endpoints API para gestión de archivos
- **FileResource**: Formateo de respuestas API

### Flujo de Datos
```
Usuario → UploadController → R2Storage → R2 + Database + AuditLog
                ↓
        FileResource → API Response
```

## 3. Estructura de Base de Datos

### Tabla: `files`
| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | Identificador único |
| `title` | varchar(200) | Título del archivo |
| `original_filename` | varchar(255) | Nombre original |
| `file_path` | varchar(255) | Ruta en R2 |
| `mime_type` | varchar(100) | Tipo MIME |
| `file_size` | bigint | Tamaño en bytes |
| `file_hash` | varchar(64) | Hash SHA-256 para deduplicación |
| `file_type` | enum | Tipo: document, image, receipt, other |
| `storage_disk` | varchar(50) | Disco (r2) |
| `metadata` | json | Metadatos adicionales |
| `visibility` | enum | Visibilidad: publico, privado, restringido |
| `uploaded_by` | bigint | ID del usuario |
| `description` | text | Descripción opcional |

## 4. API Endpoints

### Subir Archivo
**POST** `/api/v1/uploads`
- **Body**: `file` (required), `title`, `description`, `file_type`
- **Límite**: 10MB
- **Respuesta**: FileResource con URL pública

### Listar Archivos
**GET** `/api/v1/uploads`
- **Query**: `file_type`, `per_page`
- **Respuesta**: Lista paginada de archivos del usuario

### Obtener Archivo
**GET** `/api/v1/uploads/{id}`
- **Respuesta**: Información completa del archivo

### Eliminar Archivo
**DELETE** `/api/v1/uploads/{id}`
- **Respuesta**: Confirmación de eliminación

### URL Presignada
**POST** `/api/v1/uploads/presign`
- **Body**: `filename`, `content_type`
- **Respuesta**: URL presignada para subida directa

## 5. Características Avanzadas

### Deduplicación
- Calcula hash SHA-256 del contenido
- Elimina archivos duplicados automáticamente
- Retorna referencia al archivo existente

### Auditoría
- Registra automáticamente subidas/eliminaciones de PDF y Word
- Información completa en `audit_logs`: usuario, archivo, timestamp, IP

### Tipos de Archivo Soportados
- **Imágenes**: JPEG, PNG, GIF, WebP
- **Documentos**: PDF, DOC, DOCX
- **Texto**: TXT

## 6. Uso del Helper R2Storage

```php
// Subir archivo con registro
$fileRecord = R2Storage::putPublicWithRecord(
    $file,
    $userId,
    'document',
    'Título',
    'Descripción'
);

// Buscar por hash (retorna todos los archivos con el mismo contenido)
$files = R2Storage::findFilesByHash($hash);

// Eliminar completamente
R2Storage::deleteFile($file);

// Limpiar archivos huérfanos
$count = R2Storage::cleanupOrphanedFiles();
```

## 7. Seguridad

- **Autenticación**: Requerida en todos los endpoints (Sanctum)
- **Autorización**: Usuarios solo pueden gestionar sus propios archivos
- **Validación**: Tipos MIME y extensiones permitidas
- **Límites**: Tamaño máximo configurable por tipo

## 8. Monitoreo y Mantenimiento

### Logs de Auditoría
Todos los archivos incluyen metadatos:
- Usuario que subió
- Fecha y hora
- IP y User Agent
- Método de subida

### Limpieza Periódica
```php
// Comando Artisan recomendado
$deletedCount = R2Storage::cleanupOrphanedFiles();
Log::info("Cleaned up {$deletedCount} orphaned files");
```

---

*Documentación actualizada el 27 de enero de 2025 - Sistema integrado con Cloudflare R2*
