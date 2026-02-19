<?php
declare(strict_types=1);
require __DIR__ . '/auth.php';

require_login();
$me = current_user();
if (!$me || ($me['role'] ?? '') !== 'admin') {
  http_response_code(403);
  die('Yetkisiz erişim');
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $csrf = (string)($_POST['csrf'] ?? '');
  if (!verify_csrf($csrf)) {
    $error = 'Güvenlik doğrulaması başarısız.';
  } else {
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $full_name = trim((string)($_POST['full_name'] ?? ''));
    $role = trim((string)($_POST['role'] ?? 'user'));
    $pass1 = (string)($_POST['password'] ?? '');
    $pass2 = (string)($_POST['password2'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $error = 'Geçerli bir email girin.';
    } elseif ($pass1 === '' || strlen($pass1) < 8) {
      $error = 'Şifre en az 8 karakter olmalı.';
    } elseif ($pass1 !== $pass2) {
      $error = 'Şifreler eşleşmiyor.';
    } elseif (!in_array($role, ['user','admin'], true)) {
      $error = 'Geçersiz rol.';
    } else {
      try {
        // Email var mı?
        $chk = db()->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
        $chk->execute([$email]);
        if ($chk->fetch()) {
          $error = 'Bu email zaten kayıtlı.';
        } else {
          $hash = password_hash($pass1, PASSWORD_DEFAULT);

          $stmt = db()->prepare("
            INSERT INTO users (email, password_hash, full_name, role, is_active)
            VALUES (?, ?, ?, ?, 1)
          ");
          $stmt->execute([$email, $hash, $full_name !== '' ? $full_name : null, $role]);

          $success = 'Kullanıcı oluşturuldu: ' . htmlspecialchars($email);
        }
      } catch (Throwable $e) {
        $error = 'DB hatası: ' . $e->getMessage();
      }
    }
  }
}

// Kullanıcı listesi
$users = db()->query("SELECT id,email,full_name,role,is_active,created_at FROM users ORDER BY id DESC")->fetchAll();
?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin - Kullanıcılar</title>
  <style>
    :root{--bg:#0b1020;--bg2:#0f1a3a;--stroke:rgba(255,255,255,.14);--text:rgba(255,255,255,.92);--muted:rgba(255,255,255,.65);--shadow:0 18px 60px rgba(0,0,0,.55);--radius:18px;}
    *{box-sizing:border-box}
    body{margin:0;font-family:ui-sans-serif,system-ui,-apple-system,Segoe UI,Roboto,Arial;color:var(--text);
      background: radial-gradient(1200px 800px at 10% 10%, rgba(124,58,237,.18), transparent 60%),
                 radial-gradient(900px 650px at 90% 20%, rgba(34,197,94,.14), transparent 55%),
                 linear-gradient(160deg,var(--bg),var(--bg2));
      min-height:100vh; padding:20px;
    }
    a{color:rgba(255,255,255,.88);text-decoration:none}
    a:hover{text-decoration:underline}
    .top{max-width:1100px;margin:0 auto 14px;display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:center}
    .card{max-width:1100px;margin:0 auto;border-radius:var(--radius);border:1px solid var(--stroke);
      background:linear-gradient(180deg,rgba(255,255,255,.10),rgba(255,255,255,.05));box-shadow:var(--shadow);padding:18px;}
    .grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:12px}
    label{display:block;font-size:13px;color:rgba(255,255,255,.78);margin:0 0 6px}
    input,select{width:100%;padding:12px;border-radius:14px;border:1px solid rgba(255,255,255,.18);background:rgba(10,14,28,.40);color:rgba(255,255,255,.92);outline:none}
    .full{grid-column:1/-1}
    .btn{border:0;cursor:pointer;border-radius:14px;padding:12px 14px;font-weight:800;color:white;
      background:linear-gradient(135deg, rgba(124,58,237,.95), rgba(34,197,94,.85));}
    .alert{margin:12px 0;padding:12px;border-radius:14px;border:1px solid rgba(255,77,109,.35);background:rgba(255,77,109,.12)}
    .ok{margin:12px 0;padding:12px;border-radius:14px;border:1px solid rgba(34,197,94,.35);background:rgba(34,197,94,.12)}
    table{width:100%;border-collapse:collapse;margin-top:12px}
    th,td{padding:12px;border-bottom:1px solid rgba(255,255,255,.10);text-align:left;font-size:13px}
    th{color:rgba(255,255,255,.78);font-weight:800}
    .badge{display:inline-block;padding:6px 10px;border-radius:999px;border:1px solid rgba(255,255,255,.16);background:rgba(255,255,255,.06);font-size:12px}
    @media(max-width:900px){.grid{grid-template-columns:1fr}}
  </style>
</head>
<body>

<div class="top">
  <div>
    <div style="font-weight:900;">Perga Portal • Admin</div>
    <div style="color:var(--muted);font-size:12px;">Kullanıcı ekleme / liste</div>
  </div>
  <div style="display:flex;gap:12px;align-items:center;">
    <a href="<?= APP_BASE ?>/index.php">← Panel</a>
    <a href="<?= APP_BASE ?>/logout.php">Çıkış</a>
  </div>
</div>

<div class="card">
  <h2 style="margin:0 0 6px;">Yeni Kullanıcı Oluştur</h2>
  <div style="color:var(--muted);font-size:13px;">Kayıt ekranı yok. Kullanıcıları sadece admin ekler.</div>

  <?php if ($error): ?><div class="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="ok"><?= $success ?></div><?php endif; ?>

  <form method="post" autocomplete="off">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
    <div class="grid">
      <div>
        <label>Ad Soyad</label>
        <input name="full_name" placeholder="Örn: Ahmet Yılmaz">
      </div>
      <div>
        <label>Email (Giriş için)</label>
        <input name="email" type="email" required placeholder="ornek@firma.com">
      </div>
      <div>
        <label>Rol</label>
        <select name="role">
          <option value="user">user</option>
          <option value="admin">admin</option>
        </select>
      </div>
      <div>
        <label>Şifre (min 8)</label>
        <input name="password" type="password" required>
      </div>
      <div class="full">
        <label>Şifre (tekrar)</label>
        <input name="password2" type="password" required>
      </div>
      <div class="full">
        <button class="btn" type="submit">Kullanıcı Oluştur</button>
      </div>
    </div>
  </form>
</div>

<div class="card" style="margin-top:14px;">
  <h2 style="margin:0 0 6px;">Kullanıcılar</h2>
  <div style="color:var(--muted);font-size:13px;">Toplam: <?= count($users) ?></div>

  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Email</th>
        <th>Ad Soyad</th>
        <th>Rol</th>
        <th>Aktif</th>
        <th>Kayıt</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($users as $u): ?>
        <tr>
          <td><?= (int)$u['id'] ?></td>
          <td><?= htmlspecialchars((string)$u['email']) ?></td>
          <td><?= htmlspecialchars((string)($u['full_name'] ?? '')) ?></td>
          <td><span class="badge"><?= htmlspecialchars((string)$u['role']) ?></span></td>
          <td><?= ((int)$u['is_active'] === 1) ? 'Evet' : 'Hayır' ?></td>
          <td><?= htmlspecialchars((string)$u['created_at']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

</body>
</html>
