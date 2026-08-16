<?php
// =====================================================
// SİPARİŞLERİM - public/siparislerim.php
// =====================================================

require_once '../includes/config.php';

// Kullanıcı giriş kontrolü
if (!isLoggedIn()) {
    header("Location: " . SITE_URL . "public/giris.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// =====================================================
// FİLTRELEME
// =====================================================
$durum = isset($_GET['durum']) ? clean($_GET['durum']) : '';
$sayfa = isset($_GET['sayfa']) ? (int)$_GET['sayfa'] : 1;
$limit = 10;
$offset = ($sayfa - 1) * $limit;

// =====================================================
// SİPARİŞ LİSTESİNİ ÇEK
// =====================================================
$where = "kullanici_id = ?";
$params = [$user_id];

if ($durum) {
    $where .= " AND siparis_durumu = ?";
    $params[] = $durum;
}

// Toplam sipariş sayısı
try {
    $countSql = "SELECT COUNT(*) as toplam FROM siparisler WHERE $where";
    $stmt = $db->prepare($countSql);
    $stmt->execute($params);
    $toplam_kayit = $stmt->fetch()['toplam'] ?? 0;
} catch (PDOException $e) {
    $toplam_kayit = 0;
}

$toplam_sayfa = ceil($toplam_kayit / $limit);

// Siparişleri çek
try {
    $sql = "
        SELECT s.*, 
               (SELECT COUNT(*) FROM siparis_detay WHERE siparis_id = s.id) as urun_sayisi
        FROM siparisler s
        WHERE $where
        ORDER BY s.olusturma_tarihi DESC
        LIMIT ? OFFSET ?
    ";
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $siparisler = $stmt->fetchAll();
} catch (PDOException $e) {
    $siparisler = [];
    $error = 'Veritabanı hatası: ' . $e->getMessage();
}

// =====================================================
// SİPARİŞ DURUMLARI
// =====================================================
$durumlar = [
    'hazirlaniyor' => 'Hazırlanıyor',
    'kargoya_verildi' => 'Kargoya Verildi',
    'kargoda' => 'Kargoda',
    'teslim_edildi' => 'Teslim Edildi',
    'iptal' => 'İptal',
    'iade' => 'İade'
];

$durum_renkler = [
    'hazirlaniyor' => '#f39c12',
    'kargoya_verildi' => '#3498db',
    'kargoda' => '#2980b9',
    'teslim_edildi' => '#2ecc71',
    'iptal' => '#e74c3c',
    'iade' => '#e67e22'
];

$odeme_durumlari = [
    'beklemede' => 'Bekliyor',
    'onaylandi' => 'Onaylandı',
    'iptal' => 'İptal'
];

// =====================================================
// HEADER'I DAHİL ET
// =====================================================
include '../includes/header.php';
?>

<!-- ============================================ -->
<!-- SİPARİŞLERİM SAYFASI -->
<!-- ============================================ -->
<section class="orders-page">
    <div class="container">
        
        <div class="page-header">
            <h1><i class="fa-solid fa-box"></i> Siparişlerim</h1>
            <span class="order-count"><?php echo $toplam_kayit; ?> sipariş</span>
        </div>
        
        <!-- Filtreler -->
        <div class="filter-section">
            <div class="filter-tabs">
                <a href="<?php echo SITE_URL; ?>public/siparislerim.php" class="filter-tab <?php echo $durum == '' ? 'active' : ''; ?>">
                    Tümü
                </a>
                <a href="?durum=hazirlaniyor" class="filter-tab <?php echo $durum == 'hazirlaniyor' ? 'active' : ''; ?>">
                    Hazırlanıyor
                </a>
                <a href="?durum=kargoya_verildi" class="filter-tab <?php echo $durum == 'kargoya_verildi' ? 'active' : ''; ?>">
                    Kargoya Verildi
                </a>
                <a href="?durum=kargoda" class="filter-tab <?php echo $durum == 'kargoda' ? 'active' : ''; ?>">
                    Kargoda
                </a>
                <a href="?durum=teslim_edildi" class="filter-tab <?php echo $durum == 'teslim_edildi' ? 'active' : ''; ?>">
                    Teslim Edildi
                </a>
                <a href="?durum=iptal" class="filter-tab <?php echo $durum == 'iptal' ? 'active' : ''; ?>">
                    İptal
                </a>
            </div>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($siparisler)): ?>
        
            <div class="orders-list">
                <?php foreach ($siparisler as $siparis): ?>
                    <div class="order-card">
                        
                        <!-- Sipariş Başlığı -->
                        <div class="order-header">
                            <div class="order-info">
                                <span class="order-number">
                                    <i class="fa-solid fa-receipt"></i>
                                    Sipariş #<?php echo $siparis['siparis_no']; ?>
                                </span>
                                <span class="order-date">
                                    <i class="fa-regular fa-calendar"></i>
                                    <?php echo date('d.m.Y H:i', strtotime($siparis['olusturma_tarihi'])); ?>
                                </span>
                            </div>
                            <div class="order-status">
                                <span class="status-badge" style="background: <?php echo $durum_renkler[$siparis['siparis_durumu']] ?? '#999'; ?>; color: #fff;">
                                    <?php echo $durumlar[$siparis['siparis_durumu']] ?? $siparis['siparis_durumu']; ?>
                                </span>
                                <span class="payment-status <?php echo $siparis['odeme_durumu']; ?>">
                                    <?php echo $odeme_durumlari[$siparis['odeme_durumu']] ?? $siparis['odeme_durumu']; ?>
                                </span>
                            </div>
                        </div>
                        
                        <!-- Sipariş Ürünleri -->
                        <div class="order-products">
                            <?php
                            // Sipariş detaylarını çek
                            try {
                                $stmt = $db->prepare("
                                    SELECT sd.*, u.slug, u.resim_url,
                                           v.renk, v.beden
                                    FROM siparis_detay sd
                                    LEFT JOIN urunler u ON sd.urun_id = u.id
                                    LEFT JOIN urun_varyantlari v ON sd.varyant_id = v.id
                                    WHERE sd.siparis_id = ?
                                    LIMIT 3
                                ");
                                $stmt->execute([$siparis['id']]);
                                $detaylar = $stmt->fetchAll();
                                
                                $toplam_urun = count($detaylar);
                                $stmt2 = $db->prepare("SELECT COUNT(*) as toplam FROM siparis_detay WHERE siparis_id = ?");
                                $stmt2->execute([$siparis['id']]);
                                $toplam_urun = $stmt2->fetch()['toplam'];
                                
                                foreach ($detaylar as $index => $detay):
                                    // Resim URL
                                    if ($detay['resim_url']) {
                                        $resim = SITE_URL . 'uploads/' . $detay['resim_url'];
                                    } else {
                                        $resim = 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=100';
                                    }
                                    
                                    $varyant_bilgi = '';
                                    if ($detay['renk'] && $detay['beden']) {
                                        $varyant_bilgi = $detay['renk'] . ' / ' . $detay['beden'];
                                    } elseif ($detay['renk']) {
                                        $varyant_bilgi = $detay['renk'];
                                    } elseif ($detay['beden']) {
                                        $varyant_bilgi = $detay['beden'];
                                    }
                            ?>
                                <div class="order-product-item">
                                    <a href="<?php echo SITE_URL; ?>public/urun-detay.php?slug=<?php echo $detay['slug']; ?>" class="order-product-image">
                                        <img src="<?php echo $resim; ?>" alt="<?php echo $detay['urun_adi']; ?>">
                                    </a>
                                    <div class="order-product-info">
                                        <a href="<?php echo SITE_URL; ?>public/urun-detay.php?slug=<?php echo $detay['slug']; ?>" class="order-product-name">
                                            <?php echo $detay['urun_adi']; ?>
                                        </a>
                                        <?php if ($varyant_bilgi): ?>
                                            <span class="order-product-variant"><?php echo $varyant_bilgi; ?></span>
                                        <?php endif; ?>
                                        <span class="order-product-meta">
                                            <?php echo $detay['adet']; ?> adet × <?php echo number_format($detay['birim_fiyat'], 0, ',', '.'); ?> TL
                                        </span>
                                    </div>
                                    <div class="order-product-total">
                                        <?php echo number_format($detay['toplam_fiyat'], 0, ',', '.'); ?> TL
                                    </div>
                                </div>
                            <?php 
                                    if ($index == 2 && $toplam_urun > 3):
                            ?>
                                <div class="order-more-products">
                                    +<?php echo $toplam_urun - 3; ?> ürün daha
                                </div>
                            <?php 
                                    break;
                                    endif;
                                endforeach;
                            } catch (PDOException $e) {}
                            ?>
                        </div>
                        
                        <!-- Sipariş Alt Bilgisi -->
                        <div class="order-footer">
                            <div class="order-total">
                                <span>Toplam Tutar</span>
                                <strong><?php echo number_format($siparis['toplam_tutar'], 0, ',', '.'); ?> TL</strong>
                            </div>
                            <div class="order-actions">
                                <?php if ($siparis['siparis_durumu'] == 'hazirlaniyor'): ?>
                                    <button class="btn btn-sm btn-outline" onclick="cancelOrder(<?php echo $siparis['id']; ?>)" style="color:#e74c3c;">
                                        <i class="fa-solid fa-times"></i> İptal Et
                                    </button>
                                <?php endif; ?>
                                <a href="<?php echo SITE_URL; ?>public/siparis-detay.php?id=<?php echo $siparis['id']; ?>" class="btn btn-sm btn-primary">
                                    <i class="fa-regular fa-eye"></i> Detay
                                </a>
                                <?php if ($siparis['kargo_takip_no']): ?>
                                    <a href="#" class="btn btn-sm btn-outline" onclick="trackOrder('<?php echo $siparis['kargo_takip_no']; ?>')">
                                        <i class="fa-solid fa-truck"></i> Kargo Takip
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Sayfalama -->
            <?php if ($toplam_sayfa > 1): ?>
                <div class="pagination-wrapper">
                    <div class="pagination">
                        <?php if ($sayfa > 1): ?>
                            <a href="?sayfa=<?php echo $sayfa - 1; ?>&durum=<?php echo $durum; ?>" class="page-link">
                                <i class="fa-solid fa-chevron-left"></i>
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $toplam_sayfa; $i++): ?>
                            <?php if ($i == $sayfa): ?>
                                <span class="page-link active"><?php echo $i; ?></span>
                            <?php elseif ($i <= 3 || $i > $toplam_sayfa - 3 || abs($i - $sayfa) <= 1): ?>
                                <a href="?sayfa=<?php echo $i; ?>&durum=<?php echo $durum; ?>" class="page-link">
                                    <?php echo $i; ?>
                                </a>
                            <?php elseif ($i == 4 && $sayfa > 5): ?>
                                <span class="page-link dots">…</span>
                            <?php elseif ($i == $toplam_sayfa - 3 && $sayfa < $toplam_sayfa - 4): ?>
                                <span class="page-link dots">…</span>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($sayfa < $toplam_sayfa): ?>
                            <a href="?sayfa=<?php echo $sayfa + 1; ?>&durum=<?php echo $durum; ?>" class="page-link">
                                <i class="fa-solid fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            
            <!-- Boş Sipariş -->
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fa-regular fa-box"></i>
                </div>
                <h2>Henüz Siparişiniz Yok</h2>
                <p>Alışverişe başlayarak ilk siparişinizi oluşturun.</p>
                <a href="<?php echo SITE_URL; ?>public/urunler.php" class="btn btn-primary">
                    <i class="fa-solid fa-arrow-left"></i> Alışverişe Başla
                </a>
            </div>
            
        <?php endif; ?>
        
    </div>
</section>

<!-- ============================================ -->
<!-- JAVASCRIPT -->
<!-- ============================================ -->
<script>
// ============================================
// SİPARİŞ İPTAL ET
// ============================================
function cancelOrder(orderId) {
    if (!confirm('Bu siparişi iptal etmek istediğinize emin misiniz?')) {
        return;
    }
    
    fetch('<?php echo SITE_URL; ?>public/siparis-iptal.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'siparis_id=' + orderId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Sipariş başarıyla iptal edildi!');
            location.reload();
        } else {
            alert('Hata: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Hata:', error);
        alert('Bir hata oluştu. Lütfen tekrar deneyin.');
    });
}

