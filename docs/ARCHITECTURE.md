# Kiến trúc hệ thống

KaiMail được xây dựng với kiến trúc tách biệt, tập trung vào tốc độ và bảo mật.

## Các thành phần cốt lõi

### 1. Web UI (Công cộng)
Nằm tại đường dẫn gốc `/`. 
- **Mục đích**: Quyền truy cập "Chỉ đọc" cho người dùng để lấy mã OTP.
- **Cơ chế**: Sử dụng `session_start()` và một Web UI Token (`kaimail_web_ui_token`) để xác thực các yêu cầu lấy dữ liệu (fetch) và long-polling.
- **Hạn chế**: Chỉ được phép xem nội dung của email cụ thể mà người dùng đã yêu cầu.

### 2. Bảng quản lý (Chủ sở hữu)
Nằm tại đường dẫn `/adminkaishop`.
- **Mục đích**: Quản trị hệ thống.
- **Cơ chế**: Được bảo vệ bằng đăng nhập Admin và các API key dành riêng cho Admin.
- **Trách nhiệm**: Quản lý tên miền, xem thống kê toàn cục, và dọn dẹp tin nhắn.

### 3. Lớp API (Chương trình)
Tất cả các điểm cuối API được tập trung dưới thư mục `/api/`.
- **Admin APIs**: `/api/admin/*` - Yêu cầu xác thực Admin.
- **Public APIs**: `/api/*.php` - Được sử dụng bởi Trang chủ, yêu cầu Session Web hoặc Chữ ký API từ bên ngoài hợp lệ.

## Luồng dữ liệu (Người dùng lấy OTP)

1. Người dùng truy cập `tmail.kaishop.id.vn/user@domain.com`.
2. Frontend khởi tạo phiên làm việc (session) và nhận `webToken`.
3. Frontend bắt đầu quá trình **Long Polling** thông qua file `js/longPolling.js`.
4. API kiểm tra header `X-WEB-UI-TOKEN` và cho phép yêu cầu (Chế độ Chỉ đọc).
5. Khi có thư mới đến (qua SMTP/Webhook), quá trình long poll sẽ trả về dữ liệu ngay lập tức.
