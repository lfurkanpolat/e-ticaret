<?php
// =====================================================
// SİTE AYARLARI - admin/site-ayarlari.php
// =====================================================

// =====================================================
// NORMAL POST İŞLEMLERİ
// =====================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ayarlari_kaydet'])) {
    require_once '../includes/config.php';
    
    // Tüm ayarları al ve güncelle
    $ayarlar = [
        'site_title' => clean($_POST['site_title']),
        'site_desc' => clean($_POST['site_desc']),
        'site_keywords' => clean($_POST['site_keywords']),
        'isletme_adi' => clean($_POST['isletme_adi']),
        'isletme_adres' => clean($_POST['isletme_adres']),
        'isletme_telefon' => clean($_POST['isletme_telefon']),
        'isletme_email' => clean($_POST['isletme_email']),
        'isletme_harita' => clean($_POST['isletme_harita']),
        'facebook_url' => clean($_POST['facebook_url']),
        'twitter_url' => clean($_POST['twitter_url']),
        'instagram_url' => clean($_POST['instagram_url']),
        'youtube_url' => clean($_POST['youtube_url']),
        'whatsapp_url' => clean($_POST['whatsapp_url']),
        'footer_copyright' => clean($_POST['footer_copyright']),
        'footer_about' => clean($_POST['footer_about']),
        'smtp_host' => clean($_POST['smtp_host']),
        'smtp_port' => clean($_POST['smtp_port']),
        'smtp_username' => clean($_POST['smtp_username']),
        'smtp_password' => clean($_POST['smtp_password']),
        'smtp_encryption' => clean($_POST['smtp_encryption']),
        'site_durum' => clean($_POST['site_durum']),
        'bakim_mesaji' => clean($_POST['bakim_mesaji'])
    ];
    
    // Logo yükleme
    if (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] == 0) {
        $upload_dir = '../uploads/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_name = $_FILES['site_logo']['name'];
        $file_size = $_FILES['site_logo']['size'];
        $file_tmp = $_FILES['site_logo']['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
        
        if (in_array($file_ext, $allowed_ext) && $file_size <= 2 * 1024 * 1024) {
            $logo_url = 'logo.' . $file_ext;
            $upload_path = $upload_dir . $logo_url;
            
            // Eski logoyu sil
            if (file_exists($upload_path)) {
                unlink($upload_path);
            }
            
            move_uploaded_file($file_tmp, $upload_path);
            $ayarlar['site_logo'] = $logo_url;
        }
    }
    
    // Favicon yükleme
    if (isset($_FILES['site_favicon']) && $_FILES['site_favicon']['error'] == 0) {
        $upload_dir = '../uploads/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_name = $_FILES['site_favicon']['name'];
        $file_size = $_FILES['site_favicon']['size'];
        $file_tmp = $_FILES['site_favicon']['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        $allowed_ext = ['ico', 'png', 'jpg', 'jpeg', 'gif'];
        
        if (in_array($file_ext, $allowed_ext) && $file_size <= 1 * 1024 * 1024) {
            $favicon_url = 'favicon.' . $file_ext;
            $upload_path = $upload_dir . $favicon_url;
            
            if (file_exists($upload_path)) {
                unlink($upload_path);
            }
            
            move_uploaded_file($file_tmp, $upload_path);
            $ayarlar['site_favicon'] = $favicon_url;
        }
    }
    
    try {
        // Mevcut ayarları güncelle (tek satır)
        $sql = "UPDATE site_ayarlari SET 
                    site_title = ?, site_desc = ?, site_keywords = ?, 
                    site_logo = ?, site_favicon = ?,
                    isletme_adi = ?, isletme_adres = ?, isletme_telefon = ?, isletme_email = ?, isletme_harita = ?,
                    facebook_url = ?, twitter_url = ?, instagram_url = ?, youtube_url = ?, whatsapp_url = ?,
                    footer_copyright = ?, footer_about = ?,
                    smtp_host = ?, smtp_port = ?, smtp_username = ?, smtp_password = ?, smtp_encryption = ?,
                    site_durum = ?, bakim_mesaji = ?,
                    guncelleme_tarihi = NOW()
                WHERE id = 1";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $ayarlar['site_title'],
            $ayarlar['site_desc'],
            $ayarlar['site_keywords'],
            $ayarlar['site_logo'] ?? '',
            $ayarlar['site_favicon'] ?? '',
            $ayarlar['isletme_adi'],
            $ayarlar['isletme_adres'],
            $ayarlar['isletme_telefon'],
            $ayarlar['isletme_email'],
            $ayarlar['isletme_harita'],
            $ayarlar['facebook_url'],
            $ayarlar['twitter_url'],
            $ayarlar['instagram_url'],
            $ayarlar['youtube_url'],
            $ayarlar['whatsapp_url'],
            $ayarlar['footer_copyright'],
            $ayarlar['footer_about'],
            $ayarlar['smtp_host'],
            $ayarlar['smtp_port'],
            $ayarlar['smtp_username'],
            $ayarlar['smtp_password'],
            $ayarlar['smtp_encryption'],
            $ayarlar['site_durum'],
            $ayarlar['bakim_mesaji']
        ]);
        
        $_SESSION['success'] = 'Site ayarları başarıyla güncellendi!';
        header("Location: " . SITE_URL . "admin/site-ayarlari.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Veritabanı hatası: ' . $e->getMessage();
        header("Location: " . SITE_URL . "admin/site-ayarlari.php");
        exit();
    }
}

