<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'firma_admin') {
    header('Location: login.php');
    exit;
}

include 'includes/db.php';

// Firma ID'si
$stmt = $db->prepare("SELECT id FROM firms WHERE admin_id = ? LIMIT 1");
$stmt->execute([$_SESSION['user']['id']]);
$firma = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$firma) die("Bu kullanıcıya ait firma bulunamadı!");
$firmaId = $firma['id'];

// Sefer ID
$id = $_GET['id'] ?? null;

// Sefer gerçekten bu firmaya mı ait?
$stmt = $db->prepare("SELECT * FROM routes WHERE id = ? AND firm_id = ?");
$stmt->execute([$id, $firmaId]);
$sefer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sefer) {
    die("🚫 Bu sefer size ait değil veya bulunamadı!");
}

// Silme işlemi
$stmt = $db->prepare("DELETE FROM routes WHERE id = ?");
$stmt->execute([$id]);

header('Location: firma_admin_panel.php');
exit;

?>
