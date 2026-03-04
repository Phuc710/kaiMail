<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/AdminLayout.php';

$adminName = 'admin';
$baseApi = rtrim((string) BASE_URL, '/') . '/api';
$basePath = rtrim((string) parse_url((string) BASE_URL, PHP_URL_PATH), '/');
$statsPath = ($basePath === '' ? '' : $basePath) . '/api/admin/stats.php';

AdminLayout::begin('Tài liệu API', 'docs-api', $adminName);
?>
<div class="docs-container">
    <header class="docs-header">
        <h1>Tài liệu API KaiMail</h1>
        <p>UI admin đăng nhập bằng access key, không dùng cookie. Tích hợp bên ngoài dùng API key/secret + chữ ký.</p>
    </header>

    <div class="docs-content">
        <section class="docs-grid">
            <article class="info-card">
                <h3>Địa chỉ API gốc</h3>
                <p><code><?= htmlspecialchars($baseApi, ENT_QUOTES, 'UTF-8') ?></code></p>
            </article>
            <article class="info-card">
                <h3>Kiểu bảo mật</h3>
                <p>Header <code>X-API-KEY</code> + <code>X-API-SECRET</code> + chữ ký HMAC.</p>
            </article>
            <article class="info-card">
                <h3>Múi giờ hệ thống</h3>
                <p>Toàn bộ thời gian dùng chuẩn Việt Nam <code>+07:00</code> (<code>Asia/Ho_Chi_Minh</code>).</p>
            </article>
        </section>

        <section style="margin-bottom: 20px;">
            <h2>Phân biệt 2 luồng bảo mật</h2>
            <table class="param-table">
                <thead>
                    <tr>
                        <th>Luồng</th>
                        <th>Xác thực</th>
                        <th>Mục đích</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>UI Admin Web</td>
                        <td><code>ADMIN_ACCESS_KEY</code> (header <code>X-ADMIN-ACCESS-KEY</code>)</td>
                        <td>Đăng nhập trong trình duyệt tại <code>/adminkaishop/login</code>, không dùng cookie</td>
                    </tr>
                    <tr>
                        <td>API bên ngoài</td>
                        <td><code>X-API-KEY</code>, <code>X-API-SECRET</code>, <code>X-API-TIMESTAMP</code>, <code>X-API-SIGNATURE</code></td>
                        <td>Gọi API từ app/service bên ngoài</td>
                    </tr>
                </tbody>
            </table>
        </section>

        <section>
            <h2>Bắt buộc xác thực cho toàn bộ API</h2>
            <table class="param-table">
                <thead>
                    <tr>
                        <th>Header</th>
                        <th>Bắt buộc</th>
                        <th>Mô tả</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>X-API-KEY</code></td>
                        <td>Có</td>
                        <td>Khóa truy cập API từ <code>.env</code> (`API_ACCESS_KEY`)</td>
                    </tr>
                    <tr>
                        <td><code>X-API-SECRET</code></td>
                        <td>Có</td>
                        <td>Secret API từ <code>.env</code> (`API_SECRET_KEY`)</td>
                    </tr>
                    <tr>
                        <td><code>X-API-TIMESTAMP</code></td>
                        <td>Có</td>
                        <td>Unix timestamp (giây), bị từ chối nếu quá thời gian <code>API_REQUEST_TTL</code></td>
                    </tr>
                    <tr>
                        <td><code>X-API-SIGNATURE</code></td>
                        <td>Có</td>
                        <td>
                            HMAC SHA256 của chuỗi:<br>
                            <code>METHOD + "\n" + PATH + "\n" + TIMESTAMP</code>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div class="hint-box">
                Gợi ý bảo mật cao: chỉ cho phép IP riêng bằng `API_ALLOWED_IPS` trong `.env`.
            </div>
        </section>

        <section style="margin-top: 20px;">
            <h2>Cách ký chữ ký (HMAC)</h2>
            <div class="code-box"># Ví dụ với endpoint:
# GET /kaiMail/api/admin/stats.php

