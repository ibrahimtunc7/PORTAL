<?php
declare(strict_types=1);
require __DIR__ . '/auth.php';

$me = require_admin();

function h(?string $s): string {
  return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

$msg = '';
$err = '';

// CREATE USER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
  $csrf = (string)($_POST['csrf'] ?? '');
  if (!verify_csrf($csrf)) {
    $err = 'Güvenlik doğrulaması başarısız.';
  }

  $fullName = trim((string)($_POST['full_name'] ?? ''));
  $email    = trim((string)($_POST['email'] ?? ''));
  $pass     = (string)($_POST['password'] ?? '');
  $role     = trim((string)($_POST['role'] ?? 'user'));

  // Yeni alanlar
  $company  = trim((string)($_POST['company_name'] ?? ''));
  $phone    = trim((string)($_POST['phone'] ?? ''));
  $address  = trim((string)($_POST['address'] ?? ''));

  if ($err === '' && ($email === '' || $pass === '')) {
    $err = 'Email ve şifre zorunludur.';
  } elseif ($err === '' && !in_array($role, ['admin','user'], true)) {
    $err = 'Geçersiz rol.';
  } elseif ($err === '') {
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
  <link rel="stylesheet" href="<?= APP_BASE ?>/assets/styles.css">
</head>
<body class="page-admin-users">

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
    <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
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
