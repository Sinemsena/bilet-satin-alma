<?php
session_start();
include 'includes/db.php';
require_once 'includes/csrf.php'; // 🔹 CSRF helper dosyasını ekle
csrf_init(); // 🔹 Token oluştur (yoksa yeni üret)

$message = '';

if (isset($_GET['success'])) {
    $message = '<div class="alert alert-success">' . htmlspecialchars($_GET['success']) . '</div>';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 🔒 CSRF doğrulaması
    if (!csrf_validate()) {
        http_response_code(403);
        die('<div class="alert alert-danger text-center mt-3">⚠️ Geçersiz işlem (CSRF doğrulaması başarısız)!</div>');
    }

    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = $user;

        // 🔐 Güvenlik: session sabitleme önlemi
        session_regenerate_id(true);

        // Rol bazlı yönlendirme
        switch ($user['role']) {
            case 'admin':
                header('Location: admin_panel.php');
                break;
            case 'firma_admin':
                header('Location: firma_admin_panel.php');
                break;
            default:
                header('Location: index.php');
                break;
        }
        exit;
    } else {
        $message = '<div class="alert alert-danger">❌ E-posta veya şifre hatalı!</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #e9ecef; }
        .login-container { max-width: 400px; margin: 100px auto; }
    </style>
</head>
<body>

<div class="container">
    <div class="card shadow-lg login-container border-primary">
        <div class="card-header bg-primary text-white text-center py-3">
            <h3 class="mb-0 fw-bold">🔐 Giriş Yap</h3>
        </div>
        <div class="card-body p-4">
            <?= $message ?>
            <form method="POST">
                <!-- 🔹 CSRF token alanı -->
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label for="email" class="form-label fw-semibold">E-posta</label>
                    <input type="email" name="email" id="email" class="form-control" placeholder="eposta@adresiniz.com" required>
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label fw-semibold">Şifre</label>
                    <input type="password" name="password" id="password" class="form-control" required>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg">Giriş Yap</button>
                </div>
            </form>
        </div>
        <div class="card-footer text-center bg-light">
            <p class="mb-1"><a href="register.php" class="text-secondary small fw-semibold">Hesabınız yok mu? → Kayıt Ol</a></p>
            
        </div>
    </div>
</div>

</body>
</html>
