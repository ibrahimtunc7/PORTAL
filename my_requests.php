<?php
declare(strict_types=1);
require __DIR__ . '/auth.php';

require_login();
$user = current_user();
if (!$user) { header('Location: ' . APP_BASE . '/logout.php'); exit; }

$isAdmin = (($user['role'] ?? '') === 'admin');
$created = isset($_GET['created']);

function h(?string $s): string {
  return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

function make_subject(array $r): string {
  // Önce eski alanlar
  $subject = $r['subject'] ?? ($r['konu'] ?? '');
  $subject = trim((string)$subject);
  if ($subject !== '') return $subject;

  // Yeni alanlardan fallback üret
  $category    = trim((string)($r['category'] ?? ''));
  $productType = trim((string)($r['product_type'] ?? ($r['subcategory'] ?? '')));
  $quantity    = trim((string)($r['quantity'] ?? ''));

  $s = '';
  if ($category !== '' || $productType !== '') {
    $s = ($category !== '' ? $category : 'Talep') . ' / ' . ($productType !== '' ? $productType : 'Ürün');
  } else {
    $s = 'Teklif Talebi';
  }
  if ($quantity !== '') $s .= ' - ' . $quantity;
  return $s;
}

// Liste verisi
if ($isAdmin) {
  $stmt = db()->prepare("
    SELECT qr.*, u.email, u.full_name
    FROM quote_requests qr
    LEFT JOIN users u ON u.id = qr.user_id
    ORDER BY qr.id DESC
  ");
  $stmt->execute();
} else {
  $stmt = db()->prepare("SELECT * FROM quote_requests WHERE user_id=? ORDER BY id DESC");
  $stmt->execute([(int)$user['id']]);
}
$rows = $stmt->fetchAll();
?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $isAdmin ? 'Tüm Talepler' : 'Taleplerim' ?></title>
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
    th,td{padding:12px;border-bottom:1px solid rgba(255,255,255,.10);text-align:left;font-size:13px;vertical-align:top}
    th{color:rgba(255,255,255,.78);font-weight:700}
    .badge{display:inline-block;padding:6px 10px;border-radius:999px;border:1px solid rgba(255,255,255,.16);background:rgba(255,255,255,.06);font-size:12px;color:rgba(255,255,255,.86)}
    .empty{color:var(--muted);font-size:13px;padding:14px 0}
    .btn{display:inline-block;margin-top:10px;padding:10px 12px;border-radius:14px;border:1px solid rgba(255,255,255,.14);
      background:rgba(255,255,255,.06);color:rgba(255,255,255,.9);text-decoration:none}
    .sub{color:rgba(255,255,255,.65);font-size:12px;margin-top:4px}
  </style>
</head>
<body>

<div class="top">
  <div>
    <div style="font-weight:800;">Perga Portal</div>
    <div style="color:rgba(255,255,255,.65);font-size:12px;">
      <?= $isAdmin ? 'Tüm Talepler (Admin)' : 'Taleplerim' ?>
    </div>
  </div>
  <div style="display:flex;gap:12px;align-items:center;">
    <a href="<?= APP_BASE ?>/index.php">← Panel</a>
    <a href="<?= APP_BASE ?>/request_quote.php">+ Teklif Talebi</a>
    <a href="<?= APP_BASE ?>/logout.php">Çıkış</a>
  </div>
</div>

<div class="card">
  <h2 style="margin:0 0 6px;"><?= $isAdmin ? 'Tüm Talepler' : 'Taleplerim' ?></h2>
  <div style="color:rgba(255,255,255,.65);font-size:13px;">
    <?= $isAdmin ? 'Sistemdeki tüm teklif talepleri burada listelenir.' : 'Gönderdiğiniz teklif talepleri burada listelenir.' ?>
  </div>

  <?php if ($created): ?>
    <div class="ok">✅ Talebiniz başarıyla alındı.</div>
  <?php endif; ?>

  <?php if (!$rows): ?>
    <div class="empty">Henüz bir talep yok.</div>
    <a class="btn" href="<?= APP_BASE ?>/request_quote.php">Teklif Talebi Oluştur</a>
  <?php else: ?>
    <table>
      <thead>
        <tr>
          <th>#</th>
          <?php if ($isAdmin): ?>
            <th>Müşteri</th>
          <?php endif; ?>
          <th>Konu</th>
          <th>Durum</th>
          <th>Tarih</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <?php
            $subject = make_subject($r);
            $status = (string)($r['status'] ?? 'new');
            $createdAt = (string)($r['created_at'] ?? '');
            $cust = '';
            if ($isAdmin) {
              $fn = trim((string)($r['full_name'] ?? ''));
              $em = trim((string)($r['email'] ?? ''));
              $cust = $fn !== '' ? $fn : ($em !== '' ? $em : ('User #' . (int)$r['user_id']));
            }
          ?>
          <tr>
            <td><?= (int)$r['id'] ?></td>

            <?php if ($isAdmin): ?>
              <td>
                <?= h($cust) ?>
                <?php if (!empty($r['email'])): ?>
                  <div class="sub"><?= h((string)$r['email']) ?></div>
                <?php endif; ?>
              </td>
            <?php endif; ?>

            <td>
              <a href="<?= APP_BASE ?>/request_detail.php?id=<?= (int)$r['id'] ?>">
                <?= h($subject) ?>
              </a>
              <?php
                // küçük alt bilgi: kategori/ürün (varsa)
                $cat = trim((string)($r['category'] ?? ''));
                $pt  = trim((string)($r['product_type'] ?? ($r['subcategory'] ?? '')));
                if ($cat !== '' || $pt !== ''):
              ?>
                <div class="sub"><?= h($cat) ?><?= ($cat !== '' && $pt !== '') ? ' • ' : '' ?><?= h($pt) ?></div>
              <?php endif; ?>
            </td>

            <td><span class="badge"><?= h($status) ?></span></td>
            <td><?= h($createdAt) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

</body>
</html>
