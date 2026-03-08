# Bảo mật & Kiểm soát truy cập

---

## Trang chủ (Public)

Không cần đăng nhập. Xác thực tự động bằng **Web UI Token** (session cookie).

- **Quyền**: Chỉ xem thư của email được nhập vào.
- **Không thể**: Tạo email, xóa, hay gọi API nội bộ.

---

## Admin Panel (`/adminkaishop`)

- Đăng nhập bằng `ADMIN_ACCESS_KEY`.
- Toàn quyền quản lý hệ thống.

---

## External API (`/api/*`) - Bảo mật HMAC Signature

Đây là phương thức bảo mật cấp cao nhất, bảo vệ hệ thống khỏi việc lộ Secret Key và chống lại các cuộc tấn công phát lại (Replay Attack).

### Các Header bắt buộc

| Header | Mô tả |
|---|---|
| `X-API-KEY` | Lấy từ `API_ACCESS_KEY` trong `.env` |
| `X-API-TIMESTAMP` | Unix Timestamp hiện tại (Server chỉ chấp nhận lệch tối đa 5 phút) |
| `X-API-NONCE` | Một chuỗi ngẫu nhiên duy nhất cho mỗi yêu cầu |
| `X-API-SIGNATURE` | Chữ ký tính toán bằng HMAC-SHA256 |

---

### Cách tính Signature (Quan trọng cho Bot)

Để tính được `X-API-SIGNATURE`, bạn cần làm theo các bước sau:

1. **Chuẩn bị Payload**: Tạo một chuỗi bằng cách nối các thành phần sau lại, phân tách bằng dấu xuống dòng (`\n`):
   - `METHOD`: (VÍ DỤ: `GET` hoặc `POST`)
   - `PATH`: (VÍ DỤ: `/api/messages.php`)
   - `TIMESTAMP`: (VÍ DỤ: `1709900000`)
   - `NONCE`: (VÍ DỤ: `abcxyz123`)
   - `BODY_HASH`: Mã băm SHA256 của nội dung Raw Body (nếu không có body thì băm chuỗi rỗng).

2. **Dùng Secret Key để ký**: Dùng thuật toán `HMAC-SHA256` với `API_SECRET_KEY` (từ `.env`) để ký Payload trên.

### Ví dụ code Python (Dành cho Bot)

```python
import hmac
import hashlib
import time
import requests

api_key = "key_truy_cap_public"
secret_key = "key_bi_mat_de_ky"
base_url = "https://tmail.kaishop.id.vn"
path = "/api/messages.php"
method = "GET"
nonce = "chuoi_ngau_nhien_bat_ky"
timestamp = str(int(time.time()))

# 1. Tính hash của body (nếu GET thì body rỗng)
body = ""
body_hash = hashlib.sha256(body.encode()).hexdigest()

# 2. Tạo Payload
payload = f"{method}\n{path}\n{timestamp}\n{nonce}\n{body_hash}"

# 3. Tính Signature (HMAC-SHA256 với Secret Key)
signature = hmac.new(
    secret_key.encode(),
    payload.encode(),
    hashlib.sha256
).hexdigest()

# 4. Gửi request với các header
headers = {
    "X-API-KEY": api_key,
    "X-API-TIMESTAMP": timestamp,
    "X-API-NONCE": nonce,
    "X-API-SIGNATURE": signature
}

response = requests.get(f"{base_url}{path}?email=user@domain.com", headers=headers)
print(response.json())
```

---

---

## Rate Limiting

- Vượt ngưỡng → `429 Too Many Requests`.
- Headers: `X-RateLimit-Limit`, `X-RateLimit-Remaining`, `X-RateLimit-Reset`.
