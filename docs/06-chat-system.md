# 06 - Sistema de Chat 1-a-1

> **Propósito**: Este documento unifica toda la información sobre el sistema de chat, consolidando los múltiples archivos existentes (`chat-api.md`, `chat-backend.md`, etc.) en una guía definitiva.

---

## 1. Visión General

El sistema provee una funcionalidad de chat 1-a-1 basada en API para la comunicación entre usuarios registrados. No utiliza WebSockets, sino un mecanismo de **sondeo (polling)** para actualizaciones en tiempo real.

**Características Principales**:
- Mensajería de texto entre dos usuarios.
- Búsqueda de usuarios para iniciar nuevas conversaciones.
- Lista de conversaciones con último mensaje y contador de no leídos.
- Paginación de mensajes (scroll infinito hacia arriba).
- Sistema de bloqueo de usuarios.
- Confirmaciones de lectura.
- Rate limiting para prevenir spam.

---

## 2. Estructura de la Base de Datos

El chat se apoya en cuatro tablas principales:

- **`conversations`**: Almacena la relación entre dos usuarios (`user_one_id`, `user_two_id`).
- **`conversation_messages`**: Guarda cada mensaje con su emisor y receptor.
- **`conversation_reads`**: Registra el último mensaje leído por un usuario en una conversación para gestionar el estado de "no leído".
- **`user_blocks`**: Almacena los registros de usuarios que han bloqueado a otros.

---

## 3. Endpoints de la API

Todos los endpoints requieren autenticación (`auth:sanctum`) y se encuentran bajo el prefijo `/api/v1/chat`.

### Búsqueda y Conversaciones

**`GET /users/search`**
- **Descripción**: Busca usuarios por nombre o email para iniciar una conversación.
- **Query Params**: `q` (string, requerido, min: 2 caracteres).
- **Respuesta**: Un array de objetos de usuario que coinciden con la búsqueda.

**`POST /conversations`**
- **Descripción**: Crea una nueva conversación con un usuario o recupera una existente.
- **Payload**: `{ "peer_id": 123 }` o `{ "peer_email": "user@example.com" }`.
- **Respuesta**: El objeto de la conversación creada o encontrada.

**`GET /conversations`**
- **Descripción**: Lista las conversaciones del usuario autenticado, ordenadas por el mensaje más reciente. Incluye el último mensaje y el conteo de no leídos.
- **Respuesta**: Un array paginado de objetos de conversación.

### Mensajería

**`GET /conversations/{conversationId}/messages`**
- **Descripción**: Obtiene los mensajes de una conversación específica.
- **Query Params**:
  - `limit` (int, opcional, default: 25): Número de mensajes a obtener.
  - `before_id` (int, opcional): Para paginación; obtiene mensajes más antiguos que este ID.
- **Respuesta**: Un array de mensajes, un flag `has_more` y `next_before_id` para el siguiente lote.

**`POST /conversations/{conversationId}/messages`**
- **Descripción**: Envía un mensaje a una conversación.
- **Payload**: `{ "body": "Contenido del mensaje" }` (max: 2000 caracteres).
- **Respuesta**: El objeto del mensaje recién creado.

### Estado de Lectura y Resumen

**`POST /conversations/{conversationId}/read`**
- **Descripción**: Marca los mensajes de una conversación como leídos.
- **Payload**: `{ "up_to_message_id": 456 }` (opcional). Si no se envía, marca todos como leídos.
- **Respuesta**: Un mensaje de confirmación.

**`GET /unread/summary`**
- **Descripción**: Endpoint clave para el sondeo (polling). Devuelve un resumen de mensajes y conversaciones no leídas.
- **Respuesta**: `{ "total_unread_conversations": 3, "total_unread_messages": 15 }`.

### Bloqueo de Usuarios

**`GET /blocks`**
- **Descripción**: Lista los usuarios que el usuario autenticado ha bloqueado.

**`POST /blocks`**
- **Descripción**: Bloquea a un usuario.
- **Payload**: `{ "blocked_user_id": 789, "reason": "Spam" }` (`reason` es opcional).

**`DELETE /blocks/{blockedUserId}`**
- **Descripción**: Desbloquea a un usuario.

---

## 4. Flujos de Usuario Clave

### Iniciar una Conversación

1.  El frontend utiliza `GET /users/search?q=...` para encontrar a un usuario.
2.  El usuario selecciona un resultado de la búsqueda.
3.  El frontend llama a `POST /conversations` con el `peer_id` del usuario seleccionado.
4.  El backend crea la conversación si no existe y la devuelve.
5.  El frontend redirige a la vista de la conversación, usando el ID devuelto.

### Enviar y Recibir Mensajes (Polling)

1.  **Envío**: El usuario A escribe un mensaje y el frontend llama a `POST /conversations/{id}/messages`. El mensaje se añade inmediatamente a la UI del usuario A con un estado de "enviando".
2.  **Sondeo (Polling)**: El frontend del usuario B (y también del A) llama periódicamente (ej. cada 10 segundos) a `GET /unread/summary`.
3.  **Detección**: Si el resumen indica que hay nuevos mensajes, el frontend del usuario B actualiza el contador de no leídos en la lista de conversaciones.
4.  **Carga de Mensajes**: Cuando el usuario B abre la conversación, el frontend llama a `GET /conversations/{id}/messages` para obtener los mensajes más recientes y los muestra.
5.  **Marcar como Leído**: Después de mostrar los mensajes, el frontend llama a `POST /conversations/{id}/read` para notificar al backend que los mensajes han sido vistos. Esto actualizará el contador de no leídos para la próxima llamada de sondeo.

### Bloquear a un Usuario

1.  El usuario A decide bloquear al usuario B.
2.  El frontend llama a `POST /blocks` con el `blocked_user_id` del usuario B.
3.  A partir de ese momento:
    - El usuario A no verá al usuario B en las búsquedas.
    - El usuario B no podrá enviar mensajes al usuario A (la API devolverá un error 403 Forbidden).
    - El usuario A puede ver su lista de bloqueados con `GET /blocks` y desbloquearlos con `DELETE /blocks/{id}`.

---

## 5. Seguridad y Rendimiento

- **Autorización**: La lógica de servicio y las Policies de Laravel aseguran que un usuario solo pueda acceder a sus propias conversaciones y mensajes.
- **Rate Limiting**: El sistema aplica límites de velocidad para el envío de mensajes (ej. 60 por minuto por usuario y 30 por minuto por conversación) para mitigar el spam. Si se excede, la API responde con un error `429 Too Many Requests`.
- **Rendimiento**: El uso de un endpoint de `summary` para el sondeo es mucho más eficiente que solicitar la lista completa de conversaciones o mensajes en cada intervalo.
