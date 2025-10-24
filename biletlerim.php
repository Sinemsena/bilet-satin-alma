<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'user') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user']['id'];

// Biletleri çek
$stmt = $db->prepare("
    SELECT t.*, r.departure, r.arrival, r.date, r.time, r.price AS route_price
    FROM tickets t
    JOIN routes r ON r.id = t.route_id
    WHERE t.user_id = ?
    ORDER BY r.date DESC, r.time DESC
");
$stmt->execute([$user_id]);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Biletlerim</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
  <h2 class="text-primary mb-4">🎟 Biletlerim</h2>

  <?php if (empty($tickets)): ?>
    <div class="alert alert-info">Henüz satın alınmış biletiniz bulunmuyor.</div>
  <?php else: ?>
  <table class="table table-bordered bg-white shadow-sm">
    <thead class="table-light">
      <tr>
        <th>Kalkış</th>
        <th>Varış</th>
        <th>Tarih</th>
        <th>Saat</th>
        <th>Koltuk</th>
        <th>Fiyat</th>
        <th>Durum</th>
        <th>İşlemler</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($tickets as $t): 
          $canCancel = (strtotime($t['date'].' '.$t['time']) - time()) > 3600; // 1 saatten fazla varsa
      ?>
      <tr>
        <td><?= htmlspecialchars($t['departure']) ?></td>
        <td><?= htmlspecialchars($t['arrival']) ?></td>
        <td><?= htmlspecialchars($t['date']) ?></td>
        <td><?= htmlspecialchars($t['time']) ?></td>
        <td><?= htmlspecialchars($t['seat_number']) ?></td>
        <td><?= htmlspecialchars($t['price']) ?> ₺</td>
        <td><?= $t['status'] === 'cancelled' ? '❌ İptal Edildi' : '✅ Aktif' ?></td>
        <td>
          <?php if ($t['status'] === 'active' && $canCancel): ?>
            <a href="bilet_iptal.php?id=<?= $t['id'] ?>" 
               onclick="return confirm('Bu bileti iptal etmek istediğinize emin misiniz?')" 
               class="btn btn-sm btn-danger">İptal Et</a>
          <?php endif; ?>
          <a href="bilet_pdf.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-outline-primary">📄 PDF</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
  <div class="text-center mt-4">
  <a href="index.php" class="btn btn-outline-primary">
    🏠 Ana Sayfaya Dön
  </a>
</div>
</div>
</body>
</html>
