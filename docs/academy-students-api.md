# Academy Students API Documentation

## Overview

This API allows instructors and administrators to manage lists of external students (not registered as system users) enrolled in academies. These students are tracked for academy management purposes only and do not have system access.

**Base URL:** `/api/v1/academies/{academy_id}/students`

**Authentication:** Required (`auth:sanctum`)

**Authorization:** 
- **Instructors:** Can only manage students for academies where they are assigned as the instructor
- **Administrators:** Can manage students for all academies

---

## Endpoints

### 1. List Academy Students

Get a paginated list of students enrolled in a specific academy.

**Endpoint:** `GET /api/v1/academies/{academy_id}/students`

**Authentication:** Required

**Authorization:** Instructor (own academy) or Administrator

#### Query Parameters

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `per_page` | integer | No | 15 | Number of students per page (1-100) |
| `status` | string | No | - | Filter by status: `solvente` or `insolvente` |

#### Request Example

```bash
GET /api/v1/academies/5/students?per_page=20&status=solvente
Authorization: Bearer {token}
```

#### Success Response (200 OK)

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "academy_id": 5,
      "name": "María González",
      "age": 12,
      "status": "solvente",
      "created_at": "2025-01-15T10:30:00.000000Z",
      "updated_at": "2025-01-15T10:30:00.000000Z"
    },
    {
      "id": 2,
      "academy_id": 5,
      "name": "Carlos Pérez",
      "age": 10,
      "status": "solvente",
      "created_at": "2025-01-14T14:20:00.000000Z",
      "updated_at": "2025-01-14T14:20:00.000000Z"
    }
  ],
  "meta": {
    "pagination": {
      "current_page": 1,
      "last_page": 3,
      "per_page": 20,
      "total": 45
    }
  },
  "message": "Students retrieved successfully"
}
```

#### Error Responses

**403 Forbidden** - Not authorized to view students for this academy
```json
{
  "success": false,
  "message": "Access denied. You do not have permission to view students for this academy."
}
```

**404 Not Found** - Academy not found
```json
{
  "success": false,
  "message": "Academy not found"
}
```

---

### 2. Create Student

Add a new student to an academy's enrollment list.

**Endpoint:** `POST /api/v1/academies/{academy_id}/students`

**Authentication:** Required

**Authorization:** Instructor (own academy) or Administrator

#### Request Body

| Field | Type | Required | Validation | Description |
|-------|------|----------|------------|-------------|
| `name` | string | Yes | max:200 | Full name of the student |
| `age` | integer | Yes | min:1, max:120 | Age of the student |
| `status` | string | No | in:solvente,insolvente | Payment status (default: `solvente`) |

#### Request Example

```bash
POST /api/v1/academies/5/students
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Ana Pérez",
  "age": 14,
  "status": "solvente"
}
```

#### Success Response (201 Created)

```json
{
  "success": true,
  "data": {
    "id": 15,
    "academy_id": 5,
    "name": "Ana Pérez",
    "age": 14,
    "status": "solvente",
    "created_at": "2025-01-16T09:45:00.000000Z",
    "updated_at": "2025-01-16T09:45:00.000000Z"
  },
  "message": "Student created successfully"
}
```

#### Error Responses

**422 Unprocessable Entity** - Validation error
```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "name": ["The student name is required."],
    "age": ["The age must be at least 1."]
  }
}
```

**403 Forbidden** - Not authorized
```json
{
  "success": false,
  "message": "Access denied. You do not have permission to create students for this academy."
}
```

---

### 3. Update Student

Update information for an existing student.

**Endpoint:** `PUT /api/v1/academies/{academy_id}/students/{student_id}`

**Authentication:** Required

**Authorization:** Instructor (own academy) or Administrator

#### Request Body

All fields are optional. Only include fields you want to update.

| Field | Type | Required | Validation | Description |
|-------|------|----------|------------|-------------|
| `name` | string | No | max:200 | Full name of the student |
| `age` | integer | No | min:1, max:120 | Age of the student |
| `status` | string | No | in:solvente,insolvente | Payment status |

#### Request Example

```bash
PUT /api/v1/academies/5/students/15
Authorization: Bearer {token}
Content-Type: application/json

