<?php
declare(strict_types=1);
require __DIR__ . '/auth.php';

require_login();
$me = current_user();
if (!$me || ($me['role'] ?? '') !== 'admin') { http_response_code(403); die('Yetkisiz'); }

$prefRequestId = (int)($_GET['request_id'] ?? 0);
$prefUserId    = (int)($_GET['user_id'] ?? 0);

// Eğer request_id geldiyse, DB'den doğrula ve kullanıcıyı otomatik seç
if ($prefRequestId > 0) {
  $rq = db()->prepare("SELECT id, user_id FROM quote_requests WHERE id=? LIMIT 1");
  $rq->execute([$prefRequestId]);
  $rqRow = $rq->fetch();
  if ($rqRow) {
    $prefRequestId = (int)$rqRow['id'];
    $prefUserId = (int)$rqRow['user_id'];
  } else {
    // request yoksa sıfırla
    $prefRequestId = 0;
    $prefUserId = 0;
  }
}


$uploadDir = __DIR__ . '/uploads';
if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }

$categories = ['Teklifler','Kataloglar','Sertifikalar','Sözleşmeler','Diğer'];

$error = '';
$ok = '';

$users = db()->query("SELECT id,email,full_name FROM users WHERE is_active=1 ORDER BY id DESC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $csrf = (string)($_POST['csrf'] ?? '');
  if (!verify_csrf($csrf)) {
    $error = 'Güvenlik doğrulaması başarısız.';
  } else {
    $userId = (int)($_POST['user_id'] ?? 0);
    $category = trim((string)($_POST['category'] ?? 'Teklifler'));
    if (!in_array($category, $categories, true)) $category = 'Diğer';

    $title  = trim((string)($_POST['title'] ?? ''));
    $note   = trim((string)($_POST['note'] ?? ''));
    $requestId = (int)($_POST['request_id'] ?? 0);
if ($requestId <= 0) { $requestId = null; }  // boşsa NULL olsun


    if ($userId <= 0) $error = 'Kullanıcı seçin.';
    elseif ($title === '') $error = 'Başlık zorunlu.';
    elseif (empty($_FILES['file']) || ($_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
      $error = 'Dosya seçin.';
    } else {
      $f = $_FILES['file'];
      $orig = (string)$f['name'];
      $tmp  = (string)$f['tmp_name'];
      $size = (int)$f['size'];

      if ($size > 25 * 1024 * 1024) {
        $error = 'Dosya en fazla 25MB olmalı.';
      } else {
        $mime = @mime_content_type($tmp) ?: null;
        $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
        $allowed = ['pdf','png','jpg','jpeg','doc','docx','xls','xlsx','zip','rar'];
        if (!in_array($ext, $allowed, true)) {
          $error = 'Bu dosya türüne izin verilmiyor.';
        } else {
          $stored = bin2hex(random_bytes(16)) . '.' . $ext;
          $dest = $uploadDir . '/' . $stored;

          if (!move_uploaded_file($tmp, $dest)) {
            $error = 'Dosya yüklenemedi. (uploads izinlerini kontrol edin)';
          } else {
            $stmt = db()->prepare("
             INSERT INTO user_files (user_id, direction, request_id, category, title, note, original_name, stored_name, mime, size_bytes)
VALUES (?, 'admin_to_user', ?, ?, ?, ?, ?, ?, ?, ?)

            ");
            $stmt->execute([
  $userId,
  $requestId,
  $category,
  $title,
  $note !== '' ? $note : null,
  $orig,
  $stored,
  $mime,
  $size
]);

            $ok = 'Dosya kullanıcıya gönderildi.';
          }
        }
      }
    }
  }
}
?>
<!doctype html><html lang="tr"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin - Kullanıcıya Dosya Gönder</title>
<link rel="stylesheet" href="<?= APP_BASE ?>/assets/styles.css"></head><body class="page-admin-send-file">
<div class="card">
  <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:center;">
    <div>
      <div style="font-weight:900;">Admin • Kullanıcıya Dosya Gönder</div>
      <div class="muted">Teklif PDF / sözleşme / katalog vb.</div>
    </div>
    <div style="display:flex;gap:12px;align-items:center;">
      <a href="<?= APP_BASE ?>/index.php">← Panel</a>
      <a href="<?= APP_BASE ?>/admin_inbox.php">Gelen Dosyalar</a>
      <a href="<?= APP_BASE ?>/logout.php">Çıkış</a>
    </div>
  </div>

  <?php if ($error): ?><div class="err" style="margin-top:12px;"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <?php if ($ok): ?><div class="ok" style="margin-top:12px;"><?= htmlspecialchars($ok) ?></div><?php endif; ?>

  <form method="post" enctype="multipart/form-data" style="margin-top:8px;">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">

<label>Kullanıcı</label>

<?php if ($prefUserId > 0): ?>

  <?php
  $uInfo = db()->prepare("SELECT full_name, email FROM users WHERE id=?");
  $uInfo->execute([$prefUserId]);
  $uRow = $uInfo->fetch();
  ?>

  <div style="padding:12px;background:rgba(255,255,255,.06);border-radius:10px;margin-bottom:10px;">
    <b>
      <?= htmlspecialchars($uRow['full_name'] ?: $uRow['email']) ?>
    </b>
    <div style="font-size:12px;opacity:.7;">
      <?= htmlspecialchars($uRow['email']) ?>
    </div>
  </div>

  <input type="hidden" name="user_id" value="<?= (int)$prefUserId ?>">

<?php else: ?>

  <select name="user_id" required>
    <option value="">Kullanıcı seç</option>
    <?php foreach ($users as $u): ?>
      <option value="<?= (int)$u['id'] ?>">
        <?= htmlspecialchars($u['email']) ?>
      </option>
    <?php endforeach; ?>
  </select>

<?php endif; ?>



    <label>Kategori</label>
    <select name="category" required>
      <?php foreach ($categories as $c): ?>
        <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
      <?php endforeach; ?>
    </select>

    <label>Başlık</label>
    <input name="title" required placeholder="Örn: Teklif PDF">

    <label>Not (opsiyonel)</label>
    <textarea name="note" placeholder="Kısa not..."></textarea>

    <label>Dosya</label>
    <input type="file" name="file" required>
<label>Talep ID (opsiyonel)</label>
<input name="request_id" placeholder="Örn: 6" value="<?= $prefRequestId > 0 ? (int)$prefRequestId : '' ?>">


    <button type="submit">Gönder</button>
    <div class="muted" style="margin-top:8px;">Max 25MB • pdf/jpg/png/doc/xls/zip/rar</div>
  </form>
</div>
</body></html>
