<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login.php'); exit;
}
include 'includes/db.php';

/* -------------------- ACTIONS -------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1) Firma Ekle
    if ($action === 'add_firm') {
        $name = trim($_POST['name']);
        $desc = trim($_POST['description']);
        $stmt = $db->prepare("INSERT INTO firms (name, description) VALUES (?, ?)");
        $stmt->execute([$name, $desc]);
        header("Location: admin_panel.php"); exit;
    }

    // Firma Sil
if ($action === 'delete_firm') {
    $id = (int)$_POST['firm_id'];
    // admin bağlantısını kaldır (güvenli silme)
    $db->prepare("UPDATE users SET role='user' WHERE id = (SELECT admin_id FROM firms WHERE id = ?)")->execute([$id]);
    $db->prepare("DELETE FROM firms WHERE id=?")->execute([$id]);
    header("Location: admin_panel.php"); exit;
}

// Firma Güncelle
if ($action === 'update_firm') {
    $id = (int)$_POST['firm_id'];
    $name = trim($_POST['name']);
    $desc = trim($_POST['description']);

    $stmt = $db->prepare("UPDATE firms SET name=?, description=? WHERE id=?");
    $stmt->execute([$name, $desc, $id]);
    header("Location: admin_panel.php"); exit;
}


    // 2) Firma Admin Oluştur ve Firmaya Ata
    if ($action === 'create_firma_admin') {
        $name = trim($_POST['fa_name']);
        $email = trim($_POST['fa_email']);
        $password = password_hash($_POST['fa_password'], PASSWORD_DEFAULT);
        $firm_id = (int)$_POST['fa_firm_id'];

        // kullanıcıyı oluştur
        $stmt = $db->prepare("INSERT INTO users (name, email, password, role, credit) VALUES (?, ?, ?, 'firma_admin', 1000000)");
        $stmt->execute([$name, $email, $password]);
        $newUserId = $db->lastInsertId();

        // firmaya bağla
        $stmt = $db->prepare("UPDATE firms SET admin_id = ? WHERE id = ?");
        $stmt->execute([$newUserId, $firm_id]);

        header("Location: admin_panel.php"); exit;
    }

    // 3) Kupon Ekle (tüm firmalar veya belli firma)
    if ($action === 'add_coupon') {
        $code        = strtoupper(trim($_POST['code']));
        $discount    = (int)$_POST['discount'];
        $valid_until = $_POST['valid_until'];
        $usage_limit = (int)$_POST['usage_limit'];        $firm_id     = $_POST['firm_id'] !== '' ? (int)$_POST['firm_id'] : null;

        $stmt = $db->prepare("INSERT INTO coupons (code, discount, expiry_date, usage_limit, used_count, firm_id)
                              VALUES (?, ?, ?, ?, 0, ?)");
        $stmt->execute([$code, $discount, $valid_until, $usage_limit, $firm_id]);
        header("Location: admin_panel.php"); exit;
    }

    

    // 4) Kupon düzenle ve  Sil
    if ($action === 'update_coupon') {
    $id          = (int)$_POST['coupon_id'];
    $code        = strtoupper(trim($_POST['code']));
    $discount    = (int)$_POST['discount'];
    $usage_limit = (int)$_POST['usage_limit'];
    $valid_until = trim($_POST['valid_until']);
    $firm_id     = $_POST['firm_id'] !== '' ? (int)$_POST['firm_id'] : null;

    if ($valid_until && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $valid_until)) {
        $valid_until = date('Y-m-d', strtotime($valid_until));
    }

    $stmt = $db->prepare("UPDATE coupons
                          SET code=?, discount=?, usage_limit=?, expiry_date=?, firm_id=?
                          WHERE id=?");
    $stmt->execute([$code, $discount, $usage_limit, $valid_until, $firm_id, $id]);
    header("Location: admin_panel.php");
    exit;
}

    if ($action === 'delete_coupon') {
        $id = (int)$_POST['coupon_id'];
        $stmt = $db->prepare("DELETE FROM coupons WHERE id=?");
        $stmt->execute([$id]);
        header("Location: admin_panel.php"); exit;
    }

    // 5) Firma Admin Sil
if ($action === 'delete_firma_admin') {
    $id = (int)$_POST['admin_id'];
    // firmadan admin bağlantısını kaldır
    $db->prepare("UPDATE firms SET admin_id = NULL WHERE admin_id = ?")->execute([$id]);
    // kullanıcıyı sil
    $db->prepare("DELETE FROM users WHERE id = ? AND role = 'firma_admin'")->execute([$id]);
    header("Location: admin_panel.php"); exit;
}

// 6) Firma Admin Düzenle
if ($action === 'update_firma_admin') {
    $id    = (int)$_POST['admin_id'];
    $name  = trim($_POST['name']);
    $email = trim($_POST['email']);
    $firm_id = $_POST['firm_id'] !== '' ? (int)$_POST['firm_id'] : null;

    // kullanıcı güncelle
    $db->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?")->execute([$name, $email, $id]);

    // firmayı adminle eşleştir
    $db->prepare("UPDATE firms SET admin_id = NULL WHERE admin_id = ?")->execute([$id]);
    if ($firm_id) {
        $db->prepare("UPDATE firms SET admin_id = ? WHERE id = ?")->execute([$id, $firm_id]);
    }

    header("Location: admin_panel.php"); exit;
}

}

/* -------------------- DATA -------------------- */
// Firmalar
$firms = $db->query("SELECT id, name, description, admin_id FROM firms ORDER BY id DESC")
            ->fetchAll(PDO::FETCH_ASSOC);

