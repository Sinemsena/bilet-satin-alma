Bu proje, kullanıcıların çevrim içi ortamda bilet satın alma işlemlerini kolaylaştırmak amacıyla geliştirilmiştir.
Sistem, PHP tabanlı olup SQLite veritabanı kullanılmıştır ve Docker ile containerize edilmiştir.

⚙️ Teknolojiler
PHP 8.2
SQLite / PDO
HTML, CSS, JavaScript
Docker (PHP + Apache imajı)
XAMPP (lokal geliştirme ortamı)

Kurulum (Lokal)

Bu projeyi bilgisayarına klonla ve Tarayıcıdan aç:
http://localhost/bilet-satin-alma/index.php

Sistem Gereksinimleri ve Kullanıcı Rolleri:
Otobüs bileti satışı yapılabilen bir bilet satın alma platformudur. Platform, üç farklı
kullanıcı rolünü desteklemektedir: Admin, Firma Admin (Firma Yetkilisi) ve User (Yolcu).

seed.php isimli dosya en yetkili kişi olan admin hesabı hakkında bilgiler içeriyor.
Kullanıcı adı: admin@example.com
Şifre : admin123

