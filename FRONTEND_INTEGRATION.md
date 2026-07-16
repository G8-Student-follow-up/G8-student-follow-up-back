# Frontend Integration Guide — G8 Student Follow-up API

**Base URL:** `http://localhost:8000/api`  
**Auth:** Laravel Sanctum (Bearer Token)  
**CORS:** Frontend allowed at `http://localhost:5173`

---

## Table of Contents

1. [Setup & Authentication](#1-setup--authentication)
2. [API Client Setup](#2-api-client-setup)
3. [Core Flows](#3-core-flows)
4. [Complete Endpoint Reference](#4-complete-endpoint-reference)
5. [TypeScript Types](#5-typescript-types)
6. [Error Handling](#6-error-handling)
7. [File Uploads](#7-file-uploads)
8. [Real-time Considerations](#8-real-time-considerations)

---

## 1. Setup & Authentication

### 1.1 Authentication Flow

This API uses **Laravel Sanctum** with **token-based authentication**:

```
Register / Login  ->  Receive { user, token }  ->  Store token  ->  Send as Bearer header
```

### 1.2 Register

```http
POST /api/register
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "confirm_password": "password123"
}
```

**Response (201):**
```json
{
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "role": "trainer",
    "avatar": null,
    "avatar_url": null,
    "phone": null,
    "telegram": null,
    "created_at": "2026-07-14T12:00:00.000000Z"
  },
  "token": "1|abc123def456..."
}
```

### 1.3 Login

```http
POST /api/login
Content-Type: application/json

{
  "email": "john@example.com",
  "password": "password123"
}
```

**Response (200):**
```json
{
  "message": "Login successful",
  "user": { "...": "..." },
  "token": "1|abc123def456..."
}
```

### 1.4 Using the Token

Store the token (e.g. in `localStorage`) and send it on every authenticated request:

```http
Authorization: Bearer 1|abc123def456...
```

### 1.5 Logout

```http
POST /api/logout
Authorization: Bearer 1|abc123def456...
```

**Response (200):** `{ "message": "Logged Successfully" }`

---

## 2. API Client Setup

### 2.1 Axios Setup

```typescript
// api/client.ts
import axios from 'axios';

const api = axios.create({
  baseURL: 'http://localhost:8000/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// Attach token to every request
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Handle 401 globally (redirect to login)
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('token');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

export default api;
```

### 2.2 React Query Setup

```typescript
// api/queryClient.ts
import { QueryClient } from '@tanstack/react-query';

export const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 30_000,
      retry: 1,
      refetchOnWindowFocus: false,
    },
  },
});
```

### 2.3 Plain Fetch (No Library)

```typescript
async function apiRequest<T>(endpoint: string, options: RequestInit = {}): Promise<T> {
  const token = localStorage.getItem('token');
  const response = await fetch(`http://localhost:8000/api${endpoint}`, {
    ...options,
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      ...options.headers,
    },
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({}));
    throw new Error(error.message || `HTTP ${response.status}`);
  }

  if (response.status === 204) return null as T;
  return response.json();
}
```

---

## 3. Core Flows

### 3.1 Data Hierarchy

```
User
 └─ Workspace (top-level container)
     ├─ Boards (Kanban boards)
     │   ├─ Columns (lists/stages)
     │   │   └─ Cards (items/students)
     │   ├─ Labels (tags)
     │   └─ Members (users)
     └─ Members (workspace users)
```

### 3.2 Typical User Journey

1. **Login** -> Get token & user
2. **GET /workspaces** -> List workspaces (or create one)
3. **GET /boards?workspace_id=X** -> List boards
4. **GET /boards/{id}** -> Load board with columns & cards
5. **POST /cards** -> Create a card in a column
6. **PUT /cards/{id}/move** -> Drag & drop card between columns

### 3.3 Loading a Board (The Key Request)

When a user opens a board, `GET /boards/{id}` returns **everything** needed for the Kanban view:

```typescript
const { data } = await api.get(`/boards/${boardId}`);
// data.board = {
//   id, title, description, color, background,
//   is_favorite, is_archived,
//   workspace: { id, name },
//   columns: [{
//     id, title, position,
//     cards: [{
//       id, title, description, position,
//       priority, status, follow_up_date, due_date,
//       comments: [{ id, message, user, created_at }],
//       attachments: [{ id, file_url, file_name }],
//       checklists: [{ id, title, items: [{ id, title, is_completed }] }],
//       labels: [{ id, name, color }],
//       student: { id, name, photo } | null,
//     }]
//   }],
//   members: [{ user: { id, name, email, avatar } }],
//   labels: [{ id, name, color }],
// }
```

### 3.4 Card Drag & Drop

```typescript
await api.put(`/cards/${cardId}/move`, {
  column_id: newColumnId,
  position: newPosition,  // 0-based index within the column
});
```

### 3.5 Reordering Columns

```typescript
await api.post(`/boards/${boardId}/columns/reorder`, {
  columns: [
    { id: 1, position: 0 },
    { id: 2, position: 1 },
    { id: 3, position: 2 },
  ],
});
```

### 3.6 Workspace Invitation Flow

The invitation flow works like this:

1. **Workspace owner** invites a user by email:
   ```typescript
   await api.post(`/workspaces/${workspaceId}/members`, { email: "user@example.com" });
   // Response: { invitation: {...}, message: "Invitation sent successfully" }
   ```

2. **Invited user** sees pending invitations on app load:
   ```typescript
   // Call this when the user logs in or on app mount
   const { data } = await api.get('/invitations');
   // Show a notification badge if data.invitations.length > 0
   ```

3. **Invited user** accepts or declines:
   ```typescript
   // Accept
   await api.post(`/invitations/workspace/${invitationId}/accept`);
   
   // Decline
   await api.post(`/invitations/workspace/${invitationId}/decline`);
   ```

4. After accepting, the user becomes a workspace member and can access the workspace and its boards.

> **Frontend implementation tip:** On every app load / login, call `GET /api/invitations` and show a notification badge (e.g., "You have X pending invitations") or a modal/dropdown listing the invitations with Accept/Decline buttons.

---

## 4. Complete Endpoint Reference

### 4.1 Auth

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | /register | No | Register new user |
| POST | /login | No | Login |
| POST | /forgot-password | No | Send password reset email |
| POST | /reset-password | No | Reset password with token |
| POST | /logout | Yes | Logout (deletes current token) |
| GET | /user | Yes | Get current user profile |
| PUT | /user | Yes | Update current user profile |

**PUT /user** body:
```json
{
  "name": "New Name",
  "phone": "+85512345678",
  "telegram": "@username",
  "current_password": "oldpass",
  "password": "newpass",
  "password_confirmation": "newpass"
}
```

### 4.2 Workspaces

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /workspaces?search= | List user's workspaces |
| GET | /workspaces/{id} | Get workspace details (with boards, members, invitations) |
| POST | /workspaces | Create workspace |
| PUT | /workspaces/{id} | Update workspace |
| DELETE | /workspaces/{id} | Delete workspace |
| GET | /workspaces/{id}/members | List members |
| POST | /workspaces/{id}/members | Add member by user_id or email |
| DELETE | /workspaces/{id}/members/{userId} | Remove member |
| GET | /workspaces/{id}/invitations | List pending invitations for a workspace |
| GET | /invitations | Get my pending invitations (for the invited user) |
| POST | /invitations/workspace/{id}/accept | Accept a workspace invitation |
| POST | /invitations/workspace/{id}/decline | Decline a workspace invitation |

```json
// POST /workspaces
{ "name": "My Workspace", "description": "...", "color": "#3b82f6" }

// POST /workspaces/{id}/members
{ "user_id": 5 }           // existing user
// OR
{ "email": "new@email.com" }  // auto-create + invite
```

### 4.2.1 Workspace Invitations

When you invite someone by email via `POST /workspaces/{id}/members` (see above), the backend:

- Finds or auto-creates a user with that email
- Creates a **pending invitation** record (instead of directly adding them as a member)
- Sends an email to the invited user with an accept link

> **⚠️ Important for new users:** If the invited email doesn't have an account yet, the system auto-creates one with a **random password**. The user won't be able to log in until they use the **Forgot Password** flow (`POST /api/forgot-password`). The invitation email does not include a temporary password, so remind users to check their email for the invitation and use **Forgot Password** on the login page if they can't sign in.

#### View Pending Invitations (for Workspace Owners / Members)

```http
GET /api/workspaces/{id}/invitations
Authorization: Bearer <token>
```

**Response (200):**
```json
{
  "invitations": [
    {
      "id": 1,
      "workspace_id": 1,
      "user_id": 5,
      "invited_by": 1,
      "status": "pending",
      "created_at": "2026-07-14T12:00:00.000000Z",
      "updated_at": "2026-07-14T12:00:00.000000Z",
      "user": {
        "id": 5,
        "name": "ya.phorn",
        "email": "ya.phorn@student.passerellesnumeriques.org",
        "role": "trainer"
      },
      "invited_by": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "role": "admin"
      }
    }
  ]
}
```

#### View My Invitations (for Invited Users)

This is the endpoint the **invited user** calls to see all their pending workspace invitations. **Use this on app load to show a notification/badge.**

```http
GET /api/invitations
Authorization: Bearer <token>
```

**Response (200):**
```json
{
  "invitations": [
    {
      "id": 1,
      "workspace_id": 1,
      "user_id": 5,
      "invited_by": 1,
      "status": "pending",
      "created_at": "2026-07-14T12:00:00.000000Z",
      "updated_at": "2026-07-14T12:00:00.000000Z",
      "workspace": {
        "id": 1,
        "name": "My Workspace",
        "description": "A workspace description",
        "color": "#3b82f6"
      },
      "invited_by": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com"
      }
    }
  ]
}
```

#### Accept an Invitation

```http
POST /api/invitations/workspace/{id}/accept
Authorization: Bearer <token>
Content-Type: application/json

(no body required)
```

**Response (200):**
```json
{
  "message": "Invitation accepted successfully",
  "workspace": {
    "id": 1,
    "name": "My Workspace",
    ...
  }
}
```

**Error responses:**
- `403` — Unauthorized (only the invited user can accept)
- `400` — Invitation is no longer pending (already accepted or declined)

#### Decline an Invitation

```http
POST /api/invitations/workspace/{id}/decline
Authorization: Bearer <token>
Content-Type: application/json

(no body required)
```

**Response (200):**
```json
{
  "message": "Invitation declined"
}
```

---

### 4.3 Boards

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /boards?workspace_id= | List accessible boards |
| GET | /boards/{id} | Get board (with columns, cards, members, labels) |
| POST | /boards | Create board |
| PUT | /boards/{id} | Update board |
| DELETE | /boards/{id} | Delete board |
| POST | /boards/{id}/favorite | Toggle favorite |
| POST | /boards/{id}/archive | Toggle archive |
| GET | /boards/{id}/members | List members |
| POST | /boards/{id}/members | Add member (user_id + role?) |
| DELETE | /boards/{id}/members/{userId} | Remove member |

```json
// POST /boards
{
  "workspace_id": 1,
  "title": "Student Follow-up 2026",
  "description": "Track progress",
  "color": "#8b5cf6"
}
```

### 4.4 Columns

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | /columns | Create column |
| PUT | /columns/{id} | Update column |
| DELETE | /columns/{id} | Delete column |
| POST | /boards/{id}/columns/reorder | Reorder columns |

```json
// POST /columns
