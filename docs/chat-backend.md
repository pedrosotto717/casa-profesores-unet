# Chat System Backend Documentation

## Overview

This document provides comprehensive documentation for the 1:1 chat system implemented in the Laravel backend. The system supports text-only messaging, user blocking, read status tracking, and real-time polling capabilities.

## Database Schema

### Tables

#### conversations
- `id` (BIGINT, PK)
- `user_one_id` (FK → users.id, index)
- `user_two_id` (FK → users.id, index)
- `created_at`, `updated_at`
- **Unique constraint**: (`user_one_id`, `user_two_id`) with `user_one_id < user_two_id`

#### conversation_messages
- `id` (BIGINT, PK)
- `conversation_id` (FK → conversations.id, index)
- `sender_id` (FK → users.id, index)
- `receiver_id` (FK → users.id, index)
- `body` (TEXT, max 2000 chars)
- `created_at`, `updated_at`
- **Indexes**: (`conversation_id`, `id`), (`conversation_id`, `receiver_id`, `id`)

#### conversation_reads
- `id` (BIGINT, PK)
- `conversation_id` (FK → conversations.id, index)
- `user_id` (FK → users.id, index)
- `last_read_message_id` (BIGINT, nullable, index)
- `last_read_at` (DATETIME, nullable)
- `created_at`, `updated_at`
- **Unique constraint**: (`conversation_id`, `user_id`)

#### user_blocks
- `id` (BIGINT, PK)
- `blocker_id` (FK → users.id, index)
- `blocked_id` (FK → users.id, index)
- `reason` (VARCHAR(255), nullable)
- `expires_at` (DATETIME, nullable)
- `created_at`
- **Unique constraint**: (`blocker_id`, `blocked_id`)

## API Endpoints

All endpoints require authentication via Laravel Sanctum (`auth:sanctum` middleware).

Base URL: `/api/v1/chat`

### 1. Search Users

**GET** `/users/search`

Search for users to start conversations with.

**Query Parameters:**
- `q` (required, string, 2-100 chars): Search query for name or email

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "role": "profesor",
      "has_blocked_me": false,
      "i_blocked_them": false
    }
  ]
}
```

**Example:**
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
  "https://api.example.com/api/v1/chat/users/search?q=john"
```

### 2. Create/Get Conversation

**POST** `/conversations`

Create a new conversation or get existing one with another user.

**Request Body:**
```json
{
  "peer_email": "user@example.com"  // OR
  "peer_id": 123
}
```

**Response:**
```json
{
  "data": {
    "id": 1,
    "created_at": "2025-01-28T15:00:00.000000Z",
    "updated_at": "2025-01-28T15:00:00.000000Z"
  }
}
```

**Example:**
```bash
curl -X POST \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"peer_email": "user@example.com"}' \
  "https://api.example.com/api/v1/chat/conversations"
```

### 3. List Conversations

**GET** `/conversations`

Get list of conversations with unread counts and last message info.

**Query Parameters:**
- `per_page` (optional, int, default: 20): Number of conversations per page
- `page` (optional, int, default: 1): Page number

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "other_participant": {
        "id": 2,
        "name": "Jane Doe",
        "email": "jane@example.com",
        "role": "estudiante"
      },
      "last_message": {
        "id": 15,
        "body": "Hello, how are you doing?",
        "sender_id": 2,
        "created_at": "2025-01-28T15:30:00.000000Z"
      },
      "unread_count": 3,
      "created_at": "2025-01-28T15:00:00.000000Z",
      "updated_at": "2025-01-28T15:30:00.000000Z"
    }
  ]
}
```

**Example:**
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
  "https://api.example.com/api/v1/chat/conversations?per_page=10&page=1"
```

### 4. Get Messages

**GET** `/conversations/{conversationId}/messages`

Get messages for a conversation with pagination (infinite scroll upward).

**Query Parameters:**
- `limit` (optional, int, default: 25): Number of messages to fetch
- `before_id` (optional, int): Get messages before this ID (for pagination)

**Response:**
```json
{
  "data": [
    {
      "id": 15,
      "conversation_id": 1,
      "sender_id": 2,
      "receiver_id": 1,
      "body": "Hello, how are you doing?",
      "created_at": "2025-01-28T15:30:00.000000Z",
      "updated_at": "2025-01-28T15:30:00.000000Z"
    }
  ],
  "has_more": true,
  "next_before_id": 14
}
```

**Example:**
```bash
# Get latest messages
curl -H "Authorization: Bearer YOUR_TOKEN" \
  "https://api.example.com/api/v1/chat/conversations/1/messages?limit=25"

# Get older messages (pagination)
curl -H "Authorization: Bearer YOUR_TOKEN" \
  "https://api.example.com/api/v1/chat/conversations/1/messages?limit=25&before_id=14"
```

### 5. Send Message

**POST** `/conversations/{conversationId}/messages`

Send a message in a conversation.

**Request Body:**
```json
{
  "body": "Hello, how are you?"
}
```

**Response:**
```json
{
  "data": {
    "id": 16,
    "conversation_id": 1,
    "sender_id": 1,
    "receiver_id": 2,
    "body": "Hello, how are you?",
    "created_at": "2025-01-28T15:35:00.000000Z",
    "updated_at": "2025-01-28T15:35:00.000000Z"
  }
}
```

**Example:**
```bash
curl -X POST \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"body": "Hello, how are you?"}' \
  "https://api.example.com/api/v1/chat/conversations/1/messages"
```

### 6. Mark as Read

**POST** `/conversations/{conversationId}/read`

Mark messages as read in a conversation.

**Request Body:**
```json
{
  "up_to_message_id": 15  // optional, marks all if not provided
}
```

