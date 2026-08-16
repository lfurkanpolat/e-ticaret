<?php
// =====================================================
// KATEGORİ SAYFASI - public/kategori.php
// =====================================================

require_once '../includes/config.php';

$slug = isset($_GET['slug']) ? clean($_GET['slug']) : '';
$page = isset($_GET['sayfa']) ? (int)$_GET['sayfa'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// =====================================================
// KATEGORİ BİLGİLERİNİ ÇEK
// =====================================================
try {
    $stmt = $db->prepare("
        SELECT * FROM kategoriler 
        WHERE slug = ? AND durum = 'aktif'
    ");
    $stmt->execute([$slug]);
    $kategori = $stmt->fetch();
} catch (PDOException $e) {
    $kategori = null;
}

// Kategori bulunamadıysa ana sayfaya yönlendir
if (!$kategori) {
    header("Location: " . SITE_URL . "public/index.php");
    exit();
}

// =====================================================
// ALT KATEGORİLERİ ÇEK
// =====================================================
try {
    $stmt = $db->prepare("
        SELECT * FROM kategoriler 
        WHERE ust_id = ? AND durum = 'aktif'
        ORDER BY ad
    ");
    $stmt->execute([$kategori['id']]);
    $alt_kategoriler = $stmt->fetchAll();
} catch (PDOException $e) {
    $alt_kategoriler = [];
}

// =====================================================
// ÜRÜNLERİ ÇEK (Kategoriye göre)
// =====================================================
// Ana kategori ID'si ve alt kategori ID'lerini topla
$kategori_ids = [$kategori['id']];
foreach ($alt_kategoriler as $alt) {
    $kategori_ids[] = $alt['id'];
}
$kategori_ids_str = implode(',', $kategori_ids);

try {
    // Toplam ürün sayısı
    $countSql = "
        SELECT COUNT(*) as toplam 
        FROM urunler 
        WHERE kategori_id IN ($kategori_ids_str) AND durum = 'aktif'
    ";
    $stmt = $db->query($countSql);
    $toplam_kayit = $stmt->fetch()['toplam'] ?? 0;
    
    // Ürünleri çek
    $sql = "
        SELECT u.*, m.ad as marka_adi
        FROM urunler u
        LEFT JOIN markalar m ON u.marka_id = m.id
        WHERE u.kategori_id IN ($kategori_ids_str) AND u.durum = 'aktif'
        ORDER BY u.olusturma_tarihi DESC
        LIMIT ? OFFSET ?
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute([$limit, $offset]);
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
$page_title = $kategori['ad'] . ' - ' . SITE_NAME;

// =====================================================
// HEADER'I DAHİL ET
// =====================================================
include '../includes/header.php';
?>

<!-- ============================================ -->
<!-- KATEGORİ SAYFASI -->
<!-- ============================================ -->
<section class="category-page">
    <div class="container">
        
        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="<?php echo SITE_URL; ?>public/index.php">Ana Sayfa</a>
            <span class="separator">/</span>
            <span class="current"><?php echo $kategori['ad']; ?></span>
        </div>
        
        <!-- Kategori Başlığı -->
        <div class="category-header">
            <div class="category-header-content">
                <?php if (!empty($kategori['icon'])): ?>
                    <i class="<?php echo $kategori['icon']; ?>"></i>
                <?php else: ?>
                    <i class="fa-solid fa-tag"></i>
                <?php endif; ?>
                <div>
                    <h1><?php echo $kategori['ad']; ?></h1>
                    <?php if ($kategori['aciklama']): ?>
                        <p><?php echo $kategori['aciklama']; ?></p>
                    <?php endif; ?>
                    <span class="product-count"><?php echo $toplam_kayit; ?> ürün</span>
                </div>
            </div>
        </div>
        
        <!-- Alt Kategoriler -->
        <?php if (!empty($alt_kategoriler)): ?>
            <div class="sub-categories">
                <h3>Alt Kategoriler</h3>
                <div class="sub-categories-grid">
                    <?php foreach ($alt_kategoriler as $alt): ?>
                        <a href="<?php echo SITE_URL; ?>public/kategori.php?slug=<?php echo $alt['slug']; ?>" class="sub-category-card">
                            <?php if (!empty($alt['icon'])): ?>
                                <i class="<?php echo $alt['icon']; ?>"></i>
                            <?php else: ?>
                                <i class="fa-solid fa-chevron-right"></i>
                            <?php endif; ?>
                            <span><?php echo $alt['ad']; ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Ürün Listesi -->
        <div class="products-section">
            <div class="section-header">
                <h2><?php echo $kategori['ad']; ?> Ürünleri</h2>
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
                                    <img style="background:#FFFFFF;" src="<?php echo SITE_URL; ?>uploads/<?php echo $urun['resim_url']; ?>" alt="<?php echo $urun['ad']; ?>">
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
                    <i class="fa-solid fa-box-open"></i>
                    <h3>Bu kategoride henüz ürün yok</h3>
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
   KATEGORİ SAYFASI ÖZEL STİLLER
   ============================================ */

.category-page {
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

/* Category Header */
.category-header {
    background: #fff;
    border-radius: 20px;
    padding: 30px;
    margin-bottom: 30px;
    box-shadow: 0 5px 25px rgba(0,0,0,0.05);
}

.category-header-content {
    display: flex;
    align-items: center;
    gap: 20px;
}

.category-header-content i {
    font-size: 48px;
    color: #ffd400;
    width: 80px;
    height: 80px;
    background: #fef9e7;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.category-header-content h1 {
    font-size: 28px;
    font-weight: 800;
    margin-bottom: 4px;
}

.category-header-content p {
    color: #666;
    font-size: 15px;
}

.product-count {
    display: inline-block;
    background: #f5f5f5;
    padding: 4px 14px;
    border-radius: 50px;
    font-size: 13px;
    color: #666;
    margin-top: 4px;
}

/* Sub Categories */
.sub-categories {
    margin-bottom: 30px;
}

.sub-categories h3 {
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 16px;
}

.sub-categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 12px;
}

.sub-category-card {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 18px;
    background: #fff;
    border-radius: 12px;
    border: 1px solid #eee;
    transition: all 0.3s;
    text-decoration: none;
    color: #222;
}

.sub-category-card:hover {
    border-color: #ffd400;
    transform: translateY(-2px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
}

.sub-category-card i {
    font-size: 18px;
    color: #ffd400;
}

.sub-category-card span {
    font-weight: 500;
    font-size: 14px;
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

.page-link.dots:hover {
    border: none;
    color: #666;
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
}

@media (max-width: 768px) {
    .category-page {
        padding: 20px 0 40px;
    }
    
    .category-header {
        padding: 20px;
    }
    
    .category-header-content i {
        font-size: 32px;
        width: 60px;
        height: 60px;
    }
    
    .category-header-content h1 {
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
    
    .sub-categories-grid {
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    }
}

@media (max-width: 480px) {
    .category-header {
        padding: 16px;
    }
    
    .category-header-content {
        flex-direction: column;
        text-align: center;
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
    
    .pagination {
        flex-wrap: wrap;
    }
}
</style>

<?php
// =====================================================
// FOOTER'I DAHİL ET
// =====================================================
include '../includes/footer.php';
?>