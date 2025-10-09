# Preguntas sobre Endpoints de Chat - Backend

## Contexto
Estamos implementando el frontend del chat y necesitamos aclarar varios endpoints y su funcionamiento. La implementación actual tiene problemas porque no sabemos exactamente qué endpoints están disponibles y cómo funcionan.

## Preguntas Específicas

### 1. Búsqueda de Usuarios para Iniciar Conversaciones

**Problema actual:** 
- En el frontend estamos usando el endpoint `/users` para buscar usuarios
- Esto no es eficiente porque trae TODOS los usuarios del sistema
- No sabemos si existe un endpoint específico para búsqueda

**Preguntas:**
1. ¿Existe un endpoint específico para buscar usuarios por nombre o email?
2. Si existe, ¿cuál es la URL exacta? (ej: `/api/v1/chat/users/search`)
3. ¿Qué parámetros acepta? (ej: `?q=nombre` o `?search=nombre`)
4. ¿Qué campos de usuario devuelve? (id, name, email, role, etc.)
5. ¿Tiene paginación?
6. ¿Filtra automáticamente usuarios bloqueados o el usuario actual?

**Ejemplo de lo que necesitamos:**
```javascript
// Buscar usuarios que contengan "pedro" en nombre o email
GET /api/v1/chat/users/search?q=pedro
```

### 2. Endpoint de Usuarios General

**Preguntas sobre `/api/v1/users`:**
1. ¿Este endpoint devuelve TODOS los usuarios del sistema?
2. ¿Tiene filtros de búsqueda? (ej: `?search=nombre`)
3. ¿Tiene paginación?
4. ¿Qué campos devuelve exactamente?
5. ¿Es el endpoint correcto para buscar usuarios para iniciar conversaciones?

### 3. Endpoints de Chat Existentes

**Necesitamos confirmar que existen estos endpoints:**

#### 3.1 Búsqueda de Usuarios
- `GET /api/v1/chat/users/search?q={query}` - ¿Existe?
- `GET /api/v1/users?search={query}` - ¿Existe?

#### 3.2 Conversaciones
- `GET /api/v1/chat/conversations` - ¿Existe?
- `POST /api/v1/chat/conversations` - ¿Existe?

#### 3.3 Mensajes
- `GET /api/v1/chat/conversations/{id}/messages` - ¿Existe?
- `POST /api/v1/chat/conversations/{id}/messages` - ¿Existe?

#### 3.4 Resumen de No Leídos
- `GET /api/v1/chat/unread/summary` - ¿Existe?

#### 3.5 Bloqueos
- `GET /api/v1/chat/blocks` - ¿Existe?
- `POST /api/v1/chat/blocks` - ¿Existe?
- `DELETE /api/v1/chat/blocks/{id}` - ¿Existe?

### 4. Estructura de Respuestas

**Para cada endpoint, necesitamos saber:**
1. ¿Cuál es la estructura exacta de la respuesta?
2. ¿Siempre devuelve `{ success: boolean, data: any }`?
3. ¿Qué códigos de error HTTP devuelve?
4. ¿Qué mensajes de error específicos?

**Ejemplo de respuesta esperada para búsqueda de usuarios:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Pedro García",
      "email": "pedro@example.com",
      "role": "profesor",
      "has_blocked_me": false,
      "i_blocked_them": false
    }
  ]
}
```

### 5. Autenticación

**Preguntas:**
1. ¿Todos los endpoints de chat requieren el token Bearer?
2. ¿El token se pasa en el header `Authorization: Bearer {token}`?
3. ¿Qué pasa si el token expira? ¿Qué código de error devuelve?

### 6. Filtros y Validaciones

**Para búsqueda de usuarios:**
1. ¿Se puede buscar por nombre parcial? (ej: "ped" encuentra "Pedro")
2. ¿Se puede buscar por email parcial?
3. ¿Es case-sensitive?
4. ¿Tiene un mínimo de caracteres? (ej: mínimo 2 caracteres)
5. ¿Filtra automáticamente al usuario actual?
6. ¿Filtra automáticamente usuarios bloqueados?

### 7. Rate Limiting

**Preguntas:**
1. ¿Hay rate limiting en los endpoints de búsqueda?
2. ¿Cuántas búsquedas por minuto se permiten?
3. ¿Qué error devuelve cuando se excede el límite?

## Implementación Actual Problemática

### Frontend Actual (INCORRECTO):
```javascript
// En chatApi.ts - PROBLEMA: Usa /users en lugar de endpoint específico
export const searchUsers = async (query: string, token: string) => {
  const response = await fetch(`${API_BASE_URL}/users`, {
    headers: {
      'Authorization': `Bearer ${token}`,
    },
  });
  
  // Filtra en frontend - INEFICIENTE
  const filteredUsers = data.data.filter((user: any) => 
    user.name.toLowerCase().includes(query.toLowerCase()) ||
    user.email.toLowerCase().includes(query.toLowerCase())
  );
};
```

### Lo que necesitamos (CORRECTO):
```javascript
// Búsqueda eficiente en backend
export const searchUsers = async (query: string, token: string) => {
  const response = await fetch(`${API_BASE_URL}/chat/users/search?q=${query}`, {
    headers: {
      'Authorization': `Bearer ${token}`,
    },
  });
  
  return await response.json();
};
```

## Preguntas de Implementación

### 1. ¿Debemos crear un endpoint específico?
Si no existe `/api/v1/chat/users/search`, ¿deberíamos:
- Crear este endpoint en el backend?
- Usar `/api/v1/users` con parámetros de búsqueda?
- Usar otro endpoint existente?

### 2. ¿Qué campos necesitamos?
Para mostrar en el modal de búsqueda, necesitamos:
- `id` (para crear conversación)
- `name` (para mostrar)
- `email` (para mostrar)
- `role` (para mostrar)
- `has_blocked_me` (para validar)
- `i_blocked_them` (para validar)

### 3. ¿Paginación?
- ¿Cuántos usuarios devolver por página?
- ¿Necesitamos paginación en el frontend?

## Respuesta Esperada

Por favor, proporciona:

1. **Lista completa de endpoints disponibles** con URLs exactas
2. **Estructura de respuestas** para cada endpoint
3. **Parámetros aceptados** para cada endpoint
4. **Códigos de error** y mensajes
5. **Validaciones y filtros** aplicados
6. **Rate limiting** si existe
7. **Ejemplos de uso** con curl o similar

## Ejemplo de Respuesta Esperada

```markdown
## Endpoints Disponibles

### Búsqueda de Usuarios
- **URL:** `GET /api/v1/chat/users/search`
- **Parámetros:** `?q={query}&page={page}&per_page={limit}`
- **Respuesta:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Pedro García",
      "email": "pedro@example.com",
      "role": "profesor",
      "has_blocked_me": false,
      "i_blocked_them": false
    }
  ],
  "meta": {
    "total": 1,
    "page": 1,
    "per_page": 20
  }
}
```
- **Errores:** 401 (no autorizado), 422 (query muy corto)
- **Rate Limit:** 60 requests/minuto
```

Con esta información podremos corregir completamente la implementación del frontend.
