<?php
// =====================================================
// SEPET SAYFASI - public/sepet.php
// =====================================================

require_once '../includes/config.php';

// Kullanıcı giriş kontrolü
$user_id = isLoggedIn() ? $_SESSION['user_id'] : null;
$session_id = session_id();

// =====================================================
// SEPETİ TEMİZLE
// =====================================================
if (isset($_GET['temizle'])) {
    if ($user_id) {
        $db->query("DELETE FROM sepet WHERE kullanici_id = $user_id");
    } else {
        $db->query("DELETE FROM sepet WHERE session_id = '$session_id'");
    }
    header("Location: " . SITE_URL . "public/sepet.php");
    exit();
}

// =====================================================
// SEPETTEN KALDIR
// =====================================================
if (isset($_GET['kaldir']) && is_numeric($_GET['kaldir'])) {
    $id = (int)$_GET['kaldir'];
    if ($user_id) {
        $db->query("DELETE FROM sepet WHERE id = $id AND kullanici_id = $user_id");
    } else {
        $db->query("DELETE FROM sepet WHERE id = $id AND session_id = '$session_id'");
    }
    header("Location: " . SITE_URL . "public/sepet.php");
    exit();
}

// =====================================================
// SEPET GÜNCELLE (Adet)
// =====================================================
if (isset($_POST['update_cart']) && isset($_POST['adetler'])) {
    $adetler = $_POST['adetler'];
    foreach ($adetler as $id => $adet) {
        $adet = (int)$adet;
        if ($adet > 0) {
            if ($user_id) {
                $db->query("UPDATE sepet SET adet = $adet WHERE id = $id AND kullanici_id = $user_id");
            } else {
                $db->query("UPDATE sepet SET adet = $adet WHERE id = $id AND session_id = '$session_id'");
            }
        }
    }
    header("Location: " . SITE_URL . "public/sepet.php");
    exit();
}

