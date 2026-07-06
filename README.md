# G8 Student Follow-up — Backend

REST API backend for the Student Follow-up application, built with Laravel 12. It exposes endpoints to manage workspaces, class rooms, students, comments, attachments, and labels for trainer follow-up boards (Kanban-style).

## Tech Stack

- **PHP** 8.2+ / **Laravel** 12
- **Laravel Sanctum** — API authentication
- **SQLite** (default) — swap via `.env` for MySQL/Postgres
- **L5-Swagger** (`darkaonline/l5-swagger`) — OpenAPI documentation generated from PHP attributes
- **Vite** + **Tailwind CSS** — asset bundling (for any bundled front-end assets)

## Domain Model

- **Workspace** — top-level container
- **Board** — Kanban board within a workspace
- **ClassRoom** — a class of students
- **Student** — tracked with `priority`, `status`, `follow_up_date`, `position` (for board ordering)
- **Comment** / **Attachment** / **Label** — attached to students for follow-up notes, files, and tagging
- **User** — trainers, authenticated via Sanctum

## Getting Started

### Prerequisites

- PHP >= 8.2 with Composer
- Node.js + npm

### Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite   # if using the default SQLite connection
php artisan migrate
npm install
```

Or run all of the above in one step:

```bash
composer run setup
```

### Running the app

```bash
composer run dev
```

This starts the PHP dev server, queue listener, log watcher (Pail), and Vite dev server concurrently.

Alternatively, just the API:

```bash
php artisan serve
```

## API Documentation

API endpoints are documented with OpenAPI attributes (`zircote/swagger-php`) and served via L5-Swagger.

Generate the docs:

```bash
php artisan l5-swagger:generate
```

View them at:

```
http://localhost:8000/api/documentation
```

## Testing

```bash
composer run test
```

## Project Structure

```
app/Http/Controllers/Api/   API controllers (e.g. StudentController)
app/Models/                 Eloquent models
database/migrations/        Schema migrations
routes/api.php              API route definitions
```
