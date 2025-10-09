# Chat Message Flow - Frontend Integration Guide

## Overview

This document provides a comprehensive guide for implementing the chat message flow in the frontend. It covers all the necessary steps, API calls, and UI patterns for a complete chat experience.

## Table of Contents

1. [Authentication Setup](#authentication-setup)
2. [Initial Chat Setup](#initial-chat-setup)
3. [Message Flow Patterns](#message-flow-patterns)
4. [Real-time Updates (Polling)](#real-time-updates-polling)
5. [UI State Management](#ui-state-management)
6. [Error Handling](#error-handling)
7. [Complete Implementation Examples](#complete-implementation-examples)

## Authentication Setup

### 1. Get Authentication Token

Before using any chat endpoints, ensure you have a valid Sanctum token:

```javascript
// Login and get token
const loginResponse = await fetch('/api/v1/login', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  body: JSON.stringify({
    email: 'user@example.com',
    password: 'password'
  })
});

const { token } = await loginResponse.json();

// Store token for future requests
localStorage.setItem('auth_token', token);
```

### 2. Create API Helper

```javascript
class ChatAPI {
  constructor() {
    this.baseURL = '/api/v1/chat';
    this.token = localStorage.getItem('auth_token');
  }

  async request(endpoint, options = {}) {
    const url = `${this.baseURL}${endpoint}`;
    const config = {
      headers: {
        'Authorization': `Bearer ${this.token}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        ...options.headers
      },
      ...options
    };

    const response = await fetch(url, config);
    
    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.message || 'Request failed');
    }

    return response.json();
  }

  // Chat API methods
  async searchUsers(query) {
    return this.request(`/users/search?q=${encodeURIComponent(query)}`);
  }

  async createConversation(peerEmail = null, peerId = null) {
    const body = peerEmail ? { peer_email: peerEmail } : { peer_id: peerId };
    return this.request('/conversations', {
      method: 'POST',
      body: JSON.stringify(body)
    });
  }

  async getConversations(page = 1, perPage = 20) {
    return this.request(`/conversations?page=${page}&per_page=${perPage}`);
  }

  async getMessages(conversationId, limit = 25, beforeId = null) {
    let url = `/conversations/${conversationId}/messages?limit=${limit}`;
    if (beforeId) {
      url += `&before_id=${beforeId}`;
    }
    return this.request(url);
  }

  async sendMessage(conversationId, body) {
    return this.request(`/conversations/${conversationId}/messages`, {
      method: 'POST',
      body: JSON.stringify({ body })
    });
  }

  async markAsRead(conversationId, upToMessageId = null) {
    const body = upToMessageId ? { up_to_message_id: upToMessageId } : {};
    return this.request(`/conversations/${conversationId}/read`, {
      method: 'POST',
      body: JSON.stringify(body)
    });
  }

  async getUnreadSummary() {
    return this.request('/unread/summary');
  }

  async getBlocks() {
    return this.request('/blocks');
  }

  async createBlock(blockedUserId, reason = null, expiresAt = null) {
    return this.request('/blocks', {
      method: 'POST',
      body: JSON.stringify({
        blocked_user_id: blockedUserId,
        reason,
        expires_at: expiresAt
      })
    });
  }

  async removeBlock(blockedUserId) {
    return this.request(`/blocks/${blockedUserId}`, {
      method: 'DELETE'
    });
  }
}

const chatAPI = new ChatAPI();
```

## Initial Chat Setup

### 1. Load Conversations List

```javascript
async function loadConversations() {
  try {
    const response = await chatAPI.getConversations();
    const conversations = response.data;
    
    // Update UI with conversations
    updateConversationsList(conversations);
    
    // If no conversations, show empty state
    if (conversations.length === 0) {
      showEmptyConversationsState();
    }
  } catch (error) {
    console.error('Failed to load conversations:', error);
    showError('Failed to load conversations');
  }
}

function updateConversationsList(conversations) {
  const container = document.getElementById('conversations-list');
  container.innerHTML = '';

  conversations.forEach(conversation => {
    const conversationElement = createConversationElement(conversation);
    container.appendChild(conversationElement);
  });
}

function createConversationElement(conversation) {
  const div = document.createElement('div');
  div.className = 'conversation-item';
  div.dataset.conversationId = conversation.id;
  
  const unreadBadge = conversation.unread_count > 0 
    ? `<span class="unread-badge">${conversation.unread_count}</span>` 
    : '';
  
  const lastMessage = conversation.last_message 
    ? `<div class="last-message">${conversation.last_message.body}</div>`
    : '<div class="last-message">No messages yet</div>';
  
  div.innerHTML = `
    <div class="conversation-header">
      <div class="participant-info">
        <h3>${conversation.other_participant.name}</h3>
        <span class="participant-role">${conversation.other_participant.role}</span>
      </div>
      ${unreadBadge}
    </div>
    ${lastMessage}
    <div class="conversation-time">
      ${formatTime(conversation.last_message?.created_at || conversation.updated_at)}
    </div>
  `;
  
  // Add click handler
  div.addEventListener('click', () => openConversation(conversation.id));
  
  return div;
}
```

### 2. Search and Start New Conversation

```javascript
async function searchAndStartConversation() {
  const searchInput = document.getElementById('user-search');
  const query = searchInput.value.trim();
  
  if (query.length < 2) {
    showError('Please enter at least 2 characters to search');
    return;
  }
  
  try {
    // Show loading state
    showSearchLoading(true);
    
    const response = await chatAPI.searchUsers(query);
    const users = response.data;
    
    // Display search results
    displaySearchResults(users);
  } catch (error) {
    console.error('Search failed:', error);
    showError('Failed to search users');
  } finally {
    showSearchLoading(false);
  }
}

function displaySearchResults(users) {
  const resultsContainer = document.getElementById('search-results');
  resultsContainer.innerHTML = '';
  
  if (users.length === 0) {
    resultsContainer.innerHTML = '<div class="no-results">No users found</div>';
    return;
  }
  
  users.forEach(user => {
    const userElement = createUserSearchElement(user);
    resultsContainer.appendChild(userElement);
  });
}

function createUserSearchElement(user) {
  const div = document.createElement('div');
  div.className = 'user-search-item';
  
  const blockStatus = user.i_blocked_them 
    ? '<span class="block-status">You blocked this user</span>'
    : user.has_blocked_me 
    ? '<span class="block-status">This user blocked you</span>'
    : '';
  
  div.innerHTML = `
    <div class="user-info">
      <h4>${user.name}</h4>
      <p>${user.email}</p>
      <span class="user-role">${user.role}</span>
    </div>
    ${blockStatus}
    <button class="start-chat-btn" ${user.has_blocked_me || user.i_blocked_them ? 'disabled' : ''}>
      Start Chat
    </button>
  `;
  
  const startChatBtn = div.querySelector('.start-chat-btn');
  if (!startChatBtn.disabled) {
    startChatBtn.addEventListener('click', () => startConversation(user));
  }
  
  return div;
}

async function startConversation(user) {
  try {
    const response = await chatAPI.createConversation(null, user.id);
    const conversation = response.data;
    
    // Open the new conversation
    openConversation(conversation.id);
    
    // Close search modal
    closeSearchModal();
    
    // Refresh conversations list
    loadConversations();
  } catch (error) {
    console.error('Failed to start conversation:', error);
    showError('Failed to start conversation');
  }
}
```

## Message Flow Patterns

### 1. Open Conversation and Load Messages

```javascript
let currentConversationId = null;
let messages = [];
let hasMoreMessages = false;
let nextBeforeId = null;

async function openConversation(conversationId) {
  currentConversationId = conversationId;
  
  // Show conversation view
  showConversationView();
  
  // Load messages
  await loadMessages(conversationId);
  
  // Mark as read
  await markConversationAsRead(conversationId);
  
  // Start polling for new messages
  startMessagePolling(conversationId);
}

async function loadMessages(conversationId, beforeId = null) {
  try {
    const response = await chatAPI.getMessages(conversationId, 25, beforeId);
    const newMessages = response.data;
    
    if (beforeId) {
      // Loading older messages - prepend to existing
      messages = [...newMessages, ...messages];
    } else {
      // Loading latest messages - replace existing
      messages = newMessages;
    }
    
    hasMoreMessages = response.has_more;
    nextBeforeId = response.next_before_id;
    
    // Update UI
    updateMessagesDisplay();
    
    // Scroll to appropriate position
    if (beforeId) {
      // Keep current scroll position when loading older messages
      maintainScrollPosition();
    } else {
      // Scroll to bottom when loading latest messages
      scrollToBottom();
    }
  } catch (error) {
    console.error('Failed to load messages:', error);
    showError('Failed to load messages');
  }
}

function updateMessagesDisplay() {
  const messagesContainer = document.getElementById('messages-container');
  messagesContainer.innerHTML = '';
  
  messages.forEach(message => {
    const messageElement = createMessageElement(message);
    messagesContainer.appendChild(messageElement);
  });
}

function createMessageElement(message) {
  const div = document.createElement('div');
  div.className = `message ${message.sender_id === currentUserId ? 'sent' : 'received'}`;
  div.dataset.messageId = message.id;
  
  div.innerHTML = `
    <div class="message-content">
      <div class="message-body">${escapeHtml(message.body)}</div>
      <div class="message-time">${formatTime(message.created_at)}</div>
    </div>
  `;
  
  return div;
}
```

### 2. Send Message

```javascript
async function sendMessage() {
  const messageInput = document.getElementById('message-input');
  const body = messageInput.value.trim();
  
  if (!body) {
    return;
  }
  
  if (body.length > 2000) {
    showError('Message is too long (max 2000 characters)');
    return;
  }
  
  try {
    // Disable input and show sending state
    messageInput.disabled = true;
    showSendingState(true);
    
    const response = await chatAPI.sendMessage(currentConversationId, body);
    const newMessage = response.data;
    
    // Add message to local state
    messages.push(newMessage);
    
    // Update UI
    updateMessagesDisplay();
    scrollToBottom();
    
    // Clear input
    messageInput.value = '';
    
  } catch (error) {
    console.error('Failed to send message:', error);
    
    if (error.message.includes('Rate limit')) {
      showError('You are sending messages too quickly. Please wait a moment.');
    } else if (error.message.includes('blocked')) {
      showError('You cannot send messages to this user as they have blocked you.');
    } else {
      showError('Failed to send message');
    }
  } finally {
    messageInput.disabled = false;
    showSendingState(false);
  }
}

// Handle Enter key press
document.getElementById('message-input').addEventListener('keypress', (e) => {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault();
    sendMessage();
  }
});
```

### 3. Load Older Messages (Infinite Scroll)

```javascript
function setupInfiniteScroll() {
  const messagesContainer = document.getElementById('messages-container');
  
  messagesContainer.addEventListener('scroll', () => {
    if (messagesContainer.scrollTop === 0 && hasMoreMessages) {
      loadOlderMessages();
    }
  });
}