METHOD="GET"
PATH="<?= htmlspecialchars($statsPath, ENT_QUOTES, 'UTF-8') ?>"
TIMESTAMP=$(date +%s)
SIGNATURE=$(printf "%s\n%s\n%s" "$METHOD" "$PATH" "$TIMESTAMP" | \
  openssl dgst -sha256 -hmac "$API_SECRET_KEY" -r | awk '{print $1}')</div>
        </section>

        <section style="margin-top: 20px;">
            <h2>Điểm cuối chính</h2>

            <article class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge get">GET</span>
                    <span class="endpoint-url">/api/admin/auth.php</span>
                </div>
                <p class="endpoint-description">Kiểm tra API key/secret còn hợp lệ hay không.</p>
            </article>

            <article class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge get">GET</span>
                    <span class="endpoint-url">/api/admin/stats.php</span>
                </div>
                <p class="endpoint-description">Lấy thống kê hệ thống.</p>
            </article>

            <article class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge get">GET</span>
                    <span class="endpoint-url">/api/admin/emails.php?filter=active&page=1</span>
                </div>
                <p class="endpoint-description">Lấy danh sách email.</p>
            </article>

            <article class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge post">POST</span>
                    <span class="endpoint-url">/api/admin/emails.php</span>
                </div>
                <p class="endpoint-description">Tạo email mới.</p>
                <div class="code-box">{
  "name_type": "vn",
  "domain": "example.com"
}</div>
            </article>

            <article class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge delete">DELETE</span>
                    <span class="endpoint-url">/api/admin/emails.php</span>
                </div>
                <p class="endpoint-description">Xóa email theo danh sách ID.</p>
                <div class="code-box">{
  "ids": [1, 2, 3]
}</div>
            </article>

            <article class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge get">GET</span>
                    <span class="endpoint-url">/api/messages.php?email=user@example.com</span>
                </div>
                <p class="endpoint-description">Lấy tin nhắn theo email (đã bảo vệ bằng API key/secret).</p>
            </article>
        </section>

        <section style="margin-top: 20px;">
            <h2>Ví dụ cURL đầy đủ</h2>
            <div class="code-box">#!/usr/bin/env bash
BASE="<?= htmlspecialchars(rtrim((string) BASE_URL, '/'), ENT_QUOTES, 'UTF-8') ?>"
API_KEY="YOUR_API_ACCESS_KEY"
API_SECRET="YOUR_API_SECRET_KEY"
METHOD="GET"
PATH="<?= htmlspecialchars($statsPath, ENT_QUOTES, 'UTF-8') ?>"
TS=$(date +%s)
SIG=$(printf "%s\n%s\n%s" "$METHOD" "$PATH" "$TS" | openssl dgst -sha256 -hmac "$API_SECRET" -r | awk '{print $1}')

curl -X "$METHOD" "$BASE$PATH" \
  -H "X-API-KEY: $API_KEY" \
  -H "X-API-SECRET: $API_SECRET" \
  -H "X-API-TIMESTAMP: $TS" \
  -H "X-API-SIGNATURE: $SIG"</div>
        </section>

        <section style="margin-top: 20px;">
            <h2>Mã lỗi thường gặp</h2>
            <table class="param-table">
                <thead>
                    <tr>
                        <th>Mã</th>
                        <th>Ý nghĩa</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>200</code></td>
                        <td>Thành công</td>
                    </tr>
                    <tr>
                        <td><code>401</code></td>
                        <td>Thiếu/sai key, secret, timestamp hoặc signature</td>
                    </tr>
                    <tr>
                        <td><code>403</code></td>
                        <td>IP không nằm trong allowlist hoặc bắt buộc HTTPS</td>
                    </tr>
                    <tr>
                        <td><code>405</code></td>
                        <td>Sai HTTP method</td>
                    </tr>
                    <tr>
                        <td><code>500</code></td>
                        <td>Lỗi hệ thống</td>
                    </tr>
                </tbody>
            </table>
        </section>
    </div>
</div>
<?php
AdminLayout::end();