// =====================================================
// SEPET VERİLERİNİ ÇEK
// =====================================================
try {
    if ($user_id) {
        $stmt = $db->prepare("
            SELECT s.*, u.ad as urun_adi, u.slug, u.resim_url, 
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
    } else {
        $stmt = $db->prepare("
            SELECT s.*, u.ad as urun_adi, u.slug, u.resim_url,
                   u.fiyat as urun_fiyat, u.indirimli_fiyat as urun_indirimli_fiyat,
                   v.renk, v.beden, v.fiyat as varyant_fiyat, v.indirimli_fiyat as varyant_indirimli_fiyat,
                   v.resim_url as varyant_resim_url
            FROM sepet s
            LEFT JOIN urunler u ON s.urun_id = u.id
            LEFT JOIN urun_varyantlari v ON s.varyant_id = v.id
            WHERE s.session_id = ?
            ORDER BY s.eklenme_tarihi DESC
        ");
        $stmt->execute([$session_id]);
    }
    $sepet_urunler = $stmt->fetchAll();
} catch (PDOException $e) {
    $sepet_urunler = [];
}

// =====================================================
// SEPET ÖZETİ
// =====================================================
$toplam_urun = 0;
$ara_toplam = 0;
$indirim_toplam = 0;
$kargo_ucreti = 0;
$genel_toplam = 0;
$kargo_limit = 750; // Ücretsiz kargo limiti

foreach ($sepet_urunler as $item) {
    // Birim fiyatı belirle (varyant varsa varyant fiyatı, yoksa ürün fiyatı)
    if ($item['varyant_id']) {
        $birim_fiyat = $item['varyant_indirimli_fiyat'] ?? $item['varyant_fiyat'] ?? $item['urun_fiyat'];
    } else {
        $birim_fiyat = $item['urun_indirimli_fiyat'] ?? $item['urun_fiyat'];
    }
    
    $toplam_urun += $item['adet'];
    $ara_toplam += $birim_fiyat * $item['adet'];
}

// İndirim hesapla (varsa)
$indirim_toplam = 0; // İleride kampanya sistemi ile eklenecek

// Kargo ücreti
if ($ara_toplam >= $kargo_limit) {
    $kargo_ucreti = 0;
} else {
    $kargo_ucreti = 29.90;
}

$genel_toplam = $ara_toplam - $indirim_toplam + $kargo_ucreti;

// =====================================================
// HEADER'I DAHİL ET
// =====================================================
include '../includes/header.php';
?>

<!-- ============================================ -->
<!-- SEPET SAYFASI -->
<!-- ============================================ -->
<section class="cart-page">
    <div class="container">
        
        <div class="page-header">
            <h1>Sepetim</h1>
            <?php if (!empty($sepet_urunler)): ?>
                <span class="item-count"><?php echo $toplam_urun; ?> ürün</span>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($sepet_urunler)): ?>
        
        <div class="cart-wrapper">
            
            <!-- Sepet Listesi -->
            <div class="cart-items">
                <form method="POST" action="">
                    <div class="cart-table">
                        <div class="cart-header">
                            <div class="col-product">Ürün</div>
                            <div class="col-price">Fiyat</div>
                            <div class="col-quantity">Adet</div>
                            <div class="col-total">Toplam</div>
                            <div class="col-remove"></div>
                        </div>
                        
                        <?php foreach ($sepet_urunler as $item): 
                            // Birim fiyat
                            if ($item['varyant_id']) {
                                $birim_fiyat = $item['varyant_indirimli_fiyat'] ?? $item['varyant_fiyat'] ?? $item['urun_fiyat'];
                            } else {
                                $birim_fiyat = $item['urun_indirimli_fiyat'] ?? $item['urun_fiyat'];
                            }
                            $toplam_fiyat = $birim_fiyat * $item['adet'];
                            
                            // Resim URL
                            // Resim URL - Varyant resmi varsa onu göster
$resim_url = '';
if (!empty($item['varyant_resim_url'])) {
    // Varyant resmi var
    $resim_url = $item['varyant_resim_url'];
    // URL'yi düzelt
    if (strpos($resim_url, 'http://') === 0 || strpos($resim_url, 'https://') === 0) {
        $resim = $resim_url;
    } elseif (strpos($resim_url, 'uploads/urunler/') === 0) {
        $resim = SITE_URL . $resim_url;
    } elseif (strpos($resim_url, 'urunler/') === 0) {
        $resim = SITE_URL . 'uploads/urunler/' . $resim_url;
    } else {
        $resim = SITE_URL . 'uploads/urunler/' . $resim_url;
    }
} elseif ($item['resim_url']) {
    // Ürün ana resmi
    if (strpos($item['resim_url'], 'http://') === 0 || strpos($item['resim_url'], 'https://') === 0) {
        $resim = $item['resim_url'];
    } elseif (strpos($item['resim_url'], 'uploads/') === 0) {
        $resim = SITE_URL . "/urunler/" . $item['resim_url'];
    } else {
        $resim = SITE_URL . 'uploads/' . $item['resim_url'];
    }
} else {
    $resim = 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=200';
}
                            
                            // Varyant bilgisi - DETAYLI
$varyant_bilgi = '';
$varyant_detay = [];

if (!empty($item['renk'])) {
    $varyant_detay[] = 'Renk: ' . $item['renk'];
}
if (!empty($item['beden'])) {
    $varyant_detay[] = 'Beden: ' . $item['beden'];
}

if (!empty($varyant_detay)) {
    $varyant_bilgi = implode(' | ', $varyant_detay);
}
                        ?>
                        <div class="cart-item">
                            <div class="col-product">
                                <div class="product-info">
                                    <a href="<?php echo SITE_URL; ?>public/urun-detay.php?slug=<?php echo $item['slug']; ?>" class="product-image">
                                        <img src="<?php echo $resim; ?>" alt="<?php echo $item['urun_adi']; ?>">
                                    </a>
                                    <div class="product-details">
    <a href="<?php echo SITE_URL; ?>public/urun-detay.php?slug=<?php echo $item['slug']; ?>" class="product-name">
        <?php echo $item['urun_adi']; ?>
    </a>
    <?php if ($varyant_bilgi): ?>
        <span class="product-variant">
            <i class="fa-solid fa-tag"></i> <?php echo $varyant_bilgi; ?>
        </span>
    <?php endif; ?>
    <?php if ($item['varyant_id']): ?>
        <span class="product-variant-badge">Varyantlı Ürün</span>
    <?php endif; ?>
</div>
                                </div>
                            </div>
                            
                            <div class="col-price">
                                <span class="price"><?php echo number_format($birim_fiyat, 0, ',', '.'); ?> TL</span>
                            </div>
                            
                            <div class="col-quantity">
                                <div class="quantity-selector">
                                    <button type="button" class="qty-btn" onclick="updateQty(<?php echo $item['id']; ?>, -1)">-</button>
                                    <input type="number" name="adetler[<?php echo $item['id']; ?>]" value="<?php echo $item['adet']; ?>" min="1" max="99" class="qty-input" data-id="<?php echo $item['id']; ?>">
                                    <button type="button" class="qty-btn" onclick="updateQty(<?php echo $item['id']; ?>, 1)">+</button>
                                </div>
                            </div>
                            
                            <div class="col-total">
                                <span class="total-price"><?php echo number_format($toplam_fiyat, 0, ',', '.'); ?> TL</span>
                            </div>
                            
                            <div class="col-remove">
                                <a href="<?php echo SITE_URL; ?>public/sepet.php?kaldir=<?php echo $item['id']; ?>" class="remove-btn" onclick="return confirm('Bu ürünü sepetten kaldırmak istediğinize emin misiniz?')">
                                    <i class="fa-regular fa-trash-can"></i>
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="cart-actions">
                        <a href="<?php echo SITE_URL; ?>public/urunler.php" class="btn btn-outline">
                            <i class="fa-solid fa-arrow-left"></i> Alışverişe Devam Et
                        </a>
                        <div style="display:none" class="cart-actions-right">
                            <button type="submit" name="update_cart" class="btn btn-outline">
                                <i class="fa-solid fa-rotate"></i> Sepeti Güncelle
                            </button>
                            <a href="<?php echo SITE_URL; ?>public/sepet.php?temizle=1" class="btn btn-outline" onclick="return confirm('Sepeti tamamen boşaltmak istediğinize emin misiniz?')">
                                <i class="fa-regular fa-trash-can"></i> Sepeti Boşalt
                            </a>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Sepet Özeti -->
            <div class="cart-summary">
                <h3>Sipariş Özeti</h3>
                
                <div class="summary-row">
                    <span>Ara Toplam</span>
                    <span><?php echo number_format($ara_toplam, 0, ',', '.'); ?> TL</span>
                </div>
                
                <?php if ($indirim_toplam > 0): ?>
                <div class="summary-row discount">
                    <span>İndirim</span>
                    <span>-<?php echo number_format($indirim_toplam, 0, ',', '.'); ?> TL</span>
                </div>
                <?php endif; ?>
                
                <div class="summary-row">
                    <span>Kargo</span>
                    <span>
                        <?php if ($kargo_ucreti == 0): ?>
                            <span style="color:#2ecc71;">Ücretsiz</span>
                        <?php else: ?>
                            <?php echo number_format($kargo_ucreti, 0, ',', '.'); ?> TL
                        <?php endif; ?>
                    </span>
                </div>
                
                <?php if ($kargo_ucreti > 0): ?>
                <div class="free-shipping-info">
                    <i class="fa-solid fa-truck"></i>
                    <?php echo number_format($kargo_limit - $ara_toplam, 0, ',', '.'); ?> TL daha alışveriş yaparsanız kargo ücretsiz!
                </div>
                <?php else: ?>
                <div class="free-shipping-info success">
                    <i class="fa-solid fa-check-circle"></i>
                    Ücretsiz kargo kazandınız! 🎉
                </div>
                <?php endif; ?>
                
                <div class="summary-total">
                    <span>Toplam</span>
                    <span class="total-amount"><?php echo number_format($genel_toplam, 0, ',', '.'); ?> TL</span>
                </div>
                
                <a href="<?php echo SITE_URL; ?>public/odeme.php" class="btn btn-primary btn-block btn-lg">
                    <i class="fa-solid fa-lock"></i> Ödemeye Geç
                </a>
                
                <div class="payment-security">
                    <i class="fa-solid fa-shield-halved"></i>
                    <span>Güvenli Ödeme</span>
                    <i class="fa-solid fa-credit-card"></i>
                    <span>256-Bit SSL</span>
                </div>
            </div>
            
        </div>
        
        <?php else: ?>
        
        <!-- Boş Sepet -->
        <div class="empty-cart">
            <div class="empty-cart-icon">
                <i class="fa-regular fa-cart-shopping"></i>
            </div>
            <h2>Sepetiniz Boş</h2>
            <p>Alışverişe başlamak için ürünleri inceleyin.</p>
            <a href="<?php echo SITE_URL; ?>public/urunler.php" class="btn btn-primary">
                <i class="fa-solid fa-arrow-left"></i> Alışverişe Başla
            </a>
            <div class="empty-cart-suggestions">
                <h4>İlginizi Çekebilir</h4>
                <div class="suggestion-grid">
                    <?php
                    try {
                        $stmt = $db->query("SELECT * FROM urunler WHERE durum = 'aktif' ORDER BY RAND() LIMIT 4");
                        $onerilen = $stmt->fetchAll();
                        foreach ($onerilen as $urun):
                    ?>
                        <a href="<?php echo SITE_URL; ?>public/urun-detay.php?slug=<?php echo $urun['slug']; ?>" class="suggestion-item">
                            <?php if ($urun['resim_url']): ?>
                                <img src="<?php echo SITE_URL; ?>uploads/<?php echo $urun['resim_url']; ?>" alt="<?php echo $urun['ad']; ?>">
                            <?php else: ?>
                                <img src="https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=200" alt="<?php echo $urun['ad']; ?>">
                            <?php endif; ?>
                            <span><?php echo $urun['ad']; ?></span>
                            <span class="price"><?php echo number_format($urun['fiyat'], 0, ',', '.'); ?> TL</span>
                        </a>
                    <?php 
                        endforeach;
                    } catch (PDOException $e) {}
                    ?>
                </div>
            </div>
        </div>
        
        <?php endif; ?>
        
    </div>
</section>

<!-- ============================================ -->
<!-- JAVASCRIPT -->
<!-- ============================================ -->
<script>
// ============================================
// ADET GÜNCELLE - OTOMATİK
// ============================================
function updateQty(id, change) {
    const input = document.querySelector('.qty-input[data-id="' + id + '"]');
    if (!input) return;
    
    let value = parseInt(input.value) + change;
    if (value < 1) value = 1;
    if (value > 99) value = 99;
    input.value = value;
    
    // Form'u submit et - değişiklikleri kaydet
    const form = input.closest('form');
    if (form) {
        // Submit butonuna tıkla
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.click();
        } else {
            form.submit();
        }
    }
}

