<?php
session_start();
// 1. Yetki Kontrolü: Yalnızca firma adminleri erişebilir.
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'firma_admin') {
    header('Location: login.php');
    exit;
}

include 'includes/db.php';

// Hata mesajlarını oturumda tutmak için bir fonksiyon
function setError($message) {
    $_SESSION['error_message'] = $message;
}

// 2. Kupon ID'sinin alınması
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setError("Hata: Silinecek kupon ID'si belirtilmemiş.");
    header('Location: firma_admin_panel.php');
    exit;
}

$couponId = $_GET['id'];
$adminUserId = $_SESSION['user']['id'];

try {
    // 3. Firma ID'sinin Bulunması (Güvenlik için gerekli)
    $stmt = $db->prepare("SELECT id FROM firms WHERE admin_id = ? LIMIT 1");
    $stmt->execute([$adminUserId]);
    $firma = $stmt->fetch(PDO::FETCH_ASSOC);
    $firmaId = $firma ? $firma['id'] : null;

    if (!$firmaId) {
        setError("Kritik Hata: Oturumdaki kullanıcıya ait firma ID'si bulunamadı.");
        header('Location: firma_admin_panel.php');
        exit;
    }

    // 4. Güvenli Silme İşlemi
    // Silme işlemini yaparken kuponun hem doğru ID'ye sahip olduğunu
    // hem de oturum açmış firmanın kuponu olduğunu kontrol ediyoruz.
    $stmt = $db->prepare("DELETE FROM coupons WHERE id = ? AND firm_id = ?");
    $stmt->execute([$couponId, $firmaId]);

    if ($stmt->rowCount() > 0) {
        // Başarı Durumu
        $successMessage = "Kupon başarıyla silindi (ID: {$couponId}).";
        header('Location: firma_admin_panel.php?success=' . urlencode($successMessage));
        exit;
    } else {
        // Kupon bulunamadı veya yetkisiz erişim denemesi (rowCount 0 döner)
        setError("Hata: Belirtilen ID'ye sahip kupon bulunamadı veya silme yetkiniz yok.");
        header('Location: firma_admin_panel.php');
        exit;
    }

} catch (PDOException $e) {
    // Veritabanı Hatası
    setError("Veritabanı hatası: Kupon silinirken bir sorun oluştu. (" . $e->getMessage() . ")");
    header('Location: firma_admin_panel.php');
    exit;
}
?>
