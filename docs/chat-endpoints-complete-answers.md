# Respuestas Completas - Endpoints de Chat Backend

## Resumen Ejecutivo

El sistema de chat está **completamente implementado** y funcional. Todos los endpoints solicitados existen y están correctamente configurados. El problema en el frontend es que está usando endpoints incorrectos.

## 1. Búsqueda de Usuarios para Iniciar Conversaciones

### ✅ **SÍ EXISTE** - Endpoint Específico de Búsqueda

**URL:** `GET /api/v1/chat/users/search`

**Parámetros:**
- `q` (requerido): Query de búsqueda (mínimo 2 caracteres, máximo 100)
- No tiene paginación (devuelve máximo 10 resultados)

**Ejemplo de uso:**
```bash
GET /api/v1/chat/users/search?q=pedro
```

**Respuesta:**
```json
{
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

**Características:**
- ✅ Busca por nombre Y email (case-insensitive)
- ✅ Filtra automáticamente al usuario actual
- ✅ Incluye flags de bloqueo (`has_blocked_me`, `i_blocked_them`)
- ✅ Límite de 10 resultados por búsqueda
- ✅ Mínimo 2 caracteres para buscar

**Validaciones:**
- `q` es requerido
- `q` debe ser string
- `q` mínimo 2 caracteres
- `q` máximo 100 caracteres

**Errores:**
- `422` - Query muy corto o muy largo
- `401` - No autenticado

## 2. Endpoint de Usuarios General

### ⚠️ **NO USAR** - `/api/v1/users` para Chat

**URL:** `GET /api/v1/users`

**Características:**
- ❌ Devuelve TODOS los usuarios del sistema
- ❌ No tiene filtros de búsqueda específicos
- ❌ No incluye flags de bloqueo
- ❌ No está optimizado para chat

**Recomendación:** **NO usar este endpoint para búsqueda de chat**. Usar siempre `/api/v1/chat/users/search`.

## 3. Endpoints de Chat Existentes - CONFIRMADOS

### 3.1 Búsqueda de Usuarios ✅
- `GET /api/v1/chat/users/search?q={query}` - **EXISTE Y FUNCIONAL**

### 3.2 Conversaciones ✅
- `GET /api/v1/chat/conversations` - **EXISTE Y FUNCIONAL**
- `POST /api/v1/chat/conversations` - **EXISTE Y FUNCIONAL**

### 3.3 Mensajes ✅
- `GET /api/v1/chat/conversations/{id}/messages` - **EXISTE Y FUNCIONAL**
- `POST /api/v1/chat/conversations/{id}/messages` - **EXISTE Y FUNCIONAL**

### 3.4 Estado de Lectura ✅
- `POST /api/v1/chat/conversations/{id}/read` - **EXISTE Y FUNCIONAL**

### 3.5 Resumen de No Leídos ✅
- `GET /api/v1/chat/unread/summary` - **EXISTE Y FUNCIONAL**

### 3.6 Bloqueos ✅
- `GET /api/v1/chat/blocks` - **EXISTE Y FUNCIONAL**
- `POST /api/v1/chat/blocks` - **EXISTE Y FUNCIONAL**
- `DELETE /api/v1/chat/blocks/{id}` - **EXISTE Y FUNCIONAL**

## 4. Estructura de Respuestas

### Formato Estándar
```json
{
  "data": [...], // Datos principales
  "message": "...", // Para operaciones sin datos
  "has_more": true, // Para paginación
  "next_before_id": 123 // Para paginación de mensajes
}
```

### Códigos de Error HTTP
- `200` - Éxito
- `201` - Creado exitosamente
- `401` - No autenticado
- `403` - No autorizado / Bloqueado
- `404` - No encontrado
- `422` - Error de validación
- `429` - Rate limit excedido

### Mensajes de Error Específicos
- `"User not found."` - Usuario no existe
- `"Unauthorized."` - No es participante de la conversación
- `"You cannot start a conversation with yourself."` - Auto-chat
- `"You cannot block yourself."` - Auto-bloqueo
- `"Rate limit exceeded for this conversation. Please wait before sending more messages."` - Spam

## 5. Autenticación

### ✅ **TODOS los endpoints requieren autenticación**

**Header requerido:**
```
Authorization: Bearer {token}
```

**Token:** Laravel Sanctum token

**Comportamiento:**
- ✅ Token válido: Acceso permitido
- ❌ Token inválido/expirado: `401 Unauthorized`
- ❌ Sin token: `401 Unauthorized`

## 6. Filtros y Validaciones

### Búsqueda de Usuarios
- ✅ **Búsqueda parcial:** "ped" encuentra "Pedro"
- ✅ **Case-insensitive:** "PEDRO" = "pedro"
- ✅ **Mínimo 2 caracteres**
- ✅ **Máximo 100 caracteres**
- ✅ **Filtra automáticamente al usuario actual**
- ✅ **Incluye flags de bloqueo automáticamente**

### Validaciones de Mensajes
- ✅ **Body requerido**
- ✅ **Máximo 2000 caracteres**
- ✅ **No puede enviar a usuarios bloqueados**

### Validaciones de Conversaciones
- ✅ **No puede crear conversación consigo mismo**
- ✅ **No puede crear conversación con usuarios bloqueados**

## 7. Rate Limiting

### ✅ **IMPLEMENTADO**

**Límites:**
- **Global:** 60 mensajes por minuto por usuario
- **Por conversación:** 30 mensajes por minuto por conversación

**Comportamiento:**
- ✅ Excede límite global: `429 Too Many Requests`
- ✅ Excede límite por conversación: `403 Forbidden` con mensaje específico
- ✅ Rate limiting se resetea cada minuto

## 8. Ejemplos de Uso Completos

### 8.1 Búsqueda de Usuarios
```bash
curl -X GET "http://localhost:8000/api/v1/chat/users/search?q=pedro" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

