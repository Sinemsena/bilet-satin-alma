<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'user') {
    die("❌ Sadece kullanıcılar bilet satın alabilir!");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Geçersiz istek yöntemi!");
}

$user_id = $_SESSION['user']['id'];
$route_id = (int)$_POST['route_id'];
$seat_number = (int)$_POST['seat_number'];
$coupon_code = strtoupper(trim($_POST['coupon_code'] ?? ''));

// Sefer bilgisi
$stmt = $db->prepare("SELECT * FROM routes WHERE id = ?");
$stmt->execute([$route_id]);
$route = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$route) die("Sefer bulunamadı!");

// Dolu koltuk kontrolü
$stmt = $db->prepare("SELECT COUNT(*) FROM tickets WHERE route_id = ? AND seat_number = ? AND status = 'active'");
$stmt->execute([$route_id, $seat_number]);
if ($stmt->fetchColumn() > 0) {
    die("❌ Bu koltuk zaten dolu!");
}

$price = $route['price'];
$discountText = '';

// 🎟 Kupon kontrolü
if (!empty($coupon_code)) {
    $stmt = $db->prepare("SELECT * FROM coupons WHERE code = ?");
    $stmt->execute([$coupon_code]);
    $coupon = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($coupon) {
        if ($coupon['firm_id'] && $coupon['firm_id'] != $route['firm_id']) {
            $discountText = "❌ Bu kupon bu firmada geçerli değil.";
        } else {
            $valid = strtotime($coupon['expiry_date']) >= time();
            $hasLimit = $coupon['used_count'] < $coupon['usage_limit'];

            $check = $db->prepare("SELECT 1 FROM coupon_usage WHERE user_id=? AND coupon_code=?");
            $check->execute([$user_id, $coupon_code]);
            $alreadyUsed = $check->fetchColumn();

            if ($valid && $hasLimit && !$alreadyUsed) {
                $discount = (float)$coupon['discount'];
                $price = round($price - ($price * $discount / 100), 2);
                $discountText = "✅ Kupon (%$discount) indirimi uygulandı.";

                $db->prepare("INSERT INTO coupon_usage (user_id, coupon_code, used_at) VALUES (?, ?, datetime('now'))")
                    ->execute([$user_id, $coupon_code]);
                $db->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE code=?")
                    ->execute([$coupon_code]);
            } else {
                $discountText = $alreadyUsed ? "❌ Bu kuponu zaten kullandınız."
                    : (!$valid ? "❌ Kuponun süresi dolmuş." : "❌ Kupon kullanım limiti dolmuş.");
            }
        }
    } else {
        $discountText = "❌ Geçersiz kupon kodu!";
    }
    if (str_starts_with($discountText, '❌')) {
    echo "<script>
        if (confirm('$discountText\\nKuponsuz devam etmek ister misiniz?')) {
            // Kullanıcı onaylarsa sayfayı kuponsuz POST ile yeniden gönder
            fetch('bilet_al.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'route_id={$route_id}&seat_number={$seat_number}&coupon_code='
            })
            .then(res => res.text())
            .then(html => {
                document.open();
                document.write(html);
                document.close();
            });
        } else {
            window.history.back();
        }
    </script>";
    exit;
}

}

// 💰 Kullanıcı bakiyesi
$stmt = $db->prepare("SELECT credit FROM users WHERE id=?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user['credit'] < $price) {
    die("❌ Yetersiz bakiye. Lütfen kredinizi artırın.");
}

// 💳 Krediden düş
$newCredit = $user['credit'] - $price;
$stmt = $db->prepare("UPDATE users SET credit = ? WHERE id = ?");
$stmt->execute([$newCredit, $user_id]);
$_SESSION['user']['credit'] = $newCredit; // <-- oturum güncellemesi eklendi ✅

// 🎫 Bilet kaydı
$stmt = $db->prepare("INSERT INTO tickets (user_id, route_id, seat_number, price, status) VALUES (?, ?, ?, ?, 'active')");
$stmt->execute([$user_id, $route_id, $seat_number, $price]);

echo "
<!DOCTYPE html>
<html lang='tr'>
<head>
<meta charset='UTF-8'>
<title>Bilet Başarıyla Satın Alındı</title>
<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>
<style>
body {
  background: linear-gradient(135deg, #dff1ff, #f8fcff);
  font-family: 'Poppins', sans-serif;
}
.ticket-container {
  max-width: 550px;
  margin: 80px auto;
  background: #fff;
  border-radius: 16px;
  box-shadow: 0 8px 20px rgba(0,0,0,0.1);
  padding: 40px;
  text-align: center;
}
.ticket-header {
  font-size: 1.6rem;
  font-weight: 700;
  color: #27ae60;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
}
.ticket-header i {
  font-size: 1.8rem;
}
.ticket-details {
  margin-top: 25px;
  font-size: 1.1rem;
  color: #333;
}
.ticket-details p {
  margin-bottom: 8px;
}
.discount-text {
  color: " . (str_contains($discountText, '❌') ? '#e74c3c' : '#27ae60') . ";
  margin-top: 12px;
  font-weight: 500;
}
.btn-section {
  margin-top: 25px;
}
.btn-section a {
  text-decoration: none;
}
</style>
</head>
<body>

<div class='ticket-container'>
  <div class='ticket-header'>
    <i>✅</i> Bilet Başarıyla Satın Alındı!
  </div>

  <div class='ticket-details'>
  <p><strong>Güzergâh:</strong> {$route['departure']} → {$route['arrival']}</p>
  <p><strong>Tarih:</strong> {$route['date']} | <strong>Saat:</strong> {$route['time']}</p>
  <hr>
  <p><strong>Koltuk No:</strong> $seat_number</p>
  <p><strong>Normal Fiyat:</strong> {$route['price']} ₺</p>
  " . (!empty($discount) ? "
  <p><strong>İndirim:</strong> %$discount</p>
  <p><strong>Ödenen Tutar:</strong> <span style='color:#27ae60;font-weight:600;'>$price ₺</span></p>
  <p class='discount-text'>✅ Kupon (%$discount) indirimi uygulandı.</p>
  " : "
  <p><strong>Ödenen Tutar:</strong> <span style='color:#27ae60;font-weight:600;'>$price ₺</span></p>
  ") . "
</div>


  <div class='btn-section'>
    <a href='biletlerim.php' class='btn btn-primary px-4 me-2'>🎫 Biletlerimi Gör</a>
    <a href='index.php' class='btn btn-outline-success px-4'>🏠 Ana Sayfaya Dön</a>
  </div>
</div>

</body>
</html>";

?>
