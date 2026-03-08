<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/AdminLayout.php';

$adminName = 'admin';
$baseUrl = rtrim((string) BASE_URL, '/');
$apiBase = $baseUrl . '/api';

AdminLayout::begin('Tài liệu Tích hợp API (Bot)', 'docs-api', $adminName);
?>
<style>
    .docs-container { max-width: 1100px; margin: 0 auto; color: #334155; }
    .docs-header { margin-bottom: 2rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 1rem; }
    .docs-header h1 { color: #0f172a; font-size: 1.875rem; font-weight: 700; margin-bottom: 0.5rem; }
    .docs-header p { color: #64748b; font-size: 1rem; }

    .section-title { font-size: 1.25rem; font-weight: 600; color: #0f172a; margin: 2rem 0 1rem; border-left: 4px solid #10b981; padding-left: 1rem; }
    
    .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
    .info-card { background: #fff; padding: 1.25rem; border-radius: 0.75rem; border: 1px solid #e2e8f0; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
    .info-card h3 { font-size: 0.875rem; font-weight: 600; text-transform: uppercase; color: #64748b; margin-bottom: 0.5rem; }
    .info-card p { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; color: #059669; font-weight: 600; font-size: 0.95rem; }

    .auth-step { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 0.75rem; padding: 1.5rem; margin-bottom: 1.5rem; }
    .auth-step h4 { margin-bottom: 0.75rem; color: #1e293b; font-weight: 600; }
    
    .code-wrapper { position: relative; margin: 1rem 0; background: #1e293b; border-radius: 0.5rem; padding: 1rem; overflow-x: auto; }
    .code-wrapper pre { margin: 0; color: #e2e8f0; font-family: 'Fira Code', Consolas, monospace; font-size: 0.875rem; line-height: 1.5; }
    .code-lang { position: absolute; top: 0; right: 1rem; font-size: 0.75rem; color: #94a3b8; font-weight: 500; text-transform: uppercase; }

    .endpoint-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 1rem; overflow: hidden; margin-bottom: 2rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
    .endpoint-header { padding: 1rem 1.5rem; background: #f1f5f9; display: flex; align-items: center; gap: 1rem; }
    .method { padding: 0.25rem 0.625rem; border-radius: 0.375rem; font-weight: 700; font-size: 0.75rem; color: white; }
    .method.post { background: #10b981; }
    .method.get { background: #3b82f6; }
    .method.delete { background: #ef4444; }
    .url { font-family: monospace; font-weight: 600; color: #334155; }
    
    .endpoint-body { padding: 1.5rem; }
    .endpoint-description { margin-bottom: 1rem; line-height: 1.6; }
    
    .table-params { width: 100%; border-collapse: collapse; margin-top: 1rem; font-size: 0.875rem; }
    .table-params th, .table-params td { text-align: left; padding: 0.75rem; border-bottom: 1px solid #f1f5f9; }
    .table-params th { color: #64748b; font-weight: 600; text-transform: uppercase; font-size: 0.75rem; }
    
    .badge-req { background: #fee2e2; color: #b91c1c; padding: 0.125rem 0.375rem; border-radius: 0.25rem; font-size: 0.7rem; font-weight: 700; }
    .badge-opt { background: #f1f5f9; color: #475569; padding: 0.125rem 0.375rem; border-radius: 0.25rem; font-size: 0.7rem; font-weight: 700; }

    .hint-box { background: #fffbeb; border-left: 4px solid #f59e0b; padding: 1rem; border-radius: 0.5rem; margin: 1rem 0; font-size: 0.9rem; color: #92400e; }
    .hint-box code { background: #fef3c7; padding: 0.125rem 0.25rem; border-radius: 0.25rem; }
</style>

<div class="docs-container">
    <header class="docs-header">
        <h1>Tài liệu Tích hợp API (HMAC Bot)</h1>
        <p>Hướng dẫn chi tiết cách sử dụng API bên ngoài để xây dựng Bot quản lý mailbox.</p>
    </header>

    <div class="info-grid">
        <div class="info-card">
            <h3>Base URL</h3>
            <p><?= htmlspecialchars($apiBase) ?></p>
        </div>
        <div class="info-card">
            <h3>Xác thực</h3>
            <p>HMAC-SHA256 Signature</p>
        </div>
        <div class="info-card">
            <h3>Content-Type</h3>
            <p>application/json</p>
        </div>
    </div>

    <h2 class="section-title">1. Cơ chế Xác thực (Authentication)</h2>
    <p>Để gọi bất kỳ API nào (trừ Long Polling trang chủ), bạn phải gửi 4 Header bảo mật sau:</p>
    <table class="table-params" style="margin-bottom: 2rem;">
        <thead>
            <tr>
                <th>Header</th>
                <th>Mô tả</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><code>X-API-KEY</code></td>
                <td>Giá trị <code>API_ACCESS_KEY</code> trong cấu hình server của bạn.</td>
            </tr>
            <tr>
                <td><code>X-API-TIMESTAMP</code></td>
                <td>Unix Timestamp hiện tại (Server lệch tối đa 5 phút).</td>
            </tr>
            <tr>
                <td><code>X-API-NONCE</code></td>
                <td>Một chuỗi ngẫu nhiên duy nhất cho mỗi Request (Chống Replay Attack).</td>
            </tr>
            <tr>
                <td><code>X-API-SIGNATURE</code></td>
                <td>Chữ ký được tính toán từ các thành phần trên.</td>
            </tr>
        </tbody>
    </table>

    <div class="auth-step">
        <h4>Cách tính X-API-SIGNATURE</h4>
        <p><strong>Bước 1:</strong> Tạo chuỗi Payload theo cấu trúc (nối bằng dấu xuống dòng <code>\n</code>):</p>
        <div class="code-wrapper">
            <pre>PAYLOAD = METHOD + "\n" + PATH + "\n" + TIMESTAMP + "\n" + NONCE + "\n" + SHA256(BODY_RAW)</pre>
        </div>
        <p><strong>Bước 2:</strong> Dùng <code>API_SECRET_KEY</code> để ký chuỗi trên bằng thuật toán <code>HMAC-SHA256</code>.</p>
    </div>

    <h2 class="section-title">2. Ví dụ Code cho Bot</h2>
    
    <div style="margin-bottom: 2rem;">
        <div class="code-wrapper">
            <div class="code-lang">Python (requests)</div>
<pre>import hmac, hashlib, time, requests, json

API_KEY = "your_access_key"
SECRET_KEY = "your_secret_key"
BASE_URL = "<?= htmlspecialchars($baseUrl) ?>"

def call_api(method, path, body_data=None):
    ts = str(int(time.time()))
    nonce = "rand_" + ts
    body_str = json.dumps(body_data) if body_data else ""
    body_hash = hashlib.sha256(body_str.encode()).hexdigest()
    
    # Tạo payload và ký
    payload = f"{method.upper()}\n{path}\n{ts}\n{nonce}\n{body_hash}"
    sig = hmac.new(SECRET_KEY.encode(), payload.encode(), hashlib.sha256).hexdigest()

    headers = {
        "Content-Type": "application/json",
        "X-API-KEY": API_KEY,
        "X-API-TIMESTAMP": ts,
        "X-API-NONCE": nonce,
        "X-API-SIGNATURE": sig
    }
    
    url = BASE_URL + path
    if method.upper() == "GET":
        return requests.get(url, headers=headers)
    return requests.request(method, url, headers=headers, data=body_str)</pre>
        </div>
    </div>

    <h2 class="section-title">3. Danh sách Endpoints</h2>

    <!-- POST /api/emails.php -->
    <div class="endpoint-card">
        <div class="endpoint-header">
            <span class="method post">POST</span>
            <span class="url">/api/emails.php</span>
        </div>
        <div class="endpoint-body">
            <p class="endpoint-description">Tạo một hoặc nhiều Mailbox mới đồng loạt.</p>
            <h4>Request Body (JSON):</h4>
            <table class="table-params">
                <thead>
                    <tr><th>Trường</th><th>Kiểu</th><th>Yêu cầu</th><th>Mô tả</th></tr>
                </thead>
                <tbody>
                    <tr><td><code>domain</code></td><td>string</td><td><span class="badge-req">Bắt buộc</span></td><td>Tên miền (phải đang Active).</td></tr>
                    <tr><td><code>name_type</code></td><td>string</td><td><span class="badge-opt">Tùy chọn</span></td><td><code>en</code> (mặc định), <code>vn</code> hoặc <code>custom</code>.</td></tr>
                    <tr><td><code>quantity</code></td><td>integer</td><td><span class="badge-opt">Tùy chọn</span></td><td>Số lượng mail cần tạo (mặc định 1).</td></tr>
                </tbody>
            </table>
            <div class="code-wrapper">
                <div class="code-lang">Example Request Body</div>
                <pre>{ "domain": "kaishop.id.vn", "quantity": 10 }</pre>
            </div>
        </div>
    </div>

    <!-- GET /api/messages.php -->
    <div class="endpoint-card">
        <div class="endpoint-header">
            <span class="method get">GET</span>
            <span class="url">/api/messages.php</span>
        </div>
        <div class="endpoint-body">
            <p class="endpoint-description">Lấy danh sách thư hoặc nội dung chi tiết thư.</p>
            <h4>Query Parameters:</h4>
            <table class="table-params">
                <thead>
                    <tr><th>Tham số</th><th>Mô tả</th></tr>
                </thead>
                <tbody>
                    <tr><td><code>email</code></td><td>Tìm danh sách thư của email (ví dụ: <code>abc@domain.com</code>).</td></tr>
                    <tr><td><code>id</code></td><td>ID của thư cụ thể để xem toàn bộ nội dung (JSON/HTML).</td></tr>
                    <tr><td><code>limit</code></td><td>Số lượng thư lấy về (mặc định 25).</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- DELETE /api/messages.php -->
    <div class="endpoint-card">
        <div class="endpoint-header">
            <span class="method delete">DELETE</span>
            <span class="url">/api/messages.php</span>
        </div>
        <div class="endpoint-body">
            <p class="endpoint-description">Xóa thư trong hệ thống.</p>
            <div class="code-wrapper">
                <div class="code-lang">Example Body (Delete all for email)</div>
                <pre>{ "email": "abc@domain.com", "delete_all": true }</pre>
            </div>
        </div>
    </div>

    <div class="hint-box">
        <strong>Lưu ý:</strong> API hệ thống sử dụng <strong>Rate Limiting</strong>. Mỗi IP/Key sẽ có giới hạn số request nhất định một phút (mặc định 120). Nếu vượt quá bạn sẽ nhận mã lỗi <code>429</code>.
    </div>
</div>

<?php
AdminLayout::end();
