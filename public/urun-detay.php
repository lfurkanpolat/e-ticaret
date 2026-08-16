<?php
// =====================================================
// ÜRÜN DETAY - public/urun-detay.php
// =====================================================

require_once '../includes/config.php';

$slug = isset($_GET['slug']) ? clean($_GET['slug']) : '';

if (empty($slug)) {
    header("Location: " . SITE_URL . "public/index.php");
    exit();
}

// =====================================================
// ÜRÜN BİLGİLERİNİ ÇEK
// =====================================================
try {
    $stmt = $db->prepare("
        SELECT u.*, k.ad as kategori_adi, k.slug as kategori_slug, m.ad as marka_adi
        FROM urunler u
        LEFT JOIN kategoriler k ON u.kategori_id = k.id
        LEFT JOIN markalar m ON u.marka_id = m.id
        WHERE u.slug = ? AND u.durum = 'aktif'
    ");
    $stmt->execute([$slug]);
    $urun = $stmt->fetch();
} catch (PDOException $e) {
    $urun = null;
}

// Ürün bulunamadıysa ana sayfaya yönlendir
if (!$urun) {
    header("Location: " . SITE_URL . "public/index.php");
    exit();
}

// Görüntüleme sayısını artır
try {
    $stmt = $db->prepare("UPDATE urunler SET gosterim_sayisi = gosterim_sayisi + 1 WHERE id = ?");
    $stmt->execute([$urun['id']]);
} catch (PDOException $e) {}

// =====================================================
// ÜRÜN RESİMLERİNİ ÇEK
// =====================================================
try {
    $stmt = $db->prepare("
        SELECT * FROM urun_resimleri 
        WHERE urun_id = ? 
        ORDER BY ana_resim DESC, sira ASC
    ");
    $stmt->execute([$urun['id']]);
    $resimler = $stmt->fetchAll();
} catch (PDOException $e) {
    $resimler = [];
}


// =====================================================
// FAVORİ DURUMUNU KONTROL ET
// =====================================================
$is_favorite = false;
if (isLoggedIn()) {
    try {
        $stmt = $db->prepare("SELECT id FROM favoriler WHERE kullanici_id = ? AND urun_id = ?");
        $stmt->execute([$_SESSION['user_id'], $urun['id']]);
        $is_favorite = $stmt->fetch() ? true : false;
    } catch (PDOException $e) {
        $is_favorite = false;
    }
}
// =====================================================
// ÜRÜN VARYANTLARINI ÇEK
// =====================================================
try {
    $stmt = $db->prepare("
        SELECT * FROM urun_varyantlari 
        WHERE urun_id = ? 
        ORDER BY id ASC
    ");
    $stmt->execute([$urun['id']]);
    $varyantlar = $stmt->fetchAll();
} catch (PDOException $e) {
    $varyantlar = [];
}

// =====================================================
// BENZER ÜRÜNLER (Aynı kategoriden)
// =====================================================
try {
    $stmt = $db->prepare("
        SELECT * FROM urunler 
        WHERE kategori_id = ? AND id != ? AND durum = 'aktif'
        ORDER BY RAND()
        LIMIT 4
    ");
    $stmt->execute([$urun['kategori_id'], $urun['id']]);
    $benzer_urunler = $stmt->fetchAll();
} catch (PDOException $e) {
    $benzer_urunler = [];
}

// =====================================================
// ÜRÜN YORUMLARI
// =====================================================
try {
    $stmt = $db->prepare("
        SELECT y.*, u.ad, u.soyad 
        FROM urun_yorumlari y
        LEFT JOIN kullanicilar u ON y.kullanici_id = u.id
        WHERE y.urun_id = ? AND y.durum = 'onaylandi'
        ORDER BY y.olusturma_tarihi DESC
        LIMIT 10
    ");
    $stmt->execute([$urun['id']]);
    $yorumlar = $stmt->fetchAll();
} catch (PDOException $e) {
    $yorumlar = [];
}

// =====================================================
// YORUM PUAN ORTALAMASI
// =====================================================
$puan_ortalamasi = $urun['puan_ortalamasi'] ?? 0;
$puan_sayisi = $urun['puan_sayisi'] ?? 0;

// =====================================================
// HEADER'I DAHİL ET
// =====================================================
$page_title = $urun['ad'] . ' - ' . SITE_NAME;
include '../includes/header.php';
?>

<!-- ============================================ -->
<!-- ÜRÜN DETAY SAYFASI -->
<!-- ============================================ -->
<section class="product-detail-page">
    <div class="container">
        
        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="<?php echo SITE_URL; ?>public/index.php">Ana Sayfa</a>
            <span class="separator">/</span>
            <a href="<?php echo SITE_URL; ?>public/kategori.php?slug=<?php echo $urun['kategori_slug']; ?>"><?php echo $urun['kategori_adi']; ?></a>
            <span class="separator">/</span>
            <span class="current"><?php echo $urun['ad']; ?></span>
        </div>
        
        <!-- Ürün Bilgileri -->
        <div class="product-detail">
            
            <!-- Sol: Resimler -->
            <div class="product-images">
                <div class="main-image">
                    <?php 
                    $ana_resim_url = '';
                    if (!empty($resimler) && !empty($resimler[0]['resim_url'])) {
                        $ana_resim_url = SITE_URL . 'uploads/' . $resimler[0]['resim_url'];
                    } elseif ($urun['resim_url']) {
                        $ana_resim_url = SITE_URL . 'uploads/' . $urun['resim_url'];
                    } else {
                        $ana_resim_url = 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=600';
                    }
                    ?>
                    <img src="<?php echo $ana_resim_url; ?>" alt="<?php echo $urun['ad']; ?>" id="mainImage">
                </div>
                
                <?php if (count($resimler) > 1): ?>
                    <div class="thumbnail-list">
                        <?php foreach ($resimler as $resim): ?>
                            <div class="thumbnail-item <?php echo $resim['ana_resim'] ? 'active' : ''; ?>" onclick="changeImage(this, '<?php echo SITE_URL . 'uploads/' . $resim['resim_url']; ?>')">
                                <img src="<?php echo SITE_URL . 'uploads/' . $resim['resim_url']; ?>" alt="<?php echo $urun['ad']; ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Sağ: Ürün Bilgileri -->
            <div class="product-info">
                
                <!-- Marka -->
                <?php if ($urun['marka_adi']): ?>
                    <span class="product-brand"><?php echo $urun['marka_adi']; ?></span>
                <?php endif; ?>
                
                <!-- Başlık -->
                <h1><?php echo $urun['ad']; ?></h1>
                
                <!-- Rating -->
                <div class="product-rating">
                    <div class="stars">
                        <?php 
                        $puan = round($puan_ortalamasi);
                        for ($i = 1; $i <= 5; $i++): 
                        ?>
                            <?php if ($i <= $puan): ?>
                                <i class="fa-solid fa-star"></i>
                            <?php else: ?>
                                <i class="fa-regular fa-star"></i>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </div>
                    <span class="rating-count">(<?php echo $puan_sayisi; ?> değerlendirme)</span>
                </div>
                
                <!-- Fiyat -->
                <div class="product-price">
                    <?php if ($urun['indirimli_fiyat'] && $urun['indirimli_fiyat'] < $urun['fiyat']): ?>
                        <span class="current-price" id="currentPrice"><?php echo number_format($urun['indirimli_fiyat'], 0, ',', '.'); ?> TL</span>
                        <span class="old-price" id="oldPrice"><?php echo number_format($urun['fiyat'], 0, ',', '.'); ?> TL</span>
                        <span class="discount-badge" id="discountBadge">
                            -<?php echo round((($urun['fiyat'] - $urun['indirimli_fiyat']) / $urun['fiyat']) * 100); ?>%
                        </span>
                    <?php else: ?>
                        <span class="current-price" id="currentPrice"><?php echo number_format($urun['fiyat'], 0, ',', '.'); ?> TL</span>
                    <?php endif; ?>
                </div>
                
                <!-- Ürün Kodu -->
                <div class="product-code">
                    <span>Ürün Kodu: <strong><?php echo $urun['urun_kodu']; ?></strong></span>
                </div>
                
                <!-- Stok Durumu -->
                <div class="stock-status" id="stockStatus">
                    <?php if ($urun['stok'] > 0): ?>
                        <span class="in-stock"><i class="fa-solid fa-check-circle"></i> Stokta Var</span>
                        <?php if ($urun['stok'] <= $urun['kritik_stok']): ?>
                            <span class="low-stock"> (Son <?php echo $urun['stok']; ?> ürün)</span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="out-of-stock"><i class="fa-solid fa-times-circle"></i> Stokta Yok</span>
                    <?php endif; ?>
                </div>
                
                <!-- Açıklama (Kısa) -->
                <div class="product-description">
                    <p>
                        <?php 
                        $aciklama = $urun['aciklama'] ?? 'Ürün açıklaması bulunmuyor.';
                        if (strlen($aciklama) > 150) {
                            echo nl2br(substr($aciklama, 0, 150)) . '...';
                        } else {
                            echo nl2br($aciklama);
                        }
                        ?>
                    </p>
                </div>
                
                <!-- Varyantlar -->
                <?php if (!empty($varyantlar)): ?>
                    <div class="product-variants">
                        <h4>Varyantlar</h4>
                        <div class="variant-grid">
                            <?php foreach ($varyantlar as $varyant): 
                                // Varyant resim URL'sini hazırla
                                $varyant_resim_url = '';
if (!empty($varyant['resim_url'])) {
    // Eğer resim_url 'urunler/' ile başlamıyorsa ekle
    if (strpos($varyant['resim_url'], 'urunler/') === false && strpos($varyant['resim_url'], 'uploads/') === false) {
        $varyant_resim_url = SITE_URL . 'uploads/urunler/' . $varyant['resim_url'];
    } elseif (strpos($varyant['resim_url'], 'uploads/') === 0) {
        $varyant_resim_url = SITE_URL . $varyant['resim_url'];
    } else {
        $varyant_resim_url = SITE_URL . 'uploads/' . $varyant['resim_url'];
    }
}
                            ?>
                                <div class="variant-item <?php echo $varyant['stok'] > 0 ? 'in-stock' : 'out-of-stock'; ?>" 
                                     data-id="<?php echo $varyant['id']; ?>"
                                     data-resim="<?php echo $varyant_resim_url; ?>"
                                     data-fiyat="<?php echo $varyant['fiyat']; ?>"
                                     data-indirimli_fiyat="<?php echo $varyant['indirimli_fiyat']; ?>"
                                     data-stok="<?php echo $varyant['stok']; ?>"
                                     onclick="selectVariant(<?php echo $varyant['id']; ?>, this)">
                                    
                                    <?php if ($varyant['renk'] && $varyant['beden']): ?>
                                        <span class="variant-label"><?php echo $varyant['renk']; ?> / <?php echo $varyant['beden']; ?></span>
                                    <?php elseif ($varyant['renk']): ?>
                                        <span class="variant-label"><?php echo $varyant['renk']; ?></span>
                                    <?php elseif ($varyant['beden']): ?>
                                        <span class="variant-label"><?php echo $varyant['beden']; ?></span>
                                    <?php else: ?>
                                        <span class="variant-label"><?php echo $varyant['varyant_kodu']; ?></span>
                                    <?php endif; ?>
                                    
                                    <?php if ($varyant['stok'] > 0): ?>
                                        <span class="variant-stock">✓</span>
                                    <?php else: ?>
                                        <span class="variant-stock" style="color:#ff3366;">✗</span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Varyant ID için gizli input -->
                <input type="hidden" id="selectedVariantId" value="">
                
                <!-- Adet Seçimi -->
                <div class="product-quantity">
                    <label>Adet</label>
                    <div class="quantity-selector">
                        <button class="qty-btn" onclick="updateQuantity(-1)">-</button>
                        <input type="number" id="quantity" value="1" min="1" max="<?php echo $urun['stok'] > 0 ? $urun['stok'] : 1; ?>">
                        <button class="qty-btn" onclick="updateQuantity(1)">+</button>
                    </div>
                </div>
                
                <!-- Sepete Ekle Butonu -->
                <div class="product-actions">
                    <?php if ($urun['stok'] > 0): ?>
                        <button class="btn btn-primary btn-lg add-to-cart" id="addToCartBtn" onclick="addToCartDetail(<?php echo $urun['id']; ?>)">
                            <i class="fa-solid fa-cart-plus"></i> Sepete Ekle
                        </button>
                    <?php else: ?>
                        <button class="btn btn-secondary btn-lg" disabled style="background:#ccc;cursor:not-allowed;">
                            <i class="fa-solid fa-times"></i> Stokta Yok
                        </button>
                    <?php endif; ?>
                 <button class="btn btn-outline wishlist-btn" onclick="toggleWishlistDetail(<?php echo $urun['id']; ?>)">
                    <i class="<?php echo $is_favorite ? 'fa-solid' : 'fa-regular'; ?> fa-heart"></i>
                </button>
                </div>
                
                <!-- Güvenlik Bilgileri -->
                <div class="product-security">
                    <div class="security-item">
                        <i class="fa-solid fa-truck-fast"></i>
                        <span>Ücretsiz Kargo</span>
                    </div>
                    <div class="security-item">
                        <i class="fa-solid fa-shield-halved"></i>
                        <span>Güvenli Alışveriş</span>
                    </div>
                    <div class="security-item">
                        <i class="fa-solid fa-arrow-rotate-left"></i>
                        <span>14 Gün İade</span>
                    </div>
                </div>
                
            </div>
            
        </div>
        
        <!-- ============================================ -->
        <!-- ÜRÜN DETAY TABS -->
        <!-- ============================================ -->
        <div class="product-tabs">
            <div class="tab-header">
                <button class="tab-btn active" data-tab="description">Ürün Açıklaması</button>
                <button class="tab-btn" data-tab="specs">Özellikler</button>
                <button class="tab-btn" data-tab="reviews">Yorumlar (<?php echo $puan_sayisi; ?>)</button>
            </div>
            
            <div class="tab-content">
                <!-- Açıklama -->
                <div class="tab-pane active" id="description">
                    <div class="tab-inner">
                        <?php if ($urun['aciklama']): ?>
                            <p><?php echo nl2br($urun['aciklama']); ?></p>
                        <?php else: ?>
                            <p>Bu ürün için henüz açıklama eklenmemiş.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Özellikler -->
                <div class="tab-pane" id="specs">
                    <div class="tab-inner">
                        <table class="specs-table">
                            <tr>
                                <td>Ürün Kodu</td>
                                <td><strong><?php echo $urun['urun_kodu']; ?></strong></td>
                            </tr>
                            <tr>
                                <td>Kategori</td>
                                <td><strong><?php echo $urun['kategori_adi']; ?></strong></td>
                            </tr>
                            <?php if ($urun['marka_adi']): ?>
                                <tr>
                                    <td>Marka</td>
                                    <td><strong><?php echo $urun['marka_adi']; ?></strong></td>
                                </tr>
                            <?php endif; ?>
                            <tr>
                                <td>Stok Durumu</td>
                                <td>
                                    <?php if ($urun['stok'] > 0): ?>
                                        <span style="color:#2ecc71;">Stokta Var (<?php echo $urun['stok']; ?> adet)</span>
                                    <?php else: ?>
                                        <span style="color:#ff3366;">Stokta Yok</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td>Fiyat</td>
                                <td>
                                    <?php if ($urun['indirimli_fiyat'] && $urun['indirimli_fiyat'] < $urun['fiyat']): ?>
                                        <span style="text-decoration:line-through;color:#999;"><?php echo number_format($urun['fiyat'], 0, ',', '.'); ?> TL</span>
                                        <strong style="color:#ff3366;"><?php echo number_format($urun['indirimli_fiyat'], 0, ',', '.'); ?> TL</strong>
                                    <?php else: ?>
                                        <strong><?php echo number_format($urun['fiyat'], 0, ',', '.'); ?> TL</strong>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td>Görüntülenme</td>
                                <td><?php echo number_format($urun['gosterim_sayisi'] ?? 0); ?> kez</td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <!-- Yorumlar -->
                <div class="tab-pane" id="reviews">
                    <div class="tab-inner">
                        <?php if (!empty($yorumlar)): ?>
                            <div class="reviews-list">
                                <?php foreach ($yorumlar as $yorum): ?>
                                    <div class="review-item">
                                        <div class="review-header">
                                            <div class="review-user">
                                                <span class="review-avatar">
                                                    <?php echo strtoupper(mb_substr($yorum['ad'] ?? 'K', 0, 1)); ?>
                                                </span>
                                                <div class="review-user-info">
                                                    <span class="review-name"><?php echo $yorum['ad'] . ' ' . $yorum['soyad']; ?></span>
                                                    <span class="review-date"><?php echo date('d.m.Y', strtotime($yorum['olusturma_tarihi'])); ?></span>
                                                </div>
                                            </div>
                                            <div class="review-stars">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <?php if ($i <= $yorum['puan']): ?>
                                                        <i class="fa-solid fa-star"></i>
                                                    <?php else: ?>
                                                        <i class="fa-regular fa-star"></i>
                                                    <?php endif; ?>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                        <?php if ($yorum['baslik']): ?>
                                            <h4><?php echo $yorum['baslik']; ?></h4>
                                        <?php endif; ?>
                                        <p><?php echo $yorum['yorum']; ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fa-regular fa-comment"></i>
                                <p>Henüz yorum yapılmamış. İlk yorumu sen yap!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- ============================================ -->
        <!-- BENZER ÜRÜNLER -->
        <!-- ============================================ -->
        <?php if (!empty($benzer_urunler)): ?>
            <section class="similar-products">
                <h2>Benzer Ürünler</h2>
                <div class="product-grid">
                    <?php foreach ($benzer_urunler as $benzer): ?>
                        <div class="product-card">
                            <?php if ($benzer['indirimli_fiyat'] && $benzer['indirimli_fiyat'] < $benzer['fiyat']): ?>
                                <div class="discount">
                                    -<?php echo round((($benzer['fiyat'] - $benzer['indirimli_fiyat']) / $benzer['fiyat']) * 100); ?>%
                                </div>
                            <?php endif; ?>
                            
                            <div class="favorite" onclick="toggleWishlist(<?php echo $benzer['id']; ?>, this)">
                                <i class="fa-regular fa-heart"></i>
                            </div>
                            
                            <a href="<?php echo SITE_URL; ?>public/urun-detay.php?slug=<?php echo $benzer['slug']; ?>">
                                <?php if ($benzer['resim_url']): ?>
                                    <img src="<?php echo SITE_URL; ?>uploads/<?php echo $benzer['resim_url']; ?>" alt="<?php echo $benzer['ad']; ?>">
                                <?php else: ?>
                                    <img src="https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=500" alt="<?php echo $benzer['ad']; ?>">
                                <?php endif; ?>
                            </a>
                            
                            <div class="rating">
                                <?php 
                                $puan = round($benzer['puan_ortalamasi'] ?? 0);
                                for ($i = 1; $i <= 5; $i++): 
                                ?>
                                    <?php if ($i <= $puan): ?>
                                        ⭐
                                    <?php else: ?>
                                        ☆
                                    <?php endif; ?>
                                <?php endfor; ?>
                                (<?php echo $benzer['puan_sayisi'] ?? 0; ?>)
                            </div>
                            
                            <a href="<?php echo SITE_URL; ?>public/urun-detay.php?slug=<?php echo $benzer['slug']; ?>" style="text-decoration:none;color:inherit;">
                                <h3><?php echo $benzer['ad']; ?></h3>
                            </a>
                            
                            <div class="price">
                                <?php if ($benzer['indirimli_fiyat'] && $benzer['indirimli_fiyat'] < $benzer['fiyat']): ?>
                                    <span class="current"><?php echo number_format($benzer['indirimli_fiyat'], 0, ',', '.'); ?> TL</span>
                                    <span class="old"><?php echo number_format($benzer['fiyat'], 0, ',', '.'); ?> TL</span>
                                <?php else: ?>
                                    <span class="current"><?php echo number_format($benzer['fiyat'], 0, ',', '.'); ?> TL</span>
                                <?php endif; ?>
                            </div>
                            
                            <button onclick="addToCart(<?php echo $benzer['id']; ?>)">Sepete Ekle</button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
        
    </div>
</section>

<!-- ============================================ -->
<!-- JAVASCRIPT -->
<!-- ============================================ -->
<script>
// ============================================
// RESİM DEĞİŞTİR (Thumbnail tıklama)
// ============================================
function changeImage(element, src) {
    document.getElementById('mainImage').src = src;
    document.querySelectorAll('.thumbnail-item').forEach(function(item) {
        item.classList.remove('active');
    });
    element.classList.add('active');
}

// ============================================
// ADET GÜNCELLE
// ============================================
function updateQuantity(change) {
    const input = document.getElementById('quantity');
    let value = parseInt(input.value) + change;
    const max = parseInt(input.max);
    if (value < 1) value = 1;
    if (value > max) value = max;
    input.value = value;
}

// ============================================
// VARYANT SEÇ - RESİM DEĞİŞTİR
// ============================================
function selectVariant(id, element) {
    // Tüm varyantları pasif yap
    document.querySelectorAll('.variant-item').forEach(function(item) {
        item.classList.remove('selected');
    });
    
    // Seçili varyantı aktif yap
    element.classList.add('selected');
    
    // Varyant verilerini al
    const resim = element.dataset.resim;
    const fiyat = parseFloat(element.dataset.fiyat) || 0;
    const indirimliFiyat = parseFloat(element.dataset.indirimli_fiyat) || 0;
    const stok = parseInt(element.dataset.stok) || 0;
    
    // Ana resmi değiştir
    const mainImage = document.getElementById('mainImage');
    if (resim && resim !== '') {
        mainImage.style.opacity = '0.5';
        setTimeout(function() {
            mainImage.src = resim;
            mainImage.style.opacity = '1';
        }, 300);
    }
    
    // Fiyatı güncelle
    const priceContainer = document.querySelector('.product-price');
    if (priceContainer) {
        if (indirimliFiyat > 0 && indirimliFiyat < fiyat) {
            priceContainer.innerHTML = `
                <span class="current-price" id="currentPrice">${indirimliFiyat.toLocaleString('tr-TR')} TL</span>
                <span class="old-price" id="oldPrice">${fiyat.toLocaleString('tr-TR')} TL</span>
                <span class="discount-badge" id="discountBadge">-${Math.round(((fiyat - indirimliFiyat) / fiyat) * 100)}%</span>
            `;
        } else if (fiyat > 0) {
            priceContainer.innerHTML = `
                <span class="current-price" id="currentPrice">${fiyat.toLocaleString('tr-TR')} TL</span>
            `;
        }
    }
    
    // Stok durumunu güncelle
    const stockStatus = document.getElementById('stockStatus');
    const quantityInput = document.getElementById('quantity');
    const addToCartBtn = document.getElementById('addToCartBtn');
    
    if (stockStatus) {
        if (stok > 0) {
            stockStatus.innerHTML = `
                <span class="in-stock"><i class="fa-solid fa-check-circle"></i> Stokta Var</span>
                ${stok <= 5 ? `<span class="low-stock"> (Son ${stok} ürün)</span>` : ''}
            `;
            
            if (quantityInput) {
                quantityInput.max = stok;
                quantityInput.value = 1;
            }
            
            if (addToCartBtn) {
                addToCartBtn.disabled = false;
                addToCartBtn.style.background = '';
                addToCartBtn.style.cursor = 'pointer';
                addToCartBtn.innerHTML = '<i class="fa-solid fa-cart-plus"></i> Sepete Ekle';
            }
        } else {
            stockStatus.innerHTML = `
                <span class="out-of-stock"><i class="fa-solid fa-times-circle"></i> Stokta Yok</span>
            `;
            
            if (addToCartBtn) {
                addToCartBtn.disabled = true;
                addToCartBtn.style.background = '#ccc';
                addToCartBtn.style.cursor = 'not-allowed';
                addToCartBtn.innerHTML = '<i class="fa-solid fa-times"></i> Stokta Yok';
            }
            
            if (quantityInput) {
                quantityInput.value = 0;
                quantityInput.max = 0;
            }
        }
    }
    
    // Varyant ID'yi sepete ekleme için sakla
    const hiddenVariantInput = document.getElementById('selectedVariantId');
    if (hiddenVariantInput) {
        hiddenVariantInput.value = id;
    }
}

// ============================================
// SEPETE EKLE (Detay sayfası)
// ============================================
function addToCartDetail(productId) {
    const quantity = document.getElementById('quantity').value;
    const btn = document.getElementById('addToCartBtn');
    const originalText = btn.innerHTML;
    
    // Seçili varyantı bul
    const selectedVariant = document.querySelector('.variant-item.selected');
    let variantId = '';
    
    if (selectedVariant) {
        variantId = selectedVariant.dataset.id || '';
    }
    
    const body = 'urun_id=' + productId + '&adet=' + quantity + '&varyant_id=' + variantId;
    
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Ekleniyor...';
    btn.disabled = true;
    
    fetch('<?php echo SITE_URL; ?>public/sepet-ekle.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: body
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            btn.innerHTML = '<i class="fa-solid fa-check"></i> Sepete Eklendi';
            btn.style.background = '#2ecc71';
            btn.style.color = '#fff';
            
            const badge = document.querySelector('.badge');
            if (badge) {
                badge.textContent = data.cart_count || parseInt(badge.textContent) + parseInt(quantity);
            }
            
            setTimeout(function() {
                btn.innerHTML = originalText;
                btn.style.background = '';
                btn.style.color = '';
                btn.disabled = false;
            }, 2000);
        } else {
            btn.innerHTML = '<i class="fa-solid fa-exclamation-circle"></i> ' + (data.message || 'Hata!');
            btn.style.background = '#ff3366';
            btn.style.color = '#fff';
            
            setTimeout(function() {
                btn.innerHTML = originalText;
                btn.style.background = '';
                btn.style.color = '';
                btn.disabled = false;
            }, 3000);
        }
    })
    .catch(error => {
        console.error('Hata:', error);
        btn.innerHTML = '<i class="fa-solid fa-exclamation-circle"></i> Bağlantı Hatası!';
        btn.style.background = '#ff3366';
        btn.style.color = '#fff';
        
        setTimeout(function() {
            btn.innerHTML = originalText;
            btn.style.background = '';
            btn.style.color = '';
            btn.disabled = false;
        }, 3000);
    });
}

// ============================================
// FAVORİ EKLE (Detay sayfası)
// ============================================
// ============================================
// FAVORİ EKLE (Detay sayfası)
// ============================================
// ============================================
// FAVORİ EKLE (Detay sayfası - DEBUG MODLU)
// ============================================
function toggleWishlistDetail(productId) {
    console.log('🔍 Favori butonuna tıklandı! Ürün ID:', productId);
    
    const btn = document.querySelector('.wishlist-btn');
    const icon = btn.querySelector('i');
    const originalColor = btn.style.color;
    const originalBorder = btn.style.borderColor;
    
    console.log('📌 Mevcut ikon durumu:', icon.className);
    
    // Optimistik UI güncelleme
    icon.classList.toggle('fa-regular');
    icon.classList.toggle('fa-solid');
    
    console.log('📌 Yeni ikon durumu:', icon.className);
    
    if (icon.classList.contains('fa-solid')) {
        btn.style.color = '#ff3366';
        btn.style.borderColor = '#ff3366';
        console.log('❤️ Favori eklendi (UI)');
    } else {
        btn.style.color = '';
        btn.style.borderColor = '';
        console.log('💔 Favori kaldırıldı (UI)');
    }
    
    // AJAX isteği
    const url = '<?php echo SITE_URL; ?>public/favori-ekle.php';
    const data = 'urun_id=' + productId;
    
    console.log('📡 AJAX isteği gönderiliyor...');
    console.log('   URL:', url);
    console.log('   Data:', data);
    
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: data
    })
    .then(response => {
        console.log('📨 Response alındı. Status:', response.status);
        console.log('   Headers:', response.headers);
        return response.json();
    })
    .then(data => {
        console.log('📦 Gelen veri:', data);
        
        if (data.success) {
            console.log('✅ Başarılı:', data.message);
            console.log('   Favori durumu:', data.is_favorite ? 'Eklendi' : 'Kaldırıldı');
        } else {
            console.error('❌ Hata:', data.message);
            
            // Hata durumunda geri al
            icon.classList.toggle('fa-regular');
            icon.classList.toggle('fa-solid');
            btn.style.color = originalColor;
            btn.style.borderColor = originalBorder;
            alert('Hata: ' + data.message);
        }
    })
    .catch(error => {
        console.error('❌ Fetch hatası:', error);
        console.error('   Hata detayı:', error.message);
        
        // Hata durumunda geri al
        icon.classList.toggle('fa-regular');
        icon.classList.toggle('fa-solid');
        btn.style.color = originalColor;
        btn.style.borderColor = originalBorder;
        alert('Bir hata oluştu. Lütfen tekrar deneyin.');
    });
}

// ============================================
// TAB GEÇİŞİ
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.tab-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.tab-btn').forEach(function(b) {
                b.classList.remove('active');
            });
            this.classList.add('active');
            
            document.querySelectorAll('.tab-pane').forEach(function(pane) {
                pane.classList.remove('active');
            });
            
            const tabId = this.dataset.tab;
            document.getElementById(tabId).classList.add('active');
        });
    });
});

