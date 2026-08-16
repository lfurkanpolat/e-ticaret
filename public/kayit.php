<?php
// =====================================================
// KAYIT OL - public/kayit.php
// =====================================================

require_once '../includes/config.php';

// Zaten giriş yapmışsa ana sayfaya yönlendir
if (isLoggedIn()) {
    header("Location: " . SITE_URL . "public/index.php");
    exit();
}

$error = '';
$success = '';
$form_data = [];

// =====================================================
// KAYIT İŞLEMİ
// =====================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['kayit_ol'])) {
    
    // Form verilerini al
    $ad = clean($_POST['ad']);
    $soyad = clean($_POST['soyad']);
    $email = clean($_POST['email']);
    $telefon = clean($_POST['telefon']);
    $sifre = $_POST['sifre'];
    $sifre_tekrar = $_POST['sifre_tekrar'];
    $kullanici_sozlesmesi = isset($_POST['kullanici_sozlesmesi']) ? true : false;
    
    // Form verilerini sakla (hata durumunda geri doldurmak için)
    $form_data = [
        'ad' => $ad,
        'soyad' => $soyad,
        'email' => $email,
        'telefon' => $telefon
    ];
    
    // Validasyon
    $errors = [];
    
    if (empty($ad)) {
        $errors[] = 'Ad boş olamaz.';
    } elseif (strlen($ad) < 2) {
        $errors[] = 'Ad en az 2 karakter olmalıdır.';
    }
    
    if (empty($soyad)) {
        $errors[] = 'Soyad boş olamaz.';
    } elseif (strlen($soyad) < 2) {
        $errors[] = 'Soyad en az 2 karakter olmalıdır.';
    }
    
    if (empty($email)) {
        $errors[] = 'E-posta boş olamaz.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Geçerli bir e-posta adresi giriniz.';
    }
    
    if (empty($sifre)) {
        $errors[] = 'Şifre boş olamaz.';
    } elseif (strlen($sifre) < 6) {
        $errors[] = 'Şifre en az 6 karakter olmalıdır.';
    } elseif (!preg_match('/[A-Z]/', $sifre)) {
        $errors[] = 'Şifre en az 1 büyük harf içermelidir.';
    } elseif (!preg_match('/[a-z]/', $sifre)) {
        $errors[] = 'Şifre en az 1 küçük harf içermelidir.';
    } elseif (!preg_match('/[0-9]/', $sifre)) {
        $errors[] = 'Şifre en az 1 rakam içermelidir.';
    }
    
    if ($sifre !== $sifre_tekrar) {
        $errors[] = 'Şifreler eşleşmiyor.';
    }
    
    if (!$kullanici_sozlesmesi) {
        $errors[] = 'Kullanıcı sözleşmesini kabul etmelisiniz.';
    }
    
    // E-posta benzersiz mi kontrol et
    if (empty($errors)) {
        try {
            $stmt = $db->prepare("SELECT id FROM kullanicilar WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'Bu e-posta adresi zaten kayıtlı!';
            }
        } catch (PDOException $e) {
            $errors[] = 'Veritabanı hatası: ' . $e->getMessage();
        }
    }
    
    // Kayıt işlemi
    if (empty($errors)) {
        try {
            $sifre_hash = password_hash($sifre, PASSWORD_DEFAULT);
            
            $stmt = $db->prepare("
                INSERT INTO kullanicilar (
                    ad, soyad, email, telefon, sifre, 
                    yetki, durum, kayit_tarihi
                ) VALUES (?, ?, ?, ?, ?, 'user', 'aktif', NOW())
            ");
            $stmt->execute([$ad, $soyad, $email, $telefon, $sifre_hash]);
            
            $user_id = $db->lastInsertId();
            
            // Otomatik giriş yaptır
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_ad'] = $ad;
            $_SESSION['user_soyad'] = $soyad;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_yetki'] = 'user';
            
            $_SESSION['kayit_mesaji'] = 'Kaydınız başarıyla tamamlandı! Hoş geldiniz, ' . $ad . '! 🎉';
            $_SESSION['kayit_mesaji_tip'] = 'success';
            
            header("Location: " . SITE_URL . "public/index.php");
            exit();
            
        } catch (PDOException $e) {
            $error = 'Kayıt hatası: ' . $e->getMessage();
        }
    } else {
        $error = implode('<br>', $errors);
    }
}

