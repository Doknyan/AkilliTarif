<?php
// Veritabanı bağlantı bilgileri
// $host     = 'sql113.infinityfree.com';
// $port     = '3306'; // Senin belirttiğin port
// $db_name  = 'if0_41936898_yemek'; // Oluşturduğun veritabanı adı
// $username = 'if0_41936898';
// $password = 'CiA05A0Vj91O9'; // WAMP için varsayılan genellikle 'root'tur, XAMPP ise boştur.
// $charset  = 'utf8mb4';

$host     = 'localhost';
$port     = '3307'; // Senin belirttiğin port
$db_name  = 'yemek_projesi_db'; // Oluşturduğun veritabanı adı
$username = 'root';
$password = ''; // WAMP için varsayılan genellikle 'root'tur, XAMPP ise boştur.
$charset  = 'utf8mb4';

// DSN (Data Source Name) yapısı
$dsn = "mysql:host=$host;port=$port;dbname=$db_name;charset=$charset";

// PDO Seçenekleri
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Hataları yakala
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Verileri dizi (assoc) olarak getir
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Gerçek hazırlıklı ifadeleri kullan (Güvenlik için)
];

try {
    // Bağlantıyı başlat
    $db = new PDO($dsn, $username, $password, $options);
    $db->exec("SET NAMES utf8mb4");
    
    // Test amaçlı (Bağlantı başarılıysa bir şey döndürmez, hata varsa catch bloğuna düşer)
} catch (\PDOException $e) {
    // Bağlantı hatası durumunda mesajı bas ve işlemi durdur
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}
?>