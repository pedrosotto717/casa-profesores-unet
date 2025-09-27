# CRUD API Documentation - Areas, Services, and Academies

## Overview

This document describes the complete CRUD REST API system for managing Areas, Services, and Academies with multi-image support using Cloudflare R2 storage. The system implements a layered architecture with proper authorization, validation, and file management.

## Architecture

### Components
- **Controllers**: Handle HTTP requests and delegate to services
- **Services**: Contain business logic and orchestrate operations
- **Form Requests**: Validate input data
- **API Resources**: Format response data
- **Models**: Eloquent models with relationships
- **Middleware**: AdminOnly middleware for authorization

### File Management
- **EntityFile Model**: Polymorphic relationship between entities and files
- **R2Storage Helper**: Centralized file operations with Cloudflare R2
- **Deduplication**: Files are deduplicated by SHA-256 hash
- **Cleanup Policy**: Files are deleted only when no longer referenced

## API Endpoints

### Areas

#### Public Endpoints (No Authentication Required)
- `GET /api/v1/areas` - List all areas
- `GET /api/v1/areas/{id}` - Get specific area

#### Admin Endpoints (Authentication + Admin Role Required)
- `POST /api/v1/areas` - Create new area
- `PUT /api/v1/areas/{id}` - Update area
- `DELETE /api/v1/areas/{id}` - Delete area

### Services

#### Public Endpoints (No Authentication Required)
- `GET /api/v1/services` - List all services
- `GET /api/v1/services/{id}` - Get specific service

#### Admin Endpoints (Authentication + Admin Role Required)
- `POST /api/v1/services` - Create new service
- `PUT /api/v1/services/{id}` - Update service
- `DELETE /api/v1/services/{id}` - Delete service

### Academies

#### Public Endpoints (No Authentication Required)
- `GET /api/v1/academies` - List all academies
- `GET /api/v1/academies/{id}` - Get specific academy

#### Admin Endpoints (Authentication + Admin Role Required)
- `POST /api/v1/academies` - Create new academy
- `PUT /api/v1/academies/{id}` - Update academy
- `DELETE /api/v1/academies/{id}` - Delete academy

## Request/Response Formats

### Create Area (POST /api/v1/areas)
```json
{
  "name": "Gimnasio Principal",
  "slug": "gimnasio-principal",
  "description": "Gimnasio equipado con máquinas de última generación",
  "capacity": 50,
  "hourly_rate": 25.00,
  "is_active": true,
  "images": [/* File objects */]
}
```

### Update Area (PUT /api/v1/areas/{id})
```json
{
  "name": "Gimnasio Principal Actualizado",
  "description": "Nueva descripción",
  "images": [/* New file objects */],
  "remove_file_ids": [1, 2, 3]
}
```

### Area Response
```json
{
  "data": {
    "id": 1,
    "name": "Gimnasio Principal",
    "slug": "gimnasio-principal",
    "description": "Gimnasio equipado con máquinas de última generación",
    "capacity": 50,
    "hourly_rate": 25.00,
    "is_active": true,
    "images": [
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
    ],
    "services_count": 3,
    "created_at": "2025-01-27T23:30:00.000000Z",
    "updated_at": "2025-01-27T23:30:00.000000Z"
  }
}
```

## Image Management

### Upload Format
- **Field Name**: `images[]` (array of files)
- **Supported Formats**: JPEG, PNG, JPG, GIF, WebP
- **Max Size**: 10MB per image
- **Max Count**: 10 images per entity

### Image Metadata
- **sort_order**: Display order (auto-assigned)
- **is_cover**: First image becomes cover if no existing images
- **caption**: Optional caption text
- **url**: Public R2 URL for direct access

### File Removal
- **Field Name**: `remove_file_ids[]` (array of file IDs)
- **Policy**: Files are deleted only if not referenced by other entities
- **Cleanup**: Automatic cleanup of orphaned files

## Query Parameters

### Areas
- `search` - Search by name
- `is_active` - Filter by active status