// ============================================
// FAVORİ DURUMUNU KONTROL ET (Sayfa yüklendiğinde)
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    // Favori butonunun başlangıç durumunu kontrol et
    const wishlistBtn = document.querySelector('.wishlist-btn');
    if (wishlistBtn) {
        const icon = wishlistBtn.querySelector('i');
        // Eğer icon fa-solid ise favori eklenmiş demektir
        if (icon.classList.contains('fa-solid')) {
            wishlistBtn.style.color = '#ff3366';
            wishlistBtn.style.borderColor = '#ff3366';
        }
    }
});
// ============================================
// BENZER ÜRÜNLER - FAVORİ ve SEPETE EKLE
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
   ÜRÜN DETAY SAYFASI ÖZEL STİLLER
   ============================================ */

.product-detail-page {
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

/* Product Detail */
.product-detail {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
    background: #fff;
    border-radius: 24px;
    padding: 40px;
    box-shadow: 0 5px 25px rgba(0,0,0,0.05);
    margin-bottom: 40px;
}

/* Images */
.product-images {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.main-image {
    border-radius: 16px;
    overflow: hidden;
    background: #FFFFFF;
    aspect-ratio: 1;
    display: flex;
    align-items: center;
    justify-content: center;
}

.main-image img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    transition: opacity 0.3s ease;
}

.thumbnail-list {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
}

.thumbnail-item {
    border-radius: 12px;
    overflow: hidden;
    border: 2px solid transparent;
    cursor: pointer;
    transition: all 0.3s;
    background: #f8f9fb;
    aspect-ratio: 1;
    display: flex;
    align-items: center;
    justify-content: center;
}

.thumbnail-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.thumbnail-item:hover {
    border-color: #ddd;
}

.thumbnail-item.active {
    border-color: #ffd400;
}

/* Product Info */
.product-brand {
    color: #666;
    font-size: 14px;
    font-weight: 600;
    display: block;
    margin-bottom: 4px;
}

.product-info h1 {
    font-size: 28px;
    font-weight: 800;
    margin-bottom: 12px;
}

.product-rating {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 16px;
}

.product-rating .stars {
    color: #ffb400;
    font-size: 16px;
}

.rating-count {
    color: #999;
    font-size: 14px;
}

.product-price {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
}

.current-price {
    font-size: 32px;
    font-weight: 800;
    color: #222;
}

.old-price {
    font-size: 20px;
    color: #999;
    text-decoration: line-through;
}

.discount-badge {
    background: #ff3366;
    color: #fff;
    padding: 4px 12px;
    border-radius: 50px;
    font-size: 14px;
    font-weight: 700;
}

.product-code {
    color: #999;
    font-size: 14px;
    margin-bottom: 12px;
}

.stock-status {
    margin-bottom: 16px;
    font-size: 15px;
}

.in-stock {
    color: #2ecc71;
}

.in-stock i {
    margin-right: 4px;
}

.out-of-stock {
    color: #ff3366;
}

.out-of-stock i {
    margin-right: 4px;
}

.low-stock {
    color: #f39c12;
}

.product-description {
    margin-bottom: 20px;
    padding: 16px;
    background: #f8f9fb;
    border-radius: 12px;
    font-size: 15px;
    color: #666;
    line-height: 1.8;
}

/* Variants */
.product-variants {
    margin-bottom: 20px;
}

.product-variants h4 {
    font-size: 15px;
    font-weight: 600;
    margin-bottom: 10px;
}

.variant-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.variant-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border: 2px solid #eee;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s;
    font-size: 14px;
}

