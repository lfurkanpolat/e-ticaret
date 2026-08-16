<?php
// =====================================================
// FAVORİLER - public/favoriler.php
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
// FAVORİDEN KALDIR
// =====================================================
if (isset($_GET['kaldir']) && is_numeric($_GET['kaldir'])) {
    $urun_id = (int)$_GET['kaldir'];
    
    try {
        $stmt = $db->prepare("DELETE FROM favoriler WHERE kullanici_id = ? AND urun_id = ?");
        $stmt->execute([$user_id, $urun_id]);
        $success = 'Ürün favorilerden kaldırıldı!';
        header("Location: " . SITE_URL . "public/favoriler.php?success=1");
        exit();
    } catch (PDOException $e) {
        $error = 'Silme hatası: ' . $e->getMessage();
    }
}

// =====================================================
// FAVORİLERİ TEMİZLE
// =====================================================
if (isset($_GET['temizle'])) {
    try {
        $stmt = $db->prepare("DELETE FROM favoriler WHERE kullanici_id = ?");
        $stmt->execute([$user_id]);
        $success = 'Tüm favoriler temizlendi!';
        header("Location: " . SITE_URL . "public/favoriler.php?success=1");
        exit();
    } catch (PDOException $e) {
        $error = 'Temizleme hatası: ' . $e->getMessage();
    }
}

