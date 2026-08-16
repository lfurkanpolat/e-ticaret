<?php
// =====================================================
// PUBLIC HEADER
// Dizin: public/header.php
// =====================================================

require_once '../includes/config.php';

// Site ayarlarını kontrol et
$site = siteAyar();
$site_durum = $site['site_durum'] ?? 'aktif';
$bakim_mesaji = $site['bakim_mesaji'] ?? 'Sitemiz bakımda...';

// Bakım modu kontrolü
if ($site_durum == 'bakim' && !isAdmin()) {
    echo '<!DOCTYPE html>
    <html lang="tr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Bakım Modu</title>
        <style>
            body {
                font-family: "Inter", sans-serif;
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                background: #1a1a2e;
                color: #fff;
                margin: 0;
                padding: 20px;
                text-align: center;
            }
            .maintenance-box {
                max-width: 500px;
            }
            .maintenance-box i {
                font-size: 72px;
                color: #6C63FF;
                margin-bottom: 20px;
            }
            .maintenance-box h1 {
                font-size: 32px;
                margin-bottom: 12px;
            }
            .maintenance-box p {
                color: #a0a0b8;
                font-size: 18px;
                line-height: 1.6;
            }
        </style>
    </head>
    <body>
        <div class="maintenance-box">
            <i class="fas fa-tools"></i>
            <h1>Bakım Modu</h1>
            <p>' . $bakim_mesaji . '</p>
        </div>
    </body>
    </html>';
    exit();
}

