<?php
include 'includes/db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    if (empty($name) || empty($email) || empty($_POST['password'])) {
        $message = '<div class="alert alert-danger">Lütfen tüm alanları doldurun.</div>';
    } else {
    $stmt = $db->prepare("INSERT INTO users (name, email, password, role, credit) VALUES (?, ?, ?, 'user', 1000000)");
    try {
        $stmt->execute([$name, $email, $password]);
        header ('Location: login.php?success=✅ Kayıt başarılı! Giriş yapabilirsiniz.');
        exit;
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Integrity constraint violation: 1062 Duplicate entry') !== false) {
                 $message = '<div class="alert alert-warning">❌ Bu e-posta zaten kayıtlı! Lütfen farklı bir e-posta kullanın.</div>';
            } else {
                 $message = '<div class="alert alert-danger">Bir veritabanı hatası oluştu.</div>';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kayıt Ol</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #e9ecef; }
        .register-container { 
            max-width: 400px; 
            margin: 100px auto; 
        }
    </style>
</head>
<body>

<div class="container">
    <div class="card shadow-lg register-container border-success">
        <div class="card-header bg-success text-white text-center py-3">
            <h3 class="mb-0 fw-bold">🙋‍♂️ Yeni Kullanıcı Kaydı</h3>
        </div>
        <div class="card-body p-4">
            <?= $message ?>
            <form method="POST">
                <div class="mb-3">
                    <label for="name" class="form-label fw-semibold">Ad Soyad</label>
                    <input type="text" name="name" id="name" class="form-control" placeholder="Adınız Soyadınız" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label fw-semibold">E-posta</label>
                    <input type="email" name="email" id="email" class="form-control" placeholder="eposta@adresiniz.com" required>
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label fw-semibold">Şifre</label>
                    <input type="password" name="password" id="password" class="form-control" required>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-success btn-lg">Kayıt Ol</button>
                </div>
            </form>
        </div>
        <div class="card-footer text-center bg-light">
            <p class="mb-1"><a href="login.php" class="text-secondary small fw-semibold">Zaten hesabınız var mı? → Giriş Yap</a></p>
            
        </div>
    </div>
</div>

</body>
</html>