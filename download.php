<?php
declare(strict_types=1);
require __DIR__ . '/auth.php';

require_login();
$u = current_user();
if (!$u) { http_response_code(403); die('Yetkisiz'); }

$id = (int)($_GET['id'] ?? 0);

$stmt = db()->prepare("SELECT * FROM user_files WHERE id=? LIMIT 1");
$stmt->execute([$id]);
$f = $stmt->fetch();
if (!$f) { http_response_code(404); die('Dosya bulunamadı'); }

$ownerId = (int)$f['user_id'];

// Yetki:
// - admin: her şeyi indirebilir
// - user: sadece kendi dosyalarını indirebilir
if (($u['role'] ?? '') !== 'admin' && (int)$u['id'] !== $ownerId) {
  http_response_code(403);
  die('Bu dosyaya erişiminiz yok.');
}

$path = __DIR__ . '/uploads/' . $f['stored_name'];
if (!is_file($path)) { http_response_code(404); die('Dosya disk üzerinde yok'); }

$downloadName = (string)$f['original_name'];
$mime = $f['mime'] ?: 'application/octet-stream';

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($path));
header('Content-Disposition: attachment; filename="' . str_replace('"','', $downloadName) . '"');
header('X-Content-Type-Options: nosniff');

readfile($path);
exit;
