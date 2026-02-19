<?php
declare(strict_types=1);
require __DIR__ . '/auth.php';

require_login();
$user = current_user();
if (!$user) { header('Location: ' . APP_BASE . '/logout.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: ' . APP_BASE . '/request_new.php');
  exit;
}

function table_has_column(string $table, string $col): bool {
  $st = db()->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
  $st->execute([$col]);
  return (bool)$st->fetch();
}

// --------------------
// INPUTS (tekli + kombine)
// --------------------
$category    = trim((string)($_POST['category'] ?? ''));
$subcategory = trim((string)($_POST['subcategory'] ?? ''));
$quantity    = trim((string)($_POST['quantity'] ?? ''));
$city        = trim((string)($_POST['city'] ?? ''));
$deadline    = trim((string)($_POST['deadline'] ?? ''));
$details_in  = trim((string)($_POST['details'] ?? ''));

// Tekli teknik detay (varsa)
$specsJson = trim((string)($_POST['specs_json'] ?? ''));
if ($specsJson === '') $specsJson = '{}';
$specsDecoded = json_decode($specsJson, true);
if (!is_array($specsDecoded)) { $specsJson = '{}'; $specsDecoded = []; }

// Kombine ürün listesi (varsa)
$itemsJson = trim((string)($_POST['items_json'] ?? ''));
if ($itemsJson === '') $itemsJson = '[]';
$itemsDecoded = json_decode($itemsJson, true);
if (!is_array($itemsDecoded)) { $itemsJson = '[]'; $itemsDecoded = []; }

// Kombine mi?
$isCombined = (is_array($itemsDecoded) && count($itemsDecoded) > 0);

// --------------------
// VALIDATION
// --------------------
// Kombine ise: en az 1 item var, kategori/subcategory zorunlu değil.
// Tekli ise: kategori + subcategory zorunlu.
if (!$isCombined) {
  if ($category === '' || $subcategory === '') {
    header('Location: ' . APP_BASE . '/request_new.php?err=1');
    exit;
  }
}

// --------------------
// SUBJECT + DETAILS AUTO
// --------------------
$details_parts = [];

if ($isCombined) {
  $subject_auto = 'Kombine Talep (' . count($itemsDecoded) . ' ürün)';

  $details_parts[] = "Kombine Talep";
  if ($city !== '')     $details_parts[] = "Şehir: $city";
  if ($deadline !== '') $details_parts[] = "Termin: $deadline";

  $details_parts[] = "\nÜrün Listesi:";
  $i = 1;
  foreach ($itemsDecoded as $it) {
    if (!is_array($it)) continue;

    $cat = trim((string)($it['category'] ?? ''));
    $prd = trim((string)($it['product'] ?? ''));
    $qty = trim((string)($it['quantity'] ?? ''));

    $line = $i . ") ";
    $line .= ($cat !== '' ? $cat : 'Kategori') . ' / ' . ($prd !== '' ? $prd : 'Ürün');
    if ($qty !== '') $line .= " | Miktar: " . $qty;

    $details_parts[] = $line;

    $specs = $it['specs'] ?? [];
    if (is_array($specs) && count($specs) > 0) {
      foreach ($specs as $k => $v) {
        $k = trim((string)$k);
        $v = trim((string)$v);
        if ($k === '' || $v === '') continue;
        $details_parts[] = "   - $k: $v";
      }
    }
    $i++;
  }

  if ($details_in !== '') {
    $details_parts[] = "\nGenel Not:\n" . $details_in;
  }

} else {
  $productType = $subcategory;

  $subject_auto = $category . ' / ' . $subcategory;
  if ($quantity !== '') $subject_auto .= ' - ' . $quantity;

  $details_parts[] = "Kategori: $category";
  $details_parts[] = "Ürün: $subcategory";
  if ($quantity !== '') $details_parts[] = "Miktar: $quantity";
  if ($city !== '')     $details_parts[] = "Şehir: $city";
  if ($deadline !== '') $details_parts[] = "Termin: $deadline";

  if (is_array($specsDecoded) && count($specsDecoded) > 0) {
    $details_parts[] = "\nTeknik Özellikler:";
    foreach ($specsDecoded as $k => $v) {
      $k = trim((string)$k);
      $v = trim((string)$v);
      if ($k === '' || $v === '') continue;
      $details_parts[] = "- $k: $v";
    }
  }

  if ($details_in !== '') $details_parts[] = "\nAçıklama:\n" . $details_in;
}

$details_auto = implode("\n", $details_parts);

// --------------------
// DYNAMIC INSERT (kolon varsa yaz)
// --------------------
$table = 'quote_requests';
$cols = ['user_id'];
$vals = [(int)$user['id']];

// Yeni kolonlar (varsa)
if (table_has_column($table, 'category'))     { $cols[]='category';     $vals[] = ($category!==''?$category:null); }
if (table_has_column($table, 'subcategory'))  { $cols[]='subcategory';  $vals[] = ($subcategory!==''?$subcategory:null); }

// product_type varsa: tekli halde subcategory, kombinede "combined"
if (table_has_column($table, 'product_type')) {
  $cols[]='product_type';
  $vals[] = $isCombined ? 'combined' : ($subcategory!==''?$subcategory:null);
}

if (table_has_column($table, 'quantity'))     { $cols[]='quantity';     $vals[]=($quantity!==''?$quantity:null); }
if (table_has_column($table, 'city'))         { $cols[]='city';         $vals[]=($city!==''?$city:null); }
if (table_has_column($table, 'deadline'))     { $cols[]='deadline';     $vals[]=($deadline!==''?$deadline:null); }

// specs_json (tekli) + items_json (kombine) kolonları
if (table_has_column($table, 'specs_json'))   { $cols[]='specs_json';   $vals[] = $isCombined ? null : $specsJson; }
if (table_has_column($table, 'items_json'))   { $cols[]='items_json';   $vals[] = $isCombined ? $itemsJson : null; }

// Eski kolonlar (varsa)
if (table_has_column($table, 'subject'))      { $cols[]='subject';      $vals[]=$subject_auto; }
if (table_has_column($table, 'konu'))         { $cols[]='konu';         $vals[]=$subject_auto; }
if (table_has_column($table, 'details'))      { $cols[]='details';      $vals[]=$details_auto; }
if (table_has_column($table, 'aciklama'))     { $cols[]='aciklama';     $vals[]=$details_auto; }

// status / created_at (varsa)
if (table_has_column($table, 'status'))       { $cols[]='status';       $vals[]='new'; }
if (table_has_column($table, 'created_at'))   { $cols[]='created_at'; } // NOW() ile setleyeceğiz

$placeholders = array_fill(0, count($cols), '?');

// created_at varsa NOW() kullan
if (in_array('created_at', $cols, true)) {
  $idx = array_search('created_at', $cols, true);
  $placeholders[$idx] = 'NOW()';
}

$sql = "INSERT INTO `$table` (" . implode(',', array_map(fn($c)=>"`$c`",$cols)) . ")
        VALUES (" . implode(',', $placeholders) . ")";

$stmt = db()->prepare($sql);
$stmt->execute($vals);

// Kombine talepte kullanıcıyı “taleplerim” sayfasına götürmek daha mantıklı:
header('Location: ' . APP_BASE . '/my_requests.php?created=1');
exit;
