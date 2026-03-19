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
        <button id="fastCheckerBtn" class="btn" style="background: #0f172a; color: white;" type="button"
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
                <h2 style="color: #0f172a; font-size: 1.25rem; display: flex; align-items: center; gap: 0.5rem;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                    </svg>
                    Fast Email Checker
                </h2>
            </div>
            <button class="btn-close" type="button" data-modal-close="checkerModal">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <form id="checkerForm" class="checker-form-fancy">
                <div class="checker-input-group">
                    <label for="checkerKeyword">Từ khóa quét email</label>
                    <div class="checker-input-wrapper">
                        <svg class="checker-icon" width="16" height="16" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                        <input type="text" id="checkerKeyword" value="deactivating"
                            placeholder="VD: deactivating, openai..." required>
                    </div>
                </div>
                <div class="checker-input-group days-group">
                    <label for="checkerDays">Trong vòng (ngày)</label>
                    <div class="checker-input-wrapper">
                        <svg class="checker-icon" width="16" height="16" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        <input type="number" id="checkerDays" value="30" min="1" max="90">
                    </div>
                </div>
                <button type="submit" class="checker-btn-submit">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                    </svg>
                    <span>Quét ngay</span>
                </button>
            </form>

            <div id="checkerResults" class="checker-results-container">
                <div style="text-align: center; color: #94a3b8; padding: 2rem;">
                    Nhập từ khóa và click "Quét ngay" để bắt đầu
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .checker-form-fancy {
        display: flex;
        gap: 12px;
        align-items: flex-end;
        margin-bottom: 24px;
        background: #f8fafc;
        padding: 16px;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.02);
    }

    .checker-input-group {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .checker-input-group.days-group {
        flex: 0 0 140px;
    }

    .checker-input-group label {
        font-size: 0.8rem;
        font-weight: 700;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.025em;
        margin-left: 2px;
    }

    .checker-input-wrapper {
        position: relative;
        display: flex;
        align-items: center;
    }

    .checker-input-wrapper .checker-icon {
        position: absolute;
        left: 12px;
        color: #94a3b8;
        pointer-events: none;
        transition: color 0.2s ease;
    }

    .checker-input-wrapper input {
        width: 100%;
        height: 44px;
        padding: 8px 12px 8px 38px !important;
        border: 1.5px solid #e2e8f0 !important;
        border-radius: 10px !important;
        font-size: 0.95rem !important;
        background: white !important;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
        color: #1e293b !important;
        min-height: auto !important;
    }

    .checker-input-wrapper input:focus {
        border-color: #0f172a !important;
        box-shadow: 0 0 0 4px rgba(15, 23, 42, 0.08) !important;
        outline: none !important;
    }

    .checker-input-wrapper input:focus+.checker-icon,
    .checker-input-wrapper:focus-within .checker-icon {
        color: #0f172a;
    }

    .checker-btn-submit {
        height: 44px;
        padding: 0 24px;
        background: #0f172a;
        color: white;
        border: none;
        border-radius: 10px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 10px;
        cursor: pointer;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        font-size: 0.95rem;
        box-shadow: 0 4px 6px -1px rgba(15, 23, 42, 0.1), 0 2px 4px -1px rgba(15, 23, 42, 0.06);
    }

    .checker-btn-submit:hover {
        background: #1e293b;
        transform: translateY(-1px);
        box-shadow: 0 10px 15px -3px rgba(15, 23, 42, 0.15), 0 4px 6px -2px rgba(15, 23, 42, 0.1);
    }

    .checker-btn-submit:active {
        transform: translateY(0);
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    }

    .checker-btn-submit:disabled {
        opacity: 0.7;
        cursor: not-allowed;
        transform: none !important;
    }

    .checker-results-container {
        min-height: 200px;
        max-height: 450px;
        overflow-y: auto;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        background: #fafafa;
        padding: 16px;
        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.02);
    }

    .checker-results-header {
        margin-bottom: 1.25rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 2px solid #f1f5f9;
        padding-bottom: 0.75rem;
    }

    .results-count {
        font-weight: 800;
        color: #0f172a;
        font-size: 0.95rem;
        text-transform: uppercase;
        letter-spacing: 0.025em;
    }

    .execution-time {
        font-size: 0.75rem;
        color: #ffffff;
        background: #0f172a;
        padding: 4px 14px;
        border-radius: 999px;
        font-weight: 700;
        box-shadow: 0 4px 6px -1px rgba(15, 23, 42, 0.2);
    }

    .checker-result-item {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        overflow: hidden;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        margin-bottom: 10px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    }

    .checker-result-item.active {
        border-color: #0f172a !important;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
        transform: scale(1.005);
    }

    .checker-result-item.active .checker-detail {
        max-height: 1200px !important;
        border-top: 1px solid #f1f5f9 !important;
    }

    .checker-result-item.active .chevron {
        transform: rotate(180deg);
        stroke: #0f172a !important;
    }

    .checker-header-inner,
    .checker-result-header-inner {
        padding: 0.85rem 1rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        cursor: pointer;
        user-select: none;
    }

    .checker-result-info {
        flex: 1;
        min-width: 0;
    }

    .checker-result-email {
        font-weight: 800;
        color: #1e293b;
        margin-bottom: 0.25rem;
        font-size: 0.95rem;
        font-family: inherit;
    }

    .checker-result-subject {
        font-size: 0.85rem;
        color: #64748b;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        font-weight: 500;
    }

    .checker-result-meta {
        text-align: right;
        margin-left: 1.5rem;
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .checker-result-time {
        font-size: 0.8rem;
        color: #94a3b8;
        white-space: nowrap;
        font-weight: 500;
    }

    .checker-detail {
        max-height: 0;
        overflow: hidden;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        background: #f8fafc;
        border-top: 0px solid #e2e8f0;
    }

    .checker-result-item:hover:not(.active) {
        border-color: #cbd5e1;
        background: #fdfdfd;
        transform: translateY(-1px);
    }

    .spinner-sm {
        width: 18px;
        height: 18px;
        border: 2.5px solid rgba(255, 255, 255, .2);
        border-radius: 50%;
        border-top-color: #fff;
        animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }
</style>
<?php
AdminLayout::end(['/js/admin-dashboard.js']);