// ============================================
// KARGO TAKİP (Demo)
// ============================================
function trackOrder(trackingNo) {
    alert('Kargo takip numarası: ' + trackingNo + '\n\nDemo modunda olduğu için takip sayfası açılamıyor.');
}
</script>

<!-- ============================================ -->
<!-- CSS - style.css EKLENECEK -->
<!-- ============================================ -->
<style>
/* ============================================
   SİPARİŞLERİM SAYFASI ÖZEL STİLLER
   ============================================ */

.orders-page {
    padding: 40px 0 60px;
    background: #f8f9fb;
    min-height: calc(100vh - 200px);
}

.page-header {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 24px;
}

.page-header h1 {
    font-size: 28px;
    font-weight: 800;
}

.order-count {
    color: #999;
    font-size: 14px;
}

/* Filter Tabs */
.filter-section {
    margin-bottom: 30px;
}

.filter-tabs {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.filter-tab {
    padding: 8px 20px;
    border-radius: 50px;
    background: #fff;
    color: #666;
    font-weight: 600;
    font-size: 14px;
    text-decoration: none;
    transition: all 0.3s;
    border: 2px solid transparent;
}

.filter-tab:hover {
    border-color: #ffd400;
    color: #222;
}

.filter-tab.active {
    background: #ffd400;
    color: #111;
    border-color: #ffd400;
}

/* Orders List */
.orders-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.order-card {
    background: #fff;
    border-radius: 20px;
    padding: 24px;
    box-shadow: 0 5px 25px rgba(0,0,0,0.05);
    transition: all 0.3s;
}

.order-card:hover {
    box-shadow: 0 10px 40px rgba(0,0,0,0.08);
}

/* Order Header */
.order-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    padding-bottom: 16px;
    border-bottom: 1px solid #eee;
}

