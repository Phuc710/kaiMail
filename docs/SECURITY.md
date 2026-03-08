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

## External API (`/api/*`)

Dành cho bot/script tích hợp. Xác thực bằng **2 Khóa tĩnh**.

Gửi các header sau trong mọi request:

| Header | Giá trị trong .env |
|---|---|
| `X-API-KEY` | `API_ACCESS_KEY` |
| `X-API-SECRET` | `API_SECRET_KEY` |

### Ví dụ

```bash
curl -H "X-API-KEY: key_cua_ban" -H "X-API-SECRET: secret_cua_ban" https://tmail.kaishop.id.vn/api/messages.php?email=user@domain.com
```

- Không cần IP cố định.
- Không cần Signature phức tạp.
- Chỉ cần đúng 2 key là dùng được.

---

## HTTPS & Proxy

- Production bắt buộc HTTPS (`API_REQUIRE_HTTPS=true`).
- Nếu dùng Cloudflare: bật `API_TRUST_PROXY_HEADERS=true`.

---

## Rate Limiting

- Vượt ngưỡng → `429 Too Many Requests`.
- Headers: `X-RateLimit-Limit`, `X-RateLimit-Remaining`, `X-RateLimit-Reset`.
