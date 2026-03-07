<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/AdminLoginPage.php';

$page = new AdminLoginPage(BASE_URL);
$page->render();