.variant-item:hover {
    border-color: #ddd;
}

.variant-item.selected {
    border-color: #ffd400;
    background: #fef9e7;
    box-shadow: 0 0 0 3px rgba(255, 212, 0, 0.2);
}

.variant-item.in-stock {
    opacity: 1;
}

.variant-item.out-of-stock {
    opacity: 0.5;
    cursor: not-allowed;
}

.variant-item .variant-label {
    font-weight: 500;
}

.variant-item .variant-stock {
    font-size: 12px;
    font-weight: 700;
}

/* Quantity */
.product-quantity {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 20px;
}

.product-quantity label {
    font-weight: 600;
    font-size: 14px;
}

.quantity-selector {
    display: flex;
    align-items: center;
    border: 2px solid #eee;
    border-radius: 12px;
    overflow: hidden;
}

.quantity-selector .qty-btn {
    width: 44px;
    height: 44px;
    border: none;
    background: #f8f9fb;
    font-size: 20px;
    cursor: pointer;
    transition: all 0.3s;
}

.quantity-selector .qty-btn:hover {
    background: #eee;
}

.quantity-selector input {
    width: 60px;
    height: 44px;
    border: none;
    text-align: center;
    font-size: 16px;
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

/* Actions */
.product-actions {
    display: flex;
    gap: 12px;
    margin-bottom: 20px;
}

.btn-lg {
    padding: 16px 32px;
    font-size: 16px;
}

.add-to-cart {
    flex: 1;
}

.wishlist-btn {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    border: 2px solid #eee;
    background: #fff;
    font-size: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s;
}

.wishlist-btn:hover {
    border-color: #ff3366;
    color: #ff3366;
}

/* Security */
.product-security {
    display: flex;
    gap: 20px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

.security-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    color: #666;
}

.security-item i {
    color: #ffd400;
    font-size: 18px;
}

/* Tabs */
.product-tabs {
    background: #fff;
    border-radius: 24px;
    padding: 0;
    box-shadow: 0 5px 25px rgba(0,0,0,0.05);
    overflow: hidden;
    margin-bottom: 40px;
}

.tab-header {
    display: flex;
    border-bottom: 2px solid #eee;
    background: #f8f9fb;
}

.tab-btn {
    padding: 16px 32px;
    border: none;
    background: none;
    font-weight: 600;
    font-size: 15px;
    font-family: 'Inter', sans-serif;
    cursor: pointer;
    transition: all 0.3s;
    color: #999;
    position: relative;
}

.tab-btn:hover {
    color: #222;
}

.tab-btn.active {
    color: #222;
}

.tab-btn.active::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    right: 0;
    height: 3px;
    background: #ffd400;
    border-radius: 10px;
}

