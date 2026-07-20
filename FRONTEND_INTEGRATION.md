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

#### 🔧 Frontend Implementation Guide — Accepting Invitations

This guide covers **both** scenarios for accepting workspace invitations:

1. **User clicks invitation link from email** (may or may not be logged in)
2. **User accepts from within the app** (already logged in)

---

#### Understanding the Email Link

When a workspace owner invites a user, the backend sends an email with a link like:

```
{FRONTEND_URL}/app/invitations/workspace/{invitationId}/accept?token=xxx...
```

| URL Part | Description |
|----------|-------------|
| `{FRONTEND_URL}` | Configured in backend `.env` as `FRONTEND_URL` |
| `{invitationId}` | The invitation's database ID (integer) |
| `?token=xxx...` | **Unique 64-character token** — this is what authenticates the user without needing a login |

**Important:** The token is the key! Without it, an unauthenticated user will get a 401 error.

---

#### Step-by-Step: Frontend Implementation

##### Step 1: Create an Invitation Accept Page

Create a route/page at `/app/invitations/workspace/{id}/accept` that:

1. Reads `id` and `token` from the URL query parameters
2. Displays a loading state while processing
3. Calls the API to accept the invitation

```tsx
// Example: InvitationAcceptPage.tsx
import { useParams, useSearchParams, useNavigate } from 'react-router-dom';
import { useState, useEffect } from 'react';
import api from '../api/client';

export default function InvitationAcceptPage() {
  const { id } = useParams();
  const [searchParams] = useSearchParams();
  const token = searchParams.get('token');
  const navigate = useNavigate();
  const [status, setStatus] = useState<'loading' | 'success' | 'error'>('loading');
  const [errorMessage, setErrorMessage] = useState('');
  const [workspaceName, setWorkspaceName] = useState('');

  useEffect(() => {
    if (!id || !token) {
      setStatus('error');
      setErrorMessage('Invalid invitation link. The link is missing required parameters.');
      return;
    }

    acceptInvitation(Number(id), token);
  }, [id, token]);

  async function acceptInvitation(invitationId: number, invitationToken: string) {
    try {
      // Send the token as a query parameter (also works as request body)
      const { data } = await api.post(
        `/invitations/workspace/${invitationId}/accept?token=${invitationToken}`
      );
      
      setStatus('success');
      setWorkspaceName(data.workspace.name);
      
      // Redirect to the workspace after a short delay
      setTimeout(() => {
        navigate(`/workspaces/${data.workspace.id}`);
      }, 2000);
    } catch (error: any) {
      setStatus('error');
      
      // Map error codes to user-friendly messages
      const errorMessages: Record<number, string> = {
        400: 'This invitation has already been accepted or declined.',
        401: 'This invitation link is invalid or has expired. Please ask the workspace owner to send a new invitation.',
        403: 'You do not have permission to accept this invitation. Make sure you are logged in with the correct account.',
        404: 'Invitation not found. The link may be invalid.',
      };
      
      setErrorMessage(
        errorMessages[error.response?.status] ||
        error.response?.data?.message ||
        'An unexpected error occurred. Please try again.'
      );
    }
  }

  // Render based on status...
}
```

##### Step 2: Handle the "Not Logged In" Case

The key improvement this token system provides: **users no longer need to be logged in to accept an invitation!**

However, after accepting, the user will want to access the workspace. Two scenarios:

**Scenario A — User already has an account:** They accept, get redirected, and if not logged in, they'll need to log in first. After login, redirect them to the workspace.

**Scenario B — User was auto-created (new user):** The system creates their account with a random password. They must use **Forgot Password** to set their password before they can log in. Show them this message:

```tsx
// After successful acceptance for new users
{status === 'success' && (
  <div>
    <h2>Invitation Accepted! 🎉</h2>
    <p>You are now a member of <strong>{workspaceName}</strong>.</p>
    <p>
      Since this is your first time, please set your password by clicking below,
      then log in to access the workspace.
    </p>
    <button onClick={() => navigate('/forgot-password')}>
      Set My Password
    </button>
  </div>
)}
```

---

#### Common Frontend Mistakes & How to Fix Them

