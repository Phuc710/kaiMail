# KaiMail External API Documentation

Clean RESTful API để tạo email tự động và đọc messages từ tool bên ngoài.

## Base URL

```
Production: https://tmail.kaishop.id.vn/api
Development: http://localhost/kaiMail/api
```

## Authentication

Tất cả requests cần header:
```
X-Secret-Key: 65a276de438f97d2b4496724e59d18d443168d3d2ed
```

## Endpoints

### 1. Tạo Email

**POST** `/api/emails`

**Request:**
```json
{
  "count": 1,
  "name_type": "en",
  "expiry_type": "forever",
  "domain": "kaishop.id.vn"
}
```

**Parameters:**
- `count` (int, required): Số lượng email cần tạo (1-100)
- `name_type` (string, required): `vn`, `en`, hoặc `custom`
- `expiry_type` (string, required): `30days`, `1year`, `2years`, `forever`
- `domain` (string, required): Domain email (phải có trong database)

**Response:**
```json
{
  "success": true,
  "count": 1,
  "emails": ["cooluser123@kaishop.id.vn"]
}
```

**Error Response (Domain not found):**
```json
{
  "error": "Domain not found or inactive",
  "domain": "invalid.com",
  "available_domains": ["kaishop.id.vn", "example.com"]
}
```

---

### 2. Đọc Messages

**GET** `/api/emails/{email}/messages`

**Example:**
```
GET /api/emails/cooluser123@kaishop.id.vn/messages
```

**Response:**
```json
{
  "success": true,
  "email": "cooluser123@kaishop.id.vn",
  "message_count": 2,
  "messages": [
    {
      "id": 1,
      "from_email": "noreply@service.com",
      "from_name": "Service",
      "subject": "Your OTP Code",
      "body_text": "Your OTP is 123456",
      "body_html": "<p>Your OTP is <b>123456</b></p>",
      "is_read": 0,
      "received_at": "2026-01-27 13:00:00"
    }
  ]
}
```

---

## Error Codes

- `400` - Bad Request (invalid parameters)
- `401` - Unauthorized (invalid secret key)
- `404` - Not Found (email/domain không tồn tại)
- `405` - Method Not Allowed
- `410` - Gone (email đã expired)
- `500` - Internal Server Error

---

## Ví dụ sử dụng

### PHP
```php
<?php
$secretKey = '65a276de438f97d2b4496724e59d18d443168d3d2ed';
$baseUrl = 'http://localhost/kaiMail/api';

// Tạo email
$ch = curl_init("$baseUrl/emails");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    "X-Secret-Key: $secretKey"
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'count' => 1,
    'name_type' => 'en',
    'expiry_type' => 'forever',
    'domain' => 'kaishop.id.vn'
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$data = json_decode($response, true);
$email = $data['emails'][0];
echo "Created: $email\n";

// Đợi nhận OTP...
sleep(5);

// Đọc messages
$ch = curl_init("$baseUrl/emails/" . urlencode($email) . "/messages");
curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-Secret-Key: $secretKey"]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$data = json_decode($response, true);

foreach ($data['messages'] as $msg) {
    echo "From: {$msg['from_email']}\n";
    echo "Subject: {$msg['subject']}\n";
    echo "Body: {$msg['body_text']}\n\n";
}
?>
```

### Python
```python
import requests
import time

SECRET_KEY = '65a276de438f97d2b4496724e59d18d443168d3d2ed'
BASE_URL = 'http://localhost/kaiMail/api'
headers = {'X-Secret-Key': SECRET_KEY}

# Tạo email
response = requests.post(
    f'{BASE_URL}/emails',
    headers=headers,
    json={
        'count': 1,
        'name_type': 'en',
        'expiry_type': 'forever',
        'domain': 'kaishop.id.vn'
    }
)
data = response.json()
email = data['emails'][0]
print(f"Created: {email}")

# Đợi nhận OTP
time.sleep(5)

# Đọc messages
response = requests.get(
    f'{BASE_URL}/emails/{email}/messages',
    headers=headers
)
data = response.json()

for msg in data['messages']:
    print(f"From: {msg['from_email']}")
    print(f"Subject: {msg['subject']}")
    print(f"Body: {msg['body_text']}\n")
```

### cURL
```bash
# Tạo email
curl -X POST http://localhost/kaiMail/api/emails \
  -H "Content-Type: application/json" \
  -H "X-Secret-Key: 65a276de438f97d2b4496724e59d18d443168d3d2ed" \
  -d '{"count":1,"name_type":"en","expiry_type":"forever","domain":"kaishop.id.vn"}'

# Đọc messages
curl -X GET "http://localhost/kaiMail/api/emails/cooluser123@kaishop.id.vn/messages" \
  -H "X-Secret-Key: 65a276de438f97d2b4496724e59d18d443168d3d2ed"
```

### JavaScript (Fetch)
```javascript
const SECRET_KEY = '65a276de438f97d2b4496724e59d18d443168d3d2ed';
const BASE_URL = 'http://localhost/kaiMail/api';

// Tạo email
const createEmail = async () => {
  const response = await fetch(`${BASE_URL}/emails`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-Secret-Key': SECRET_KEY
    },
    body: JSON.stringify({
      count: 1,
      name_type: 'en',
      expiry_type: 'forever',
      domain: 'kaishop.id.vn'
    })
  });
  
  const data = await response.json();
  return data.emails[0];
};

// Đọc messages
const getMessages = async (email) => {
  const response = await fetch(
    `${BASE_URL}/emails/${encodeURIComponent(email)}/messages`,
    {
      headers: {
        'X-Secret-Key': SECRET_KEY
      }
    }
  );
  
  return await response.json();
};

// Sử dụng
(async () => {
  const email = await createEmail();
  console.log('Created:', email);
  
  await new Promise(resolve => setTimeout(resolve, 5000));
  
  const data = await getMessages(email);
  data.messages.forEach(msg => {
    console.log(`From: ${msg.from_email}`);
    console.log(`Subject: ${msg.subject}`);
    console.log(`Body: ${msg.body_text}\n`);
  });
})();
```

---

## Thêm Domain mới

Không cần sửa code, chỉ cần thêm vào database:

```sql
INSERT INTO domains (domain, is_active) VALUES ('newdomain.com', 1);
```

Sau đó có thể tạo email với domain mới:
```json
{
  "count": 1,
  "domain": "newdomain.com",
  "name_type": "en",
  "expiry_type": "forever"
}
```

---

## Architecture

### Clean RESTful Design

```
Request → Router → Middleware → Controller → Service → Database
                                                ↓
                                            Response
```

### Components

- **Router** (`api/index.php`) - Parse URL và route requests
- **Middleware** (`AuthMiddleware`) - Validate secret key
- **Controller** (`EmailController`) - Handle HTTP logic
- **Services** (`EmailService`, `DomainService`) - Business logic
- **Models** - Database queries

### Benefits

✅ **Clean URLs** - No `.php` extensions  
✅ **RESTful** - Proper resource naming  
✅ **Scalable** - Easy to add new endpoints  
✅ **Maintainable** - Clear separation of concerns  
✅ **Flexible** - Domain from input, support multiple domains