.order-info {
    display: flex;
    align-items: center;
    gap: 16px;
}

.order-number {
    font-weight: 700;
    font-size: 16px;
    color: #222;
}

.order-number i {
    color: #ffd400;
    margin-right: 6px;
}

.order-date {
    color: #999;
    font-size: 14px;
}

.order-date i {
    margin-right: 4px;
}

.order-status {
    display: flex;
    align-items: center;
    gap: 10px;
}

.status-badge {
    padding: 4px 14px;
    border-radius: 50px;
    font-size: 12px;
    font-weight: 600;
}

.payment-status {
    padding: 4px 12px;
    border-radius: 50px;
    font-size: 12px;
    font-weight: 600;
}

.payment-status.beklemede {
    background: #fef3c7;
    color: #92400e;
}

.payment-status.onaylandi {
    background: #d1fae5;
    color: #065f46;
}

.payment-status.iptal {
    background: #fee2e2;
    color: #991b1b;
}

/* Order Products */
.order-products {
    padding: 16px 0;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.order-product-item {
    display: flex;
    align-items: center;
    gap: 16px;
}

.order-product-image {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    overflow: hidden;
    flex-shrink: 0;
    background: #f8f9fb;
}

.order-product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.order-product-info {
    flex: 1;
    min-width: 0;
}

.order-product-name {
    font-weight: 600;
    font-size: 14px;
    color: #222;
    text-decoration: none;
    display: block;
}

.order-product-name:hover {
    color: #ffd400;
}

.order-product-variant {
    font-size: 12px;
    color: #6C63FF;
}

.order-product-meta {
    font-size: 13px;
    color: #999;
}

.order-product-total {
    font-weight: 700;
    font-size: 16px;
    color: #222;
    white-space: nowrap;
}

.order-more-products {
    padding: 8px 16px;
    background: #f8f9fb;
    border-radius: 8px;
    font-size: 13px;
    color: #999;
    text-align: center;
}

/* Order Footer */
.order-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
    padding-top: 16px;
    border-top: 1px solid #eee;
}

