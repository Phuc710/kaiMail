# Tài liệu KaiMail - Tổng quan

Chào mừng bạn đến với tài liệu chính thức của KaiMail, hệ thống nhận thư tạm thời thời gian thực thuộc hệ sinh thái KaiShop.

## Tầm nhìn dự án
KaiMail cung cấp nền tảng cho người dùng truy cập các hộp thư tạm thời để:
- Nhận mã OTP (mật khẩu một lần).
- Xác minh tài khoản.
- Kiểm tra các luồng đăng ký an toàn.

## Vai trò giao diện

| Thành phần | Đường dẫn URL | Quyền truy cập | Trách nhiệm |
|------------|---------------|----------------|-------------|
| **Cổng thông tin công cộng** | `/` | Công cộng (Public) | Trang chủ để người dùng nhập email và đọc tin nhắn đến. |
| **Bảng điều khiển Admin** | `/adminkaishop` | Riêng tư (Chủ sở hữu) | Quản lý domain, email, tin nhắn và cấu hình hệ thống. |
| **External API** | `/api/*` | Riêng tư (Chủ sở hữu) | API tích hợp cho bot/script bên ngoài, xác thực bằng chữ ký HMAC. |

## Mục lục tài liệu

- [Kiến trúc & Luồng dữ liệu](./ARCHITECTURE.md) - Cách hệ thống hoạt động.
- [Bảo mật & Quyền truy cập](./SECURITY.md) - Signature, HTTPS, rate limit và policy.
- [Cài đặt & Môi trường](./SETUP.md) - Cấu hình triển khai.

## Ghi chú vận hành

- **Trang chủ** (`index.php`): cổng công cộng cho người dùng lấy OTP. Trình duyệt dùng Web UI token để truy cập API cùng origin.
- **Trang Admin** (`/adminkaishop`): khu vực riêng tư dành cho chủ sở hữu.
- **External API** (`/api/*`): chỉ yêu cầu chữ ký hợp lệ; không yêu cầu IP cố định khi `API_ENFORCE_IP_POLICY=false`.
- **Middleware chính** (`api/middleware/ApiSecurity.php`): kiểm tra HTTPS, rate limit, timestamp/nonce/signature và chống replay.