async function loadOlderMessages() {
  if (!hasMoreMessages || !nextBeforeId) {
    return;
  }
  
  try {
    showLoadingOlderMessages(true);
    
    // Store current scroll position
    const messagesContainer = document.getElementById('messages-container');
    const scrollHeight = messagesContainer.scrollHeight;
    
    await loadMessages(currentConversationId, nextBeforeId);
    
    // Restore scroll position
    const newScrollHeight = messagesContainer.scrollHeight;
    messagesContainer.scrollTop = newScrollHeight - scrollHeight;
    
  } catch (error) {
    console.error('Failed to load older messages:', error);
    showError('Failed to load older messages');
  } finally {
    showLoadingOlderMessages(false);
  }
}
```

## Real-time Updates (Polling)

### 1. Polling for New Messages

```javascript
let pollingInterval = null;
let lastPollTime = null;

function startMessagePolling(conversationId) {
  // Clear any existing polling
  stopMessagePolling();
  
  // Poll every 10 seconds
  pollingInterval = setInterval(async () => {
    await pollForNewMessages(conversationId);
  }, 10000);
  
  // Also poll for unread summary
  startUnreadSummaryPolling();
}

function stopMessagePolling() {
  if (pollingInterval) {
    clearInterval(pollingInterval);
    pollingInterval = null;
  }
}

