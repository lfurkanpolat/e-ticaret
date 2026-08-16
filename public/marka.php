<?php
// =====================================================
// MARKA SAYFASI - public/marka.php
// =====================================================

require_once '../includes/config.php';

$slug = isset($_GET['slug']) ? clean($_GET['slug']) : '';
$page = isset($_GET['sayfa']) ? (int)$_GET['sayfa'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// =====================================================
// MARKA BİLGİLERİNİ ÇEK
// =====================================================
try {
    $stmt = $db->prepare("
        SELECT * FROM markalar 
        WHERE slug = ? AND durum = 'aktif'
    ");
    $stmt->execute([$slug]);
    $marka = $stmt->fetch();
} catch (PDOException $e) {
    $marka = null;
}

// Marka bulunamadıysa ana sayfaya yönlendir
if (!$marka) {
    header("Location: " . SITE_URL . "public/index.php");
    exit();
}

// =====================================================
// MARKAYA AİT ÜRÜNLERİ ÇEK
// =====================================================
try {
    // Toplam ürün sayısı
    $countSql = "
        SELECT COUNT(*) as toplam 
        FROM urunler 
        WHERE marka_id = ? AND durum = 'aktif'
    ";
    $stmt = $db->prepare($countSql);
    $stmt->execute([$marka['id']]);
    $toplam_kayit = $stmt->fetch()['toplam'] ?? 0;
    
    // Ürünleri çek
    $sql = "
        SELECT u.*, k.ad as kategori_adi
        FROM urunler u
        LEFT JOIN kategoriler k ON u.kategori_id = k.id
        WHERE u.marka_id = ? AND u.durum = 'aktif'
        ORDER BY u.olusturma_tarihi DESC
        LIMIT ? OFFSET ?
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute([$marka['id'], $limit, $offset]);
    $urunler = $stmt->fetchAll();
    
    $toplam_sayfa = ceil($toplam_kayit / $limit);
    
} catch (PDOException $e) {
    $urunler = [];
    $toplam_kayit = 0;
    $toplam_sayfa = 0;
}

// =====================================================
// SAYFA BAŞLIĞI
// =====================================================
$page_title = $marka['ad'] . ' - ' . SITE_NAME;

// =====================================================
// HEADER'I DAHİL ET
// =====================================================
include '../includes/header.php';
?>

<!-- ============================================ -->
<!-- MARKA SAYFASI -->
<!-- ============================================ -->
<section class="brand-page">
    <div class="container">
        
        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="<?php echo SITE_URL; ?>public/index.php">Ana Sayfa</a>
            <span class="separator">/</span>
            <a href="<?php echo SITE_URL; ?>public/markalar.php">Markalar</a>
            <span class="separator">/</span>
            <span class="current"><?php echo $marka['ad']; ?></span>
        </div>
        
        <!-- Marka Başlığı -->
        <div class="brand-header">
            <div class="brand-header-content">
                <?php if ($marka['logo_url']): ?>
                    <div class="brand-logo">
                        <img src="<?php echo SITE_URL; ?>uploads/markalar/<?php echo $marka['logo_url']; ?>" alt="<?php echo $marka['ad']; ?>">
                    </div>
                <?php else: ?>
                    <div class="brand-logo placeholder">
                        <i class="fa-solid fa-copyright"></i>
                    </div>
                <?php endif; ?>
                <div class="brand-info">
                    <h1><?php echo $marka['ad']; ?></h1>
                    <?php if ($marka['aciklama']): ?>
                        <p><?php echo $marka['aciklama']; ?></p>
                    <?php endif; ?>
                    <?php if ($marka['web_sitesi']): ?>
                        <a href="<?php echo $marka['web_sitesi']; ?>" target="_blank" class="brand-website">
                            <i class="fa-solid fa-globe"></i> <?php echo parse_url($marka['web_sitesi'], PHP_URL_HOST); ?>
                        </a>
                    <?php endif; ?>
                    <span class="product-count"><?php echo $toplam_kayit; ?> ürün</span>
                </div>
            </div>
        </div>
        
        <!-- Ürün Listesi -->
        <div class="products-section">
            <div class="section-header">
                <h2><?php echo $marka['ad']; ?> Ürünleri</h2>
                <?php if ($toplam_kayit > 0): ?>
                    <span class="result-count"><?php echo $toplam_kayit; ?> ürün bulundu</span>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($urunler)): ?>
                <div class="product-grid">
                    <?php foreach ($urunler as $urun): ?>
                        <div class="product-card">
                            <?php if ($urun['indirimli_fiyat'] && $urun['indirimli_fiyat'] < $urun['fiyat']): ?>
                                <div class="discount">
                                    -<?php echo round((($urun['fiyat'] - $urun['indirimli_fiyat']) / $urun['fiyat']) * 100); ?>%
                                </div>
                            <?php endif; ?>
                            
                            <div class="favorite" onclick="toggleWishlist(<?php echo $urun['id']; ?>, this)">
                                <i class="fa-regular fa-heart"></i>
                            </div>
                            
                            <a href="<?php echo SITE_URL; ?>public/urun-detay.php?slug=<?php echo $urun['slug']; ?>">
                                <?php if ($urun['resim_url']): ?>
                                    <img src="<?php echo SITE_URL; ?>uploads/<?php echo $urun['resim_url']; ?>" alt="<?php echo $urun['ad']; ?>">
                                <?php else: ?>
                                    <img src="https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=500" alt="<?php echo $urun['ad']; ?>">
                                <?php endif; ?>
                            </a>
                            
                            <div class="rating">
                                <?php 
                                $puan = round($urun['puan_ortalamasi'] ?? 0);
                                for ($i = 1; $i <= 5; $i++): 
                                ?>
                                    <?php if ($i <= $puan): ?>
                                        ⭐
                                    <?php else: ?>
                                        ☆
                                    <?php endif; ?>
                                <?php endfor; ?>
                                (<?php echo $urun['puan_sayisi'] ?? 0; ?>)
                            </div>
                            
                            <a href="<?php echo SITE_URL; ?>public/urun-detay.php?slug=<?php echo $urun['slug']; ?>" style="text-decoration:none;color:inherit;">
                                <h3><?php echo $urun['ad']; ?></h3>
                            </a>
                            
                            <?php if ($urun['kategori_adi']): ?>
                                <span class="product-category"><?php echo $urun['kategori_adi']; ?></span>
                            <?php endif; ?>
                            
                            <div class="price">
                                <?php if ($urun['indirimli_fiyat'] && $urun['indirimli_fiyat'] < $urun['fiyat']): ?>
                                    <span class="current"><?php echo number_format($urun['indirimli_fiyat'], 0, ',', '.'); ?> TL</span>
                                    <span class="old"><?php echo number_format($urun['fiyat'], 0, ',', '.'); ?> TL</span>
                                <?php else: ?>
                                    <span class="current"><?php echo number_format($urun['fiyat'], 0, ',', '.'); ?> TL</span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($urun['stok'] > 0): ?>
                                <button onclick="addToCart(<?php echo $urun['id']; ?>)">Sepete Ekle</button>
                            <?php else: ?>
                                <button style="background:#ccc;cursor:not-allowed;" disabled>Stokta Yok</button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Sayfalama -->
                <?php if ($toplam_sayfa > 1): ?>
                    <div class="pagination-wrapper">
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?slug=<?php echo $slug; ?>&sayfa=<?php echo $page - 1; ?>" class="page-link">
                                    <i class="fa-solid fa-chevron-left"></i>
                                </a>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $toplam_sayfa; $i++): ?>
                                <?php if ($i == $page): ?>
                                    <span class="page-link active"><?php echo $i; ?></span>
                                <?php elseif ($i <= 3 || $i > $toplam_sayfa - 3 || abs($i - $page) <= 1): ?>
                                    <a href="?slug=<?php echo $slug; ?>&sayfa=<?php echo $i; ?>" class="page-link">
                                        <?php echo $i; ?>
                                    </a>
                                <?php elseif ($i == 4 && $page > 5): ?>
                                    <span class="page-link dots">…</span>
                                <?php elseif ($i == $toplam_sayfa - 3 && $page < $toplam_sayfa - 4): ?>
                                    <span class="page-link dots">…</span>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if ($page < $toplam_sayfa): ?>
                                <a href="?slug=<?php echo $slug; ?>&sayfa=<?php echo $page + 1; ?>" class="page-link">
                                    <i class="fa-solid fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="empty-state">
                    <i class="fa-regular fa-box-open"></i>
                    <h3>Bu markaya ait henüz ürün yok</h3>
                    <p>Yeni ürünler yakında eklenecek.</p>
                    <a href="<?php echo SITE_URL; ?>public/index.php" class="btn btn-primary" style="margin-top:16px;">
                        <i class="fa-solid fa-arrow-left"></i> Ana Sayfaya Dön
                    </a>
                </div>
            <?php endif; ?>
            
        </div>
        
    </div>
