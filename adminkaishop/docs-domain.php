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
                                    <button
                                        type="button"
                                        class="btn danger btn-sm"
                                        data-domain-delete="<?= (int) $domain['id'] ?>"
                                        data-domain-name="<?= htmlspecialchars((string) $domain['domain'], ENT_QUOTES, 'UTF-8') ?>"
                                    >
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
            <h2>Quy trình cấu hình chuẩn</h2>

            <article class="step-card">
                <h3><span class="step-number">1</span> Thêm domain vào admin</h3>
                <p>Tại trang quản lý email, nhấn <strong>Thêm domain</strong> và nhập domain dạng <code>example.com</code>.</p>
            </article>

            <article class="step-card">
                <h3><span class="step-number">2</span> Cấu hình bản ghi MX</h3>
                <p>Trỏ MX của domain về hệ thống nhận mail (Cloudflare Worker hoặc dịch vụ tiếp nhận bạn dùng).</p>
                <div class="code-box">Tên bản ghi: @
Loại: MX
Priority: 10
Giá trị: your-worker.your-account.workers.dev</div>
            </article>

            <article class="step-card">
                <h3><span class="step-number">3</span> Cấu hình SPF (khuyến nghị)</h3>
                <p>SPF giúp giảm rủi ro giả mạo sender và cải thiện độ tin cậy khi gửi.</p>
                <div class="code-box">Loại: TXT
Tên: @
Giá trị: v=spf1 include:_spf.google.com ~all</div>
            </article>

            <article class="step-card">
                <h3><span class="step-number">4</span> Kiểm tra hoạt động</h3>
                <p>Gửi email thử đến địa chỉ trong domain mới, sau đó kiểm tra trong trang admin.</p>
                <div class="code-box">curl "<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>/api/emails.php?email=test@yourdomain.com"</div>
            </article>

            <article class="step-card">
                <h3><span class="step-number">5</span> Giám sát và xử lý lỗi</h3>
                <ul style="padding-left: 20px; color: var(--color-text-secondary);">
                    <li>Email không vào: kiểm tra MX và thời gian propagation DNS.</li>
                    <li>Ký tự lỗi: kiểm tra worker có chuyển tiếp raw MIME đúng không.</li>
                    <li>Vào spam: rà soát SPF/DKIM/DMARC.</li>
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
