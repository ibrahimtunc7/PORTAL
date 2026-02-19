<?php
declare(strict_types=1);
require __DIR__ . '/auth.php';

require_login();
$user = current_user();
if (!$user) { header('Location: ' . APP_BASE . '/logout.php'); exit; }

$created = isset($_GET['created']);
?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Teklif Talebi (Kombine)</title>
  <link rel="stylesheet" href="<?= APP_BASE ?>/assets/styles.css">
</head>
<body class="page-request-new">

<div class="top">
  <div>
    <div style="font-weight:800;">Perga Portal</div>
    <div class="muted">Kombine Teklif Talebi</div>
  </div>
  <div style="display:flex;gap:12px;align-items:center;">
    <a href="<?= APP_BASE ?>/index.php">← Panel</a>
    <a href="<?= APP_BASE ?>/my_requests.php">Talepler</a>
    <a href="<?= APP_BASE ?>/logout.php">Çıkış</a>
  </div>
</div>

<div class="card">
  <h2 style="margin:0 0 6px;">Kombine Teklif Talebi</h2>
  <div class="muted">Birden fazla ürünü sepete ekleyip tek seferde talep oluştur.</div>

  <?php if ($created): ?>
    <div class="ok">✅ Talebiniz başarıyla alındı.</div>
  <?php endif; ?>

  <form method="post" action="<?= APP_BASE ?>/request_save.php" id="mainForm">

    <!-- Ürün ekleme alanı -->
    <div class="box">
      <div style="font-weight:900;margin-bottom:6px;">Ürün Ekle</div>
      <div class="muted">Kategori + ürün seç → teknik detay gir → <b>+ Ürünü Ekle</b></div>

      <div class="row" style="margin-top:10px;">
        <div>
          <label>Kategori</label>
          <select id="category">
            <option value="">Seçiniz</option>
            <option>Ambalaj</option>
            <option>Hırdavat</option>
            <option>Sarf Malzemeleri</option>
            <option>Diğer</option>
          </select>
        </div>
        <div>
          <label>Ürün</label>
          <select id="product">
            <option value="">Önce kategori seçin</option>
          </select>
        </div>
      </div>

      <label>Miktar</label>
      <input id="qty" type="text" placeholder="Örn: 5000 adet / 2 ton / 10 koli">

      <label>Teknik Özellikler</label>
      <div id="specsBox" style="background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.12);border-radius:12px;padding:12px;">
        <div class="muted">Ürün seçince ilgili teknik alanlar otomatik gelir.</div>
        <div id="specsFields" style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:10px;"></div>
      </div>

      <div style="margin-top:12px;display:flex;gap:10px;flex-wrap:wrap;">
        <button type="button" class="btn primary" id="addItemBtn">+ Ürünü Ekle</button>
        <button type="button" class="btn" id="clearItemsBtn">Listeyi Temizle</button>
      </div>

      <div class="hint">İpucu: “Diğer” seçip ürünü açıklama kısmında detaylandırabilirsiniz.</div>
    </div>

    <!-- Eklenen ürünler -->
    <div class="box">
      <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;">
        <div style="font-weight:900;">Eklenen Ürünler</div>
        <div class="muted">Toplam: <b id="itemCount">0</b></div>
      </div>

      <div id="itemsList" class="items">
        <div class="muted">Henüz ürün eklenmedi.</div>
      </div>
    </div>

    <!-- Genel bilgiler -->
    <div class="box">
      <div style="font-weight:900;margin-bottom:6px;">Genel Bilgiler</div>

      <div class="row">
        <div>
          <label>Şehir (opsiyonel)</label>
          <input name="city" placeholder="Örn: İstanbul">
        </div>
        <div>
          <label>Termin / İhtiyaç Tarihi (opsiyonel)</label>
          <input name="deadline" placeholder="Örn: 3 gün içinde / 2026-03-01">
        </div>
      </div>

      <label>Genel Not (opsiyonel)</label>
      <textarea name="details" rows="4" placeholder="Tüm ürünler için geçerli notlar (teslimat, kalite, marka tercihi vb.)"></textarea>
    </div>

    <!-- Hidden: Kombine ürün listesi -->
    <input type="hidden" name="items_json" id="items_json" value="[]">

    <!-- Formu göndermek -->
    <div style="margin-top:14px;display:flex;gap:10px;flex-wrap:wrap;justify-content:flex-end;">
      <a class="btn" href="<?= APP_BASE ?>/my_requests.php">İptal</a>
      <button class="btn primary" type="submit" id="submitBtn">Toplu Talebi Gönder</button>
    </div>

    <div class="hint">
      Not: En az 1 ürün ekleyip göndermen önerilir. (İstersen 0 ürünle de gönderebilirsin; ama platform mantığı için 1+ daha doğru.)
    </div>
  </form>