// =====================================================
// FAVORİ LİSTESİNİ ÇEK
// =====================================================
try {
    $stmt = $db->prepare("
        SELECT f.*, u.id as urun_id, u.ad, u.slug, u.fiyat, u.indirimli_fiyat, u.resim_url, 
               u.stok, u.kritik_stok,
               (SELECT AVG(puan) FROM urun_yorumlari WHERE urun_id = u.id AND durum = 'onaylandi') as puan_ortalamasi,
               (SELECT COUNT(*) FROM urun_yorumlari WHERE urun_id = u.id AND durum = 'onaylandi') as puan_sayisi,
               k.ad as kategori_adi,
               m.ad as marka_adi
        FROM favoriler f
        LEFT JOIN urunler u ON f.urun_id = u.id
        LEFT JOIN kategoriler k ON u.kategori_id = k.id
        LEFT JOIN markalar m ON u.marka_id = m.id
        WHERE f.kullanici_id = ? AND u.durum = 'aktif'
        ORDER BY f.ekleme_tarihi DESC
    ");
    $stmt->execute([$user_id]);
    $favoriler = $stmt->fetchAll();
} catch (PDOException $e) {
    $favoriler = [];
    $error = 'Veritabanı hatası: ' . $e->getMessage();
}

// Başarı mesajı
if (isset($_GET['success'])) {
    $success = 'İşlem başarıyla tamamlandı!';
}

// =====================================================
// HEADER'I DAHİL ET
// =====================================================
include '../includes/header.php';
?>

<!-- ============================================ -->
<!-- FAVORİLER SAYFASI -->
<!-- ============================================ -->
<section class="favorites-page">
    <div class="container">
        
        <div class="page-header">
            <div class="page-header-left">
                <h1><i class="fa-regular fa-heart"></i> Favorilerim</h1>
                <span class="favorite-count"><?php echo count($favoriler); ?> ürün</span>
            </div>
            <?php if (!empty($favoriler)): ?>
                <div class="page-header-right">
                    <a href="<?php echo SITE_URL; ?>public/favoriler.php?temizle=1" class="btn btn-outline" onclick="return confirm('Tüm favorilerinizi temizlemek istediğinize emin misiniz?')">
                        <i class="fa-regular fa-trash-can"></i> Tümünü Temizle
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Hata ve Başarı Mesajları -->
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($favoriler)): ?>
        
            <div class="favorites-grid">
                <?php foreach ($favoriler as $urun): ?>
                    <div class="favorite-card">
                        
                        <!-- Resim -->
                        <div class="favorite-image">
                            <a href="<?php echo SITE_URL; ?>public/urun-detay.php?slug=<?php echo $urun['slug']; ?>">
                                <?php if ($urun['resim_url']): ?>
                                    <img src="<?php echo SITE_URL; ?>uploads/<?php echo $urun['resim_url']; ?>" alt="<?php echo $urun['ad']; ?>">
                                <?php else: ?>
                                    <img src="https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=300" alt="<?php echo $urun['ad']; ?>">
                                <?php endif; ?>
                            </a>
                            
                            <!-- İndirim Etiketi -->
                            <?php if ($urun['indirimli_fiyat'] && $urun['indirimli_fiyat'] < $urun['fiyat']): ?>
                                <span class="discount-badge">
                                    -<?php echo round((($urun['fiyat'] - $urun['indirimli_fiyat']) / $urun['fiyat']) * 100); ?>%
                                </span>
                            <?php endif; ?>
                            
                            <!-- Stok Durumu -->
                            <?php if ($urun['stok'] <= 0): ?>
                                <span class="stock-badge out-of-stock">Stokta Yok</span>
                            <?php elseif ($urun['stok'] <= $urun['kritik_stok']): ?>
                                <span class="stock-badge low-stock">Son <?php echo $urun['stok']; ?> ürün</span>
                            <?php endif; ?>
                            
                            <!-- Favori Kaldır Butonu -->
                            <button class="remove-favorite" onclick="removeFavorite(<?php echo $urun['urun_id']; ?>, this)" title="Favorilerden Kaldır">
                                <i class="fa-solid fa-heart"></i>
                            </button>
                        </div>
                        
                        <!-- Bilgiler -->
                        <div class="favorite-info">
                            <?php if ($urun['marka_adi']): ?>
                                <span class="favorite-brand"><?php echo $urun['marka_adi']; ?></span>
                            <?php endif; ?>
                            
                            <a href="<?php echo SITE_URL; ?>public/urun-detay.php?slug=<?php echo $urun['slug']; ?>" class="favorite-name">
                                <?php echo $urun['ad']; ?>
                            </a>
                            
                            <?php if ($urun['kategori_adi']): ?>
                                <span class="favorite-category">
                                    <i class="fa-regular fa-folder"></i>
                                    <?php echo $urun['kategori_adi']; ?>
                                </span>
                            <?php endif; ?>
                            
                            <!-- Rating -->
                            <div class="favorite-rating">
                                <?php 
                                $puan = round($urun['puan_ortalamasi'] ?? 0);
                                for ($i = 1; $i <= 5; $i++): 
                                ?>
                                    <?php if ($i <= $puan): ?>
                                        <i class="fa-solid fa-star"></i>
                                    <?php else: ?>
                                        <i class="fa-regular fa-star"></i>
                                    <?php endif; ?>
                                <?php endfor; ?>
                                <span>(<?php echo $urun['puan_sayisi'] ?? 0; ?>)</span>
                            </div>
                            
                            <!-- Fiyat -->
                            <div class="favorite-price">
                                <?php if ($urun['indirimli_fiyat'] && $urun['indirimli_fiyat'] < $urun['fiyat']): ?>
                                    <span class="current-price"><?php echo number_format($urun['indirimli_fiyat'], 0, ',', '.'); ?> TL</span>
                                    <span class="old-price"><?php echo number_format($urun['fiyat'], 0, ',', '.'); ?> TL</span>
                                <?php else: ?>
                                    <span class="current-price"><?php echo number_format($urun['fiyat'], 0, ',', '.'); ?> TL</span>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Butonlar -->
                            <div class="favorite-actions">
                                <?php if ($urun['stok'] > 0): ?>
                                    <button class="btn btn-primary btn-sm add-to-cart-btn" 
                                            data-product-id="<?php echo (int)$urun['urun_id']; ?>" 
                                            onclick="addToCart(<?php echo (int)$urun['urun_id']; ?>, this)">
                                        <i class="fa-solid fa-cart-plus"></i> Sepete Ekle
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-disabled" disabled>
                                        <i class="fa-solid fa-times"></i> Stokta Yok
                                    </button>
                                <?php endif; ?>
                                <a href="<?php echo SITE_URL; ?>public/favoriler.php?kaldir=<?php echo $urun['urun_id']; ?>" class="btn btn-outline btn-sm remove-favorite-btn" onclick="return confirm('Bu ürünü favorilerden kaldırmak istediğinize emin misiniz?')">
                                    <i class="fa-regular fa-heart"></i>
                                </a>
                            </div>
                            
                            <!-- Eklenme Tarihi -->
                            <span class="favorite-date">
                                <i class="fa-regular fa-clock"></i>
                                <?php echo date('d.m.Y', strtotime($urun['ekleme_tarihi'])); ?> tarihinde eklendi
                            </span>
                        </div>
                        
                    </div>
                <?php endforeach; ?>
            </div>
            
        <?php else: ?>
            
            <!-- Boş Favori -->
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fa-regular fa-heart"></i>
                </div>
                <h2>Favorileriniz Boş</h2>
                <p>Beğendiğiniz ürünleri favorilere ekleyerek burada toplayın.</p>
                <a href="<?php echo SITE_URL; ?>public/urunler.php" class="btn btn-primary">
                    <i class="fa-solid fa-arrow-left"></i> Ürünleri İncele
                </a>
            </div>
            
        <?php endif; ?>
        
        <!-- Önerilen Ürünler -->
        <?php if (empty($favoriler)): ?>
            <div class="suggested-products">
                <h3>İlginizi Çekebilir</h3>
                <div class="suggested-grid">
                    <?php
                    try {
                        $stmt = $db->query("SELECT * FROM urunler WHERE durum = 'aktif' ORDER BY RAND() LIMIT 4");
                        $onerilen = $stmt->fetchAll();
                        foreach ($onerilen as $urun):
                    ?>
                        <div class="suggested-card">
                            <a href="<?php echo SITE_URL; ?>public/urun-detay.php?slug=<?php echo $urun['slug']; ?>">
                                <?php if ($urun['resim_url']): ?>
                                    <img src="<?php echo SITE_URL; ?>uploads/<?php echo $urun['resim_url']; ?>" alt="<?php echo $urun['ad']; ?>">
                                <?php else: ?>
                                    <img src="https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=300" alt="<?php echo $urun['ad']; ?>">
                                <?php endif; ?>
                            </a>
                            <a href="<?php echo SITE_URL; ?>public/urun-detay.php?slug=<?php echo $urun['slug']; ?>" class="suggested-name">
                                <?php echo $urun['ad']; ?>
                            </a>
                            <span class="suggested-price"><?php echo number_format($urun['fiyat'], 0, ',', '.'); ?> TL</span>
                            <button class="btn btn-sm btn-primary add-to-cart-btn" onclick="addToCart(<?php echo (int)$urun['id']; ?>, this)">Sepete Ekle</button>
                        </div>
                    <?php 
                        endforeach;
                    } catch (PDOException $e) {}
                    ?>
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
// FAVORİDEN KALDIR (AJAX)
// ============================================
function removeFavorite(productId, element) {
    if (!confirm('Bu ürünü favorilerden kaldırmak istediğinize emin misiniz?')) {
        return;
    }
    
    const card = element.closest('.favorite-card');
    const originalText = element.innerHTML;
    
    element.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
    element.disabled = true;
    
    fetch('<?php echo SITE_URL; ?>public/favori-ekle.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'urun_id=' + productId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            card.style.transition = 'all 0.3s';
            card.style.opacity = '0';
            card.style.transform = 'scale(0.8)';
            setTimeout(function() {
                card.remove();
                const count = document.querySelector('.favorite-count');
                if (count) {
                    const current = parseInt(count.textContent);
                    count.textContent = current - 1;
                }
                if (document.querySelectorAll('.favorite-card').length === 0) {
                    location.reload();
                }
            }, 300);
        } else {
            alert('Hata: ' + data.message);
            element.innerHTML = originalText;
            element.disabled = false;
        }
    })
    .catch(error => {
        console.error('Hata:', error);
        alert('Bir hata oluştu. Lütfen tekrar deneyin.');
        element.innerHTML = originalText;
        element.disabled = false;
    });
}

