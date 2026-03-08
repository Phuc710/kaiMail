# Cài đặt & Môi trường

---

## Biến môi trường (`.env`)

### Ứng dụng

| Biến | Mô tả | Production |
|---|---|---|
| `APP_ENV` | Môi trường | `production` |
| `APP_BASE_URL` | URL chính thức | `https://tmail.kaishop.id.vn` |
| `APP_TIMEZONE` | Múi giờ | `Asia/Ho_Chi_Minh` |

### Cơ sở dữ liệu

| Biến | Mô tả |
|---|---|
| `DB_HOST` | Host MySQL |
| `DB_NAME` | Tên database |
| `DB_USER` | Tên user |
| `DB_PASS` | Mật khẩu |

### API & Bảo mật

| Biến | Mô tả | Giá trị |
|---|---|---|
| `API_ACCESS_KEY` | **Khóa API (X-API-KEY)** | Chuỗi ngẫu nhiên 64 ký tự |
| `API_SECRET_KEY` | **Khóa Bí mật (X-API-SECRET)** | Chuỗi ngẫu nhiên 64 ký tự |
| `ADMIN_ACCESS_KEY` | Mật khẩu đăng nhập admin | Chuỗi ngẫu nhiên mạnh |
| `WEBHOOK_SECRET` | Secret xác thực webhook nhận thư | Chuỗi ngẫu nhiên mạnh |
| `API_REQUIRE_HTTPS` | Bắt buộc HTTPS | `true` |
| `API_TRUST_PROXY_HEADERS` | Tin tưởng Cloudflare/proxy | `true` nếu dùng Cloudflare |
| `API_RATE_LIMIT_PER_MIN` | Giới hạn request/phút | `120` |

### Session

| Biến | Production |
|---|---|
| `SESSION_COOKIE_SECURE` | `true` |
| `SESSION_COOKIE_SAMESITE` | `Lax` |

---

## Checklist triển khai Production

- [ ] `APP_ENV=production`
- [ ] `DISPLAY_ERRORS=false`, `EXPOSE_ERROR_DETAILS=false`
- [ ] Đổi `API_ACCESS_KEY`, `API_SECRET_KEY`, `ADMIN_ACCESS_KEY` sang giá trị mạnh
- [ ] Bật SSL (Let's Encrypt hoặc Cloudflare Full Strict)
- [ ] `API_TRUST_PROXY_HEADERS=true` nếu dùng Cloudflare
- [ ] Thư mục `storage/` có quyền ghi
