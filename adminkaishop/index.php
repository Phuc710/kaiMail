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
                <path d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"></path>
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

    <select id="expiryFilter" class="select-filter">
        <option value="">Tất cả email</option>
        <option value="no_message">Không có tin nhắn</option>
    </select>

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
                    <input type="text" id="customEmail" placeholder="vi-du: support123" pattern="[a-z0-9]+">
                    <p class="field-note">Chỉ dùng chữ thường và số.</p>
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
                    <a href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>/adminkaishop/docs-domain" style="text-decoration: underline;">
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
<?php
AdminLayout::end(['/js/admin-dashboard.js']);
