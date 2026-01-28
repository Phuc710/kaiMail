<?php
/**
 * Admin Email Management
 * KaiMail - Temp Mail System
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
    <title>Quản lý Email - KaiMail Admin</title>
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
            <a href="<?= BASE_URL ?>/adminkaishop" class="nav-item active" data-page="emails">
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
        <header class="page-header">
            <div>
                <h1>Quản lý Email</h1>
                <p>Tạo và quản lý tất cả email trong hệ thống</p>
            </div>
            <div style="display: flex; gap: 12px;">
                <button id="addDomainBtn" class="btn secondary">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10" />
                        <line x1="12" y1="8" x2="12" y2="16" />
                        <line x1="8" y1="12" x2="16" y2="12" />
                    </svg>
                    <span>Add Domain</span>
                </button>
                <button id="createEmailBtn" class="btn primary">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19" />
                        <line x1="5" y1="12" x2="19" y2="12" />
                    </svg>
                    <span>Tạo Email</span>
                </button>
            </div>
        </header>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
                        <polyline points="22,6 12,13 2,6" />
                    </svg>
                </div>
                <div class="stat-info">
                    <span class="stat-value" id="statTotalEmails">-</span>
                    <span class="stat-label">Tổng Email</span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon green">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 11 12 14 22 4" />
                        <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" />
                    </svg>
                </div>
                <div class="stat-info">
                    <span class="stat-value" id="statActiveEmails">-</span>
                    <span class="stat-label">Email Active</span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon purple">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="22 12 16 12 14 15 10 15 8 12 2 12" />
                        <path
                            d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z" />
                    </svg>
                </div>
                <div class="stat-info">
                    <span class="stat-value" id="statTotalMessages">-</span>
                    <span class="stat-label">Tổng Messages</span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon orange">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10" />
                        <line x1="12" y1="8" x2="12" y2="12" />
                        <line x1="12" y1="16" x2="12.01" y2="16" />
                    </svg>
                </div>
                <div class="stat-info">
                    <span class="stat-value" id="statExpiredEmails">-</span>
                    <span class="stat-label">Email hết hạn</span>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters">
            <div class="search-box">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8" />
                    <line x1="21" y1="21" x2="16.65" y2="16.65" />
                </svg>
                <input type="text" id="searchInput" placeholder="Tìm kiếm email...">
            </div>

            <select id="expiryFilter" class="select-filter">
                <option value="">Tất cả thời hạn</option>
                <option value="30days">30 ngày</option>
                <option value="1year">1 năm</option>
                <option value="2years">2 năm</option>
                <option value="forever">Vĩnh viễn</option>
            </select>

            <button id="deleteSelectedBtn" class="btn danger hidden">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="3 6 5 6 21 6" />
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                </svg>
                <span>Xóa đã chọn</span>
            </button>
        </div>

        <!-- Email Table -->
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="col-check">
                            <input type="checkbox" id="selectAll">
                        </th>
                        <th>Email</th>
                        <th>Messages</th>
                        <th>Thời hạn</th>
                        <th>Hết hạn</th>
                        <th>Tạo lúc</th>
                        <th class="col-actions">Thao tác</th>
                    </tr>
                </thead>
                <tbody id="emailsTableBody">
                    <!-- Will be populated by JS -->
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="pagination" id="pagination">
            <!-- Will be populated by JS -->
        </div>

        <!-- Loading -->
        <div id="loadingState" class="loading-overlay hidden">
            <div class="spinner"></div>
        </div>
    </main>

    <!-- Create Email Modal -->
    <div id="createModal" class="modal hidden">
        <div class="modal-backdrop"></div>
        <div class="modal-content modal-sm">
            <div class="modal-header">
                <h2>Tạo Email mới</h2>
                <button class="btn-close" onclick="closeCreateModal()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18" />
                        <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <form id="createEmailForm">
                    <div class="form-group">
                        <label>Loại tên</label>
                        <div class="radio-group">
                            <label class="radio-item">
                                <input type="radio" name="name_type" value="vn" checked>
                                <span>Tên Việt Nam</span>
                            </label>
                            <label class="radio-item">
                                <input type="radio" name="name_type" value="en">
                                <span>Tên English</span>
                            </label>
                            <label class="radio-item">
                                <input type="radio" name="name_type" value="custom">
                                <span>Tự nhập</span>
                            </label>
                        </div>
                    </div>

                    <div class="form-group hidden" id="customEmailGroup">
                        <label for="customEmail">Email (không có @domain)</label>
                        <input type="text" id="customEmail" placeholder="vd: chatgpt123" pattern="[a-z0-9]+"
                            title="Chỉ chữ thường và số">
                    </div>

                    <div class="form-group">
                        <label for="domainSelect">Domain</label>
                        <select id="domainSelect" class="select-filter" required>
                            <?php
                            // Load domains from database
                            try {
                                require_once __DIR__ . '/../config/database.php';
                                $db = getDB();
                                $stmt = $db->query("SELECT domain FROM domains WHERE is_active = 1 ORDER BY domain ASC");
                                $domains = $stmt->fetchAll(PDO::FETCH_COLUMN);

                                if (empty($domains)) {
                                    echo '<option value="">⚠️ No active domains - Add one first!</option>';
                                } else {
                                    foreach ($domains as $index => $domain) {
                                        // Select first domain as default
                                        $selected = ($index === 0) ? 'selected' : '';
                                        echo '<option value="' . htmlspecialchars($domain) . '" ' . $selected . '>' . htmlspecialchars($domain) . '</option>';
                                    }
                                }
                            } catch (Exception $e) {
                                error_log('Error loading domains: ' . $e->getMessage());
                                echo '<option value="">Error loading domains</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Thời hạn</label>
                        <div class="radio-group">
                            <label class="radio-item">
                                <input type="radio" name="expiry_type" value="30days">
                                <span>30 ngày</span>
                            </label>
                            <label class="radio-item">
                                <input type="radio" name="expiry_type" value="1year">
                                <span>1 năm</span>
                            </label>
                            <label class="radio-item">
                                <input type="radio" name="expiry_type" value="2years">
                                <span>2 năm</span>
                            </label>
                            <label class="radio-item">
                                <input type="radio" name="expiry_type" value="forever" checked>
                                <span>Vĩnh viễn</span>
                            </label>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn secondary" onclick="closeCreateModal()">Hủy</button>
                        <button type="submit" class="btn primary">Tạo Email</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Messages Modal -->
    <div id="messagesModal" class="modal hidden">
        <div class="modal-backdrop"></div>
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h2 id="messagesModalTitle">Messages</h2>
                <button class="btn-close" onclick="closeMessagesModal()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18" />
                        <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                </button>
            </div>
            <div class="modal-body" id="messagesModalBody">
                <!-- Messages list -->
            </div>
        </div>
    </div>

    <!-- View Message Modal -->
    <div id="viewMessageModal" class="modal hidden">
        <div class="modal-backdrop"></div>
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <div class="modal-title-info">
                    <h2 id="viewMessageSubject"></h2>
                    <p id="viewMessageFrom"></p>
                </div>
                <button class="btn-close" onclick="closeViewMessageModal()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18" />
                        <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                </button>
            </div>
            <div class="modal-body email-body" id="viewMessageBody">
                <!-- Email content -->
            </div>
        </div>
    </div>

    <!-- Add Domain Modal -->
    <div id="addDomainModal" class="modal hidden">
        <div class="modal-backdrop"></div>
        <div class="modal-content modal-sm">
            <div class="modal-header">
                <h2>Add New Domain</h2>
                <button class="btn-close" onclick="closeAddDomainModal()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18" />
                        <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <form id="addDomainForm">
                    <div class="form-group">
                        <label for="domainName">Domain Name</label>
                        <input type="text" id="domainName" placeholder="example.com" pattern="[a-z0-9.-]+"
                            title="Only lowercase letters, numbers, dots, and hyphens allowed" required>
                        <p style="font-size: 0.8125rem; color: var(--text-muted); margin-top: 6px;">
                            Enter domain without http:// or www
                        </p>
                    </div>

                    <div class="form-group">
                        <label>Status</label>
                        <div class="radio-group">
                            <label class="radio-item">
                                <input type="radio" name="domain_status" value="1" checked>
                                <span>Active</span>
                            </label>
                            <label class="radio-item">
                                <input type="radio" name="domain_status" value="0">
                                <span>Inactive</span>
                            </label>
                        </div>
                    </div>

                    <div class="alert-box info" style="font-size: 0.875rem;">
                        <strong>Note:</strong> Make sure to configure DNS MX records before activating the domain.
                        <a href="<?= BASE_URL ?>/adminkaishop/docs-domain"
                            style="color: var(--accent); text-decoration: underline;">
                            See documentation →
                        </a>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn secondary" onclick="closeAddDomainModal()">Cancel</button>
                        <button type="submit" class="btn primary">Add Domain</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    </div>

    <script src="<?= BASE_URL ?>/js/admin.js"></script>
    <script>
        // ============================================
        // Time Formatting Utilities (GMT+7)
        // ============================================
        function parseToGMT7(dateStr) {
            const date = new Date(dateStr);
            const utc = date.getTime() + (date.getTimezoneOffset() * 60000);
            const gmt7 = new Date(utc + (3600000 * 7));
            return gmt7;
        }

        function getCurrentGMT7() {
            const now = new Date();
            const utc = now.getTime() + (now.getTimezoneOffset() * 60000);
            return new Date(utc + (3600000 * 7));
        }

        function formatTimeVN(dateStr) {
            const date = parseToGMT7(dateStr);
            const now = getCurrentGMT7();
            const diff = now - date;
            const minutes = Math.floor(diff / 60000);
            const hours = Math.floor(diff / 3600000);
            const days = Math.floor(diff / 86400000);

            if (minutes < 1) return 'Vừa xong';
            if (minutes < 60) return `${minutes} phút trước`;
            if (hours < 24) return `${hours} giờ trước`;
            if (days < 7) return `${days} ngày trước`;

            return formatDateVN(dateStr);
        }

        function formatDateVN(dateStr) {
            const date = parseToGMT7(dateStr);
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = date.getFullYear();
            return `${day}/${month}/${year}`;
        }

        function formatDateTimeVN(dateStr) {
            const date = parseToGMT7(dateStr);
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = date.getFullYear();
            const hours = String(date.getHours()).padStart(2, '0');
            const minutes = String(date.getMinutes()).padStart(2, '0');
            return `${day}/${month}/${year} ${hours}:${minutes}`;
        }

        function cleanEmail(email, name = null) {
            if (name && name.trim() && !name.includes('@')) {
                return name.trim();
            }
            if (!email) return '';
            if (email.includes('bounces+') && email.includes('=')) {
                const match = email.match(/([^=]+)=([^@]+)@/);
                if (match) {
                    return match[1] + '@' + match[2];
                }
            }
            if (email.includes('@em') || email.includes('@tm.') || email.includes('.openai.com')) {
                const username = email.split('@')[0];
                if (username.length > 30 || username.includes('+')) {
                    return username.split('+')[0] + '@...';
                }
                return username;
            }
            return email;
        }

        // ============================================
        // Emails page specific
        // ============================================
        let currentPage = 1;
        let selectedIds = [];

        document.addEventListener('DOMContentLoaded', () => {
            loadStats();
            loadEmails();

            // Search
            let searchTimeout;
            document.getElementById('searchInput').addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    currentPage = 1;
                    loadEmails();
                }, 300);
            });

            // Expiry filter
            document.getElementById('expiryFilter').addEventListener('change', () => {
                currentPage = 1;
                loadEmails();
            });

            // Select all
            document.getElementById('selectAll').addEventListener('change', (e) => {
                const checkboxes = document.querySelectorAll('.email-checkbox');
                checkboxes.forEach(cb => {
                    cb.checked = e.target.checked;
                    updateSelection(parseInt(cb.value), e.target.checked);
                });
            });

            // Delete selected
            document.getElementById('deleteSelectedBtn').addEventListener('click', deleteSelected);
        });

        async function loadStats() {
            try {
                const response = await fetch('<?= BASE_URL ?>/api/admin/stats.php');
                const data = await response.json();

                document.getElementById('statTotalEmails').textContent = data.total_emails || 0;
                document.getElementById('statActiveEmails').textContent = data.active_emails || 0;
                document.getElementById('statTotalMessages').textContent = data.total_messages || 0;
                document.getElementById('statExpiredEmails').textContent = data.expired_emails || 0;
            } catch (error) {
                console.error('Error loading stats:', error);
            }
        }

        async function loadEmails() {
            const search = document.getElementById('searchInput').value;
            const expiry = document.getElementById('expiryFilter').value;

            showLoading(true);

            try {
                const params = new URLSearchParams({
                    filter: 'active',
                    page: currentPage,
                    limit: 10
                });
                if (search) params.append('search', search);
                if (expiry) params.append('expiry', expiry);

                const response = await fetch(`<?= BASE_URL ?>/api/admin/emails.php?${params}`);
                const data = await response.json();

                renderEmails(data.emails);
                renderPagination(data);

            } catch (error) {
                console.error('Error loading emails:', error);
                showToast('Không thể tải danh sách email', 'error');
            } finally {
                showLoading(false);
            }
        }

        function renderEmails(emails) {
            const tbody = document.getElementById('emailsTableBody');

            if (!emails || emails.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="empty-row">Không có email nào</td>
                    </tr>
                `;
                return;
            }

            const expiryLabels = {
                '30days': '30 ngày',
                '1year': '1 năm',
                '2years': '2 năm',
                'forever': 'Vĩnh viễn'
            };

            tbody.innerHTML = emails.map(email => `
                <tr>
                    <td class="col-check">
                        <input type="checkbox" class="email-checkbox" value="${email.id}" 
                            ${selectedIds.includes(email.id) ? 'checked' : ''}
                            onchange="updateSelection(${email.id}, this.checked)">
                    </td>
                    <td>
                        <div class="email-cell">
                            <span class="email-address">${escapeHtml(email.email)}</span>
                            <button class="btn-icon" onclick="copyToClipboard('${email.email}')" title="Copy">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                                </svg>
                            </button>
                        </div>
                    </td>
                    <td>
                        <button class="btn-link" onclick="viewMessages(${email.id}, '${email.email}')">
                            ${email.message_count || 0} 
                            ${email.unread_count > 0 ? `<span class="unread-badge">${email.unread_count}</span>` : ''}
                        </button>
                    </td>
                    <td><span class="badge badge-${email.expiry_type}">${expiryLabels[email.expiry_type]}</span></td>
                    <td>${email.expires_at ? formatDate(email.expires_at) : '-'}</td>
                    <td>${formatDate(email.created_at)}</td>
                    <td class="col-actions">
                        <button class="btn-icon" onclick="viewMessages(${email.id}, '${email.email}')" title="Xem tin nhắn">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                        <button class="btn-icon danger" onclick="deleteEmail(${email.id})" title="Xóa">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="3 6 5 6 21 6"/>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                            </svg>
                        </button>
                    </td>
                </tr>
            `).join('');
        }

        function renderPagination(data) {
            const pagination = document.getElementById('pagination');
            if (data.pages <= 1) {
                pagination.innerHTML = '';
                return;
            }

            let html = '';

            if (currentPage > 1) {
                html += `<button onclick="goToPage(${currentPage - 1})">←</button>`;
            }

            for (let i = 1; i <= data.pages; i++) {
                if (i === 1 || i === data.pages || (i >= currentPage - 2 && i <= currentPage + 2)) {
                    html += `<button class="${i === currentPage ? 'active' : ''}" onclick="goToPage(${i})">${i}</button>`;
                } else if (i === currentPage - 3 || i === currentPage + 3) {
                    html += `<span>...</span>`;
                }
            }

            if (currentPage < data.pages) {
                html += `<button onclick="goToPage(${currentPage + 1})">→</button>`;
            }

            pagination.innerHTML = html;
        }

        function goToPage(page) {
            currentPage = page;
            loadEmails();
        }

        function updateSelection(id, checked) {
            if (checked) {
                if (!selectedIds.includes(id)) selectedIds.push(id);
            } else {
                selectedIds = selectedIds.filter(i => i !== id);
            }

            const deleteBtn = document.getElementById('deleteSelectedBtn');
            if (selectedIds.length > 0) {
                deleteBtn.classList.remove('hidden');
                deleteBtn.querySelector('span').textContent = `Xóa ${selectedIds.length} email`;
            } else {
                deleteBtn.classList.add('hidden');
            }
        }

        async function deleteEmail(id) {
            if (!confirm('Bạn có chắc muốn xóa email này?')) return;

            try {
                // Use POST with _method override for better server compatibility
                const response = await fetch('<?= BASE_URL ?>/api/admin/emails.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        _method: 'DELETE',
                        ids: [id]
                    })
                });

                const result = await response.json();

                if (response.ok) {
                    showToast('Đã xóa email', 'success');
                    loadEmails();
                } else {
                    showToast(result.error || 'Không thể xóa email', 'error');
                }
            } catch (error) {
                console.error('Delete error:', error);
                showToast('Lỗi kết nối server', 'error');
            }
        }

        async function deleteSelected() {
            if (!confirm(`Bạn có chắc muốn xóa ${selectedIds.length} email?`)) return;

            try {
                const response = await fetch('<?= BASE_URL ?>/api/admin/emails.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        _method: 'DELETE',
                        ids: selectedIds
                    })
                });

                const result = await response.json();

                if (response.ok) {
                    showToast(`Đã xóa ${selectedIds.length} email`, 'success');
                    selectedIds = [];
                    document.getElementById('selectAll').checked = false;
                    document.getElementById('deleteSelectedBtn').classList.add('hidden');
                    loadEmails();
                } else {
                    showToast(result.error || 'Không thể xóa email', 'error');
                }
            } catch (error) {
                console.error('Delete selected error:', error);
                showToast('Lỗi kết nối server', 'error');
            }
        }

        async function viewMessages(emailId, emailAddress) {
            document.getElementById('messagesModalTitle').textContent = emailAddress;
            document.getElementById('messagesModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';

            const modalBody = document.getElementById('messagesModalBody');
            modalBody.innerHTML = '<div class="loading-state"><div class="spinner"></div></div>';

            try {
                const response = await fetch(`<?= BASE_URL ?>/api/admin/messages.php?email_id=${emailId}`);
                const data = await response.json();

                if (!data.messages || data.messages.length === 0) {
                    modalBody.innerHTML = '<div class="empty-state-sm">Không có message nào</div>';
                    return;
                }

                modalBody.innerHTML = `
                    <div class="messages-list-admin">
                        ${data.messages.map(msg => `
                            <div class="message-item-admin ${msg.is_read ? '' : 'unread'}" onclick="viewMessage(${msg.id})">
                                <div class="message-info">
                                    <strong>${escapeHtml(cleanEmail(msg.from_email, msg.from_name))}</strong>
                                    <span>${escapeHtml(msg.subject)}</span>
                                </div>
                                <div class="message-meta">
                                    <span>${formatTimeVN(msg.received_at)}</span>
                                    <button class="btn-icon danger" onclick="event.stopPropagation(); deleteMessage(${msg.id}, ${emailId})">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="3 6 5 6 21 6"/>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                `;

            } catch (error) {
                modalBody.innerHTML = '<div class="error-state">Không thể tải messages</div>';
            }
        }

        function closeMessagesModal() {
            document.getElementById('messagesModal').classList.add('hidden');
            document.body.style.overflow = '';
        }

        async function viewMessage(id) {
            try {
                const response = await fetch(`<?= BASE_URL ?>/api/admin/messages.php?id=${id}`);
                const message = await response.json();

                document.getElementById('viewMessageSubject').textContent = message.subject;
                document.getElementById('viewMessageFrom').innerHTML = `From: <strong>${escapeHtml(cleanEmail(message.from_email, message.from_name))}</strong> • ${formatDateTimeVN(message.received_at)}`;

                const body = document.getElementById('viewMessageBody');
                if (message.body_html) {
                    body.innerHTML = message.body_html;
                } else {
                    body.textContent = message.body_text || '(No content)';
                    body.style.whiteSpace = 'pre-wrap';
                }

                document.getElementById('viewMessageModal').classList.remove('hidden');

            } catch (error) {
                showToast('Không thể mở email', 'error');
            }
        }

        function closeViewMessageModal() {
            document.getElementById('viewMessageModal').classList.add('hidden');
        }

        async function deleteMessage(id, emailId) {
            if (!confirm('Xóa message này?')) return;

            try {
                const response = await fetch('<?= BASE_URL ?>/api/admin/messages.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        _method: 'DELETE',
                        ids: [id]
                    })
                });

                const result = await response.json();

                if (response.ok) {
                    showToast('Đã xóa message', 'success');
                    viewMessages(emailId, document.getElementById('messagesModalTitle').textContent);
                    loadEmails();
                } else {
                    showToast(result.error || 'Không thể xóa message', 'error');
                }
            } catch (error) {
                console.error('Delete message error:', error);
                showToast('Lỗi kết nối server', 'error');
            }
        }

        function showLoading(show) {
            document.getElementById('loadingState').classList.toggle('hidden', !show);
        }

        function formatDate(dateStr) {
            return formatDateVN(dateStr);
        }

        function formatDateTime(dateStr) {
            return formatDateTimeVN(dateStr);
        }

        function formatTime(dateStr) {
            return formatTimeVN(dateStr);
        }

        // Add Domain functionality
        document.getElementById('addDomainBtn').addEventListener('click', () => {
            document.getElementById('addDomainModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        });

        function closeAddDomainModal() {
            document.getElementById('addDomainModal').classList.add('hidden');
            document.body.style.overflow = '';
            document.getElementById('addDomainForm').reset();
        }

        document.getElementById('addDomainForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const domainName = document.getElementById('domainName').value.toLowerCase().trim();
            const isActive = document.querySelector('input[name="domain_status"]:checked').value;

            if (!domainName) {
                showToast('Please enter a domain name', 'error');
                return;
            }

            // Basic validation
            if (!/^[a-z0-9.-]+\.[a-z]{2,}$/.test(domainName)) {
                showToast('Invalid domain format', 'error');
                return;
            }

            try {
                const response = await fetch('<?= BASE_URL ?>/api/admin/domains.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        domain: domainName,
                        is_active: parseInt(isActive)
                    })
                });

                const result = await response.json();

                if (response.ok) {
                    showToast('Domain added successfully!', 'success');
                    closeAddDomainModal();

                    // Reload domain select in create email modal
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    showToast(result.error || 'Failed to add domain', 'error');
                }
            } catch (error) {
                console.error('Add domain error:', error);
                showToast('Connection error', 'error');
            }
        });
    </script>
</body>

</html>