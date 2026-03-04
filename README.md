# KaiMail - Temporary Email System

## 📋 Tổng Quan

KaiMail là hệ thống temporary email (email tạm thời) được xây dựng bằng PHP, RESTful API. Hệ thống cho phép tạo email tạm thời và nhận messages từ external services.

## 🏗️ Architecture

### Technology Stack
- **Backend**: PHP 8.0+
- **Database**: MySQL/MariaDB
- **Web Server**: Apache (XAMPP)
- **Pattern**: MVC + Service Layer
- **API Style**: RESTful

### Project Structure

```
kaiMail/
├── api/                        # API Layer
│   ├── controllers/            # Controllers
│   │   └── EmailController.php
│   ├── services/               # Business Logic
│   │   ├── BaseService.php
│   │   ├── DomainService.php
│   │   └── EmailService.php
│   ├── middleware/             # Middleware
│   │   └── AuthMiddleware.php
│   ├── admin/                  # Admin API
│   │   ├── auth.php
│   │   ├── emails.php
│   │   ├── messages.php
│   │   └── stats.php
│   ├── webhook/                # Cloudflare Worker Webhook
│   │   └── emails.php
│   ├── index.php               # API Router (Clean URLs)
│   ├── emails.php              # User API - Check email
│   ├── messages.php            # User API - Get messages
│   └── poll.php                # Long polling
│
├── adminkaishop/               # Admin Panel
│   ├── index.php               # Dashboard
│   ├── login.php               # Login page
│   ├── expired.php             # Expired emails
│   ├── docs-api.php            # API Documentation
│   └── docs-domain.php         # Domain Management Guide
│
├── config/                     # Configuration
│   ├── app.php                 # App settings
│   ├── database.php            # Database connection
│   └── domains.php             # Domain config
│
├── includes/                   # Shared Classes
│   ├── Auth.php                # Authentication class
│   └── NameGenerator.php       # Email name generator
│
├── css/                        # Stylesheets
├── js/                         # JavaScript
├── index.php                   # User interface
└── .htaccess                   # URL Rewriting
```

## 🔑 Core Components

### 1. API Router (`api/index.php`)
Clean RESTful API router:
- `GET /api` - API info
- `POST /api/emails` - Create email
- `GET /api/emails/{email}/messages` - Get messages

### 2. Services Layer

#### BaseService
- Shared validation logic
- Common helper methods
- Parent class cho tất cả services

#### DomainService
- Validate domains
- Get domain ID
- List active domains

#### EmailService
- Create emails (dùng NameGenerator)
- Get email data
- Get messages
- Calculate expiry dates

### 3. Controllers

#### EmailController
- Handle HTTP requests
- Input validation
- Call services
- Format responses

### 4. Middleware

#### AuthMiddleware
- Validate `X-API-KEY`, `X-API-SECRET`, `X-API-TIMESTAMP`, `X-API-SIGNATURE`
- Centralized authentication

## 📡 API Endpoints

### External API (for tools)

**Authentication (required for external/integration API calls)**:
- `X-API-KEY`
- `X-API-SECRET`
- `X-API-TIMESTAMP` (Unix timestamp)
- `X-API-SIGNATURE` (`HMAC-SHA256(METHOD + "\n" + PATH + "\n" + TIMESTAMP)`)

#### Create Email
```http
POST /api/emails
Content-Type: application/json

{
  "count": 1,
  "name_type": "en",
  "expiry_type": "forever",
  "domain": "kaishop.id.vn"
}
```

#### Get Messages
```http
GET /api/emails/{email}/messages
```

### User API

All user endpoints are protected by API key/secret headers.

#### Check Email
```http
GET /api/emails?email=test@kaishop.id.vn
```

#### Get Messages List
```http
GET /api/messages?email=test@kaishop.id.vn
```

#### Get Message Detail
```http
GET /api/messages?id=123
```

#### Long Polling
```http
GET /api/poll?email_id=1&last_check=2026-01-27%2014:00:00
```

### Admin API

#### Access Key Check (Admin UI)
```http
POST /api/admin/auth.php
{"password":"<ADMIN_ACCESS_KEY>"}
```

#### Auth Check (Key/Secret hoặc Admin Access Key Header)
```http
GET /api/admin/auth.php
```

#### Get Emails
```http
GET /api/admin/emails?page=1&limit=50
```

#### Delete Email
```http
DELETE /api/admin/emails/{id}
```

## 🗄️ Database Schema

### Tables

#### `domains`
- `id` - Primary key
- `domain` - Domain name (unique)
- `is_active` - Active status
- `created_at` - Creation timestamp

#### `emails`
- `id` - Primary key
- `domain_id` - Foreign key → domains
- `email` - Full email address (unique)
- `name_type` - Name type (vn/en/custom)
- `expiry_type` - Expiry type
- `expires_at` - Expiry datetime
- `is_expired` - Expired flag
- `created_at` - Creation timestamp

