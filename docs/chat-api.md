# API de Chat para Frontend

Este documento detalla los endpoints del API de chat, su uso y las interfaces de TypeScript correspondientes para facilitar la integración con el frontend.

## Autenticación

Todos los endpoints de chat requieren autenticación a través de un token de Sanctum. El token debe ser enviado en la cabecera `Authorization` como un token `Bearer`.

**Cabeceras requeridas:**

```
Authorization: Bearer <your-api-token>
Accept: application/json
```

---

## 1. Búsqueda de Usuarios

Permite buscar usuarios en el sistema para iniciar una conversación.

- **Endpoint:** `GET /api/v1/chat/users/search`
- **Método:** `GET`

### Parámetros de Query

| Parámetro | Tipo   | Descripción                               | Requerido |
| --------- | ------ | ----------------------------------------- | --------- |
| `q`       | string | Término de búsqueda (nombre o email). Mínimo 2 caracteres. | Sí        |

### Ejemplo de Petición

```
GET /api/v1/chat/users/search?q=pedro
```

### Respuesta Exitosa (200 OK)

```json
{
  "data": [
    {
      "id": 2,
      "name": "Pedro Soto",
      "email": "pedro.soto@unet.edu.ve",
      "role": "profesor"
    }
  ]
}
```

### Interfaces de TypeScript

```typescript
export interface UserSearchResult {
  id: number;
  name: string;
  email: string;
  role: string;
}

export interface SearchUsersResponse {
  data: UserSearchResult[];
}
```

---

## 2. Crear o Obtener una Conversación

Crea una nueva conversación con un usuario o recupera una existente si ya existe.

- **Endpoint:** `POST /api/v1/chat/conversations`
- **Método:** `POST`

### Cuerpo de la Petición

Se debe proporcionar `peer_email` o `peer_id`.

| Campo        | Tipo   | Descripción                        | Requerido |
| ------------ | ------ | ---------------------------------- | --------- |
| `peer_email` | string | Email del usuario con quien chatear. | A veces   |
| `peer_id`    | number | ID del usuario con quien chatear.    | A veces   |

### Ejemplo de Petición

```json
{
  "peer_email": "pedro.soto@unet.edu.ve"
}
```

### Respuesta Exitosa (201 Created)

```json
{
  "data": {
    "id": 1,
    "created_at": "2025-10-08T12:00:00.000000Z",
    "updated_at": "2025-10-08T12:00:00.000000Z",
    "participants": [
      {
        "id": 1,
        "name": "Usuario Actual",
        "email": "current.user@example.com"
      },
      {
        "id": 2,
        "name": "Pedro Soto",
        "email": "pedro.soto@unet.edu.ve"
      }
    ]
  }
}
```

### Interfaces de TypeScript

```typescript
export interface CreateConversationRequest {
  peer_email?: string;
  peer_id?: number;
}

export interface ConversationParticipant {
  id: number;
  name: string;
  email: string;
}

export interface Conversation {
  id: number;
  created_at: string;
  updated_at: string;
  participants: ConversationParticipant[];
}

export interface CreateConversationResponse {
  data: Conversation;
}
```

---

## 3. Listar Conversaciones

Obtiene la lista de conversaciones del usuario autenticado, junto con el conteo de mensajes no leídos.

- **Endpoint:** `GET /api/v1/chat/conversations`
- **Método:** `GET`

### Parámetros de Query

| Parámetro  | Tipo   | Descripción                          | Requerido |
| ---------- | ------ | ------------------------------------ | --------- |
| `per_page` | number | Número de conversaciones por página. | No        |
| `page`     | number | Número de la página a obtener.       | No        |

### Respuesta Exitosa (200 OK)