// ============================================
// ADET MANUEL DEĞİŞTİRME
// ============================================
document.querySelectorAll('.qty-input').forEach(function(input) {
    // Enter tuşu ile güncelle
    input.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const form = this.closest('form');
            if (form) {
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.click();
                } else {
                    form.submit();
                }
            }
        }
    });
    
    // Input değiştiğinde otomatik güncelle (1 saniye bekle)
    let timeout;
    input.addEventListener('input', function() {
        clearTimeout(timeout);
        let value = parseInt(this.value);
        if (isNaN(value) || value < 1) value = 1;
        if (value > 99) value = 99;
        this.value = value;
        
        timeout = setTimeout(() => {
            const form = this.closest('form');
            if (form) {
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.click();
                } else {
                    form.submit();
                }
            }
        }, 800);
    });
});
</script>

<!-- ============================================ -->
<!-- CSS - style.css EKLENECEK -->
<!-- ============================================ -->
<style>
/* ============================================
   SEPET SAYFASI ÖZEL STİLLER
   ============================================ */

.cart-page {
    padding: 40px 0 60px;
    background: #f8f9fb;
    min-height: calc(100vh - 200px);
}

.page-header {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 30px;
}

.page-header h1 {
    font-size: 28px;
    font-weight: 800;
}

.item-count {
    color: #999;
    font-size: 14px;
}