</div>

<script>
// 1) Kategori->Ürün listeleri
const data = {
  "Ambalaj":[ "PP Çember","Kompozit Çember","Polyester Çember","Streç","Bant","Köşebent" ],
  "Hırdavat":[ "Vida","Civata","Somun","Matkap Ucu","El Aletleri" ],
  "Sarf Malzemeleri":[ "Eldiven","Maske","Temizlik Ürünleri","Sprey/Yağ" ],
  "Diğer":[ "Diğer" ]
};

// 2) Ürün->Teknik alan şablonları
const specTemplates = {
  "PP Çember": [
    {k:"en_mm", label:"En (mm)", placeholder:"Örn: 12"},
    {k:"kalinlik_mm", label:"Kalınlık (mm)", placeholder:"Örn: 0.80"},
    {k:"cekme_kg", label:"Çekme (kg)", placeholder:"Örn: 1400"},
    {k:"renk", label:"Renk", placeholder:"Örn: Beyaz / Siyah"}
  ],
  "Kompozit Çember": [
    {k:"en_mm", label:"En (mm)", placeholder:"Örn: 16"},
    {k:"uzunluk_m", label:"Top uzunluğu (m)", placeholder:"Örn: 850"},
    {k:"tokalama", label:"Tokalama", placeholder:"Tokalı / Tokasız"},
    {k:"renk", label:"Renk", placeholder:"Örn: Beyaz"}
  ],
  "Polyester Çember": [
    {k:"en_mm", label:"En (mm)", placeholder:"Örn: 16"},
    {k:"kalinlik_mm", label:"Kalınlık (mm)", placeholder:"Örn: 0.80"},
    {k:"cekme_kg", label:"Çekme (kg)", placeholder:"Örn: 600"},
    {k:"renk", label:"Renk", placeholder:"Örn: Yeşil"}
  ],
  "Streç": [
    {k:"tip", label:"Tip", placeholder:"El tipi / Makine tipi"},
    {k:"micron", label:"Micron", placeholder:"Örn: 17 / 23"},
    {k:"en_cm", label:"En (cm)", placeholder:"Örn: 50"},
    {k:"core", label:"Core", placeholder:"38 / 50 / 76"}
  ],
  "Bant": [
    {k:"tip", label:"Bant tipi", placeholder:"Akrilik / Hotmelt / Solvent"},
    {k:"en_mm", label:"En (mm)", placeholder:"Örn: 45"},
    {k:"uzunluk_m", label:"Uzunluk (m)", placeholder:"Örn: 100"},
    {k:"renk", label:"Renk", placeholder:"Şeffaf / Taba"}
  ],
  "Köşebent": [
    {k:"kalinlik_mm", label:"Kalınlık (mm)", placeholder:"Örn: 3"},
    {k:"kol_olcusu", label:"Kol ölçüsü", placeholder:"Örn: 50x50"},
    {k:"boy_m", label:"Boy (m)", placeholder:"Örn: 1.2"},
    {k:"adet", label:"Adet", placeholder:"Örn: 500"}
  ],
  "Vida": [
    {k:"cap", label:"Çap", placeholder:"Örn: M6"},
    {k:"boy", label:"Boy", placeholder:"Örn: 30"},
    {k:"malzeme", label:"Malzeme", placeholder:"Paslanmaz / Çelik"},
    {k:"adet", label:"Adet", placeholder:"Örn: 2000"}
  ],
  "Civata": [
    {k:"cap", label:"Çap", placeholder:"Örn: M10"},
    {k:"boy", label:"Boy", placeholder:"Örn: 50"},
    {k:"sinif", label:"Sınıf", placeholder:"8.8 / 10.9"},
    {k:"adet", label:"Adet", placeholder:"Örn: 500"}
  ],
  "Eldiven": [
    {k:"tip", label:"Tip", placeholder:"Nitril / Lateks / İş eldiveni"},
    {k:"beden", label:"Beden", placeholder:"S / M / L / XL"},
    {k:"adet", label:"Adet", placeholder:"Örn: 1000"},
    {k:"kutu", label:"Kutu içi", placeholder:"Örn: 100"}
  ],
  "Diğer": []
};

const categoryEl = document.getElementById('category');
const productEl  = document.getElementById('product');
const qtyEl      = document.getElementById('qty');
const specsFields= document.getElementById('specsFields');

const addBtn     = document.getElementById('addItemBtn');
const clearBtn   = document.getElementById('clearItemsBtn');