#### `messages`
- `id` - Primary key
- `email_id` - Foreign key → emails
- `from_email` - Sender email
- `from_name` - Sender name
- `subject` - Email subject
- `body_text` - Plain text body
- `body_html` - HTML body
- `is_read` - Read status
- `received_at` - Received timestamp

## 🔐 Security

### Authentication Methods

1. **External API calls**: API key + secret + timestamp + HMAC signature headers
2. **Admin Web UI**: `ADMIN_ACCESS_KEY` qua header `X-ADMIN-ACCESS-KEY` (không dùng cookie)
3. **Admin API endpoints**: chấp nhận `X-ADMIN-ACCESS-KEY` hoặc bộ key/secret cho tool ngoài
4. **Webhook endpoint**: `X-WEBHOOK-SECRET` header

### Secret Key
```
65a276de438f97d2b4496724e59d18d443168d3d2ed
```

### Admin Access Key
```
kaishop@2026
```

## 🚀 Deployment

### Requirements
- PHP 8.0+
- MySQL/MariaDB
- Apache with mod_rewrite
- cURL extension

### Setup Steps

1. **Clone/Upload code**
   ```bash
   cd c:\xampp\htdocs\
   # Upload kaiMail folder
   ```

2. **Create database**
   ```sql
   CREATE DATABASE kaishop1_tmail;
   ```

3. **Import schema**
   ```bash
   mysql -u root kaishop1_tmail < database.sql
   ```

4. **Configure database**
   Edit `config/database.php`:
   ```php
   'host' => 'localhost',
   'database' => 'kaishop1_tmail',
   'username' => 'root',
   'password' => ''
   ```

5. **Add domains**
   ```sql
   INSERT INTO domains (domain, is_active) 
   VALUES ('kaishop.id.vn', 1);
   ```

6. **Setup Cloudflare Worker**
   - Deploy `cloudflare-worker.js`
   - Configure email routing
   - Point webhook to your server

7. **Access**
   - User: `http://localhost/kaiMail`
   - Admin: `http://localhost/kaiMail/adminkaishop`
   - API: `http://localhost/kaiMail/api`

## 🎯 Features

### ✅ Implemented

- ✅ Clean RESTful API
- ✅ Multiple domain support
- ✅ Email expiry management
- ✅ Real-time long polling
- ✅ Admin dashboard
- ✅ Cloudflare Worker integration
- ✅ Vietnamese & English name generator
- ✅ OOP architecture with service layer
- ✅ CORS support
- ✅ Error handling

### 🔄 Architecture Improvements (Done)

- ✅ Removed duplicate code
- ✅ Use NameGenerator.php (no duplicate)
- ✅ BaseService for shared logic
- ✅ Clean URL routing
- ✅ Middleware pattern
- ✅ Controller/Service separation

## 📝 Code Guidelines

### Service Layer Pattern
```php
// BaseService - shared logic
class BaseService {
    protected PDO $db;
    protected function isValidEmail($email) { ... }
}

// Specific services extend base
class EmailService extends BaseService {
    public function createEmail(...) { ... }
}
```

### Controller Pattern
```php
class EmailController {
    private EmailService $emailService;
    
    public function create() {
        // 1. Validate input
        // 2. Call service
        // 3. Return response
    }
}
```

### Router Pattern
```php
// Parse URL → Route to controller → Execute action
$path = parse_url($_SERVER['REQUEST_URI'])['path'];
$controller->handleRequest($path, $method);
```

## 🛠️ Maintenance

### Adding New Domain
```sql
INSERT INTO domains (domain, is_active) 
VALUES ('newdomain.com', 1);
```

### Viewing Logs
```bash
tail -f storage/logs/error.log
```

### Database Backup
```bash
mysqldump -u root kaishop1_tmail > backup.sql
```

## 📚 Documentation Files

- `API_DOCS.md` - API documentation
- `README.md` - This file
- Admin Panel:
  - `/adminkaishop/docs-api` - API usage guide
  - `/adminkaishop/docs-domain` - Domain management guide

## 🐛 Troubleshooting

### API returns 404
- Check `.htaccess` rewrite rules
- Verify mod_rewrite is enabled

### Email not created
- Check domain exists in database
- Verify domain is active
- Check database connection

### Messages not received
- Verify Cloudflare Worker is deployed
- Check webhook URL is correct
- Verify secret key matches

## 📞 Support

For issues or questions, check:
1. Error logs in `storage/logs/`
2. Browser console (for frontend)
3. API responses (for debugging)

---

**Version**: 2.0.0  
**Last Updated**: 2026-01-27  
**Author**: KaiShop Development Team
