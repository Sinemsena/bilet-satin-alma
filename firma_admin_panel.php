<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'firma_admin') {
    header('Location: login.php');
    exit;
}

include 'includes/db.php';

$message = '';
if (isset($_GET['success'])) {
    $message = '<div class="alert alert-success mt-3">' . htmlspecialchars($_GET['success']) . '</div>';
}
if (isset($_SESSION['error_message'])) {
    $message = '<div class="alert alert-danger mt-3">' . htmlspecialchars($_SESSION['error_message']) . '</div>';
    unset($_SESSION['error_message']); 
}

// Firma adminine ait firma bilgilerini alıyoruz
$stmt = $db->prepare("SELECT * FROM firms WHERE admin_id = ? LIMIT 1");
$stmt->execute([$_SESSION['user']['id']]);
$firma = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$firma) {
    die('<div class="alert alert-danger m-5 text-center">
        ❌ Bu kullanıcıya ait bir firma bulunamadı.<br>
        Lütfen sistem yöneticisi (Admin) tarafından atanmış bir firmayla giriş yapın.
    </div>');
}

$firmaId = $firma['id'];

// Seferleri çek
$stmt = $db->prepare("SELECT * FROM routes WHERE firm_id = ? ORDER BY date DESC, time ASC");
$stmt->execute([$firmaId]);
$routes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// İstatistik verileri
$totalRoutes = count($routes);

$stmt = $db->prepare("SELECT COUNT(*) FROM tickets WHERE route_id IN (SELECT id FROM routes WHERE firm_id = ?)");
$stmt->execute([$firmaId]);
$totalTickets = $stmt->fetchColumn();

// Kuponlar
$stmt = $db->prepare("SELECT * FROM coupons WHERE firm_id = ? ORDER BY id DESC");
$stmt->execute([$firmaId]);
$coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalCoupons = count($coupons);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Firma Admin Paneli</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
<!-- 🔹 Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm sticky-top mb-4">
  <div class="container">
    <a class="navbar-brand fw-bold" href="index.php">🚌 OBÜSTÜK</a>
    
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarContent">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link" href="index.php">Ana Sayfa</a></li>
        <li class="nav-item"><a class="nav-link active" href="firma_admin_panel.php">🏢 Firma Paneli</a></li>
        <li class="nav-item"><a class="nav-link" href="sefer_ekle.php">➕ Yeni Sefer</a></li>
      </ul>

      <ul class="navbar-nav ms-auto">
        <li class="nav-item d-flex align-items-center me-3">
          <span class="text-muted small">
            👋 <strong><?= $_SESSION['user']['name'] ?></strong> |
            💰 <?= $_SESSION['user']['credit'] ?> ₺
          </span>
        </li>
        <li class="nav-item">
          <a href="logout.php" class="btn btn-outline-danger btn-sm">Çıkış Yap</a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<div class="container">
<div class="text-center mb-4">
    <h2 class="fw-bold text-primary"><?= htmlspecialchars($firma['name']) ?> Paneli</h2>
    <p class="text-muted"><?= htmlspecialchars($firma['description'] ?? 'Açıklama bulunmuyor') ?></p>
  </div>
  <!-- 📊 İstatistik Kartları -->
  <div class="row mb-4">
    <div class="col-md-4">
      <div class="card shadow-sm border-0">
        <div class="card-body text-center">
          <h5 class="card-title text-muted">Toplam Sefer</h5>
          <h3 class="fw-bold text-primary"><?= $totalRoutes ?></h3>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card shadow-sm border-0">
        <div class="card-body text-center">
          <h5 class="card-title text-muted">Satılan Bilet</h5>
          <h3 class="fw-bold text-success"><?= $totalTickets ?></h3>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card shadow-sm border-0">
        <div class="card-body text-center">
          <h5 class="card-title text-muted">Toplam Kupon</h5>
          <h3 class="fw-bold text-warning"><?= $totalCoupons ?></h3>
        </div>
      </div>
    </div>
  </div>

  <!-- 🚍 Seferler -->
  <div class="card shadow-sm mb-5">
    <div class="card-header bg-primary text-white fw-semibold">🚍 Mevcut Seferler</div>
    <div class="card-body">
      <a href="sefer_ekle.php" class="btn btn-success mb-3">➕ Yeni Sefer Ekle</a>
      <div class="table-responsive">
        <table class="table table-bordered align-middle">
          <thead class="table-light">
            <tr>
              <th>ID</th>
              <th>Kalkış</th>
              <th>Varış</th>
              <th>Tarih</th>
              <th>Saat</th>
              <th>Fiyat</th>
              <th>Koltuk</th>
              <th>İşlemler</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($routes as $r): ?>
            <tr>
              <td><?= $r['id'] ?></td>
              <td><?= $r['departure'] ?></td>
              <td><?= $r['arrival'] ?></td>
              <td><?= $r['date'] ?></td>
              <td><?= $r['time'] ?></td>
              <td><?= $r['price'] ?> ₺</td>
              <td><?= $r['seats'] ?></td>
              <td>
                <a href="sefer_duzenle.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-warning">✏️ Düzenle</a>
                <a href="sefer_sil.php?id=<?= $r['id'] ?>" onclick="return confirm('Bu sefer silinsin mi?')" class="btn btn-sm btn-outline-danger">🗑️ Sil</a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- 🎟️ Firma Kuponları -->
  <div class="card shadow-sm mb-5">
    <div class="card-header bg-success text-white fw-semibold">🎟️ Firma Kuponları</div>
    <div class="card-body">

      <h5 class="fw-semibold mb-3 text-secondary">Yeni Kupon Oluştur</h5>
      <form method="POST" action="kupon_ekle.php" class="row g-3 mb-4">
        
        <div class="col-md-2">
          <label class="form-label fw-semibold">Kupon Kodu</label>
          <input type="text" name="code" class="form-control" placeholder="Örn: INDIRIM10" required>
        </div>
        <div class="col-md-2">
          <label class="form-label fw-semibold">İndirim (%)</label>
          <input type="number" name="discount" class="form-control" placeholder="%"  min="1" max="100" required>
        </div>
        <div class="col-md-3">
            <label class="form-label fw-semibold">Son Geçerlilik Tarihi</label>
            <input type="date" name="valid_until" class="form-control" required>
         </div>
        <div class="col-md-2">
          <label class="form-label fw-semibold">Limit</label>
          <input type="number" name="usage_limit" class="form-control" min="1" value="1" required>
        </div>
        <div class="col-md-1 d-grid align-items-end">
          <button type="submit" class="btn btn-success">Ekle</button>
        </div>
      </form>

      <hr>
      <h5 class="fw-semibold mb-3 text-secondary">Mevcut Kuponlar</h5>
      <div class="table-responsive">
        <table class="table table-bordered align-middle">
          <thead class="table-light">
            <tr>
              <th>Kod</th>
              <th>İndirim (%)</th>
              <th>Son Geçerlilik Tarihi</th>
              <th>Kullanım</th>
              <th>İşlemler</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($coupons as $c): ?>
              <tr>
                <td><?= $c['code'] ?></td>
                <td><?= $c['discount'] ?>%</td>
                <td>
                    <?= htmlspecialchars($c['expiry_date'] ?? 'N/A') ?>
                </td>
                <td><?= htmlspecialchars($c['used_count'] ?? '0') ?>/<?= htmlspecialchars($c['usage_limit'] ?? '∞') ?></td>
                <td>
                    <button class="btn btn-sm btn-outline-warning edit-coupon-btn" 
                            data-bs-toggle="modal" 
                            data-bs-target="#editCouponModal"
                            data-id="<?= $c['id'] ?>"
                            data-code="<?= htmlspecialchars($c['code']) ?>"
                            data-discount="<?= htmlspecialchars($c['discount']) ?>"
                            data-limit="<?= htmlspecialchars($c['usage_limit']) ?>"
                            data-date="<?= htmlspecialchars($c['expiry_date']) ?>"
                            >✏️ Düzenle</button>
                    <!-- Kullanıcı onayı yerine alert/confirm kullanılamaz, ancak burada onay için confirm kullandım. -->
                    <a href="kupon_sil.php?id=<?= $c['id'] ?>" onclick="return confirm('Bu kupon kalıcı olarak silinecektir. Emin misiniz?')" class="btn btn-sm btn-outline-danger">🗑️ Sil</a>
                </td>
                
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

    </div>
  </div>
</div>

<!--  Kupon Düzenleme Modal'ı -->
<div class="modal fade" id="editCouponModal" tabindex="-1" aria-labelledby="editCouponModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-warning text-white">
        <h5 class="modal-title" id="editCouponModalLabel">Kuponu Düzenle</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
        <form method="POST" action="kupon_duzenle_islem.php" class="modal-body row g-3">
        <div class="modal-body">
            <!-- Kupon ID'si gizli alan olarak gönderiliyor -->
            <input type="hidden" id="edit_id" name="edit_id">

            <div class="mb-3">
                <label for="edit_code" class="form-label fw-semibold">Kupon Kodu</label>
                <input type="text" id="edit_code" name="edit_code" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="edit_discount" class="form-label fw-semibold">İndirim (%)</label>
                <input type="number" id="edit_discount" name="edit_discount" class="form-control" min="1" max="100" required>
            </div>
            <div class="mb-3">
                <label for="edit_expiry_date" class="form-label fw-semibold">Son Kullanma Tarihi</label>
                <input type="date" id="edit_expiry_date" name="edit_expiry_date" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="edit_usage_limit" class="form-label fw-semibold">Kullanım Limiti</label>
                <input type="number" id="edit_usage_limit" name="edit_usage_limit" class="form-control" min="1" required>
                
            </div>
            
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
          <button type="submit" class="btn btn-warning">Güncelle</button>
        </div>
      </form>
    </div>
  </div>
</div>
<!-- ⚙️ Kupon Düzenleme Modal'ı SONU -->

<script>
document.addEventListener('DOMContentLoaded', function () {
    const editCouponModal = document.getElementById('editCouponModal');
    
    // Modal açıldığında tetiklenir
    editCouponModal.addEventListener('show.bs.modal', function (event) {
        // Düzenle butonunu tetikleyen elementi al
        const button = event.relatedTarget;
        
        // Data niteliklerinden kupon verilerini çek
        const id = button.getAttribute('data-id');
        const code = button.getAttribute('data-code');
        const discount = button.getAttribute('data-discount');
        const limit = button.getAttribute('data-limit');
        const date = button.getAttribute('data-date');
        
        // Modal içindeki form alanlarını doldur
        const modalBodyInputId = editCouponModal.querySelector('#edit_id');
        const modalBodyInputCode = editCouponModal.querySelector('#edit_code');
        const modalBodyInputDiscount = editCouponModal.querySelector('#edit_discount');
        const modalBodyInputLimit = editCouponModal.querySelector('#edit_usage_limit');
        const modalBodyInputDate = editCouponModal.querySelector('#edit_expiry_date');

        modalBodyInputId.value = id;
        modalBodyInputCode.value = code;
        modalBodyInputDiscount.value = discount;
        modalBodyInputLimit.value = limit;
        modalBodyInputDate.value = date;
    });
});
</script>
</body>
</html>