const itemsList  = document.getElementById('itemsList');
const itemCount  = document.getElementById('itemCount');
const itemsJsonEl= document.getElementById('items_json');
const mainForm   = document.getElementById('mainForm');

let items = [];

function renderProducts(){
  productEl.innerHTML = "";
  const arr = data[categoryEl.value] || [];
  productEl.appendChild(new Option("Seçiniz",""));
  arr.forEach(p => productEl.appendChild(new Option(p,p)));
  renderSpecs("");
}

function renderSpecs(product){
  specsFields.innerHTML = "";
  const fields = specTemplates[product] || [];

  if (!fields.length){
    specsFields.innerHTML =
      `<div style="grid-column:1/-1;color:rgba(255,255,255,.65);font-size:13px;">
        Bu ürün için otomatik teknik alan yok. Lütfen genel not kısmında detay yazın.
      </div>`;
    return;
  }

  fields.forEach(f=>{
    const wrap = document.createElement('div');
    wrap.innerHTML = `
      <label style="margin-top:0;font-size:12px;color:rgba(255,255,255,.78)">${f.label}</label>
      <input data-spec-key="${f.k}" placeholder="${f.placeholder}"
        style="width:100%;padding:12px;margin-top:6px;border-radius:10px;border:1px solid rgba(255,255,255,.2);background:#111;color:#fff;">
    `;
    specsFields.appendChild(wrap);
  });
}

function getSpecsObject(){
  const obj = {};
  specsFields.querySelectorAll('input[data-spec-key]').forEach(inp=>{
    const k = inp.getAttribute('data-spec-key');
    const v = (inp.value || '').trim();
    if (v !== '') obj[k] = v;
  });
  return obj;
}

function escapeHtml(s){
  return String(s ?? '')
    .replaceAll('&','&amp;')
    .replaceAll('<','&lt;')
    .replaceAll('>','&gt;')
    .replaceAll('"','&quot;')
    .replaceAll("'","&#039;");
}

function renderItems(){
  itemCount.textContent = String(items.length);
  itemsJsonEl.value = JSON.stringify(items);

  if (items.length === 0){
    itemsList.innerHTML = `<div class="muted">Henüz ürün eklenmedi.</div>`;
    return;
  }

  itemsList.innerHTML = "";
  items.forEach((it, idx)=>{
    const specs = it.specs || {};
    const chips = Object.keys(specs).length
      ? Object.entries(specs).map(([k,v])=> `<span class="chip">${escapeHtml(k)}: <b>${escapeHtml(v)}</b></span>`).join("")
      : `<span class="chip">Teknik detay yok</span>`;

    const row = document.createElement('div');
    row.className = "item";
    row.innerHTML = `
      <div style="min-width:0;">
        <div class="t">${escapeHtml(it.category)} / ${escapeHtml(it.product)}</div>
        <div class="s">Miktar: <b>${escapeHtml(it.quantity || '-')}</b></div>
        <div class="chips">${chips}</div>
      </div>
      <div style="display:flex;gap:8px;">
        <button type="button" class="btn" data-remove="${idx}">Sil</button>
      </div>
    `;
    itemsList.appendChild(row);
  });

  itemsList.querySelectorAll('button[data-remove]').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      const i = Number(btn.getAttribute('data-remove'));
      items.splice(i,1);
      renderItems();
    });
  });
}

// events
categoryEl.addEventListener('change', renderProducts);
productEl.addEventListener('change', ()=> renderSpecs(productEl.value));

addBtn.addEventListener('click', ()=>{
  const category = (categoryEl.value || '').trim();
  const product  = (productEl.value || '').trim();
  const qty      = (qtyEl.value || '').trim();
  const specs    = getSpecsObject();

  if (!category || !product){
    alert("Lütfen kategori ve ürün seçin.");
    return;
  }

  // miktar boşsa uyar
  if (!qty){
    if (!confirm("Miktar boş. Yine de eklemek istiyor musun?")) return;
  }

  items.push({ category, product, quantity: qty, specs });

  // reset
  qtyEl.value = "";
  specsFields.querySelectorAll('input').forEach(i=> i.value = "");
  renderItems();
});

clearBtn.addEventListener('click', ()=>{
  if (!confirm("Tüm eklenen ürünleri silmek istiyor musun?")) return;
  items = [];
  renderItems();
});

// submit kontrolü (en az 1 ürün öner)
mainForm.addEventListener('submit', (e)=>{
  if (items.length === 0){
    if (!confirm("Hiç ürün eklemedin. Yine de talebi göndermek istiyor musun?")) {
      e.preventDefault();
      return;
    }
  }
});

// init
renderItems();
</script>

</body>
</html>
