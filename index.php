<?php
session_start();
include 'includes/db.php';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Otobüs Bileti Satın Al</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <style>
        body {
            background-color: #f8f9fa;
        }
        .search-box {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            padding: 30px;
        }
        .search-btn {
            background-color: #00bfa6;
            color: white;
            font-weight: bold;
            border-radius: 50px;
            transition: 0.3s;
        }
        .search-btn:hover {
            background-color: #009f8c;
        }
        .trip-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.1);
            margin-top: 25px;
            padding: 20px;
        }
    </style>
</head>
<body>

<!-- 🔹 Navbar Başlangıcı -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
  <div class="container">
    <a class="navbar-brand fw-bold text-primary" href="index.php">🚌 OBÜSTÜK</a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="Menüyü Aç">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarContent">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link" href="index.php">Ana Sayfa</a></li>

        <?php if (isset($_SESSION['user'])): ?>
          <?php if ($_SESSION['user']['role'] === 'user'): ?>
            <li class="nav-item"><a class="nav-link" href="biletlerim.php">🎟️ Biletlerim</a></li>
          <?php endif; ?>

          <?php if ($_SESSION['user']['role'] === 'firma_admin'): ?>
            <li class="nav-item"><a class="nav-link" href="firma_admin_panel.php">🏢 Firma Paneli</a></li>
          <?php endif; ?>

          <?php if ($_SESSION['user']['role'] === 'admin'): ?>
            <li class="nav-item"><a class="nav-link" href="admin_panel.php">⚙️ Admin Paneli</a></li>
          <?php endif; ?>
        <?php endif; ?>
      </ul>

      <ul class="navbar-nav ms-auto">
        <?php if (isset($_SESSION['user'])): ?>
          <li class="nav-item d-flex align-items-center me-3">
            <span class="text-muted small">
              👋 <strong><?= $_SESSION['user']['name'] ?></strong> |
              💰 <?= $_SESSION['user']['credit'] ?> ₺
            </span>
          </li>
          <li class="nav-item">
            <a href="logout.php" class="btn btn-outline-danger btn-sm">Çıkış Yap</a>
          </li>
        <?php else: ?>
          <li class="nav-item me-2">
            <a href="login.php" class="btn btn-primary btn-sm">Giriş Yap</a>
          </li>
          <li class="nav-item">
            <a href="register.php" class="btn btn-outline-primary btn-sm">Kayıt Ol</a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
<!-- 🔹 Navbar Sonu -->

<div class="container py-5">
    <h2 class="text-center fw-bold mb-5 text-primary">🚌 Otobüs Bileti Satın Alma Platformu</h2>

    <!-- 🔹 Kullanıcı Durumu -->
<div class="mb-4 text-center">
    <?php if (isset($_SESSION['user'])): ?>
        <div class="alert alert-light border d-inline-block shadow-sm px-4 py-3">
            <p class="mb-1 fw-semibold">
                👋 Hoş geldin, <span class="text-primary"><?= $_SESSION['user']['name'] ?></span>
            </p>
            <p class="mb-2">💰 Bakiye: <strong><?= $_SESSION['user']['credit'] ?> ₺</strong></p>
            
            <div class="d-inline-flex gap-2">
                <?php if ($_SESSION['user']['role'] === 'user'): ?>
                    <a href="biletlerim.php" class="btn btn-outline-primary btn-sm">🎟️ Biletlerim</a>
                <?php endif; ?>

                <?php if ($_SESSION['user']['role'] === 'firma_admin'): ?>
                    <a href="firma_admin_panel.php" class="btn btn-outline-warning btn-sm">🚌 Firma Paneli</a>
                <?php endif; ?>

                <?php if ($_SESSION['user']['role'] === 'admin'): ?>
                    <a href="admin_panel.php" class="btn btn-outline-dark btn-sm">⚙️ Admin Paneli</a>
                <?php endif; ?>

                <a href="logout.php" class="btn btn-danger btn-sm">Çıkış Yap</a>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-light border d-inline-block shadow-sm px-4 py-3">
            <p class="fw-semibold mb-2">🚪 Henüz giriş yapmadınız.</p>
            <div class="d-flex justify-content-center gap-3">
                <a href="login.php" class="btn btn-primary btn-sm">Giriş Yap</a>
                <a href="register.php" class="btn btn-outline-primary btn-sm">Kayıt Ol</a>
            </div>
        </div>
    <?php endif; ?>
