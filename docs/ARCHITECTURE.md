# Architecture

## Components

### 1. Public UI
- Path: `/`
- Purpose: read inbox messages for an email
- JS uses:
  - `GET /api/messages.php`
  - `GET /api/long-poll.php`

### 2. Admin UI
- Path: `/adminkaishop`
- Purpose: manage domains, emails, messages, stats
- Auth: `X-ADMIN-ACCESS-KEY`

### 3. External API
- Base: `/api/*.php`
- Purpose: programmatic integrations (bots/tools)
- Auth: HMAC headers (`X-API-*`)

### 4. Webhook Receiver
- Path: `/api/webhook/receive-email.php`
- Purpose: ingest inbound email payloads

## Data Flow

### Inbox Read Flow
1. Client requests `GET /api/messages.php?email=...`
2. API validates auth + input
3. API returns message list
4. Client opens long-poll `GET /api/long-poll.php` for near real-time updates

### Email Creation Flow (Admin)
1. Admin submits create form
2. Admin UI calls `POST /api/admin/emails.php`
3. `includes/EmailService.php` creates one or many emails
4. Admin UI reloads list/stats

## Directory Notes
- `api/`: endpoint handlers and middleware
- `api/admin/`: admin-only endpoints
- `includes/`: shared services (`EmailService`, `MessageService`, ...)
- `adminkaishop/`: admin UI pages and docs pages
- `docs/`: documentation