**Respuesta:**
```json
{
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

### 8.2 Crear Conversación
```bash
curl -X POST "http://localhost:8000/api/v1/chat/conversations" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"peer_id": 1}'
```

**Respuesta:**
```json
{
  "data": {
    "id": 1,
    "user_one_id": 1,
    "user_two_id": 2,
    "created_at": "2025-10-07T01:40:00.000000Z",
    "updated_at": "2025-10-07T01:40:00.000000Z"
  }
}
```

### 8.3 Listar Conversaciones
```bash
curl -X GET "http://localhost:8000/api/v1/chat/conversations" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

**Respuesta:**
```json
{
  "data": [
    {
      "id": 1,
      "other_participant": {
        "id": 1,
        "name": "Pedro García",
        "email": "pedro@example.com",
        "role": "profesor"
      },
      "last_message": {
        "id": 1,
        "body": "Hola, ¿cómo estás?",
        "sender_id": 1,
        "created_at": "2025-10-07T01:40:00.000000Z"
      },
      "unread_count": 2,
      "created_at": "2025-10-07T01:40:00.000000Z",
      "updated_at": "2025-10-07T01:40:00.000000Z"
    }
  ]
}
```

### 8.4 Enviar Mensaje
```bash
curl -X POST "http://localhost:8000/api/v1/chat/conversations/1/messages" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"body": "Hola, ¿cómo estás?"}'
```

**Respuesta:**
```json
{
  "data": {
    "id": 1,
    "conversation_id": 1,
    "sender_id": 1,
    "receiver_id": 2,
    "body": "Hola, ¿cómo estás?",
    "created_at": "2025-10-07T01:40:00.000000Z",
    "updated_at": "2025-10-07T01:40:00.000000Z"
  }
}
```

### 8.5 Obtener Mensajes
```bash
curl -X GET "http://localhost:8000/api/v1/chat/conversations/1/messages?limit=25" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

**Respuesta:**
```json
{
  "data": [
    {
      "id": 1,
      "conversation_id": 1,
      "sender_id": 1,
      "receiver_id": 2,
      "body": "Hola, ¿cómo estás?",
      "created_at": "2025-10-07T01:40:00.000000Z",
      "updated_at": "2025-10-07T01:40:00.000000Z"
    }
  ],
  "has_more": false,
  "next_before_id": null
}
```

### 8.6 Resumen de No Leídos
```bash
curl -X GET "http://localhost:8000/api/v1/chat/unread/summary" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

