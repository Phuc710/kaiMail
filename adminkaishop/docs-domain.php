<?php
/**
 * Domain Management Documentation
 * KaiMail Admin - Domain Configuration Guide
 */

require_once __DIR__ . '/../includes/Auth.php';
Auth::requireLogin();

$admin = Auth::getAdmin();

// Load active domains from database
try {
    require_once __DIR__ . '/../config/database.php';
    $db = getDB();
    $stmt = $db->query("SELECT domain, is_active, created_at FROM domains ORDER BY is_active DESC, domain ASC");
    $domains = $stmt->fetchAll();
} catch (Exception $e) {
    $domains = [];
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Domain Management - KaiMail Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/logo.svg">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/admin.css?v=<?= time() ?>">
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
            <a href="<?= BASE_URL ?>/adminkaishop/docs-domain" class="nav-item active" data-page="docs-domain">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                    <polyline points="14 2 14 8 20 8" />
                    <line x1="12" y1="18" x2="12" y2="12" />
                    <line x1="9" y1="15" x2="15" y2="15" />
                </svg>
                <span>Docs - Domain</span>
            </a>
            <a href="<?= BASE_URL ?>/adminkaishop/docs-api" class="nav-item" data-page="docs-api">
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
                <h1>Hướng dẫn Cấu hình Tên miền (Domain)</h1>
                <p>Quy trình chi tiết để thêm và kích hoạt tên miền mới cho hệ thống KaiMail</p>
            </header>

            <div class="docs-content">
                <div class="alert-box info">
                    <strong>Thông tin quan trọng:</strong> Hệ thống KaiMail nhận email thông qua Webhook. Bạn cần cấu
                    hình định tuyến email từ nhà cung cấp DNS (như Cloudflare) về địa chỉ webhook của hệ thống.
                </div>

                <h2>Quy trình 4 bước Thêm Tên miền mới</h2>

                <div class="setup-steps">
                    <!-- Step 1 -->
                    <h3>Bước 1: Khai báo Tên miền trong Hệ thống</h3>
                    <p>Đầu tiên, bạn cần thêm tên miền vào cơ sở dữ liệu để hệ thống nhận diện và chấp nhận email gửi
                        đến.</p>
                    <div class="action-box">
                        <p><strong>Cách thực hiện:</strong> Truy cập vào <strong>Quản lý Email</strong> -> Nhấn nút
                            <strong>Add Domain</strong> và nhập tên miền của bạn (ví dụ: <code>kaishop.id.vn</code>).
                        </p>
                        <p>Hoặc sử dụng câu lệnh SQL:</p>
                        <pre><code>INSERT INTO domains (domain, is_active) VALUES ('kaishop.id.vn', 1);</code></pre>
                    </div>

                    <!-- Step 2 -->
                    <h3>Bước 2: Cấu hình DNS (Bản ghi MX)</h3>
                    <p>Bản ghi MX (Mail Exchange) cho máy chủ email biết nơi gửi email. Nếu bạn sử dụng Cloudflare Email
                        Routing, hãy cấu hình như sau:</p>

                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Loại</th>
                                    <th>Cấu hình (Host)</th>
                                    <th>Giá trị (Value)</th>
                                    <th>Độ ưu tiên (Priority)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>MX</code></td>
                                    <td><code>@</code></td>
                                    <td><code>route1.mx.cloudflare.net</code></td>
                                    <td><code>10</code></td>
                                </tr>
                                <tr>
                                    <td><code>MX</code></td>
                                    <td><code>@</code></td>
                                    <td><code>route2.mx.cloudflare.net</code></td>
                                    <td><code>20</code></td>
                                </tr>
                                <tr>
                                    <td><code>MX</code></td>
                                    <td><code>@</code></td>
                                    <td><code>route3.mx.cloudflare.net</code></td>
                                    <td><code>30</code></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="alert-box warning">
                        <strong>Lưu ý:</strong> Sau khi cập nhật DNS, có thể mất từ vài phút đến 24 giờ để các thay đổi
                        có hiệu lực trên toàn cầu.
                    </div>

                    <!-- Step 3 -->
                    <h3>Bước 3: Cấu hình Định tuyến Webhook</h3>
                    <p>Sau khi email đã được trỏ về máy chủ (ví dụ Cloudflare), bạn cần thiết lập quy tắc chuyển tiếp
                        email đó về Webhook của KaiMail.</p>

                    <div class="action-box">
                        <p><strong>Webhook URL của bạn:</strong></p>
                        <pre><code><?= BASE_URL ?>/api/webhook/receive-email.php</code></pre>
                        <p><strong>Trong Cloudflare Email Routing:</strong></p>
                        <ol>
                            <li>Chọn <strong>Email</strong> -> <strong>Email Routing</strong> -> <strong>Routing
                                    Rules</strong>.</li>
                            <li>Chọn <strong>Catch-all address</strong> hoặc tạo quy tắc cụ thể.</li>
                            <li>Phần <strong>Action</strong>: Chọn "Send to worker" hoặc "Forward to webhook" (tùy thuộc
                                vào cấu hình của bạn).</li>
                        </ol>
                    </div>

                    <!-- Step 4 -->
                    <h3>Bước 4: Kiểm tra và Xác minh</h3>
                    <p>Hãy thực hiện một bài kiểm tra thực tế để đảm bảo mọi thứ hoạt động trơn tru:</p>
                    <ol>
                        <li>Vào trang chủ KaiMail, nhập một địa chỉ email bất kỳ với tên miền mới (ví dụ:
                            <code>test@kaishop.id.vn</code>).</li>
                        <li>Sử dụng một email khác (Gmail, Outlook...) gửi thử một thư đến địa chỉ đó.</li>
                        <li>Chờ vài giây và nhấn <strong>Refresh</strong> trên hệ thống KaiMail để kiểm tra thư đến.
                        </li>
                    </ol>
                </div>

                <h2>Yêu cầu Kỹ thuật & Bảo mật</h2>
                <ul>
                    <li><strong>SSL/TLS:</strong> Luôn sử dụng HTTPS cho URL Webhook để đảm bảo an toàn dữ liệu.</li>
                    <li><strong>SPF/DKIM:</strong> Nên cấu hình thêm các bản ghi SPF và DKIM để tăng độ tin cậy cho tên
                        miền khi cần gửi phản hồi (nếu có).</li>
                    <li><strong>Lọc Spam:</strong> Hệ thống sẽ tự động lọc các email không thuộc các tên miền đã đăng ký
                        trong Bước 1.</li>
                </ul>

                <div class="alert-box success">
                    <strong>Mẹo:</strong> Bạn có thể thêm không giới hạn số lượng tên miền để đa dạng hóa lựa chọn cho
                    người dùng.
                </div>
            </div>
        </div>
    </main>

    <script src="<?= BASE_URL ?>/js/admin.js"></script>
</body>

</html>