### Services
- `search` - Search by name
- `area_id` - Filter by area
- `is_active` - Filter by active status

### Academies
- `search` - Search by name
- `status` - Filter by status (activa, cerrada, cancelada)
- `lead_instructor_id` - Filter by instructor

## Validation Rules

### Areas
- `name`: Required, string, max 150 chars, unique
- `slug`: Optional, string, max 180 chars, unique
- `capacity`: Optional, integer, min 1
- `hourly_rate`: Optional, numeric, min 0
- `images`: Optional, array, max 10 items
- `images.*`: File, image, max 10MB

### Services
- `area_id`: Required, integer, exists in areas table
- `name`: Required, string, max 150 chars
- `hourly_rate`: Optional, numeric, min 0
- `images`: Optional, array, max 10 items
- `images.*`: File, image, max 10MB

### Academies
- `name`: Required, string, max 150 chars, unique
- `lead_instructor_id`: Required, integer, exists in users with instructor/professor role
- `status`: Optional, enum (activa, cerrada, cancelada)
- `images`: Optional, array, max 10 items
- `images.*`: File, image, max 10MB

## Authorization

### Public Access
- All `GET` endpoints are publicly accessible
- No authentication required for reading data

### Admin Access
- All `POST`, `PUT`, `DELETE` endpoints require admin role
- Middleware: `auth:sanctum` + `admin`
- Admin role validation: `UserRole::Administrador`

## Error Responses

### 401 Unauthorized
```json
{
  "message": "Authentication required.",
  "error": "UNAUTHENTICATED"
}
```

### 403 Forbidden
```json
{
  "message": "Access denied. Administrator privileges required.",
  "error": "INSUFFICIENT_PRIVILEGES"
}
```

### 422 Validation Error
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "name": ["The area name is required."],
    "images.0": ["Each image must be smaller than 10MB."]
  }
}
```

## Database Schema

### entity_files Table
```sql
CREATE TABLE entity_files (
    id BIGINT PRIMARY KEY,
    entity_type VARCHAR(50), -- 'Area', 'Service', 'Academy'
    entity_id BIGINT,
    file_id BIGINT,
    sort_order INT DEFAULT 0,
    caption VARCHAR(255) NULL,
    is_cover BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    INDEX idx_entity_type_id (entity_type, entity_id),
    INDEX idx_entity_type_id_sort (entity_type, entity_id, sort_order),
    INDEX idx_entity_type_id_cover (entity_type, entity_id, is_cover),
    UNIQUE KEY unique_entity_file (entity_type, entity_id, file_id),
    FOREIGN KEY (file_id) REFERENCES files(id) ON DELETE CASCADE
);
```

## Usage Examples

### Frontend Integration (React)
```javascript
// Fetch areas for landing page
const fetchAreas = async () => {
  const response = await fetch('/api/v1/areas');
  const data = await response.json();
  return data.data;
};

// Create new area (admin only)
const createArea = async (areaData, images) => {
  const formData = new FormData();
  
  // Add text fields
  Object.keys(areaData).forEach(key => {
    formData.append(key, areaData[key]);
  });
  
  // Add images
  images.forEach(image => {
    formData.append('images[]', image);
  });
  
  const response = await fetch('/api/v1/areas', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
    },
    body: formData
  });
  
  return response.json();
};
```

## Security Considerations

1. **File Validation**: All uploaded files are validated for type and size
2. **Deduplication**: Files are deduplicated by hash to prevent storage waste
3. **Authorization**: Admin-only access to write operations
4. **Cleanup**: Automatic cleanup of orphaned files
5. **Audit Trail**: File operations are logged for compliance

## Performance Optimizations

1. **Eager Loading**: Images are loaded with entities using `with()`
2. **Indexes**: Database indexes on frequently queried fields
3. **Caching**: R2 provides built-in CDN caching
4. **Deduplication**: Prevents duplicate file storage

---

*Documentation updated: January 27, 2025*

