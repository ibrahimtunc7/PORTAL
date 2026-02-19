<?php
declare(strict_types=1);
require __DIR__ . '/auth.php';

require_login();
$me = current_user();
if (!$me) { header('Location: ' . APP_BASE . '/logout.php'); exit; }

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { http_response_code(404); die('Talep bulunamadı'); }

$isAdmin = (($me['role'] ?? '') === 'admin');

// Talebi çek (admin değilse kendi talebi)
if ($isAdmin) {
  $st = db()->prepare("SELECT * FROM quote_requests WHERE id=? LIMIT 1");
  $st->execute([$id]);
} else {
  $st = db()->prepare("SELECT * FROM quote_requests WHERE id=? AND user_id=? LIMIT 1");
  $st->execute([$id, (int)$me['id']]);
}
$r = $st->fetch();
if (!$r) { http_response_code(404); die('Talep bulunamadı'); }

function h(?string $s): string { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

$category    = $r['category'] ?? null;
$subcategory = $r['subcategory'] ?? null;
$productType = $r['product_type'] ?? ($r['subcategory'] ?? null);
$quantity    = $r['quantity'] ?? null;
$city        = $r['city'] ?? null;
$deadline    = $r['deadline'] ?? null;

$subject = $r['subject'] ?? $r['konu'] ?? null;
if (!$subject) {
  $subject = trim((string)$category . ' / ' . (string)$productType);
  if (!empty($quantity)) $subject .= ' - ' . $quantity;
  if ($subject === '' ) $subject = 'Teklif Talebi';
}

$details = $r['details'] ?? $r['aciklama'] ?? null;

$status = $r['status'] ?? 'new';
$created = $r['created_at'] ?? null;

$specs = [];
if (!empty($r['specs_json'])) {
  $tmp = json_decode((string)$r['specs_json'], true);
  if (is_array($tmp)) $specs = $tmp;
}

function status_label(string $s): array {
  // [text, badgeClass]
  switch ($s) {
    case 'new': return ['Yeni', 'b-new'];
    case 'processing': return ['İşleniyor', 'b-proc'];
    case 'offer_sent': return ['Teklif Gönderildi', 'b-offer'];
    case 'closed': return ['Kapandı', 'b-closed'];
    default: return [strtoupper($s), 'b-new'];
  }
}
[$statusText, $badgeClass] = status_label((string)$status);
?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($subject) ?> • Talep Detay</title>
<style>
  :root{
    --bg:#0b1020;
    --card:rgba(255,255,255,.06);
    --card2:rgba(255,255,255,.04);
    --br:rgba(255,255,255,.12);
    --mut:rgba(255,255,255,.65);
    --txt:#fff;
    --g1:#7c3aed; --g2:#22c55e;
  }
  *{box-sizing:border-box}
  body{margin:0;background:var(--bg);color:var(--txt);font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial}
  a{color:#fff;text-decoration:none}
  .wrap{max-width:1100px;margin:0 auto;padding:20px}
  .topbar{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:14px}
  .crumb{display:flex;gap:10px;align-items:center;color:var(--mut);font-size:13px}
  .crumb a{color:var(--mut)}
  .btn{display:inline-flex;align-items:center;gap:8px;padding:10px 12px;border-radius:12px;border:1px solid var(--br);background:var(--card);cursor:pointer}
  .btn.primary{border:0;background:linear-gradient(135deg,var(--g1),var(--g2));font-weight:800}
  .btn.ghost{background:transparent}
  .grid{display:grid;grid-template-columns: 1.6fr .9fr;gap:14px}
  @media (max-width: 980px){ .grid{grid-template-columns:1fr} }
  .card{background:var(--card);border:1px solid var(--br);border-radius:18px;padding:16px}
  .title{font-weight:950;font-size:18px;letter-spacing:.2px}
  .muted{color:var(--mut);font-size:12px}
  .badge{display:inline-flex;align-items:center;padding:7px 10px;border-radius:999px;border:1px solid var(--br);background:var(--card2);font-size:12px}
  .b-new{border-color:rgba(124,58,237,.35);background:rgba(124,58,237,.14)}
  .b-proc{border-color:rgba(59,130,246,.35);background:rgba(59,130,246,.14)}
  .b-offer{border-color:rgba(34,197,94,.35);background:rgba(34,197,94,.14)}
  .b-closed{border-color:rgba(148,163,184,.35);background:rgba(148,163,184,.10)}
  .hero{display:flex;justify-content:space-between;gap:14px;flex-wrap:wrap;align-items:flex-start}
  .hero-left{min-width:260px}
  .hero-right{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
  .kpi{display:grid;grid-template-columns:repeat(2,1fr);gap:10px;margin-top:12px}
  @media (max-width:520px){ .kpi{grid-template-columns:1fr} }
  .k{background:var(--card2);border:1px solid var(--br);border-radius:14px;padding:12px}
  .k .t{font-size:12px;color:var(--mut);margin-bottom:6px}
  .k .v{font-weight:900}
  .section{margin-top:14px}
  .h3{font-weight:900;margin:0 0 10px 0;font-size:14px;color:rgba(255,255,255,.9)}
  .list{display:flex;flex-direction:column;gap:10px}
  .row{display:flex;justify-content:space-between;gap:12px;padding:10px 0;border-bottom:1px solid rgba(255,255,255,.10)}
  .row:last-child{border-bottom:0}
  .row .l{color:var(--mut);font-size:12px}
  .row .r{font-weight:800}
  .spec{display:flex;flex-wrap:wrap;gap:8px}
  .chip{font-size:12px;border:1px solid var(--br);background:var(--card2);padding:8px 10px;border-radius:999px}
  .note{border-left:3px solid rgba(34,197,94,.55);padding:10px 12px;background:rgba(34,197,94,.08);border-radius:12px}
</style>
</head>
<body>
<div class="wrap">

  <div class="topbar">
    <div class="crumb">
      <a href="<?= APP_BASE ?>/index.php">Panel</a>
      <span>›</span>
      <a href="<?= APP_BASE ?>/index.php">Talepler</a>
      <span>›</span>
      <span><?= h($subject) ?></span>
    </div>

    <div style="display:flex;gap:10px;flex-wrap:wrap;">
      <a class="btn ghost" href="<?= APP_BASE ?>/index.php">← Geri</a>
      <a class="btn" href="<?= APP_BASE ?>/send_file.php">Dosya Gönder</a>
      <?php if ($isAdmin): ?>
  <a class="btn primary" href="<?= APP_BASE ?>/admin_send_file.php?request_id=<?= (int)$r['id'] ?>&user_id=<?= (int)$r['user_id'] ?>">
    Bu talebe dosya yükle
  </a>
<?php endif; ?>
    </div>
  </div>

  <div class="grid">
    <!-- LEFT -->
    <div class="card">
      <div class="hero">
        <div class="hero-left">
          <div class="title"><?= h($subject) ?></div>
          <div class="muted" style="margin-top:6px;">
            Talep ID: <b>#<?= (int)$r['id'] ?></b>
            <?php if ($created): ?> • Oluşturma: <b><?= h((string)$created) ?></b><?php endif; ?>
          </div>
          <div style="margin-top:10px;">
            <span class="badge <?= h($badgeClass) ?>"><?= h($statusText) ?></span>
          </div>
        </div>



        <div class="hero-right">
          <?php if (!empty($city)): ?><span class="chip">📍 <?= h((string)$city) ?></span><?php endif; ?>
          <?php if (!empty($deadline)): ?><span class="chip">⏱ <?= h((string)$deadline) ?></span><?php endif; ?>
          <?php if (!empty($quantity)): ?><span class="chip">📦 <?= h((string)$quantity) ?></span><?php endif; ?>
        </div>
      </div>

      <div class="kpi">
        <div class="k">
          <div class="t">Kategori</div>
          <div class="v"><?= h((string)($category ?: '-')) ?></div>
        </div>
        <div class="k">
          <div class="t">Ürün / Alt Kategori</div>
          <div class="v"><?= h((string)($productType ?: ($subcategory ?: '-'))) ?></div>
        </div>
        <div class="k">
          <div class="t">Miktar</div>
          <div class="v"><?= h((string)($quantity ?: '-')) ?></div>
        </div>
        <div class="k">
          <div class="t">Termin</div>
          <div class="v"><?= h((string)($deadline ?: '-')) ?></div>
        </div>
      </div>

      <div class="section">
        <div class="h3">Açıklama</div>
        <div class="box"><?= h((string)($details ?: '—')) ?></div>
      </div>

      <?php if (!empty($specs)): ?>
        <div class="section">
          <div class="h3">Teknik Özellikler</div>
          <div class="spec">
            <?php foreach ($specs as $k => $v): ?>
              <span class="chip"><?= h((string)$k) ?>: <b><?= h(is_scalar($v) ? (string)$v : json_encode($v, JSON_UNESCAPED_UNICODE)) ?></b></span>
            <?php endforeach; ?>
          </div>
        </div>
      <?php else: ?>
        <div class="section">
          <div class="note">
            Teknik detayları daha sonra genişletebiliriz (ör. PP çember için en/kalınlık/çekme dayanımı gibi).
          </div>
        </div>
      <?php endif; ?>
    </div>

    <!-- RIGHT -->
    <div class="card">
      <div class="title" style="font-size:16px;">Özet Bilgiler</div>
      <div class="muted" style="margin-top:6px;">Hızlı kontrol ve aksiyon alanı.</div>

      <div class="section">
        <div class="list">
          <div class="row"><div class="l">Durum</div><div class="r"><?= h($statusText) ?></div></div>
          <div class="row"><div class="l">Şehir</div><div class="r"><?= h((string)($city ?: '-')) ?></div></div>
          <div class="row"><div class="l">Kategori</div><div class="r"><?= h((string)($category ?: '-')) ?></div></div>
          <div class="row"><div class="l">Ürün</div><div class="r"><?= h((string)($productType ?: '-')) ?></div></div>
          <div class="row"><div class="l">Miktar</div><div class="r"><?= h((string)($quantity ?: '-')) ?></div></div>
          <div class="row"><div class="l">Termin</div><div class="r"><?= h((string)($deadline ?: '-')) ?></div></div>
        </div>
      </div>

      <div class="section">
        <div class="h3">Aksiyonlar</div>
        <div style="display:flex;flex-direction:column;gap:10px;">
          <a class="btn" href="<?= APP_BASE ?>/files.php">Dosyalarım</a>
          <?php if ($isAdmin): ?>
            
          <?php endif; ?>
        </div>
        <div class="muted" style="margin-top:10px;">
        <?php
        
$files = db()->prepare("
  SELECT *
  FROM user_files
  WHERE request_id = ?
  ORDER BY id DESC
");
$files->execute([$id]);
$fileRows = $files->fetchAll();
?>

<div class="card" style="margin-top:14px;">
  <div class="title" style="font-size:16px;">Bu Talebe Ait Dosyalar</div>

  <?php if (!$fileRows): ?>
    <div class="muted" style="margin-top:10px;">Henüz dosya yok.</div>
  <?php else: ?>
    <div style="margin-top:10px;display:flex;flex-direction:column;gap:8px;">
      <?php foreach ($fileRows as $f): ?>
        <div style="display:flex;justify-content:space-between;align-items:center;background:rgba(255,255,255,.04);padding:10px;border-radius:10px;">
          <div>
            <b><?= htmlspecialchars($f['title']) ?></b><br>
            <span class="muted"><?= htmlspecialchars($f['original_name']) ?></span>
          </div>
          <a class="btn" href="<?= APP_BASE ?>/download.php?id=<?= (int)$f['id'] ?>">İndir</a>
        </div>
        
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

        </div>
      </div>
    </div>

  </div>
</div>
</body>
</html>
