# KaiMail

KaiMail is a temporary email platform with:
- Public inbox UI (`/`)
- Admin panel (`/adminkaishop`)
- External API for bots/integrations (`/api/*.php`)

## Current Product Rules
- `emails` table does not use expiry fields anymore.
- Admin bulk email creation supports up to `50` emails/request.
- External `POST /api/emails.php` currently allows `quantity` from `1` to `10`.

## Main URLs
- Public UI: `/`
- Admin UI: `/adminkaishop`
- API docs page (admin): `/adminkaishop/docs-api`
- Domain guide (admin): `/adminkaishop/docs-domain`

## Authentication

### External API (HMAC)
Required headers:
- `X-API-KEY`
- `X-API-TIMESTAMP`
- `X-API-NONCE`
- `X-API-SIGNATURE`

### Admin API
Required header:
- `X-ADMIN-ACCESS-KEY`

Login check endpoint:
- `POST /api/admin/auth.php` with `{ "password": "<ADMIN_ACCESS_KEY>" }`

## API Summary

### External API (`/api/*.php`)
- `POST /api/emails.php` create email(s)
- `GET /api/emails.php?email=user@domain.com` check email exists
- `DELETE /api/emails.php` delete by email
- `GET /api/messages.php?email=user@domain.com&limit=30` list messages
- `GET /api/messages.php?id=123` message detail
- `DELETE /api/messages.php` delete message(s)
- `GET /api/long-poll.php?email_id=1&last_check=YYYY-mm-dd HH:ii:ss`

### Admin API (`/api/admin/*.php`)
- `GET/POST/DELETE /api/admin/emails.php`
- `GET/POST/PUT/DELETE /api/admin/domains.php`
- `GET/DELETE /api/admin/messages.php`
- `GET /api/admin/stats.php`
- `GET/POST/DELETE /api/admin/auth.php`

## Database (Current)

### `domains`
- `id`
- `domain`
- `is_active`
- `created_at`

### `emails`
- `id`
- `domain_id`
- `email`
- `name_type` (`vn|en|custom`)
- `created_at`

### `messages`
- `id`
- `email_id`
- `from_email`
- `from_name`
- `subject`
- `body_text`
- `body_html`
- `message_id`
- `is_read`
- `received_at`

## Setup (Quick)
1. Configure `.env` from `.env.example`.
2. Create database.
3. Import schema from `data.sql`.
4. Configure web server rewrite rules.
5. Add domains in admin panel or `domains` table.

## Documentation
- [System docs index](docs/README.md)
- [Architecture](docs/ARCHITECTURE.md)
- [Setup](docs/SETUP.md)
- [Security](docs/SECURITY.md)
