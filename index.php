<?php
declare(strict_types=1);
require __DIR__ . '/auth.php';

require_login();  
$user = current_user();
$isAdmin = (($user['role'] ?? '') === 'admin');

if (!$user) { header('Location: ' . APP_BASE . '/logout.php'); exit; }

$name = $user['full_name'] ?: $user['email'];
// İstatistikler
if ($isAdmin) {
  $stmt = db()->prepare("SELECT COUNT(*) FROM quote_requests");
  $stmt->execute();
} else {
  $stmt = db()->prepare("SELECT COUNT(*) FROM quote_requests WHERE user_id=?");
  $stmt->execute([(int)$user['id']]);
}
$total = (int)$stmt->fetchColumn();


if ($isAdmin) {
  $stmt = db()->prepare("SELECT * FROM quote_requests ORDER BY id DESC LIMIT 1");
  $stmt->execute();
} else {
  $stmt = db()->prepare("SELECT * FROM quote_requests WHERE user_id=? ORDER BY id DESC LIMIT 1");
  $stmt->execute([(int)$user['id']]);
}
$last = $stmt->fetch();


if ($isAdmin) {
  $stmt = db()->prepare("SELECT status, COUNT(*) c FROM quote_requests GROUP BY status");
  $stmt->execute();
} else {
  $stmt = db()->prepare("SELECT status, COUNT(*) c FROM quote_requests WHERE user_id=? GROUP BY status");
  $stmt->execute([(int)$user['id']]);
}
$byStatus = $stmt->fetchAll();

$lastSubject = trim((string)($last['subject'] ?? (string)($last['konu'] ?? '')));
if ($lastSubject === '') {
  $cat = trim((string)($last['category'] ?? ''));
  $pt = trim((string)($last['product_type'] ?? (string)($last['subcategory'] ?? '')));
  $lastSubject = trim($cat . ' / ' . $pt, " /\t\n\r\0\x0B");
  if ($lastSubject === '') $lastSubject = 'Teklif Talebi';
}

?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Portal - Panel</title>
  <link rel="stylesheet" href="<?= APP_BASE ?>/assets/styles.css">
</head>
<body class="page-index">

<div class="layout">
  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="brand">
      <div class="logo">P</div>
      <div>
        <b>Perga Portal</b>
        <span><?= htmlspecialchars($user['role']) ?> erişimi</span>
      </div>
    </div>

    <nav class="nav">
      <a class="active" href="<?= APP_BASE ?>/index.php"><span class="dot"></span> Dashboard</a>
      <a href="<?= APP_BASE ?>/request_quote.php"><span class="dot"></span> Teklif Talebi</a>
      <a href="<?= APP_BASE ?>/my_requests.php"><span class="dot"></span> Taleplerim</a>
      <a href="<?= APP_BASE ?>/files.php"><span class="dot"></span> Dosyalar</a>
      <?php if (($user['role'] ?? '') === 'admin'): ?>
        <a href="<?= APP_BASE ?>/admin_users.php"><span class="dot"></span> Kullanıcılar (Admin)</a>
      <?php endif; ?>
    </nav>

    <div class="sidefoot">
      <div><b><?= htmlspecialchars($name) ?></b></div>
      <div><?= htmlspecialchars($user['email']) ?></div>
      <a class="btn-logout" href="<?= APP_BASE ?>/logout.php">Çıkış Yap</a>
    </div>
  </aside>

  <!-- Main -->
  <main class="main">
    <div class="topbar">
      <div class="hello">
        <h1>Hoş geldin, <?= htmlspecialchars($name) ?> 👋</h1>
        <p>Bu panelden teklif taleplerini oluşturabilir, geçmiş işlemlerini görüntüleyebilirsin.</p>
      </div>
      <div class="pill">🔒 Güvenli Oturum • <?= date('d.m.Y') ?></div>
    </div>

    <div class="grid">
      <section class="card" style="grid-column: span 4;">
        <h3>Toplam Talepler</h3>
        <p>Sistem üzerinden gönderdiğiniz toplam teklif talebi sayısı.</p>
        <div class="kpi"><?= $total ?></div>
        <div class="hint">Son 30 gün</div>
      </section>
<section class="card" style="grid-column: span 8;">
  <h3>Son Talebiniz</h3>
  <?php if (!$last): ?>
    <p>Henüz talep oluşturmadınız.</p>
    <div class="cta">
      <a class="btn" href="<?= APP_BASE ?>/request_quote.php">➕ İlk Teklif Talebini Oluştur</a>
    </div>
  <?php else: ?>
    <p><b><?= htmlspecialchars($lastSubject) ?></b></p>
    <p style="margin-top:8px;">Durum: <b><?= htmlspecialchars($last['status']) ?></b> • Tarih: <?= htmlspecialchars((string)$last['created_at']) ?></p>
    <div class="cta">
      <a class="btn" href="<?= APP_BASE ?>/request_detail.php?id=<?= (int)$last['id'] ?>">🔎 Detayı Gör</a>
      <a class="btn secondary" href="<?= APP_BASE ?>/my_requests.php">🧾 Tüm Talepler</a>
    </div>
  <?php endif; ?>
</section>
<section class="card" style="grid-column: span 4;">
  <h3>Durum Dağılımı</h3>
  <?php if (!$byStatus): ?>
    <p>Henüz veri yok.</p>
  <?php else: ?>
    <?php foreach ($byStatus as $s): ?>
      <p style="margin-top:8px;">
        <b><?= htmlspecialchars((string)$s['status']) ?></b> — <?= (int)$s['c'] ?>
      </p>
    <?php endforeach; ?>
  <?php endif; ?>
  <div class="hint">Durumlar admin tarafında güncellenecek.</div>
</section>

      <section class="card" style="grid-column: span 4;">
        <h3>Dosyalar</h3>
        <p>Size özel paylaşılan PDF ve dokümanlar.</p>
        <div class="kpi">0</div>
        <div class="hint">Görüntülemeye hazır</div>
      </section>

      <section class="card" style="grid-column: span 4;">
        <h3>Destek</h3>
        <p>Talep veya sorunlarınız için hızlı iletişim.</p>
        <div class="kpi">24/7</div>
        <div class="hint">info@pergaendustriyel.net</div>
      </section>

      <section class="card" style="grid-column: span 8;">
        <h3>Hızlı İşlemler</h3>
        <p>En sık kullanılan işlemler.</p>
        <div class="cta">
          <a class="btn" href="<?= APP_BASE ?>/request_quote.php">➕ Teklif Talebi Oluştur</a>
          <a class="btn secondary" href="<?= APP_BASE ?>/files.php">📄 Dosyalarım</a>
          <a class="btn secondary" href="<?= APP_BASE ?>/my_requests.php">🧾 Taleplerim</a>
        </div>
      </section>

      <section class="card" style="grid-column: span 4;">
        <h3>Hesap Bilgisi</h3>
        <p>Rol: <b><?= htmlspecialchars($user['role']) ?></b></p>
        <p style="margin-top:8px;">Email: <b><?= htmlspecialchars($user['email']) ?></b></p>
        <div class="hint" style="margin-top:10px;">Profil düzenleme modülünü ekleyebiliriz.</div>
      </section>
    </div>
  </main>
</div>

</body>
</html>
