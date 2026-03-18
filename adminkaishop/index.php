<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/AdminLayout.php';
require_once __DIR__ . '/../config/database.php';

$admin = ['username' => 'admin'];

$domains = [];
try {
    $db = getDB();
    $stmt = $db->query("SELECT domain FROM domains WHERE is_active = 1 ORDER BY domain ASC");
    $domains = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    error_log('Admin index: load domains failed - ' . $e->getMessage());
}

AdminLayout::begin('Quản lý email', 'emails', (string) ($admin['username'] ?? 'admin'));
?>
<header class="page-header">
    <div>
        <h1>Quản lý email</h1>
        <p>Theo dõi, tạo mới và xử lý email trong hệ thống KaiMail.</p>
    </div>
    <div class="page-actions">
        <button id="fastCheckerBtn" class="btn" style="background: #8b5cf6; color: white;" type="button"
            data-modal-open="checkerModal">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
            </svg>
            <span>Fast Checker</span>
        </button>
        <button id="addDomainBtn" class="btn secondary" type="button" data-modal-open="addDomainModal">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="16"></line>
                <line x1="8" y1="12" x2="16" y2="12"></line>
            </svg>
            <span>Thêm domain</span>
        </button>
        <button id="createEmailBtn" class="btn primary" type="button" data-modal-open="createModal">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            <span>Tạo email</span>
        </button>
    </div>
</header>

<section class="stats-grid">
    <article class="stat-card">
        <div class="stat-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                <polyline points="22,6 12,13 2,6"></polyline>
            </svg>
        </div>
        <div class="stat-info">
            <span class="stat-value" id="statTotalEmails">0</span>
            <span class="stat-label">Tổng email</span>
        </div>
    </article>

    <article class="stat-card">
        <div class="stat-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="9 11 12 14 22 4"></polyline>
                <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
            </svg>
        </div>
        <div class="stat-info">
            <span class="stat-value" id="statActiveEmails">0</span>
            <span class="stat-label">Email hoạt động</span>
        </div>
    </article>

    <article class="stat-card">
        <div class="stat-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="22 12 16 12 14 15 10 15 8 12 2 12"></polyline>
                <path
                    d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z">
                </path>
            </svg>
        </div>
        <div class="stat-info">
            <span class="stat-value" id="statTotalMessages">0</span>
            <span class="stat-label">Tổng tin nhắn</span>
        </div>
    </article>

</section>

<section class="filters">
    <div class="search-box">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
        <input type="text" id="searchInput" placeholder="Tìm email...">
    </div>

    <div class="filter-group">
        <select id="statusFilter" class="select-filter">
            <option value="all">Tất cả trạng thái</option>
        </select>

        <select id="domainFilter" class="select-filter">
            <option value="">Tất cả tên miền</option>
            <?php foreach ($domains as $domain): ?>
                <option value="<?= htmlspecialchars((string) $domain, ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars((string) $domain, ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select id="expiryFilter" class="select-filter">
            <option value="">Tất cả tiêu chí</option>
            <option value="no_message">Không có tin nhắn</option>
        </select>
    </div>

    <button id="deleteSelectedBtn" class="btn danger hidden" type="button">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="3 6 5 6 21 6"></polyline>
            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
        </svg>
        <span>Xóa đã chọn</span>
    </button>
</section>

<section class="table-container">
    <table class="data-table">
        <thead>
            <tr>
                <th class="col-check">
                    <input type="checkbox" id="selectAll">
                </th>
                <th>Email</th>
                <th>Done</th>
                <th>Tin nhắn</th>
                <th>Tạo lúc</th>
                <th class="col-actions">Thao tác</th>
            </tr>
        </thead>
        <tbody id="emailsTableBody"></tbody>
    </table>
</section>

<section class="pagination" id="pagination"></section>

<div id="loadingState" class="loading-overlay hidden">
    <div class="spinner"></div>
</div>

