# Cài đặt & Môi trường

---

## Biến môi trường (`.env`)

### API & Bảo mật (Cực kỳ quan trọng)

| Biến | Mô tả |
|---|---|
| `API_ACCESS_KEY` | Khóa API công khai (Gửi qua header `X-API-KEY`) |
| `API_SECRET_KEY` | **Khóa Bí mật dùng để ký Signature** (Không bao giờ gửi key này đi) |
| `API_REQUEST_TTL` | Thời gian hiệu lực của yêu cầu (giây) - Mặc định: `300` |
| `API_REQUIRE_NONCE` | Bắt buộc dùng `X-API-NONCE` để chống Replay Attack | `true` |
| `API_NONCE_TTL` | Thời gian lưu trữ Nonce (giây) | `300` |

### Quản trị & Webhook

| Biến | Mô tả |
|---|---|
| `ADMIN_ACCESS_KEY` | Mật khẩu đăng nhập Admin Panel |
| `WEBHOOK_SECRET` | Khóa để xác thực dữ liệu từ Cloudflare Worker gửi về |

---

## Cấu hình Cloudflare Worker

Để nhận được mail, bạn cần cấu hình Cloudflare Worker với 2 biến môi trường (Environment Variables):

1. **`WEBHOOK_URL`**: Đường dẫn đến api nhận mail.
   - Ví dụ: `https://tmail.kaishop.id.vn/api/webhook/receive-email.php`
2. **`WEBHOOK_SECRET`**: Phải khớp với giá trị `WEBHOOK_SECRET` trong file `.env`.

---

## Checklist bảo mật cho Bot

1. **Không lộ API_SECRET_KEY**: Chỉ để key này tại server của bạn và server bot. Không bao giờ gửi nó qua Header.
2. **Khớp Timestamp**: Đảm bảo đồng hồ của máy chạy bot khớp với server (Server cho phép lệch tối đa 5 phút).
3. **Nonce duy nhất**: Mỗi request bot nên tạo một chuỗi ngẫu nhiên mới cho `X-API-NONCE`.

---

## Các bước triển khai Production

- [ ] Import database `csdl.sql`
- [ ] Cấu hình `.env` chuẩn (đã dọn dẹp ở bước trước)
- [ ] Deploy Cloudflare Worker và set biến môi trường
- [ ] Bật Email Routing trên Cloudflare và trỏ vào Worker
- [ ] Kiểm tra quyền ghi cho thư mục `storage/`
