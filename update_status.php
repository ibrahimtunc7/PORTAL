<?php
declare(strict_types=1);
require __DIR__ . '/auth.php';

require_login();
$me = current_user();

if (($me['role'] ?? '') !== 'admin') {
  die('Yetkisiz');
}

$requestId = (int)($_POST['request_id'] ?? 0);
$status = $_POST['status'] ?? '';

$allowed = ['new','processing','offer_sent','closed'];
if (!$requestId || !in_array($status, $allowed, true)) {
  die('Hatalı veri');
}

$stmt = db()->prepare("UPDATE quote_requests SET status=? WHERE id=?");
$stmt->execute([$status, $requestId]);

header("Location: request_detail.php?id=".$requestId);
exit;
