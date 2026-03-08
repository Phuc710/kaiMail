# Kiến trúc hệ thống

---

## Thành phần chính

### 1. Trang chủ (Public Portal)

- **Đường dẫn**: `/`
- **Mục đích**: Khách hàng nhập email → xem hòm thư → lấy OTP.
- **Cơ chế**: Dùng PHP Session + `kaimail_web_ui_token` để xác thực các request API nội bộ.
- **Quyền hạn**: Chỉ xem thư của email được nhập vào. Không thể tạo email mới.

### 2. Admin Panel

- **Đường dẫn**: `/adminkaishop`
- **Mục đích**: Quản lý toàn bộ hệ thống (domain, email, thư, cài đặt).
- **Xác thực**: Đăng nhập bằng `ADMIN_ACCESS_KEY`.

### 3. Public API

- **Đường dẫn**: `/api/messages.php`, `/api/longpoll.php`
- **Mục đích**: Phục vụ trang chủ. Nhận dữ liệu hòm thư realtime qua Long Polling.
- **Xác thực**: Web UI Session Token.

### 4. External API

- **Đường dẫn**: `/api/admin/*`, các endpoint riêng
- **Mục đích**: Bot/script của chủ sở hữu tích hợp (tạo email, nhận webhook, v.v.)
- **Xác thực**: HMAC Signature.

---

## Luồng dữ liệu - Người dùng lấy OTP

```
1. User truy cập tmail.kaishop.id.vn/user@domain.com
2. PHP tạo session + web_ui_token
3. JS gửi request đến /api/messages.php với X-WEB-UI-TOKEN
4. API xác thực token → trả về danh sách thư
5. JS khởi động Long Polling → nhận thư mới realtime
6. Thư OTP đến → hiển thị ngay trong hòm thư
```

---

## Luồng dữ liệu - Nhận thư qua Webhook

```
1. Server mail gửi webhook đến /api/webhook.php
2. Webhook xác thực WEBHOOK_SECRET
3. Lưu thư vào bảng messages
4. Long Polling phát hiện thư mới → đẩy đến trình duyệt của user
```

---

## Cấu trúc thư mục chính

```
/
├── index.php           # Trang chủ (Public)
├── api/
│   ├── messages.php    # API lấy thư (Public)
│   ├── longpoll.php    # Long Polling (Public)
│   ├── webhook.php     # Nhận thư từ mail server
│   ├── admin/          # Admin API (riêng tư)
│   └── middleware/
│       ├── ApiSecurity.php     # Bảo mật API
│       └── AdminSecurity.php   # Bảo mật Admin
├── config/
│   ├── config.php      # Cấu hình constants
│   └── database.php    # Kết nối DB
├── includes/
│   └── MessageService.php  # Logic xử lý thư
├── js/
│   ├── app.js          # Logic trang chủ
│   └── longPolling.js  # Long Polling client
├── docs/               # Tài liệu này
└── .env                # Biến môi trường
```
