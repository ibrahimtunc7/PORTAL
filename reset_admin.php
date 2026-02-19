<?php
require __DIR__ . '/auth.php';

$newPassword = "123456";

$hash = password_hash($newPassword, PASSWORD_DEFAULT);

$stmt = db()->prepare("
  UPDATE users 
  SET password=? 
  WHERE role='admin'
");

$stmt->execute([$hash]);

echo "Admin şifresi sıfırlandı.<br>";
echo "Yeni şifre: 123456";
