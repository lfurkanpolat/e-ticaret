<?php
// =====================================================
// SEPETE EKLE - AJAX - public/sepet-ekle.php
// =====================================================

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../includes/config.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'cart_count' => 0];

// =====================================================
// KULLANICI KONTROLÜ
// =====================================================
$user_id = isLoggedIn() ? $_SESSION['user_id'] : null;
$session_id = session_id();

// =====================================================
// POST VERİLERİNİ AL
// =====================================================
$urun_id = isset($_POST['urun_id']) ? (int)$_POST['urun_id'] : 0;
$adet = isset($_POST['adet']) ? (int)$_POST['adet'] : 1;

// Debug
error_log("=== SEPETE EKLE (DEBUG) ===");
error_log("POST: " . print_r($_POST, true));
error_log("Ürün ID (int): " . $urun_id);

if ($urun_id <= 0) {
    $response['message'] = 'Geçersiz ürün ID! (Gelen: ' . $urun_id . ')';
    error_log("❌ Geçersiz ürün ID");
    echo json_encode($response);
    exit();
}

// =====================================================
// ÜRÜN KONTROLÜ (Detaylı)
// =====================================================
try {
    // Önce ürünü kontrol et
    $stmt = $db->prepare("SELECT * FROM urunler WHERE id = ?");
    $stmt->execute([$urun_id]);
    $urun = $stmt->fetch();
    
    if (!$urun) {
        // Ürün yoksa favorilerden de kontrol et
        $stmt2 = $db->prepare("SELECT * FROM favoriler WHERE urun_id = ?");
        $stmt2->execute([$urun_id]);
        $favori = $stmt2->fetch();
        
        if ($favori) {
            $response['message'] = 'Bu ürün favorilerde var ama ürün tablosunda bulunamadı! (Ürün silinmiş olabilir)';
            error_log("❌ Ürün tablosunda yok ama favorilerde var. ID: " . $urun_id);
        } else {
            $response['message'] = 'Ürün bulunamadı! (ID: ' . $urun_id . ')';
            error_log("❌ Ürün bulunamadı. ID: " . $urun_id);
        }
        echo json_encode($response);
        exit();
    }
    
    // Ürün aktif mi?
    if ($urun['durum'] != 'aktif') {
        $response['message'] = 'Bu ürün aktif değil! Durum: ' . $urun['durum'];
        error_log("❌ Ürün aktif değil. ID: " . $urun_id . ", Durum: " . $urun['durum']);
        echo json_encode($response);
        exit();
    }
    
    error_log("✅ Ürün bulundu: " . $urun['ad'] . " (ID: " . $urun['id'] . ", Durum: " . $urun['durum'] . ")");
    
    // Varyant kontrolü (opsiyonel)
    $varyant_id = null;
    if (isset($_POST['varyant_id']) && $_POST['varyant_id'] !== '' && $_POST['varyant_id'] !== 'null') {
        $varyant_id = (int)$_POST['varyant_id'];
    }
    
    $birim_fiyat = $urun['indirimli_fiyat'] ?? $urun['fiyat'];
    
    // Stok kontrolü
    if ($urun['stok'] < $adet) {
        $response['message'] = 'Stokta yeterli miktarda ürün yok! (Mevcut: ' . $urun['stok'] . ', İstenen: ' . $adet . ')';
        error_log("❌ Stok yetersiz. Mevcut: " . $urun['stok'] . ", İstenen: " . $adet);
        echo json_encode($response);
        exit();
    }
    
} catch (PDOException $e) {
    $response['message'] = 'Veritabanı hatası: ' . $e->getMessage();
    error_log("❌ Veritabanı hatası: " . $e->getMessage());
    echo json_encode($response);
    exit();
}

// =====================================================
// SEPETE EKLE
// =====================================================
try {
    // Sepet kontrolü
    if ($user_id) {
        $stmt = $db->prepare("
            SELECT id, adet FROM sepet 
            WHERE kullanici_id = ? AND urun_id = ? AND (varyant_id = ? OR (varyant_id IS NULL AND ? IS NULL))
        ");
        $stmt->execute([$user_id, $urun_id, $varyant_id, $varyant_id]);
    } else {
        $stmt = $db->prepare("
            SELECT id, adet FROM sepet 
            WHERE session_id = ? AND urun_id = ? AND (varyant_id = ? OR (varyant_id IS NULL AND ? IS NULL))
        ");
        $stmt->execute([$session_id, $urun_id, $varyant_id, $varyant_id]);
    }
    $mevcut = $stmt->fetch();
    
    if ($mevcut) {
        $yeni_adet = $mevcut['adet'] + $adet;
        $stmt = $db->prepare("UPDATE sepet SET adet = ? WHERE id = ?");
        $stmt->execute([$yeni_adet, $mevcut['id']]);
        error_log("🔄 Sepet güncellendi. Yeni adet: " . $yeni_adet);
    } else {
        if ($user_id) {
            $stmt = $db->prepare("
                INSERT INTO sepet (kullanici_id, urun_id, varyant_id, adet, birim_fiyat) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$user_id, $urun_id, $varyant_id, $adet, $birim_fiyat]);
        } else {
            $stmt = $db->prepare("
                INSERT INTO sepet (session_id, urun_id, varyant_id, adet, birim_fiyat) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$session_id, $urun_id, $varyant_id, $adet, $birim_fiyat]);
        }
        error_log("➕ Sepete yeni ürün eklendi");
    }
    
    // Sepet sayısını getir
    if ($user_id) {
        $stmt = $db->prepare("SELECT SUM(adet) as toplam FROM sepet WHERE kullanici_id = ?");
        $stmt->execute([$user_id]);
    } else {
        $stmt = $db->prepare("SELECT SUM(adet) as toplam FROM sepet WHERE session_id = ?");
        $stmt->execute([$session_id]);
    }
    $cart_count = $stmt->fetch()['toplam'] ?? 0;
    
    $response['success'] = true;
    $response['message'] = 'Ürün sepete eklendi!';
    $response['cart_count'] = (int)$cart_count;
    
    error_log("✅ Başarılı! Sepet toplamı: " . $cart_count);
    
} catch (PDOException $e) {
    $response['message'] = 'Sepet hatası: ' . $e->getMessage();
    error_log("❌ Sepet hatası: " . $e->getMessage());
}

echo json_encode($response);
exit();
?>