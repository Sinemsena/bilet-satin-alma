<?php
session_start();
include 'includes/db.php';

// Oturum kontrolü
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'firma_admin') {
    header('Location: login.php');
    exit;
}

// POST isteği kontrolü
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = "Hata: Geçersiz istek yöntemi.";
    header('Location: firma_admin_panel.php');
    exit;
}

// Gerekli alanlar
$required_fields = ['edit_id', 'edit_code', 'edit_discount', 'edit_expiry_date', 'edit_usage_limit'];
foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || $_POST[$field] === '') {
        $_SESSION['error_message'] = "Hata: Tüm alanlar doldurulmalıdır.";
        header('Location: firma_admin_panel.php');
        exit;
    }
}

// Form verilerini al
$kuponId = (int)$_POST['edit_id'];
$code = trim($_POST['edit_code']);
$discount = (int)$_POST['edit_discount'];
$expiry_date = $_POST['edit_expiry_date'];
$usage_limit = (int)$_POST['edit_usage_limit'];

$adminId = $_SESSION['user']['id'];

// Firma kontrolü
$stmt = $db->prepare("SELECT id FROM firms WHERE admin_id = ? LIMIT 1");
$stmt->execute([$adminId]);
$firma = $stmt->fetch(PDO::FETCH_ASSOC);
$firmaId = $firma ? $firma['id'] : null;

if (!$firmaId) {
    $_SESSION['error_message'] = "Kritik Hata: Admin kullanıcısına ait firma ID'si bulunamadı.";
    header('Location: firma_admin_panel.php');
    exit;
}

// Kuponu güncelle
try {
    $stmt = $db->prepare("
        UPDATE coupons 
        SET code = ?, discount = ?, expiry_date = ?, usage_limit = ?
        WHERE id = ? AND firm_id = ?
    ");
    $success = $stmt->execute([
        $code,
        $discount,
        $expiry_date,
        $usage_limit,
        $kuponId,
        $firmaId
    ]);

    if ($success && $stmt->rowCount() > 0) {
        header("Location: firma_admin_panel.php?success=" . urlencode("Kupon başarıyla güncellendi."));
        exit;
    } else {
        $_SESSION['error_message'] = "Hata: Güncelleme başarısız veya değişiklik yapılmadı.";
        header('Location: firma_admin_panel.php');
        exit;
    }

} catch (PDOException $e) {
    $_SESSION['error_message'] = "Veritabanı hatası: " . $e->getMessage();
    header('Location: firma_admin_panel.php');
    exit;
}
?>
