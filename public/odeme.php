<?php
// =====================================================
// ÖDEME SAYFASI - public/odeme.php (Sipariş Kayıtsız Yapı)
// =====================================================

require_once '../includes/config.php';

// iyzico kütüphanesini dahil et
require_once 'autoload.php';

use Iyzipay\Model\CheckoutFormInitialize;
use Iyzipay\Model\Locale;
use Iyzipay\Options;
use Iyzipay\Request\CreateCheckoutFormInitializeRequest;

// Kullanıcı giriş kontrolü
if (!isLoggedIn()) {
    header("Location: " . SITE_URL . "public/giris.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$iyzico_html_content = '';

// =====================================================
// SİTE AYARLARINI ÇEK (iyzico API bilgileri)
// =====================================================
try {
    $stmt = $db->query("SELECT * FROM site_ayarlari WHERE id = 1");
    $ayar = $stmt->fetch();
} catch (PDOException $e) {
    $ayar = [];
}

// iyzico ayarları
$iyzico_api_key = $ayar['iyzico_api_key'] ?? '';
$iyzico_secret_key = $ayar['iyzico_secret_key'] ?? '';
$iyzico_mode = $ayar['iyzico_mode'] ?? 'test';

// =====================================================
// SEPET VERİLERİNİ ÇEK
// =====================================================
try {
    $stmt = $db->prepare("
        SELECT s.*, u.ad as urun_adi, u.slug, u.resim_url,
               u.urun_kodu,
               u.fiyat as urun_fiyat, u.indirimli_fiyat as urun_indirimli_fiyat,
               v.renk, v.beden, v.fiyat as varyant_fiyat, v.indirimli_fiyat as varyant_indirimli_fiyat,
               v.resim_url as varyant_resim_url
        FROM sepet s
        LEFT JOIN urunler u ON s.urun_id = u.id
        LEFT JOIN urun_varyantlari v ON s.varyant_id = v.id
        WHERE s.kullanici_id = ?
        ORDER BY s.eklenme_tarihi DESC
    ");
    $stmt->execute([$user_id]);
    $sepet_urunler = $stmt->fetchAll();
} catch (PDOException $e) {
    $sepet_urunler = [];
    $error = 'Sepet bilgileri alınamadı!';
}

// Sepet boşsa yönlendir
if (empty($sepet_urunler)) {
    header("Location: " . SITE_URL . "public/sepet.php");
    exit();
}

// =====================================================
// SEPET ÖZETİ (Ondalıklı Hassas Hesaplama)
// =====================================================
$toplam_urun = 0;
$ara_toplam = 0.00;
$indirim_toplam = 0.00;
$kargo_ucreti = 0.00;
$genel_toplam = 0.00;
$kargo_limit = 750.00;

foreach ($sepet_urunler as $item) {
    if ($item['varyant_id']) {
        $birim_fiyat = (float)($item['varyant_indirimli_fiyat'] ?? $item['varyant_fiyat'] ?? $item['urun_fiyat']);
    } else {
        $birim_fiyat = (float)($item['urun_indirimli_fiyat'] ?? $item['urun_fiyat']);
    }
    
    $toplam_urun += $item['adet'];
    $ara_toplam += $birim_fiyat * $item['adet'];
}

if ($ara_toplam >= $kargo_limit) {
    $kargo_ucreti = 0.00;
} else {
    $kargo_ucreti = 29.90;
}

$genel_toplam = round($ara_toplam - $indirim_toplam + $kargo_ucreti, 2);

// =====================================================
// KULLANICI BİLGİLERİNİ ÇEK
// =====================================================
try {
    $stmt = $db->prepare("SELECT * FROM kullanicilar WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    $user = null;
}

// =====================================================
// KULLANICI ADRESLERİNİ ÇEK
// =====================================================
try {
    $stmt = $db->prepare("
        SELECT * FROM kullanici_adresleri 
        WHERE kullanici_id = ? 
        ORDER BY varsayilan DESC, id DESC
    ");
    $stmt->execute([$user_id]);
    $adresler = $stmt->fetchAll();
} catch (PDOException $e) {
    $adresler = [];
}

// =====================================================
// İYZİCO ÖDEME BAŞLAT (DATABASE INSERT İŞLEMİ YOK!)
// =====================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['iyzico_odeme'])) {
    $fatura_adres_id = isset($_POST['fatura_adres_id']) ? (int)$_POST['fatura_adres_id'] : 0;
    $teslimat_adres_id = isset($_POST['teslimat_adres_id']) ? (int)$_POST['teslimat_adres_id'] : 0;
    $siparis_notu = isset($_POST['siparis_notu']) ? trim($_POST['siparis_notu']) : '';
    
    // Validasyon
    $errors = [];
    if ($fatura_adres_id <= 0) $errors[] = 'Lütfen fatura adresi seçin.';
    if ($teslimat_adres_id <= 0) $errors[] = 'Lütfen teslimat adresi seçin.';
    
    // Fatura adresi bilgilerini al
    try {
        $stmt = $db->prepare("SELECT * FROM kullanici_adresleri WHERE id = ? AND kullanici_id = ?");
        $stmt->execute([$fatura_adres_id, $user_id]);
        $fatura_adres = $stmt->fetch();
    } catch (PDOException $e) {
        $fatura_adres = null;
    }
    
    if (empty($errors) && $fatura_adres) {
        try {
            // Geçici Referans Kod ve Oturum Bilgilerini Saklama
            $gecici_ref_no = 'REF-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
            
            // Adres ve sipariş notu gibi detayları oturumda saklıyoruz. 
            // Ödeme başarılı olunca odeme-basarili.php bunlarla DB kaydı oluşturacak.
            $_SESSION['gecici_siparis'] = [
                'ref_no' => $gecici_ref_no,
                'fatura_adres_id' => $fatura_adres_id,
                'teslimat_adres_id' => $teslimat_adres_id,
                'siparis_notu' => $siparis_notu,
                'genel_toplam' => $genel_toplam
            ];
            
            // iyzico ayarları
            $options = new Options();
            $options->setApiKey($iyzico_api_key);
            $options->setSecretKey($iyzico_secret_key);
            $options->setBaseUrl($iyzico_mode == 'live' 
                ? 'https://api.iyzipay.com' 
                : 'https://sandbox-api.iyzipay.com'
            );
            
            // Ödeme isteği oluştur
            $request = new CreateCheckoutFormInitializeRequest();
            $request->setLocale(Locale::TR);
            $request->setConversationId($gecici_ref_no);
            $request->setPrice(number_format($ara_toplam, 2, '.', ''));
            $request->setPaidPrice(number_format($genel_toplam, 2, '.', ''));
            $request->setCurrency("TRY");
            $request->setBasketId($gecici_ref_no);
            $request->setPaymentGroup("PRODUCT");
            $request->setCallbackUrl(SITE_URL . "public/odeme-basarili.php");
            
            // Müşteri bilgileri
            $buyer = new \Iyzipay\Model\Buyer();
            $buyer->setId($user_id);
            $buyer->setName($user['ad']);
            $buyer->setSurname($user['soyad']);
            $buyer->setIdentityNumber("11111111111");
            $buyer->setEmail($user['email']);
            $buyer->setGsmNumber($user['telefon'] ?? "5555555555");
            $buyer->setRegistrationDate(date("Y-m-d H:i:s", strtotime($user['kayit_tarihi'])));
            $buyer->setLastLoginDate(date("Y-m-d H:i:s", strtotime($user['son_giris'] ?? $user['kayit_tarihi'])));
            $buyer->setRegistrationAddress($fatura_adres['acik_adres']);
            $buyer->setCity($fatura_adres['il']);
            $buyer->setCountry("Turkey");
            $buyer->setZipCode("34000");
            $request->setBuyer($buyer);
            
            // Fatura adresi
            $shippingAddress = new \Iyzipay\Model\Address();
            $shippingAddress->setContactName($fatura_adres['ad'] . ' ' . $fatura_adres['soyad']);
            $shippingAddress->setCity($fatura_adres['il']);
            $shippingAddress->setCountry("Turkey");
            $shippingAddress->setAddress($fatura_adres['acik_adres']);
            $shippingAddress->setZipCode("34000");
            $request->setShippingAddress($shippingAddress);
            
            // Teslimat adresi
            $billingAddress = new \Iyzipay\Model\Address();
            $billingAddress->setContactName($fatura_adres['ad'] . ' ' . $fatura_adres['soyad']);
            $billingAddress->setCity($fatura_adres['il']);
            $billingAddress->setCountry("Turkey");
            $billingAddress->setAddress($fatura_adres['acik_adres']);
            $billingAddress->setZipCode("34000");
            $request->setBillingAddress($billingAddress);
            
            // Sepet ürünleri
            $basketItems = [];
            foreach ($sepet_urunler as $item) {
                if ($item['varyant_id']) {
                    $item_fiyat = (float)($item['varyant_indirimli_fiyat'] ?? $item['varyant_fiyat'] ?? $item['urun_fiyat']);
                } else {
                    $item_fiyat = (float)($item['urun_indirimli_fiyat'] ?? $item['urun_fiyat']);
                }
                
                $basketItem = new \Iyzipay\Model\BasketItem();
                $basketItem->setId($item['urun_id']);
                $basketItem->setName($item['urun_adi']);
                $basketItem->setCategory1("Mobil Aksesuar");
                $basketItem->setItemType(\Iyzipay\Model\BasketItemType::PHYSICAL);
                $basketItem->setPrice(number_format($item_fiyat * $item['adet'], 2, '.', ''));
                $basketItems[] = $basketItem;
            }
            $request->setBasketItems($basketItems);
            
            // iyzico formunu başlat
            $checkoutFormInitialize = CheckoutFormInitialize::create($request, $options);
            
            if ($checkoutFormInitialize->getStatus() == "success") {
                $iyzico_html_content = $checkoutFormInitialize->getCheckoutFormContent();
            } else {
                $error = 'Ödeme başlatılamadı: ' . $checkoutFormInitialize->getErrorMessage();
            }
            
        } catch (Exception $e) {
            $error = 'Ödeme başlatılamadı: ' . $e->getMessage();
        }
    } else {
        $error = implode('<br>', $errors);
    }
}

// =====================================================
// HEADER'I DAHİL ET
// =====================================================
include '../includes/header.php';
?>

<!-- ÖDEME SAYFASI HTML -->
<section class="checkout-page">
    <div class="container">
        
        <div class="page-header">
            <h1><i class="fa-solid fa-credit-card"></i> Ödeme</h1>
            <span class="step">Adım 3/3</span>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="payment-info-box">
            <i class="fa-solid fa-shield-halved"></i>
            <div>
                <strong>Güvenli Ödeme</strong>
                <p>Ödemeleriniz 256-bit SSL sertifikası ile korunmaktadır. Kredi kartı bilgileriniz asla sistemimizde saklanmaz.</p>
            </div>
        </div>
        
        <div class="checkout-wrapper">
            
            <!-- SOL ALAN: ADRES SEÇİMİ VEYA İYZİCO KART EKRANI -->
            <div class="checkout-form">
                
                <?php if (!empty($iyzico_html_content)): ?>
                    <div class="form-section iyzico-embedded-container">
                        <h2><i class="fa-solid fa-credit-card"></i> Kart Bilgilerinizi Girin</h2>
                        <div id="iyzipay-checkout-form" class="responsive"></div>
                        <?php echo $iyzico_html_content; ?>
                    </div>
                <?php else: ?>
                    <form method="POST" action="" id="checkoutForm">
                        
                        <!-- Adres Bilgileri -->
                        <div class="form-section">
                            <h2><i class="fa-regular fa-address-book"></i> Adres Bilgileri</h2>
                            
                            <div class="form-group">
                                <label>Fatura Adresi</label>
                                <select name="fatura_adres_id" class="form-control" required>
                                    <option value="">Fatura adresi seçin</option>
                                    <?php foreach ($adresler as $adres): ?>
                                        <option value="<?php echo $adres['id']; ?>" <?php echo $adres['varsayilan'] ? 'selected' : ''; ?>>
                                            <?php echo $adres['adres_basligi']; ?> - <?php echo $adres['il']; ?>, <?php echo $adres['ilce']; ?>
                                            <?php echo $adres['varsayilan'] ? '(Varsayılan)' : ''; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="form-hint">
                                    <a href="<?php echo SITE_URL; ?>public/profil.php#adresler" target="_blank">
                                        <i class="fa-solid fa-plus"></i> Yeni adres ekle
                                    </a>
                                </small>
                            </div>
                            
                            <div class="form-group">
                                <label>Teslimat Adresi</label>
                                <select name="teslimat_adres_id" class="form-control" required>
                                    <option value="">Teslimat adresi seçin</option>
                                    <?php foreach ($adresler as $adres): ?>
                                        <option value="<?php echo $adres['id']; ?>" <?php echo $adres['varsayilan'] ? 'selected' : ''; ?>>
                                            <?php echo $adres['adres_basligi']; ?> - <?php echo $adres['il']; ?>, <?php echo $adres['ilce']; ?>
                                            <?php echo $adres['varsayilan'] ? '(Varsayılan)' : ''; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div style="margin-top:8px;">
                                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:14px;color:#666;">
                                        <input type="checkbox" id="sameAddress" checked onchange="toggleSameAddress()">
                                        Fatura adresi ile aynı
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Ödeme Yöntemi -->
                        <div class="form-section">
                            <h2><i class="fa-solid fa-wallet"></i> Ödeme Yöntemi</h2>
                            <div class="payment-methods">
                                <div class="payment-method active">
                                    <div class="payment-method-content">
                                        <i class="fa-solid fa-credit-card"></i>
                                        <span>Kredi Kartı / Banka Kartı</span>
                                        <div class="payment-icons">
                                            <i class="fab fa-cc-visa"></i>
                                            <i class="fab fa-cc-mastercard"></i>
                                            <i class="fab fa-cc-amex"></i>
                                            <i class="fab fa-cc-troy"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Sipariş Notu -->
                        <div class="form-section">
                            <h2><i class="fa-regular fa-note-sticky"></i> Sipariş Notu</h2>
                            <div class="form-group">
                                <textarea name="siparis_notu" class="form-control" rows="3" placeholder="Siparişinizle ilgili notlar (isteğe bağlı)"></textarea>
                            </div>
                        </div>
                        
                        <!-- Sözleşme Onayı -->
                        <div class="form-section">
                            <div class="terms">
                                <label style="display:flex;align-items:flex-start;gap:10px;cursor:pointer;">
                                    <input type="checkbox" name="sozlesme" required style="margin-top:3px;width:18px;height:18px;accent-color:#ffd400;">
                                    <span style="font-size:14px;color:#666;">
                                        <a href="<?php echo SITE_URL; ?>public/kullanim-sozlesmesi.php" target="_blank" style="color:#ffd400;font-weight:600;">Kullanıcı Sözleşmesi</a>
                                        ve 
                                        <a href="<?php echo SITE_URL; ?>public/gizlilik-politikasi.php" target="_blank" style="color:#ffd400;font-weight:600;">Gizlilik Politikası</a>
                                        'nı okudum, kabul ediyorum.
                                    </span>
                                </label>
                            </div>
                        </div>
                        
                    </form>
                <?php endif; ?>
            </div>
            
            <!-- SAĞ ALAN: SİPARİŞ ÖZETİ -->
            <div class="checkout-summary">
                <div class="summary-card">
                    <h3>Sipariş Özeti</h3>
                    
                    <div class="summary-products">
                        <?php foreach ($sepet_urunler as $item): 
                            if ($item['varyant_id']) {
                                $birim_fiyat = (float)($item['varyant_indirimli_fiyat'] ?? $item['varyant_fiyat'] ?? $item['urun_fiyat']);
                            } else {
                                $birim_fiyat = (float)($item['urun_indirimli_fiyat'] ?? $item['urun_fiyat']);
                            }
                            
                            if ($item['varyant_resim_url']) {
                                $resim = SITE_URL . 'uploads/' . $item['varyant_resim_url'];
                            } elseif ($item['resim_url']) {
                                $resim = SITE_URL . 'uploads/' . $item['resim_url'];
                            } else {
                                $resim = 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=100';
                            }
                        ?>
                            <div class="summary-product">
                                <img src="<?php echo $resim; ?>" alt="<?php echo $item['urun_adi']; ?>">
                                <div class="summary-product-info">
                                    <span class="summary-product-name"><?php echo $item['urun_adi']; ?></span>
                                    <span class="summary-product-meta"><?php echo $item['adet']; ?> adet × <?php echo number_format($birim_fiyat, 2, ',', '.'); ?> TL</span>
                                </div>
                                <span class="summary-product-total"><?php echo number_format($birim_fiyat * $item['adet'], 2, ',', '.'); ?> TL</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="summary-divider"></div>
                    
                    <div class="summary-row">
                        <span>Ara Toplam</span>
                        <span><?php echo number_format($ara_toplam, 2, ',', '.'); ?> TL</span>
                    </div>
                    
                    <div class="summary-row">
                        <span>Kargo</span>
                        <span>
                            <?php if ($kargo_ucreti == 0): ?>
                                <span style="color:#2ecc71;">Ücretsiz</span>
                            <?php else: ?>
                                <?php echo number_format($kargo_ucreti, 2, ',', '.'); ?> TL
                            <?php endif; ?>
                        </span>
                    </div>
                    
                    <div class="summary-divider"></div>
                    
                    <div class="summary-total">
                        <span>Toplam</span>
                        <span class="total-amount"><?php echo number_format($genel_toplam, 2, ',', '.'); ?> TL</span>
                    </div>
                    
                    <?php if (empty($iyzico_html_content)): ?>
                        <button type="submit" form="checkoutForm" name="iyzico_odeme" class="btn btn-primary btn-block btn-lg" onclick="return validateForm()">
                            <i class="fa-solid fa-lock"></i> Güvenli Ödemeye Geç
                        </button>
                    <?php else: ?>
                        <a href="<?php echo SITE_URL; ?>public/odeme.php" class="btn btn-block btn-lg" style="background:#eee;color:#333;text-decoration:none;text-align:center;">
                            <i class="fa-solid fa-rotate-left"></i> Bilgileri Değiştir
                        </a>
                    <?php endif; ?>
                    
                    <div class="payment-security">
                        <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSkdqrBPGqG6w_d3uuJ02f7O-vpyrNVCEKqPgkY8Ij96SAO53VFduCzXWxr&s=10" alt="iyzico" style="height:24px;">
                        <span>Güvenli Ödeme</span>
                    </div>
                    
                    <div class="payment-security">
                        <i class="fa-solid fa-shield-halved"></i>
                        <span>256-Bit SSL</span>
                        <i class="fa-solid fa-credit-card"></i>
                        <span>Kart Bilgileriniz Saklanmaz</span>
                    </div>
                    
                    <a href="<?php echo SITE_URL; ?>public/sepet.php" class="back-to-cart">
                        <i class="fa-solid fa-arrow-left"></i> Sepete Dön
                    </a>
                </div>
            </div>
            
        </div>
        
    </div>
</section>

<script>
    // Sayfa tamamen yüklendiğinde çalıştır
window.addEventListener('load', function() {
    // Butonu seç
    const button = document.querySelector('#iyzi-proceed-to-co');
    
    // Buton varsa tıkla
    if (button) {
        button.click();
    }
});
function toggleSameAddress() {
    const sameAddress = document.getElementById('sameAddress');
    const faturaSelect = document.querySelector('select[name="fatura_adres_id"]');
    const teslimatSelect = document.querySelector('select[name="teslimat_adres_id"]');
    
    if (sameAddress && faturaSelect && teslimatSelect) {
        if (sameAddress.checked) {
            teslimatSelect.value = faturaSelect.value;
            teslimatSelect.disabled = true;
            teslimatSelect.style.opacity = '0.6';
        } else {
            teslimatSelect.disabled = false;
            teslimatSelect.style.opacity = '1';
        }
    }
}

const faturaElem = document.querySelector('select[name="fatura_adres_id"]');
if (faturaElem) {
    faturaElem.addEventListener('change', function() {
        const sameAddress = document.getElementById('sameAddress');
        if (sameAddress && sameAddress.checked) {
            const teslimatSelect = document.querySelector('select[name="teslimat_adres_id"]');
            if (teslimatSelect) teslimatSelect.value = this.value;
        }
    });
}

function validateForm() {
    const faturaAdres = document.querySelector('select[name="fatura_adres_id"]');
    const teslimatAdres = document.querySelector('select[name="teslimat_adres_id"]');
    
    if (faturaAdres && !faturaAdres.value) {
        alert('Lütfen fatura adresi seçin!');
        faturaAdres.focus();
        return false;
    }
    
    if (teslimatAdres && !teslimatAdres.value && !teslimatAdres.disabled) {
        alert('Lütfen teslimat adresi seçin!');
        teslimatAdres.focus();
        return false;
    }
    
    const sozlesme = document.querySelector('input[name="sozlesme"]');
    if (sozlesme && !sozlesme.checked) {
        alert('Lütfen kullanıcı sözleşmesini kabul edin!');
        return false;
    }
    
    return true;
}

document.addEventListener('DOMContentLoaded', function() {
    const sameAddress = document.getElementById('sameAddress');
    if (sameAddress && sameAddress.checked) {
        const teslimatSelect = document.querySelector('select[name="teslimat_adres_id"]');
        if (teslimatSelect) {
            teslimatSelect.disabled = true;
            teslimatSelect.style.opacity = '0.6';
        }
    }
});
</script>

<style>
    /* Direkt class ile */
.css-c7vmkg-CampaignWrapper {
    display: none !important;
}

/* veya */
.e18r3hf0 {
    display: none !important;
}

/* ya da tüm kampanya wrapper'larını gizle */
[class*="CampaignWrapper"] {
    display: none !important;
}
.checkout-page {
    padding: 30px 0 60px;
    background: #f8f9fb;
    min-height: calc(100vh - 200px);
}
/* Tüm divider'ları gizle */
[class*="Divider"] {
    display: none !important;
}

/* veya spesifik class */
.e92n6r811 {
    display: none !important;
}
/* Ana wrapper'ı gizle */
.css-186f829-LeadPermissionCheckboxWrapper {
    display: none !important;
}

/* veya */
.eqrl9we4 {
    display: none !important;
}
/* ID ile gizle */
#iyzi-back-to-pwi {
    display: none !important;
}

/* veya class ile */
.css-1ei2lfs-SwitchSubscriptionButtonContainer {
    display: none !important;
}

/* ya da */
.exgn1sg1 {
    display: none !important;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.page-header h1 {
    font-size: 28px;
    font-weight: 800;
}

.page-header .step {
    color: #999;
    font-size: 14px;
}

.payment-info-box {
    background: #d1fae5;
    border-radius: 12px;
    padding: 16px 20px;
    display: flex;
    align-items: flex-start;
    gap: 14px;
    margin-bottom: 24px;
    border: 1px solid #a7f3d0;
}

.payment-info-box i {
    font-size: 24px;
    color: #065f46;
    margin-top: 2px;
}

.payment-info-box strong {
    color: #065f46;
    display: block;
}

.payment-info-box p {
    color: #065f46;
    font-size: 14px;
    margin: 0;
}

.checkout-wrapper {
    display: grid;
    grid-template-columns: 1fr 380px;
    gap: 30px;
}

.checkout-form {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.form-section {
    background: #fff;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 5px 25px rgba(0,0,0,0.05);
}

.iyzico-embedded-container {
    min-height: 450px;
}

.form-section h2 {
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.form-section h2 i {
    color: #ffd400;
}

.form-group {
    margin-bottom: 16px;
}

.form-group label {
    display: block;
    font-weight: 600;
    font-size: 14px;
    margin-bottom: 6px;
    color: #222;
}

.form-control {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #eee;
    border-radius: 10px;
    font-size: 14px;
    font-family: 'Inter', sans-serif;
    transition: all 0.3s;
    background: #fff;
    color: #222;
}

.form-control:focus {
    border-color: #ffd400;
    outline: none;
    box-shadow: 0 0 0 4px rgba(255, 212, 0, 0.1);
}

.form-hint {
    display: block;
    font-size: 12px;
    color: #999;
    margin-top: 4px;
}

.form-hint a {
    color: #ffd400;
    font-weight: 600;
    text-decoration: none;
}

.payment-methods {
    margin-bottom: 16px;
}

.payment-method {
    border: 2px solid #ffd400;
    border-radius: 12px;
    background: #fef9e7;
}

.payment-method-content {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 18px;
}

.payment-method-content i {
    font-size: 20px;
    color: #666;
}

.payment-method-content span {
    font-weight: 600;
    font-size: 14px;
}

.payment-method-content .payment-icons {
    margin-left: auto;
    font-size: 20px;
    color: #999;
}

.payment-method-content .payment-icons i {
    margin-left: 4px;
}

.payment-info {
    background: #f8f9fb;
    border-radius: 12px;
    padding: 16px;
}

.payment-info-row {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 4px 0;
    font-size: 14px;
    color: #666;
}

.payment-info-row i {
    color: #ffd400;
    font-size: 16px;
}

.payment-info-row strong {
    color: #222;
}

.terms {
    padding: 4px 0;
}

.checkout-summary {
    position: sticky;
    top: 120px;
    height: fit-content;
}

.summary-card {
    background: #fff;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 5px 25px rgba(0,0,0,0.05);
}

.summary-card h3 {
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 2px solid #eee;
}

.summary-products {
    max-height: 300px;
    overflow-y: auto;
    margin-bottom: 16px;
}

.summary-products::-webkit-scrollbar {
    width: 4px;
}

.summary-products::-webkit-scrollbar-thumb {
    background: #ddd;
    border-radius: 10px;
}

.summary-product {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 0;
    border-bottom: 1px solid #f0f0f0;
}

.summary-product:last-child {
    border-bottom: none;
}

.summary-product img {
    width: 50px;
    height: 50px;
    border-radius: 8px;
    object-fit: cover;
    background: #f8f9fb;
}

.summary-product-info {
    flex: 1;
    min-width: 0;
}

.summary-product-name {
    display: block;
    font-weight: 600;
    font-size: 13px;
    color: #222;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.summary-product-variant {
    font-size: 12px;
    color: #6C63FF;
}

.summary-product-meta {
    font-size: 12px;
    color: #999;
}

.summary-product-total {
    font-weight: 700;
    font-size: 14px;
    color: #222;
    white-space: nowrap;
}

.summary-divider {
    border-top: 1px solid #eee;
    margin: 12px 0;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    padding: 6px 0;
    font-size: 14px;
    color: #666;
}

.summary-row.discount {
    color: #2ecc71;
}

.summary-total {
    display: flex;
    justify-content: space-between;
    padding: 12px 0;
    font-size: 20px;
    font-weight: 800;
}

.total-amount {
    color: #ffd400;
}

.btn-block {
    width: 100%;
    justify-content: center;
    margin-top: 8px;
}

.btn-lg {
    padding: 16px;
    font-size: 16px;
}

.payment-security {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    margin-top: 16px;
    font-size: 13px;
    color: #999;
}

.payment-security img {
    height: 24px;
}

.payment-security i {
    font-size: 16px;
    color: #ffd400;
}

.back-to-cart {
    display: block;
    text-align: center;
    margin-top: 16px;
    color: #999;
    font-size: 14px;
    text-decoration: none;
    transition: all 0.3s;
}

.back-to-cart:hover {
    color: #ffd400;
}

.alert {
    padding: 14px 18px;
    border-radius: 12px;
    font-weight: 500;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
}

.alert-error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

@media (max-width: 1024px) {
    .checkout-wrapper {
        grid-template-columns: 1fr;
    }
    
    .checkout-summary {
        position: static;
    }
}

@media (max-width: 768px) {
    .checkout-page {
        padding: 20px 0 40px;
    }
    
    .page-header h1 {
        font-size: 24px;
    }
    
    .form-section {
        padding: 20px;
    }
    
    .payment-method-content {
        flex-wrap: wrap;
    }
    
    .payment-method-content .payment-icons {
        margin-left: 0;
        width: 100%;
    }
}

@media (max-width: 480px) {
    .form-section {
        padding: 16px;
    }
    
    .summary-product {
        flex-wrap: wrap;
    }
    
    .summary-product-total {
        margin-left: 62px;
        width: 100%;
    }
    
    .payment-info-box {
        flex-direction: column;
        align-items: center;
        text-align: center;
    }
}
/* Güvenli Ödemeye Geç Butonu Özel Stili */
.btn-primary.btn-block.btn-lg[name="iyzico_odeme"] {
    background-color: #ffd400;
    color: #111111;
    border: none;
    border-radius: 12px;
    padding: 16px 24px;
    font-size: 18px;
    font-weight: 700;
    letter-spacing: 0.3px;
    width: 100%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    box-shadow: 0 6px 20px rgba(255, 212, 0, 0.35);
    transition: all 0.25s ease-in-out;
    position: relative;
    overflow: hidden;
}

/* Hover (Üzerine Gelindiğinde) Etkisi */
.btn-primary.btn-block.btn-lg[name="iyzico_odeme"]:hover {
    background-color: #e6bf00;
    color: #000000;
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(255, 212, 0, 0.5);
}

/* Active (Tıklanma Anı) Etkisi */
.btn-primary.btn-block.btn-lg[name="iyzico_odeme"]:active {
    transform: translateY(1px);
    box-shadow: 0 4px 12px rgba(255, 212, 0, 0.3);
}

/* İçindeki Kilit İkonu Ayarı */
.btn-primary.btn-block.btn-lg[name="iyzico_odeme"] i {
    font-size: 19px;
    transition: transform 0.2s ease;
}

.btn-primary.btn-block.btn-lg[name="iyzico_odeme"]:hover i {
    transform: scale(1.15);
}
</style>

<?php
// =====================================================
// FOOTER'I DAHİL ET
// =====================================================
include '../includes/footer.php';
?>