</section>

<!-- ============================================ -->
<!-- JAVASCRIPT -->
<!-- ============================================ -->
<script>
// ============================================
// FAVORİ EKLE/ÇIKAR
// ============================================
function toggleWishlist(productId, element) {
    const icon = element.querySelector('i');
    icon.classList.toggle('fa-regular');
    icon.classList.toggle('fa-solid');
    
    if (icon.classList.contains('fa-solid')) {
        element.style.color = '#ff3366';
    } else {
        element.style.color = '';
    }
    
    fetch('<?php echo SITE_URL; ?>public/favori-ekle.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'urun_id=' + productId
    })
    .catch(error => console.error('Hata:', error));
}

// ============================================
// SEPETE EKLE
// ============================================
function addToCart(productId) {
    const btn = event.currentTarget;
    const originalText = btn.textContent;
    
    btn.textContent = '✓ Eklendi';
    btn.style.background = '#2ecc71';
    btn.style.color = '#fff';
    
    const badge = document.querySelector('.badge');
    if (badge) {
        let current = parseInt(badge.textContent) || 0;
        badge.textContent = current + 1;
    }
    
    setTimeout(function() {
        btn.textContent = originalText;
        btn.style.background = '';
        btn.style.color = '';
    }, 2000);
    
    fetch('<?php echo SITE_URL; ?>public/sepet-ekle.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'urun_id=' + productId + '&adet=1'
    })
    .catch(error => console.error('Hata:', error));
}
</script>