// =====================================================
// NORMAL SAYFA
// =====================================================
$page_title = 'Site Ayarları';
include 'header.php';

// Session mesajlarını al ve temizle
$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['error']);
unset($_SESSION['success']);

// =====================================================
// SİTE AYARLARINI ÇEK
// =====================================================
try {
    $stmt = $db->query("SELECT * FROM site_ayarlari WHERE id = 1");
    $ayar = $stmt->fetch();
    
    // Eğer kayıt yoksa boş array oluştur
    if (!$ayar) {
        $ayar = [];
    }
} catch (PDOException $e) {
    $ayar = [];
    $error = 'Veritabanı hatası: ' . $e->getMessage();
}

// Site durumları
$site_durumlari = [
    'aktif' => 'Aktif',
    'bakim' => 'Bakım Modu'
];

// SMTP şifreleme seçenekleri
$smtp_encryption_options = [
    'tls' => 'TLS',
    'ssl' => 'SSL',
    'none' => 'Yok'
];
?>

<!-- ============================================ -->
<!-- SAYFA BAŞLIĞI -->
<!-- ============================================ -->
<div class="page-header">
    <div class="page-header-left">
        <h2><i class="fas fa-cog"></i> Site Ayarları</h2>
    </div>
</div>

<!-- ============================================ -->
<!-- HATA VE BAŞARI MESAJLARI -->
<!-- ============================================ -->
<?php if ($error): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle"></i>
        <?php echo $error; ?>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <?php echo $success; ?>
    </div>
<?php endif; ?>

