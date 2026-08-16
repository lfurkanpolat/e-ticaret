<?php
// =====================================================
// ÖDEME BAŞARILI & DB KAYIT - public/odeme-basarili.php
// =====================================================

require_once '../includes/config.php';
require_once 'autoload.php';

use Iyzipay\Model\CheckoutForm;
use Iyzipay\Options;
use Iyzipay\Request\RetrieveCheckoutFormRequest;

// Kullanıcı giriş kontrolü
if (!isLoggedIn()) {
    header("Location: " . SITE_URL . "public/giris.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$token = $_POST['token'] ?? '';
$gecici_siparis = $_SESSION['gecici_siparis'] ?? null;

// iyzico token veya session verisi yoksa ana sayfaya yönlendir
if (empty($token) || !$gecici_siparis) {
    header("Location: " . SITE_URL . "public/index.php");
    exit();
}

// Site ayarlarından iyzico bilgilerini çek
try {
    $stmt = $db->query("SELECT * FROM site_ayarlari WHERE id = 1");
    $ayar = $stmt->fetch();
} catch (PDOException $e) {
    $ayar = [];
}

$iyzico_api_key = $ayar['iyzico_api_key'] ?? '';
$iyzico_secret_key = $ayar['iyzico_secret_key'] ?? '';
$iyzico_mode = $ayar['iyzico_mode'] ?? 'test';

// =====================================================
// İYZİCO ÖDEME DOĞRULAMA
// =====================================================
$options = new Options();
$options->setApiKey($iyzico_api_key);
$options->setSecretKey($iyzico_secret_key);
$options->setBaseUrl($iyzico_mode == 'live' 
    ? 'https://api.iyzipay.com' 
    : 'https://sandbox-api.iyzipay.com'
);

$request = new RetrieveCheckoutFormRequest();
$request->setLocale(\Iyzipay\Model\Locale::TR);
$request->setToken($token);

$checkoutForm = CheckoutForm::retrieve($request, $options);

// Ödeme iyzico tarafında BAŞARISIZ ise sepet sayfasına hata ile dön
if ($checkoutForm->getStatus() != "success" || $checkoutForm->getPaymentStatus() != "SUCCESS") {
    $_SESSION['error'] = 'Ödeme işlemi başarısız: ' . $checkoutForm->getErrorMessage();
    header("Location: " . SITE_URL . "public/sepet.php");
    exit();
}

$siparis = null;

// =====================================================
// VERİTABANI İŞLEMLERİ (TRANSACTION)
// =====================================================
try {
    $db->beginTransaction();

    // 1. Benzersiz Sipariş Numarası Üret
    $siparis_no = 'SP-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));

    // 2. siparisler Tablosuna Insert (ENUM değerleri tablonuza tam uyumlu hale getirildi)
    $stmt = $db->prepare("
        INSERT INTO siparisler (
            siparis_no, kullanici_id, fatura_adres_id, teslimat_adres_id,
            toplam_tutar, odeme_yontemi, odeme_durumu, siparis_durumu, olusturma_tarihi
        ) VALUES (?, ?, ?, ?, ?, 'kredi_karti', 'onaylandi', 'hazirlaniyor', NOW())
    ");
    $stmt->execute([
        $siparis_no,
        $user_id,
        $gecici_siparis['fatura_adres_id'],
        $gecici_siparis['teslimat_adres_id'],
        $gecici_siparis['genel_toplam']
    ]);

    $siparis_id = $db->lastInsertId();

    // 3. Sepet Ürünlerini Çek ve siparis_detay Tablosuna Ekle
    $stmt = $db->prepare("
        SELECT s.*, u.ad as urun_adi, u.urun_kodu,
               u.fiyat as urun_fiyat, u.indirimli_fiyat as urun_indirimli_fiyat,
               v.fiyat as varyant_fiyat, v.indirimli_fiyat as varyant_indirimli_fiyat
        FROM sepet s
        LEFT JOIN urunler u ON s.urun_id = u.id
        LEFT JOIN urun_varyantlari v ON s.varyant_id = v.id
        WHERE s.kullanici_id = ?
    ");
    $stmt->execute([$user_id]);
    $sepet_urunler = $stmt->fetchAll();

    foreach ($sepet_urunler as $item) {
        if ($item['varyant_id']) {
            $birim_fiyat = (float)($item['varyant_indirimli_fiyat'] ?? $item['varyant_fiyat'] ?? $item['urun_fiyat']);
        } else {
            $birim_fiyat = (float)($item['urun_indirimli_fiyat'] ?? $item['urun_fiyat']);
        }

        $toplam_fiyat = $birim_fiyat * $item['adet'];

        // Sipariş Detay Insert
        $stmtDetay = $db->prepare("
            INSERT INTO siparis_detay (
                siparis_id, urun_id, varyant_id, urun_adi, urun_kodu, adet, birim_fiyat, toplam_fiyat
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmtDetay->execute([
            $siparis_id,
            $item['urun_id'],
            $item['varyant_id'],
            $item['urun_adi'],
            $item['urun_kodu'] ?? '',
            $item['adet'],
            $birim_fiyat,
            $toplam_fiyat
        ]);

        // Stok Düşürme İşlemi
        if ($item['varyant_id']) {
            $stokStmt = $db->prepare("UPDATE urun_varyantlari SET stok = GREATEST(0, stok - ?) WHERE id = ?");
            $stokStmt->execute([$item['adet'], $item['varyant_id']]);
        } else {
            $stokStmt = $db->prepare("UPDATE urunler SET stok = GREATEST(0, stok - ?) WHERE id = ?");
            $stokStmt->execute([$item['adet'], $item['urun_id']]);
        }
    }

    // 4. Kullanıcının Sepetini Temizle
    $stmt = $db->prepare("DELETE FROM sepet WHERE kullanici_id = ?");
    $stmt->execute([$user_id]);

    // 5. İşlemi Onayla ve Geçici Session'ı Sil
    $db->commit();
    unset($_SESSION['gecici_siparis']);

    // Ekranda Göstermek İçin Sipariş Bilgisi
    $siparis = [
        'siparis_no' => $siparis_no,
        'toplam_tutar' => $gecici_siparis['genel_toplam']
    ];

} catch (Exception $e) {
    $db->rollBack();
    header("Location: " . SITE_URL . "public/index.php");
    exit();
}

// =====================================================
// HEADER'I DAHİL ET
// =====================================================
include '../includes/header.php';
?>

<section class="success-page">
    <div class="container">
        <div class="success-card">
            <div class="success-icon">
                <i class="fa-regular fa-circle-check"></i>
            </div>
            <h1>Ödeme Başarılı!</h1>
            <p>Siparişiniz başarıyla oluşturuldu.</p>
            
            <div class="order-info">
                <div class="order-info-row">
                    <span>Sipariş No</span>
                    <strong>#<?php echo htmlspecialchars($siparis['siparis_no']); ?></strong>
                </div>
                <div class="order-info-row">
                    <span>Toplam Tutar</span>
                    <strong><?php echo number_format($siparis['toplam_tutar'], 2, ',', '.'); ?> TL</strong>
                </div>
                <div class="order-info-row">
                    <span>Durum</span>
                    <span class="status-badge">Hazırlanıyor</span>
                </div>
            </div>
            
            <div class="success-actions">
                <a href="<?php echo SITE_URL; ?>public/siparislerim.php" class="btn btn-primary">
                    <i class="fa-solid fa-box"></i> Siparişlerim
                </a>
                <a href="<?php echo SITE_URL; ?>public/index.php" class="btn btn-outline">
                    <i class="fa-solid fa-arrow-left"></i> Ana Sayfa
                </a>
            </div>
            
            <div class="success-info">
                <p>
                    <i class="fa-solid fa-envelope"></i>
                    Sipariş onayı e-posta adresinize gönderildi.
                </p>
                <p>
                    <i class="fa-solid fa-truck"></i>
                    Siparişiniz hazırlandığında kargo takip numaranız tarafınıza iletilecektir.
                </p>
            </div>
        </div>
    </div>
</section>

<style>
.success-page { display: flex; align-items: center; justify-content: center; min-height: calc(100vh - 300px); padding: 40px 0; }
.success-card { background: #fff; border-radius: 24px; padding: 48px 40px; text-align: center; max-width: 500px; width: 100%; box-shadow: 0 20px 60px rgba(0,0,0,0.06); }
.success-icon { font-size: 80px; color: #2ecc71; margin-bottom: 16px; }
.success-card h1 { font-size: 28px; font-weight: 800; margin-bottom: 8px; }
.success-card p { color: #666; font-size: 16px; margin-bottom: 24px; }
.order-info { background: #f8f9fb; border-radius: 12px; padding: 16px; margin-bottom: 24px; text-align: left; }
.order-info-row { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #eee; font-size: 14px; }
.order-info-row:last-child { border-bottom: none; }
.order-info-row strong { color: #222; }
.status-badge { background: #ffd400; color: #111; padding: 2px 12px; border-radius: 50px; font-size: 12px; font-weight: 600; }
.success-actions { display: flex; gap: 12px; justify-content: center; margin-bottom: 24px; }
.success-info { text-align: left; font-size: 14px; color: #666; }
.success-info p { margin-bottom: 8px; display: flex; align-items: center; gap: 10px; }
.success-info p i { color: #ffd400; width: 20px; }
@media (max-width: 480px) { .success-card { padding: 32px 20px; } .success-actions { flex-direction: column; } }
</style>

<?php include '../includes/footer.php'; ?>