<!-- ============================================ -->
<!-- CSS - style.css EKLENECEK -->
<!-- ============================================ -->
<style>
/* ============================================
   MARKA SAYFASI ÖZEL STİLLER
   ============================================ */

.brand-page {
    padding: 30px 0 60px;
    background: #f8f9fb;
    min-height: calc(100vh - 200px);
}

/* Breadcrumb */
.breadcrumb {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    color: #999;
    margin-bottom: 24px;
}

.breadcrumb a {
    color: #666;
    text-decoration: none;
    transition: all 0.3s;
}

.breadcrumb a:hover {
    color: #ffd400;
}

.breadcrumb .separator {
    color: #ccc;
}

.breadcrumb .current {
    color: #222;
    font-weight: 600;
}

/* Brand Header */
.brand-header {
    background: #fff;
    border-radius: 20px;
    padding: 30px;
    margin-bottom: 30px;
    box-shadow: 0 5px 25px rgba(0,0,0,0.05);
}

.brand-header-content {
    display: flex;
    align-items: center;
    gap: 30px;
}

.brand-logo {
    width: 120px;
    height: 120px;
    border-radius: 16px;
    overflow: hidden;
    background: #f8f9fb;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    border: 1px solid #eee;
    padding: 16px;
}

.brand-logo img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.brand-logo.placeholder {
    font-size: 48px;
    color: #ccc;
}

.brand-info {
    flex: 1;
}

.brand-info h1 {
    font-size: 28px;
    font-weight: 800;
    margin-bottom: 4px;
}

.brand-info p {
    color: #666;
    font-size: 15px;
    margin-bottom: 8px;
}

.brand-website {
    display: inline-block;
    color: #6C63FF;
    font-weight: 500;
    font-size: 14px;
    text-decoration: none;
    margin-bottom: 8px;
}

