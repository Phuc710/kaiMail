<?php require_once __DIR__ . '/config/app.php'; ?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="KaiMail - Temporary Email Service">
    <title>KaiMail - Temp Mail</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/logo.svg">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css?v=<?= time() ?>">
</head>

<body>
    <!-- Toast Notification -->
    <div id="toast" class="toast"></div>

    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
                    <polyline points="22,6 12,13 2,6" />
                </svg>
                <span>KaiMail</span>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main">
        <!-- Email Input Section -->
        <section class="email-section">
            <div class="email-input-wrapper">
                <input type="text" id="emailInput" class="email-input" placeholder="Nhập email (vd: user@domain.com)"
                    autocomplete="off" spellcheck="false">
                <button id="copyBtn" class="btn-copy" title="Copy email" style="display: none;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="9" y="9" width="13" height="13" rx="2" ry="2" />
                        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" />
                    </svg>
                </button>
                <button id="getMailBtn" class="btn-get">
                    <span>Get Mail</span>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="5" y1="12" x2="19" y2="12" />
                        <polyline points="12 5 19 12 12 19" />
                    </svg>
                </button>
            </div>
        </section>

        <!-- Inbox Section -->
        <section id="inboxSection" class="inbox-section">
            <div class="inbox-header">
                <div class="inbox-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="22 12 16 12 14 15 10 15 8 12 2 12" />
                        <path
                            d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z" />
                    </svg>
                    <span>Inbox</span>
                    <span id="unreadBadge" class="unread-badge hidden">0</span>
                </div>
                <button id="refreshBtn" class="btn-refresh" title="Refresh">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="23 4 23 10 17 10" />
                        <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10" />
                    </svg>
                </button>
            </div>

            <!-- Messages List -->
            <div id="messagesList" class="messages-list">
                <!-- Messages will be loaded here -->
            </div>

            <!-- Empty State -->
            <div id="emptyState" class="empty-state">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
                    <polyline points="22,6 12,13 2,6" />
                </svg>
                <p>Chưa có email nào</p>
            </div>

            <!-- Loading State -->
            <div id="loadingState" class="loading-state hidden">
                <div class="spinner"></div>
                <span>Đang tải...</span>
            </div>
        </section>
    </main>

    <!-- Email Modal -->
    <div id="emailModal" class="modal hidden">
        <div class="modal-backdrop"></div>
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title">
                    <h2 id="modalSubject"></h2>
                    <p id="modalFrom"></p>
                </div>
                <button id="closeModal" class="btn-close">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18" />
                        <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                </button>
            </div>
            <div id="modalBody" class="modal-body">
                <!-- Email content will be loaded here -->
            </div>
        </div>
    </div>

    <!-- App JS -->
    <script src="<?= BASE_URL ?>/js/app.js"></script>
</body>

</html>