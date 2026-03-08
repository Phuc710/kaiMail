# Setup

## 1. Environment
Copy `.env.example` to `.env` and set values.

Key variables:
- `API_ACCESS_KEY`
- `API_SECRET_KEY`
- `ADMIN_ACCESS_KEY`
- `WEBHOOK_SECRET`
- DB credentials

## 2. Database
Create DB and import schema:

```bash
mysql -u root -p < data.sql
```

The current `emails` table schema includes:
- `id`, `domain_id`, `email`, `name_type`, `created_at`

## 3. Web Server
- PHP 8+
- Apache/Nginx rewrite configured
- Project root points to this repository

## 4. Cloudflare/Webhook (optional)
Set worker env vars:
- `WEBHOOK_URL` -> `/api/webhook/receive-email.php`
- `WEBHOOK_SECRET` -> same value as server `.env`

## 5. Verify
- Open `/adminkaishop/login`
- Login using `ADMIN_ACCESS_KEY`
- Create test mailbox from admin UI
- Read messages from public UI or API
