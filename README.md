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



👤 Kullanıcı Rolleri ve Yetkiler

Otobüs bileti satışı yapılabilen bir bilet satın alma platformudur. Platform, üç farklı
kullanıcı rolünü desteklemektedir: Admin, Firma Admin (Firma Yetkilisi) ve User (Yolcu).

1️⃣ Admin

Sistemin en yetkili kullanıcısıdır.

seed.php dosyasında admin hesabı tanımlıdır:

Admin, firma yetkilisi (firma admin) ekleme ve yönetme işlemlerini yapabilir.

Yeni bir firma admin oluşturduğunda, o kullanıcı sistemde ilgili firmanın paneline erişim sağlar.

2️⃣ Firma Admin (Firma Yetkilisi)

Sadece kendi firmasına ait bilet, sefer ve kullanıcı işlemlerini yönetebilir.

Admin tarafından oluşturulan bilgilerle giriş yapar.

Firma admin panelinden yeni seferler ekleyebilir, bilet durumlarını güncelleyebilir.

3️⃣ User (Yolcu)

Sisteme kayıt olarak giriş yapar.

Mevcut seferleri görüntüleyip bilet satın alabilir.

Kendi bilet geçmişini ve durumunu görebilir.

4️⃣ Ziyaretçi (Giriş Yapmamış Kullanıcı)

Ana sayfada kalkış ve varış noktası seçerek seferleri listeleyebilir.

Sefer detaylarını görüntüleyebilir ancak bilet satın alamaz.

“Bilet Satın Al” butonuna tıkladığında “Lütfen Giriş Yapın” uyarısı alır ve giriş sayfasına yönlendirilir.
