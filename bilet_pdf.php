<?php
session_start();
require('includes/fpdf.php');  // FPDF dosya yolu doğru olmalı
include 'includes/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'user') {
    die("❌ Yetkisiz erişim!");
}

$user_id   = $_SESSION['user']['id'];
$ticket_id = (int)($_GET['id'] ?? 0);

// Bilet + Sefer + Firma bilgileri
$stmt = $db->prepare("
    SELECT t.*, r.departure, r.arrival, r.date, r.time, r.firm_id, f.name AS firm_name
    FROM tickets t
    JOIN routes r ON r.id = t.route_id
    LEFT JOIN firms f ON f.id = r.firm_id
    WHERE t.id = ? AND t.user_id = ?
");
$stmt->execute([$ticket_id, $user_id]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$ticket) die("Bilet bulunamadı!");

// --------------------------------------
// PDF OLUŞTURMA
// --------------------------------------
$pdf = new FPDF();           // <-- ÖNCE nesneyi oluştur
$pdf->AddPage();

// (İsteğe bağlı) Türkçe karakterler sorun çıkarmasın diye yardımcı fonksiyon
function tr($s) {
    return iconv('UTF-8', 'windows-1254//IGNORE', $s);
}


// Üst başlık şeridi (mavi)
$pdf->SetFillColor(41, 128, 185);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('Arial', 'B', 18);
$pdf->Cell(0, 12, tr('🚌 OBÜSTÜK BİLET BİLGİSİ'), 0, 1, 'C', true);
$pdf->Ln(6);

// Gövde yazı rengi ve font
$pdf->SetTextColor(0, 0, 0);

// Firma ve yolcu bilgileri
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 8, tr('Firma: ') . tr($ticket['firm_name'] ?? 'Belirtilmemiş'), 0, 1);
$pdf->Cell(0, 8, tr('Yolcu: ') . tr($_SESSION['user']['name']), 0, 1);
$pdf->Ln(4);

// Sefer bilgileri başlık
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, tr('Sefer Bilgileri'), 0, 1);

// Sefer satırları
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 8, tr('Kalkış: ') . tr($ticket['departure']), 0, 1);
$pdf->Cell(0, 8, tr('Varış: ')  . tr($ticket['arrival']),   0, 1);
$pdf->Cell(0, 8, tr('Tarih: ')  . tr($ticket['date']) . tr('   Saat: ') . tr($ticket['time']), 0, 1);
$pdf->Ln(4);

// Bilet bilgileri başlık
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, tr('Bilet Bilgileri'), 0, 1);

// Bilet satırları
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 8, tr('Koltuk No: ') . tr($ticket['seat_number']), 0, 1);
$pdf->Cell(0, 8, tr('Fiyat: ') . tr($ticket['price']) . tr(' ₺'), 0, 1);
$pdf->Cell(0, 8, tr('Durum: ') . tr(strtoupper($ticket['status'])), 0, 1);
$pdf->Ln(8);

// Alt bilgi
$pdf->SetFont('Arial', 'I', 10);
$pdf->Cell(0, 8, tr('Bu bilet OBÜSTÜK sistemi tarafından oluşturulmuştur.'), 0, 1, 'C');
$pdf->Cell(0, 8, tr(date('d.m.Y H:i')), 0, 1, 'C');

// İndir
$pdf->Output('D', 'Bilet-' . $ticket['id'] . '.pdf');
exit;
