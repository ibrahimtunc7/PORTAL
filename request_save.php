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

// Input
$category    = trim((string)($_POST['category'] ?? ''));
$subcategory = trim((string)($_POST['subcategory'] ?? ''));
$quantity    = trim((string)($_POST['quantity'] ?? ''));
$city        = trim((string)($_POST['city'] ?? ''));
$deadline    = trim((string)($_POST['deadline'] ?? ''));
$details_in  = trim((string)($_POST['details'] ?? ''));

if ($category === '' || $subcategory === '') {
  header('Location: ' . APP_BASE . '/request_new.php?err=1');
  exit;
}

$productType = $subcategory;

// Eski “konu/subject” için otomatik başlık üret
$subject_auto = $category . ' / ' . $subcategory;
if ($quantity !== '') $subject_auto .= ' - ' . $quantity;

// Eski “details” metnini de zenginleştir (listeleme/teklif için faydalı)
$details_parts = [];
$details_parts[] = "Kategori: $category";
$details_parts[] = "Ürün: $subcategory";
if ($quantity !== '') $details_parts[] = "Miktar: $quantity";
if ($city !== '') $details_parts[] = "Şehir: $city";
if ($deadline !== '') $details_parts[] = "Termin: $deadline";
if ($details_in !== '') $details_parts[] = "\nAçıklama:\n" . $details_in;

$details_auto = implode("\n", $details_parts);

// specs_json şimdilik boş (sonra ürün bazlı sorularla dolduracağız)
$specsJson = null;

// Kolon varlığına göre dinamik INSERT
$table = 'quote_requests';
$cols = ['user_id'];
$vals = [(int)$user['id']];

// Yeni kolonlar (varsa)
if (table_has_column($table, 'category'))     { $cols[]='category';     $vals[]=$category; }
if (table_has_column($table, 'subcategory'))  { $cols[]='subcategory';  $vals[]=$subcategory; }
if (table_has_column($table, 'product_type')) { $cols[]='product_type'; $vals[]=$productType; }
if (table_has_column($table, 'quantity'))     { $cols[]='quantity';     $vals[]=($quantity!==''?$quantity:null); }
if (table_has_column($table, 'city'))         { $cols[]='city';         $vals[]=($city!==''?$city:null); }
if (table_has_column($table, 'deadline'))     { $cols[]='deadline';     $vals[]=($deadline!==''?$deadline:null); }
if (table_has_column($table, 'specs_json'))   { $cols[]='specs_json';   $vals[]=$specsJson; }

// Eski kolonlar (varsa)
if (table_has_column($table, 'subject'))      { $cols[]='subject';      $vals[]=$subject_auto; }
if (table_has_column($table, 'konu'))         { $cols[]='konu';         $vals[]=$subject_auto; } // eğer TR isimle yaptıysan
if (table_has_column($table, 'details'))      { $cols[]='details';      $vals[]=$details_auto; }
if (table_has_column($table, 'aciklama'))     { $cols[]='aciklama';     $vals[]=$details_auto; } // TR isimli olasılık

// status / created_at (varsa)
if (table_has_column($table, 'status'))       { $cols[]='status';       $vals[]='new'; }
if (table_has_column($table, 'created_at'))   { $cols[]='created_at';   } // NOW() ile setleyeceğiz

$placeholders = array_fill(0, count($cols), '?');

// created_at varsa son placeholder yerine NOW() kullanalım
if (in_array('created_at', $cols, true)) {
  $idx = array_search('created_at', $cols, true);
  $placeholders[$idx] = 'NOW()';
}

$sql = "INSERT INTO `$table` (" . implode(',', array_map(fn($c)=>"`$c`",$cols)) . ")
        VALUES (" . implode(',', $placeholders) . ")";

$stmt = db()->prepare($sql);
$stmt->execute($vals);

// Yönlendirme (sende talep listesi hangi sayfadaysa onu da yazabiliriz)
header('Location: ' . APP_BASE . '/index.php?created=1');
exit;