{
  "status": "insolvente"
}
```

#### Success Response (200 OK)

```json
{
  "success": true,
  "data": {
    "id": 15,
    "academy_id": 5,
    "name": "Ana Pérez",
    "age": 14,
    "status": "insolvente",
    "created_at": "2025-01-16T09:45:00.000000Z",
    "updated_at": "2025-01-16T11:30:00.000000Z"
  },
  "message": "Student updated successfully"
}
```

#### Error Responses

**404 Not Found** - Student not found or doesn't belong to this academy
```json
{
  "success": false,
  "message": "Student does not belong to this academy"
}
```

**403 Forbidden** - Not authorized
```json
{
  "success": false,
  "message": "Access denied. You do not have permission to update this student."
}
```

**422 Unprocessable Entity** - Validation error
```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "age": ["The age must be a valid number."]
  }
}
```

---

### 4. Delete Student

Remove a student from an academy's enrollment list.

**Endpoint:** `DELETE /api/v1/academies/{academy_id}/students/{student_id}`

**Authentication:** Required

**Authorization:** Instructor (own academy) or Administrator

#### Request Example

```bash
DELETE /api/v1/academies/5/students/15
Authorization: Bearer {token}
```

#### Success Response (200 OK)

```json
{
  "success": true,
  "message": "Student deleted successfully"
}
```

#### Error Responses

**404 Not Found** - Student not found or doesn't belong to this academy
```json
{
  "success": false,
  "message": "Student does not belong to this academy"
}
```

**403 Forbidden** - Not authorized
```json
{
  "success": false,
  "message": "Access denied. You do not have permission to delete this student."
}
```

---

## Data Models

### AcademyStudent

| Field | Type | Description |
|-------|------|-------------|
| `id` | integer | Unique identifier |
| `academy_id` | integer | ID of the academy |
| `name` | string | Full name of the student |
| `age` | integer | Age of the student |
| `status` | string | Payment status: `solvente` or `insolvente` |
| `created_at` | string (ISO 8601) | Creation timestamp |
| `updated_at` | string (ISO 8601) | Last update timestamp |

---

## Status Values

### Student Status

- **`solvente`**: Student is up to date with payments
- **`insolvente`**: Student has pending payments

---

## Usage Flow

### For Instructors

1. **View students in your academy:**
   ```
   GET /api/v1/academies/{your_academy_id}/students
   ```

2. **Add a new student:**
   ```
   POST /api/v1/academies/{your_academy_id}/students
   ```

3. **Update student status (e.g., mark as insolvente):**
   ```
   PUT /api/v1/academies/{your_academy_id}/students/{student_id}
   ```

4. **Remove a student:**
   ```
   DELETE /api/v1/academies/{your_academy_id}/students/{student_id}
   ```

### For Administrators

Administrators have the same access as instructors but can manage students for **all academies**, not just their own.

---

## Error Codes

| Code | Description |
|------|-------------|
| 200 | Success |
| 201 | Created successfully |
| 400 | Bad request |
| 401 | Unauthorized (missing or invalid token) |
| 403 | Forbidden (insufficient permissions) |
| 404 | Resource not found |
| 422 | Validation error |
| 500 | Internal server error |

---

## Notes

1. **Students are NOT system users:** These records are for academy management only. Students do not have login access.

2. **Authorization is automatic:** The API automatically checks if an instructor can only access their own academy's students.

3. **Pagination:** All list endpoints use pagination. Adjust `per_page` as needed (max 100).

4. **Timestamps:** All timestamps are in ISO 8601 format with UTC timezone.

5. **Validation:** Server-side validation is strict. Always handle validation errors (422 responses).

---

## Example Frontend Integration

### React/TypeScript Example

```typescript
// API service
const API_BASE = 'https://your-domain.com/api/v1';

interface AcademyStudent {
  id: number;
  academy_id: number;
  name: string;
  age: number;
  status: 'solvente' | 'insolvente';
  created_at: string;
  updated_at: string;
}

// Fetch students
async function getAcademyStudents(academyId: number, token: string) {
  const response = await fetch(
    `${API_BASE}/academies/${academyId}/students`,
    {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
      },
    }
  );
  return await response.json();
}

// Create student
async function createStudent(
  academyId: number,
  data: { name: string; age: number; status?: 'solvente' | 'insolvente' },
  token: string
) {
  const response = await fetch(
    `${API_BASE}/academies/${academyId}/students`,
    {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify(data),
    }
  );
  return await response.json();
}

// Update student
async function updateStudent(
  academyId: number,
  studentId: number,
  data: Partial<{ name: string; age: number; status: 'solvente' | 'insolvente' }>,
  token: string
) {
  const response = await fetch(
    `${API_BASE}/academies/${academyId}/students/${studentId}`,
    {
      method: 'PUT',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify(data),
    }
  );
  return await response.json();
}

// Delete student
async function deleteStudent(
  academyId: number,
  studentId: number,
  token: string
) {
  const response = await fetch(
    `${API_BASE}/academies/${academyId}/students/${studentId}`,
    {
      method: 'DELETE',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
      },
    }
  );
  return await response.json();
}
```

---

## Support

For additional help or to report issues, please contact the development team or refer to the main API documentation.

