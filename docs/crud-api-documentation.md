# CRUD API Documentation - Areas and Academies

## Overview

This document describes the complete CRUD REST API system for managing Areas and Academies with multi-image support and scheduling systems using Cloudflare R2 storage. The system implements a layered architecture with proper authorization, validation, file management, and comprehensive audit logging.

**Note:** The Services system has been removed and replaced with a `is_reservable` field in Areas and a comprehensive scheduling system for both Areas and Academies.

## Architecture

### Components
- **Controllers**: Handle HTTP requests and delegate to services
- **Services**: Contain business logic and orchestrate operations
- **Form Requests**: Validate input data including schedules
- **API Resources**: Format response data with relationships
- **Models**: Eloquent models with relationships and scheduling
- **Middleware**: AdminOnly middleware for authorization
- **Audit Logging**: Comprehensive logging of all operations

### File Management
- **EntityFile Model**: Polymorphic relationship between entities and files
- **R2Storage Helper**: Centralized file operations with Cloudflare R2
- **Deduplication**: Files are deduplicated by SHA-256 hash
- **Cleanup Policy**: Files are deleted only when no longer referenced

### Scheduling System
- **Area Schedules**: Define availability for reservable areas
- **Academy Schedules**: Define class schedules with areas and capacity
- **Day of Week**: ISO 8601 standard (1=Monday, 7=Sunday)
- **Time Format**: HH:MM format for start and end times

## API Endpoints

### Areas

#### Public Endpoints (No Authentication Required)
- `GET /api/v1/areas` - List all areas
- `GET /api/v1/areas/{id}` - Get specific area

