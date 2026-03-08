# Cài đặt & Môi trường

Hướng dẫn cấu hình KaiMail cho production.

## Biến môi trường chính (`.env`)

| Biến | Mô tả | Đề xuất (Production) |
|------|-------|----------------------|
| `APP_ENV` | Môi trường chạy | `production` |
| `APP_BASE_URL` | URL chính thức | `https://tmail.kaishop.id.vn` |
| `API_REQUIRE_HTTPS` | Bắt buộc HTTPS cho API | `true` |
| `API_TRUST_PROXY_HEADERS` | Tin cậy `X-Forwarded-*`/`CF-Connecting-IP` | `true` nếu dùng Cloudflare/proxy |
| `API_ENFORCE_IP_POLICY` | Bật kiểm tra IP cho External API | `false` |
| `API_STRICT_MODE` | Chế độ strict khi dùng IP policy | `false` nếu không whitelist |
| `API_ALLOWED_IPS` | Danh sách IP/CIDR được phép | để trống nếu không bật IP policy |
| `SESSION_COOKIE_SECURE` | Chỉ gửi cookie qua HTTPS | `true` |

## Khuyến nghị cho bot/script IP thay đổi

Đặt:
- `API_ENFORCE_IP_POLICY=false`
- `API_STRICT_MODE=false`
- `API_ALLOWED_IPS=`

Khi đó External API chỉ dựa vào Signature + TTL + Nonce.

## Checklist triển khai

1. Cấu hình SSL hợp lệ (Let's Encrypt hoặc Cloudflare Full Strict).
2. Kiểm tra `WEBHOOK_SECRET`, `API_ACCESS_KEY`, `API_SECRET_KEY` đã đổi sang giá trị mạnh.
3. Bật `DISPLAY_ERRORS=false`, `EXPOSE_ERROR_DETAILS=false`.
4. Đảm bảo thư mục `storage/` có quyền ghi cho cache và log.
5. Kiểm tra endpoint `/api/admin/auth.php` trả đúng `200/401/403` theo cấu hình bảo mật.
