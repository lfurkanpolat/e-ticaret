<?php
// =====================================================
// FAVORİ EKLE/ÇIKAR - AJAX - public/favori-ekle.php (DEBUG MODLU)
// =====================================================

// Tüm hataları göster
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../includes/config.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'is_favorite' => false];

// Debug log
error_log("=== FAVORİ İSTEĞİ BAŞLADI ===");
error_log("POST verileri: " . print_r($_POST, true));

// Kullanıcı giriş kontrolü
if (!isLoggedIn()) {
    $response['message'] = 'Lütfen giriş yapın!';
    error_log("❌ Kullanıcı giriş yapmamış");
    echo json_encode($response);
    exit();
}

$user_id = $_SESSION['user_id'];
$urun_id = isset($_POST['urun_id']) ? (int)$_POST['urun_id'] : 0;

error_log("👤 Kullanıcı ID: " . $user_id);
error_log("📦 Ürün ID: " . $urun_id);

if ($urun_id <= 0) {
    $response['message'] = 'Geçersiz ürün ID!';
    error_log("❌ Geçersiz ürün ID");
    echo json_encode($response);
    exit();
}

// =====================================================
// FAVORİ VAR MI KONTROL ET
// =====================================================
try {
    $stmt = $db->prepare("SELECT id FROM favoriler WHERE kullanici_id = ? AND urun_id = ?");
    $stmt->execute([$user_id, $urun_id]);
    $exists = $stmt->fetch();
    
    error_log("🔍 Favori kontrolü: " . ($exists ? "VAR" : "YOK"));
    
    if ($exists) {
        // Varsa sil
        error_log("🗑️ Favori siliniyor...");
        $stmt = $db->prepare("DELETE FROM favoriler WHERE kullanici_id = ? AND urun_id = ?");
        $stmt->execute([$user_id, $urun_id]);
        
        $response['success'] = true;
        $response['message'] = 'Favorilerden kaldırıldı!';
        $response['is_favorite'] = false;
        error_log("✅ Favori silindi");
    } else {
        // Yoksa ekle
        error_log("➕ Favori ekleniyor...");
        $stmt = $db->prepare("INSERT INTO favoriler (kullanici_id, urun_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $urun_id]);
        
        $response['success'] = true;
        $response['message'] = 'Favorilere eklendi!';
        $response['is_favorite'] = true;
        error_log("✅ Favori eklendi");
    }
    
} catch (PDOException $e) {
    $response['message'] = 'Veritabanı hatası: ' . $e->getMessage();
    error_log("❌ Veritabanı hatası: " . $e->getMessage());
    error_log("   SQLSTATE: " . $e->getCode());
}

error_log("📤 Yanıt: " . json_encode($response));
error_log("=== FAVORİ İSTEĞİ BİTTİ ===");

echo json_encode($response);
exit();
?>