.tab-content {
    padding: 30px;
}

.tab-pane {
    display: none;
}

.tab-pane.active {
    display: block;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.tab-inner {
    max-width: 100%;
}

/* Specs Table */
.specs-table {
    width: 100%;
    border-collapse: collapse;
}

.specs-table tr {
    border-bottom: 1px solid #eee;
}

.specs-table td {
    padding: 12px 16px;
    font-size: 14px;
}

.specs-table td:first-child {
    color: #999;
    font-weight: 500;
    width: 150px;
}

.specs-table tr:last-child {
    border-bottom: none;
}

/* Reviews */
.reviews-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.review-item {
    padding: 16px;
    background: #f8f9fb;
    border-radius: 12px;
}

.review-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.review-user {
    display: flex;
    align-items: center;
    gap: 12px;
}

.review-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #ffd400;
    color: #111;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 16px;
}

.review-user-info {
    display: flex;
    flex-direction: column;
}

.review-name {
    font-weight: 600;
    font-size: 14px;
}

.review-date {
    font-size: 12px;
    color: #999;
}

.review-stars {
    color: #ffb400;
    font-size: 14px;
}

.review-item h4 {
    font-size: 15px;
    font-weight: 600;
    margin-bottom: 4px;
}

.review-item p {
    color: #666;
    font-size: 14px;
    line-height: 1.6;
}

