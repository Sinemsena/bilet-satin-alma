<?php
// includes/csrf.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function csrf_init() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        // optional: token timestamp
        $_SESSION['csrf_token_time'] = time();
    }
}

function csrf_get_token() {
    csrf_init();
    return $_SESSION['csrf_token'];
}

// form içine kolay ekleme
function csrf_field() {
    $t = htmlspecialchars(csrf_get_token(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $t . '">';
}

// token doğrulama, hata durumunda false döner
function csrf_validate($incoming = null) {
    csrf_init();
    if ($incoming === null) {
        if (isset($_POST['csrf_token'])) $incoming = $_POST['csrf_token'];
        elseif (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) $incoming = $_SERVER['HTTP_X_CSRF_TOKEN'];
        else return false;
    }
    return hash_equals($_SESSION['csrf_token'], (string)$incoming);
}
