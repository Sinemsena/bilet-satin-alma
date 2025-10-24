<?php
include 'includes/db.php';

// Admin kullanıcı
$adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
$db->exec("INSERT INTO users (name, email, password, role, credit) 
           VALUES ('Admin', 'admin@example.com', '$adminPassword', 'admin', 1000000)");

// Firma ve firma admin
$db->exec("INSERT INTO firms (name, description) VALUES ('Pamukkale Turizm', 'Yurt içi seferler')");
$firmaId = $db->lastInsertId();

$firmaAdminPassword = password_hash('firma123', PASSWORD_DEFAULT);
$db->exec("INSERT INTO users (name, email, password, role, credit) 
           VALUES ('Firma Admin', 'firma@example.com', '$firmaAdminPassword', 'firma_admin', 1000000)");

// 2 örnek sefer ekleyelim
$stmt = $db->prepare("INSERT INTO routes (firm_id, departure, arrival, date, time, price, seats)
                      VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->execute([$firmaId, 'İstanbul', 'Ankara', '2025-10-25', '09:00', 450, 40]);
$stmt->execute([$firmaId, 'İzmir', 'Antalya', '2025-10-26', '13:30', 380, 38]);

echo "✅ Başlangıç verileri başarıyla eklendi!";
