<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'user') {
    die("❌ Yetkisiz erişim!");
}

$user_id = $_SESSION['user']['id'];
$ticket_id = (int)$_GET['id'];

// Bilet bilgisi
$stmt = $db->prepare("
    SELECT t.*, r.date, r.time 
    FROM tickets t 
    JOIN routes r ON r.id = t.route_id 
    WHERE t.id=? AND t.user_id=?
");
$stmt->execute([$ticket_id, $user_id]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) die("Bilet bulunamadı!");

// 1 saat kuralı
$diff = strtotime($ticket['date'].' '.$ticket['time']) - time();
if ($diff < 3600) {
    die("🚫 Kalkış saatine 1 saatten az kaldığı için iptal edilemez.");
}

// Bileti iptal et ve ücreti iade et
$db->prepare("UPDATE tickets SET status='cancelled' WHERE id=?")->execute([$ticket_id]);
$db->prepare("UPDATE users SET credit = credit + ? WHERE id=?")->execute([$ticket['price'], $user_id]);
$_SESSION['user']['credit'] += $ticket['price'];

header("Location: biletlerim.php?success=" . urlencode("Bilet başarıyla iptal edildi, ücret iade edildi."));
exit;
?>
