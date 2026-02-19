<?php
declare(strict_types=1);
require __DIR__ . '/db.php';

function start_session(): void {
  if (session_status() === PHP_SESSION_ACTIVE) return;

  session_name(SESSION_NAME);
  session_set_cookie_params([
    'lifetime' => 0,
    'path' => APP_BASE . '/',
    'secure' => !empty($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax',
  ]);
  session_start();
}

function csrf_token(): string {
  start_session();
  if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
  return $_SESSION['csrf'];
}

function verify_csrf(string $token): bool {
  start_session();
  return hash_equals($_SESSION['csrf'] ?? '', $token);
}

function is_logged_in(): bool {
  start_session();
  return !empty($_SESSION['user_id']);
}

function require_login(): void {
  if (!is_logged_in()) {
    header('Location: ' . APP_BASE . '/login.php');
    exit;
  }
}

function require_admin(): array {
  require_login();
  $user = current_user();
  if (!$user || ($user['role'] ?? '') !== 'admin') {
    http_response_code(403);
    die('Yetkisiz');
  }
  return $user;
}

function require_post_csrf(): void {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Method not allowed');
  }

  $csrf = (string)($_POST['csrf'] ?? '');
  if (!verify_csrf($csrf)) {
    http_response_code(400);
    die('Güvenlik doğrulaması başarısız.');
  }
}

function login_user(string $email, string $password): bool {
  $stmt = db()->prepare('SELECT id,password_hash,is_active FROM users WHERE email=? LIMIT 1');
  $stmt->execute([$email]);
  $u = $stmt->fetch();

  if (!$u || (int)$u['is_active'] !== 1) return false;
  if (!password_verify($password, $u['password_hash'])) return false;

  start_session();
  session_regenerate_id(true);
  $_SESSION['user_id'] = (int)$u['id'];
  return true;
}

function current_user(): ?array {
  if (!is_logged_in()) return null;
  $stmt = db()->prepare('SELECT id,email,full_name,role,is_active FROM users WHERE id=? LIMIT 1');
  $stmt->execute([$_SESSION['user_id']]);
  $u = $stmt->fetch();
  if (!$u || (int)$u['is_active'] !== 1) return null;
  return $u;
}

function logout_user(): void {
  start_session();
  $_SESSION = [];
  if (ini_get("session.use_cookies")) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
  }
  session_destroy();
}
