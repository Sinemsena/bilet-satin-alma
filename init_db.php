<?php
try {
    $db = new PDO('sqlite:database.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // USERS tablosu
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT,
        email TEXT UNIQUE,
        password TEXT,
        role TEXT DEFAULT 'user',
        credit REAL DEFAULT 1000000
    )");

    // FIRMS tablosu
    $db->exec("CREATE TABLE IF NOT EXISTS firms (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT,
        description TEXT,
        admin_id INTEGER UNIQUE,
        FOREIGN KEY (admin_id) REFERENCES users(id)
    )");

    // ROUTES tablosu
    $db->exec("CREATE TABLE IF NOT EXISTS routes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        firm_id INTEGER,
        departure TEXT,
        arrival TEXT,
        date TEXT,
        time TEXT,
        price REAL,
        seats INTEGER,
        FOREIGN KEY (firm_id) REFERENCES firms(id)
    )");

    // TICKETS tablosu
    $db->exec("CREATE TABLE IF NOT EXISTS tickets (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        route_id INTEGER,
        seat_number INTEGER,
        price REAL,
        status TEXT DEFAULT 'active',
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (route_id) REFERENCES routes(id)
    )");

    // COUPONS tablosu
    /*$db->exec("CREATE TABLE IF NOT EXISTS coupons (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        code TEXT UNIQUE,
        discount REAL,
        limit_count INTEGER,
        expiry_date TEXT,
        firm_id INTEGER NULL
    )");*/
    $db->exec("CREATE TABLE IF NOT EXISTS coupons (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT UNIQUE,
    discount INTEGER,
    expiry_date TEXT,
    usage_limit INTEGER DEFAULT 1, 
    used_count INTEGER DEFAULT 0, 
    active INTEGER DEFAULT 1,
    firm_id INTEGER DEFAULT NULL,
    FOREIGN KEY (firm_id) REFERENCES firms(id)
    )");


    $db->exec("CREATE TABLE IF NOT EXISTS coupon_usage (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    coupon_code TEXT,
    used_at TEXT
)");




    echo "✅ Veritabanı başarıyla oluşturuldu!";
} catch (PDOException $e) {
    echo "Hata: " . $e->getMessage();
}
?>