// ============================================
// SEPETE EKLE (Favoriler sayfası)
// ============================================
function addToCart(productId, btn) {
    console.log('🔍 Sepete ekle butonuna tıklandı. Ürün ID:', productId);
    
    if (!productId || productId <= 0) {
        alert('Geçersiz ürün ID!');
        console.error('❌ Geçersiz ürün ID:', productId);
        return;
    }
    
    const originalText = btn.innerHTML;
    
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Ekleniyor...';
    btn.disabled = true;
    
    const url = '<?php echo SITE_URL; ?>public/sepet-ekle.php';
    const data = 'urun_id=' + productId + '&adet=1';
    
    console.log('📡 İstek gönderiliyor:', url);
    console.log('📡 Veri:', data);
    
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: data
    })
    .then(response => {
        console.log('📨 Response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('📦 Gelen veri:', data);
        
        if (data.success) {
            btn.innerHTML = '<i class="fa-solid fa-check"></i> Eklendi';
            btn.style.background = '#2ecc71';
            btn.style.color = '#fff';
            
            const badge = document.querySelector('.badge');
            if (badge) {
                const current = parseInt(badge.textContent) || 0;
                badge.textContent = data.cart_count || current + 1;
            }
            
            setTimeout(function() {
                btn.innerHTML = originalText;
                btn.style.background = '';
                btn.style.color = '';
                btn.disabled = false;
            }, 2000);
        } else {
            console.error('❌ Sunucu hatası:', data.message);
            alert('Hata: ' + data.message);
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    })
    .catch(error => {
        console.error('❌ Fetch hatası:', error);
        alert('Bağlantı hatası! Lütfen tekrar deneyin.');
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}
</script>

<!-- ============================================ -->
<!-- CSS -->
<!-- ============================================ -->
<style>
.favorites-page {
    padding: 40px 0 60px;
    background: #f8f9fb;
    min-height: calc(100vh - 200px);
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 30px;
}

.page-header-left {
    display: flex;
    align-items: center;
    gap: 16px;
}

.page-header-left h1 {
    font-size: 28px;
    font-weight: 800;
}

.page-header-left h1 i {
    color: #ff3366;
}

.favorite-count {
    color: #999;
    font-size: 14px;
}

.favorites-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 24px;
}

.favorite-card {
    background: #fff;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 5px 25px rgba(0,0,0,0.05);
    transition: all 0.3s;
}

.favorite-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 40px rgba(0,0,0,0.08);
}

.favorite-image {
    position: relative;
    height: 220px;
    background: #f8f9fb;
    overflow: hidden;
}

.favorite-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: all 0.3s;
}

