<?php
declare(strict_types=1);
require __DIR__ . '/auth.php';

require_login();  
$user = current_user();
if (!$user) { header('Location: ' . APP_BASE . '/logout.php'); exit; }

$name = $user['full_name'] ?: $user['email'];
// İstatistikler
$stmt = db()->prepare("SELECT COUNT(*) AS c FROM quote_requests WHERE user_id=?");
$stmt->execute([(int)$user['id']]);
$total = (int)($stmt->fetch()['c'] ?? 0);

$stmt = db()->prepare("SELECT id, subject, status, created_at FROM quote_requests WHERE user_id=? ORDER BY id DESC LIMIT 1");
$stmt->execute([(int)$user['id']]);
$last = $stmt->fetch(); // yoksa false

$stmt = db()->prepare("
  SELECT status, COUNT(*) AS c
  FROM quote_requests
  WHERE user_id=?
  GROUP BY status
");
$stmt->execute([(int)$user['id']]);
$byStatus = $stmt->fetchAll();

?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Portal - Panel</title>
  <style>
    :root{
      --bg:#0b1020;
      --bg2:#0f1a3a;
      --card: rgba(255,255,255,.08);
      --stroke: rgba(255,255,255,.14);
      --text: rgba(255,255,255,.92);
      --muted: rgba(255,255,255,.65);
      --accent:#7c3aed;
      --accent2:#22c55e;
      --shadow: 0 18px 60px rgba(0,0,0,.55);
      --radius: 18px;
    }
    *{box-sizing:border-box}
    body{
      margin:0;
      font-family: ui-sans-serif,system-ui,-apple-system,Segoe UI,Roboto,Arial;
      color:var(--text);
      min-height:100vh;
      background:
        radial-gradient(1200px 800px at 10% 10%, rgba(124,58,237,.25), transparent 60%),
        radial-gradient(900px 650px at 90% 20%, rgba(34,197,94,.18), transparent 55%),
        linear-gradient(160deg, var(--bg), var(--bg2));
    }
    .layout{
      display:grid;
      grid-template-columns: 280px 1fr;
      min-height:100vh;
    }
    /* Sidebar */
    .sidebar{
      border-right: 1px solid var(--stroke);
      background: rgba(255,255,255,.04);
      backdrop-filter: blur(10px);
      padding: 18px;
      position: sticky;
      top: 0;
      height: 100vh;
    }
    .brand{
      display:flex; align-items:center; gap:12px;
      padding: 10px 10px 16px;
      border-bottom: 1px solid rgba(255,255,255,.10);
      margin-bottom: 14px;
    }
    .logo{
      width:42px;height:42px;border-radius:14px;
      background: linear-gradient(135deg, rgba(124,58,237,.95), rgba(34,197,94,.85));
      display:flex;align-items:center;justify-content:center;
      font-weight:800;
    }
    .brand b{display:block;font-size:14px}
    .brand span{display:block;color:var(--muted);font-size:12px;margin-top:2px}
    .nav a{
      display:flex; align-items:center; gap:10px;
      padding: 11px 12px;
      border-radius: 14px;
      color: rgba(255,255,255,.86);
      text-decoration:none;
      border: 1px solid transparent;
      margin: 6px 0;
    }
    .nav a:hover{
      background: rgba(255,255,255,.06);
      border-color: rgba(255,255,255,.10);
    }
    .nav .active{
      background: rgba(124,58,237,.18);
      border-color: rgba(124,58,237,.35);
    }
    .nav .dot{
      width:8px;height:8px;border-radius:99px;
      background: rgba(255,255,255,.35);
    }
    .nav .active .dot{ background: rgba(124,58,237,.95); }

    .sidefoot{
      margin-top: 16px;
      padding-top: 14px;
      border-top: 1px solid rgba(255,255,255,.10);
      color: var(--muted);
      font-size: 12px;
    }
    .btn-logout{
      display:block;
      margin-top: 12px;
      text-align:center;
      padding: 10px 12px;
      border-radius: 14px;
      border: 1px solid rgba(255,255,255,.14);
      background: rgba(255,255,255,.06);
      color: rgba(255,255,255,.9);
      text-decoration:none;
    }
    .btn-logout:hover{ filter: brightness(1.06); }

    /* Main */
    .main{ padding: 22px; }
    .topbar{
      display:flex;
      align-items:flex-start;
      justify-content:space-between;
      gap: 14px;
      margin-bottom: 16px;
    }
    .hello h1{
      margin:0;
      font-size: 24px;
      letter-spacing:-.3px;
    }
    .hello p{
      margin:6px 0 0;
      color: var(--muted);
      font-size: 13px;
    }
    .pill{
      border:1px solid rgba(255,255,255,.14);
      background: rgba(255,255,255,.06);
      padding: 10px 12px;
      border-radius: 999px;
      color: rgba(255,255,255,.86);
      font-size: 13px;
      display:flex; gap:10px; align-items:center;
      white-space:nowrap;
    }
    .grid{
      display:grid;
      grid-template-columns: repeat(12, 1fr);
      gap: 14px;
    }
    .card{
      border-radius: var(--radius);
      border: 1px solid var(--stroke);
      background: linear-gradient(180deg, rgba(255,255,255,.10), rgba(255,255,255,.05));
      box-shadow: var(--shadow);
      padding: 16px;
      overflow:hidden;
    }
    .card h3{
      margin:0 0 6px;
      font-size: 14px;
      letter-spacing:.2px;
    }
    .card p{
      margin:0;
      color: var(--muted);
      font-size: 13px;
      line-height:1.45;
    }
    .kpi{
      font-size: 26px;
      font-weight: 800;
      margin-top: 10px;
      letter-spacing:-.6px;
    }
    .hint{
      margin-top: 8px;
      font-size: 12px;
      color: rgba(255,255,255,.55);
    }
    .cta{
      display:flex;
      gap: 10px;
      flex-wrap:wrap;
      margin-top: 12px;
    }
    .btn{
      border:0;
      cursor:pointer;
      border-radius: 14px;
      padding: 10px 12px;
      font-weight: 700;
      color:white;
      background: linear-gradient(135deg, rgba(124,58,237,.95), rgba(34,197,94,.85));
      text-decoration:none;
      display:inline-flex;
      align-items:center;
      gap: 8px;
    }
    .btn.secondary{
      background: rgba(255,255,255,.08);
      border:1px solid rgba(255,255,255,.14);
      color: rgba(255,255,255,.9);
      font-weight: 600;
    }

    /* responsive */
    @media (max-width: 980px){
      .layout{ grid-template-columns: 1fr; }
      .sidebar{
        height:auto; position:relative;
        border-right:0; border-bottom:1px solid var(--stroke);
      }
    }
  </style>
</head>
<body>

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
    <p><b><?= htmlspecialchars($last['subject']) ?></b></p>
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
