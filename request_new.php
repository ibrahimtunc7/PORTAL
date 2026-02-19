<?php
declare(strict_types=1);
require __DIR__ . '/auth.php';

require_login();
$user = current_user();
?>

<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Yeni Talep Oluştur</title>

<style>
body{
  font-family:system-ui;
  background:#0b1020;
  color:#fff;
  margin:0;
  padding:20px;
}
.card{
  max-width:700px;
  margin:auto;
  background:rgba(255,255,255,.05);
  padding:20px;
  border-radius:16px;
}
label{display:block;margin-top:15px}
select,input,textarea{
  width:100%;
  padding:12px;
  margin-top:5px;
  border-radius:10px;
  border:1px solid rgba(255,255,255,.2);
  background:#111;
  color:#fff;
}
button{
  margin-top:20px;
  width:100%;
  padding:14px;
  border-radius:12px;
  border:0;
  background:linear-gradient(135deg,#7c3aed,#22c55e);
  color:#fff;
  font-weight:700;
  cursor:pointer;
}
</style>
</head>
<body>

<div class="card">
<h2>Teklif Talebi Oluştur</h2>

<form method="post" action="request_save.php">

<label>Kategori</label>
<select name="category" id="category" required>
  <option value="">Seçiniz</option>
  <option>Ambalaj</option>
  <option>Hırdavat</option>
  <option>Sarf Malzemeleri</option>
  <option>Diğer</option>
</select>

<label>Alt Kategori</label>
<select name="subcategory" id="subcategory" required>
  <option value="">Önce kategori seçin</option>
</select>

<label>Miktar</label>
<input name="quantity" placeholder="Örn: 2 ton / 500 adet">

<label>Şehir</label>
<input name="city" placeholder="Örn: İstanbul">

<label>Termin</label>
<input name="deadline" placeholder="Örn: 1 hafta">

<label>Açıklama</label>
<textarea name="details" placeholder="Detayları yazınız..."></textarea>

<button>Talebi Oluştur</button>

</form>
</div>

<script>
const data = {
  "Ambalaj":[
    "PP Çember",
    "Kompozit Çember",
    "Polyester Çember",
    "Streç",
    "Bant",
    "Köşebent"
  ],
  "Hırdavat":[
    "Vida",
    "Civata",
    "Somun",
    "Matkap Ucu",
    "El Aletleri"
  ],
  "Sarf Malzemeleri":[
    "Eldiven",
    "Maske",
    "Temizlik Ürünleri",
    "Sprey/Yağ"
  ],
  "Diğer":[
    "Diğer"
  ]
};

document.getElementById('category').onchange = function(){
  let sub = document.getElementById('subcategory');
  sub.innerHTML = "";

  data[this.value].forEach(function(item){
    let opt = document.createElement("option");
    opt.text = item;
    opt.value = item;
    sub.appendChild(opt);
  });
}
</script>

</body>
</html>
