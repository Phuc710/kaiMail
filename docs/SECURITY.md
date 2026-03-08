# Bảo mật & Kiểm soát truy cập

KaiMail triển khai mô hình bảo mật nhiều lớp để cân bằng giữa an toàn và khả năng tích hợp.

## Bảo mật External API

External API (`/api/*`) xác thực bằng **HMAC Signature**.

Header bắt buộc:
- `X-API-KEY`: Khóa truy cập.
- `X-API-TIMESTAMP`: Unix timestamp.
- `X-API-NONCE`: Chuỗi duy nhất cho từng request (khi `API_REQUIRE_NONCE=true`).
- `X-API-SIGNATURE`: Chữ ký HMAC-SHA256.

Server kiểm tra:
- API key hợp lệ.
- Timestamp trong TTL cho phép.
- Signature đúng theo payload chuẩn.
- Nonce chưa từng dùng (chống replay).

## Chính sách IP cho External API

KaiMail hiện **không chặn IP mặc định** cho External API.

- `API_ENFORCE_IP_POLICY=false` (mặc định): gọi API từ mọi IP, chỉ cần signature hợp lệ.
- `API_ENFORCE_IP_POLICY=true`: bật kiểm tra IP whitelist qua `API_ALLOWED_IPS` và `API_STRICT_MODE`.

Điều này phù hợp khi bot/script chạy trên hạ tầng IP thay đổi liên tục.

## Web UI Session Fallback

Để người dùng web truy cập API nội bộ mà không cần API key:
- Web UI gửi `X-WEB-UI-TOKEN`.
- Token phải khớp session server và cùng origin.
- Cơ chế này chỉ phục vụ luồng web nội bộ.

## HTTPS & Proxy

Trong production:
- Bật `API_REQUIRE_HTTPS=true`.
- Nếu chạy sau Cloudflare/reverse proxy, bật `API_TRUST_PROXY_HEADERS=true` để nhận đúng proto và client IP.

## Rate Limit

Tất cả request API đều qua rate limiter:
- Quá ngưỡng sẽ trả `429 Too Many Requests`.
- Header phản hồi có `X-RateLimit-Limit`, `X-RateLimit-Remaining`, `X-RateLimit-Reset`.