async function pollForNewMessages(conversationId) {
  try {
    const response = await chatAPI.getMessages(conversationId, 25);
    const latestMessages = response.data;
    
    // Check for new messages
    const newMessages = latestMessages.filter(newMsg => 
      !messages.some(existingMsg => existingMsg.id === newMsg.id)
    );
    
    if (newMessages.length > 0) {
      // Add new messages to the end
      messages = [...messages, ...newMessages];
      updateMessagesDisplay();
      
      // Auto-scroll to bottom if user is near bottom
      if (isNearBottom()) {
        scrollToBottom();
      }
      
      // Mark as read if conversation is active
      if (currentConversationId === conversationId) {
        await markConversationAsRead(conversationId);
      }
    }
  } catch (error) {
    console.error('Polling error:', error);
  }
}
```

### 2. Polling for Unread Summary

```javascript
let unreadPollingInterval = null;

function startUnreadSummaryPolling() {
  unreadPollingInterval = setInterval(async () => {
    await pollUnreadSummary();
  }, 10000);
}

async function pollUnreadSummary() {
  try {
    const response = await chatAPI.getUnreadSummary();
    const summary = response.data;
    
    // Update unread counts in conversations list
    updateUnreadCounts(summary.conversations);
    
    // Update global unread badge
    updateGlobalUnreadBadge(summary.total_unread);
    
  } catch (error) {
    console.error('Unread summary polling error:', error);
  }
}