#### Admin Endpoints (Authentication + Admin Role Required)
- `POST /api/v1/areas` - Create new area
- `PUT /api/v1/areas/{id}` - Update area
- `DELETE /api/v1/areas/{id}` - Delete area


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
  "is_reservable": true,
  "is_active": true,
  "images": [/* File objects */],
  "schedules": [
    {
      "day_of_week": 1,
      "start_time": "06:00",
      "end_time": "22:00",
      "is_open": true
    },
    {
      "day_of_week": 2,
      "start_time": "06:00",
      "end_time": "22:00",
      "is_open": true
    }
  ]
}
```

### Update Area (PUT /api/v1/areas/{id})
```json
{
  "name": "Gimnasio Principal Actualizado",
  "description": "Nueva descripción",
  "is_reservable": true,
  "images": [/* New file objects */],
  "remove_file_ids": [1, 2, 3],
  "schedules": [
    {
      "day_of_week": 1,
      "start_time": "06:00",
      "end_time": "22:00",
      "is_open": true
    }
  ]
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
    "is_reservable": true,
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
    "schedules": [
      {
        "id": 1,
        "day_of_week": 1,
        "start_time": "06:00",
        "end_time": "22:00",
        "is_open": true
      },
      {
        "id": 2,
        "day_of_week": 2,
        "start_time": "06:00",
        "end_time": "22:00",
        "is_open": true
      }
    ],
    "created_at": "2025-01-27T23:30:00.000000Z",
    "updated_at": "2025-01-27T23:30:00.000000Z"
  }
}
```

### Create Academy (POST /api/v1/academies)
```json
{
  "name": "Escuela de Natación",
  "description": "Academia de natación para todas las edades",
  "lead_instructor_id": 1,
  "status": "activa",
  "images": [/* File objects */],
  "schedules": [
    {
      "area_id": 1,
      "day_of_week": 1,
      "start_time": "16:00",
      "end_time": "18:00",
      "capacity": 20
    },
    {
      "area_id": 1,
      "day_of_week": 3,
      "start_time": "16:00",
      "end_time": "18:00",
      "capacity": 20
    }
  ]
}
```

### Update Academy (PUT /api/v1/academies/{id})
```json
{
  "name": "Escuela de Natación Avanzada",
  "description": "Nueva descripción",
  "lead_instructor_id": 2,
  "status": "activa",
  "images": [/* New file objects */],
  "remove_file_ids": [1, 2, 3],
  "schedules": [
    {
      "area_id": 1,
      "day_of_week": 1,
      "start_time": "16:00",
      "end_time": "18:00",
      "capacity": 25
    }
  ]
}
```

### Academy Response
```json
{
  "data": {
    "id": 1,
    "name": "Escuela de Natación",
    "description": "Academia de natación para todas las edades",
    "status": "activa",
    "lead_instructor": {
      "id": 1,
      "name": "Juan Pérez",
      "email": "juan.perez@unet.edu.ve"
    },
    "images": [
      {
        "id": 1,
        "url": "https://pub-...r2.dev/natacion.jpg",
        "title": "natacion-clase.jpg",
        "caption": null,
        "is_cover": true,
        "sort_order": 1,
        "file_size": 1024000,
        "mime_type": "image/jpeg"
      }
    ],
    "schedules": [
      {
        "id": 1,
        "area_id": 1,
        "area_name": "Piscina",
        "day_of_week": 1,
        "start_time": "16:00",
        "end_time": "18:00",
        "capacity": 20
      },
      {
        "id": 2,
        "area_id": 1,
        "area_name": "Piscina",
        "day_of_week": 3,
        "start_time": "16:00",
        "end_time": "18:00",
        "capacity": 20
      }
    ],
    "schedules_count": 2,
    "enrollments_count": 15,
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
- `is_reservable` - Filter by reservable status

### Academies
- `search` - Search by name
- `status` - Filter by status (activa, cerrada, cancelada)
- `lead_instructor_id` - Filter by instructor

## Validation Rules

### Areas
- `name`: Required, string, max 150 chars, unique
- `slug`: Optional, string, max 180 chars, unique
- `capacity`: Optional, integer, min 1
- `is_reservable`: Optional, boolean
- `is_active`: Optional, boolean
- `images`: Optional, array, max 10 items
- `images.*`: File, image, max 10MB
- `schedules`: Optional, array
- `schedules.*.day_of_week`: Required with schedules, integer, min 1, max 7
- `schedules.*.start_time`: Required with schedules, date_format H:i
- `schedules.*.end_time`: Required with schedules, date_format H:i, after start_time
- `schedules.*.is_open`: Required with schedules, boolean

### Academies
- `name`: Required, string, max 150 chars, unique
- `description`: Optional, string
- `lead_instructor_id`: Required, integer, exists in users with instructor/professor role
- `status`: Optional, enum (activa, cerrada, cancelada)
- `images`: Optional, array, max 10 items
- `images.*`: File, image, max 10MB
- `schedules`: Optional, array
- `schedules.*.area_id`: Required with schedules, integer, exists in areas
- `schedules.*.day_of_week`: Required with schedules, integer, min 1, max 7
- `schedules.*.start_time`: Required with schedules, date_format H:i
- `schedules.*.end_time`: Required with schedules, date_format H:i, after start_time
- `schedules.*.capacity`: Optional, integer, min 1, max 100

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

### areas Table
```sql
CREATE TABLE areas (
    id BIGINT PRIMARY KEY,
    name VARCHAR(150) UNIQUE,
    slug VARCHAR(180) UNIQUE,
    description TEXT,
    capacity INT,
    is_reservable BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL
);
```

### area_schedules Table
```sql
CREATE TABLE area_schedules (
    id BIGINT PRIMARY KEY,
    area_id BIGINT,
    day_of_week TINYINT, -- 1=Monday, 7=Sunday
    start_time TIME,
    end_time TIME,
    is_open BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (area_id) REFERENCES areas(id) ON DELETE CASCADE,
    UNIQUE KEY unique_area_schedule (area_id, day_of_week, start_time, end_time)
);
```

### academies Table
```sql
CREATE TABLE academies (
    id BIGINT PRIMARY KEY,
    name VARCHAR(150) UNIQUE,
    description TEXT,
    lead_instructor_id BIGINT,
    status ENUM('activa', 'cerrada', 'cancelada') DEFAULT 'activa',
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (lead_instructor_id) REFERENCES users(id)
);
```

### academy_schedules Table
```sql
CREATE TABLE academy_schedules (
    id BIGINT PRIMARY KEY,
    academy_id BIGINT,
    area_id BIGINT,
    day_of_week TINYINT, -- 1=Monday, 7=Sunday
    start_time TIME,
    end_time TIME,
    capacity INT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (academy_id) REFERENCES academies(id) ON DELETE CASCADE,
    FOREIGN KEY (area_id) REFERENCES areas(id),
    UNIQUE KEY unique_academy_schedule (academy_id, day_of_week, start_time, end_time)
);
```

### entity_files Table
```sql
CREATE TABLE entity_files (
    id BIGINT PRIMARY KEY,
    entity_type VARCHAR(50), -- 'Area', 'Academy'
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

### audit_logs Table
```sql
CREATE TABLE audit_logs (
    id BIGINT PRIMARY KEY,
    user_id BIGINT,
    entity_type VARCHAR(50),
    entity_id BIGINT,
    action VARCHAR(100),
    before JSON,
    after JSON,
    created_at TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_user (user_id),
    INDEX idx_action (action)
);
```

## Usage Examples

### Frontend Integration (React)
```javascript
// Fetch areas with schedules
const fetchAreas = async () => {
  const response = await fetch('/api/v1/areas');
  const data = await response.json();
  return data.data;
};

// Create new area with schedules (admin only)
const createArea = async (areaData, images, schedules) => {
  const formData = new FormData();
  
  // Add text fields
  Object.keys(areaData).forEach(key => {
    formData.append(key, areaData[key]);
  });
  
  // Add schedules
  if (schedules) {
    schedules.forEach((schedule, index) => {
      Object.keys(schedule).forEach(key => {
        formData.append(`schedules[${index}][${key}]`, schedule[key]);
      });
    });
  }
  
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

// Create academy with schedules (admin only)
const createAcademy = async (academyData, images, schedules) => {
  const formData = new FormData();
  
  // Add text fields
  Object.keys(academyData).forEach(key => {
    formData.append(key, academyData[key]);
  });
  
  // Add schedules
  if (schedules) {
    schedules.forEach((schedule, index) => {
      Object.keys(schedule).forEach(key => {
        formData.append(`schedules[${index}][${key}]`, schedule[key]);
      });
    });
  }
  
  // Add images
  images.forEach(image => {
    formData.append('images[]', image);
  });
  
  const response = await fetch('/api/v1/academies', {
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
5. **Audit Trail**: All operations are logged for compliance and security
6. **Input Validation**: Comprehensive validation of all input data including schedules
7. **SQL Injection Protection**: Eloquent ORM provides built-in protection

## Performance Optimizations

1. **Eager Loading**: Images and schedules are loaded with entities using `with()`
2. **Indexes**: Database indexes on frequently queried fields
3. **Caching**: R2 provides built-in CDN caching
4. **Deduplication**: Prevents duplicate file storage
5. **Transaction Safety**: All operations use database transactions for consistency

## Audit Logging

All create, update, and delete operations are automatically logged in the `audit_logs` table with:

- **User ID**: Who performed the action
- **Entity Type**: Area or Academy
- **Entity ID**: Which specific entity was modified
- **Action**: Type of operation (created, updated, deleted)
- **Before/After**: JSON data showing changes
- **Timestamp**: When the action occurred
- **IP Address**: Source IP of the request
- **User Agent**: Browser/client information

### Audit Log Example
```json
{
  "id": 1,
  "user_id": 1,
  "entity_type": "Academy",
  "entity_id": 1,
  "action": "academy_updated",
  "before": {
    "name": "Escuela de Natación",
    "description": "Descripción anterior",
    "status": "activa"
  },
  "after": {
    "name": "Escuela de Natación Avanzada",
    "description": "Nueva descripción",
    "status": "activa"
  },
  "created_at": "2025-09-28T19:30:00.000000Z"
}
```

## Day of Week Reference

The system uses ISO 8601 standard for day of week:

| Value | Day |
|-------|-----|
| 1 | Monday |
| 2 | Tuesday |
| 3 | Wednesday |
| 4 | Thursday |
| 5 | Friday |
| 6 | Saturday |
| 7 | Sunday |

---

*Documentation updated: September 28, 2025*

