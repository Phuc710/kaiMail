# KaiMail - Tài liệu hệ thống

Tài liệu chính thức của **KaiMail**, hệ thống email tạm thời thuộc hệ sinh thái KaiShop.

---

## Tổng quan kiến trúc

KaiMail hoạt động theo mô hình phân quyền rõ ràng:

| Thành phần | URL | Ai dùng | Vai trò |
|---|---|---|---|
| **Trang chủ** | `/` | Khách hàng | Xem hòm thư và lấy OTP (chỉ đọc) |
| **Admin Panel** | `/adminkaishop` | Chủ sở hữu | Tạo/quản lý domain, email, tin nhắn |
| **External API** | `/api/*` | Bot/Script của chủ | Tạo email, webhook nhận thư, tích hợp |

---

## Luồng hoạt động

```
Chủ tạo email (Admin/API) → Bán cho khách → Khách vào trang chủ → Nhập email → Xem OTP
```

1. **Chủ sở hữu** tạo email qua Admin Panel hoặc External API.
2. **Bán email** cho khách hàng.
3. **Khách hàng** vào `tmail.kaishop.id.vn`, nhập email mình đã mua → xem thư và lấy OTP.
4. Nếu email chưa có trong hệ thống → thông báo lỗi rõ ràng.

---

## Tài liệu chi tiết

- [Bảo mật & API](./SECURITY.md)
- [Cài đặt môi trường](./SETUP.md)
