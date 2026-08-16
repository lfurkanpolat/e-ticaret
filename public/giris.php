<?php
// =====================================================
// GİRİŞ YAP - public/giris.php
// =====================================================

require_once '../includes/config.php';

// Zaten giriş yapmışsa ana sayfaya yönlendir
if (isLoggedIn()) {
    header("Location: " . SITE_URL . "public/index.php");
    exit();
}

$error = '';
$success = '';

// =====================================================
// GİRİŞ İŞLEMİ
// =====================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['giris_yap'])) {
    $email = clean($_POST['email']);
    $sifre = $_POST['sifre'];
    $remember = isset($_POST['remember']) ? true : false;
    
    // Validasyon
    $errors = [];
    if (empty($email)) $errors[] = 'E-posta adresi boş olamaz.';
    if (empty($sifre)) $errors[] = 'Şifre boş olamaz.';
    
    if (empty($errors)) {
        try {
            $stmt = $db->prepare("SELECT * FROM kullanicilar WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Şifre kontrolü
                if (password_verify($sifre, $user['sifre'])) {
                    // Kullanıcı aktif mi?
                    if ($user['durum'] == 'aktif') {
                        // Oturum başlat
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_ad'] = $user['ad'];
                        $_SESSION['user_soyad'] = $user['soyad'];
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['user_yetki'] = $user['yetki'];
                        
                        // Beni hatırla
                        if ($remember) {
                            $token = bin2hex(random_bytes(32));
                            setcookie('remember_token', $token, time() + (86400 * 30), '/');
                            // Token'ı veritabanına kaydet (opsiyonel)
                        }
                        
                        // Son giriş tarihini güncelle
                        $updateStmt = $db->prepare("UPDATE kullanicilar SET son_giris = NOW() WHERE id = ?");
                        $updateStmt->execute([$user['id']]);
                        
                        // Kullanıcıyı yönlendir
                        $_SESSION['giris_mesaji'] = 'Hoş geldiniz, ' . $user['ad'] . '! 👋';
                        $_SESSION['giris_mesaji_tip'] = 'success';
                        
                        header("Location: " . SITE_URL . "public/index.php");
                        exit();
                    } else {
                        $error = 'Hesabınız pasif durumda. Lütfen yönetici ile iletişime geçin.';
                    }
                } else {
                    $error = 'Geçersiz şifre!';
                }
            } else {
                $error = 'Bu e-posta adresine kayıtlı kullanıcı bulunamadı!';
            }
        } catch (PDOException $e) {
            $error = 'Veritabanı hatası: ' . $e->getMessage();
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
<!-- GİRİŞ SAYFASI -->
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
                    <h1>Hoş Geldiniz</h1>
                    <p>Hesabınıza giriş yaparak alışverişe başlayın.</p>
                </div>
                
                <!-- Hata ve Başarı Mesajları -->
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fa-solid fa-exclamation-circle"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fa-solid fa-check-circle"></i>
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Giriş Formu -->
                <form method="POST" action="" class="auth-form">
                    <input type="hidden" name="giris_yap" value="1">
                    
                    <div class="form-group">
                        <label for="email">E-posta Adresi</label>
                        <div class="input-wrapper">
                            <i class="fa-regular fa-envelope"></i>
                            <input type="email" id="email" name="email" placeholder="ornek@site.com" required value="<?php echo isset($_POST['email']) ? clean($_POST['email']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="sifre">Şifre</label>
                        <div class="input-wrapper">
                            <i class="fa-solid fa-lock"></i>
                            <input type="password" id="sifre" name="sifre" placeholder="••••••••" required>
                            <button type="button" class="toggle-password" onclick="togglePassword()">
                                <i class="fa-regular fa-eye" id="eyeIcon"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-options">
                        <label>
                            <input type="checkbox" name="remember" <?php echo isset($_POST['remember']) ? 'checked' : ''; ?>>
                            Beni Hatırla
                        </label>
                        <a href="<?php echo SITE_URL; ?>public/sifre-sifirla.php" class="forgot-link">Şifremi Unuttum</a>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fa-solid fa-sign-in-alt"></i>
                        Giriş Yap
                    </button>
                </form>
                
                <div class="auth-footer">
                    <p>Hesabınız yok mu? <a href="<?php echo SITE_URL; ?>public/kayit.php">Hemen Kayıt Ol</a></p>
                </div>
                
                <!-- Sosyal Giriş (Opsiyonel) -->
                <div class="social-login">
                    <div class="divider">
                        <span>veya</span>
                    </div>
                    <div class="social-buttons">
                        <button class="social-btn google">
                            <i class="fa-brands fa-google"></i>
                            Google ile Giriş Yap
                        </button>
                        <button class="social-btn facebook">
                            <i class="fa-brands fa-facebook-f"></i>
                            Facebook ile Giriş Yap
                        </button>
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
function togglePassword() {
    const passwordInput = document.getElementById('sifre');
    const eyeIcon = document.getElementById('eyeIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        eyeIcon.className = 'fa-regular fa-eye-slash';
    } else {
        passwordInput.type = 'password';
        eyeIcon.className = 'fa-regular fa-eye';
    }
}
</script>

<!-- ============================================ -->
<!-- CSS - style.css EKLENECEK -->
<!-- ============================================ -->
<style>
/* ============================================
   GİRİŞ / KAYIT SAYFASI STİLLERİ
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
    max-width: 480px;
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

.form-options {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.form-options label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    color: #666;
    cursor: pointer;
}

.form-options label input[type="checkbox"] {
    width: 18px;
    height: 18px;
    accent-color: #ffd400;
    cursor: pointer;
}

.forgot-link {
    color: #ffd400;
    font-weight: 500;
    font-size: 14px;
    text-decoration: none;
}

.forgot-link:hover {
    color: #f5c800;
    text-decoration: underline;
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

/* Social Login */
.social-login {
    margin-top: 24px;
}

.divider {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 20px;
    color: #999;
    font-size: 13px;
}

.divider::before,
.divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: #eee;
}

.social-buttons {
    display: flex;
    gap: 12px;
}

.social-btn {
    flex: 1;
    padding: 12px;
    border: 2px solid #eee;
    border-radius: 14px;
    background: #fff;
    font-weight: 600;
    font-size: 14px;
    font-family: 'Inter', sans-serif;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.social-btn.google:hover {
    border-color: #ea4335;
    background: #fef2f0;
    color: #ea4335;
}

.social-btn.facebook:hover {
    border-color: #1877f2;
    background: #f0f4fe;
    color: #1877f2;
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
    
    .social-buttons {
        flex-direction: column;
    }
    
    .form-options {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
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
}
</style>

<?php
// =====================================================
// FOOTER'I DAHİL ET
// =====================================================
include '../includes/footer.php';
?>