| ❌ Mistake | ✅ Correct Approach |
|-----------|-------------------|
| Sending `token` in the **request body** as JSON | Send `token` as a **query parameter** `?token=xxx` in the URL — this works for both GET and POST requests |
| Using `GET` instead of `POST` | The endpoint is `POST /api/invitations/workspace/{id}/accept` — must use POST |
| Forgetting to extract `token` from the URL query string | Read `?token=xxx` from `window.location.search` or `useSearchParams()` |
| Sending the Bearer token from `localStorage` (which might be expired or missing) | The new token-based flow doesn't need a Bearer token! Just send `?token=xxx` |
| Showing a generic 401 error to the user | Map error codes to user-friendly messages (see table below) |
| Redirecting to login on 401 (global axios interceptor) | ⚠️ **CRITICAL:** Exempt the invitation accept endpoint from your global 401 → login redirect, because 401 here means "invalid token", not "session expired"! |
| Not handling the case where token is `null` | Always check `if (!token)` before making the API call |

---

#### ⚠️ CRITICAL: Fix Your Global Axios Error Interceptor

Most frontend apps have a global axios interceptor that **automatically redirects to login on any 401 response**:

```typescript
// ❌ WRONG — This will break invitation acceptance!
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('token');  // Clears token
      window.location.href = '/login';   // Redirects to login
    }
    return Promise.reject(error);
  }
);
```

The problem: When an unauthenticated user clicks the invitation link, the invitation accept endpoint returns 401 if the token is invalid. **But your global interceptor will treat ANY 401 as "session expired" and redirect to login, even though the user might just have an invalid invitation token!**

**✅ Fix — Exempt the invitation endpoints:**

```typescript
api.interceptors.response.use(
  (response) => response,
  (error) => {
    // ❗ Don't redirect on 401 for invitation endpoints
    const isInvitationEndpoint = error.config?.url?.includes('/invitations/workspace/');
    
    if (error.response?.status === 401 && !isInvitationEndpoint) {
      localStorage.removeItem('token');
      window.location.href = '/login';
    }
    
    return Promise.reject(error);
  }
);
```

---

#### Error Response Reference

When the invitation accept/decline fails, here's what the API returns:

| Status | `message` | Meaning | Frontend Action |
|--------|-----------|---------|----------------|
| `401` | `"Invalid or missing invitation token"` | Token is wrong, expired, or invitation doesn't have one | Show "Link is invalid. Ask for a new invitation." |
| `403` | `"Unauthorized"` | User is logged in but is not the invited user | Show "This invitation was sent to a different email." |
| `400` | `"Invitation is no longer pending"` | Already accepted or declined | Show "This invitation was already used." |
| `404` | Route model binding failure | Invitation ID doesn't exist | Show "Invitation not found." |
| `409` | `"User is already a member"` | From `addMember`, not accept | User is already in the workspace |

---

#### Testing the Flow Locally

1. **Check the FRONTEND_URL** in your backend `.env`:
   ```
   FRONTEND_URL=http://localhost:5173
   ```

2. **Send an invitation** via `POST /api/workspaces/{id}/members` with `{ "email": "test@example.com" }`

3. **Check the Laravel log** or email preview to see the link:
   ```
   storage/logs/laravel.log
   ```
   Or if using Mailtrap/log, check your email inbox.

4. **Copy the full URL**, which will look like:
   ```
   http://localhost:5173/app/invitations/workspace/5/accept?token=abc123...
   ```

5. **Paste it in your browser** (without being logged in) — it should accept the invitation successfully!

---

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

The accept endpoint works with or without authentication:

**Option A: From email link (unauthenticated — uses token)**

The email link includes a unique token: `{FRONTEND_URL}/app/invitations/workspace/{id}/accept?token=xxx...`

The frontend should extract the `id` and `token` from the URL, then call:

```http
POST /api/invitations/workspace/{id}/accept
token: xxx...

(no body required)
```

> The `token` parameter can be sent as a query parameter or in the request body.

**Option B: From app UI (authenticated — uses Bearer token)**

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
- `401` — Invalid or missing invitation token
- `403` — Unauthorized (only the invited user can accept)
- `400` — Invitation is no longer pending (already accepted or declined)

#### Decline an Invitation

Like accept, decline works with or without authentication:

```http
POST /api/invitations/workspace/{id}/decline
Authorization: Bearer <token>
# OR with token:
# token: xxx...

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
