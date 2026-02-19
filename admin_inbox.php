<?php
declare(strict_types=1);
require __DIR__ . '/auth.php';

require_login();
$me = current_user();
if (!$me || ($me['role'] ?? '') !== 'admin') { http_response_code(403); die('Yetkisiz'); }

$rows = db()->query("
  SELECT uf.*, u.email, u.full_name
  FROM user_files uf
  LEFT JOIN users u ON u.id = uf.user_id
  WHERE uf.direction='user_to_admin'
  ORDER BY uf.id DESC
  LIMIT 200
")->fetchAll();

function human_bytes(?int $b): string {
  if (!$b) return '-';
  $u = ['B','KB','MB','GB']; $i=0; $v=(float)$b;
  while ($v>=1024 && $i<3){$v/=1024;$i++;}
  return round($v,1).' '.$u[$i];
}
?>
<!doctype html><html lang="tr"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin - Gelen Dosyalar</title>
<style>
body{font-family:system-ui,Arial;margin:0;padding:20px;background:#0b1020;color:#fff}
a{color:#fff}
.card{max-width:1100px;margin:0 auto;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);border-radius:16px;padding:16px}
table{width:100%;border-collapse:collapse;margin-top:10px}
th,td{padding:10px;border-bottom:1px solid rgba(255,255,255,.12);text-align:left;font-size:13px}
th{color:rgba(255,255,255,.8)}
.btn{display:inline-block;padding:8px 10px;border-radius:12px;border:1px solid rgba(255,255,255,.14);background:rgba(255,255,255,.06);text-decoration:none}
.muted{color:rgba(255,255,255,.65);font-size:12px}
</style></head><body>
<div class="card">
  <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:center;">
    <div>
      <div style="font-weight:900;">Admin • Gelen Dosyalar</div>
      <div class="muted">Müşterilerin gönderdiği dosyalar.</div>
    </div>
    <div style="display:flex;gap:12px;align-items:center;">
      <a href="<?= APP_BASE ?>/index.php">← Panel</a>
      <a href="<?= APP_BASE ?>/admin_send_file.php">Kullanıcıya Dosya Gönder</a>
      <a href="<?= APP_BASE ?>/logout.php">Çıkış</a>
    </div>
  </div>

  <?php if (!$rows): ?>
    <div class="muted" style="margin-top:12px;">Henüz dosya yok.</div>
  <?php else: ?>
    <table>
      <thead><tr>
        <th>ID</th><th>Müşteri</th><th>Başlık</th><th>Not</th><th>Dosya</th><th>Boyut</th><th>Tarih</th><th></th>
      </tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= (int)$r['id'] ?></td>
          <td><?= htmlspecialchars((string)(($r['full_name'] ?? '') ?: ($r['email'] ?? ''))) ?></td>
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