// =====================================================
// HEADER'I DAHİL ET
// =====================================================
include '../includes/header.php';
?>

<!-- ============================================ -->
<!-- KAYIT SAYFASI -->
<!-- ============================================ -->
<section class="auth-section">
    <div class="container">
        <div class="auth-wrapper">
            <div class="auth-card">
                
                <div class="auth-header">
                    <div class="auth-logo">
                        <i class="fa-solid fa-bag-shopping"></i>
                        <span><?php echo SITE_NAME; ?></span>
                    </div>
                    <h1>Hesap Oluştur</h1>
                    <p>Alışverişin keyfini çıkarmak için kaydolun.</p>
                </div>
                
                <!-- Hata Mesajları -->
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fa-solid fa-exclamation-circle"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Kayıt Formu -->
                <form method="POST" action="" class="auth-form">
                    <input type="hidden" name="kayit_ol" value="1">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="ad">Ad</label>
                            <div class="input-wrapper">
                                <i class="fa-regular fa-user"></i>
                                <input type="text" id="ad" name="ad" placeholder="Adınız" required value="<?php echo $form_data['ad'] ?? ''; ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="soyad">Soyad</label>
                            <div class="input-wrapper">
                                <i class="fa-regular fa-user"></i>
                                <input type="text" id="soyad" name="soyad" placeholder="Soyadınız" required value="<?php echo $form_data['soyad'] ?? ''; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">E-posta Adresi</label>
                        <div class="input-wrapper">
                            <i class="fa-regular fa-envelope"></i>
                            <input type="email" id="email" name="email" placeholder="ornek@site.com" required value="<?php echo $form_data['email'] ?? ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="telefon">Telefon (Opsiyonel)</label>
                        <div class="input-wrapper">
                            <i class="fa-solid fa-phone"></i>
                            <input type="text" id="telefon" name="telefon" placeholder="05XX XXX XX XX" value="<?php echo $form_data['telefon'] ?? ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="sifre">Şifre</label>
                            <div class="input-wrapper">
                                <i class="fa-solid fa-lock"></i>
                                <input type="password" id="sifre" name="sifre" placeholder="En az 6 karakter" required>
                                <button type="button" class="toggle-password" onclick="togglePassword('sifre', 'eyeIcon1')">
                                    <i class="fa-regular fa-eye" id="eyeIcon1"></i>
                                </button>
                            </div>
                            <small class="form-hint">En az 6 karakter, 1 büyük harf, 1 küçük harf ve 1 rakam içermelidir.</small>
                        </div>
                        <div class="form-group">
                            <label for="sifre_tekrar">Şifre Tekrar</label>
                            <div class="input-wrapper">
                                <i class="fa-solid fa-lock"></i>
                                <input type="password" id="sifre_tekrar" name="sifre_tekrar" placeholder="Şifreyi tekrar girin" required>
                                <button type="button" class="toggle-password" onclick="togglePassword('sifre_tekrar', 'eyeIcon2')">
                                    <i class="fa-regular fa-eye" id="eyeIcon2"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label style="display:flex;align-items:flex-start;gap:10px;cursor:pointer;font-weight:400;font-size:14px;">
                            <input type="checkbox" name="kullanici_sozlesmesi" required style="margin-top:3px;width:18px;height:18px;accent-color:#ffd400;">
                            <span>
                                <a href="<?php echo SITE_URL; ?>public/kullanim-sozlesmesi.php" target="_blank" style="color:#ffd400;font-weight:600;">Kullanıcı Sözleşmesi</a>
                                'ni okudum, kabul ediyorum.
                            </span>
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fa-solid fa-user-plus"></i>
                        Kayıt Ol
                    </button>
                </form>
                
                <div class="auth-footer">
                    <p>Zaten hesabınız var mı? <a href="<?php echo SITE_URL; ?>public/giris.php">Giriş Yap</a></p>
                </div>
                
            </div>
        </div>
    </div>
</section>

<!-- ============================================ -->
<!-- JAVASCRIPT -->
<!-- ============================================ -->
<script>
function togglePassword(inputId, iconId) {
    const passwordInput = document.getElementById(inputId);
    const eyeIcon = document.getElementById(iconId);
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        eyeIcon.className = 'fa-regular fa-eye-slash';
    } else {
        passwordInput.type = 'password';
        eyeIcon.className = 'fa-regular fa-eye';
    }
}