/* Cart Wrapper */
.cart-wrapper {
    display: grid;
    grid-template-columns: 1fr 360px;
    gap: 30px;
}

/* Cart Items */
.cart-items {
    background: #fff;
    border-radius: 20px;
    padding: 24px;
    box-shadow: 0 5px 25px rgba(0,0,0,0.05);
}

.cart-table {
    width: 100%;
}

.cart-header {
    display: grid;
    grid-template-columns: 1fr 120px 120px 120px 40px;
    gap: 16px;
    padding-bottom: 12px;
    border-bottom: 2px solid #eee;
    font-weight: 600;
    font-size: 14px;
    color: #999;
}

.cart-item {
    display: grid;
    grid-template-columns: 1fr 120px 120px 120px 40px;
    gap: 16px;
    align-items: center;
    padding: 16px 0;
    border-bottom: 1px solid #f0f0f0;
}

.cart-item:last-child {
    border-bottom: none;
}

/* Product Info */
.col-product {
    min-width: 0;
}

.product-info {
    display: flex;
    gap: 16px;
    align-items: center;
}

.product-image {
    width: 80px;
    height: 80px;
    border-radius: 12px;
    overflow: hidden;
    flex-shrink: 0;
    background: #f8f9fb;
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.product-details {
    flex: 1;
    min-width: 0;
}

.product-name {
    display: block;
    font-weight: 600;
    font-size: 15px;
    color: #222;
    text-decoration: none;
    transition: all 0.3s;
}

.product-name:hover {
    color: #ffd400;
}

.product-variant {
    font-size: 13px;
    color: #999;
}

/* Price */
.col-price .price {
    font-weight: 600;
    font-size: 15px;
}

/* Quantity */
.quantity-selector {
    display: flex;
    align-items: center;
    border: 2px solid #eee;
    border-radius: 10px;
    overflow: hidden;
    max-width: 110px;
}

.quantity-selector .qty-btn {
    width: 34px;
    height: 34px;
    border: none;
    background: #f8f9fb;
    font-size: 16px;
    cursor: pointer;
    transition: all 0.3s;
}

.quantity-selector .qty-btn:hover {
    background: #eee;
}

.quantity-selector input {
    width: 40px;
    height: 34px;
    border: none;
    text-align: center;
    font-size: 14px;
    font-weight: 600;
    font-family: 'Inter', sans-serif;
}

.quantity-selector input::-webkit-outer-spin-button,
.quantity-selector input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

.quantity-selector input[type=number] {
    -moz-appearance: textfield;
}

/* Total */
.col-total .total-price {
    font-weight: 700;
    font-size: 16px;
    color: #222;
}

/* Remove */
.remove-btn {
    color: #ccc;
    font-size: 18px;
    transition: all 0.3s;
}

.remove-btn:hover {
    color: #ff3366;
}

/* Cart Actions */
.cart-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #eee;
    flex-wrap: wrap;
    gap: 12px;
}