**Respuesta:**
```json
{
  "data": {
    "total_unread": 5,
    "conversations": [
      {
        "conversation_id": 1,
        "unread_count": 3
      },
      {
        "conversation_id": 2,
        "unread_count": 2
      }
    ]
  }
}
```

### 8.7 Bloquear Usuario
```bash
curl -X POST "http://localhost:8000/api/v1/chat/blocks" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"blocked_id": 1, "reason": "Spam"}'
```

**Respuesta:**
```json
{
  "data": {
    "id": 1,
    "blocker_id": 2,
    "blocked_id": 1,
    "reason": "Spam",
    "expires_at": null,
    "created_at": "2025-10-07T01:40:00.000000Z",
    "updated_at": "2025-10-07T01:40:00.000000Z"
  }
}
```

## 9. Corrección del Frontend

### ❌ **Código Actual (INCORRECTO):**
```javascript
// INCORRECTO - Usa endpoint general
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

### ✅ **Código Corregido (CORRECTO):**
```javascript
// CORRECTO - Usa endpoint específico de chat
export const searchUsers = async (query: string, token: string) => {
  const response = await fetch(`${API_BASE_URL}/chat/users/search?q=${encodeURIComponent(query)}`, {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json',
    },
  });
  
  if (!response.ok) {
    throw new Error(`HTTP error! status: ${response.status}`);
  }
  
  const result = await response.json();
  return result.data; // Ya viene filtrado y con flags de bloqueo
};
```

## 10. Lista Completa de Endpoints

### Endpoints de Chat (Todos Requieren Autenticación)

| Método | URL | Descripción | Parámetros |
|--------|-----|-------------|------------|
| `GET` | `/api/v1/chat/users/search` | Buscar usuarios | `q` (requerido) |
| `POST` | `/api/v1/chat/conversations` | Crear conversación | `peer_id` o `peer_email` |
| `GET` | `/api/v1/chat/conversations` | Listar conversaciones | `page`, `per_page` |
| `GET` | `/api/v1/chat/conversations/{id}/messages` | Obtener mensajes | `limit`, `before_id` |
| `POST` | `/api/v1/chat/conversations/{id}/messages` | Enviar mensaje | `body` |
| `POST` | `/api/v1/chat/conversations/{id}/read` | Marcar como leído | `up_to_message_id` (opcional) |
| `GET` | `/api/v1/chat/unread/summary` | Resumen no leídos | - |
| `GET` | `/api/v1/chat/blocks` | Listar bloqueos | - |
| `POST` | `/api/v1/chat/blocks` | Crear bloqueo | `blocked_id`, `reason`, `expires_at` |
| `DELETE` | `/api/v1/chat/blocks/{id}` | Eliminar bloqueo | - |

## 11. Recomendaciones para el Frontend

### ✅ **Implementación Correcta:**

1. **Usar siempre `/api/v1/chat/users/search`** para búsqueda de usuarios
2. **Implementar polling cada 10 segundos** usando `/api/v1/chat/unread/summary`
3. **Manejar rate limiting** con mensajes de error apropiados
4. **Validar flags de bloqueo** antes de mostrar opciones de chat
5. **Usar paginación de mensajes** con `before_id` para cargar historial
6. **Manejar errores 401/403** para redirigir a login o mostrar mensajes apropiados

### 🔧 **Configuración de Polling:**
```javascript
// Polling cada 10 segundos para resumen de no leídos
setInterval(async () => {
  try {
    const summary = await getUnreadSummary(token);
    updateUnreadCounts(summary.data);
  } catch (error) {
    console.error('Error polling unread summary:', error);
  }
}, 10000);
```

## 12. Conclusión

**El sistema de chat está 100% funcional y completo.** El problema en el frontend es simplemente que está usando endpoints incorrectos. Con esta documentación, el frontend puede implementarse correctamente usando todos los endpoints disponibles.

**Acción requerida:** Cambiar el frontend para usar `/api/v1/chat/users/search` en lugar de `/api/v1/users` para la búsqueda de usuarios.

