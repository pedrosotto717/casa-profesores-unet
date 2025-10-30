# Módulo de Beneficiarios - Guía de Implementación Frontend

## Resumen del Módulo

El módulo de Beneficiarios permite a los profesores (agremiados) gestionar su grupo familiar. Los beneficiarios **NO son usuarios del sistema** - no tienen login ni acceso directo. Son entidades asociadas a un profesor que requieren aprobación administrativa.

## Flujo de Negocio

1. **Profesor** crea un beneficiario → Estatus: `pendiente`
2. **Administrador** revisa y aprueba/rechaza → Estatus: `aprobado`/`inactivo`
3. **Profesor** puede ver, editar y eliminar sus beneficiarios aprobados

## Estructura de Datos

### Enum: Parentesco
```typescript
enum BeneficiarioParentesco {
  CONYUGE = 'conyuge',
  HIJO = 'hijo', 
  MADRE = 'madre',
  PADRE = 'padre'
}
```

### Enum: Estatus
```typescript
enum BeneficiarioEstatus {
  PENDIENTE = 'pendiente',
  APROBADO = 'aprobado',
  INACTIVO = 'inactivo'
}
```

### Modelo: Beneficiario
```typescript
interface Beneficiario {
  id: number;
  agremiado_id: number;
  nombre_completo: string;
  parentesco: BeneficiarioParentesco;
  estatus: BeneficiarioEstatus;
  agremiado?: User; // Solo cuando se carga la relación
  created_at: string; // ISO8601
  updated_at: string; // ISO8601
}
```

## Endpoints de la API

### Base URL
Todos los endpoints están bajo `/api/v1/` y requieren autenticación (`Authorization: Bearer <token>`).

### 1. Obtener Mis Beneficiarios (Profesor)
```http
GET /api/v1/me/beneficiarios
```

**Permisos:** Solo Profesores

**Respuesta:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "agremiado_id": 5,
      "nombre_completo": "María Pérez",
      "parentesco": "conyuge",
      "estatus": "aprobado",
      "created_at": "2025-01-02T12:00:00.000000Z",
      "updated_at": "2025-01-02T12:00:00.000000Z"
    }
  ],
  "message": "My beneficiarios retrieved successfully"
}
```

### 2. Listar Todos los Beneficiarios (Admin)
```http
GET /api/v1/beneficiarios?per_page=15&agremiado_id=5&estatus=pendiente
```

**Permisos:** Solo Administradores

**Query Parameters:**
- `per_page` (opcional): Número de elementos por página (default: 15)
- `agremiado_id` (opcional): Filtrar por profesor específico
- `estatus` (opcional): Filtrar por estatus (`pendiente`, `aprobado`, `inactivo`)

**Respuesta:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "agremiado_id": 5,
      "nombre_completo": "María Pérez",
      "parentesco": "conyuge",
      "estatus": "aprobado",
      "agremiado": {
        "id": 5,
        "name": "Juan Pérez"
      },
      "created_at": "2025-01-02T12:00:00.000000Z",
      "updated_at": "2025-01-02T12:00:00.000000Z"
    }
  ],
  "meta": {
    "pagination": {
      "current_page": 1,
      "last_page": 3,
      "per_page": 15,
      "total": 45
    }
  },
  "message": "Beneficiarios retrieved successfully"
}
```

### 3. Crear Beneficiario (Profesor)
```http
POST /api/v1/beneficiarios
```

**Permisos:** Solo Profesores

**Body:**
```json
{
  "nombre_completo": "María Pérez",
  "parentesco": "conyuge"
}
```

**Validaciones:**
- `nombre_completo`: requerido, string, máximo 255 caracteres
- `parentesco`: requerido, debe ser uno de: `conyuge`, `hijo`, `madre`, `padre`

**Respuesta (201):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "agremiado_id": 5,
    "nombre_completo": "María Pérez",
    "parentesco": "conyuge",
    "estatus": "pendiente",
    "created_at": "2025-01-02T12:00:00.000000Z",
    "updated_at": "2025-01-02T12:00:00.000000Z"
  },
  "message": "Beneficiario created successfully"
}
```

### 4. Ver Detalle de Beneficiario
```http
GET /api/v1/beneficiarios/{id}
```

**Permisos:** Administradores (cualquier beneficiario) o Profesores (solo sus beneficiarios)

**Respuesta:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "agremiado_id": 5,
    "nombre_completo": "María Pérez",
    "parentesco": "conyuge",
    "estatus": "aprobado",
    "agremiado": {
      "id": 5,
      "name": "Juan Pérez"
    },
    "created_at": "2025-01-02T12:00:00.000000Z",
    "updated_at": "2025-01-02T12:00:00.000000Z"
  },
  "message": "Beneficiario retrieved successfully"
}
```

