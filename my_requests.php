<?php
declare(strict_types=1);
require __DIR__ . '/auth.php';

require_login();
$user = current_user();
if (!$user) { header('Location: ' . APP_BASE . '/logout.php'); exit; }

$created = isset($_GET['created']);

$stmt = db()->prepare("SELECT id, subject, status, created_at FROM quote_requests WHERE user_id=? ORDER BY id DESC");
$stmt->execute([(int)$user['id']]);
$rows = $stmt->fetchAll();
?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Taleplerim</title>
  <style>
    :root{--bg:#0b1020;--bg2:#0f1a3a;--card:rgba(255,255,255,.08);--stroke:rgba(255,255,255,.14);--text:rgba(255,255,255,.92);--muted:rgba(255,255,255,.65);--shadow:0 18px 60px rgba(0,0,0,.55);--radius:18px;}
    *{box-sizing:border-box}
    body{margin:0;font-family:ui-sans-serif,system-ui,-apple-system,Segoe UI,Roboto,Arial;color:var(--text);
      background: radial-gradient(1200px 800px at 10% 10%, rgba(124,58,237,.20), transparent 60%),
                 radial-gradient(900px 650px at 90% 20%, rgba(34,197,94,.16), transparent 55%),
                 linear-gradient(160deg,var(--bg),var(--bg2));
      min-height:100vh; padding:20px;
    }
    a{color:rgba(255,255,255,.88);text-decoration:none}
    a:hover{text-decoration:underline}
    .top{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;max-width:980px;margin:0 auto 14px;}
    .card{max-width:980px;margin:0 auto;border-radius:var(--radius);border:1px solid var(--stroke);
      background:linear-gradient(180deg,rgba(255,255,255,.10),rgba(255,255,255,.05));box-shadow:var(--shadow);padding:18px;
    }
    .ok{margin:12px 0;padding:12px;border-radius:14px;border:1px solid rgba(34,197,94,.35);background:rgba(34,197,94,.12)}
    table{width:100%;border-collapse:collapse;margin-top:12px}
    th,td{padding:12px;border-bottom:1px solid rgba(255,255,255,.10);text-align:left;font-size:13px}
    th{color:rgba(255,255,255,.78);font-weight:700}
    .badge{display:inline-block;padding:6px 10px;border-radius:999px;border:1px solid rgba(255,255,255,.16);background:rgba(255,255,255,.06);font-size:12px;color:rgba(255,255,255,.86)}
    .empty{color:var(--muted);font-size:13px;padding:14px 0}
    .btn{display:inline-block;margin-top:10px;padding:10px 12px;border-radius:14px;border:1px solid rgba(255,255,255,.14);
      background:rgba(255,255,255,.06);color:rgba(255,255,255,.9);text-decoration:none}
  </style>
</head>
<body>

<div class="top">
  <div>
    <div style="font-weight:800;">Perga Portal</div>
    <div style="color:rgba(255,255,255,.65);font-size:12px;">Taleplerim</div>
  </div>
  <div style="display:flex;gap:12px;align-items:center;">
    <a href="<?= APP_BASE ?>/index.php">← Panel</a>
    <a href="<?= APP_BASE ?>/request_quote.php">+ Teklif Talebi</a>
    <a href="<?= APP_BASE ?>/logout.php">Çıkış</a>
  </div>
</div>

<div class="card">
  <h2 style="margin:0 0 6px;">Taleplerim</h2>
  <div style="color:rgba(255,255,255,.65);font-size:13px;">Gönderdiğiniz teklif talepleri burada listelenir.</div>

  <?php if ($created): ?>
    <div class="ok">✅ Talebiniz başarıyla alındı.</div>
  <?php endif; ?>

  <?php if (!$rows): ?>
    <div class="empty">Henüz bir talebiniz yok. İlk talebi oluşturmak için:</div>
    <a class="btn" href="<?= APP_BASE ?>/request_quote.php">Teklif Talebi Oluştur</a>
  <?php else: ?>
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Konu</th>
          <th>Durum</th>
          <th>Tarih</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?= (int)$r['id'] ?></td>
           <td>
<a href="request_detail.php?id=<?= $r['id'] ?>">
<?= htmlspecialchars($r['subject']) ?>
</a>
</td>
            <td><span class="badge"><?= htmlspecialchars($r['status']) ?></span></td>
            <td><?= htmlspecialchars((string)$r['created_at']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

</body>
</html>