<!-- ============================================ -->
<!-- SİTE AYARLARI FORM -->
<!-- ============================================ -->
<div class="form-card">
    <form method="POST" action="" enctype="multipart/form-data" class="settings-form">
        
        <!-- SİTE BİLGİLERİ -->
        <h3 class="form-section-title">
            <i class="fas fa-globe"></i> Site Bilgileri
        </h3>
        
        <div class="form-row">
            <div class="form-group">
                <label>Site Başlığı</label>
                <input type="text" name="site_title" value="<?php echo $ayar['site_title'] ?? 'E-Ticaret Sitesi'; ?>">
            </div>
            <div class="form-group">
                <label>Site Açıklaması</label>
                <input type="text" name="site_desc" value="<?php echo $ayar['site_desc'] ?? ''; ?>">
            </div>
        </div>
        
        <div class="form-group">
            <label>Site Anahtar Kelimeleri</label>
            <input type="text" name="site_keywords" value="<?php echo $ayar['site_keywords'] ?? ''; ?>">
            <small class="form-hint">Virgülle ayırarak yazın. Örn: e-ticaret, alışveriş, online satış</small>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Site Logosu</label>
                <input type="file" name="site_logo" accept="image/*">
                <small class="form-hint">Önerilen boyut: 200x60px. Maksimum 2MB.</small>
                <?php if (!empty($ayar['site_logo'])): ?>
                    <div style="margin-top:8px;">
                        <img src="<?php echo SITE_URL; ?>uploads/<?php echo $ayar['site_logo']; ?>" 
                             style="max-height:60px; border-radius:4px; border:1px solid var(--light-gray); padding:4px;">
                        <br>
                        <small style="color: var(--gray);">Mevcut logo</small>
                    </div>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label>Site Favicon</label>
                <input type="file" name="site_favicon" accept="image/*">
                <small class="form-hint">Önerilen boyut: 32x32px. Maksimum 1MB.</small>
                <?php if (!empty($ayar['site_favicon'])): ?>
                    <div style="margin-top:8px;">
                        <img src="<?php echo SITE_URL; ?>uploads/<?php echo $ayar['site_favicon']; ?>" 
                             style="width:32px; height:32px; border-radius:4px; border:1px solid var(--light-gray); padding:2px;">
                        <br>
                        <small style="color: var(--gray);">Mevcut favicon</small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- İLETİŞİM BİLGİLERİ -->
        <h3 class="form-section-title" style="margin-top:30px;">
            <i class="fas fa-address-book"></i> İletişim Bilgileri
        </h3>
        
        <div class="form-row">
            <div class="form-group">
                <label>İşletme Adı</label>
                <input type="text" name="isletme_adi" value="<?php echo $ayar['isletme_adi'] ?? ''; ?>">
            </div>
            <div class="form-group">
                <label>Telefon</label>
                <input type="text" name="isletme_telefon" value="<?php echo $ayar['isletme_telefon'] ?? ''; ?>">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>E-posta</label>
                <input type="email" name="isletme_email" value="<?php echo $ayar['isletme_email'] ?? ''; ?>">
            </div>
            <div class="form-group">
                <label>Google Maps Embed URL</label>
                <input type="text" name="isletme_harita" value="<?php echo $ayar['isletme_harita'] ?? ''; ?>">
                <small class="form-hint">Google Maps'ten aldığınız embed kodunun src kısmı.</small>
            </div>
        </div>
        
        <div class="form-group">
            <label>Adres</label>
            <textarea name="isletme_adres" rows="3"><?php echo $ayar['isletme_adres'] ?? ''; ?></textarea>
        </div>
        
        <!-- SOSYAL MEDYA -->
        <h3 class="form-section-title" style="margin-top:30px;">
            <i class="fas fa-share-alt"></i> Sosyal Medya
        </h3>
        
        <div class="form-row">
            <div class="form-group">
                <label><i class="fab fa-facebook" style="color:#1877F2;"></i> Facebook</label>
                <input type="url" name="facebook_url" value="<?php echo $ayar['facebook_url'] ?? ''; ?>" placeholder="https://facebook.com/...">
            </div>
            <div class="form-group">
                <label><i class="fab fa-twitter" style="color:#1DA1F2;"></i> Twitter</label>
                <input type="url" name="twitter_url" value="<?php echo $ayar['twitter_url'] ?? ''; ?>" placeholder="https://twitter.com/...">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label><i class="fab fa-instagram" style="color:#E4405F;"></i> Instagram</label>
                <input type="url" name="instagram_url" value="<?php echo $ayar['instagram_url'] ?? ''; ?>" placeholder="https://instagram.com/...">
            </div>
            <div class="form-group">
                <label><i class="fab fa-youtube" style="color:#FF0000;"></i> YouTube</label>
                <input type="url" name="youtube_url" value="<?php echo $ayar['youtube_url'] ?? ''; ?>" placeholder="https://youtube.com/...">
            </div>
        </div>
        
        <div class="form-group">
            <label><i class="fab fa-whatsapp" style="color:#25D366;"></i> WhatsApp</label>
            <input type="url" name="whatsapp_url" value="<?php echo $ayar['whatsapp_url'] ?? ''; ?>" placeholder="https://wa.me/905551234567">
        </div>
        
        <!-- FOOTER -->
        <h3 class="form-section-title" style="margin-top:30px;">
            <i class="fas fa-copyright"></i> Footer Ayarları
        </h3>
        
        <div class="form-group">
            <label>Footer Copyright Metni</label>
            <input type="text" name="footer_copyright" value="<?php echo $ayar['footer_copyright'] ?? 'Tüm hakları saklıdır.'; ?>">
        </div>
        
        <div class="form-group">
            <label>Footer Hakkımızda Metni</label>
            <textarea name="footer_about" rows="3"><?php echo $ayar['footer_about'] ?? ''; ?></textarea>
        </div>
        
        <!-- SMTP AYARLARI -->
        <h3 class="form-section-title" style="margin-top:30px;">
            <i class="fas fa-envelope"></i> SMTP Ayarları (Mail Gönderimi)
        </h3>
        
        <div class="form-row">
            <div class="form-group">
                <label>SMTP Host</label>
                <input type="text" name="smtp_host" value="<?php echo $ayar['smtp_host'] ?? 'smtp.gmail.com'; ?>">
            </div>
            <div class="form-group">
                <label>SMTP Port</label>
                <input type="text" name="smtp_port" value="<?php echo $ayar['smtp_port'] ?? '587'; ?>">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>SMTP Kullanıcı Adı</label>
                <input type="text" name="smtp_username" value="<?php echo $ayar['smtp_username'] ?? ''; ?>">
            </div>
            <div class="form-group">
                <label>SMTP Şifre</label>
                <input type="password" name="smtp_password" value="<?php echo $ayar['smtp_password'] ?? ''; ?>">
                <small class="form-hint">Boş bırakırsanız mevcut şifre korunur.</small>
            </div>
        </div>
        
        <div class="form-group">
            <label>SMTP Şifreleme</label>
            <select name="smtp_encryption">
                <?php foreach ($smtp_encryption_options as $key => $value): ?>
                    <option value="<?php echo $key; ?>" <?php echo ($ayar['smtp_encryption'] ?? 'tls') == $key ? 'selected' : ''; ?>>
                        <?php echo $value; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <!-- SİTE DURUMU -->
        <h3 class="form-section-title" style="margin-top:30px;">
            <i class="fas fa-power-off"></i> Site Durumu
        </h3>
        
        <div class="form-row">
            <div class="form-group">
                <label>Site Durumu</label>
                <select name="site_durum">
                    <?php foreach ($site_durumlari as $key => $value): ?>
                        <option value="<?php echo $key; ?>" <?php echo ($ayar['site_durum'] ?? 'aktif') == $key ? 'selected' : ''; ?>>
                            <?php echo $value; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="form-hint">Bakım modunda site ziyaretçilere kapatılır.</small>
            </div>
            <div class="form-group">
                <label>Bakım Mesajı</label>
                <input type="text" name="bakim_mesaji" value="<?php echo $ayar['bakim_mesaji'] ?? 'Sitemiz bakımda, en kısa sürede hizmetinizdeyiz.'; ?>">
            </div>
        </div>
        
        <!-- FORM BUTTONLARI -->
        <div class="form-actions">
            <button type="submit" name="ayarlari_kaydet" class="btn btn-primary">
                <i class="fas fa-save"></i> Ayarları Kaydet
            </button>
        </div>
        
    </form>
</div>



<?php
include 'footer.php';
?>