// ============================================
// KATEGORİLERİ ÇEK (Açılır menü için)
// ============================================
try {
    $stmt = $db->prepare("
        SELECT * FROM kategoriler 
        WHERE durum = 'aktif' 
        ORDER BY ust_id ASC, sira ASC, ad ASC
    ");
    $stmt->execute();
    $tum_kategoriler = $stmt->fetchAll();
} catch (PDOException $e) {
    $tum_kategoriler = [];
}

// Ana kategoriler
$ana_kategoriler = array_filter($tum_kategoriler, function($kat) {
    return $kat['ust_id'] === null || $kat['ust_id'] == 0 || $kat['ust_id'] == '';
});

// Alt kategorileri grupla
$alt_kategoriler = [];
foreach ($tum_kategoriler as $kat) {
    if ($kat['ust_id'] !== null && $kat['ust_id'] != 0 && $kat['ust_id'] != '') {
        $alt_kategoriler[$kat['ust_id']][] = $kat;
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_TITLE; ?></title>
    <meta name="description" content="<?php echo SITE_DESC; ?>">
    <meta name="keywords" content="<?php echo SITE_KEYWORDS; ?>">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    
    <!-- Ana CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/style.css?v=<?php echo time(); ?>">
    
    <?php if (!empty($site['site_favicon'])): ?>
        <link rel="icon" href="<?php echo SITE_URL; ?>uploads/<?php echo $site['site_favicon']; ?>">
    <?php endif; ?>
</head>
<body>

<!-- ============================================ -->
<!-- TOP BAR -->
<!-- ============================================ -->
<div class="topbar">
    <div class="container topbar-wrapper">
        <div class="topbar-left">
            🚚 750 TL ve üzeri <strong>ÜCRETSİZ KARGO!</strong>
        </div>
        <div class="topbar-right">
            <a href="#"><i class="fa-solid fa-mobile-screen"></i> Uygulama</a>
            <a href="#"><i class="fa-solid fa-location-dot"></i> Sipariş Takibi</a>
            <a href="#"><i class="fa-solid fa-headset"></i> Yardım</a>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- HEADER -->
<!-- ============================================ -->
<header class="header">
    <div class="container header-wrapper">
        <!-- Logo -->
        <a href="<?php echo SITE_URL; ?>public/index.php" class="logo">
            <div class="logo-icon">
                <i class="fa-solid fa-bag-shopping"></i>
            </div>
            <span><?php echo SITE_NAME; ?></span>
        </a>
        
        <!-- Arama -->
        <div class="search-box">
            <input type="text" id="searchInput" placeholder="Ürün, kategori veya marka ara...">
            <button onclick="searchSubmit()">Ara</button>
            <div id="searchResults" class="search-results">
                <div class="search-empty">🔍 Arama yapmak için yazın...</div>
            </div>
        </div>
        
        <!-- Header Actions -->
        <div class="header-actions">
            <?php if (isLoggedIn()): ?>
                <a href="<?php echo SITE_URL; ?>public/profil.php" class="action-link" title="Profilim">
                    <i class="fa-regular fa-user"></i>
                    <span class="action-label"><?php echo clean($_SESSION['user_ad'] ?? 'Kullanıcı'); ?></span>
                </a>
            <?php else: ?>
                <a href="<?php echo SITE_URL; ?>public/giris.php" class="action-link" title="Giriş Yap">
                    <i class="fa-regular fa-user"></i>
                    <span class="action-label">Giriş Yap</span>
                </a>
            <?php endif; ?>
            <a href="<?php echo SITE_URL; ?>public/favoriler.php" class="action-link" title="Favoriler">
                <i class="fa-regular fa-heart"></i>
                <span class="action-label">Favoriler</span>
            </a>
            <a href="<?php echo SITE_URL; ?>public/sepet.php" class="action-link cart-link" title="Sepetim">
                <i class="fa-solid fa-cart-shopping"></i>
                <span class="action-label">Sepetim</span>
                <span class="badge"><?php echo getCartCount(); ?></span>
            </a>
        </div>
        
        <!-- Mobil Menü Butonu -->
        <button class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Menü">
            <i class="fa-solid fa-bars"></i>
        </button>
    </div>
</header>

<!-- ============================================ -->
<!-- NAVBAR - KATEGORİ AÇILIR MENÜLÜ -->
<!-- ============================================ -->
<nav class="navbar">
    <div class="container nav-wrapper">
        <div class="category-dropdown">
            <button class="category-btn">
                <i class="fa-solid fa-bars"></i>
                TÜM KATEGORİLER
                <i class="fa-solid fa-chevron-down"></i>
            </button>
            <div class="category-menu-dropdown">
                <?php if (!empty($ana_kategoriler)): ?>
                    <?php foreach ($ana_kategoriler as $ana): ?>
                        <?php if (isset($alt_kategoriler[$ana['id']]) && !empty($alt_kategoriler[$ana['id']])): ?>
                            <div class="category-item has-sub">
                                <a href="<?php echo SITE_URL; ?>public/kategori.php?slug=<?php echo $ana['slug']; ?>">
                                    <?php if (!empty($ana['icon'])): ?>
                                        <i class="<?php echo $ana['icon']; ?>"></i>
                                    <?php else: ?>
                                        <i class="fa-solid fa-tag"></i>
                                    <?php endif; ?>
                                    <?php echo $ana['ad']; ?>
                                    <i class="fa-solid fa-chevron-right sub-arrow"></i>
                                </a>
                                <div class="sub-menu">
                                    <?php foreach ($alt_kategoriler[$ana['id']] as $alt): ?>
                                        <a href="<?php echo SITE_URL; ?>public/kategori.php?slug=<?php echo $alt['slug']; ?>">
                                            <?php if (!empty($alt['icon'])): ?>
                                                <i class="<?php echo $alt['icon']; ?>"></i>
                                            <?php else: ?>
                                                <i class="fa-solid fa-chevron-right"></i>
                                            <?php endif; ?>
                                            <?php echo $alt['ad']; ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="category-item">
                                <a href="<?php echo SITE_URL; ?>public/kategori.php?slug=<?php echo $ana['slug']; ?>">
                                    <?php if (!empty($ana['icon'])): ?>
                                        <i class="<?php echo $ana['icon']; ?>"></i>
                                    <?php else: ?>
                                        <i class="fa-solid fa-tag"></i>
                                    <?php endif; ?>
                                    <?php echo $ana['ad']; ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="category-item">
                        <a href="#"><i class="fa-solid fa-headphones"></i> Elektronik</a>
                    </div>
                    <div class="category-item">
                        <a href="#"><i class="fa-solid fa-shirt"></i> Moda</a>
                    </div>
                    <div class="category-item has-sub">
                        <a href="#">
                            <i class="fa-solid fa-tag"></i> Diğer
                            <i class="fa-solid fa-chevron-right sub-arrow"></i>
                        </a>
                        <div class="sub-menu">
                            <a href="#"><i class="fa-solid fa-couch"></i> Ev & Yaşam</a>
                            <a href="#"><i class="fa-solid fa-pump-soap"></i> Kozmetik</a>
                            <a href="#"><i class="fa-solid fa-dumbbell"></i> Spor</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <ul class="menu">
            <li><a href="<?php echo SITE_URL; ?>public/index.php">Anasayfa</a></li>

            <li><a href="<?php echo SITE_URL; ?>public/cok-satanlar.php">Çok Satanlar <span class="new-badge">Yeni</span></a></li>

            <li><a href="<?php echo SITE_URL; ?>public/markalar.php">Markalar</a></li>
  
        </ul>
    </div>
</nav>

<!-- ============================================ -->
<!-- MOBİL MENÜ (Sağdan Açılır) -->
<!-- ============================================ -->
<div class="mobile-menu-overlay" id="mobileMenuOverlay"></div>
<div class="mobile-menu" id="mobileMenu">
    <div class="mobile-menu-header">
        <div class="mobile-logo">
            <i class="fa-solid fa-bag-shopping"></i>
            <span><?php echo SITE_NAME; ?></span>
        </div>
        <button class="mobile-menu-close" id="mobileMenuClose">
            <i class="fa-solid fa-times"></i>
        </button>
    </div>
    <nav class="mobile-nav">
        <a href="<?php echo SITE_URL; ?>public/index.php">
            <i class="fa-solid fa-home"></i> Anasayfa
        </a>
        <a href="#">
            <i class="fa-solid fa-gift"></i> Kampanyalar
            <span class="new-badge">Yeni</span>
        </a>
        <a href="<?php echo SITE_URL; ?>public/cok-satanlar.php">
            <i class="fa-solid fa-fire"></i> Çok Satanlar
        </a>
        <a href="<?php echo SITE_URL; ?>public/yeni-urunler.php">
            <i class="fa-solid fa-star"></i> Yeni Gelenler
        </a>
        <a href="<?php echo SITE_URL; ?>public/markalar.php">
            <i class="fa-solid fa-copyright"></i> Markalar
        </a>
        <hr>
        <!-- Kategoriler -->
        <?php if (!empty($ana_kategoriler)): ?>
            <?php foreach ($ana_kategoriler as $ana): ?>
                <a href="<?php echo SITE_URL; ?>public/kategori.php?slug=<?php echo $ana['slug']; ?>">
                    <?php if (!empty($ana['icon'])): ?>
                        <i class="<?php echo $ana['icon']; ?>"></i>
                    <?php else: ?>
                        <i class="fa-solid fa-tag"></i>
                    <?php endif; ?>
                    <?php echo $ana['ad']; ?>
                </a>
                <?php if (isset($alt_kategoriler[$ana['id']])): ?>
                    <?php foreach ($alt_kategoriler[$ana['id']] as $alt): ?>
                        <a href="<?php echo SITE_URL; ?>public/kategori.php?slug=<?php echo $alt['slug']; ?>" style="padding-left: 40px; font-size: 13px;">
                            <i class="fa-solid fa-chevron-right"></i>
                            <?php echo $alt['ad']; ?>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php else: ?>
            <a href="#"><i class="fa-solid fa-headphones"></i> Elektronik</a>
            <a href="#"><i class="fa-solid fa-shirt"></i> Moda</a>
            <a href="#"><i class="fa-solid fa-couch"></i> Ev & Yaşam</a>
            <a href="#"><i class="fa-solid fa-pump-soap"></i> Kozmetik</a>
            <a href="#"><i class="fa-solid fa-dumbbell"></i> Spor</a>
            <a href="#"><i class="fa-solid fa-baby"></i> Anne & Bebek</a>
            <a href="#"><i class="fa-solid fa-book"></i> Kitap</a>
            <a href="#"><i class="fa-solid fa-car"></i> Otomotiv</a>
        <?php endif; ?>
        <hr>
        <a href="<?php echo SITE_URL; ?>public/favoriler.php">
            <i class="fa-regular fa-heart"></i> Favoriler
        </a>
        <a href="<?php echo SITE_URL; ?>public/sepet.php">
            <i class="fa-solid fa-cart-shopping"></i> Sepetim
        </a>
        <hr>
        <?php if (isLoggedIn()): ?>
            <a href="<?php echo SITE_URL; ?>public/profil.php">
                <i class="fa-regular fa-user"></i> Profilim
            </a>
            <a href="<?php echo SITE_URL; ?>public/siparislerim.php">
                <i class="fa-solid fa-box"></i> Siparişlerim
            </a>
            <a href="<?php echo SITE_URL; ?>public/cikis.php" style="color:#ff3366;">
                <i class="fa-solid fa-sign-out-alt"></i> Çıkış Yap
            </a>
        <?php else: ?>
            <a href="<?php echo SITE_URL; ?>public/giris.php">
                <i class="fa-solid fa-sign-in-alt"></i> Giriş Yap
            </a>
            <a href="<?php echo SITE_URL; ?>public/kayit.php">
                <i class="fa-solid fa-user-plus"></i> Kayıt Ol
            </a>
        <?php endif; ?>
    </nav>
</div>

<!-- ============================================ -->
<!-- STYLE - MOBİL OPTİMİZE -->
<!-- ============================================ -->
<style>
/* ============================================
   HEADER - MOBİL OPTİMİZE
   ============================================ */

/* Logo - Link olarak düzenlendi */
.logo {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 28px;
    font-weight: 800;
    text-decoration: none;
    color: inherit;
}

.logo-icon {
    width: 48px;
    height: 48px;
    background: #ffd400;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.logo-icon i {
    font-size: 20px;
    color: #111;
}

.logo span {
    background: linear-gradient(135deg, #1a1a2e, #ffd400);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* ============================================
   KATEGORİ AÇILIR MENÜ
   ============================================ */
.category-dropdown {
    position: relative;
    display: inline-block;
}

.category-menu-dropdown {
    position: absolute;
    top: calc(100% + 4px);
    left: 0;
    min-width: 260px;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.12);
    padding: 8px 0;
    display: none;
    z-index: 1000;
    border: 1px solid #eee;
}

.category-dropdown:hover .category-menu-dropdown {
    display: block;
}

.category-item {
    position: relative;
}

.category-item a {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 20px;
    color: #222;
    font-weight: 500;
    font-size: 14px;
    transition: all 0.2s;
    text-decoration: none;
}

.category-item a:hover {
    background: #f5f5f5;
    color: #6f2cff;
}

.category-item a i {
    width: 20px;
    font-size: 16px;
    color: #666;
}

.category-item a .sub-arrow {
    margin-left: auto;
    font-size: 12px;
    color: #999;
}

.sub-menu {
    position: absolute;
    top: 0;
    left: 100%;
    min-width: 220px;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.12);
    padding: 8px 0;
    display: none;
    border: 1px solid #eee;
    z-index: 1001;
}

.category-item.has-sub:hover .sub-menu {
    display: block;
}

.sub-menu a {
    padding: 10px 20px;
    font-size: 14px;
}

.sub-menu a i {
    width: 18px;
    font-size: 14px;
}

/* ============================================
   HEADER ACTIONS
   ============================================ */
.header-actions {
    display: flex;
    align-items: center;
    gap: 6px;
}

.action-link {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 12px;
    border-radius: 10px;
    color: #666;
    font-weight: 500;
    font-size: 14px;
    text-decoration: none;
    transition: all 0.3s;
    position: relative;
}

.action-link:hover {
    background: #f5f5f5;
    color: #222;
}

.action-link i {
    font-size: 18px;
}

.action-link .badge {
    position: absolute;
    top: -4px;
    right: -4px;
    background: #ff3366;
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 50px;
    min-width: 18px;
    text-align: center;
}

.cart-link .badge {
    background: #ffd400;
    color: #111;
}

.mobile-menu-btn {
    display: none;
    background: none;
    border: none;
    font-size: 24px;
    color: #222;
    cursor: pointer;
    padding: 8px;
}

/* ============================================
   MOBİL MENÜ
   ============================================ */
.mobile-menu-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    z-index: 1000;
    display: none;
}

.mobile-menu-overlay.active {
    display: block;
}

.mobile-menu {
    position: fixed;
    top: 0;
    right: -100%;
    width: 300px;
    height: 100%;
    background: #fff;
    z-index: 1001;
    transition: all 0.3s ease;
    box-shadow: -10px 0 40px rgba(0, 0, 0, 0.15);
    overflow-y: auto;
    padding: 24px 20px;
}

.mobile-menu.active {
    right: 0;
}

.mobile-menu-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 16px;
    border-bottom: 2px solid #f5f5f5;
    margin-bottom: 16px;
}

.mobile-logo {
    font-size: 20px;
    font-weight: 800;
    display: flex;
    align-items: center;
    gap: 10px;
}

.mobile-logo i {
    background: #ffd400;
    padding: 8px;
    border-radius: 10px;
    font-size: 16px;
    color: #111;
}

.mobile-menu-close {
    background: #f5f5f5;
    border: none;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    font-size: 20px;
    color: #222;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.mobile-menu-close:hover {
    background: #ff3366;
    color: #fff;
    transform: rotate(90deg);
}

.mobile-nav {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.mobile-nav a {
    padding: 12px 16px;
    border-radius: 10px;
    font-weight: 500;
    color: #222;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
    font-size: 14px;
}

.mobile-nav a:hover {
    background: #f5f5f5;
    color: #6f2cff;
}

.mobile-nav a i {
    width: 22px;
    font-size: 16px;
    color: #999;
}

.mobile-nav a .new-badge {
    background: #ff3366;
    color: #fff;
    padding: 2px 8px;
    border-radius: 50px;
    font-size: 10px;
    font-weight: 700;
}

.mobile-nav hr {
    border: none;
    border-top: 2px solid #f5f5f5;
    margin: 6px 0;
}

/* ============================================
   RESPONSIVE
   ============================================ */

/* Tablet ve altı */
@media (max-width: 992px) {
    .search-box {
        display: none;
    }
    
    .menu {
        display: none;
    }
    
    .mobile-menu-btn {
        display: block;
    }
    
    .header-actions .action-label {
        display: none;
    }
    
    .header-actions .action-link {
        padding: 8px 10px;
    }
    
    .category-dropdown .category-menu-dropdown {
        display: none !important;
    }
    
    .category-dropdown:hover .category-menu-dropdown {
        display: none !important;
    }
    
    .sub-menu {
        position: static;
        box-shadow: none;
        border: none;
        padding-left: 20px;
        display: block !important;
        background: transparent;
    }
    
    .category-item.has-sub .sub-menu {
        display: block !important;
    }
    
    .category-item.has-sub a .sub-arrow {
        display: none;
    }
}

/* Telefon (480px ve altı) */
@media (max-width: 480px) {
    .topbar-left {
        font-size: 11px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .topbar-right {
        display: none;
    }
    
    .logo {
        font-size: 20px;
        gap: 6px;
    }
    
    .logo-icon {
        width: 36px;
        height: 36px;
        border-radius: 10px;
    }
    
    .logo-icon i {
        font-size: 16px;
    }
    
    .logo span {
        font-size: 18px;
    }
    
    .header-actions {
        gap: 2px;
    }
    
    .action-link {
        padding: 6px 8px;
    }
    
    .action-link i {
        font-size: 16px;
    }
    
    .action-link .badge {
        font-size: 9px;
        padding: 1px 6px;
        min-width: 16px;
        top: -2px;
        right: -2px;
    }
    
    .mobile-menu-btn {
        font-size: 20px;
        padding: 6px;
    }
    
    .mobile-menu {
        width: 280px;
        padding: 16px 14px;
    }
    
    .mobile-logo {
        font-size: 18px;
    }
    
    .mobile-nav a {
        padding: 10px 14px;
        font-size: 13px;
    }
    
    .mobile-nav a i {
        width: 20px;
        font-size: 14px;
    }
}

/* Çok küçük telefonlar (380px ve altı) */
@media (max-width: 380px) {
    .logo {
        font-size: 16px;
    }
    
    .logo-icon {
        width: 30px;
        height: 30px;
        border-radius: 8px;
    }
    
    .logo-icon i {
        font-size: 14px;
    }
    
    .logo span {
        font-size: 14px;
    }
    
    .action-link {
        padding: 4px 6px;
    }
    
    .action-link i {
        font-size: 14px;
    }
    
    .mobile-menu-btn {
        font-size: 18px;
        padding: 4px;
    }
    
    .mobile-menu {
        width: 260px;
        padding: 12px;
    }
}
</style>

<!-- ============================================ -->
<!-- JAVASCRIPT -->
<!-- ============================================ -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // ============================================
    // MOBİL MENÜ
    // ============================================
    const menuBtn = document.getElementById('mobileMenuBtn');
    const menuClose = document.getElementById('mobileMenuClose');
    const menuOverlay = document.getElementById('mobileMenuOverlay');
    const mobileMenu = document.getElementById('mobileMenu');
    
    function openMenu() {
        mobileMenu.classList.add('active');
        menuOverlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
    function closeMenu() {
        mobileMenu.classList.remove('active');
        menuOverlay.classList.remove('active');
        document.body.style.overflow = '';
    }
    
    if (menuBtn) {
        menuBtn.addEventListener('click', openMenu);
    }
    if (menuClose) {
        menuClose.addEventListener('click', closeMenu);
    }
    if (menuOverlay) {
        menuOverlay.addEventListener('click', closeMenu);
    }
    
    // ============================================
    // SEARCH SUBMIT
    // ============================================
    window.searchSubmit = function() {
        const input = document.getElementById('searchInput');
        const query = input.value.trim();
        if (query.length > 0) {
            window.location.href = '<?php echo SITE_URL; ?>public/ara.php?q=' + encodeURIComponent(query);
        }
    };
    
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                searchSubmit();
            }
        });
    }
    
    // ============================================
    // LIVE SEARCH
    // ============================================
    const searchResults = document.getElementById('searchResults');
    let searchTimeout;
    
    if (searchInput && searchResults) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            
            if (query.length < 2) {
                searchResults.classList.remove('active');
                searchResults.innerHTML = '<div class="search-empty">🔍 En az 2 karakter girin...</div>';
                return;
            }
            
            searchResults.innerHTML = '<div class="search-empty">⏳ Aranıyor...</div>';
            searchResults.classList.add('active');
            
            searchTimeout = setTimeout(function() {
                fetch('<?php echo SITE_URL; ?>public/live-search.php?q=' + encodeURIComponent(query))
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            searchResults.innerHTML = '<div class="search-empty">❌ Bir hata oluştu</div>';
                            return;
                        }
                        
                        if (data.length > 0) {
                            let html = '';
                            data.forEach(function(item) {
                                let imgSrc = '';
                                if (item.resim_url) {
                                    if (item.resim_url.startsWith('http')) {
                                        imgSrc = item.resim_url;
                                    } else if (item.resim_url.startsWith('uploads/')) {
                                        imgSrc = '<?php echo SITE_URL; ?>' + item.resim_url;
                                    } else {
                                        imgSrc = '<?php echo SITE_URL; ?>uploads/' + item.resim_url;
                                    }
                                }
                                
                                html += '<a href="<?php echo SITE_URL; ?>public/urun-detay.php?slug=' + item.slug + '" class="search-item">';
                                html += '<div class="search-item-image">';
                                if (imgSrc) {
                                    html += '<img src="' + imgSrc + '" alt="' + item.ad + '">';
                                } else {
                                    html += '<i class="fa-solid fa-image"></i>';
                                }
                                html += '</div>';
                                html += '<div class="search-item-info">';
                                html += '<div class="search-item-name">' + item.ad + '</div>';
                                html += '<div class="search-item-price">' + Number(item.fiyat).toLocaleString('tr-TR') + ' ₺</div>';
                                html += '</div>';
                                html += '</a>';
                            });
                            searchResults.innerHTML = html;
                            searchResults.classList.add('active');
                        } else {
                            searchResults.innerHTML = '<div class="search-empty">😕 Sonuç bulunamadı</div>';
                            searchResults.classList.add('active');
                        }
                    })
                    .catch(function(error) {
                        console.error('Search error:', error);
                        searchResults.innerHTML = '<div class="search-empty">❌ Bağlantı hatası</div>';
                        searchResults.classList.add('active');
                    });
            }, 400);
        });
        
        document.addEventListener('click', function(e) {
            const searchBox = document.querySelector('.search-box');
            if (searchBox && !searchBox.contains(e.target)) {
                searchResults.classList.remove('active');
            }
        });
    }
    
});
</script>