**Response:**
```json
{
  "message": "Messages marked as read."
}
```

**Example:**
```bash
curl -X POST \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"up_to_message_id": 15}' \
  "https://api.example.com/api/v1/chat/conversations/1/read"
```

### 7. Unread Summary

**GET** `/unread/summary`

Get unread message summary for polling.

**Response:**
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

**Example:**
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
  "https://api.example.com/api/v1/chat/unread/summary"
```

### 8. User Blocking

#### List Blocks
**GET** `/blocks`

Get list of users blocked by the authenticated user.

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "blocker_id": 1,
      "blocked_id": 3,
      "blocked_user": {
        "id": 3,
        "name": "Spam User",
        "email": "spam@example.com",
        "role": "usuario"
      },
      "reason": "Spam messages",
      "expires_at": null,
      "is_active": true,
      "created_at": "2025-01-28T15:00:00.000000Z"
    }
  ]
}
```

#### Create Block
**POST** `/blocks`

Block a user.

**Request Body:**
```json
{
  "blocked_user_id": 3,
  "reason": "Spam messages",  // optional
  "expires_at": "2025-02-28T15:00:00.000000Z"  // optional
}
```

#### Remove Block
**DELETE** `/blocks/{blockedUserId}`

Unblock a user.

**Response:**
```json
{
  "message": "Block removed successfully."
}
```

## Rate Limiting

The system implements rate limiting to prevent spam:

- **Global limit**: 60 messages per minute per user
- **Per-conversation limit**: 30 messages per minute per user per conversation

When rate limits are exceeded, the API returns:
```json
{
  "message": "Rate limit exceeded. Please wait before sending more messages."
}
```

## Error Handling

### Common Error Responses

#### 401 Unauthorized
```json
{
  "message": "Unauthenticated."
}
```

#### 403 Forbidden
```json
{
  "message": "You cannot send messages to this user as they have blocked you."
}
```

#### 404 Not Found
```json
{
  "message": "User not found."
}
```

#### 422 Validation Error
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "body": ["The message body is required."]
  }
}
```

#### 429 Too Many Requests
```json
{
  "message": "Rate limit exceeded. Please wait before sending more messages."
}
```

## Frontend Integration Guide

### Polling Strategy

For real-time updates without WebSockets, implement polling:

```javascript
// Poll every 10 seconds for unread summary
setInterval(async () => {
  try {
    const response = await fetch('/api/v1/chat/unread/summary', {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
      }
    });
    
    const data = await response.json();
    
    if (data.data.total_unread > 0) {
      // Update UI with unread counts
      updateUnreadBadges(data.data.conversations);
    }
  } catch (error) {
    console.error('Polling error:', error);
  }
}, 10000); // 10 seconds
```

### Message Pagination

Implement infinite scroll for loading older messages:

```javascript
async function loadOlderMessages(conversationId, beforeId = null) {
  const url = new URL(`/api/v1/chat/conversations/${conversationId}/messages`);
  url.searchParams.set('limit', '25');
  if (beforeId) {
    url.searchParams.set('before_id', beforeId);
  }
  
  const response = await fetch(url, {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json'
    }
  });
  
  const data = await response.json();
  
  // Add messages to UI (prepend for older messages)
  data.data.forEach(message => {
    prependMessageToUI(message);
  });
  
  // Check if there are more messages to load
  if (data.has_more) {
    setupLoadMoreButton(data.next_before_id);
  }
}
```

### Conversation List Updates

When new messages arrive, update the conversation list:

```javascript
async function refreshConversationList() {
  const response = await fetch('/api/v1/chat/conversations', {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json'
    }
  });
  
  const data = await response.json();
  
  // Update conversation list UI
  updateConversationList(data.data);
}
```

## Configuration

### Environment Variables

Add these to your `.env` file for rate limiting configuration:

```env
# Chat rate limiting (optional, defaults shown)
CHAT_MAX_MSG_PER_MIN=60
CHAT_MAX_MSG_PER_CONV_PER_MIN=30
CHAT_MAX_BODY=2000
```

### Database Migration

Run the migrations to create the chat tables:

```bash
php artisan migrate
```

## Security Considerations

1. **Authentication**: All endpoints require valid Sanctum tokens
2. **Authorization**: Users can only access their own conversations
3. **Rate Limiting**: Prevents spam and abuse
4. **Input Validation**: All inputs are validated and sanitized
5. **Blocking System**: Users can block others to prevent harassment
6. **Message Length**: Limited to 2000 characters to prevent abuse

## Testing

Run the chat system tests:

```bash
php artisan test tests/Feature/ChatTest.php
php artisan test tests/Feature/ChatApiTest.php
```

## Troubleshooting

### Common Issues

1. **Rate Limit Errors**: Wait 1 minute before sending more messages
2. **Blocked User Errors**: Check if the recipient has blocked you
3. **Authentication Errors**: Ensure valid Sanctum token is provided
4. **Validation Errors**: Check request body format and required fields

### Debug Mode

Enable debug logging in `config/logging.php` to see detailed error information.

## Performance Considerations

1. **Database Indexes**: Optimized for common query patterns
2. **Pagination**: Use appropriate limits to avoid large responses
3. **Polling Frequency**: 10-second intervals recommended for unread summary
4. **Message History**: Load messages in batches of 25 for optimal performance

## Future Enhancements

Potential improvements for future versions:

1. **WebSocket Support**: Real-time messaging without polling
2. **Message Status**: Delivery and read receipts
3. **File Attachments**: Support for images and documents
4. **Message Search**: Full-text search within conversations
5. **Group Chats**: Multi-user conversations
6. **Message Reactions**: Emoji reactions to messages
