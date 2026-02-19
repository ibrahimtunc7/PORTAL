<?php
declare(strict_types=1);
require __DIR__ . '/auth.php';

require_login();
$me = current_user();
if (!$me || (($me['role'] ?? '') !== 'admin')) {
  http_response_code(403);
  die('Yetkisiz');
}

function h(?string $s): string {
  return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

$msg = '';
$err = '';

// CREATE USER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
  $fullName = trim((string)($_POST['full_name'] ?? ''));
  $email    = trim((string)($_POST['email'] ?? ''));
  $pass     = (string)($_POST['password'] ?? '');
  $role     = trim((string)($_POST['role'] ?? 'user'));

  // Yeni alanlar
  $company  = trim((string)($_POST['company_name'] ?? ''));
  $phone    = trim((string)($_POST['phone'] ?? ''));
  $address  = trim((string)($_POST['address'] ?? ''));

  if ($email === '' || $pass === '') {
    $err = 'Email ve şifre zorunludur.';
  } elseif (!in_array($role, ['admin','user'], true)) {
    $err = 'Geçersiz rol.';
  } else {
    // Email benzersiz mi?
    $chk = db()->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
    $chk->execute([$email]);
    if ($chk->fetch()) {
      $err = 'Bu email zaten kayıtlı.';
    } else {
      $hash = password_hash($pass, PASSWORD_DEFAULT);

      // users tablonuzda bu kolonlar olmalı:
      // full_name, email, password_hash, role, company_name, phone, address
      $st = db()->prepare("
        INSERT INTO users (full_name, email, password_hash, role, company_name, phone, address)
        VALUES (?, ?, ?, ?, ?, ?, ?)
      ");
      $st->execute([
        $fullName !== '' ? $fullName : null,
        $email,
        $hash,
        $role,
        $company !== '' ? $company : null,
        $phone !== '' ? $phone : null,
        $address !== '' ? $address : null,
      ]);

      $msg = '✅ Kullanıcı oluşturuldu.';
    }
  }
}

// LIST USERS
$st = db()->prepare("SELECT id, full_name, email, role, company_name, phone, address, created_at FROM users ORDER BY id DESC");
$st->execute();
$users = $st->fetchAll();
?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Kullanıcılar (Admin)</title>
  <style>
    :root{--bg:#0b1020;--bg2:#0f1a3a;--card:rgba(255,255,255,.08);--stroke:rgba(255,255,255,.14);--text:rgba(255,255,255,.92);--muted:rgba(255,255,255,.65);--radius:18px;}
    *{box-sizing:border-box}
    body{margin:0;font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial;color:var(--text);
      background: radial-gradient(1200px 800px at 10% 10%, rgba(124,58,237,.20), transparent 60%),
                 radial-gradient(900px 650px at 90% 20%, rgba(34,197,94,.16), transparent 55%),
                 linear-gradient(160deg,var(--bg),var(--bg2));
      min-height:100vh; padding:20px;
    }
    a{color:rgba(255,255,255,.88);text-decoration:none}
    .top{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;max-width:1100px;margin:0 auto 14px;}
    .card{max-width:1100px;margin:0 auto;border-radius:var(--radius);border:1px solid var(--stroke);
      background:linear-gradient(180deg,rgba(255,255,255,.10),rgba(255,255,255,.05));padding:18px;
    }
    label{display:block;margin-top:12px;color:rgba(255,255,255,.78);font-size:13px}
    input,select,textarea{width:100%;padding:12px;border-radius:12px;border:1px solid rgba(255,255,255,.14);background:rgba(0,0,0,.22);color:#fff}
    .row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    @media (max-width:900px){ .row{grid-template-columns:1fr} }
    .btn{display:inline-flex;align-items:center;justify-content:center;margin-top:14px;padding:12px 14px;border-radius:14px;border:0;
      background:linear-gradient(135deg,#7c3aed,#22c55e);color:#fff;font-weight:900;cursor:pointer}
    .msg{margin:12px 0;padding:12px;border-radius:14px;border:1px solid rgba(34,197,94,.35);background:rgba(34,197,94,.12)}
    .err{margin:12px 0;padding:12px;border-radius:14px;border:1px solid rgba(239,68,68,.35);background:rgba(239,68,68,.12)}
    table{width:100%;border-collapse:collapse;margin-top:14px}
    th,td{padding:12px;border-bottom:1px solid rgba(255,255,255,.10);text-align:left;font-size:13px;vertical-align:top}
    th{color:rgba(255,255,255,.78);font-weight:800}
    .muted{color:var(--muted);font-size:12px;margin-top:4px}
    .badge{display:inline-block;padding:6px 10px;border-radius:999px;border:1px solid rgba(255,255,255,.16);background:rgba(255,255,255,.06);font-size:12px}
  </style>
</head>
<body>

<div class="top">
  <div>
    <div style="font-weight:900;">Perga Portal</div>
    <div class="muted">Kullanıcı Yönetimi (Admin)</div>
  </div>
  <div style="display:flex;gap:12px;align-items:center;">
    <a href="<?= APP_BASE ?>/index.php">← Panel</a>
    <a href="<?= APP_BASE ?>/logout.php">Çıkış</a>
  </div>
</div>

<div class="card">
  <h2 style="margin:0 0 6px;">Yeni Kullanıcı Ekle</h2>
  <div class="muted">Kullanıcıları sadece admin ekler.</div>

  <?php if ($msg): ?><div class="msg"><?= h($msg) ?></div><?php endif; ?>
  <?php if ($err): ?><div class="err"><?= h($err) ?></div><?php endif; ?>

  <form method="post">
    <input type="hidden" name="action" value="create">

    <div class="row">
      <div>
        <label>Yetkili Ad Soyad</label>
        <input name="full_name" placeholder="Örn: Ahmet Yılmaz">
      </div>
      <div>
        <label>Email</label>
        <input name="email" type="email" required placeholder="ornek@firma.com">
      </div>
    </div>

    <div class="row">
      <div>
        <label>Şifre</label>
        <input name="password" type="password" required placeholder="••••••••">
      </div>
      <div>
        <label>Rol</label>
        <select name="role">
          <option value="user">User (Müşteri)</option>
          <option value="admin">Admin (Yönetici)</option>
        </select>
      </div>
    </div>

    <div class="row">
      <div>
        <label>Firma Adı</label>
        <input name="company_name" placeholder="Örn: ABC Ambalaj Sanayi">
      </div>
      <div>
        <label>Telefon</label>
        <input name="phone" placeholder="Örn: 05xx xxx xx xx">
      </div>
    </div>

    <label>Adres</label>
    <textarea name="address" rows="2" placeholder="Örn: İstanbul / Tuzla"></textarea>

    <button class="btn">Kullanıcı Oluştur</button>
  </form>
</div>

<div class="card" style="margin-top:14px;">
  <h2 style="margin:0 0 6px;">Kullanıcılar</h2>

  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Firma / Yetkili</th>
        <th>Email</th>
        <th>Telefon</th>
        <th>Rol</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($users as $u): ?>
        <tr>
          <td><?= (int)$u['id'] ?></td>
          <td>
            <div style="font-weight:900;"><?= h((string)($u['company_name'] ?? '-')) ?></div>
            <div class="muted"><?= h((string)($u['full_name'] ?? '')) ?></div>
            <?php if (!empty($u['address'])): ?><div class="muted"><?= h((string)$u['address']) ?></div><?php endif; ?>
          </td>
          <td><?= h((string)$u['email']) ?></td>
          <td><?= h((string)($u['phone'] ?? '-')) ?></td>
          <td><span class="badge"><?= h((string)$u['role']) ?></span></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

</body>
</html>