```json
{
  "data": [
    {
      "id": 1,
      "created_at": "2025-10-08T12:00:00.000000Z",
      "updated_at": "2025-10-08T12:05:00.000000Z",
      "participants": [
        { "id": 1, "name": "Usuario Actual", "email": "current.user@example.com" },
        { "id": 2, "name": "Pedro Soto", "email": "pedro.soto@unet.edu.ve" }
      ],
      "last_message": {
        "id": 10,
        "body": "Hola, ¿cómo estás?",
        "created_at": "2025-10-08T12:05:00.000000Z",
        "sender": { "id": 2, "name": "Pedro Soto" }
      },
      "unread_count": 1
    }
  ]
}
```

### Interfaces de TypeScript

```typescript
export interface LastMessage {
  id: number;
  body: string;
  created_at: string;
  sender: {
    id: number;
    name: string;
  };
}

export interface ConversationWithDetails extends Conversation {
  last_message: LastMessage | null;
  unread_count: number;
}

export interface ListConversationsResponse {
  data: ConversationWithDetails[];
}
```

---

## 4. Obtener Mensajes de una Conversación

Recupera los mensajes de una conversación específica, con paginación.

- **Endpoint:** `GET /api/v1/chat/conversations/{conversationId}/messages`
- **Método:** `GET`

### Parámetros de URL

| Parámetro        | Tipo   | Descripción                  |
| ---------------- | ------ | ---------------------------- |
| `conversationId` | number | ID de la conversación.       |

### Parámetros de Query

| Parámetro   | Tipo   | Descripción                                                  | Requerido |
| ----------- | ------ | ------------------------------------------------------------ | --------- |
| `limit`     | number | Número de mensajes a obtener (default: 25).                  | No        |
| `before_id` | number | ID del mensaje antes del cual obtener los mensajes más antiguos (para paginación). | No        |

### Respuesta Exitosa (200 OK)

```json
{
  "data": [
    {
      "id": 10,
      "body": "Hola, ¿cómo estás?",
      "created_at": "2025-10-08T12:05:00.000000Z",
      "sender": { "id": 2, "name": "Pedro Soto" }
    },
    {
      "id": 9,
      "body": "¡Hola!",
      "created_at": "2025-10-08T12:04:00.000000Z",
      "sender": { "id": 1, "name": "Usuario Actual" }
    }
  ],
  "has_more": true,
  "next_before_id": 9
}
```

### Interfaces de TypeScript

```typescript
export interface Message {
  id: number;
  body: string;
  created_at: string;
  sender: {
    id: number;
    name: string;
  };
}

export interface GetMessagesResponse {
  data: Message[];
  has_more: boolean;
  next_before_id: number | null;
}
```

---

## 5. Enviar un Mensaje

Envía un mensaje a una conversación.

- **Endpoint:** `POST /api/v1/chat/conversations/{conversationId}/messages`
- **Método:** `POST`

### Parámetros de URL

| Parámetro        | Tipo   | Descripción                  |
| ---------------- | ------ | ---------------------------- |
| `conversationId` | number | ID de la conversación.       |

### Cuerpo de la Petición

| Campo  | Tipo   | Descripción                               | Requerido |
| ------ | ------ | ----------------------------------------- | --------- |
| `body` | string | Contenido del mensaje (máx 2000 caracteres). | Sí        |

### Ejemplo de Petición

```json
{
  "body": "¡Entendido, gracias!"
}
```

### Respuesta Exitosa (201 Created)

Retorna el objeto del mensaje recién creado.

```json
{
  "data": {
    "id": 11,
    "body": "¡Entendido, gracias!",
    "created_at": "2025-10-08T12:06:00.000000Z",
    "sender": { "id": 1, "name": "Usuario Actual" }
  }
}
```

### Interfaces de TypeScript

```typescript
export interface SendMessageRequest {
  body: string;
}

export interface SendMessageResponse {
  data: Message;
}
```

---

## 6. Marcar Mensajes como Leídos

Marca los mensajes de una conversación como leídos.

- **Endpoint:** `POST /api/v1/chat/conversations/{conversationId}/read`
- **Método:** `POST`

### Parámetros de URL

| Parámetro        | Tipo   | Descripción                  |
| ---------------- | ------ | ---------------------------- |
| `conversationId` | number | ID de la conversación.       |

### Cuerpo de la Petición

