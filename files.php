<?php
declare(strict_types=1);
require __DIR__ . '/auth.php';

require_login();
$user = current_user();
if (!$user) { header('Location: ' . APP_BASE . '/logout.php'); exit; }

$categories = ['Hepsi','Teklifler','Kataloglar','Sertifikalar','Sözleşmeler','Diğer'];
$cat = trim((string)($_GET['cat'] ?? 'Hepsi'));
if (!in_array($cat, $categories, true)) $cat = 'Hepsi';

if ($cat === 'Hepsi') {
  $stmt = db()->prepare("
    SELECT id, direction, category, title, note, original_name, size_bytes, created_at
    FROM user_files
    WHERE user_id=?
    ORDER BY id DESC
  ");
  $stmt->execute([(int)$user['id']]);
} else {
  $stmt = db()->prepare("
    SELECT id, direction, category, title, note, original_name, size_bytes, created_at
    FROM user_files
    WHERE user_id=? AND category=?
    ORDER BY id DESC
  ");
  $stmt->execute([(int)$user['id'], $cat]);
}
$rows = $stmt->fetchAll();

function human_bytes(?int $b): string {
  if (!$b) return '-';
  $u = ['B','KB','MB','GB']; $i=0; $v=(float)$b;
  while ($v>=1024 && $i<3){$v/=1024;$i++;}
  return round($v,1).' '.$u[$i];
}
function dir_label(string $d): string {
  return $d === 'admin_to_user' ? 'Gelen (Admin)' : 'Giden (Size ait)';
}
?>
<!doctype html><html lang="tr"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dosyalarım</title>
<link rel="stylesheet" href="<?= APP_BASE ?>/assets/styles.css"></head><body class="page-files">
<div class="card">
  <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:center;">
    <div>
      <div style="font-weight:900;">Dosyalarım</div>
      <div class="muted">Gelen teklifler ve gönderdiğiniz dosyalar.</div>
    </div>
    <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
      <a href="<?= APP_BASE ?>/index.php">← Panel</a>
      <a class="btn" href="<?= APP_BASE ?>/send_file.php">+ Dosya Gönder</a>
      <a href="<?= APP_BASE ?>/logout.php">Çıkış</a>
    </div>
  </div>

  <form method="get" style="margin-top:12px;">
    <div class="muted" style="margin-bottom:6px;">Kategori filtre</div>
    <select name="cat" onchange="this.form.submit()">
      <?php foreach ($categories as $c): ?>
        <option value="<?= htmlspecialchars($c) ?>" <?= $cat===$c ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
      <?php endforeach; ?>
    </select>
  </form>

  <?php if (!$rows): ?>
    <div class="muted" style="margin-top:12px;">Henüz dosya yok.</div>
  <?php else: ?>
    <table>
      <thead><tr>
        <th>Tür</th><th>Kategori</th><th>Başlık</th><th>Not</th><th>Dosya</th><th>Boyut</th><th>Tarih</th><th></th>
      </tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><span class="badge"><?= htmlspecialchars(dir_label((string)$r['direction'])) ?></span></td>
          <td><?= htmlspecialchars((string)$r['category']) ?></td>
          <td><?= htmlspecialchars((string)$r['title']) ?></td>
          <td><?= htmlspecialchars((string)($r['note'] ?? '')) ?></td>
          <td><?= htmlspecialchars((string)$r['original_name']) ?></td>
          <td><?= htmlspecialchars(human_bytes((int)$r['size_bytes'])) ?></td>
          <td><?= htmlspecialchars((string)$r['created_at']) ?></td>
          <td><a class="btn" href="<?= APP_BASE ?>/download.php?id=<?= (int)$r['id'] ?>">İndir</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
</body></html>