### 5. Actualizar Beneficiario
```http
PUT /api/v1/beneficiarios/{id}
```

**Permisos:** Administradores (cualquier beneficiario) o Profesores (solo sus beneficiarios)

**Body:**
```json
{
  "nombre_completo": "María Pérez García",
  "parentesco": "conyuge"
}
```

**Validaciones:**
- `nombre_completo`: opcional, string, máximo 255 caracteres
- `parentesco`: opcional, debe ser uno de: `conyuge`, `hijo`, `madre`, `padre`

**Respuesta:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "agremiado_id": 5,
    "nombre_completo": "María Pérez García",
    "parentesco": "conyuge",
    "estatus": "aprobado",
    "created_at": "2025-01-02T12:00:00.000000Z",
    "updated_at": "2025-01-02T12:05:00.000000Z"
  },
  "message": "Beneficiario updated successfully"
}
```

### 6. Eliminar Beneficiario
```http
DELETE /api/v1/beneficiarios/{id}
```

**Permisos:** Administradores (cualquier beneficiario) o Profesores (solo sus beneficiarios)

**Respuesta (200):**
```json
{
  "success": true,
  "message": "Beneficiario deleted successfully"
}
```

### 7. Aprobar Beneficiario (Admin)
```http
POST /api/v1/beneficiarios/{id}/approve
```

**Permisos:** Solo Administradores

**Validaciones:** El beneficiario debe estar en estatus `pendiente`

**Respuesta:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "agremiado_id": 5,
    "nombre_completo": "María Pérez",
    "parentesco": "conyuge",
    "estatus": "aprobado",
    "created_at": "2025-01-02T12:00:00.000000Z",
    "updated_at": "2025-01-02T12:10:00.000000Z"
  },
  "message": "Beneficiario approved successfully"
}
```

### 8. Rechazar Beneficiario (Admin)
```http
POST /api/v1/beneficiarios/{id}/reject
```

**Permisos:** Solo Administradores

**Respuesta:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "agremiado_id": 5,
    "nombre_completo": "María Pérez",
    "parentesco": "conyuge",
    "estatus": "inactivo",
    "created_at": "2025-01-02T12:00:00.000000Z",
    "updated_at": "2025-01-02T12:10:00.000000Z"
  },
  "message": "Beneficiario rejected successfully"
}
```

## Códigos de Error Comunes

### 401 - No Autenticado
```json
{
  "message": "Unauthenticated."
}
```

### 403 - Sin Permisos
```json
{
  "success": false,
  "message": "Access denied. Only professors can view their beneficiarios."
}
```

### 422 - Error de Validación
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "nombre_completo": ["The beneficiary name is required."],
    "parentesco": ["The relationship must be a valid option."]
  }
}
```

### 422 - Beneficiario No Pendiente (Aprobar)
```json
{
  "success": false,
  "message": "The beneficiario is not pending approval"
}
```

### 500 - Error del Servidor
```json
{
  "success": false,
  "message": "Error creating beneficiario: [detalle del error]"
}
```

## Guía de Implementación Frontend

### 1. Páginas Necesarias

#### Para Profesores:
- **Lista de Mis Beneficiarios** (`/beneficiarios`)
  - Mostrar tabla con beneficiarios del profesor
  - Filtros por estatus
  - Botones: Ver, Editar, Eliminar, Crear Nuevo
  - Indicadores visuales de estatus (badges/colores)

- **Formulario de Crear/Editar** (`/beneficiarios/nuevo`, `/beneficiarios/{id}/editar`)
  - Campo: Nombre completo (texto)
  - Campo: Parentesco (select con opciones)
  - Validación en tiempo real
  - Botones: Guardar, Cancelar

#### Para Administradores:
- **Lista de Todos los Beneficiarios** (`/admin/beneficiarios`)
  - Tabla con todos los beneficiarios
  - Filtros: por profesor, por estatus
  - Paginación
  - Botones: Ver, Editar, Eliminar, Aprobar, Rechazar
  - Columna adicional: Profesor (agremiado)

- **Panel de Aprobación** (`/admin/beneficiarios/pendientes`)
  - Lista solo beneficiarios pendientes
  - Botones de acción rápida: Aprobar, Rechazar
  - Información del profesor asociado

### 2. Componentes Sugeridos

```typescript
// Componentes principales
<BeneficiariosList />           // Lista con filtros y paginación
<BeneficiarioForm />            // Formulario crear/editar
<BeneficiarioCard />            // Tarjeta individual
<BeneficiarioStatusBadge />     // Badge de estatus
<BeneficiarioActions />         // Botones de acción
<BeneficiarioFilters />         // Filtros de búsqueda

// Para Admin
<BeneficiariosAdminList />      // Lista administrativa
<BeneficiarioApprovalPanel />   // Panel de aprobación
<BeneficiarioStats />           // Estadísticas (opcional)
```