.cart-actions-right {
    display: flex;
    gap: 12px;
}

/* Summary */
.cart-summary {
    background: #fff;
    border-radius: 20px;
    padding: 24px;
    box-shadow: 0 5px 25px rgba(0,0,0,0.05);
    height: fit-content;
    position: sticky;
    top: 120px;
}

.cart-summary h3 {
    font-size: 20px;
    font-weight: 700;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 2px solid #eee;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    font-size: 15px;
    color: #666;
}

.summary-row.discount {
    color: #2ecc71;
}

.free-shipping-info {
    padding: 12px 16px;
    background: #fef9e7;
    border-radius: 10px;
    font-size: 13px;
    color: #856404;
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 8px 0 16px;
}

.free-shipping-info.success {
    background: #d1fae5;
    color: #065f46;
}

.summary-total {
    display: flex;
    justify-content: space-between;
    padding: 16px 0;
    border-top: 2px solid #eee;
    font-size: 20px;
    font-weight: 800;
}

.total-amount {
    color: #ffd400;
}

.btn-block {
    width: 100%;
    justify-content: center;
    margin-top: 16px;
}

.btn-lg {
    padding: 16px;
    font-size: 16px;
}

.payment-security {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 16px;
    margin-top: 16px;
    font-size: 13px;
    color: #999;
}

.payment-security i {
    font-size: 18px;
    color: #ffd400;
}

/* Empty Cart */
.empty-cart {
    text-align: center;
    padding: 60px 20px;
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 5px 25px rgba(0,0,0,0.05);
}

.empty-cart-icon {
    font-size: 80px;
    color: #ddd;
    margin-bottom: 16px;
}

.empty-cart h2 {
    font-size: 28px;
    font-weight: 800;
    margin-bottom: 8px;
}

.empty-cart p {
    color: #999;
    font-size: 16px;
    margin-bottom: 24px;
}