<div id="createModal" class="modal hidden">
    <div class="modal-backdrop"></div>
    <div class="modal-content modal-sm">
        <div class="modal-header">
            <h2>Tạo email mới</h2>
            <button class="btn-close" type="button" data-modal-close="createModal">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <form id="createEmailForm">
                <div class="form-group">
                    <label>Kiểu tên email</label>
                    <div class="radio-group">
                        <label class="radio-item">
                            <input type="radio" name="name_type" value="vn" checked>
                            <span>Tên Việt Nam</span>
                        </label>
                        <label class="radio-item">
                            <input type="radio" name="name_type" value="en">
                            <span>Tên tiếng Anh</span>
                        </label>
                        <label class="radio-item">
                            <input type="radio" name="name_type" value="custom">
                            <span>Tự nhập</span>
                        </label>
                    </div>
                </div>

                <div class="form-group hidden" id="customEmailGroup">
                    <label for="customEmail">Tên email tùy chỉnh (không gồm @domain)</label>
                    <input type="text" id="customEmail" placeholder="vi-du: support123" pattern="[A-Za-z0-9\-\._]+">
                    <p class="field-note">Chỉ dùng chữ cái, số, dấu chấm, gạch ngang và gạch dưới.</p>
                </div>

                <div class="form-group" id="quantityGroup">
                    <label for="emailQuantity">Số lượng (Tối đa 50)</label>
                    <input type="number" id="emailQuantity" name="quantity" min="1" max="50" value="1"
                        class="custom-number-input">
                </div>

                <div class="form-group">
                    <label for="domainSelect">Domain</label>
                    <select id="domainSelect" class="select-filter" required>
                        <?php if (empty($domains)): ?>
                            <option value="">Chưa có domain hoạt động. Hãy thêm domain trước.</option>
                        <?php else: ?>
                            <?php foreach ($domains as $index => $domain): ?>
                                <option value="<?= htmlspecialchars((string) $domain, ENT_QUOTES, 'UTF-8') ?>" <?= $index === 0 ? 'selected' : '' ?>>
                                    <?= htmlspecialchars((string) $domain, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn secondary" data-modal-close="createModal">Hủy</button>
                    <button type="submit" class="btn primary">Tạo email</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="messagesModal" class="modal hidden">
    <div class="modal-backdrop"></div>
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h2 id="messagesModalTitle">Danh sách tin nhắn</h2>
            <button class="btn-close" type="button" data-modal-close="messagesModal">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
        <div class="modal-body" id="messagesModalBody"></div>
    </div>
</div>

<div id="viewMessageModal" class="modal hidden">
    <div class="modal-backdrop"></div>
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <div class="modal-title-info">
                <h2 id="viewMessageSubject"></h2>
                <p id="viewMessageFrom"></p>
            </div>
            <button class="btn-close" type="button" data-modal-close="viewMessageModal">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
        <div class="modal-body email-body" id="viewMessageBody"></div>
    </div>
</div>

<div id="addDomainModal" class="modal hidden">
    <div class="modal-backdrop"></div>
    <div class="modal-content modal-sm">
        <div class="modal-header">
            <h2>Thêm domain mới</h2>
            <button class="btn-close" type="button" data-modal-close="addDomainModal">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <form id="addDomainForm">
                <div class="form-group">
                    <label for="domainName">Tên domain</label>
                    <input type="text" id="domainName" placeholder="example.com" pattern="[a-z0-9.-]+" required>
                    <p class="field-note">Không nhập tiền tố `http://` hoặc `www`.</p>
                </div>

                <div class="form-group">
                    <label>Trạng thái</label>
                    <div class="radio-group">
                        <label class="radio-item">
                            <input type="radio" name="domain_status" value="1" checked>
                            <span>Hoạt động</span>
                        </label>
                        <label class="radio-item">
                            <input type="radio" name="domain_status" value="0">
                            <span>Tạm tắt</span>
                        </label>
                    </div>
                </div>

                <div class="hint-box">
                    Cần cấu hình DNS/MX trước khi dùng domain nhận mail.
                    <a href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>/adminkaishop/docs-domain"
                        style="text-decoration: underline;">
                        Xem hướng dẫn
                    </a>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn secondary" data-modal-close="addDomainModal">Hủy</button>
                    <button type="submit" class="btn primary">Thêm domain</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="checkerModal" class="modal hidden">
    <div class="modal-backdrop"></div>
    <div class="modal-content modal-lg">
        <div class="modal-header" style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
            <div>
                <h2 style="color: #4c1d95; font-size: 1.25rem; display: flex; align-items: center; gap: 0.5rem;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                    </svg>
                    Fast Email Checker
                </h2>
                <p style="color: #64748b; font-size: 0.85rem; margin-top: 0.25rem;">Quét hàng triệu email trong nháy mắt
                    (&lt; 1s)</p>
            </div>
            <button class="btn-close" type="button" data-modal-close="checkerModal">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <form id="checkerForm"
                style="display: grid; grid-template-columns: 1fr auto auto; gap: 1rem; align-items: end; margin-bottom: 1.5rem;">
                <div class="form-group" style="margin: 0;">
                    <label for="checkerKeyword">Từ khóa cần quét (VD: deactivating, openai)</label>
                    <input type="text" id="checkerKeyword" value="deactivating" required style="border-color: #8b5cf6;">
                </div>
                <div class="form-group" style="margin: 0;">
                    <label for="checkerDays">Số ngày</label>
                    <input type="number" id="checkerDays" value="7" min="1" max="30" style="width: 80px;">
                </div>
                <button type="submit" class="btn" style="background: #8b5cf6; color: white; height: 42px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <span>Quét ngay</span>
                </button>
            </form>

            <div id="checkerResults"
                style="min-height: 200px; max-height: 400px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 0.5rem; background: #f8fafc; padding: 1rem;">
                <div style="text-align: center; color: #94a3b8; padding: 2rem;">
                    Nhập từ khóa và click "Quét ngay" để bắt đầu
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .checker-result-item.active {
        border-color: #8b5cf6 !important;
        box-shadow: 0 4px 12px rgba(139, 92, 246, 0.1);
    }

    .checker-result-item.active .checker-detail {
        max-height: 1000px !important;
        border-top: 1px solid #e2e8f0 !important;
    }

    .checker-result-item.active .chevron {
        transform: rotate(180deg);
        stroke: #8b5cf6 !important;
    }

    .checker-result-item:hover {
        border-color: #c084fc !important;
        background: #fafafa !important;
    }

    .spinner-sm {
        width: 16px;
        height: 16px;
        border: 2px solid rgba(255, 255, 255, .3);
        border-radius: 50%;
        border-top-color: #fff;
        animation: spin 1s linear infinite;
    }
</style>
<?php
AdminLayout::end(['/js/admin-dashboard.js']);
