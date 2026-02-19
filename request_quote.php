<?php
declare(strict_types=1);
require __DIR__ . '/auth.php';

require_login();

// Eski sayfa kapalı: yeni sayfaya yönlendir
header('Location: ' . APP_BASE . '/request_new.php', true, 301);
exit;
