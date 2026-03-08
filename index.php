<?php
require_once __DIR__ . '/config/app.php';

// Disable caching
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

if (session_status() !== PHP_SESSION_ACTIVE) {
    if (defined('SESSION_NAME') && SESSION_NAME !== '') {
        session_name((string) SESSION_NAME);
    }

    $https = strtolower((string) ($_SERVER['HTTPS'] ?? ''));
    $isHttps = ($https !== '' && $https !== 'off') || ((string) ($_SERVER['SERVER_PORT'] ?? '')) === '443';

    session_set_cookie_params([
        'lifetime' => defined('SESSION_LIFETIME') ? (int) SESSION_LIFETIME : 86400,
        'path' => defined('SESSION_COOKIE_PATH') ? (string) SESSION_COOKIE_PATH : '/',
        'domain' => defined('SESSION_COOKIE_DOMAIN') ? (string) SESSION_COOKIE_DOMAIN : '',
        'secure' => (defined('SESSION_COOKIE_SECURE') ? (bool) SESSION_COOKIE_SECURE : false) && $isHttps,
        'httponly' => defined('SESSION_COOKIE_HTTP_ONLY') ? (bool) SESSION_COOKIE_HTTP_ONLY : true,
        'samesite' => defined('SESSION_COOKIE_SAMESITE') ? (string) SESSION_COOKIE_SAMESITE : 'Lax',
    ]);

    session_start();
}

if (empty($_SESSION['kaimail_web_ui_token']) || !is_string($_SESSION['kaimail_web_ui_token'])) {
    try {
        $_SESSION['kaimail_web_ui_token'] = bin2hex(random_bytes(32));
    } catch (Throwable $e) {
        $_SESSION['kaimail_web_ui_token'] = hash('sha256', uniqid('km_web_', true));
    }
}

$webUiToken = (string) $_SESSION['kaimail_web_ui_token'];

// Release session lock early to keep API requests responsive.
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}
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
                            placeholder="Nhập email của bạn, ví dụ: user@domain.com" autocomplete="off"
                            spellcheck="false">
                        <button id="copyBtn" class="btn-copy" title="Sao chép email" style="display: none;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <rect x="9" y="9" width="13" height="13" rx="2" ry="2" />
                                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" />
                            </svg>
                        </button>
                    </div>
                    <button id="getMailBtn" class="btn-get">
                        <span>Get Mail</span>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <line x1="5" y1="12" x2="19" y2="12" />
                            <polyline points="12 5 19 12 12 19" />
                        </svg>
                    </button>
                </div>
            </section>

            <section id="inboxSection" class="inbox-section">
                <div class="inbox-header">
                    <div class="inbox-title">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <polyline points="22 12 16 12 14 15 10 15 8 12 2 12" />
                            <path
                                d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z" />
                        </svg>
                        <span>Hộp thư</span>
                        <span id="unreadBadge" class="unread-badge hidden">0</span>
                    </div>
                    <button id="refreshBtn" class="btn-refresh" title="Làm mới">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <polyline points="23 4 23 10 17 10" />
                            <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10" />
                        </svg>
                    </button>
                </div>

                <div id="messagesList" class="messages-list"></div>

                <div id="emptyState" class="empty-state">
                    <svg width="92" height="94" viewBox="0 0 92 87" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M26 54.37V38.9C26.003 37.125 26.9469 35.4846 28.48 34.59L43.48 25.84C45.027 24.9468 46.933 24.9468 48.48 25.84L63.48 34.59C65.0285 35.4745 65.9887 37.1167 66 38.9V54.37C66 57.1314 63.7614 59.37 61 59.37H31C28.2386 59.37 26 57.1314 26 54.37Z"
                            fill="#8C92A5"></path>
                        <path
                            d="M46 47.7L26.68 36.39C26.2325 37.1579 25.9978 38.0312 26 38.92V54.37C26 57.1314 28.2386 59.37 31 59.37H61C63.7614 59.37 66 57.1314 66 54.37V38.9C66.0022 38.0112 65.7675 37.1379 65.32 36.37L46 47.7Z"
                            fill="#CDCDD8"></path>
                        <path
                            d="M27.8999 58.27C28.7796 58.9758 29.8721 59.3634 30.9999 59.37H60.9999C63.7613 59.37 65.9999 57.1314 65.9999 54.37V38.9C65.9992 38.0287 65.768 37.1731 65.3299 36.42L27.8999 58.27Z"
                            fill="#E5E5F0"></path>
                        <path class="emptyInboxRotation"
                            d="M77.8202 29.21L89.5402 25.21C89.9645 25.0678 90.4327 25.1942 90.7277 25.5307C91.0227 25.8673 91.0868 26.348 90.8902 26.75L87.0002 34.62C86.8709 34.8874 86.6407 35.0924 86.3602 35.19C86.0798 35.2806 85.7751 35.2591 85.5102 35.13L77.6502 31.26C77.2436 31.0643 76.9978 30.6401 77.0302 30.19C77.0677 29.7323 77.3808 29.3438 77.8202 29.21Z"
                            fill="#E5E5F0"></path>
                        <path class="emptyInboxRotation"
                            d="M5.12012 40.75C6.36707 20.9791 21.5719 4.92744 41.2463 2.61179C60.9207 0.296147 79.4368 12.3789 85.2401 31.32"
                            stroke="#E5E5F0" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"></path>
                        <path class="emptyInboxRotation"
                            d="M14.18 57.79L2.46001 61.79C2.03313 61.9358 1.56046 61.8088 1.2642 61.4686C0.967927 61.1284 0.906981 60.6428 1.11001 60.24L5.00001 52.38C5.12933 52.1127 5.35954 51.9076 5.64001 51.81C5.92044 51.7194 6.22508 51.7409 6.49001 51.87L14.35 55.74C14.7224 55.9522 14.9394 56.36 14.9073 56.7874C14.8753 57.2149 14.5999 57.5857 14.2 57.74L14.18 57.79Z"
                            fill="#E5E5F0"></path>
                        <path class="emptyInboxRotation"
                            d="M86.9998 45.8C85.9593 65.5282 70.9982 81.709 51.4118 84.2894C31.8254 86.8697 13.1841 75.1156 7.06982 56.33"
                            stroke="#E5E5F0" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"></path>
                    </svg>
                    <p>Chưa có thư</p>
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
            baseUrl: "<?= BASE_URL ?>",
            userToken: "<?= htmlspecialchars($webUiToken, ENT_QUOTES, 'UTF-8') ?>"
        };
    </script>
    <script src="<?= BASE_URL ?>/js/longPolling.js?v=<?= $longPollingVer ?>"></script>
    <script src="<?= BASE_URL ?>/js/app.js?v=<?= $appJsVer ?>"></script>
</body>

</html>