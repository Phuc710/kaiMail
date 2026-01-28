<?php
/**
 * API Documentation
 * KaiMail Admin - Complete API Reference
 */

require_once __DIR__ . '/../includes/Auth.php';
Auth::requireLogin();

$admin = Auth::getAdmin();
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Documentation - KaiMail Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/logo.svg">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/admin.css">
</head>

<body>
    <!-- Toast -->
    <div id="toast" class="toast"></div>

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
                <polyline points="22,6 12,13 2,6" />
            </svg>
            <span>KaiMail</span>
        </div>

        <nav class="sidebar-nav">
            <a href="<?= BASE_URL ?>/adminkaishop" class="nav-item" data-page="emails">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
                    <polyline points="22,6 12,13 2,6" />
                </svg>
                <span>Quản lý Email</span>
            </a>
            <a href="<?= BASE_URL ?>/adminkaishop/expired" class="nav-item" data-page="expired">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10" />
                    <line x1="12" y1="8" x2="12" y2="12" />
                    <line x1="12" y1="16" x2="12.01" y2="16" />
                </svg>
                <span>Email hết hạn</span>
            </a>
            <a href="<?= BASE_URL ?>/adminkaishop/docs-domain" class="nav-item" data-page="docs-domain">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                    <polyline points="14 2 14 8 20 8" />
                    <line x1="12" y1="18" x2="12" y2="12" />
                    <line x1="9" y1="15" x2="15" y2="15" />
                </svg>
                <span>Docs - Domain</span>
            </a>
            <a href="<?= BASE_URL ?>/adminkaishop/docs-api" class="nav-item active" data-page="docs-api">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="16 18 22 12 16 6" />
                    <polyline points="8 6 2 12 8 18" />
                </svg>
                <span>Docs - API</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="admin-info">
                <span>
                    <?= htmlspecialchars($admin['username']) ?>
                </span>
            </div>
            <button id="logoutBtn" class="btn-logout" title="Đăng xuất">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                    <polyline points="16 17 21 12 16 7" />
                    <line x1="21" y1="12" x2="9" y2="12" />
                </svg>
            </button>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <div class="docs-container">
            <header class="docs-header">
                <h1>Tài liệu API</h1>
                <p>Hướng dẫn đầy đủ về các API của KaiMail - Dịch vụ Email tạm thời</p>
            </header>

            <div class="docs-content">
                <div class="alert-box info">
                    <strong>Base URL:</strong> <code><?= BASE_URL ?></code>
                </div>

                <h2>Xác thực (Authentication)</h2>
                <p>Các endpoint dành cho admin yêu cầu xác thực phiên làm việc (session). Admin phải đăng nhập qua
                    <code>/adminkaishop/login</code>
                </p>

                <!-- User Email API -->
                <h2>User Email API</h2>
                <p>Các endpoint công khai để kiểm tra và lấy thông tin email.</p>

                <div class="endpoint-card">
                    <div class="endpoint-header">
                        <span class="method-badge get">GET</span>
                        <span class="endpoint-url">/api/emails</span>
                    </div>
                    <p class="endpoint-description">Kiểm tra xem email có tồn tại trong hệ thống hay không</p>

                    <h4 style="color: var(--text-secondary); font-size: 0.875rem; margin-top: 12px;">Tham số
                        (Parameters)</h4>
                    <table class="param-table">
                        <thead>
                            <tr>
                                <th>Tên</th>
                                <th>Kiểu</th>
                                <th>Bắt buộc</th>
                                <th>Mô tả</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>email</code></td>
                                <td>string</td>
                                <td>Có</td>
                                <td>Địa chỉ email cần kiểm tra</td>
                            </tr>
                            <tr>
                                <td><code>action</code></td>
                                <td>string</td>
                                <td>Không</td>
                                <td>Loại hành động: "check" hoặc "get" (mặc định: "check")</td>
                            </tr>
                        </tbody>
                    </table>

                    <h4 style="color: var(--text-secondary); font-size: 0.875rem; margin-top: 16px;">Ví dụ Response
                        (Phản hồi)
                    </h4>
                    <pre><code>{
  "exists": true,
  "expired": false,
  "email": "user@kaishop.id.vn",
  "created_at": "2026-01-27 15:00:00"
}</code></pre>
                </div>

                <div class="endpoint-card">
                    <div class="endpoint-header">
                        <span class="method-badge get">GET</span>
                        <span class="endpoint-url">/api/messages</span>
                    </div>
                    <p class="endpoint-description">Lấy danh sách tin nhắn cho một email cụ thể</p>

                    <h4 style="color: var(--text-secondary); font-size: 0.875rem; margin-top: 12px;">Tham số
                        (Parameters)</h4>
                    <table class="param-table">
                        <thead>
                            <tr>
                                <th>Tên</th>
                                <th>Kiểu</th>
                                <th>Bắt buộc</th>
                                <th>Mô tả</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>email</code></td>
                                <td>string</td>
                                <td>Có</td>
                                <td>Địa chỉ email</td>
                            </tr>
                        </tbody>
                    </table>

                    <h4 style="color: var(--text-secondary); font-size: 0.875rem; margin-top: 16px;">Ví dụ Response
                        (Phản hồi)
                    </h4>
                    <pre><code>{
  "email": "user@kaishop.id.vn",
  "messages": [
    {
      "id": 1,
      "from_email": "sender@example.com",
      "from_name": "Sender Name",
      "subject": "Chào mừng!",
      "body_text": "Chào mừng bạn đến với dịch vụ của chúng tôi",
      "body_html": "&lt;p&gt;Chào mừng bạn đến với dịch vụ của chúng tôi&lt;/p&gt;",
      "received_at": "2026-01-27 15:05:00",
      "is_read": false
    }
  ]
}</code></pre>
                </div>

                <!-- Admin Email API -->
                <h2>Admin Email API</h2>
                <p>Các endpoint chỉ dành cho Admin để quản lý email. Yêu cầu xác thực.</p>

                <div class="endpoint-card">
                    <div class="endpoint-header">
                        <span class="method-badge get">GET</span>
                        <span class="endpoint-url">/api/admin/emails</span>
                    </div>
                    <p class="endpoint-description">Danh sách tất cả email với phân trang và bộ lọc</p>

                    <h4 style="color: var(--text-secondary); font-size: 0.875rem; margin-top: 12px;">Tham số
                        (Parameters)</h4>
                    <table class="param-table">
                        <thead>
                            <tr>
                                <th>Tên</th>
                                <th>Kiểu</th>
                                <th>Mặc định</th>
                                <th>Mô tả</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>filter</code></td>
                                <td>string</td>
                                <td>"active"</td>
                                <td>Bộ lọc: "active" (đang dùng), "expired" (hết hạn), "all" (tất cả)</td>
                            </tr>
                            <tr>
                                <td><code>expiry</code></td>
                                <td>string</td>
                                <td>""</td>
                                <td>Loại thời hạn: "30days", "1year", "2years", "forever"</td>
                            </tr>
                            <tr>
                                <td><code>search</code></td>
                                <td>string</td>
                                <td>""</td>
                                <td>Tìm kiếm địa chỉ email</td>
                            </tr>
                            <tr>
                                <td><code>page</code></td>
                                <td>integer</td>
                                <td>1</td>
                                <td>Số trang</td>
                            </tr>
                            <tr>
                                <td><code>limit</code></td>
                                <td>integer</td>
                                <td>20</td>
                                <td>Số mục mỗi trang (tối đa: 100)</td>
                            </tr>
                        </tbody>
                    </table>

                    <h4 style="color: var(--text-secondary); font-size: 0.875rem; margin-top: 16px;">Ví dụ Response
                        (Phản hồi)
                    </h4>
                    <pre><code>{
  "total": 150,
  "page": 1,
  "limit": 20,
  "pages": 8,
  "emails": [...]
}</code></pre>
                </div>

                <div class="endpoint-card">
                    <div class="endpoint-header">
                        <span class="method-badge post">POST</span>
                        <span class="endpoint-url">/api/admin/emails</span>
                    </div>
                    <p class="endpoint-description">Tạo email mới (một hoặc nhiều)</p>

                    <h4 style="color: var(--text-secondary); font-size: 0.875rem; margin-top: 12px;">Dữ liệu gửi lên
                        (Request Body)</h4>
                    <table class="param-table">
                        <thead>
                            <tr>
                                <th>Trường</th>
                                <th>Kiểu</th>
                                <th>Bắt buộc</th>
                                <th>Mô tả</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>name_type</code></td>
                                <td>string</td>
                                <td>Không</td>
                                <td>"vn", "en", hoặc "custom" (mặc định: "vn")</td>
                            </tr>
                            <tr>
                                <td><code>email</code></td>
                                <td>string</td>
                                <td>Tùy điều kiện</td>
                                <td>Tên email tùy chỉnh (bắt buộc nếu name_type=custom)</td>
                            </tr>
                            <tr>
                                <td><code>domain</code></td>
                                <td>string</td>
                                <td>Không</td>
                                <td>Tên miền (mặc định theo cấu hình hệ thống)</td>
                            </tr>
                            <tr>
                                <td><code>expiry_type</code></td>
                                <td>string</td>
                                <td>Không</td>
                                <td>"30days", "1year", "2years", "forever" (mặc định: "forever")</td>
                            </tr>
                            <tr>
                                <td><code>quantity</code></td>
                                <td>integer</td>
                                <td>Không</td>
                                <td>Số lượng email cần tạo (1-10, mặc định: 1)</td>
                            </tr>
                        </tbody>
                    </table>

                    <h4 style="color: var(--text-secondary); font-size: 0.875rem; margin-top: 16px;">Ví dụ Request
                    </h4>
                    <pre><code>{
  "name_type": "vn",
  "domain": "kaishop.id.vn",
  "expiry_type": "forever",
  "quantity": 1
}</code></pre>

                    <h4 style="color: var(--text-secondary); font-size: 0.875rem; margin-top: 16px;">Ví dụ Response
                        (Phản hồi)
                    </h4>
                    <pre><code>{
  "success": true,
  "created": 1,
  "emails": [
    {
      "id": 123,
      "email": "nguyenvana@kaishop.id.vn",
      "name_type": "vn",
      "expiry_type": "forever",
      "expires_at": null
    }
  ],
  "errors": []
}</code></pre>
                </div>

                <div class="endpoint-card">
                    <div class="endpoint-header">
                        <span class="method-badge delete">DELETE</span>
                        <span class="endpoint-url">/api/admin/emails</span>
                    </div>
                    <p class="endpoint-description">Xóa một hoặc nhiều email</p>

                    <h4 style="color: var(--text-secondary); font-size: 0.875rem; margin-top: 12px;">Dữ liệu gửi lên
                        (Request Body)</h4>
                    <table class="param-table">
                        <thead>
                            <tr>
                                <th>Trường</th>
                                <th>Kiểu</th>
                                <th>Mô tả</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>_method</code></td>
                                <td>string</td>
                                <td>Phải là "DELETE"</td>
                            </tr>
                            <tr>
                                <td><code>ids</code></td>
                                <td>array</td>
                                <td>Mảng các ID email cần xóa</td>
                            </tr>
                        </tbody>
                    </table>

                    <h4 style="color: var(--text-secondary); font-size: 0.875rem; margin-top: 16px;">Ví dụ Request
                    </h4>
                    <pre><code>{
  "_method": "DELETE",
  "ids": [1, 2, 3]
}</code></pre>
                </div>

                <!-- Admin Stats API -->
                <h2>Admin Stats API</h2>

                <div class="endpoint-card">
                    <div class="endpoint-header">
                        <span class="method-badge get">GET</span>
                        <span class="endpoint-url">/api/admin/stats</span>
                    </div>
                    <p class="endpoint-description">Lấy thống kê hệ thống</p>

                    <h4 style="color: var(--text-secondary); font-size: 0.875rem; margin-top: 16px;">Ví dụ Response
                        (Phản hồi)
                    </h4>
                    <pre><code>{
  "total_emails": 1525,
  "active_emails": 1450,
  "expired_emails": 75,
  "total_messages": 3240
}</code></pre>
                </div>

                <!-- Admin Messages API -->
                <h2>Admin Messages API</h2>

                <div class="endpoint-card">
                    <div class="endpoint-header">
                        <span class="method-badge get">GET</span>
                        <span class="endpoint-url">/api/admin/messages</span>
                    </div>
                    <p class="endpoint-description">Lấy tin nhắn cho một email cụ thể (theo email_id) hoặc lấy một tin
                        nhắn duy nhất (theo id)</p>

                    <h4 style="color: var(--text-secondary); font-size: 0.875rem; margin-top: 12px;">Tham số
                        (Parameters)</h4>
                    <table class="param-table">
                        <thead>
                            <tr>
                                <th>Tên</th>
                                <th>Kiểu</th>
                                <th>Mô tả</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>email_id</code></td>
                                <td>integer</td>
                                <td>Lấy tất cả tin nhắn cho ID email này</td>
                            </tr>
                            <tr>
                                <td><code>id</code></td>
                                <td>integer</td>
                                <td>Lấy một tin nhắn cụ thể theo ID</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="endpoint-card">
                    <div class="endpoint-header">
                        <span class="method-badge delete">DELETE</span>
                        <span class="endpoint-url">/api/admin/messages</span>
                    </div>
                    <p class="endpoint-description">Xóa một hoặc nhiều tin nhắn</p>

                    <h4 style="color: var(--text-secondary); font-size: 0.875rem; margin-top: 12px;">Dữ liệu gửi lên
                        (Request Body)</h4>
                    <pre><code>{
  "_method": "DELETE",
  "ids": [1, 2, 3]
}</code></pre>
                </div>

                <h2>Mã lỗi (Error Codes)</h2>
                <table class="param-table">
                    <thead>
                        <tr>
                            <th>Mã</th>
                            <th>Ý nghĩa</th>
                            <th>Mô tả</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>200</code></td>
                            <td>OK</td>
                            <td>Yêu cầu thành công</td>
                        </tr>
                        <tr>
                            <td><code>201</code></td>
                            <td>Created</td>
                            <td>Tạo tài nguyên thành công</td>
                        </tr>
                        <tr>
                            <td><code>400</code></td>
                            <td>Bad Request</td>
                            <td>Tham số yêu cầu không hợp lệ</td>
                        </tr>
                        <tr>
                            <td><code>401</code></td>
                            <td>Unauthorized</td>
                            <td>Yêu cầu xác thực Admin</td>
                        </tr>
                        <tr>
                            <td><code>404</code></td>
                            <td>Not Found</td>
                            <td>Không tìm thấy tài nguyên</td>
                        </tr>
                        <tr>
                            <td><code>410</code></td>
                            <td>Gone</td>
                            <td>Email đã hết hạn</td>
                        </tr>
                        <tr>
                            <td><code>500</code></td>
                            <td>Internal Server Error</td>
                            <td>Lỗi máy chủ nội bộ</td>
                        </tr>
                    </tbody>
                </table>

                <div class="alert-box success" style="margin-top: 32px;">
                    <strong>Đã sẵn sàng!</strong> Tất cả các API hiện đang hoạt động và đã được lập tài liệu. Sử dụng
                    các endpoint này để tích hợp KaiMail vào ứng dụng của bạn.
                </div>
            </div>
        </div>
    </main>

    <script src="<?= BASE_URL ?>/js/admin.js"></script>
</body>

</html>