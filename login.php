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
  <style>
    :root{
      --bg1:#0b1020;
      --bg2:#0f1a3a;
      --card: rgba(255,255,255,.10);
      --card2: rgba(255,255,255,.06);
      --stroke: rgba(255,255,255,.14);
      --text: rgba(255,255,255,.92);
      --muted: rgba(255,255,255,.68);
      --accent:#7c3aed;   /* mor */
      --accent2:#22c55e;  /* yeşil */
      --danger:#ff4d6d;
      --shadow: 0 18px 60px rgba(0,0,0,.55);
      --radius: 18px;
    }
    *{box-sizing:border-box}
    body{
      margin:0;
      font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Arial, "Apple Color Emoji","Segoe UI Emoji";
      color: var(--text);
      min-height:100vh;
      background:
        radial-gradient(1200px 800px at 10% 10%, rgba(124,58,237,.35), transparent 60%),
        radial-gradient(900px 650px at 90% 20%, rgba(34,197,94,.22), transparent 55%),
        radial-gradient(900px 650px at 70% 90%, rgba(59,130,246,.20), transparent 55%),
        linear-gradient(160deg, var(--bg1), var(--bg2));
      display:flex;
      align-items:center;
      justify-content:center;
      padding: 28px 14px;
    }

    .wrap{
      width: min(980px, 100%);
      display:grid;
      grid-template-columns: 1.1fr 0.9fr;
      gap: 18px;
      align-items:stretch;
    }

    .hero, .card{
      border-radius: var(--radius);
      border:1px solid var(--stroke);
      background: linear-gradient(180deg, var(--card), var(--card2));
      box-shadow: var(--shadow);
      overflow:hidden;
      position:relative;
    }

    /* Sol alan (hero) */
    .hero{
      padding: 28px;
      display:flex;
      flex-direction:column;
      justify-content:space-between;
      min-height: 520px;
    }
    .brand{
      display:flex; align-items:center; gap:12px;
    }
    .logo{
      width:44px; height:44px;
      border-radius: 12px;
      background: linear-gradient(135deg, rgba(124,58,237,.9), rgba(34,197,94,.75));
      display:flex; align-items:center; justify-content:center;
      font-weight:800;
      letter-spacing:.5px;
    }
    .brand h1{
      margin:0;
      font-size: 18px;
      letter-spacing:.2px;
    }
    .brand p{
      margin:3px 0 0;
      color: var(--muted);
      font-size: 13px;
    }

    .headline{
      margin: 26px 0 0;
      font-size: 40px;
      line-height:1.05;
      letter-spacing:-.8px;
    }
    .sub{
      margin: 12px 0 0;
      color: var(--muted);
      font-size: 15px;
      max-width: 52ch;
    }

    .chips{
      margin-top: 18px;
      display:flex; flex-wrap:wrap; gap:10px;
    }
    .chip{
      padding: 9px 12px;
      border-radius: 999px;
      border:1px solid rgba(255,255,255,.16);
      background: rgba(255,255,255,.06);
      color: rgba(255,255,255,.86);
      font-size: 13px;
      backdrop-filter: blur(10px);
    }

    .foot{
      display:flex;
      gap: 10px;
      align-items:center;
      justify-content:space-between;
      color: var(--muted);
      font-size: 12px;
      margin-top: 22px;
    }
    .foot a{ color: rgba(255,255,255,.82); text-decoration:none; }
    .foot a:hover{ text-decoration:underline; }

    /* Sağ alan (login card) */
    .card{
      padding: 26px;
      display:flex;
      flex-direction:column;
      justify-content:center;
      min-height: 520px;
    }
    .card h2{
      margin:0 0 6px;
      font-size: 20px;
      letter-spacing:.2px;
    }
    .card .hint{
      margin:0 0 18px;
      color: var(--muted);
      font-size: 13px;
    }

    .alert{
      border:1px solid rgba(255,77,109,.35);
      background: rgba(255,77,109,.12);
      color: rgba(255,255,255,.92);
      padding: 12px 12px;
      border-radius: 14px;
      margin: 0 0 14px;
      font-size: 13px;
    }

    .field{ margin: 10px 0; }
    label{
      display:block;
      font-size: 13px;
      color: rgba(255,255,255,.78);
      margin: 0 0 6px;
    }
    input{
      width: 100%;
      padding: 12px 12px;
      border-radius: 14px;
      border: 1px solid rgba(255,255,255,.18);
      background: rgba(10,14,28,.40);
      color: rgba(255,255,255,.92);
      outline: none;
      transition: border .15s ease, box-shadow .15s ease, transform .05s ease;
    }
    input::placeholder{ color: rgba(255,255,255,.40); }
    input:focus{
      border-color: rgba(124,58,237,.70);
      box-shadow: 0 0 0 4px rgba(124,58,237,.18);
    }

    .row{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap: 12px;
      margin-top: 10px;
    }
    .small{
      font-size: 12px;
      color: var(--muted);
    }
    .small a{ color: rgba(255,255,255,.84); text-decoration:none; }
    .small a:hover{ text-decoration:underline; }

    .btn{
      margin-top: 14px;
      width: 100%;
      border: 0;
      padding: 12px 14px;
      border-radius: 14px;
      cursor: pointer;
      color: white;
      font-weight: 700;
      letter-spacing:.2px;
      background: linear-gradient(135deg, rgba(124,58,237,.95), rgba(34,197,94,.85));
      box-shadow: 0 12px 28px rgba(0,0,0,.35);
      transition: transform .06s ease, filter .15s ease;
    }
    .btn:hover{ filter: brightness(1.06); }
    .btn:active{ transform: translateY(1px); }

    .divider{
      margin: 18px 0 0;
      border-top: 1px solid rgba(255,255,255,.14);
      padding-top: 14px;
      color: var(--muted);
      font-size: 12px;
      display:flex;
      justify-content:space-between;
      gap: 10px;
      flex-wrap:wrap;
    }

    /* responsive */
    @media (max-width: 880px){
      .wrap{ grid-template-columns: 1fr; }
      .hero{ min-height: 0; }
      .card{ min-height: 0; }
      .headline{ font-size: 34px; }
    }
  </style>
</head>

<body>
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
