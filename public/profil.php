<?php
// =====================================================
// KULLANICI PROFİLİ - public/profil.php
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
// KULLANICI BİLGİLERİNİ ÇEK
// =====================================================
try {
    $stmt = $db->prepare("
        SELECT * FROM kullanicilar WHERE id = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    $user = null;
    $error = 'Kullanıcı bilgileri alınamadı!';
}

// =====================================================
// ADRES BİLGİLERİNİ ÇEK
// =====================================================
try {
    $stmt = $db->prepare("
        SELECT * FROM kullanici_adresleri WHERE kullanici_id = ? ORDER BY varsayilan DESC, id DESC
    ");
    $stmt->execute([$user_id]);
    $adresler = $stmt->fetchAll();
} catch (PDOException $e) {
    $adresler = [];
}

// =====================================================
// PROFİL GÜNCELLEME
// =====================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['profile_update'])) {
    $ad = clean($_POST['ad']);
    $soyad = clean($_POST['soyad']);
    $telefon = clean($_POST['telefon']);
    
    $errors = [];
    if (empty($ad)) $errors[] = 'Ad boş olamaz.';
    if (empty($soyad)) $errors[] = 'Soyad boş olamaz.';
    
    if (empty($errors)) {
        try {
            $stmt = $db->prepare("
                UPDATE kullanicilar SET 
                    ad = ?, soyad = ?, telefon = ? 
                WHERE id = ?
            ");
            $stmt->execute([$ad, $soyad, $telefon, $user_id]);
            
            $_SESSION['user_ad'] = $ad;
            $_SESSION['user_soyad'] = $soyad;
            
            $success = 'Profil bilgileriniz güncellendi!';
            
            // Verileri yeniden çek
            $stmt = $db->prepare("SELECT * FROM kullanicilar WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
        } catch (PDOException $e) {
            $error = 'Güncelleme hatası: ' . $e->getMessage();
        }
    } else {
        $error = implode('<br>', $errors);
    }
}

// =============================================
// ŞİFRE DEĞİŞTİR
// =============================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['password_update'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    $errors = [];
    
    if (empty($current_password)) $errors[] = 'Mevcut şifrenizi girin.';
    if (empty($new_password)) $errors[] = 'Yeni şifre boş olamaz.';
    if (strlen($new_password) < 6) $errors[] = 'Yeni şifre en az 6 karakter olmalıdır.';
    if ($new_password !== $confirm_password) $errors[] = 'Şifreler eşleşmiyor.';
    
    if (empty($errors)) {
        // Mevcut şifreyi kontrol et
        if (password_verify($current_password, $user['sifre'])) {
            try {
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE kullanicilar SET sifre = ? WHERE id = ?");
                $stmt->execute([$new_hash, $user_id]);
                
                $success = 'Şifreniz başarıyla değiştirildi!';
            } catch (PDOException $e) {
                $error = 'Şifre güncelleme hatası: ' . $e->getMessage();
            }
        } else {
            $error = 'Mevcut şifreniz yanlış!';
        }
    } else {
        $error = implode('<br>', $errors);
    }
}

// =============================================
// ADRES EKLE
// =============================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['address_add'])) {
    $adres_basligi = clean($_POST['adres_basligi']);
    $ad = clean($_POST['ad']);
    $soyad = clean($_POST['soyad']);
    $telefon = clean($_POST['telefon']);
    $il = clean($_POST['il']);
    $ilce = clean($_POST['ilce']);
    $acik_adres = clean($_POST['acik_adres']);
    $varsayilan = isset($_POST['varsayilan']) ? 1 : 0;
    
    $errors = [];
    if (empty($adres_basligi)) $errors[] = 'Adres başlığı boş olamaz.';
    if (empty($ad)) $errors[] = 'Ad boş olamaz.';
    if (empty($soyad)) $errors[] = 'Soyad boş olamaz.';
    if (empty($telefon)) $errors[] = 'Telefon boş olamaz.';
    if (empty($il)) $errors[] = 'İl boş olamaz.';
    if (empty($ilce)) $errors[] = 'İlçe boş olamaz.';
    if (empty($acik_adres)) $errors[] = 'Açık adres boş olamaz.';
    
    if (empty($errors)) {
        try {
            // Varsayılan adres seçilmişse diğerlerini kaldır
            if ($varsayilan == 1) {
                $db->query("UPDATE kullanici_adresleri SET varsayilan = 0 WHERE kullanici_id = $user_id");
            }
            
            $stmt = $db->prepare("
                INSERT INTO kullanici_adresleri (
                    kullanici_id, adres_basligi, ad, soyad, telefon, 
                    il, ilce, acik_adres, varsayilan
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $user_id, $adres_basligi, $ad, $soyad, $telefon,
                $il, $ilce, $acik_adres, $varsayilan
            ]);
            
            $success = 'Adres başarıyla eklendi!';
            
            // Adresleri yeniden çek
            $stmt = $db->prepare("SELECT * FROM kullanici_adresleri WHERE kullanici_id = ? ORDER BY varsayilan DESC, id DESC");
            $stmt->execute([$user_id]);
            $adresler = $stmt->fetchAll();
            
        } catch (PDOException $e) {
            $error = 'Adres ekleme hatası: ' . $e->getMessage();
        }
    } else {
        $error = implode('<br>', $errors);
    }
}

// =============================================
// ADRES SİL
// =============================================
if (isset($_GET['adres_sil']) && is_numeric($_GET['adres_sil'])) {
    $adres_id = (int)$_GET['adres_sil'];
    
    try {
        $stmt = $db->prepare("DELETE FROM kullanici_adresleri WHERE id = ? AND kullanici_id = ?");
        $stmt->execute([$adres_id, $user_id]);
        
        $success = 'Adres silindi!';
        
        // Adresleri yeniden çek
        $stmt = $db->prepare("SELECT * FROM kullanici_adresleri WHERE kullanici_id = ? ORDER BY varsayilan DESC, id DESC");
        $stmt->execute([$user_id]);
        $adresler = $stmt->fetchAll();
        
    } catch (PDOException $e) {
        $error = 'Adres silme hatası: ' . $e->getMessage();
    }
}

// =============================================
// VARSAYILAN ADRES DEĞİŞTİR
// =============================================
if (isset($_GET['varsayilan_yap']) && is_numeric($_GET['varsayilan_yap'])) {
    $adres_id = (int)$_GET['varsayilan_yap'];
    
    try {
        $db->query("UPDATE kullanici_adresleri SET varsayilan = 0 WHERE kullanici_id = $user_id");
        $db->query("UPDATE kullanici_adresleri SET varsayilan = 1 WHERE id = $adres_id AND kullanici_id = $user_id");
        
        $success = 'Varsayılan adres güncellendi!';
        
        // Adresleri yeniden çek
        $stmt = $db->prepare("SELECT * FROM kullanici_adresleri WHERE kullanici_id = ? ORDER BY varsayilan DESC, id DESC");
        $stmt->execute([$user_id]);
        $adresler = $stmt->fetchAll();
        
    } catch (PDOException $e) {
        $error = 'Adres güncelleme hatası: ' . $e->getMessage();
    }
}

// =============================================
// HEADER'I DAHİL ET
// =============================================
include '../includes/header.php';
?>

<!-- ============================================ -->
<!-- PROFİL SAYFASI -->
<!-- ============================================ -->
<section class="profile-section">
    <div class="container">
        <div class="profile-wrapper">
            
            <!-- Sol Menü -->
            <aside class="profile-sidebar">
                <div class="profile-avatar">
                    <div class="avatar-circle">
                        <?php 
                        $bas_harf = strtoupper(mb_substr($user['ad'] ?? 'K', 0, 1));
                        echo $bas_harf;
                        ?>
                    </div>
                    <h3><?php echo $user['ad'] . ' ' . $user['soyad']; ?></h3>
                    <p><?php echo $user['email']; ?></p>
                </div>
                <nav class="profile-menu">
                    <a href="#bilgiler" class="active">
                        <i class="fa-regular fa-user"></i>
                        Profil Bilgileri
                    </a>
                    <a href="#adresler">
                        <i class="fa-regular fa-address-book"></i>
                        Adreslerim
                    </a>
                    <a href="#sifre">
                        <i class="fa-solid fa-lock"></i>
                        Şifre Değiştir
                    </a>
                    <a href="<?php echo SITE_URL; ?>public/siparislerim.php">
                        <i class="fa-solid fa-box"></i>
                        Siparişlerim
                    </a>
                    <a href="<?php echo SITE_URL; ?>public/favoriler.php">
                        <i class="fa-regular fa-heart"></i>
                        Favorilerim
                    </a>
                    <a href="<?php echo SITE_URL; ?>public/cikis.php" style="color:#ff3366;">
                        <i class="fa-solid fa-sign-out-alt"></i>
                        Çıkış Yap
                    </a>
                </nav>
            </aside>
            
            <!-- Sağ İçerik -->
            <div class="profile-content">
                
                <!-- Hata ve Başarı Mesajları -->
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <!-- ========================================== -->
                <!-- PROFİL BİLGİLERİ -->
                <!-- ========================================== -->
                <div class="profile-card" id="bilgiler">
                    <h2><i class="fa-regular fa-user"></i> Profil Bilgileri</h2>
                    <form method="POST" action="">
                        <input type="hidden" name="profile_update" value="1">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Ad</label>
                                <input type="text" name="ad" value="<?php echo $user['ad'] ?? ''; ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Soyad</label>
                                <input type="text" name="soyad" value="<?php echo $user['soyad'] ?? ''; ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>E-posta</label>
                                <input type="email" value="<?php echo $user['email'] ?? ''; ?>" disabled>
                                <small class="form-hint">E-posta adresi değiştirilemez.</small>
                            </div>
                            <div class="form-group">
                                <label>Telefon</label>
                                <input type="text" name="telefon" value="<?php echo $user['telefon'] ?? ''; ?>" placeholder="05XX XXX XX XX">
                            </div>
                        </div>
                        
                        <button type="submit" class="btn-profil">Bilgileri Güncelle</button>
                    </form>
                </div>
                
                <!-- ========================================== -->
                <!-- ŞİFRE DEĞİŞTİR -->
                <!-- ========================================== -->
                <div class="profile-card" id="sifre">
                    <h2><i class="fa-solid fa-lock"></i> Şifre Değiştir</h2>
                    <form method="POST" action="">
                        <input type="hidden" name="password_update" value="1">
                        
                        <div class="form-group">
                            <label>Mevcut Şifre</label>
                            <input type="password" name="current_password" placeholder="Mevcut şifrenizi girin" required>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Yeni Şifre</label>
                                <input type="password" name="new_password" placeholder="En az 6 karakter" required>
                            </div>
                            <div class="form-group">
                                <label>Yeni Şifre Tekrar</label>
                                <input type="password" name="confirm_password" placeholder="Yeni şifreyi tekrar girin" required>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn-profil">Şifreyi Değiştir</button>
                    </form>
                </div>
                
                <!-- ========================================== -->
                <!-- ADRESLERİM -->
                <!-- ========================================== -->
                <div class="profile-card" id="adresler">
                    <h2><i class="fa-regular fa-address-book"></i> Adreslerim</h2>
                    
                    <!-- Adres Listesi -->
                    <?php if (!empty($adresler)): ?>
                        <div class="address-grid">
                            <?php foreach ($adresler as $adres): ?>
                                <div class="address-card">
                                    <div class="address-header">
                                        <h4><?php echo $adres['adres_basligi']; ?></h4>
                                        <?php if ($adres['varsayilan'] == 1): ?>
                                            <span class="address-badge">Varsayılan</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="address-body">
                                        <p><strong><?php echo $adres['ad'] . ' ' . $adres['soyad']; ?></strong></p>
                                        <p><?php echo $adres['telefon']; ?></p>
                                        <p><?php echo $adres['il'] . ' / ' . $adres['ilce']; ?></p>
                                        <p><?php echo $adres['acik_adres']; ?></p>
                                    </div>
                                    <div class="address-actions">
                                        <?php if ($adres['varsayilan'] != 1): ?>
                                            <a href="?varsayilan_yap=<?php echo $adres['id']; ?>" class="btn btn-sm btn-outline">
                                                <i class="fa-solid fa-check"></i> Varsayılan Yap
                                            </a>
                                        <?php endif; ?>
                                        <a href="?adres_sil=<?php echo $adres['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bu adresi silmek istediğinize emin misiniz?')">
                                            <i class="fa-regular fa-trash-can"></i> Sil
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fa-regular fa-address-book"></i>
                            <p>Henüz adres eklenmemiş.</p>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Yeni Adres Ekle -->
                    <div class="address-form-toggle">
                        <button class="btn-profil" onclick="toggleAddressForm()">
                            <i class="fa-solid fa-plus"></i> Yeni Adres Ekle
                        </button>
                    </div>
                    
                    <div class="address-form-wrapper" id="addressForm" style="display:none; margin-top:20px;">
                        <h4>Yeni Adres Ekle</h4>
                        <form method="POST" action="">
                            <input type="hidden" name="address_add" value="1">
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Adres Başlığı</label>
                                    <input type="text" name="adres_basligi" placeholder="Örn: Ev, İş" required>
                                </div>
                                <div class="form-group">
                                    <label>Telefon</label>
                                    <input type="text" name="telefon" placeholder="05XX XXX XX XX" required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Ad</label>
                                    <input type="text" name="ad" placeholder="Adınız" required>
                                </div>
                                <div class="form-group">
                                    <label>Soyad</label>
                                    <input type="text" name="soyad" placeholder="Soyadınız" required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>İl</label>
                                    <input type="text" name="il" placeholder="İl" required>
                                </div>
                                <div class="form-group">
                                    <label>İlçe</label>
                                    <input type="text" name="ilce" placeholder="İlçe" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Açık Adres</label>
                                <textarea name="acik_adres" rows="3" placeholder="Mahalle, sokak, apartman no, daire no..." required></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                                    <input type="checkbox" name="varsayilan" value="1" checked>
                                    <span>Varsayılan adres olarak ayarla</span>
                                </label>
                            </div>
                            
                            <div style="display:flex;gap:10px;margin-top:10px;">
                                <button type="submit" class="btn btn-primary">Adres Ekle</button>
                                <button type="button" class="btn btn-outline" onclick="toggleAddressForm()">İptal</button>
                            </div>
                        </form>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</section>

<!-- ============================================ -->
<!-- JAVASCRIPT -->
<!-- ============================================ -->
<script>
function toggleAddressForm() {
    const form = document.getElementById('addressForm');
    if (form.style.display === 'none') {
        form.style.display = 'block';
    } else {
        form.style.display = 'none';
    }
}
</script>

<?php
// =====================================================
// FOOTER'I DAHİL ET
// =====================================================
include '../includes/footer.php';
?>