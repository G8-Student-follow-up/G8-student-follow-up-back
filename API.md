# API Documentation — Student Follow-up System

Base URL: `http://127.0.0.1:8000/api`

---

## Authentication (Sanctum Token)

All protected endpoints require a `Bearer` token in the `Authorization` header:

```
Authorization: Bearer <your-token>
```

Tokens are returned on register/login and never expire by default.

---

## Endpoints

### Trash

Deleted boards and cards are retained in Trash until they are permanently deleted. All Trash endpoints require a Bearer token.

```
GET /api/trash
```

Returns a frontend-friendly combined `items` list plus separate `boards` and `cards` arrays. Each combined item has `id`, `type` (`board` or `card`), `title`, `deleted_at`, and `data`.

```
POST /api/trash/boards/{id}/restore
DELETE /api/trash/boards/{id}
POST /api/trash/cards/{id}/restore
DELETE /api/trash/cards/{id}
```

The `POST` endpoints restore the item. The `DELETE` endpoints permanently remove an item that is already in Trash. A card whose board is still deleted must be restored after its board.

---

### 1. Register

Create a new user account.

```
POST /api/register
```

**Request body:**

| Field            | Type   | Required | Description                     |
| ---------------- | ------ | -------- | ------------------------------- |
| name             | string | yes      | User's full name                |
| email            | string | yes      | Valid email (unique)            |
| password         | string | yes      | Min 8 characters                |
| confirm_password | string | yes      | Must match `password`           |
| role             | string | no       | Defaults to `trainer`           |

**Response `201`:**

```json
{
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "role": "trainer",
    "avatar": null,
    "created_at": "2026-07-07T00:00:00.000000Z",
    "updated_at": "2026-07-07T00:00:00.000000Z"
  },
  "token": "1|abc123..."
}
```

---

### 2. Login

```
POST /api/login
```

**Request body:**

| Field    | Type   | Required | Description |
| -------- | ------ | -------- | ----------- |
| email    | string | yes      | User email  |
| password | string | yes      | User password |

**Response `200`:**

```json
{
  "message": "Login successful",
  "user": { "...same as register..." },
  "token": "1|abc123..."
}
```

**Response `401`:**

```json
{ "message": "Invalid credentials" }
```

---

### 3. Logout

Revoke the current token.

```
POST /api/logout
```

**Headers:** `Authorization: Bearer <token>`

**Response `200`:**

```json
{ "message": "Logged Successfully" }
```

---

### 4. Get Current User

```
GET /api/user
```

**Headers:** `Authorization: Bearer <token>`

**Response `200`:**

```json
{
  "id": 1,
  "name": "John Doe",
  "email": "john@example.com",
  "role": "trainer",
  "avatar": null,
  "created_at": "...",
  "updated_at": "..."
}
```

---

### 5. Students

#### List All Students

```
GET /api/students
```

**Headers:** `Authorization: Bearer <token>`

**Response `200`:**

```json
[
  {
    "id": 1,
    "class_id": 1,
    "trainer_id": 1,
    "name": "John Doe",
    "photo": null,
    "description": null,
    "follow_up_date": "2026-08-01",
    "priority": "Medium",
    "status": "Pending",
    "position": 0,
    "created_at": "...",
    "updated_at": "...",
    "classroom": { "id": 1, "title": "Math 101", "board_id": 1, "position": 0 },
    "trainer": { "id": 1, "name": "Trainer", "email": "...", "role": "trainer" },
    "labels": [
      { "id": 1, "name": "VIP", "color": "#FF0000", "pivot": { "student_id": 1, "label_id": 1 } }
    ]
  }
]
```

Includes the related `classroom`, `trainer`, and `labels`.

---

#### Get Single Student

```
GET /api/students/{id}
```

**Headers:** `Authorization: Bearer <token>`

**Response `200`** — includes all relations:

```json
{
  "id": 1,
  "name": "...",
  "...fields...": "...",
  "classroom": { "...": "..." },
  "trainer": { "...": "..." },
  "comments": [
    { "id": 1, "user_id": 1, "message": "Followed up today", "created_at": "...", "user": { "id": 1, "name": "Trainer" } }
  ],
  "attachments": [
    { "id": 1, "file_path": "/uploads/doc.pdf", "file_type": "application/pdf", "file_size": 102400 }
  ],
  "labels": [ { "id": 1, "name": "VIP", "color": "#FF0000" } ]
}
```

---

