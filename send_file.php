<?php
declare(strict_types=1);
require __DIR__ . '/auth.php';

require_login();
$user = current_user();
if (!$user) { header('Location: ' . APP_BASE . '/logout.php'); exit; }

$uploadDir = __DIR__ . '/uploads';
if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }

$categories = ['Teklifler','Kataloglar','Sertifikalar','Sözleşmeler','Diğer'];

$error = '';
$ok = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $csrf = (string)($_POST['csrf'] ?? '');
  if (!verify_csrf($csrf)) {
    $error = 'Güvenlik doğrulaması başarısız.';
  } else {
    $category = trim((string)($_POST['category'] ?? 'Diğer'));
    if (!in_array($category, $categories, true)) $category = 'Diğer';

    $title = trim((string)($_POST['title'] ?? ''));
    $note  = trim((string)($_POST['note'] ?? ''));

    if ($title === '') $error = 'Başlık zorunlu.';
    elseif (empty($_FILES['file']) || ($_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
      $error = 'Dosya seçin.';
    } else {
      $f = $_FILES['file'];
      $orig = (string)$f['name'];
      $tmp  = (string)$f['tmp_name'];
      $size = (int)$f['size'];

      if ($size > 15 * 1024 * 1024) {
        $error = 'Dosya en fazla 15MB olmalı.';
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
              INSERT INTO user_files (user_id, direction, category, title, note, original_name, stored_name, mime, size_bytes)
              VALUES (?, 'user_to_admin', ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
              (int)$user['id'],
              $category,
              $title,
              $note !== '' ? $note : null,
              $orig,
              $stored,
              $mime,
              $size
            ]);
            $ok = 'Dosyanız başarıyla gönderildi.';
          }
        }
      }
    }
  }
}
?>
<!doctype html><html lang="tr"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dosya Gönder</title>
<style>
body{font-family:system-ui,Arial;margin:0;padding:20px;background:#0b1020;color:#fff}
a{color:#fff}
.card{max-width:900px;margin:0 auto;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);border-radius:16px;padding:16px}
label{display:block;margin-top:10px;margin-bottom:6px;color:rgba(255,255,255,.8)}
input,select,textarea{width:100%;padding:12px;border-radius:12px;border:1px solid rgba(255,255,255,.14);background:rgba(0,0,0,.2);color:#fff}
textarea{min-height:110px}
button{margin-top:12px;padding:12px 14px;border-radius:12px;border:0;background:linear-gradient(135deg,#7c3aed,#22c55e);color:#fff;font-weight:800;cursor:pointer}
.err{padding:10px;border-radius:12px;background:rgba(255,77,109,.14);border:1px solid rgba(255,77,109,.35)}
.ok{padding:10px;border-radius:12px;background:rgba(34,197,94,.14);border:1px solid rgba(34,197,94,.35)}
.muted{color:rgba(255,255,255,.65);font-size:12px}
</style></head><body>
<div class="card">
  <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:center;">
    <div>
      <div style="font-weight:900;">Admin’e Dosya Gönder</div>
      <div class="muted">Logo / çizim / excel vb. dosyaları gönderebilirsiniz.</div>
    </div>
    <div style="display:flex;gap:12px;align-items:center;">
      <a href="<?= APP_BASE ?>/index.php">← Panel</a>
      <a href="<?= APP_BASE ?>/files.php">Dosyalarım</a>
      <a href="<?= APP_BASE ?>/logout.php">Çıkış</a>
    </div>
  </div>

  <?php if ($error): ?><div class="err" style="margin-top:12px;"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <?php if ($ok): ?><div class="ok" style="margin-top:12px;"><?= htmlspecialchars($ok) ?></div><?php endif; ?>

  <form method="post" enctype="multipart/form-data" style="margin-top:8px;">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">

    <label>Kategori</label>
    <select name="category" required>
      <?php foreach ($categories as $c): ?>
        <option value="<?= htmlspecialchars($c) ?>" <?= $c==='Diğer' ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
      <?php endforeach; ?>
    </select>

    <label>Başlık</label>
    <input name="title" required placeholder="Örn: Logo / Teknik çizim">

    <label>Not (opsiyonel)</label>
    <textarea name="note" placeholder="Kısa açıklama..."></textarea>

    <label>Dosya</label>
    <input type="file" name="file" required>

    <button type="submit">Gönder</button>
    <div class="muted" style="margin-top:8px;">Max 15MB • pdf/jpg/png/doc/xls/zip/rar</div>
  </form>
</div>
</body></html>