function updateUnreadCounts(conversations) {
  conversations.forEach(conversation => {
    const conversationElement = document.querySelector(
      `[data-conversation-id="${conversation.conversation_id}"]`
    );
    
    if (conversationElement) {
      const badge = conversationElement.querySelector('.unread-badge');
      
      if (conversation.unread_count > 0) {
        if (badge) {
          badge.textContent = conversation.unread_count;
        } else {
          const newBadge = document.createElement('span');
          newBadge.className = 'unread-badge';
          newBadge.textContent = conversation.unread_count;
          conversationElement.querySelector('.conversation-header').appendChild(newBadge);
        }
      } else if (badge) {
        badge.remove();
      }
    }
  });
}
```

## UI State Management

### 1. Conversation State

```javascript
class ChatState {
  constructor() {
    this.currentConversationId = null;
    this.messages = [];
    this.conversations = [];
    this.hasMoreMessages = false;
    this.nextBeforeId = null;
    this.isLoading = false;
    this.isSending = false;
  }
  
  setCurrentConversation(conversationId) {
    this.currentConversationId = conversationId;
    this.messages = [];
    this.hasMoreMessages = false;
    this.nextBeforeId = null;
  }
  
  addMessage(message) {
    this.messages.push(message);
  }
  
  addMessages(newMessages) {
    this.messages = [...this.messages, ...newMessages];
  }
  
  setConversations(conversations) {
    this.conversations = conversations;
  }
  
  updateConversation(conversationId, updates) {
    const index = this.conversations.findIndex(c => c.id === conversationId);
    if (index !== -1) {
      this.conversations[index] = { ...this.conversations[index], ...updates };
    }
  }
}

const chatState = new ChatState();
```

### 2. UI Updates

```javascript
function updateUI() {
  updateConversationsList(chatState.conversations);
  updateMessagesDisplay(chatState.messages);
  updateLoadingStates();
}

function updateLoadingStates() {
  const messageInput = document.getElementById('message-input');
  const sendButton = document.getElementById('send-button');
  
  if (chatState.isSending) {
    messageInput.disabled = true;
    sendButton.disabled = true;
    sendButton.textContent = 'Sending...';
  } else {
    messageInput.disabled = false;
    sendButton.disabled = false;
    sendButton.textContent = 'Send';
  }
}
```

## Error Handling

### 1. Global Error Handler

```javascript
function showError(message, duration = 5000) {
  const errorContainer = document.getElementById('error-container');
  const errorElement = document.createElement('div');
  errorElement.className = 'error-message';
  errorElement.textContent = message;
  
  errorContainer.appendChild(errorElement);
  
  // Auto-remove after duration
  setTimeout(() => {
    errorElement.remove();
  }, duration);
}

function handleAPIError(error) {
  console.error('API Error:', error);
  
  if (error.message.includes('Rate limit')) {
    showError('You are sending messages too quickly. Please wait a moment.');
  } else if (error.message.includes('blocked')) {
    showError('You cannot send messages to this user as they have blocked you.');
  } else if (error.message.includes('Unauthorized')) {
    showError('Your session has expired. Please log in again.');
    // Redirect to login
    window.location.href = '/login';
  } else {
    showError('An error occurred. Please try again.');
  }
}
```

### 2. Network Error Handling

```javascript
async function makeAPICall(apiCall) {
  try {
    return await apiCall();
  } catch (error) {
    if (error.name === 'TypeError' && error.message.includes('fetch')) {
      showError('Network error. Please check your connection.');
    } else {
      handleAPIError(error);
    }
    throw error;
  }
}
```

## Complete Implementation Examples

### 1. Complete Chat Component (React-like structure)

```javascript
class ChatComponent {
  constructor() {
    this.chatAPI = new ChatAPI();
    this.state = new ChatState();
    this.initializeEventListeners();
  }
  