#### Create Student

```
POST /api/students
```

**Headers:** `Authorization: Bearer <token>`

**Request body:**

| Field          | Type   | Required | Values                                           |
| -------------- | ------ | -------- | ------------------------------------------------ |
| class_id       | int    | yes      | Must reference an existing `classes` row         |
| trainer_id     | int    | yes      | Must reference an existing `users` row           |
| name           | string | yes      | Max 255 chars                                    |
| photo          | string | no       | File path or URL                                 |
| description    | string | no       |                                                  |
| follow_up_date | date   | no       | Format: `Y-m-d` (e.g. `2026-08-01`)              |
| priority       | string | yes      | `Low`, `Medium`, `High`                          |
| status         | string | yes      | `Pending`, `In Progress`, `Completed`, `Archived` |
| position       | int    | no       | Defaults to `0`                                  |

**Response `201`** — returns the created student (without relations).

**Response `422`** — validation errors.

---

#### Update Student

```
PUT /api/students/{id}
```

**Headers:** `Authorization: Bearer <token>`

Same fields as create — all fields are **optional** on update (use `sometimes` validation).

**Response `200`** — returns the updated student.

---

#### Delete Student

```
DELETE /api/students/{id}
```

**Headers:** `Authorization: Bearer <token>`

**Response `204`** — no content.

---

## Data Model (Database Schema)

### users

| Column   | Type                 | Notes                         |
| -------- | -------------------- | ----------------------------- |
| id       | bigint (PK)          | Auto-increment                |
| name     | string(255)          |                               |
| email    | string(255)          | Unique                        |
| password         | string(255)          | Hashed                        |
| confirm_password | string(255)          | Nullable                      |
| role             | enum                 | `admin` or `trainer`          |
| avatar           | string(255)          | Nullable                      |

### workspaces

| Column     | Type        | FK       |
| ---------- | ----------- | -------- |
| id         | bigint (PK) |          |
| name       | string      |          |
| created_by | bigint      | users.id |

### boards

| Column       | Type        | FK            |
| ------------ | ----------- | ------------- |
| id           | bigint (PK) |               |
| workspace_id | bigint      | workspaces.id |
| title        | string      |               |
| is_favorite  | boolean     | Default false |
| is_archived  | boolean     | Default false |

### classes

| Column   | Type        | FK         |
| -------- | ----------- | ---------- |
| id       | bigint (PK) |            |
| board_id | bigint      | boards.id  |
| title    | string      |            |
| position | int         | Default 0  |

### students

| Column         | Type        | FK          |
| -------------- | ----------- | ----------- |
| id             | bigint (PK) |             |
| class_id       | bigint      | classes.id  |
| trainer_id     | bigint      | users.id    |
| name           | string      |             |
| photo          | string      | Nullable    |
| description    | text        | Nullable    |
| follow_up_date | date        | Nullable    |
| priority       | enum        | Low/Medium/High |
| status         | enum        | Pending/In Progress/Completed/Archived |
| position       | int         | Default 0   |

### comments

| Column     | Type        | FK           |
| ---------- | ----------- | ------------ |
| id         | bigint (PK) |              |
| student_id | bigint      | students.id  |
| user_id    | bigint      | users.id     |
| message    | text        |              |

### attachments

| Column     | Type        | FK           |
| ---------- | ----------- | ------------ |
| id         | bigint (PK) |              |
| student_id | bigint      | students.id  |
| file_path  | string      |              |
| file_type  | string      |              |
| file_size  | bigint      |              |

### labels

| Column | Type        | Notes    |
| ------ | ----------- | -------- |
| id     | bigint (PK) |          |
| name   | string      |          |
| color  | string(20)  | Hex code |

### student_labels (pivot)

| Column     | Type   | FK           |
| ---------- | ------ | ------------ |
| student_id | bigint | students.id  |
| label_id   | bigint | labels.id    |

Primary key: `(student_id, label_id)`

---

## Notes

- Token-based auth via **Laravel Sanctum**. Send the token in the `Authorization: Bearer <token>` header.
- All timestamps are in ISO 8601 format (`2026-07-07T00:00:00.000000Z`).
- Date fields (`follow_up_date`) use `Y-m-d` format.
- Validation errors return `422` with field-level messages.
- Only **Auth** and **Students** endpoints are currently exposed. Models for Workspaces, Boards, Classes, Comments, Attachments, and Labels exist in the database but do not have API routes yet.