.order-total {
    font-size: 15px;
    color: #666;
}

.order-total strong {
    font-size: 20px;
    color: #222;
    margin-left: 8px;
}

.order-actions {
    display: flex;
    gap: 8px;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 80px 20px;
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 5px 25px rgba(0,0,0,0.05);
}

.empty-state-icon {
    font-size: 72px;
    color: #ddd;
    margin-bottom: 16px;
}

.empty-state h2 {
    font-size: 28px;
    font-weight: 800;
    margin-bottom: 8px;
}

.empty-state p {
    color: #999;
    font-size: 16px;
    margin-bottom: 24px;
}

/* Pagination */
.pagination-wrapper {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

.pagination {
    display: flex;
    justify-content: center;
    gap: 4px;
}

.page-link {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 40px;
    height: 40px;
    padding: 0 12px;
    border-radius: 10px;
    border: 1px solid #eee;
    color: #666;
    text-decoration: none;
    transition: all 0.3s;
    font-size: 14px;
}

.page-link:hover {
    border-color: #ffd400;
    color: #222;
}

.page-link.active {
    background: #ffd400;
    border-color: #ffd400;
    color: #111;
    font-weight: 700;
}

.page-link.dots {
    border: none;
    cursor: default;
}

/* Responsive */
@media (max-width: 768px) {
    .orders-page {
        padding: 20px 0 40px;
    }
    
    .page-header h1 {
        font-size: 24px;
    }
    
    .order-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .order-info {
        flex-wrap: wrap;
    }
    
    .order-footer {
        flex-direction: column;
        align-items: stretch;
    }
    
    .order-actions {
        flex-wrap: wrap;
    }
    
    .order-actions .btn {
        flex: 1;
        justify-content: center;
    }
    
    .order-product-item {
        flex-wrap: wrap;
    }
    
    .order-product-total {
        margin-left: 76px;
        width: 100%;
    }
    
    .filter-tabs {
        gap: 4px;
    }
    
    .filter-tab {
        padding: 6px 14px;
        font-size: 13px;
    }
}

@media (max-width: 480px) {
    .order-card {
        padding: 16px;
    }
    
    .order-product-image {
        width: 50px;
        height: 50px;
    }
    
    .order-product-total {
        margin-left: 66px;
    }
    
    .order-total strong {
        font-size: 18px;
    }
}
</style>

<?php
// =====================================================
// FOOTER'I DAHİL ET
// =====================================================
include '../includes/footer.php';
?>