| Campo              | Tipo   | Descripción                                                  | Requerido |
| ------------------ | ------ | ------------------------------------------------------------ | --------- |
| `up_to_message_id` | number | ID del último mensaje a marcar como leído. Si se omite, se marcan todos. | No        |

### Ejemplo de Petición

```json
{
  "up_to_message_id": 10
}
```

### Respuesta Exitosa (200 OK)

```json
{
  "message": "Messages marked as read."
}
```

### Interfaces de TypeScript

```typescript
export interface MarkAsReadRequest {
  up_to_message_id?: number;
}
```

---

## 7. Resumen de Mensajes No Leídos

Obtiene un resumen del total de conversaciones no leídas y el total de mensajes no leídos.

- **Endpoint:** `GET /api/v1/chat/unread/summary`
- **Método:** `GET`

### Respuesta Exitosa (200 OK)

```json
{
  "data": {
    "total_unread_conversations": 3,
    "total_unread_messages": 15
  }
}
```

### Interfaces de TypeScript

```typescript
export interface UnreadSummary {
  total_unread_conversations: number;
  total_unread_messages: number;
}

export interface GetUnreadSummaryResponse {
  data: UnreadSummary;
}
```

---

## 8. Bloqueo de Usuarios

### 8.1. Listar Usuarios Bloqueados

- **Endpoint:** `GET /api/v1/chat/blocks`
- **Método:** `GET`

#### Respuesta Exitosa (200 OK)

```json
{
  "data": [
    {
      "id": 1,
      "blocked_user": {
        "id": 5,
        "name": "Usuario Bloqueado",
        "email": "blocked@example.com"
      },
      "reason": "Spam",
      "expires_at": null,
      "created_at": "2025-10-08T10:00:00.000000Z"
    }
  ]
}
```

#### Interfaces de TypeScript

```typescript
export interface BlockedUser {
  id: number;
  name: string;
  email: string;
}

export interface UserBlock {
  id: number;
  blocked_user: BlockedUser;
  reason: string | null;
  expires_at: string | null;
  created_at: string;
}

export interface GetUserBlocksResponse {
  data: UserBlock[];
}
```

### 8.2. Bloquear un Usuario

- **Endpoint:** `POST /api/v1/chat/blocks`
- **Método:** `POST`

#### Cuerpo de la Petición

| Campo             | Tipo   | Descripción                        | Requerido |
| ----------------- | ------ | ---------------------------------- | --------- |
| `blocked_user_id` | number | ID del usuario a bloquear.         | Sí        |
| `reason`          | string | Razón del bloqueo (opcional).      | No        |
| `expires_at`      | string | Fecha de expiración del bloqueo (opcional). | No        |

#### Ejemplo de Petición

```json
{
  "blocked_user_id": 5,
  "reason": "Comportamiento inadecuado"
}
```

#### Respuesta Exitosa (201 Created)

Retorna el objeto del bloqueo recién creado.

```json
{
  "data": {
    "id": 2,
    "blocked_user": {
      "id": 5,
      "name": "Usuario Bloqueado",
      "email": "blocked@example.com"
    },
    "reason": "Comportamiento inadecuado",
    "expires_at": null,
    "created_at": "2025-10-08T13:00:00.000000Z"
  }
}
```

#### Interfaces de TypeScript

```typescript
export interface CreateBlockRequest {
  blocked_user_id: number;
  reason?: string;
  expires_at?: string; // Formato: YYYY-MM-DD HH:MM:SS
}

export interface CreateBlockResponse {
  data: UserBlock;
}
```

### 8.3. Desbloquear un Usuario

- **Endpoint:** `DELETE /api/v1/chat/blocks/{blockedUserId}`
- **Método:** `DELETE`

#### Parámetros de URL

| Parámetro       | Tipo   | Descripción                  |
| --------------- | ------ | ---------------------------- |
| `blockedUserId` | number | ID del usuario a desbloquear. |

#### Respuesta Exitosa (200 OK)

```json
{
  "message": "Block removed successfully."
}
```
