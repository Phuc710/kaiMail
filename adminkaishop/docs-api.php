<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/AdminLayout.php';

$adminName = 'admin';
$baseUrl = rtrim((string) BASE_URL, '/');
$basePath = rtrim((string) parse_url((string) BASE_URL, PHP_URL_PATH), '/');
$apiBase = $baseUrl . '/api';
$emailsPath = ($basePath === '' ? '' : $basePath) . '/api/emails.php';
$messagesPath = ($basePath === '' ? '' : $basePath) . '/api/messages.php';
$pollPath = ($basePath === '' ? '' : $basePath) . '/api/poll.php';

AdminLayout::begin('Tài liệu API bên ngoài', 'docs-api', $adminName);
?>
<div class="docs-container">
    <header class="docs-header">
        <h1>Hướng dẫn API bên ngoài (create/read/delete)</h1>
        <p>Trang này chỉ tập trung luồng bạn dùng: tạo mailbox, đọc mail, đọc chi tiết và xóa dữ liệu.</p>
    </header>

    <div class="docs-content">
        <section class="docs-grid">
            <article class="info-card">
                <h3>Base URL</h3>
                <p><code><?= htmlspecialchars($apiBase, ENT_QUOTES, 'UTF-8') ?></code></p>
            </article>
            <article class="info-card">
                <h3>Xác thực</h3>
                <p>Bắt buộc 4 header: <code>X-API-KEY</code>, <code>X-API-SECRET</code>, <code>X-API-TIMESTAMP</code>, <code>X-API-SIGNATURE</code>.</p>
            </article>
            <article class="info-card">
                <h3>Lưu ý local</h3>
                <p>Nếu test trên <code>http://localhost</code> mà bị <code>403</code>, set <code>API_REQUIRE_HTTPS=false</code> trong <code>.env</code>.</p>
            </article>
        </section>

        <section style="margin-bottom: 20px;">
            <h2>1) Tạo chữ ký HMAC</h2>
            <p>Chữ ký tính theo công thức: <code>METHOD + "\n" + PATH + "\n" + TIMESTAMP</code>, key là <code>API_SECRET_KEY</code>.</p>
            <div class="code-box">#!/usr/bin/env bash
API_KEY="YOUR_API_ACCESS_KEY"
API_SECRET="YOUR_API_SECRET_KEY"
TS=$(date +%s)
METHOD="POST"
PATH="<?= htmlspecialchars($emailsPath, ENT_QUOTES, 'UTF-8') ?>"
SIG=$(printf "%s\n%s\n%s" "$METHOD" "$PATH" "$TS" | openssl dgst -sha256 -hmac "$API_SECRET" -r | awk '{print $1}')</div>
        </section>

        <section style="margin-bottom: 20px;">
            <h2>2) Tạo mailbox</h2>
            <article class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge post">POST</span>
                    <span class="endpoint-url">/api/emails.php</span>
                </div>
                <p class="endpoint-description">Tạo mailbox mới. Mặc định là email vĩnh viễn.</p>
                <div class="code-box">{
  "domain": "trongnghia.store",
  "name_type": "en",
  "quantity": 1
}</div>
                <div class="code-box">curl -X POST "<?= htmlspecialchars($baseUrl . '/api/emails.php', ENT_QUOTES, 'UTF-8') ?>" \
  -H "Content-Type: application/json" \
  -H "X-API-KEY: $API_KEY" \
  -H "X-API-SECRET: $API_SECRET" \
  -H "X-API-TIMESTAMP: $TS" \
  -H "X-API-SIGNATURE: $SIG" \
  -d '{"domain":"trongnghia.store","name_type":"en","quantity":1}'</div>
            </article>
        </section>

        <section style="margin-bottom: 20px;">
            <h2>3) Đọc danh sách mail</h2>
            <article class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge get">GET</span>
                    <span class="endpoint-url">/api/messages.php?email=mailbox@domain.com</span>
                </div>
                <p class="endpoint-description">Lấy danh sách mail theo mailbox.</p>
            </article>

            <article class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge get">GET</span>
                    <span class="endpoint-url">/api/messages.php?id=123</span>
                </div>
                <p class="endpoint-description">Lấy chi tiết 1 mail theo ID.</p>
            </article>
        </section>

        <section style="margin-bottom: 20px;">
            <h2>4) Xóa dữ liệu</h2>
            <article class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge delete">DELETE</span>
                    <span class="endpoint-url">/api/messages.php</span>
                </div>
                <p class="endpoint-description">Xóa mail theo mailbox.</p>
                <div class="code-box">{
  "email": "mailbox@trongnghia.store",
  "ids": [101, 102]
}</div>
                <div class="code-box">{
  "email": "mailbox@trongnghia.store",
  "delete_all": true
}</div>
            </article>

            <article class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge delete">DELETE</span>
                    <span class="endpoint-url">/api/emails.php</span>
                </div>
                <p class="endpoint-description">Xóa mailbox (messages sẽ bị xóa theo).</p>
                <div class="code-box">{
  "email": "mailbox@trongnghia.store"
}</div>
            </article>
        </section>

        <section style="margin-bottom: 20px;">
            <h2>5) Poll realtime (tùy chọn)</h2>
            <article class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge get">GET</span>
                    <span class="endpoint-url">/api/poll.php?email_id=123&last_check=2026-03-05 19:00:00</span>
                </div>
                <p class="endpoint-description">Long-poll để lấy mail mới gần realtime.</p>
            </article>
        </section>

        <section>
            <h2>Flow nối chung để dùng nhanh</h2>
            <div class="code-box">B1: POST /api/emails.php  -> lấy email vừa tạo
B2: GET  /api/messages.php?email=...  -> danh sách mail
B3: GET  /api/messages.php?id=...     -> đọc chi tiết
B4: DELETE /api/messages.php          -> xóa message (ids hoặc delete_all)
B5: DELETE /api/emails.php            -> xóa mailbox khi dùng xong</div>
            <div class="hint-box">
                API public và API admin đã tách riêng.
                - API bạn dùng bên ngoài: <code>/api/*.php</code> + API key/secret.
                - API dashboard admin: <code>/api/admin/*.php</code> + <code>X-ADMIN-ACCESS-KEY</code>.
            </div>
        </section>
    </div>
</div>
<?php
AdminLayout::end();