### 3. Estados y Hooks Sugeridos

```typescript
// Estados globales (Redux/Zustand)
interface BeneficiariosState {
  beneficiarios: Beneficiario[];
  loading: boolean;
  error: string | null;
  filters: {
    estatus?: BeneficiarioEstatus;
    agremiado_id?: number;
  };
  pagination: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

// Hooks personalizados
useBeneficiarios()              // Obtener lista
useBeneficiario(id)             // Obtener uno específico
useCreateBeneficiario()         // Crear
useUpdateBeneficiario()         // Actualizar
useDeleteBeneficiario()         // Eliminar
useApproveBeneficiario()        // Aprobar
useRejectBeneficiario()         // Rechazar
```

### 4. Validaciones Frontend

```typescript
// Esquema de validación (Zod/Yup)
const beneficiarioSchema = z.object({
  nombre_completo: z.string()
    .min(1, 'El nombre es requerido')
    .max(255, 'El nombre no puede exceder 255 caracteres'),
  parentesco: z.enum(['conyuge', 'hijo', 'madre', 'padre'], {
    errorMap: () => ({ message: 'Selecciona un parentesco válido' })
  })
});
```

### 5. Indicadores Visuales

#### Colores por Estatus:
- **Pendiente**: Amarillo/Naranja (`#f59e0b`)
- **Aprobado**: Verde (`#10b981`)
- **Inactivo**: Rojo (`#ef4444`)

#### Iconos Sugeridos:
- **Cónyuge**: 👫 o 💑
- **Hijo/a**: 👶 o 👧👦
- **Madre**: 👩
- **Padre**: 👨

### 6. Funcionalidades Especiales

#### Notificaciones:
- Mostrar toast cuando se crea un beneficiario (estatus pendiente)
- Notificar al admin cuando hay beneficiarios pendientes
- Confirmar antes de eliminar

#### Filtros y Búsqueda:
- Búsqueda por nombre
- Filtro por parentesco
- Filtro por estatus
- Ordenamiento por fecha de creación

#### Responsive:
- Tabla responsive en móviles
- Cards en lugar de tabla en pantallas pequeñas
- Formularios adaptativos

### 7. Consideraciones de UX

#### Para Profesores:
- Mostrar claramente el estatus de cada beneficiario
- Explicar que los beneficiarios requieren aprobación
- Permitir editar solo beneficiarios propios
- Confirmación antes de eliminar

#### Para Administradores:
- Vista rápida de beneficiarios pendientes
- Información del profesor asociado
- Acciones masivas (opcional)
- Estadísticas de aprobación

### 8. Integración con Otros Módulos

- **Módulo de Usuarios**: Mostrar información del profesor en listas admin
- **Módulo de Reservaciones**: Considerar beneficiarios aprobados para reservas familiares
- **Módulo de Notificaciones**: Notificar cambios de estatus

## Testing Frontend

### Casos de Prueba Sugeridos:

1. **Profesor crea beneficiario** → Verificar estatus pendiente
2. **Admin aprueba beneficiario** → Verificar cambio de estatus
3. **Profesor edita beneficiario** → Verificar actualización
4. **Validaciones de formulario** → Campos requeridos
5. **Permisos de acceso** → Profesor no puede ver otros beneficiarios
6. **Filtros y búsqueda** → Funcionamiento correcto
7. **Paginación** → Navegación entre páginas
8. **Responsive** → Funcionamiento en móviles

## Notas Importantes

1. **Los beneficiarios NO son usuarios** - no tienen login ni acceso al sistema
2. **Solo profesores pueden crear** beneficiarios
3. **Solo administradores pueden aprobar/rechazar**
4. **Los profesores solo ven sus propios beneficiarios**
5. **Todos los cambios se auditan** en el backend
6. **El estatus inicial siempre es 'pendiente'**
7. **Los beneficiarios aprobados pueden ser editados/eliminados por su profesor**

## Archivos de Referencia Backend

- **Modelo**: `app/Models/Beneficiario.php`
- **Service**: `app/Services/BeneficiarioService.php`
- **Controller**: `app/Http/Controllers/Api/V1/BeneficiarioController.php`
- **Policy**: `app/Policies/BeneficiarioPolicy.php`
- **Requests**: `app/Http/Requests/StoreBeneficiarioRequest.php`, `app/Http/Requests/UpdateBeneficiarioRequest.php`
- **Resource**: `app/Http/Resources/BeneficiarioResource.php`
- **Enums**: `app/Enums/BeneficiarioParentesco.php`, `app/Enums/BeneficiarioEstatus.php`
- **Rutas**: `routes/api.php` (líneas 83-85, 143-145)