// Şifre gücü göstergesi (opsiyonel)
document.addEventListener('DOMContentLoaded', function() {
    const sifreInput = document.getElementById('sifre');
    if (sifreInput) {
        sifreInput.addEventListener('input', function() {
            const sifre = this.value;
            const strength = document.getElementById('sifreGucu');
            
            // Basit şifre gücü kontrolü
            let guc = 0;
            if (sifre.length >= 6) guc++;
            if (sifre.length >= 10) guc++;
            if (/[A-Z]/.test(sifre)) guc++;
            if (/[a-z]/.test(sifre)) guc++;
            if (/[0-9]/.test(sifre)) guc++;
            if (/[^A-Za-z0-9]/.test(sifre)) guc++;
            
            // Güç göstergesini güncelle (isteğe bağlı)
        });
    }
});
</script>

<!-- ============================================ -->
<!-- CSS - style.css EKLENECEK -->
<!-- ============================================ -->
<style>
/* ============================================
   KAYIT SAYFASI ÖZEL STİLLERİ
   ============================================ */

.auth-section {
    min-height: calc(100vh - 200px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 60px 0;
    background: #f8f9fb;
}

.auth-wrapper {
    width: 100%;
    max-width: 520px;
    margin: 0 auto;
}

.auth-card {
    background: #fff;
    border-radius: 24px;
    padding: 48px 40px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.06);
}

.auth-header {
    text-align: center;
    margin-bottom: 32px;
}

.auth-logo {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    font-size: 28px;
    font-weight: 800;
    margin-bottom: 16px;
}

.auth-logo i {
    background: #ffd400;
    padding: 12px;
    border-radius: 14px;
    font-size: 20px;
    color: #111;
}

.auth-header h1 {
    font-size: 28px;
    font-weight: 800;
    margin-bottom: 4px;
}

.auth-header p {
    color: #999;
    font-size: 15px;
}

/* Form */
.auth-form .form-group {
    margin-bottom: 20px;
}

.auth-form .form-group label {
    display: block;
    font-weight: 600;
    font-size: 14px;
    margin-bottom: 6px;
    color: #222;
}

.auth-form .form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.auth-form .input-wrapper {
    position: relative;
}

.auth-form .input-wrapper i {
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    color: #999;
    font-size: 18px;
}

.auth-form .input-wrapper input {
    width: 100%;
    padding: 14px 16px 14px 48px;
    border: 2px solid #eee;
    border-radius: 14px;
    font-size: 15px;
    font-family: 'Inter', sans-serif;
    transition: all 0.3s;
    background: #fff;
    color: #222;
}

.auth-form .input-wrapper input:focus {
    border-color: #ffd400;
    outline: none;
    box-shadow: 0 0 0 4px rgba(255, 212, 0, 0.1);
}

.auth-form .input-wrapper input:disabled {
    background: #f5f5f5;
    cursor: not-allowed;
}

.auth-form .input-wrapper .toggle-password {
    position: absolute;
    right: 16px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #999;
    cursor: pointer;
    font-size: 18px;
    padding: 4px;
}

.form-hint {
    display: block;
    font-size: 12px;
    color: #999;
    margin-top: 4px;
}

.btn-block {
    width: 100%;
    justify-content: center;
    padding: 16px;
    font-size: 16px;
}

.auth-footer {
    text-align: center;
    margin-top: 24px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

.auth-footer p {
    color: #666;
    font-size: 14px;
}

.auth-footer a {
    color: #ffd400;
    font-weight: 600;
    text-decoration: none;
}

.auth-footer a:hover {
    text-decoration: underline;
}

/* Alert */
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

.alert i {
    font-size: 18px;
}

/* Responsive */
@media (max-width: 768px) {
    .auth-section {
        padding: 40px 0;
    }
    
    .auth-card {
        padding: 32px 24px;
    }
    
    .auth-header h1 {
        font-size: 24px;
    }
    
    .auth-form .form-row {
        grid-template-columns: 1fr;
        gap: 0;
    }
}

@media (max-width: 480px) {
    .auth-card {
        padding: 24px 16px;
        border-radius: 16px;
    }
    
    .auth-logo {
        font-size: 24px;
    }
    
    .auth-form .input-wrapper input {
        padding: 12px 14px 12px 42px;
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