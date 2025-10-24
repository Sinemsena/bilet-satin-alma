<?php
include 'includes/db.php';

$code = strtoupper(trim($_POST['coupon_code'] ?? ''));
$route_id = $_POST['route_id'] ?? 0;

if (!$code) {
    echo json_encode(['valid' => false, 'message' => 'Kupon kodu boş olamaz.']);
    exit;
}

$stmt = $db->prepare("SELECT * FROM coupons WHERE code=?");
$stmt->execute([$code]);
$coupon = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$coupon) {
    echo json_encode(['valid' => false, 'message' => '❌ Geçersiz kupon kodu!']);
    exit;
}

// Tarih kontrolü
if (strtotime($coupon['expiry_date']) < time()) {
    echo json_encode(['valid' => false, 'message' => '⏰ Kupon süresi dolmuş.']);
    exit;
}

// Limit kontrolü
if ($coupon['used_count'] >= $coupon['usage_limit']) {
    echo json_encode(['valid' => false, 'message' => '🚫 Kupon kullanım limiti dolmuş.']);
    exit;
}

echo json_encode([
    'valid' => true,
    'discount' => $coupon['discount'],
    'message' => "✅ Kupon geçerli! %" . $coupon['discount'] . " indirim uygulanacak."
]);
