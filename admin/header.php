<?php
// =====================================================
// ADMIN HEADER - admin/header.php
// =====================================================

// Config dosyasını dahil et
require_once '../includes/config.php';

// Admin kontrolü - Giriş yapılmadıysa login sayfasına yönlendir
if (!isLoggedIn() || !isAdmin()) {
    header("Location: " . SITE_URL . "admin/login.php");
    exit();
}

// Kullanıcı bilgilerini al
try {
    $stmt = $db->prepare("SELECT * FROM kullanicilar WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $admin_user = $stmt->fetch();
} catch (PDOException $e) {
    $admin_user = null;
}

// Aktif sayfayı belirle
$current_page = basename($_SERVER['PHP_SELF']);

// Dark tema kontrolü (session'da sakla)
if (isset($_POST['toggle_theme'])) {
    if (isset($_SESSION['admin_theme']) && $_SESSION['admin_theme'] == 'dark') {
        $_SESSION['admin_theme'] = 'light';
    } else {
        $_SESSION['admin_theme'] = 'dark';
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

$theme = $_SESSION['admin_theme'] ?? 'light';
$theme_class = $theme == 'dark' ? 'dark-theme' : '';

// CSS versiyon numarası
$css_version = time();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - <?php echo SITE_NAME; ?></title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Admin CSS - Versiyonlu -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/admin-style.css?v=<?php echo $css_version; ?>">
</head>
<body class="<?php echo $theme_class; ?>">
    
    <!-- ============================================ -->
    <!-- ADMIN SIDEBAR -->
    <!-- ============================================ -->
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <i class="fas fa-store"></i>
                <span><?php echo SITE_NAME; ?></span>
            </div>
            <button class="sidebar-toggle" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        
        <nav class="sidebar-nav">
            <ul>
                <!-- ========================================== -->
                <!-- DASHBOARD -->
                <!-- ========================================== -->
                <li>
                    <a href="<?php echo SITE_URL; ?>admin/index.php" class="<?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
                        <i class="fas fa-th-large"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                
                <!-- ========================================== -->
                <!-- ÜRÜN YÖNETİMİ -->
                <!-- ========================================== -->
                <li class="menu-group">
                    <span class="menu-label">Ürün Yönetimi</span>
                </li>
                <li>
                    <a href="<?php echo SITE_URL; ?>admin/urunler.php" class="<?php echo $current_page == 'urunler.php' ? 'active' : ''; ?>">
                        <i class="fas fa-box"></i>
                        <span>Tüm Ürünler</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo SITE_URL; ?>admin/urun-ekle.php" class="<?php echo $current_page == 'urun-ekle.php' ? 'active' : ''; ?>">
                        <i class="fas fa-plus-circle"></i>
                        <span>Ürün Ekle</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo SITE_URL; ?>admin/kategoriler.php" class="<?php echo $current_page == 'kategoriler.php' ? 'active' : ''; ?>">
                        <i class="fas fa-tags"></i>
                        <span>Kategoriler</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo SITE_URL; ?>admin/markalar.php" class="<?php echo $current_page == 'markalar.php' ? 'active' : ''; ?>">
                        <i class="fas fa-copyright"></i>
                        <span>Markalar</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo SITE_URL; ?>admin/varyantlar.php" class="<?php echo $current_page == 'varyantlar.php' ? 'active' : ''; ?>">
                        <i class="fas fa-layer-group"></i>
                        <span>Ürün Varyantları</span>
                    </a>
                </li>
                
                <!-- ========================================== -->
                <!-- SİPARİŞ YÖNETİMİ -->
                <!-- ========================================== -->
                <li class="menu-group">
                    <span class="menu-label">Sipariş Yönetimi</span>
                </li>
                <li>
                    <a href="<?php echo SITE_URL; ?>admin/siparisler.php" class="<?php echo $current_page == 'siparisler.php' ? 'active' : ''; ?>">
                        <i class="fas fa-shopping-cart"></i>
                        <span>Tüm Siparişler</span>
                        <?php
                        // Bekleyen sipariş sayısı
                        try {
                            $stmt = $db->query("SELECT COUNT(*) as sayi FROM siparisler WHERE siparis_durumu = 'hazirlaniyor'");
                            $bekleyen = $stmt->fetch()['sayi'] ?? 0;
                            if ($bekleyen > 0) {
                                echo '<span class="badge">' . $bekleyen . '</span>';
                            }
                        } catch (PDOException $e) {}
                        ?>
                    </a>
                </li>
                <li>
                    <a href="<?php echo SITE_URL; ?>admin/kargo-takip.php" class="<?php echo $current_page == 'kargo-takip.php' ? 'active' : ''; ?>">
                        <i class="fas fa-truck"></i>
                        <span>Kargo Takip</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo SITE_URL; ?>admin/iadeler.php" class="<?php echo $current_page == 'iadeler.php' ? 'active' : ''; ?>">
                        <i class="fas fa-undo-alt"></i>
                        <span>İade Yönetimi</span>
                    </a>
                </li>
                
                <!-- ========================================== -->
                <!-- MÜŞTERİ YÖNETİMİ -->
                <!-- ========================================== -->
                <li class="menu-group">
                    <span class="menu-label">Müşteri Yönetimi</span>
                </li>
                <li>
                    <a href="<?php echo SITE_URL; ?>admin/kullanicilar.php" class="<?php echo $current_page == 'kullanicilar.php' ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i>
                        <span>Tüm Kullanıcılar</span>
                        <?php
                        try {
                            $stmt = $db->query("SELECT COUNT(*) as sayi FROM kullanicilar WHERE durum = 'beklemede'");
                            $bekleyen_kullanici = $stmt->fetch()['sayi'] ?? 0;
                            if ($bekleyen_kullanici > 0) {
                                echo '<span class="badge">' . $bekleyen_kullanici . '</span>';
                            }
                        } catch (PDOException $e) {}
                        ?>
                    </a>
                </li>
                <li>
                    <a href="<?php echo SITE_URL; ?>admin/yorumlar.php" class="<?php echo $current_page == 'yorumlar.php' ? 'active' : ''; ?>">
                        <i class="fas fa-star"></i>
                        <span>Ürün Yorumları</span>
                        <?php
                        try {
                            $stmt = $db->query("SELECT COUNT(*) as sayi FROM urun_yorumlari WHERE durum = 'beklemede'");
                            $bekleyen_yorum = $stmt->fetch()['sayi'] ?? 0;
                            if ($bekleyen_yorum > 0) {
                                echo '<span class="badge">' . $bekleyen_yorum . '</span>';
                            }
                        } catch (PDOException $e) {}
                        ?>
                    </a>
                </li>
                
                <!-- ========================================== -->
                <!-- PAZARLAMA -->
                <!-- ========================================== -->
                <li class="menu-group">
                    <span class="menu-label">Pazarlama</span>
                </li>
                <li>
                    <a href="<?php echo SITE_URL; ?>admin/kampanyalar.php" class="<?php echo $current_page == 'kampanyalar.php' ? 'active' : ''; ?>">
                        <i class="fas fa-gift"></i>
                        <span>Kampanyalar</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo SITE_URL; ?>admin/slider.php" class="<?php echo $current_page == 'slider.php' ? 'active' : ''; ?>">
                        <i class="fas fa-images"></i>
                        <span>Slider Yönetimi</span>
                    </a>
                </li>
          
                
                <!-- ========================================== -->
                <!-- SİTE AYARLARI -->
                <!-- ========================================== -->
                <li class="menu-group">
                    <span class="menu-label">Sistem</span>
                </li>
                <li>
                    <a href="<?php echo SITE_URL; ?>admin/site-ayarlari.php" class="<?php echo $current_page == 'site-ayarlari.php' ? 'active' : ''; ?>">
                        <i class="fas fa-cog"></i>
                        <span>Site Ayarları</span>
                    </a>
                </li>

                <li>
                    <a href="<?php echo SITE_URL; ?>admin/promo-banner.php" class="<?php echo $current_page == 'site-ayarlari.php' ? 'active' : ''; ?>">
                        <i class="fas fa-address-book"></i>
                        <span>İndex Ayaları</span>
                    </a>
                </li>


                <li>
                    <a href="<?php echo SITE_URL; ?>admin/api-ayarlari.php" class="<?php echo $current_page == 'api-ayarlari.php' ? 'active' : ''; ?>">
                        <i class="fas fa-plug"></i>
                        <span>API Ayarları</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo SITE_URL; ?>admin/kargo-ayarlari.php" class="<?php echo $current_page == 'kargo-ayarlari.php' ? 'active' : ''; ?>">
                        <i class="fas fa-shipping-fast"></i>
                        <span>Kargo Ayarları</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo SITE_URL; ?>admin/odeme-ayarlari.php" class="<?php echo $current_page == 'odeme-ayarlari.php' ? 'active' : ''; ?>">
                        <i class="fas fa-credit-card"></i>
                        <span>Ödeme Ayarları</span>
                    </a>
                </li>
                
                <!-- ========================================== -->
                <!-- RAPORLAR -->
                <!-- ========================================== -->
                <li class="menu-group">
                    <span class="menu-label">Raporlar</span>
                </li>
                <li>
                    <a href="<?php echo SITE_URL; ?>admin/satis-raporlari.php" class="<?php echo $current_page == 'satis-raporlari.php' ? 'active' : ''; ?>">
                        <i class="fas fa-chart-bar"></i>
                        <span>Satış Raporları</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo SITE_URL; ?>admin/stok-raporlari.php" class="<?php echo $current_page == 'stok-raporlari.php' ? 'active' : ''; ?>">
                        <i class="fas fa-warehouse"></i>
                        <span>Stok Raporları</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo SITE_URL; ?>admin/loglar.php" class="<?php echo $current_page == 'loglar.php' ? 'active' : ''; ?>">
                        <i class="fas fa-history"></i>
                        <span>Sistem Logları</span>
                    </a>
                </li>
            </ul>
        </nav>
        
        <!-- ========================================== -->
        <!-- SIDEBAR FOOTER -->
        <!-- ========================================== -->
        <div class="sidebar-footer">
            <!-- Dark Theme Toggle -->
            <form method="POST" action="" class="theme-toggle-form">
                <button type="submit" name="toggle_theme" class="theme-toggle-btn" title="<?php echo $theme == 'dark' ? 'Aydınlık Tema' : 'Karanlık Tema'; ?>">
                    <i class="fas <?php echo $theme == 'dark' ? 'fa-sun' : 'fa-moon'; ?>"></i>
                    <span><?php echo $theme == 'dark' ? 'Aydınlık Tema' : 'Karanlık Tema'; ?></span>
                </button>
            </form>
            
            <a href="<?php echo SITE_URL; ?>public/index.php" target="_blank" class="logout-btn" style="color: var(--primary); border-bottom: 1px solid var(--light-gray); margin-bottom: 8px; border-radius: 0;">
                <i class="fas fa-external-link-alt"></i>
                <span>Siteyi Görüntüle</span>
            </a>
            <a href="<?php echo SITE_URL; ?>public/cikis.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                <span>Çıkış Yap</span>
            </a>
        </div>
    </aside>
    
    <!-- ============================================ -->
    <!-- ADMIN MAIN CONTENT -->
    <!-- ============================================ -->
    <main class="admin-main">
        
        <!-- ============================================ -->
        <!-- ADMIN HEADER BAR -->
        <!-- ============================================ -->
        <header class="admin-header">
            <div class="header-left">
                <button class="sidebar-toggle-mobile" id="sidebarToggleMobile">
                    <i class="fas fa-bars"></i>
                </button>
                <h1><?php echo $page_title ?? 'Dashboard'; ?></h1>
            </div>
            
            <div class="header-right">
                <div class="search-box admin-search">
                    <form action="<?php echo SITE_URL; ?>admin/arama.php" method="GET">
                        <input type="text" name="q" placeholder="Ürün, sipariş, kullanıcı ara...">
                        <button type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
                
                <div class="header-actions">
                    <!-- Dark Theme Toggle (Header'da da göster) -->
                    <form method="POST" action="" class="theme-toggle-form-header">
                        <button type="submit" name="toggle_theme" class="header-btn theme-toggle-header" title="<?php echo $theme == 'dark' ? 'Aydınlık Tema' : 'Karanlık Tema'; ?>">
                            <i class="fas <?php echo $theme == 'dark' ? 'fa-sun' : 'fa-moon'; ?>"></i>
                        </button>
                    </form>
                    
                    <button class="header-btn" title="Bildirimler">
                        <i class="fas fa-bell"></i>
                        <?php
                        // Toplam bildirim sayısı
                        $toplam_bildirim = 0;
                        try {
                            $stmt = $db->query("SELECT COUNT(*) as sayi FROM siparisler WHERE siparis_durumu = 'hazirlaniyor'");
                            $toplam_bildirim += $stmt->fetch()['sayi'] ?? 0;
                            
                            $stmt = $db->query("SELECT COUNT(*) as sayi FROM urun_yorumlari WHERE durum = 'beklemede'");
                            $toplam_bildirim += $stmt->fetch()['sayi'] ?? 0;
                            
                            $stmt = $db->query("SELECT COUNT(*) as sayi FROM kullanicilar WHERE durum = 'beklemede'");
                            $toplam_bildirim += $stmt->fetch()['sayi'] ?? 0;
                            
                            $stmt = $db->query("SELECT COUNT(*) as sayi FROM urunler WHERE stok <= kritik_stok AND stok > 0");
                            $toplam_bildirim += $stmt->fetch()['sayi'] ?? 0;
                            
                            if ($toplam_bildirim > 0) {
                                echo '<span class="badge">' . $toplam_bildirim . '</span>';
                            }
                        } catch (PDOException $e) {}
                        ?>
                    </button>
                    <button class="header-btn" title="Mesajlar">
                        <i class="fas fa-envelope"></i>
                    </button>
                    <div class="user-profile">
    <div class="user-avatar">
        <?php 
        $ad = $admin_user['ad'] ?? 'Admin';
        $soyad = $admin_user['soyad'] ?? '';
        $bas_harf = strtoupper(mb_substr($ad, 0, 1));
        if (!empty($soyad)) {
            $bas_harf .= strtoupper(mb_substr($soyad, 0, 1));
        }
        echo $bas_harf;
        ?>
    </div>
    <div class="user-info">
        <span class="user-name"><?php echo $ad . ' ' . $soyad; ?></span>
        <span class="user-role">Administrator</span>
    </div>
</div>
                </div>
            </div>
        </header>
        
        <!-- ============================================ -->
        <!-- PAGE CONTENT START -->
        <!-- ============================================ -->
        <div class="admin-content">