</div>

    <!-- 🔹 Arama Formu -->
    <div class="search-box">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Nereden</label>
                <select name="departure" class="form-select" required>
                    <option value="" selected disabled>Şehir Seçin</option>
                    <?php
                    $iller = ["Adana","Adıyaman","Afyonkarahisar","Ağrı","Amasya","Ankara","Antalya","Artvin","Aydın","Balıkesir",
                              "Bilecik","Bingöl","Bitlis","Bolu","Burdur","Bursa","Çanakkale","Çankırı","Çorum","Denizli",
                              "Diyarbakır","Edirne","Elazığ","Erzincan","Erzurum","Eskişehir","Gaziantep","Giresun","Gümüşhane",
                              "Hakkari","Hatay","Isparta","Mersin","İstanbul","İzmir","Kars","Kastamonu","Kayseri","Kırklareli",
                              "Kırşehir","Kocaeli","Konya","Kütahya","Malatya","Manisa","Kahramanmaraş","Mardin","Muğla","Muş",
                              "Nevşehir","Niğde","Ordu","Rize","Sakarya","Samsun","Siirt","Sinop","Sivas","Tekirdağ",
                              "Tokat","Trabzon","Tunceli","Şanlıurfa","Uşak","Van","Yozgat","Zonguldak","Aksaray","Bayburt",
                              "Karaman","Kırıkkale","Batman","Şırnak","Bartın","Ardahan","Iğdır","Yalova","Karabük","Kilis",
                              "Osmaniye","Düzce"];
                    foreach ($iller as $il) {
                        echo "<option value='$il'>$il</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Nereye</label>
                <select name="arrival" class="form-select" required>
                    <option value="" selected disabled>Şehir Seçin</option>
                    <?php
                    foreach ($iller as $il) {
                        echo "<option value='$il'>$il</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Gidiş Tarihi</label>
                <input type="text" id="date" name="date" class="form-control" placeholder="Tarih Seç" required>
            </div>

            <div class="col-md-1 d-grid">
                <button type="submit" class="btn search-btn">Otobüs Ara</button>
            </div>
        </form>
    </div>

    <script>
        flatpickr("#date", {
            minDate: "today",
            dateFormat: "Y-m-d",
            locale: "tr"
        });
    </script>

    <!-- 🔹 Sefer Sonuçları -->
    <div class="mt-5">
        <?php
        if (isset($_GET['departure'], $_GET['arrival'], $_GET['date'])) {
            $stmt = $db->prepare("SELECT r.*, f.name AS firm_name 
                                  FROM routes r 
                                  JOIN firms f ON r.firm_id = f.id
                                  WHERE r.departure LIKE ? 
                                    AND r.arrival LIKE ? 
                                    AND r.date = ?");
            $stmt->execute(['%' . $_GET['departure'] . '%', '%' . $_GET['arrival'] . '%', $_GET['date']]);
            $routes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($routes):
                echo '<h4 class="fw-bold mb-4">🚌 Uygun Seferler</h4>';
                foreach ($routes as $r): ?>
                    <div class="trip-card d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="fw-bold text-primary"><?= $r['firm_name'] ?></h5>
                            <p class="mb-1"><strong><?= $r['departure'] ?></strong> → <strong><?= $r['arrival'] ?></strong></p>
                            <p class="text-muted mb-0"><?= $r['date'] ?> | <?= $r['time'] ?> | <?= $r['seats'] ?> koltuk</p>
                        </div>
                        <div class="text-end">
                            <p class="fw-bold text-success fs-4 mb-2"><?= $r['price'] ?> ₺</p>
                            <a href="sefer_detay.php?id=<?= $r['id'] ?>" class="btn btn-outline-primary">Koltuk Seç</a>
                        </div>
                    </div>
                <?php endforeach;
            else:
                echo '<div class="alert alert-warning mt-4">❌ Uygun sefer bulunamadı.</div>';
            endif;
        }
        ?>
    </div>
</div>

</body>
</html>
