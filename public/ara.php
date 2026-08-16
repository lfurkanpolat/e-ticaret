<?php
// =====================================================
// ARAMA SAYFASI - public/ara.php (SADE)
// =====================================================

require_once '../includes/config.php';

$query = isset($_GET['q']) ? clean($_GET['q']) : '';
$sayfa = isset($_GET['sayfa']) ? (int)$_GET['sayfa'] : 1;
$limit = 12;
$offset = ($sayfa - 1) * $limit;

$page_title = empty($query) ? 'Ürün Ara' : 'Arama: ' . $query;

// =====================================================
// ARAMA SORGUSU
// =====================================================
$where = "u.durum = 'aktif'";
$params = [];

if (!empty($query)) {
    $where .= " AND u.ad LIKE ?";
    $search_term = "%$query%";
    $params[] = $search_term;
}

// Toplam sonuç sayısı
try {
    $countSql = "SELECT COUNT(*) as toplam FROM urunler u WHERE $where";
    $stmt = $db->prepare($countSql);
    $stmt->execute($params);
    $toplam_kayit = $stmt->fetch()['toplam'] ?? 0;
} catch (PDOException $e) {
    $toplam_kayit = 0;
}

$toplam_sayfa = ceil($toplam_kayit / $limit);

// Ürünleri çek
try {
    $sql = "
        SELECT u.*, k.ad as kategori_adi, m.ad as marka_adi
        FROM urunler u
        LEFT JOIN kategoriler k ON u.kategori_id = k.id
        LEFT JOIN markalar m ON u.marka_id = m.id
        WHERE $where
        ORDER BY u.olusturma_tarihi DESC
        LIMIT ? OFFSET ?
    ";
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $urunler = $stmt->fetchAll();
} catch (PDOException $e) {
    $urunler = [];
}

// =====================================================
// HEADER'I DAHİL ET
// =====================================================
include '../includes/header.php';
?>

<!-- ============================================ -->
<!-- ARAMA SONUÇLARI -->
<!-- ============================================ -->
<section style="padding: 30px 0 60px; background: #f8f9fb; min-height: calc(100vh - 200px);">

    <div class="container">

        <!-- Başlık -->
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 24px;">
            <h1 style="font-size: 24px; font-weight: 800; margin: 0;">
                <?php if (!empty($query)): ?>
                    "<?php echo $query; ?>" için arama sonuçları
                <?php else: ?>
                    Tüm Ürünler
                <?php endif; ?>
            </h1>
            <span style="color: #999; font-size: 14px;"><?php echo $toplam_kayit; ?> ürün bulundu</span>
        </div>

        <!-- Breadcrumb -->
        <div style="display: flex; align-items: center; gap: 8px; font-size: 14px; color: #999; margin-bottom: 20px;">
            <a href="<?php echo SITE_URL; ?>public/index.php" style="color: #666; text-decoration: none;">Ana Sayfa</a>
            <span style="color: #ccc;">/</span>
            <span style="color: #222; font-weight: 600;">Arama Sonuçları</span>
        </div>

        <?php if (!empty($urunler)): ?>

            <!-- Ürün Grid -->
            <div class="product-grid">
                <?php foreach ($urunler as $urun): ?>
