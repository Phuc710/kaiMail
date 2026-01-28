<?php require_once __DIR__ . '/../config/app.php'; ?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - KaiMail Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/admin.css">
</head>

<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
                    <polyline points="22,6 12,13 2,6" />
                </svg>
                <h1>KaiMail Admin</h1>
            </div>

            <form id="loginForm">
                <div class="form-group">
                    <label for="password">Access Key</label>
                    <input type="password" id="password" name="password" required autocomplete="current-password"
                        placeholder="Nhập access key...">
                </div>

                <div id="errorMsg" class="error-message hidden"></div>

                <button type="submit" class="btn-login">
                    <span>Đăng nhập</span>
                </button>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const password = document.getElementById('password').value;
            const errorMsg = document.getElementById('errorMsg');
            const btn = e.target.querySelector('button');

            btn.disabled = true;
            btn.innerHTML = '<span>Đang đăng nhập...</span>';
            errorMsg.classList.add('hidden');

            try {
                const response = await fetch('<?= BASE_URL ?>/api/admin/auth.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ password })
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    window.location.href = './';
                } else {
                    errorMsg.textContent = data.error || 'Access key không đúng';
                    errorMsg.classList.remove('hidden');
                }
            } catch (error) {
                errorMsg.textContent = 'Không thể kết nối server';
                errorMsg.classList.remove('hidden');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<span>Đăng nhập</span>';
            }
        });
    </script>
</body>

</html>