.empty-cart-suggestions {
    margin-top: 40px;
    padding-top: 30px;
    border-top: 2px solid #eee;
}

.empty-cart-suggestions h4 {
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 16px;
}

.suggestion-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
}

.suggestion-item {
    background: #f8f9fb;
    border-radius: 12px;
    padding: 12px;
    text-align: center;
    text-decoration: none;
    color: #222;
    transition: all 0.3s;
}

.suggestion-item:hover {
    transform: translateY(-4px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
}

.suggestion-item img {
    width: 100%;
    height: 120px;
    object-fit: contain;
    border-radius: 8px;
    background: #fff;
}

.suggestion-item span {
    display: block;
    font-size: 13px;
    font-weight: 500;
    margin-top: 6px;
}

.suggestion-item .price {
    font-weight: 700;
    color: #ffd400;
    font-size: 14px;
}

/* Responsive */
@media (max-width: 1024px) {
    .cart-wrapper {
        grid-template-columns: 1fr;
    }
    
    .cart-summary {
        position: static;
    }
}

@media (max-width: 768px) {
    .cart-header {
        display: none;
    }
    
    .cart-item {
        grid-template-columns: 1fr;
        gap: 8px;
        padding: 16px 0;
    }
    
    .col-product {
        grid-row: 1;
    }
    
    .col-price {
        display: flex;
        justify-content: space-between;
        padding: 4px 0;
        border-top: 1px solid #f0f0f0;
    }
    
    .col-price::before {
        content: 'Fiyat:';
        font-weight: 600;
        color: #999;
    }
    
    .col-quantity {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 4px 0;
        border-top: 1px solid #f0f0f0;
    }
    
    .col-quantity::before {
        content: 'Adet:';
        font-weight: 600;
        color: #999;
    }
    
    .col-total {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 4px 0;
        border-top: 1px solid #f0f0f0;
    }
    
    .col-total::before {
        content: 'Toplam:';
        font-weight: 600;
        color: #999;
    }
    
    .col-remove {
        text-align: right;
        border-top: 1px solid #f0f0f0;
        padding-top: 8px;
    }
    
    .product-info {
        gap: 12px;
    }
    
    .product-image {
        width: 60px;
        height: 60px;
    }
    
    .cart-actions {
        flex-direction: column;
        align-items: stretch;
    }
    
    .cart-actions-right {
        flex-direction: column;
    }
    
    .suggestion-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 480px) {
    .cart-page {
        padding: 20px 0 40px;
    }
    
    .page-header h1 {
        font-size: 24px;
    }
    
    .cart-items {
        padding: 16px;
    }
    
    .cart-summary {
        padding: 16px;
    }
    
    .suggestion-grid {
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }
    
    .suggestion-item img {
        height: 100px;
    }
}
/* Ödemeye Geç Butonları Özel Stili (Hem <a> hem <button> için) */
a.btn-primary[href*="odeme.php"],
button.btn-primary[name="iyzico_odeme"] {
    background-color: #ffd400;
    color: #111111 !important;
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
    text-decoration: none;
    box-shadow: 0 6px 20px rgba(255, 212, 0, 0.35);
    transition: all 0.25s ease-in-out;
    box-sizing: border-box;
}

/* Hover (Üzerine Gelindiğinde) Etkisi */
a.btn-primary[href*="odeme.php"]:hover,
button.btn-primary[name="iyzico_odeme"]:hover {
    background-color: #e6bf00;
    color: #000000 !important;
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(255, 212, 0, 0.5);
}

/* Active (Tıklanma Anı) Etkisi */
a.btn-primary[href*="odeme.php"]:active,
button.btn-primary[name="iyzico_odeme"]:active {
    transform: translateY(1px);
    box-shadow: 0 4px 12px rgba(255, 212, 0, 0.3);
}

/* İçindeki Kilit İkonu Ayarı */
a.btn-primary[href*="odeme.php"] i,
button.btn-primary[name="iyzico_odeme"] i {
    font-size: 19px;
    transition: transform 0.2s ease;
}

a.btn-primary[href*="odeme.php"]:hover i,
button.btn-primary[name="iyzico_odeme"]:hover i {
    transform: scale(1.15);
}
</style>

<?php
// =====================================================
// FOOTER'I DAHİL ET
// =====================================================
include '../includes/footer.php';
?>