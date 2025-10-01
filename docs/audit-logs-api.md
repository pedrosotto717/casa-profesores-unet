# Audit Logs API Documentation

## Overview

The Audit Logs API provides access to system audit trails for compliance and security monitoring. All audit log endpoints are **admin-only** and require authentication with admin privileges.

## Endpoints

### GET /api/v1/audit-logs

Retrieve a paginated list of audit logs with optional filtering.

**Authentication:** Required (admin only)

**Query Parameters:**

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `entity_type` | string | Filter by entity type | `User`, `Area`, `Academy`, `Invitation`, `File` |
| `entity_id` | integer | Filter by specific entity ID | `123` |
| `action` | string | Filter by action type | `user_created`, `area_updated`, `file_uploaded` |
| `user_id` | integer | Filter by user who performed the action | `456` |
| `from` | date | Filter logs from this date (YYYY-MM-DD) | `2025-01-01` |
| `to` | date | Filter logs until this date (YYYY-MM-DD) | `2025-01-31` |
| `q` | string | Search in before/after JSON data | `password` |
| `per_page` | integer | Number of results per page (max 100) | `25` |
| `page` | integer | Page number for pagination | `2` |

**Response Example:**

```json
{
  "data": [
    {
      "id": 1,
      "created_at": "2025-01-27T10:30:00.000000Z",
      "action": "user_created",
      "actor": {
        "id": 1,
        "name": "Admin User",
        "role": "admin"
      },
      "entity": {
        "type": "User",
        "id": 123,
        "label": "User #123"
      },
      "before": null,
      "after": {
        "name": "John Doe",
        "email": "john@example.com",
        "role": "teacher",
        "password": "[REDACTED]"
      }
    }
  ],
  "links": {
    "first": "http://localhost/api/v1/audit-logs?page=1",
    "last": "http://localhost/api/v1/audit-logs?page=10",
    "prev": null,
    "next": "http://localhost/api/v1/audit-logs?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 10,
    "per_page": 15,
    "to": 15,
    "total": 150
  }
}
```

### GET /api/v1/audit-logs/{id}

Retrieve a specific audit log entry.

**Authentication:** Required (admin only)

**Path Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `id` | integer | Audit log ID |

**Response Example:**

```json
{
  "data": {
    "id": 1,
    "created_at": "2025-01-27T10:30:00.000000Z",
    "action": "area_updated",
    "actor": {
      "id": 1,
      "name": "Admin User",
      "role": "admin"
    },
    "entity": {
      "type": "Area",
      "id": 5,
      "label": "Area #5"
    },
    "before": {
      "name": "Old Area Name",
      "description": "Old description",
      "capacity": 50
    },
    "after": {
      "name": "New Area Name",
      "description": "Updated description",
      "capacity": 75
    }
  }
}
```

## Available Actions

The system tracks the following actions:

### User Actions
- `user_created` - New user registration
- `user_updated` - User profile updates
- `user_deleted` - User account deletion

### Area Actions
- `area_created` - New area creation
- `area_updated` - Area information updates
- `area_deleted` - Area deletion

### Academy Actions
- `academy_created` - New academy creation
- `academy_updated` - Academy information updates
- `academy_deleted` - Academy deletion

### Invitation Actions
- `invitation_created` - New invitation sent
- `invitation_approved` - Invitation approved
- `invitation_rejected` - Invitation rejected

### File Actions
- `file_uploaded` - File upload to R2 storage
- `file_deleted` - File deletion from R2 storage

## Security Features

### Data Sanitization

Sensitive fields are automatically redacted in the `before` and `after` data:

- `password`
- `password_confirmation`
- `remember_token`
- `api_token`
- `access_token`
- `refresh_token`
- `secret`
- `private_key`
- `ssn`
- `social_security_number`
- `credit_card`
- `bank_account`

### Access Control

- All endpoints require authentication via Laravel Sanctum
- Admin role is required for all audit log access
- No public access to audit logs

## Error Responses

### 401 Unauthorized
```json
{
  "message": "Unauthenticated."
}
```

### 403 Forbidden
```json
{
  "message": "This action is unauthorized."
}
```

### 404 Not Found
```json
{
  "message": "No query results for model [App\\Models\\AuditLog] {id}"
}
```

---

*Documentation updated: January 27, 2025*
