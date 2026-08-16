<?php
// =====================================================
// VERİTABANI YAPILANDIRMA DOSYASI
// =====================================================
// Dizin: includes/config.php

// Hata raporlamayı aç (geliştirme aşamasında)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Zaman dilimi ayarı
date_default_timezone_set('Europe/Istanbul');

// Veritabanı bilgileri
define('DB_HOST', 'localhost');
define('DB_NAME', 'e_ticaret');
define('DB_USER', 'root');
define('DB_PASS', '');

// Oturum başlat
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// =====================================================
// PDO VERİTABANI BAĞLANTISI
// =====================================================
try {
    $db = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}

// =====================================================
// SİTE AYARLARINI TEK SATIR OLARAK YÜKLE
// =====================================================
$site_ayar = null;

function siteAyar() {
    global $db, $site_ayar;
    
    // Ayarlar daha önce yüklenmediyse yükle
    if ($site_ayar === null) {
        try {
            $stmt = $db->query("SELECT * FROM site_ayarlari LIMIT 1");
            $site_ayar = $stmt->fetch();
            
            // Eğer kayıt yoksa boş array oluştur
            if (!$site_ayar) {
                $site_ayar = [];
            }
        } catch (PDOException $e) {
            $site_ayar = [];
        }
    }
    
    return $site_ayar;
}

// Site ayarlarını yükle
$site = siteAyar();

// =====================================================
// SİTE SABİTLERİ (Ayarlardan dinamik oluştur)
// =====================================================

define('SITE_URL', 'http://localhost/e-ticaret/');

// Site bilgileri
define('SITE_NAME', $site['site_title'] ?? 'E-Ticaret Sitesi');  // ← EKLENDİ
define('SITE_TITLE', $site['site_title'] ?? 'E-Ticaret Sitesi');
define('SITE_DESC', $site['site_desc'] ?? '');
define('SITE_KEYWORDS', $site['site_keywords'] ?? '');
define('SITE_LOGO', $site['site_logo'] ?? '');
define('SITE_FAVICON', $site['site_favicon'] ?? '');

// İletişim bilgileri
define('ISLETME_ADI', $site['isletme_adi'] ?? '');
define('ISLETME_ADRES', $site['isletme_adres'] ?? '');
define('ISLETME_TELEFON', $site['isletme_telefon'] ?? '');
define('ISLETME_EMAIL', $site['isletme_email'] ?? '');
define('ISLETME_HARITA', $site['isletme_harita'] ?? '');

// Sosyal medya
define('FACEBOOK_URL', $site['facebook_url'] ?? '#');
define('TWITTER_URL', $site['twitter_url'] ?? '#');
define('INSTAGRAM_URL', $site['instagram_url'] ?? '#');
define('YOUTUBE_URL', $site['youtube_url'] ?? '#');
define('WHATSAPP_URL', $site['whatsapp_url'] ?? '#');

// Footer
define('FOOTER_COPYRIGHT', $site['footer_copyright'] ?? 'Tüm hakları saklıdır.');
define('FOOTER_ABOUT', $site['footer_about'] ?? '');

// Mail
define('SMTP_HOST', $site['smtp_host'] ?? '');
define('SMTP_PORT', $site['smtp_port'] ?? '');
define('SMTP_USERNAME', $site['smtp_username'] ?? '');
define('SMTP_PASSWORD', $site['smtp_password'] ?? '');
define('SMTP_ENCRYPTION', $site['smtp_encryption'] ?? '');

// Site durumu
define('SITE_DURUM', $site['site_durum'] ?? 'aktif');
define('BAKIM_MESAJI', $site['bakim_mesaji'] ?? 'Sitemiz bakımda...');

// =====================================================
// YARDIMCI FONKSİYONLAR
// =====================================================

// Güvenli çıktı alma
function clean($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Redirect fonksiyonu - DÜZELTİLDİ
function redirect($url) {
    // Eğer URL zaten http:// ile başlıyorsa direkt yönlendir
    if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
        header("Location: " . $url);
    } else {
        header("Location: " . SITE_URL . $url);
    }
    exit();
}

// Mesaj göster
function showMessage($type, $message) {
    $classes = [
        'success' => 'alert-success',
        'error' => 'alert-danger',
        'warning' => 'alert-warning',
        'info' => 'alert-info'
    ];
    return '<div class="alert ' . $classes[$type] . '">' . $message . '</div>';
}

// Kullanıcı giriş kontrolü
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Admin kontrolü
function isAdmin() {
    return isset($_SESSION['user_yetki']) && $_SESSION['user_yetki'] == 'admin';
}

// CSRF Token oluştur
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// CSRF Token doğrula
function csrf_verify($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// =====================================================
// SEPET İŞLEMLERİ
// =====================================================

// Sepet sayısını getir
function getCartCount() {
    global $db;
    
    if (isLoggedIn()) {
        $stmt = $db->prepare("SELECT SUM(adet) as toplam FROM sepet WHERE kullanici_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $result = $stmt->fetch();
        return $result['toplam'] ?? 0;
    } else {
        if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
            $count = 0;
            foreach ($_SESSION['cart'] as $item) {
                $count += $item['adet'];
            }
            return $count;
        }
        return 0;
    }
}

// Sepet toplamını getir
function getCartTotal() {
    global $db;
    
    if (isLoggedIn()) {
        $stmt = $db->prepare("SELECT SUM(adet * birim_fiyat) as toplam FROM sepet WHERE kullanici_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $result = $stmt->fetch();
        return $result['toplam'] ?? 0;
    } else {
        if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
            $total = 0;
            foreach ($_SESSION['cart'] as $item) {
                $total += $item['adet'] * $item['fiyat'];
            }
            return $total;
        }
        return 0;
    }
}
?>