.favorite-card:hover .favorite-image img {
    transform: scale(1.03);
}

.discount-badge {
    position: absolute;
    top: 12px;
    left: 12px;
    background: #ff3366;
    color: #fff;
    padding: 4px 12px;
    border-radius: 50px;
    font-size: 12px;
    font-weight: 700;
}

.stock-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    padding: 4px 12px;
    border-radius: 50px;
    font-size: 11px;
    font-weight: 600;
}

.stock-badge.out-of-stock {
    background: #e74c3c;
    color: #fff;
}

.stock-badge.low-stock {
    background: #f39c12;
    color: #fff;
}

.remove-favorite {
    position: absolute;
    bottom: 12px;
    right: 12px;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    border: none;
    background: #ff3366;
    color: #fff;
    font-size: 16px;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transform: translateY(10px);
}

.favorite-card:hover .remove-favorite {
    opacity: 1;
    transform: translateY(0);
}

.remove-favorite:hover {
    transform: scale(1.1);
    box-shadow: 0 4px 15px rgba(255, 51, 102, 0.4);
}

.favorite-info {
    padding: 16px 18px 18px;
}

.favorite-brand {
    font-size: 12px;
    color: #999;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
    display: block;
    margin-bottom: 2px;
}

.favorite-name {
    font-size: 15px;
    font-weight: 600;
    color: #222;
    text-decoration: none;
    display: block;
    margin-bottom: 4px;
    min-height: 42px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.favorite-name:hover {
    color: #ffd400;
}

.favorite-category {
    font-size: 13px;
    color: #999;
    display: block;
    margin-bottom: 8px;
}

.favorite-category i {
    margin-right: 4px;
}

.favorite-rating {
    color: #ffb400;
    font-size: 14px;
    margin-bottom: 8px;
}

.favorite-rating span {
    color: #999;
    font-size: 13px;
    margin-left: 4px;
}

.favorite-price {
    margin-bottom: 12px;
}

.favorite-price .current-price {
    font-size: 20px;
    font-weight: 800;
    color: #222;
}

.favorite-price .old-price {
    font-size: 15px;
    color: #999;
    text-decoration: line-through;
    margin-left: 8px;
}

.favorite-actions {
    display: flex;
    gap: 8px;
    margin-top: 4px;
}

.favorite-actions .btn {
    flex: 1;
    justify-content: center;
    padding: 10px 16px;
    font-size: 14px;
    border-radius: 10px;
    font-weight: 600;
    font-family: 'Inter', sans-serif;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    border: none;
}

.favorite-actions .btn-sm {
    padding: 8px 14px;
    font-size: 13px;
}

.add-to-cart-btn {
    background: #ffd400 !important;
    color: #111 !important;
    border: none !important;
}

.add-to-cart-btn:hover {
    background: #f5c800 !important;
    transform: translateY(-2px) !important;
    box-shadow: 0 4px 15px rgba(255, 212, 0, 0.3) !important;
}

.add-to-cart-btn:disabled {
    opacity: 0.6 !important;
    cursor: not-allowed !important;
    transform: none !important;
}

.remove-favorite-btn {
    background: transparent !important;
    border: 2px solid #e0e0e0 !important;
    color: #666 !important;
}

.remove-favorite-btn:hover {
    border-color: #ff3366 !important;
    color: #ff3366 !important;
    background: rgba(255, 51, 102, 0.05) !important;
    transform: translateY(-2px) !important;
}

.btn-disabled {
    background: #e0e0e0 !important;
    color: #999 !important;
    cursor: not-allowed !important;
    border: none !important;
}

.favorite-date {
    display: block;
    font-size: 12px;
    color: #ccc;
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid #f0f0f0;
}

.favorite-date i {
    margin-right: 4px;
}

.suggested-products {
    margin-top: 60px;
    padding-top: 40px;
    border-top: 2px solid #eee;
}

.suggested-products h3 {
    font-size: 22px;
    font-weight: 700;
    margin-bottom: 24px;
}

.suggested-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
}

