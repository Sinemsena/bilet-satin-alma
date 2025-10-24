<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'firma_admin') {
 header('Location: login.php');
 exit;
}

// Varsayımsal veritabanı bağlantısı
include 'includes/db.php'; 

$cities = [
    'Adana',
    'Adıyaman',
    'Afyonkarahisar',
    'Ağrı',
    'Aksaray',
    'Amasya',
    'Ankara',
    'Antalya',
    'Ardahan',
    'Artvin',
    'Aydın',
    'Balıkesir',
    'Bartın',
    'Batman',
    'Bayburt',
    'Bilecik',
    'Bingöl',
    'Bitlis',
    'Bolu',
    'Burdur',
    'Bursa',
    'Çanakkale',
    'Çankırı',
    'Çorum',
    'Denizli',
    'Diyarbakır',
    'Düzce',
    'Edirne',
    'Elazığ',
    'Erzincan',
    'Erzurum',
    'Eskişehir',
    'Gaziantep',
    'Giresun',
    'Gümüşhane',
    'Hakkâri',
    'Hatay',
    'Iğdır',
    'Isparta',
    'İstanbul',
    'İzmir',
    'Kahramanmaraş',
    'Karabük',
    'Karaman',
    'Kars',
    'Kastamonu',
    'Kayseri',
    'Kilis',
    'Kırıkkale',
    'Kırklareli',
    'Kırşehir',
    'Kocaeli',
    'Konya',
    'Kütahya',
    'Malatya',
    'Manisa',
    'Mardin',
    'Mersin',
    'Muğla',
    'Muş',
    'Nevşehir',
    'Niğde',
    'Ordu',
    'Osmaniye',
    'Rize',
    'Sakarya',
    'Samsun',
    'Şanlıurfa',
    'Siirt',
    'Sinop',
    'Sivas',
    'Şırnak',
    'Tekirdağ',
    'Tokat',
    'Trabzon',
    'Tunceli',
    'Uşak',
    'Van',
    'Yalova',
    'Yozgat',
    'Zonguldak'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

 // Giriş yapan firma admininin firmasını bul
 $stmt = $db->prepare("SELECT id FROM firms WHERE admin_id = ? LIMIT 1");
 $stmt->execute([$_SESSION['user']['id']]);
 $firma = $stmt->fetch(PDO::FETCH_ASSOC);

 if (!$firma) {
die("Bu kullanıcıya ait firma bulunamadı! Lütfen yöneticinizle iletişime geçin.");
 }

 $firmaId = $firma['id'];

// Güvenlik için prepared statement kullandım.
$stmt = $db->prepare("INSERT INTO routes (firm_id, departure, arrival, date, time, price, seats) VALUES (?, ?, ?, ?, ?, ?, ?)");
 $stmt->execute([
 $firmaId, 
$_POST['departure'], 
$_POST['arrival'], 
 $_POST['date'], 
 $_POST['time'], 
 $_POST['price'], 
$_POST['seats']
 ]);

 // Başarılı eklemeden sonra panele yönlendir
 header('Location: firma_admin_panel.php');
 exit;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
 <meta charset="UTF-8">
 <meta name="viewport" content="width=device-width, initial-scale=1.0">
 <title>Yeni Sefer Ekle | Firma Paneli</title>
 <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
 <style>
 /* Form kartını ortalamak ve boyutunu sınırlamak için özel stil */
 .form-card {
 max-width: 600px;
 margin: 50px auto;
 }
 body {
 background-color: #f8f9fa; /* Hafif bir arka plan rengi */
 }
 .input-group-text {
 background-color: #e9ecef;
 color: #0d6efd;
 border-right: none;
 }
 </style>
</head>
<body>

<!-- Basit bir Navbar -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-5">
 <div class="container">
 <a class="navbar-brand fw-bold text-primary" href="firma_admin_panel.php">🚌 OBÜSTÜK</a>
 <span class="navbar-text ms-auto">
 👋 Hoş Geldiniz, <strong><?= $_SESSION['user']['name'] ?></strong>
 </span>
 </div>
</nav>

<div class="container">
 <div class="card shadow-lg form-card border-primary">
 <div class="card-header bg-primary text-white text-center py-3">
 <h3 class="mb-0 fw-bold">✨ Yeni Sefer Tanımla</h3>
</div>
<div class="card-body p-4">
<form method="POST">

<!-- Kalkış Noktası -->
 <div class="mb-3">
 <label for="departure" class="form-label fw-semibold">Kalkış Noktası</label>
 <select name="departure" id="departure" class="form-select" required>
                <option value="" disabled selected>Kalkış noktasını seçiniz</option>
                <?php foreach ($cities as $city): ?>
                    <option value="<?= htmlspecialchars($city) ?>"><?= htmlspecialchars($city) ?></option>
                <?php endforeach; ?>
            </select>
 </div>

 <!-- Varış Noktası -->
 <div class="mb-3">
 <label for="arrival" class="form-label fw-semibold">Varış Noktası</label>
            <select name="arrival" id="arrival" class="form-select" required>
                <option value="" disabled selected>Varış noktasını seçiniz</option>
                <?php foreach ($cities as $city): ?>
                    <option value="<?= htmlspecialchars($city) ?>"><?= htmlspecialchars($city) ?></option>
                <?php endforeach; ?>
            </select>
 </div>

 <!-- Tarih ve Saat (Yan Yana) -->
 <div class="row mb-3">
 <div class="col-md-6">
 <label for="date" class="form-label fw-semibold">Tarih</label>
 <input type="date" name="date" id="date" class="form-control" required>
 </div>
 <div class="col-md-6">
<label for="time" class="form-label fw-semibold">Saat</label>
 <input type="time" name="time" id="time" class="form-control" required>
 </div>
 </div>

 <!-- Fiyat ve Koltuk Sayısı (Yan Yana) -->
<div class="row mb-4">
<div class="col-md-6">
 <label for="price" class="form-label fw-semibold">Bilet Fiyatı</label>
 <div class="input-group">
 <input type="number" name="price" id="price" class="form-control" placeholder="Örn: 500" min="1" required>
 <span class="input-group-text">₺</span>
 </div>
 </div>
 <div class="col-md-6">
 <label for="seats" class="form-label fw-semibold">Koltuk Kapasitesi</label>
 <input type="number" name="seats" id="seats" class="form-control" placeholder="Örn: 40" min="1" required>
 </div>
 </div>

<!-- Kaydet Butonu -->
 <div class="d-grid gap-2">
 <button type="submit" class="btn btn-primary btn-lg">Seferi Kaydet</button>
 </div>
 </form>
 </div>
 <div class="card-footer text-center bg-light">
 <a href="firma_admin_panel.php" class="text-secondary small fw-semibold">← Firma Paneline Geri Dön</a>
 </div>
 </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
