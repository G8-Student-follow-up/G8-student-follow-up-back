# G8 Student Follow-up — Backend

REST API backend built with Laravel 12 + Sanctum.  
Base URL: `http://localhost:8000/api`

---

## Authentication

All protected endpoints require a **Bearer token** in the `Authorization` header:
```
Authorization: Bearer <token>
```

---

## Endpoints

### Auth

| Method | Endpoint | Body | Response |
|--------|----------|------|----------|
| POST | `/register` | `{ name, email, password, confirm_password }` | `{ user, token }` |
| POST | `/login` | `{ email, password }` | `{ user, token }` |
| POST | `/logout` | — | `{ message }` |
| GET | `/user` | — | `{ user }` |
| PUT | `/user` | `{ name?, email?, phone?, telegram?, current_password?, password?, password_confirmation? }` | `{ user, message }` |
| POST | `/forgot-password` | `{ email }` | `{ message }` |
| POST | `/reset-password` | `{ email, token, password, password_confirmation }` | `{ message }` |

### Workspaces

| Method | Endpoint | Body / Params | Response |
|--------|----------|---------------|----------|
| GET | `/workspaces` | `?search=` | `{ workspaces[] }` |
| GET | `/workspaces/{id}` | — | `{ workspace }` |
| POST | `/workspaces` | `{ name }` | `{ workspace }` |
| PUT | `/workspaces/{id}` | `{ name? }` | `{ workspace }` |
| DELETE | `/workspaces/{id}` | — | 204 |
| GET | `/workspaces/{id}/members` | — | `{ members[] }` |
| POST | `/workspaces/{id}/members` | `{ user_id }` | `{ message }` |
| DELETE | `/workspaces/{id}/members/{userId}` | — | 204 |

### Boards

| Method | Endpoint | Body / Params | Response |
|--------|----------|---------------|----------|
| GET | `/boards` | `?workspace_id=` | `{ boards[] }` |
| GET | `/boards/{id}` | — | `{ board }` (includes `columns[]` with `cards[]`) |
| POST | `/boards` | `{ workspace_id, title }` | `{ board }` |
| PUT | `/boards/{id}` | `{ title?, workspace_id? }` | `{ board }` |
| DELETE | `/boards/{id}` | — | 204 |
| POST | `/boards/{id}/favorite` | — | `{ board }` |
| POST | `/boards/{id}/archive` | — | `{ board }` |
| GET | `/boards/{id}/members` | — | `{ members[] }` |
| POST | `/boards/{id}/members` | `{ user_id, role? }` | `{ member }` |
| DELETE | `/boards/{id}/members/{userId}` | — | 204 |

### Columns

| Method | Endpoint | Body | Response |
|--------|----------|------|----------|
| POST | `/columns` | `{ board_id, title, position? }` | `{ column }` |
| PUT | `/columns/{id}` | `{ title?, position? }` | `{ column }` |
| DELETE | `/columns/{id}` | — | 204 |
| POST | `/boards/{id}/columns/reorder` | `{ columns: [{ id, position }] }` | 200 |

### Cards

| Method | Endpoint | Body | Response |
|--------|----------|------|----------|
| POST | `/cards` | `{ board_id, column_id, title, student_id?, description?, position?, priority?, status?, follow_up_date?, due_date? }` | `{ card }` |
| PUT | `/cards/{id}` | `{ column_id?, title?, description?, position?, priority?, status?, follow_up_date?, due_date?, student_id? }` | `{ card }` |
| DELETE | `/cards/{id}` | — | 204 |
| PUT | `/cards/{id}/move` | `{ column_id, position }` | `{ card }` |
| GET | `/cards/{id}/comments` | — | `{ comments[] }` |
| POST | `/cards/{id}/comments` | `{ message }` | `{ comment }` |
| GET | `/cards/{id}/labels` | — | `{ labels[] }` |
| POST | `/cards/{id}/labels` | `{ label_id }` | `{ card_label }` |
| DELETE | `/cards/{id}/labels/{labelId}` | — | 204 |
| GET | `/cards/{id}/checklists` | — | `{ checklists[] }` |
| POST | `/cards/{id}/checklists` | `{ title }` | `{ checklist }` |
| GET | `/cards/{id}/attachments` | — | `{ attachments[] }` |
| POST | `/cards/{id}/attachments` | `{ file (multipart), file_type? }` | `{ attachment }` |

### Checklists

| Method | Endpoint | Body | Response |
|--------|----------|------|----------|
| POST | `/checklists/{id}/items` | `{ title, position? }` | `{ item }` |
| PUT | `/checklist-items/{id}` | `{ title?, is_completed?, position? }` | `{ item }` |
| DELETE | `/checklist-items/{id}` | — | 204 |

### Labels

| Method | Endpoint | Body | Response |
|--------|----------|------|----------|
| GET | `/boards/{id}/labels` | — | `{ labels[] }` |
| POST | `/boards/{id}/labels` | `{ name, color }` | `{ label }` |
| PUT | `/labels/{id}` | `{ name?, color? }` | `{ label }` |
| DELETE | `/labels/{id}` | — | 204 |

### Students

| Method | Endpoint | Params | Response |
|--------|----------|--------|----------|
| GET | `/students` | `?search=&status=&class=` | `{ students[] }` |
| GET | `/students/{id}` | — | `{ student }` |
| POST | `/students` | — | `{ student }` |
| PUT | `/students/{id}` | — | `{ student }` |
| DELETE | `/students/{id}` | — | 204 |

**POST/PUT `/students` body:** `{ class_id, trainer_id, name, photo?, description?, follow_up_date?, priority (Low|Medium|High), status (Pending|In Progress|Completed|Archived), position? }`

### Users

| Method | Endpoint | Params | Response |
|--------|----------|--------|----------|
| GET | `/users` | `?search=&role=` | `{ data[] }` (paginated) |
| GET | `/users/{id}` | — | `{ user }` |
| POST | `/users` | — | `{ user }` |
| PUT | `/users/{id}` | — | `{ user }` |
| DELETE | `/users/{id}` | — | 204 |

**POST `/users` body:** `{ name, email, password, role? (admin|trainer) }`  
**PUT `/users/{id}` body:** `{ name?, email?, avatar? (file) }`

### Activities

| Method | Endpoint | Params | Response |
|--------|----------|--------|----------|
| GET | `/activities` | `?page=&per_page=&type=&user_id=&search=` | `{ activities[], total, last_page }` |

---

## Running the Server

```bash
php artisan serve
# → http://127.0.0.1:8000
```

## CORS

Frontend is allowed at `http://localhost:5173`.  
Credentials (cookies / Authorization headers) are supported.

## Tech Stack

- **Laravel 12** + **PHP 8.2+**
- **Laravel Sanctum** — token-based auth
- **MySQL** — database