/* Similar Products */
.similar-products {
    margin-top: 40px;
}

.similar-products h2 {
    font-size: 24px;
    font-weight: 700;
    margin-bottom: 24px;
}

.similar-products .product-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
}

.similar-products .product-card {
    background: #fff;
    border-radius: 16px;
    padding: 16px;
    border: 1px solid #f0f0f0;
    transition: all 0.3s;
    position: relative;
}

.similar-products .product-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 40px rgba(0,0,0,0.08);
    border-color: #ffd400;
}

.similar-products .product-card img {
    width: 100%;
    height: 200px;
    object-fit: contain;
    border-radius: 12px;
    background: #f8f9fb;
}

.similar-products .product-card .discount {
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

.similar-products .product-card .favorite {
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

.similar-products .product-card .favorite:hover {
    color: #ff3366;
}

.similar-products .product-card .rating {
    color: #ffb400;
    font-size: 13px;
    margin-top: 10px;
}

.similar-products .product-card h3 {
    font-size: 14px;
    font-weight: 600;
    margin: 8px 0;
    min-height: 40px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.similar-products .product-card .price {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
}

.similar-products .product-card .price .current {
    font-size: 18px;
    font-weight: 800;
    color: #222;
}

.similar-products .product-card .price .old {
    font-size: 13px;
    color: #999;
    text-decoration: line-through;
}

.similar-products .product-card button {
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

.similar-products .product-card button:hover {
    background: #f5c800;
    transform: translateY(-2px);
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #999;
}

.empty-state i {
    font-size: 48px;
    margin-bottom: 12px;
    opacity: 0.3;
}

.empty-state p {
    font-size: 16px;
}

/* Responsive */
@media (max-width: 992px) {
    .product-detail {
        grid-template-columns: 1fr;
        padding: 24px;
    }
    
    .similar-products .product-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .product-detail {
        padding: 20px;
        gap: 24px;
    }
    
    .product-info h1 {
        font-size: 22px;
    }
    
    .current-price {
        font-size: 28px;
    }
    
    .tab-btn {
        padding: 12px 16px;
        font-size: 14px;
    }
    
    .tab-content {
        padding: 20px;
    }
    
    .product-security {
        flex-wrap: wrap;
        gap: 12px;
    }
    
    .similar-products .product-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .similar-products .product-card img {
        height: 160px;
    }
    
    .review-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
}

@media (max-width: 480px) {
    .product-detail {
        padding: 16px;
        border-radius: 16px;
    }
    
    .current-price {
        font-size: 24px;
    }
    
    .product-actions {
        flex-direction: column;
    }
    
    .wishlist-btn {
        width: 100%;
        height: 48px;
    }
    
    .product-security {
        flex-direction: column;
        gap: 8px;
    }
    
    .thumbnail-list {
        grid-template-columns: repeat(4, 1fr);
        gap: 8px;
    }
    
    .similar-products .product-grid {
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }
    
    .similar-products .product-card {
        padding: 12px;
    }
    
    .similar-products .product-card img {
        height: 140px;
    }
    
    .similar-products .product-card h3 {
        font-size: 13px;
        min-height: 34px;
    }
}
</style>

<?php
// =====================================================
// FOOTER'I DAHİL ET
// =====================================================
include '../includes/footer.php';
?>