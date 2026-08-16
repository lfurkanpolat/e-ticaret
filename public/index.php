<?php
// =====================================================
// ANA SAYFA - public/index.php
// =====================================================

require_once '../includes/config.php';

// =====================================================
// SLIDER VERİLERİNİ ÇEK
// =====================================================
try {
    $stmt = $db->prepare("
        SELECT * FROM slider 
        WHERE durum = 'aktif' 
        ORDER BY sira ASC, id ASC
    ");
    $stmt->execute();
    $slider_items = $stmt->fetchAll();
} catch (PDOException $e) {
    $slider_items = [];
}

// =====================================================
// ÜRÜNLERİ ÇEK
// =====================================================
try {
    $stmt = $db->prepare("
        SELECT u.*, k.ad as kategori_adi, m.ad as marka_adi
        FROM urunler u
        LEFT JOIN kategoriler k ON u.kategori_id = k.id
        LEFT JOIN markalar m ON u.marka_id = m.id
        WHERE u.durum = 'aktif'
        ORDER BY u.olusturma_tarihi DESC
        LIMIT 8
    ");
    $stmt->execute();
    $son_urunler = $stmt->fetchAll();
} catch (PDOException $e) {
    $son_urunler = [];
}

// =====================================================
// ANA KATEGORİLERİ ÇEK (Sadece ust_id = NULL olanlar)
// =====================================================
try {
    $stmt = $db->prepare("
        SELECT * FROM kategoriler 
        WHERE ust_id IS NULL AND durum = 'aktif'
    ");
    $stmt->execute();
    $kategoriler = $stmt->fetchAll();
} catch (PDOException $e) {
    $kategoriler = [];
}

// =====================================================
// HEADER'I DAHİL ET
// =====================================================
include '../includes/header.php';
?>

<!-- ============================================ -->
<!-- HERO SLIDER -->
<!-- ============================================ -->
<section class="hero">
    <div class="container">
        <div class="hero-slider" id="heroSlider">
            <?php if (!empty($slider_items)): ?>
                <?php foreach ($slider_items as $index => $slider): ?>
                    <div class="hero-slide <?php echo $index === 0 ? 'active' : ''; ?>" data-index="<?php echo $index; ?>">
                        <div class="hero-content">
                            <span class="hero-tag"><?php echo $slider['alt_baslik'] ?? 'YAZA ÖZEL'; ?></span>
                            <h1>
                                <?php 
                                $baslik = explode(' ', $slider['baslik'] ?? 'İNDİRİM FESTİVALİ');
                                if (count($baslik) > 1) {
                                    $son = array_pop($baslik);
                                    echo implode(' ', $baslik) . ' <span>' . $son . '</span>';
                                } else {
                                    echo $slider['baslik'] ?? 'İNDİRİM <span>FESTİVALİ</span>';
                                }
                                ?>
                            </h1>
                            <p><?php echo $slider['aciklama'] ?? 'Seçili ürünlerde %50\'ye varan indirimler!'; ?></p>
                            <a href="<?php echo SITE_URL . ($slider['link_url'] ?? 'public/urunler.php'); ?>" class="hero-btn">
                                <?php echo $slider['buton_metni'] ?? 'Alışverişe Başla'; ?>
                                <i class="fa-solid fa-arrow-right"></i>
                            </a>
                        </div>
                        <div class="hero-image">
                            <?php if (!empty($slider['resim_url'])): ?>
                                <img src="<?php echo SITE_URL; ?>uploads/slider/<?php echo $slider['resim_url']; ?>" alt="<?php echo $slider['baslik'] ?? 'Slider'; ?>">
                            <?php else: ?>
                                <img src="https://images.unsplash.com/photo-1546435770-a3e426bf472b?w=900" alt="Slider">
                            <?php endif; ?>
                            
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <!-- Slider Kontrolleri -->
                <?php if (count($slider_items) > 1): ?>
                    <button class="slider-btn prev-btn" id="prevBtn">
                        <i class="fa-solid fa-chevron-left"></i>
                    </button>
                    <button class="slider-btn next-btn" id="nextBtn">
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>
                    <div class="slider-dots">
                        <?php foreach ($slider_items as $index => $slider): ?>
                            <span class="dot <?php echo $index === 0 ? 'active' : ''; ?>" data-index="<?php echo $index; ?>"></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
            <?php else: ?>
                <!-- Varsayılan slider (slider yoksa) -->
                <div class="hero-slide active">
                    <div class="hero-content">
                        <span class="hero-tag">YAZA ÖZEL</span>
                        <h1>
                            İNDİRİM
                            <span>FESTİVALİ</span>
                        </h1>
                        <p>Seçili ürünlerde %50'ye varan indirimler!</p>
                        <a href="<?php echo SITE_URL; ?>public/urunler.php" class="hero-btn">
                            Alışverişe Başla
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </div>
                    <div class="hero-image">
                        <img src="https://images.unsplash.com/photo-1546435770-a3e426bf472b?w=900" alt="Slider">
                        <div class="discount-circle">
                            %50
                            <br>
                            İNDİRİM
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php
// Arka plan için tanımlı renk dizisi
$bg_renkler = [
    "#f2f5f7",
    "#f9f5f1", 
    "#f7eef4", 
    "#f8f6ef",
    "#f7f3f5"
];

// <i> simgeleri/metinler için yeni tanımlanan renk dizisi
$icon_renkler = [
    "#1036bc",
    "#c9120d",
    "#78a98d",
    "#e8c2d7",
    "#fce085",
    "#251087"
];
?>
<!-- ============================================ -->
<!-- ANA KATEGORİLER (SADECE VERİTABANINDAN) -->
<!-- ============================================ -->
<section class="categories">
    <div class="container">
        <div class="category-grid">
            <?php if (!empty($kategoriler)): ?>
                <?php foreach ($kategoriler as $kategori): ?>
                    <?php 
                        // Her döngüde arka plan ve icon için ayrı ayrı rastgele renk seçimi
                        $rastgele_bg = $bg_renkler[array_rand($bg_renkler)]; 
                        $rastgele_icon_color = $icon_renkler[array_rand($icon_renkler)];
                    ?>
                    <div style="background-color: <?php echo $rastgele_bg; ?>; border-radius:20px;">
                        <a href="<?php echo SITE_URL; ?>public/kategori.php?slug=<?php echo $kategori['slug']; ?>" class="category-card">
                            <?php if (!empty($kategori['icon'])): ?>
                                <i class="<?php echo $kategori['icon']; ?>" style="color: <?php echo $rastgele_icon_color; ?>;"></i>
                            <?php else: ?>
                                <i class="fa-solid fa-tag" style="color: <?php echo $rastgele_icon_color; ?>;"></i>
                            <?php endif; ?>
                            <span><?php echo $kategori['ad']; ?></span>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>
<!-- ============================================ -->
<!-- PROMO BANNERS -->
<!-- ============================================ -->
<?php
// =====================================================
// ANA SAYFA - public/index.php (PROMO BANNER KISMI)
// =====================================================

// =====================================================
// PROMO BANNER VERİLERİNİ ÇEK
// =====================================================
try {
    $stmt = $db->prepare("
        SELECT * FROM promo_banners 
        WHERE durum = 'aktif' 
        ORDER BY sira ASC, id ASC
    ");
    $stmt->execute();
    $promo_banners = $stmt->fetchAll();
} catch (PDOException $e) {
    $promo_banners = [];
}


// =====================================================
// PROMO BANNERS
// =====================================================
if (!empty($promo_banners)): ?>
<section class="promo-banners">
    <div class="container">
        <div class="promo-grid">
            <?php foreach ($promo_banners as $banner): 
                // Renk sınıfını belirle
                $renk_class = $banner['renk'] ?? 'purple';
                
                // Renk map'i
                $renk_map = [
                    'purple' => 'purple',
                    'dark' => 'dark',
                    'orange' => 'orange',
                    'blue' => 'blue',
                    'green' => 'green',
                    'red' => 'red',
                    'pink' => 'pink'
                ];
                $renk_class = $renk_map[$renk_class] ?? 'purple';
            ?>
                <div class="promo-card <?php echo $renk_class; ?>">
                    <div>
                        <h3><?php echo $banner['baslik']; ?></h3>
                        <?php if ($banner['alt_baslik']): ?>
                            <p><?php echo $banner['alt_baslik']; ?></p>
                        <?php endif; ?>
                        <?php if ($banner['buton_metni'] && $banner['buton_link']): ?>
                            <button onclick="window.location.href='<?php echo SITE_URL . $banner['buton_link']; ?>'">
                                <?php echo $banner['buton_metni']; ?>
                            </button>
                        <?php endif; ?>
                    </div>
                    <?php if ($banner['resim_url']): ?>
                        <img src="<?php echo $banner['resim_url']; ?>" alt="<?php echo $banner['baslik']; ?>">
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ============================================ -->
<!-- ÇOK SATAN ÜRÜNLER -->
<!-- ============================================ -->
<section class="products">
    <div class="container">
        <div class="section-title">
            <h2><i class="fa-solid fa-bolt"></i> Çok Satan Ürünler</h2>
            <a href="<?php echo SITE_URL; ?>public/cok-satanlar.php">Tümünü Gör</a>
        </div>
        <div class="product-grid">
            <?php if (!empty($son_urunler)): ?>
                <?php foreach ($son_urunler as $urun): ?>
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
                        
                        <div class="price">
                            <?php if ($urun['indirimli_fiyat'] && $urun['indirimli_fiyat'] < $urun['fiyat']): ?>
                                <span class="current"><?php echo number_format($urun['indirimli_fiyat'], 0, ',', '.'); ?> TL</span>
                                <span class="old"><?php echo number_format($urun['fiyat'], 0, ',', '.'); ?> TL</span>
                            <?php else: ?>
                                <span class="current"><?php echo number_format($urun['fiyat'], 0, ',', '.'); ?> TL</span>
                            <?php endif; ?>
                        </div>
                        
                        <button onclick="addToCart(<?php echo $urun['id']; ?>)">Sepete Ekle</button>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="product-grid-empty">
                    <p>Henüz ürün eklenmemiş.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ============================================ -->
<!-- JAVASCRIPT -->
<!-- ============================================ -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // ============================================
    // SLIDER - OTOMATİK DÖNGÜ
    // ============================================
    const slides = document.querySelectorAll('.hero-slide');
    const dots = document.querySelectorAll('.dot');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    let currentSlide = 0;
    let slideInterval;
    const intervalTime = 5000;
    
    if (slides.length > 1) {
        
        function goToSlide(index) {
            slides.forEach(slide => {
                slide.classList.remove('active');
            });
            dots.forEach(dot => {
                dot.classList.remove('active');
            });
            slides[index].classList.add('active');
            if (dots[index]) {
                dots[index].classList.add('active');
            }
            currentSlide = index;
        }
        
        function nextSlide() {
            let next = currentSlide + 1;
            if (next >= slides.length) {
                next = 0;
            }
            goToSlide(next);
        }
        
        function prevSlide() {
            let prev = currentSlide - 1;
            if (prev < 0) {
                prev = slides.length - 1;
            }
            goToSlide(prev);
        }
        
        dots.forEach(dot => {
            dot.addEventListener('click', function() {
                const index = parseInt(this.dataset.index);
                goToSlide(index);
                resetInterval();
            });
        });
        
        if (prevBtn) {
            prevBtn.addEventListener('click', function() {
                prevSlide();
                resetInterval();
            });
        }
        
        if (nextBtn) {
            nextBtn.addEventListener('click', function() {
                nextSlide();
                resetInterval();
            });
        }
        
        function startInterval() {
            slideInterval = setInterval(nextSlide, intervalTime);
        }
        
        function resetInterval() {
            clearInterval(slideInterval);
            startInterval();
        }
        
        const slider = document.getElementById('heroSlider');
        if (slider) {
            slider.addEventListener('mouseenter', function() {
                clearInterval(slideInterval);
            });
            slider.addEventListener('mouseleave', function() {
                startInterval();
            });
        }
        
        startInterval();
    }
    
    // ============================================
    // FAVORİ EKLE/ÇIKAR
    // ============================================
    window.toggleWishlist = function(productId, element) {
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
    };
    
    // ============================================
    // SEPETE EKLE
    // ============================================
    window.addToCart = function(productId) {
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
    };
    
});
</script>

<style>
/* ============================================
   SLIDER ÖZEL STİLLER
   ============================================ */

.hero-slider {
    position: relative;
    overflow: hidden;
    background: linear-gradient(135deg, #ffe600, #ffc400);
    min-height: 520px;
    border-radius: 35px;
    padding: 50px;
}

.hero-slide {
    display: none;
    align-items: center;
    justify-content: space-between;
    animation: fadeSlide 0.6s ease;
}

.hero-slide.active {
    display: flex;
}

@keyframes fadeSlide {
    from {
        opacity: 0;
        transform: translateX(30px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.slider-btn {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    z-index: 10;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    border: none;
    background: rgba(255, 255, 255, 0.3);
    backdrop-filter: blur(10px);
    color: #111;
    font-size: 20px;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.slider-btn:hover {
    background: #fff;
    transform: translateY(-50%) scale(1.05);
}

.prev-btn {
    left: 20px;
}

.next-btn {
    right: 20px;
}

.slider-dots {
    position: absolute;
    bottom: 30px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 10;
    display: flex;
    gap: 12px;
}

.slider-dots .dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.5);
    cursor: pointer;
    transition: all 0.3s;
    border: 2px solid transparent;
}

.slider-dots .dot.active {
    background: #111;
    border-color: #ffd400;
    transform: scale(1.2);
}

.slider-dots .dot:hover {
    transform: scale(1.1);
}

/* Product Grid Empty */
.product-grid-empty {
    text-align: center;
    padding: 60px 20px;
    color: #999;
}

.product-grid-empty p {
    font-size: 18px;
}

/* Responsive */
@media (max-width: 992px) {
    .hero-slider {
        flex-direction: column;
        text-align: center;
        padding: 40px;
        min-height: auto;
    }
    
    .hero-slide {
        flex-direction: column;
        text-align: center;
    }
    
    .hero-image {
        width: 90%;
        margin-top: 20px;
    }
    
    .hero-content {
        max-width: 100%;
    }
    
    .discount-circle {
        display: none;
    }
    
    .slider-btn {
        width: 40px;
        height: 40px;
        font-size: 16px;
    }
    
    .prev-btn {
        left: 10px;
    }
    
    .next-btn {
        right: 10px;
    }
}

@media (max-width: 768px) {
    .hero-slider {
        padding: 30px;
        border-radius: 20px;
    }
    
    .hero-content h1 {
        font-size: 48px;
    }
    
    .slider-dots .dot {
        width: 10px;
        height: 10px;
    }
}

@media (max-width: 480px) {
    .hero-slider {
        padding: 20px;
    }
    
    .hero-content h1 {
        font-size: 38px;
    }
    
    .slider-btn {
        width: 34px;
        height: 34px;
        font-size: 14px;
    }
}
</style>

<?php
// =====================================================
// FOOTER'I DAHİL ET
// =====================================================
include '../includes/footer.php';
?>