  async initialize() {
    await this.loadConversations();
    this.startPolling();
  }
  
  initializeEventListeners() {
    // Message input
    document.getElementById('message-input').addEventListener('keypress', (e) => {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        this.sendMessage();
      }
    });
    
    // Send button
    document.getElementById('send-button').addEventListener('click', () => {
      this.sendMessage();
    });
    
    // Search functionality
    document.getElementById('user-search').addEventListener('input', 
      debounce(() => this.searchUsers(), 300)
    );
    
    // Infinite scroll
    document.getElementById('messages-container').addEventListener('scroll', () => {
      if (this.isAtTop() && this.state.hasMoreMessages) {
        this.loadOlderMessages();
      }
    });
  }
  
  async loadConversations() {
    try {
      const response = await this.chatAPI.getConversations();
      this.state.setConversations(response.data);
      this.updateConversationsList();
    } catch (error) {
      handleAPIError(error);
    }
  }
  
  async openConversation(conversationId) {
    this.state.setCurrentConversation(conversationId);
    await this.loadMessages(conversationId);
    await this.markAsRead(conversationId);
    this.showConversationView();
  }
  
  async sendMessage() {
    const input = document.getElementById('message-input');
    const body = input.value.trim();
    
    if (!body) return;
    
    this.state.isSending = true;
    this.updateUI();
    
    try {
      const response = await this.chatAPI.sendMessage(
        this.state.currentConversationId, 
        body
      );
      
      this.state.addMessage(response.data);
      this.updateMessagesDisplay();
      this.scrollToBottom();
      input.value = '';
      
    } catch (error) {
      handleAPIError(error);
    } finally {
      this.state.isSending = false;
      this.updateUI();
    }
  }
  
  startPolling() {
    setInterval(async () => {
      await this.pollForUpdates();
    }, 10000);
  }
  
  async pollForUpdates() {
    if (this.state.currentConversationId) {
      await this.pollForNewMessages();
    }
    await this.pollUnreadSummary();
  }
}

// Initialize chat when page loads
document.addEventListener('DOMContentLoaded', () => {
  const chat = new ChatComponent();
  chat.initialize();
});
```

### 2. Utility Functions

```javascript
// Debounce function for search
function debounce(func, wait) {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
}

// HTML escaping
function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

// Time formatting
function formatTime(timestamp) {
  const date = new Date(timestamp);
  const now = new Date();
  const diff = now - date;
  
  if (diff < 60000) { // Less than 1 minute
    return 'Just now';
  } else if (diff < 3600000) { // Less than 1 hour
    return `${Math.floor(diff / 60000)}m ago`;
  } else if (diff < 86400000) { // Less than 1 day
    return `${Math.floor(diff / 3600000)}h ago`;
  } else {
    return date.toLocaleDateString();
  }
}

// Scroll utilities
function scrollToBottom() {
  const container = document.getElementById('messages-container');
  container.scrollTop = container.scrollHeight;
}

function isNearBottom() {
  const container = document.getElementById('messages-container');
  const threshold = 100;
  return container.scrollTop + container.clientHeight >= 
         container.scrollHeight - threshold;
}

function isAtTop() {
  const container = document.getElementById('messages-container');
  return container.scrollTop === 0;
}
```

## Best Practices

1. **Error Handling**: Always wrap API calls in try-catch blocks
2. **Loading States**: Show loading indicators for better UX
3. **Debouncing**: Use debouncing for search inputs
4. **Memory Management**: Clear intervals when components unmount
5. **Accessibility**: Add proper ARIA labels and keyboard navigation
6. **Performance**: Use pagination and virtual scrolling for large message lists
7. **Security**: Never trust client-side data, always validate on server
8. **Offline Handling**: Consider implementing offline message queuing

## Testing

```javascript
// Example test for message sending
async function testSendMessage() {
  const chatAPI = new ChatAPI();
  
  try {
    const response = await chatAPI.sendMessage(1, 'Test message');
    console.assert(response.data.body === 'Test message');
    console.log('✅ Message sent successfully');
  } catch (error) {
    console.error('❌ Message sending failed:', error);
  }
}
```

This guide provides a complete foundation for implementing the chat message flow in your frontend application. Adapt the code examples to your specific framework and requirements.
