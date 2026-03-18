<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/AdminLayout.php';
require_once __DIR__ . '/../config/database.php';

$admin = ['username' => 'admin'];

$domains = [];
try {
    $db = getDB();
    $stmt = $db->query("
        SELECT d.id, d.domain, d.is_active, d.created_at, COUNT(e.id) AS email_count
        FROM domains d
        LEFT JOIN emails e ON e.email LIKE CONCAT('%@', d.domain)
        GROUP BY d.id, d.domain, d.is_active, d.created_at
        ORDER BY d.is_active DESC, d.domain ASC
    ");
    $domains = $stmt->fetchAll();
} catch (Exception $e) {
    error_log('Docs domain: load failed - ' . $e->getMessage());
}

AdminLayout::begin('Hướng dẫn domain', 'docs-domain', (string) ($admin['username'] ?? 'admin'));
?>
<div class="docs-container">
    <header class="docs-header">
        <h1>Hướng dẫn quản lý domain</h1>
        <p>Thiết lập domain nhận email cho KaiMail đúng cách, dễ kiểm soát và an toàn.</p>
    </header>

    <div class="docs-content">
        <section class="docs-grid">
            <article class="info-card">
                <h3>Mục tiêu</h3>
                <p>Thêm domain, cấu hình MX và xác thực DNS để email đi vào hệ thống ổn định.</p>
            </article>
            <article class="info-card">
                <h3>Lưu ý nhanh</h3>
                <p>Domain chỉ nên bật trạng thái hoạt động khi đã trỏ DNS xong.</p>
            </article>
        </section>

        <section>
            <h2>Danh sách domain hiện có</h2>
            <table class="param-table">
                <thead>
                    <tr>
                        <th>Domain</th>
                        <th>Trạng thái</th>
                        <th>Số email</th>
                        <th>Ngày tạo</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($domains)): ?>
                        <tr>
                            <td colspan="5">Chưa có domain nào.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($domains as $domain): ?>
                            <tr>
                                <td><code><?= htmlspecialchars((string) $domain['domain'], ENT_QUOTES, 'UTF-8') ?></code></td>
                                <td>
                                    <?php if ((int) $domain['is_active'] === 1): ?>
                                        <span class="status-chip active">Hoạt động</span>
                                    <?php else: ?>
                                        <span class="status-chip inactive">Tạm tắt</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= (int) $domain['email_count'] ?></td>
                                <td><?= htmlspecialchars((string) $domain['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <button type="button" class="btn danger btn-sm"
                                        data-domain-delete="<?= (int) $domain['id'] ?>"
                                        data-domain-name="<?= htmlspecialchars((string) $domain['domain'], ENT_QUOTES, 'UTF-8') ?>">
                                        Xóa
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <p class="field-note">Nếu domain đã có email, API sẽ chặn xóa để tránh mất dữ liệu.</p>
        </section>

        <section style="margin-top: 20px;">
            <h2>Quy trình cấu hình chuẩn (2 Bước)</h2>
            <p style="margin-bottom: 20px; color: var(--color-text-secondary);">Vì worker của bạn đã được thiết kế
                Universal, bạn chỉ cần cấu hình DNS trên Cloudflare và thêm vào Admin là xong.</p>

            <article class="step-card">
                <h3><span class="step-number">1</span> Cấu hình trên Cloudflare</h3>
                <p>Chọn domain <code>maiyeuem.indevs.in</code> trong Cloudflare Dashboard, sau đó thực hiện:</p>
                <ul style="padding-left: 20px; margin-top: 10px; color: var(--color-text-secondary); line-height: 1.6;">
                    <li>Vào <strong>Email</strong> -> <strong>Email Routing</strong>.</li>
                    <li>Tại tab <strong>Settings</strong>: Nhấn <strong>Enable Email Routing</strong> và
                        <strong>Configure</strong> để tự động thêm bản ghi DNS (MX/TXT).
                    </li>
                    <li>Tại tab <strong>Routing rules</strong> -> <strong>Catch-all address</strong>: Nhấn
                        <strong>Edit</strong>.
                    </li>
                    <li><strong>Action</strong>: Chọn <code>Send to a Worker</code>.</li>
                    <li><strong>Destination</strong>: Chọn worker <code>kaishop</code> (URL:
                        <code>https://kaishop.phucngx0710it.workers.dev/</code>).
                    </li>
                    <li><strong>Status</strong>: Gạt sang <strong>Active</strong> và nhấn <strong>Save</strong>.</li>
                </ul>
            </article>

            <article class="step-card">
                <h3><span class="step-number">2</span> Thêm domain vào KaiMail Admin</h3>
                <p>Khai báo domain để hệ thống bắt đầu chấp nhận email:</p>
                <div class="code-box" style="margin: 10px 0;">
                    Trang quản lý: <?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>/adminkaishop
                    Nút: "Thêm domain" (Góc phải trên cùng)
                    Tên miền: maiyeuem.indevs.in
                    Trạng thái: Hoạt động</div>
            </article>

            <article class="step-card">
                <h3><span class="step-number">3</span> Kiểm tra và Thuận tiện</h3>
                <p>Sau khi thiết lập xong, hãy thử tạo một địa chỉ và gửi mail kiểm tra:</p>
                <div class="code-box">curl
                    "<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>/api/emails.php?email=test@maiyeuem.indevs.in"
                </div>
                <p style="margin-top: 10px; font-size: 0.9em; color: var(--color-text-secondary);">* Lưu ý: Nếu mail
                    không vào, hãy kiểm tra lại trạng thái bản ghi MX trên Cloudflare (thường mất 1-5 phút để nhận
                    diện).</p>
            </article>

            <article class="step-card" style="border-left: 4px solid var(--color-warning);">
                <h3><span class="step-number" style="background: var(--color-warning); color: #fff;">!</span> Sử dụng
                    domain từ tài khoản Cloudflare khác?</h3>
                <p>Cloudflare không cho phép chọn Worker giữa các tài khoản khác nhau. Cách xử lý:</p>
                <ul
                    style="padding-left: 20px; margin-top: 10px; color: var(--color-text-secondary); font-size: 0.9em; line-height: 1.6;">
                    <li><strong>Tại Tài khoản B:</strong> Tạo 1 Worker mới (ví dụ: <code>v-bridge</code>).</li>
                    <li>Copy toàn bộ code từ <code>cloudflare-worker.js</code> dán vào đó.</li>
                    <li>Cài đặt <strong>Environment Variables</strong> (<code>WEBHOOK_URL</code>,
                        <code>WEBHOOK_SECRET</code>) giống hệt Tài khoản A.</li>
                    <li>Trong <strong>Email Routing</strong> (Tài khoản B), trỏ Catch-all về Worker vừa tạo này.</li>
                </ul>
            </article>
        </section>

        <section style="margin-top: 20px;">
            <div class="hint-box warning">
                Nên đợi tối đa 24-48 giờ sau khi đổi DNS trước khi kết luận cấu hình lỗi.
            </div>
        </section>
    </div>
</div>
<?php
AdminLayout::end();
