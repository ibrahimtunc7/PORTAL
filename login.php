<?php
declare(strict_types=1);
require __DIR__ . '/auth.php';

start_session();

if (is_logged_in()) {
  header('Location: ' . APP_BASE . '/index.php');
  exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim((string)($_POST['email'] ?? ''));
  $password = (string)($_POST['password'] ?? '');
  $csrf = (string)($_POST['csrf'] ?? '');

  if (!verify_csrf($csrf)) {
    $error = 'Güvenlik doğrulaması başarısız. Lütfen tekrar deneyin.';
  } elseif ($email === '' || $password === '') {
    $error = 'Email ve şifre zorunludur.';
  } elseif (login_user($email, $password)) {
    header('Location: ' . APP_BASE . '/index.php');
    exit;
  } else {
    $error = 'Email veya şifre hatalı.';
  }
}
?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Portal Giriş</title>
  <meta name="color-scheme" content="light">
  <link rel="stylesheet" href="<?= APP_BASE ?>/assets/styles.css">
</head>

<body class="page-login">
  <div class="wrap">
    <!-- SOL: Tanıtım -->
    <section class="hero" aria-label="Portal Bilgisi">
      <div>
        <div class="brand">
          <!-- Eğer logo eklemek istersen:
               <img src="assets/logo.png" alt="Logo" style="width:44px;height:44px;border-radius:12px"> -->
          <div class="logo">P</div>
          <div>
            <h1>Perga Endüstriyel Portal</h1>
            <p>Yetkili kullanıcı girişi</p>
          </div>
        </div>

        <div>
          <div class="headline">Hızlı. Güvenli.<br>Kurumsal erişim.</div>
          <p class="sub">
            Teklifler, dökümanlar ve müşteri işlemleri için tek panel.
            Giriş yaparak size özel alanınıza erişebilirsiniz.
          </p>

          <div class="chips">
            <div class="chip">🔒 Şifreleme (bcrypt)</div>
            <div class="chip">⚡ Hızlı erişim</div>
            <div class="chip">📄 Müşteri evrakları</div>
            <div class="chip">📬 Teklif talepleri</div>
          </div>
        </div>
      </div>

      <div class="foot">
        <span>© <?= date('Y') ?> Perga Endüstriyel</span>
        <span><a href="/">Anasayfa</a></span>
      </div>
    </section>

    <!-- SAĞ: Login -->
    <section class="card" aria-label="Giriş Formu">
      <h2>Giriş Yap</h2>
      <p class="hint">Hesabınıza erişmek için bilgilerinizi girin.</p>

      <?php if ($error): ?>
        <div class="alert"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="post" autocomplete="off" novalidate>
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">

        <div class="field">
          <label for="email">Email</label>
          <input id="email" name="email" type="email" placeholder="ornek@firma.com" required>
        </div>

        <div class="field">
          <label for="password">Şifre</label>
          <input id="password" name="password" type="password" placeholder="••••••••" required>
        </div>

        <button class="btn" type="submit">Portala Giriş</button>

        <div class="divider">
          <span>Girişte sorun mu var?</span>
          <span class="small"><a href="mailto:info@siteadresin.com">Destek</a></span>
        </div>
      </form>
    </section>
  </div>
</body>
</html>
