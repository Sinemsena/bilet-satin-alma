<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'firma_admin') {
    header('Location: login.php');
    exit;
}

include 'includes/db.php';

// --- GÜVENLİK KONTROLLERİ ---

// 1. Giriş yapan firma admininin firmasını bul
$stmt = $db->prepare("SELECT id FROM firms WHERE admin_id = ? LIMIT 1");
$stmt->execute([$_SESSION['user']['id']]);
$firma = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$firma){ die('<div style="padding: 20px; background-color: #f44336; color: white; margin: 10px;">🚫 Hata: Bu kullanıcıya ait firma bulunamadı! Lütfen yöneticinizle iletişime geçin.</div>');
}
$firmaId = $firma['id'];

// 2. Düzenlenecek sefer id'sini al
$seferId = $_GET['id'] ?? null;

if (!$seferId) {
    die('<div style="padding: 20px; background-color: #ff9800; color: white; margin: 10px;">🚫 Hata: Düzenlenecek sefer ID\'si eksik.</div>');
}

// 3. Sefer bu firmaya mı ait?
$stmt = $db->prepare("SELECT * FROM routes WHERE id = ? AND firm_id = ?");
$stmt->execute([$seferId, $firmaId]);
$sefer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sefer) {
    die('<div style="padding: 20px; background-color: #ff9800; color: white; margin: 10px;">🚫 Bu sefer size ait değil veya bulunamadı!</div>');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $departure = $_POST['departure'];
    $arrival = $_POST['arrival'];
    $date = $_POST['date'];
    $time = $_POST['time'];
    $price = $_POST['price'];
    $seats = $_POST['seats'];

     if (empty($departure) || empty($arrival) || empty($date) || empty($time) || $price <= 0 || $seats <= 0) {
         $message = '<div class="alert alert-danger">Lütfen tüm alanları geçerli değerlerle doldurun.</div>';
    } else {
    $stmt = $db->prepare("UPDATE routes SET departure=?, arrival=?, date=?, time=?, price=?, seats=? WHERE id=? AND firm_id=?");
    $result = $stmt->execute([$departure, $arrival, $date, $time, $price, $seats, $seferId, $firmaId]);

    if ($result) {
            // Başarılı kaydetme sonrası admin paneline yönlendir
            header('Location: firma_admin_panel.php?success=Sefer başarıyla güncellendi!');
            exit;
        } else {
             $message = '<div class="alert alert-danger">Güncelleme sırasında bir hata oluştu.</div>';
        }
    }
}
$iller = ["Adana", "Adıyaman", "Afyonkarahisar", "Ağrı", 'Aksaray', "Amasya", "Ankara", "Antalya", "Artvin", "Aydın", "Balıkesir", "Bilecik", "Bingöl", "Bitlis", "Bolu", "Burdur", "Bursa", "Çanakkale", "Çankırı", "Çorum", "Denizli", "Diyarbakır", "Düzce", "Edirne", "Elazığ", "Erzincan", "Erzurum", "Eskişehir", "Gaziantep", "Giresun", "Gümüşhane", "Hakkari", "Hatay", "Iğdır", "Isparta", "İstanbul", "İzmir", "Kahramanmaraş", "Karabük", "Karaman", "Kars", "Kastamonu", "Kayseri", "Kilis", "Kırıkkale", "Kırklareli", "Kırşehir", "Kocaeli", "Konya", "Kütahya", "Malatya", "Manisa", "Mardin", "Mersin", "Muğla", "Muş", "Nevşehir", "Niğde", "Ordu", "Osmaniye", "Rize", "Sakarya", "Samsun", "Şanlıurfa", "Siirt", "Sinop", "Sivas", "Şırnak", "Tekirdağ", "Tokat", "Trabzon", "Tunceli", "Uşak", "Van", "Yalova", "Yozgat", "Zonguldak"];
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sefer Düzenle - <?= htmlspecialchars($sefer['departure']) ?> → <?= htmlspecialchars($sefer['arrival']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f7f6; }
        .card-container { max-width: 600px; margin: 50px auto; }
    </style>
</head>
<body>

<div class="container">
    <div class="card shadow-lg card-container border-primary">
        <div class="card-header bg-primary text-white py-3">
            <h3 class="mb-0 fw-bold">✏️ Sefer Düzenle</h3>
            <p class="mb-0 small"><?= htmlspecialchars($sefer['departure']) ?> &rarr; <?= htmlspecialchars($sefer['arrival']) ?></p>
        </div>
        <div class="card-body p-4">
            <?= $message ?? '' ?>
            <form method="POST">
                
                <!-- Kalkış Noktası -->
                <div class="mb-3">
                    <label for="departure" class="form-label fw-semibold">Kalkış Noktası</label>
                    <select name="departure" id="departure" class="form-select" required>
                        <?php foreach ($iller as $il): ?>
                            <option value="<?= htmlspecialchars($il) ?>" <?= $sefer['departure'] === $il ? 'selected' : '' ?>>
                                <?= htmlspecialchars($il) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Varış Noktası -->
                <div class="mb-3">
                    <label for="arrival" class="form-label fw-semibold">Varış Noktası</label>
                    <select name="arrival" id="arrival" class="form-select" required>
                        <?php foreach ($iller as $il): ?>
                            <option value="<?= htmlspecialchars($il) ?>" <?= $sefer['arrival'] === $il ? 'selected' : '' ?>>
                                <?= htmlspecialchars($il) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Tarih ve Saat -->
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="date" class="form-label fw-semibold">Tarih</label>
                        <input type="date" name="date" id="date" class="form-control" value="<?= htmlspecialchars($sefer['date']) ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="time" class="form-label fw-semibold">Saat</label>
                        <input type="time" name="time" id="time" class="form-control" value="<?= htmlspecialchars($sefer['time']) ?>" required>
                    </div>
                </div>
                
                <!-- Fiyat ve Koltuk Sayısı -->
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <label for="price" class="form-label fw-semibold">Fiyat (₺)</label>
                        <input type="number" name="price" id="price" class="form-control" value="<?= htmlspecialchars($sefer['price']) ?>" min="0.01" step="0.01" required>
                    </div>
                    <div class="col-md-6 mb-4">
                        <label for="seats" class="form-label fw-semibold">Koltuk Sayısı</label>
                        <input type="number" name="seats" id="seats" class="form-control" value="<?= htmlspecialchars($sefer['seats']) ?>" min="1" required>
                    </div>
                </div>

                <!-- Kaydet Butonu -->
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-success btn-lg">Değişiklikleri Kaydet</button>
                </div>
            </form>
        </div>
        <div class="card-footer text-center bg-light">
            <a href="firma_admin_panel.php" class="text-primary fw-semibold">← Firma Admin Paneline Geri Dön</a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