.suggested-card {
    background: #fff;
    border-radius: 16px;
    padding: 16px;
    text-align: center;
    box-shadow: 0 5px 25px rgba(0,0,0,0.05);
    transition: all 0.3s;
}

.suggested-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 40px rgba(0,0,0,0.08);
}

.suggested-card img {
    width: 100%;
    height: 160px;
    object-fit: contain;
    border-radius: 12px;
    background: #f8f9fb;
}

.suggested-name {
    display: block;
    font-weight: 600;
    font-size: 14px;
    color: #222;
    text-decoration: none;
    margin: 10px 0 4px;
    min-height: 40px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.suggested-name:hover {
    color: #ffd400;
}

.suggested-price {
    font-weight: 700;
    font-size: 16px;
    color: #222;
    display: block;
    margin-bottom: 10px;
}

.suggested-card .btn {
    width: 100%;
}

.alert {
    padding: 14px 18px;
    border-radius: 12px;
    font-weight: 500;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 16px;
}

.alert-error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

.alert-success {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #a7f3d0;
}

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

.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 24px;
    border: none;
    border-radius: 12px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s;
    font-family: 'Inter', sans-serif;
    text-decoration: none;
}

.btn-primary {
    background: #ffd400;
    color: #111;
}

.btn-primary:hover {
    background: #f5c800;
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(255, 212, 0, 0.3);
}

.btn-outline {
    background: transparent;
    border: 2px solid #e0e0e0;
    color: #666;
}

.btn-outline:hover {
    border-color: #ffd400;
    color: #111;
}

.btn-sm {
    padding: 8px 16px;
    font-size: 13px;
    border-radius: 8px;
}

@media (max-width: 992px) {
    .suggested-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .favorites-page {
        padding: 20px 0 40px;
    }
    
    .page-header-left h1 {
        font-size: 24px;
    }
    
    .favorites-grid {
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    }
    
    .favorite-image {
        height: 180px;
    }
    
    .remove-favorite {
        opacity: 1;
        transform: translateY(0);
    }
    
    .suggested-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 480px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .favorites-grid {
        grid-template-columns: 1fr;
    }
    
    .favorite-image {
        height: 200px;
    }
    
    .favorite-actions {
        flex-direction: column;
    }
    
    .favorite-actions .btn {
        width: 100%;
    }
    
    .suggested-grid {
        grid-template-columns: 1fr 1fr;
    }
    
    .suggested-card img {
        height: 120px;
    }
}
</style>

<?php
include '../includes/footer.php';
?>