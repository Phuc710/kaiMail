<?php
/**
 * Admin Expired Emails
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
    <title>Email hết hạn - KaiMail Admin</title>
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
            <a href="<?= BASE_URL ?>/adminkaishop/expired" class="nav-item active" data-page="expired">
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
                <h1>Email hết hạn</h1>
                <p>Danh sách email đã hết hạn sử dụng</p>
            </div>
            <button id="deleteAllExpiredBtn" class="btn danger">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="3 6 5 6 21 6" />
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                </svg>
                <span>Xóa tất cả</span>
            </button>
        </header>

        <!-- Email Table -->
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>Messages</th>
                        <th>Loại thời hạn</th>
                        <th>Hết hạn lúc</th>
                        <th>Tạo lúc</th>
                        <th class="col-actions">Thao tác</th>
                    </tr>
                </thead>
                <tbody id="expiredTableBody">
                    <!-- Will be populated by JS -->
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="pagination" id="pagination"></div>

        <!-- Loading -->
        <div id="loadingState" class="loading-overlay hidden">
            <div class="spinner"></div>
        </div>
    </main>

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
        // Expired page specific
        // ============================================
        let currentPage = 1;

        document.addEventListener('DOMContentLoaded', () => {
            loadExpiredEmails();

            document.getElementById('deleteAllExpiredBtn').addEventListener('click', deleteAllExpired);
        });

        async function loadExpiredEmails() {
            showLoading(true);

            try {
                const response = await fetch(`<?= BASE_URL ?>/api/admin/emails.php?filter=expired&page=${currentPage}&limit=20`);
                const data = await response.json();

                renderExpiredEmails(data.emails);
                renderPagination(data);

            } catch (error) {
                console.error('Error:', error);
            } finally {
                showLoading(false);
            }
        }

        function renderExpiredEmails(emails) {
            const tbody = document.getElementById('expiredTableBody');

            if (!emails || emails.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="empty-row">Không có email hết hạn</td>
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
                    <td>${escapeHtml(email.email)}</td>
                    <td>${email.message_count || 0}</td>
                    <td><span class="badge badge-expired">${expiryLabels[email.expiry_type]}</span></td>
                    <td>${email.expires_at ? formatDateTime(email.expires_at) : '-'}</td>
                    <td>${formatDate(email.created_at)}</td>
                    <td class="col-actions">
                        <button class="btn-icon" onclick="viewMessages(${email.id}, '${email.email}')" title="Xem tin nhắn">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                        <button class="btn-icon" onclick="renewEmail(${email.id})" title="Gia hạn">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="23 4 23 10 17 10"/>
                                <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/>
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
            for (let i = 1; i <= data.pages; i++) {
                html += `<button class="${i === currentPage ? 'active' : ''}" onclick="goToPage(${i})">${i}</button>`;
            }
            pagination.innerHTML = html;
        }

        function goToPage(page) {
            currentPage = page;
            loadExpiredEmails();
        }

        async function renewEmail(id) {
            const expiry = prompt('Gia hạn thêm bao lâu?\n1 = 30 ngày\n2 = 1 năm\n3 = 2 năm\n4 = Vĩnh viễn');
            const expiryMap = { '1': '30days', '2': '1year', '3': '2years', '4': 'forever' };

            if (!expiry || !expiryMap[expiry]) return;

            try {
                const response = await fetch('<?= BASE_URL ?>/api/admin/emails.php', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id, expiry_type: expiryMap[expiry] })
                });

                if (response.ok) {
                    showToast('Đã gia hạn email', 'success');
                    loadExpiredEmails();
                }
            } catch (error) {
                showToast('Không thể gia hạn', 'error');
            }
        }

        async function deleteEmail(id) {
            if (!confirm('Xóa email này?')) return;

            try {
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
                    showToast('Đã xóa', 'success');
                    loadExpiredEmails();
                } else {
                    showToast(result.error || 'Không thể xóa email', 'error');
                }
            } catch (error) {
                console.error('Delete error:', error);
                showToast('Không thể xóa', 'error');
            }
        }

        async function deleteAllExpired() {
            if (!confirm('Xóa TẤT CẢ email hết hạn? Hành động này không thể hoàn tác!')) return;

            try {
                const response = await fetch('<?= BASE_URL ?>/api/admin/emails.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        _method: 'DELETE',
                        delete_all: true,
                        filter: 'expired'
                    })
                });

                const result = await response.json();
                if (response.ok) {
                    showToast(`Đã xóa ${result.deleted} email`, 'success');
                    loadExpiredEmails();
                } else {
                    showToast(result.error || 'Không thể xóa', 'error');
                }
            } catch (error) {
                console.error('Delete all error:', error);
                showToast('Không thể xóa', 'error');
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
                    loadExpiredEmails();
                } else {
                    showToast(result.error || 'Không thể xóa message', 'error');
                }
            } catch (error) {
                console.error('Delete message error:', error);
                showToast('Không thể xóa', 'error');
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
    </script>
</body>

</html>