.brand-website:hover {
    text-decoration: underline;
}

.product-count {
    display: inline-block;
    background: #f5f5f5;
    padding: 4px 14px;
    border-radius: 50px;
    font-size: 13px;
    color: #666;
}

/* Products Section */
.products-section {
    background: #fff;
    border-radius: 20px;
    padding: 30px;
    box-shadow: 0 5px 25px rgba(0,0,0,0.05);
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 12px;
}

.section-header h2 {
    font-size: 22px;
    font-weight: 700;
}

.result-count {
    color: #999;
    font-size: 14px;
}

/* Product Grid */
.product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 20px;
}

.product-card {
    background: #fff;
    border-radius: 16px;
    padding: 16px;
    border: 1px solid #f0f0f0;
    transition: all 0.3s;
    position: relative;
}

.product-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 40px rgba(0,0,0,0.08);
    border-color: #ffd400;
}

.product-card img {
    width: 100%;
    height: 200px;
    object-fit: contain;
    border-radius: 12px;
    background: #f8f9fb;
}

.discount {
    position: absolute;
    top: 16px;
    left: 16px;
    background: #ff3366;
    color: #fff;
    padding: 4px 12px;
    border-radius: 50px;
    font-size: 12px;
    font-weight: 700;
}

.favorite {
    position: absolute;
    top: 16px;
    right: 16px;
    font-size: 18px;
    cursor: pointer;
    color: #ccc;
    transition: all 0.3s;
    background: #fff;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 10px rgba(0,0,0,0.06);
}

.favorite:hover {
    color: #ff3366;
}

.rating {
    color: #ffb400;
    font-size: 13px;
    margin-top: 10px;
}

.product-category {
    display: block;
    font-size: 12px;
    color: #999;
    margin-top: -4px;
    margin-bottom: 8px;
}

.product-card h3 {
    font-size: 14px;
    font-weight: 600;
    margin: 8px 0;
    min-height: 40px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.price {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
}

.price .current {
    font-size: 18px;
    font-weight: 800;
    color: #222;
}

.price .old {
    font-size: 13px;
    color: #999;
    text-decoration: line-through;
}

.product-card button {
    width: 100%;
    padding: 10px;
    border: none;
    border-radius: 10px;
    background: #ffd400;
    font-weight: 700;
    font-size: 13px;
    font-family: 'Inter', sans-serif;
    cursor: pointer;
    transition: all 0.3s;
}

.product-card button:hover {
    background: #f5c800;
    transform: translateY(-2px);
}

.product-card button:disabled {
    background: #ccc;
    cursor: not-allowed;
    transform: none;
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

/* Empty State */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #999;
}

.empty-state i {
    font-size: 64px;
    margin-bottom: 16px;
    opacity: 0.3;
}

.empty-state h3 {
    font-size: 20px;
    color: #222;
    margin-bottom: 8px;
}

.empty-state p {
    color: #999;
}

/* Responsive */
@media (max-width: 992px) {
    .product-grid {
        grid-template-columns: repeat(3, 1fr);
    }
    
    .brand-header-content {
        flex-direction: column;
        text-align: center;
    }
    
    .brand-logo {
        width: 100px;
        height: 100px;
    }
}

@media (max-width: 768px) {
    .brand-page {
        padding: 20px 0 40px;
    }
    
    .brand-header {
        padding: 20px;
    }
    
    .brand-info h1 {
        font-size: 22px;
    }
    
    .products-section {
        padding: 20px;
    }
    
    .product-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
    }
    
    .product-card img {
        height: 160px;
    }
}

@media (max-width: 480px) {
    .brand-header {
        padding: 16px;
    }
    
    .brand-logo {
        width: 80px;
        height: 80px;
        padding: 12px;
    }
    
    .product-grid {
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }
    
    .product-card {
        padding: 12px;
    }
    
    .product-card img {
        height: 140px;
    }
    
    .product-card h3 {
        font-size: 13px;
        min-height: 36px;
    }
    
    .price .current {
        font-size: 16px;
    }
    
    .section-header h2 {
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