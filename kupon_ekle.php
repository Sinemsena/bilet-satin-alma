<?php
session_start();
include 'includes/db.php';

// Hata mesajlarını depolamak için değişken
$errorMessage = '';

// Eğer oturum açan kullanıcı bir firma admini değilse veya oturum yoksa çıkış yap
if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] !== 'firma_admin' && $_SESSION['user']['role'] !== 'super_admin')) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // POST verilerini al
    $code = strtoupper(trim($_POST['code']));
    // Discount ve Limit tam sayı olmalı
    $discount = (int)$_POST['discount'];
    $limit = (int)$_POST['usage_limit']; 
    // valid_until (formdan gelen isim) -> expiry_date (DB sütunu)
    $valid_until = $_POST['valid_until']; 
    // Active değişkeni formdan alınır ancak DB'ye kaydedilmez (default 1 olduğu varsayılır, ancak biz 0'ı kullanmayacağız)
    //$active = isset($_POST['active']) ? (int)$_POST['active'] : 1;
    $firm_id = null;

    // 1. Firma ID'sini Bulma
    if ($_SESSION['user']['role'] === 'firma_admin') {
        $stmt = $db->prepare("SELECT id FROM firms WHERE admin_id = ?");
        $stmt->execute([$_SESSION['user']['id']]);
        $firm = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($firm) {
            $firm_id = $firm['id'];
        } else {
            // Hata mesajını yakala ve kaydet
            $errorMessage = "Hata: Oturumdaki admin kullanıcısına ait bir firma bulunamadı. Lütfen önce firma kaydınızı tamamlayın.";
        }
    } 

    // 2. Kuponu Veritabanına Kaydetme
    if (!$errorMessage) {
        try {
            
            $stmt = $db->prepare("INSERT INTO coupons (code, discount, usage_limit, expiry_date, used_count, firm_id) VALUES (?, ?, ?, ?, 0, ?)");
            $stmt->execute([$code, $discount, $limit, $valid_until, $firm_id]);

            // Yönlendirme (Başarı mesajı ile)
            $successMessage = "Kupon ('{$code}') başarıyla eklendi.";
            
            if ($_SESSION['user']['role'] === 'firma_admin') {
                header("Location: firma_admin_panel.php?success=" . urlencode($successMessage));
            } else {
                header("Location: admin_panel.php?success=" . urlencode($successMessage));
            }
            exit;
            
        } catch (PDOException $e) {
             // Veritabanı hatası: Hatanın ne olduğunu öğrenmek için mesajı yakala.
             $errorMessage = "Veritabanı Hatası: Lütfen veritabanı şemasını kontrol edin. Hata: " . $e->getMessage();
        }
    }
    
    // Eğer bir hata varsa (Firma ID hatası veya PDO hatası), oturumda sakla ve geri yönlendir
    if ($errorMessage) {
        $_SESSION['error_message'] = $errorMessage;
        header("Location: firma_admin_panel.php");
        exit;
    }
} else {
    // POST olmayan istekleri ana panele yönlendir
    header("Location: firma_admin_panel.php");
    exit;
}
?>
