<?php require_once __DIR__ . '/../config/app.php'; ?>
<!DOCTYPE html>
<html lang="vi" data-base-url="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập Admin - KaiMail</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>/assets/kaishop_favicon.png">
    <link rel="stylesheet" href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>/css/admin.css">
</head>

<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <h1>KaiMail Admin</h1>
            </div>

            <form id="loginForm">
                <div class="form-group">
                    <label for="password">Khóa truy cập</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        placeholder="Nhập khóa truy cập..."
                    >
                </div>

                <div id="errorMsg" class="error-message hidden"></div>

                <button type="submit" class="btn-login">
                    <span>Đăng nhập</span>
                </button>
            </form>
        </div>
    </div>

    <script>
        const loginForm = document.getElementById('loginForm');
        const passwordInput = document.getElementById('password');
        const errorMsg = document.getElementById('errorMsg');
        const baseUrl = document.documentElement.dataset.baseUrl || '';
        const adminKeyStorage = 'kaimail_admin_access_key';

        async function checkStoredKeyAndRedirect() {
            const storedKey = (localStorage.getItem(adminKeyStorage) || '').trim();
            if (!storedKey) return;

            try {
                const response = await fetch(`${baseUrl}/api/admin/auth.php`, {
                    method: 'GET',
                    headers: {
                        'X-ADMIN-ACCESS-KEY': storedKey
                    }
                });

                if (response.ok) {
                    window.location.href = `${baseUrl}/adminkaishop`;
                }
            } catch (error) {
                // Bỏ qua lỗi mạng tạm thời
            }
        }

        checkStoredKeyAndRedirect();

        loginForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            const submitBtn = loginForm.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span>Đang đăng nhập...</span>';
            errorMsg.classList.add('hidden');

            try {
                const response = await fetch(`${baseUrl}/api/admin/auth.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ password: passwordInput.value })
                });

                const data = await response.json();
                if (response.ok && data.success) {
                    localStorage.setItem(adminKeyStorage, passwordInput.value.trim());
                    window.location.href = `${baseUrl}/adminkaishop`;
                    return;
                }

                errorMsg.textContent = data.error || 'Khóa truy cập không đúng';
                errorMsg.classList.remove('hidden');
            } catch (error) {
                errorMsg.textContent = 'Không thể kết nối máy chủ';
                errorMsg.classList.remove('hidden');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<span>Đăng nhập</span>';
            }
        });
    </script>
</body>

</html>