<div class="product-card">                        
                        <!-- İndirim Etiketi -->
                        <?php if ($urun['indirimli_fiyat'] && $urun['indirimli_fiyat'] < $urun['fiyat']): ?>
                                <div class="discount">
                                -<?php echo round((($urun['fiyat'] - $urun['indirimli_fiyat']) / $urun['fiyat']) * 100); ?>%
                            </div>
                        <?php endif; ?>

                        <!-- Favori Butonu -->
                            <div class="favorite" onclick="toggleWishlist(<?php echo $urun['id']; ?>, this)">
                                <i class="fa-regular fa-heart"></i>
                        </div>

                        <!-- Ürün Resmi -->
                        <a href="<?php echo SITE_URL; ?>public/urun-detay.php?slug=<?php echo $urun['slug']; ?>">
                            <?php if ($urun['resim_url']): ?>
                                <img src="<?php echo SITE_URL; ?>uploads/<?php echo $urun['resim_url']; ?>" alt="<?php echo $urun['ad']; ?>">
                            <?php else: ?>
                                <img src="https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=500" alt="<?php echo $urun['ad']; ?>">
                            <?php endif; ?>
                        </a>

                        <!-- Rating -->
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
                            <span style="color: #999; margin-left: 4px;">(<?php echo $urun['puan_sayisi'] ?? 0; ?>)</span>
                        </div>

                        <!-- Ürün Adı -->
                        <a href="<?php echo SITE_URL; ?>public/urun-detay.php?slug=<?php echo $urun['slug']; ?>" style="text-decoration: none; color: inherit;">
                            <h3 style="font-size: 14px; font-weight: 600; margin: 8px 0; min-height: 40px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;"><?php echo $urun['ad']; ?></h3>
                        </a>

                        <!-- Marka -->
                        <?php if ($urun['marka_adi']): ?>
                            <span style="display: block; font-size: 12px; color: #999; margin-top: -4px; margin-bottom: 8px;"><?php echo $urun['marka_adi']; ?></span>
                        <?php endif; ?>

                        <!-- Fiyat -->
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                            <?php if ($urun['indirimli_fiyat'] && $urun['indirimli_fiyat'] < $urun['fiyat']): ?>
                                <span style="font-size: 18px; font-weight: 800; color: #222;"><?php echo number_format($urun['indirimli_fiyat'], 0, ',', '.'); ?> TL</span>
                                <span style="font-size: 13px; color: #999; text-decoration: line-through;"><?php echo number_format($urun['fiyat'], 0, ',', '.'); ?> TL</span>
                            <?php else: ?>
                                <span style="font-size: 18px; font-weight: 800; color: #222;"><?php echo number_format($urun['fiyat'], 0, ',', '.'); ?> TL</span>
                            <?php endif; ?>
                        </div>

                        <!-- Sepete Ekle -->
                        <?php if ($urun['stok'] > 0): ?>
                            <button onclick="addToCart(<?php echo $urun['id']; ?>)" style="width: 100%; padding: 10px; border: none; border-radius: 10px; background: #ffd400; font-weight: 700; font-size: 13px; font-family: 'Inter', sans-serif; cursor: pointer; transition: all 0.3s; color: #111;">
                                Sepete Ekle
                            </button>
                        <?php else: ?>
                            <button disabled style="width: 100%; padding: 10px; border: none; border-radius: 10px; background: #ccc; font-weight: 700; font-size: 13px; font-family: 'Inter', sans-serif; cursor: not-allowed; color: #999;">
                                Stokta Yok
                            </button>
                        <?php endif; ?>

                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Sayfalama -->
            <?php if ($toplam_sayfa > 1): ?>
                <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; display: flex; justify-content: center; gap: 4px;">
                    <?php if ($sayfa > 1): ?>
                        <a href="?q=<?php echo urlencode($query); ?>&sayfa=<?php echo $sayfa - 1; ?>" style="display: flex; align-items: center; justify-content: center; min-width: 40px; height: 40px; padding: 0 12px; border-radius: 10px; border: 1px solid #eee; color: #666; text-decoration: none; transition: all 0.3s; font-size: 14px;">
                            <i class="fa-solid fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $toplam_sayfa; $i++): ?>
                        <?php if ($i == $sayfa): ?>
                            <span style="display: flex; align-items: center; justify-content: center; min-width: 40px; height: 40px; padding: 0 12px; border-radius: 10px; border: 1px solid #ffd400; background: #ffd400; color: #111; font-weight: 700; font-size: 14px;"><?php echo $i; ?></span>
                        <?php elseif ($i <= 3 || $i > $toplam_sayfa - 3 || abs($i - $sayfa) <= 1): ?>
                            <a href="?q=<?php echo urlencode($query); ?>&sayfa=<?php echo $i; ?>" style="display: flex; align-items: center; justify-content: center; min-width: 40px; height: 40px; padding: 0 12px; border-radius: 10px; border: 1px solid #eee; color: #666; text-decoration: none; transition: all 0.3s; font-size: 14px;">
                                <?php echo $i; ?>
                            </a>
                        <?php elseif ($i == 4 && $sayfa > 5): ?>
                            <span style="display: flex; align-items: center; justify-content: center; min-width: 40px; height: 40px; padding: 0 12px; border-radius: 10px; border: none; color: #999; font-size: 14px;">…</span>
                        <?php elseif ($i == $toplam_sayfa - 3 && $sayfa < $toplam_sayfa - 4): ?>
                            <span style="display: flex; align-items: center; justify-content: center; min-width: 40px; height: 40px; padding: 0 12px; border-radius: 10px; border: none; color: #999; font-size: 14px;">…</span>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($sayfa < $toplam_sayfa): ?>
                        <a href="?q=<?php echo urlencode($query); ?>&sayfa=<?php echo $sayfa + 1; ?>" style="display: flex; align-items: center; justify-content: center; min-width: 40px; height: 40px; padding: 0 12px; border-radius: 10px; border: 1px solid #eee; color: #666; text-decoration: none; transition: all 0.3s; font-size: 14px;">
                            <i class="fa-solid fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        <?php else: ?>

            <!-- Sonuç Yok -->
            <div style="text-align: center; padding: 60px 20px; background: #fff; border-radius: 20px; box-shadow: 0 5px 25px rgba(0,0,0,0.05);">
                <div style="font-size: 72px; color: #ddd; margin-bottom: 16px;">
                    <i class="fa-regular fa-face-frown"></i>
                </div>
                <h2 style="font-size: 28px; font-weight: 800; margin-bottom: 8px;">Sonuç Bulunamadı</h2>
                <p style="color: #999; font-size: 16px; margin-bottom: 24px;">
                    <?php if (!empty($query)): ?>
                        "<?php echo $query; ?>" için sonuç bulunamadı. Lütfen farklı anahtar kelimeler deneyin.
                    <?php else: ?>
                        Henüz hiç ürün bulunmuyor.
                    <?php endif; ?>
                </p>
                <?php if (!empty($query)): ?>
                    <a href="<?php echo SITE_URL; ?>public/ara.php" style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; background: #ffd400; color: #111; border-radius: 12px; font-weight: 600; font-size: 14px; text-decoration: none; transition: all 0.3s;">
                        <i class="fa-solid fa-arrow-left"></i> Tüm Ürünlere Göz At
                    </a>
                <?php endif; ?>
            </div>

        <?php endif; ?>

    </div>
</section>

<!-- ============================================ -->
<!-- JAVASCRIPT -->
<!-- ============================================ -->
<script>
// ============================================
// FAVORİ EKLE
// ============================================
function toggleWishlist(productId, element) {
    const icon = element;
    icon.classList.toggle('fa-regular');
    icon.classList.toggle('fa-solid');
    
    if (icon.classList.contains('fa-solid')) {
        icon.style.color = '#ff3366';
    } else {
        icon.style.color = '#ccc';
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

<?php
include '../includes/footer.php';
?>