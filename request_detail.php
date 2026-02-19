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



$items = [];
if (!empty($r['items_json'])) {
  $tmpItems = json_decode((string)$r['items_json'], true);
  if (is_array($tmpItems)) $items = $tmpItems;
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
  <link rel="stylesheet" href="<?= APP_BASE ?>/assets/styles.css">
</head>
<body class="page-request-detail">
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


      <?php if (!empty($items)): ?>
        <div class="section">
          <div class="h3">Talep Edilen Ürünler</div>

          <div style="display:flex;flex-direction:column;gap:12px;">
            <?php foreach ($items as $it): ?>
              <?php
                $itCat = trim((string)($it['category'] ?? ''));
                $itPrd = trim((string)($it['product'] ?? ''));
                $itQty = trim((string)($it['quantity'] ?? ''));
                $itSpecs = (isset($it['specs']) && is_array($it['specs'])) ? $it['specs'] : [];
                $title = (($itCat !== '' ? $itCat : 'Kategori') . ' / ' . ($itPrd !== '' ? $itPrd : 'Ürün'));
              ?>
              <div class="box" style="padding:12px;">
                <div style="font-weight:900;"><?= h($title) ?></div>
                <div class="muted" style="margin-top:4px;">
                  Miktar: <b><?= h($itQty !== '' ? $itQty : '-') ?></b>
                </div>

                <?php if (!empty($itSpecs)): ?>
                  <div class="spec" style="margin-top:10px;">
                    <?php foreach ($itSpecs as $k => $v): ?>
                      <?php
                        $kk = is_scalar($k) ? (string)$k : json_encode($k, JSON_UNESCAPED_UNICODE);
                        $vv = is_scalar($v) ? (string)$v : json_encode($v, JSON_UNESCAPED_UNICODE);
                      ?>
                      <span class="chip"><?= h($kk) ?>: <b><?= h($vv) ?></b></span>
                    <?php endforeach; ?>
                  </div>
                <?php else: ?>
                  <div class="note" style="margin-top:10px;">Bu üründe teknik detay belirtilmemiş.</div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>



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
  <?php if ($isAdmin): ?>
<div class="card" style="margin-top:14px;">
  <div class="title" style="font-size:16px;">Talep Durumu</div>

  <form method="post" action="<?= APP_BASE ?>/update_status.php" style="margin-top:10px;">
    <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
    <input type="hidden" name="request_id" value="<?= (int)$r['id'] ?>">

    <select name="status" style="width:100%;padding:10px;border-radius:10px;background:#111;color:#fff;border:1px solid rgba(255,255,255,.2);">
      <option value="new" <?= $status=='new'?'selected':'' ?>>Yeni Talep</option>
      <option value="processing" <?= $status=='processing'?'selected':'' ?>>İnceleniyor</option>
      <option value="offer_sent" <?= $status=='offer_sent'?'selected':'' ?>>Teklif Gönderildi</option>
      <option value="closed" <?= $status=='closed'?'selected':'' ?>>Kapandı</option>
    </select>

    <button class="btn primary" style="width:100%;margin-top:10px;">
      Durumu Güncelle
    </button>
  </form>
</div>
<?php endif; ?>

        </div>
      </div>
    </div>

  </div>
</div>
</body>
</html>