// Firma admin adayları (mevcut)
$firmaAdmins = $db->query("SELECT id, name, email FROM users WHERE role='firma_admin' ORDER BY id DESC")
                 ->fetchAll(PDO::FETCH_ASSOC);

// Kuponlar
$coupons = $db->query("SELECT c.*, f.name AS firm_name
                       FROM coupons c
                       LEFT JOIN firms f ON c.firm_id = f.id
                       ORDER BY c.id DESC")->fetchAll(PDO::FETCH_ASSOC);

// Hızlı istatistikler
$totalFirms   = count($firms);
$totalAdmins  = $db->query("SELECT COUNT(*) FROM users WHERE role='firma_admin'")->fetchColumn();
$totalCoupons = $db->query("SELECT COUNT(*) FROM coupons")->fetchColumn();

?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Admin Paneli</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="bg-light">

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top mb-4">
  <div class="container">
    <a class="navbar-brand fw-bold text-primary" href="index.php">🚌 OBÜSTÜK</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div id="nav" class="collapse navbar-collapse">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link active" href="admin_panel.php">⚙️ Admin Paneli</a></li>
      </ul>
      <ul class="navbar-nav ms-auto">
        <li class="nav-item d-flex align-items-center me-3 small text-muted">
          👋 <strong class="ms-1"><?= $_SESSION['user']['name'] ?></strong>
        </li>
        <li class="nav-item"><a class="btn btn-outline-danger btn-sm" href="logout.php">Çıkış Yap</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container">
  <h2 class="fw-bold text-primary mb-3">Admin Paneli</h2>
  <p class="text-muted">Sistemdeki en yetkili rolsün. Firmalar, firma adminleri ve kuponları yönetebilirsin.</p>

  <!-- DASHBOARD KARTLARI -->
  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <div class="card shadow-sm border-0">
        <div class="card-body text-center">
          <div class="text-muted">Toplam Firma</div>
          <div class="fs-3 fw-bold text-primary"><?= $totalFirms ?></div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card shadow-sm border-0">
        <div class="card-body text-center">
          <div class="text-muted">Firma Admin Sayısı</div>
          <div class="fs-3 fw-bold text-success"><?= $totalAdmins ?></div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card shadow-sm border-0">
        <div class="card-body text-center">
          <div class="text-muted">Toplam Kupon</div>
          <div class="fs-3 fw-bold text-warning"><?= $totalCoupons ?></div>
        </div>
      </div>
    </div>
  </div>

  <!-- FIRMALAR -->
  <div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white fw-semibold">🏢 Firmalar</div>
    <div class="card-body">
      <!-- Firma Ekle -->
      <form class="row g-3 mb-3" method="POST">
        <input type="hidden" name="action" value="add_firm">
        <div class="col-md-4">
          <label class="form-label fw-semibold">Firma Adı</label>
          <input type="text" name="name" class="form-control" required>
        </div>
        <div class="col-md-7">
          <label class="form-label fw-semibold">Açıklama</label>
          <input type="text" name="description" class="form-control">
        </div>
        <div class="col-md-1 d-grid align-items-end">
          <button class="btn btn-success">Ekle</button>
        </div>
      </form>

      <!-- Firma Liste -->
      <div class="table-responsive">
        <table class="table table-bordered align-middle">
          <thead class="table-light">
            <tr>
              <th>ID</th>
              <th>Firma</th>
              <th>Açıklama</th>
              <th>Firma Admin</th>
              <th>İşlemler</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($firms as $f): ?>
            <tr>
              <td><?= $f['id'] ?></td>
              <td><?= htmlspecialchars($f['name']) ?></td>
              <td><?= htmlspecialchars($f['description']) ?></td>
              <td>
                <?php
                if ($f['admin_id']) {
                    $u = $db->prepare("SELECT name, email FROM users WHERE id=?");
                    $u->execute([$f['admin_id']]);
                    if ($row = $u->fetch(PDO::FETCH_ASSOC)) {
                        echo htmlspecialchars($row['name']) . " <small class='text-muted'>(" . htmlspecialchars($row['email']) . ")</small>";
                    } else {
                        echo "<em>—</em>";
                    }
                } else {
                    echo "<em>Atanmamış</em>";
                }
                ?>
              </td>
              <td>
  <button class="btn btn-sm btn-outline-warning"
          data-bs-toggle="modal"
          data-bs-target="#editFirmModal"
          data-id="<?= $f['id'] ?>"
          data-name="<?= htmlspecialchars($f['name']) ?>"
          data-description="<?= htmlspecialchars($f['description']) ?>">
    ✏️ Düzenle
  </button>

  <form method="POST" action="admin_panel.php" style="display:inline-block" onsubmit="return confirm('Bu firma silinsin mi?')">
    <input type="hidden" name="action" value="delete_firm">
    <input type="hidden" name="firm_id" value="<?= $f['id'] ?>">
    <button class="btn btn-sm btn-outline-danger">🗑️ Sil</button>
  </form>
</td>

            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- FIRMA ADMIN OLUŞTUR & ATAMA -->
  <div class="card shadow-sm mb-4">
    <div class="card-header bg-dark text-white fw-semibold">👤 Firma Admin Oluştur ve Ata</div>
    <div class="card-body">
      <form class="row g-3" method="POST">
        <input type="hidden" name="action" value="create_firma_admin">
        <div class="col-md-3">
          <label class="form-label fw-semibold">Ad Soyad</label>
          <input type="text" name="fa_name" class="form-control" required>
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold">E-posta</label>
          <input type="email" name="fa_email" class="form-control" required>
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold">Şifre</label>
          <input type="password" name="fa_password" class="form-control" required>
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold">Bağlanacak Firma</label>
          <select name="fa_firm_id" class="form-select" required>
            <option value="" disabled selected>Seçin</option>
            <?php foreach ($firms as $f): ?>
              <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-12 d-grid">
          <button class="btn btn-dark">Firma Admini Oluştur ve Ata</button>
        </div>
      </form>
    </div>
  </div>

    <!-- 📋 Firma Admin Listesi -->
  <div class="card shadow-sm mb-4">
    <div class="card-header bg-secondary text-white fw-semibold">👥 Mevcut Firma Adminleri</div>
    <div class="card-body">
      <?php
      $stmt = $db->query("
          SELECT 
              u.id AS user_id,
              u.name AS user_name,
              u.email,
              f.name AS firm_name
          FROM users u
          LEFT JOIN firms f ON f.admin_id = u.id
          WHERE u.role = 'firma_admin'
          ORDER BY f.name ASC
      ");
      $firmaAdminler = $stmt->fetchAll(PDO::FETCH_ASSOC);
      ?>

      <?php if (count($firmaAdminler) > 0): ?>
        <div class="table-responsive">
          <table class="table table-bordered align-middle">
            <thead class="table-light">
              <tr>
                <th>Ad Soyad</th>
                <th>E-posta</th>
                <th>Bağlı Firma</th>
                <th>İşlemler</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($firmaAdminler as $a): ?>
                <tr>
                  <td><?= htmlspecialchars($a['user_name']) ?></td>
                  <td><?= htmlspecialchars($a['email']) ?></td>
                  <td><?= $a['firm_name'] ? htmlspecialchars($a['firm_name']) : '<em>Atanmamış</em>' ?></td>
                  <td>
  <button class="btn btn-sm btn-outline-warning"
          data-bs-toggle="modal"
          data-bs-target="#editAdminModal"
          data-id="<?= $a['user_id'] ?>"
          data-name="<?= htmlspecialchars($a['user_name']) ?>"
          data-email="<?= htmlspecialchars($a['email']) ?>"
          data-firm="<?= htmlspecialchars($a['firm_name'] ?? '') ?>">
    ✏️ Düzenle
  </button>

  <form method="POST" action="admin_panel.php" style="display:inline-block" onsubmit="return confirm('Bu kullanıcı silinsin mi?')">
    <input type="hidden" name="action" value="delete_firma_admin">
    <input type="hidden" name="admin_id" value="<?= $a['user_id'] ?>">
    <button class="btn btn-sm btn-outline-danger">🗑️ Sil</button>
  </form>
</td>

                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <p class="text-muted text-center mb-0">Henüz kayıtlı firma admini bulunmuyor.</p>
      <?php endif; ?>
    </div>
  </div>
<style>
.card-header { border-top-left-radius: .5rem; border-top-right-radius: .5rem; }
.table td, .table th { vertical-align: middle; }
</style>

<!-- Firma Admin Düzenleme Modal -->
<div class="modal fade" id="editAdminModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title">Firma Admin Düzenle</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <form method="POST" action="admin_panel.php" class="modal-body">
        <input type="hidden" name="action" value="update_firma_admin">
        <input type="hidden" name="admin_id" id="editAdminId">

        <div class="mb-3">
          <label class="form-label fw-semibold">Ad Soyad</label>
          <input type="text" name="name" id="editAdminName" class="form-control" required>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">E-posta</label>
          <input type="email" name="email" id="editAdminEmail" class="form-control" required>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Bağlı Firma</label>
          <select name="firm_id" id="editAdminFirm" class="form-select">
            <option value="">Atanmamış</option>
            <?php foreach ($firms as $f): ?>
              <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="text-end">
          <button type="submit" class="btn btn-success">Kaydet</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
        </div>
      </form>
    </div>
  </div>
</div>


  <!-- KUPON YÖNETİMİ -->
  <div class="card shadow-sm mb-5">
    <div class="card-header bg-success text-white fw-semibold">🎟️ Kupon Yönetimi</div>
    <div class="card-body">
      <!-- Kupon Ekle -->
      <form class="row g-3 mb-3" method="POST">
        <input type="hidden" name="action" value="add_coupon">
        <div class="col-md-2">
          <label class="form-label fw-semibold">Kupon Kodu</label>
          <input type="text" name="code" class="form-control" placeholder="INDIRIM10" required>
        </div>
        <div class="col-md-2">
          <label class="form-label fw-semibold">İndirim (%)</label>
          <input type="number" name="discount" min="1" max="100" class="form-control" required>
        </div>
        <div class="col-md-2">
          <label class="form-label fw-semibold">Limit</label>
          <input type="number" name="usage_limit" min="1" value="1" class="form-control" required>
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold">Son Geçerlilik Tarihi</label>
          <input type="date" name="valid_until" class="form-control" required>
        </div>
        <div class="col-md-2">
          <label class="form-label fw-semibold">Geçerli Olduğu Firma</label>
          <select name="firm_id" class="form-select">
            <option value="">Tüm Firmalar</option>
            <?php foreach ($firms as $f): ?>
              <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['name']) ?></option>
            <?php endforeach; ?>
          </select>
            </div>
        <div class="col-12 d-grid">
          <button class="btn btn-success">Kuponu Ekle</button>
        </div>
      </form>

     <!-- Kupon Liste -->
<div class="table-responsive">
  <table class="table table-bordered align-middle">
    <thead class="table-light">
      <tr>
        <th>Kod</th>
        <th>İndirim</th>
        <th>Geçerlilik</th>
        <th>Firma</th>
        <th>Kullanım</th>
        <th>İşlem</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($coupons as $c): ?>
        <tr>
          <!-- Kod -->
          <td><?= htmlspecialchars($c['code']) ?></td>

          <!-- İndirim -->
          <td>%<?= (int)$c['discount'] ?></td>

          <!-- Geçerlilik (tarih) -->
          <td><?= htmlspecialchars($c['expiry_date'] ?? '-') ?></td>

          <!-- Firma -->
          <td><?= $c['firm_name'] ? htmlspecialchars($c['firm_name']) : '<em>Tüm Firmalar</em>' ?></td>

          <!-- Kullanım -->
          <td><?= (int)$c['used_count'] ?>/<?= (int)$c['usage_limit'] ?></td>

          <!-- İşlem -->
          <td class="d-flex gap-2">
  <button class="btn btn-sm btn-outline-secondary"
          data-bs-toggle="modal"
          data-bs-target="#editCouponModal"
          data-id="<?= $c['id'] ?>"
          data-code="<?= htmlspecialchars($c['code']) ?>"
          data-discount="<?= (int)$c['discount'] ?>"
          data-usage_limit="<?= (int)$c['usage_limit'] ?>"
          data-date="<?= htmlspecialchars($c['expiry_date']) ?>"
          data-firm_id="<?= (int)$c['firm_id'] ?>">
    Düzenle
  </button>

  <form method="POST" onsubmit="return confirm('Kupon silinsin mi?')">
    <input type="hidden" name="action" value="delete_coupon">
    <input type="hidden" name="coupon_id" value="<?= (int)$c['id'] ?>">
    <button class="btn btn-sm btn-outline-danger">Sil</button>
  </form>
</td>

        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>


    </div>
  </div>

</div>
<!-- Firma Düzenleme Modal -->
<div class="modal fade" id="editFirmModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">🏢 Firma Düzenle</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <form method="POST" action="admin_panel.php" class="modal-body">
        <input type="hidden" name="action" value="update_firm">
        <input type="hidden" name="firm_id" id="editFirmId">

        <div class="mb-3">
          <label class="form-label fw-semibold">Firma Adı</label>
          <input type="text" name="name" id="editFirmName" class="form-control" required>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Açıklama</label>
          <textarea name="description" id="editFirmDesc" class="form-control" rows="3"></textarea>
        </div>

        <div class="text-end">
          <button type="submit" class="btn btn-success">Kaydet</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Kupon Düzenleme Modal -->
<div class="modal fade" id="editCouponModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title">Kupon Düzenle</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <form method="POST" action="admin_panel.php" class="modal-body row g-3">
        <input type="hidden" name="action" value="update_coupon">
        <input type="hidden" name="coupon_id" id="editCouponId">

        <div class="col-md-3">
          <label class="form-label fw-semibold">Kupon Kodu</label>
          <input type="text" name="code" id="editCouponCode" class="form-control" required>
        </div>

        <div class="col-md-2">
          <label class="form-label fw-semibold">İndirim (%)</label>
          <input type="number" name="discount" id="editCouponDiscount" min="1" max="100" class="form-control" required>
        </div>

        <div class="col-md-2">
          <label class="form-label fw-semibold">Kullanım Limiti</label>
          <input type="number" name="usage_limit" id="editCouponLimit" min="1" class="form-control" required>
        </div>

        <div class="col-md-3">
          <label class="form-label fw-semibold">Son Kullanma Tarihi</label>
          <input type="date" name="valid_until" id="editCouponValidUntil" class="form-control">
        </div>

        <div class="col-md-2">
          <label class="form-label fw-semibold">Firma</label>
          <select name="firm_id" id="editCouponFirm" class="form-select">
            <option value="">Tüm Firmalar</option>
            <?php foreach ($firms as $f): ?>
              <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-12 text-end mt-3">
          <button type="submit" class="btn btn-success">Kaydet</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const editModal = document.getElementById('editCouponModal');
  editModal.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;

    document.getElementById('editCouponId').value = button.getAttribute('data-id');
    document.getElementById('editCouponCode').value = button.getAttribute('data-code');
    document.getElementById('editCouponDiscount').value = button.getAttribute('data-discount');
    document.getElementById('editCouponLimit').value = button.getAttribute('data-usage_limit');
    document.getElementById('editCouponValidUntil').value = button.getAttribute('data-valid_until');
    document.getElementById('editCouponFirm').value = button.getAttribute('data-firm_id');
  });
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const editModal = document.getElementById('editAdminModal');
  editModal.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    document.getElementById('editAdminId').value = button.getAttribute('data-id');
    document.getElementById('editAdminName').value = button.getAttribute('data-name');
    document.getElementById('editAdminEmail').value = button.getAttribute('data-email');
  });
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const firmModal = document.getElementById('editFirmModal');
  firmModal.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    document.getElementById('editFirmId').value = button.getAttribute('data-id');
    document.getElementById('editFirmName').value = button.getAttribute('data-name');
    document.getElementById('editFirmDesc').value = button.getAttribute('data-description');
  });
});
</script>

</body>
</html>
