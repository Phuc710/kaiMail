<?php
require_once __DIR__ . '/config/app.php';
$homeCssVer = @filemtime(__DIR__ . '/css/home.css') ?: time();
$longPollingVer = @filemtime(__DIR__ . '/js/longPolling.js') ?: time();
$appJsVer = @filemtime(__DIR__ . '/js/app.js') ?: time();
$siteUrl = rtrim(BASE_URL, '/');
$pageUrl = $siteUrl . '/';
$kaishopUrl = 'https://kaishop.id.vn';
$telegramBotUrl = 'https://t.me/KaiHub_bot';
$seoTitle = 'KaiMail - Hệ thống Get Mail thuộc hệ sinh thái KaiShop';
$seoDescription = 'KaiMail là hệ thống Get Mail của KaiShop, hỗ trợ nhận email tạm thời theo thời gian thực để lấy OTP, xác minh tài khoản và kiểm tra luồng đăng ký an toàn.';
$seoKeywords = 'KaiMail, get mail, email tạm thời, hộp thư tạm thời, nhận OTP, KaiShop, KaiHub';
$seoImage = $siteUrl . '/assets/kaishop_favicon.png';
$seoImageAlt = 'KaiMail - Hệ thống Get Mail thuộc KaiShop';
$organizationId = $pageUrl . '#organization';
$websiteId = $pageUrl . '#website';
$webpageId = $pageUrl . '#webpage';
$structuredData = [
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'Organization',
            '@id' => $organizationId,
            'name' => 'KaiHub',
            'url' => $kaishopUrl,
            'sameAs' => [$kaishopUrl, $telegramBotUrl],
        ],
        [
            '@type' => 'WebSite',
            '@id' => $websiteId,
            'name' => 'KaiMail',
            'url' => $pageUrl,
            'description' => $seoDescription,
            'inLanguage' => 'vi-VN',
            'publisher' => ['@id' => $organizationId],
            'isPartOf' => [
                '@type' => 'WebSite',
                'name' => 'KaiShop',
                'url' => $kaishopUrl,
            ],
        ],
        [
            '@type' => 'WebPage',
            '@id' => $webpageId,
            'name' => $seoTitle,
            'url' => $pageUrl,
            'description' => $seoDescription,
            'inLanguage' => 'vi-VN',
            'isPartOf' => ['@id' => $websiteId],
        ],
    ],
];
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($seoTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <meta name="description" content="<?= htmlspecialchars($seoDescription, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="keywords" content="<?= htmlspecialchars($seoKeywords, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="robots" content="index,follow,max-image-preview:large">
    <meta name="author" content="KaiMail">
    <meta name="application-name" content="KaiMail">
    <meta name="apple-mobile-web-app-title" content="KaiMail">
    <meta name="referrer" content="strict-origin-when-cross-origin">
    <meta name="format-detection" content="telephone=no">
    <meta name="theme-color" content="#ffffff">
    <link rel="canonical" href="<?= htmlspecialchars($pageUrl, ENT_QUOTES, 'UTF-8') ?>">

    <meta property="og:locale" content="vi_VN">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="KaiMail - KaiShop Ecosystem">
    <meta property="og:title" content="<?= htmlspecialchars($seoTitle, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:description" content="<?= htmlspecialchars($seoDescription, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:url" content="<?= htmlspecialchars($pageUrl, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:image" content="<?= htmlspecialchars($seoImage, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:image:alt" content="<?= htmlspecialchars($seoImageAlt, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:image:type" content="image/png">
    <meta property="og:image:width" content="512">
    <meta property="og:image:height" content="512">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($seoTitle, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($seoDescription, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($seoImage, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="twitter:image:alt" content="<?= htmlspecialchars($seoImageAlt, ENT_QUOTES, 'UTF-8') ?>">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/kaishop_favicon.png">
    <link rel="shortcut icon" type="image/png" href="<?= BASE_URL ?>/assets/kaishop_favicon.png">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/home.css?v=<?= $homeCssVer ?>">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script type="application/ld+json">
        <?= json_encode($structuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?>
    </script>
</head>

<body class="user-page-body">
    <div class="user-page-container">
        <header class="user-topbar">
            <div class="brand-block">
                <span class="brand-icon" aria-hidden="true">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
                        <polyline points="22,6 12,13 2,6" />
                    </svg>
                </span>
                <span class="brand-text">
                    <span class="brand-title">KaiMail</span>
                </span>
            </div>
            <nav class="ecosystem-links" aria-label="Liên kết hệ sinh thái KaiHub">
                <a href="https://kaishop.id.vn" target="_blank" rel="noopener noreferrer" class="eco-link">
                    <span class="eco-dot" aria-hidden="true">🌐</span>
                    <span>Website: kaishop.id.vn</span>
                </a>
                <a href="https://t.me/KaiHub_bot" target="_blank" rel="noopener noreferrer" class="eco-link">
                    <span class="eco-dot" aria-hidden="true">🤖</span>
                    <span>Bot Telegram: @KaiHub_bot</span>
                </a>
            </nav>
        </header>

        <main class="user-main">
            <section class="compose-shell">
                <div class="compose-row">
                    <div class="email-input-wrapper">
                        <input type="text" id="emailInput" class="email-input"
                        placeholder="Dán email đầy đủ, ví dụ: user@domain.com" autocomplete="off" spellcheck="false">
                        <button id="copyBtn" class="btn-copy" title="Sao chép email" style="display: none;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="9" y="9" width="13" height="13" rx="2" ry="2" />
                                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" />
                            </svg>
                        </button>
                    </div>
                    <button id="getMailBtn" class="btn-get">
                        <span>Mở inbox</span>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="5" y1="12" x2="19" y2="12" />
                            <polyline points="12 5 19 12 12 19" />
                        </svg>
                    </button>
                </div>
            </section>

            <section id="inboxSection" class="inbox-section">
                <div class="inbox-header">
                    <div class="inbox-title">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="22 12 16 12 14 15 10 15 8 12 2 12" />
                            <path
                                d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z" />
                        </svg>
                        <span>Hộp thư</span>
                        <span id="unreadBadge" class="unread-badge hidden">0</span>
                    </div>
                    <button id="refreshBtn" class="btn-refresh" title="Làm mới">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="23 4 23 10 17 10" />
                            <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10" />
                        </svg>
                    </button>
                </div>

                <div id="messagesList" class="messages-list"></div>

                <div id="emptyState" class="empty-state">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
                        <polyline points="22,6 12,13 2,6" />
                    </svg>
                    <p>Chưa có thư</p>
                </div>

                <div id="loadingState" class="loading-state hidden">
                    <div class="spinner"></div>
                    <span>Đang tải...</span>
                </div>
            </section>
        </main>
    </div>

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
            <div id="modalBody" class="modal-body"></div>
        </div>
    </div>

    <script>
        window.KAIMAIL_CONFIG = {
            baseUrl: "<?= BASE_URL ?>"
        };
    </script>
    <script src="<?= BASE_URL ?>/js/longPolling.js?v=<?= $longPollingVer ?>"></script>
    <script src="<?= BASE_URL ?>/js/app.js?v=<?= $appJsVer ?>"></script>
</body>

</html>
