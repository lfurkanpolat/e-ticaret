<?php
// =====================================================
// ADMIN LOGIN - admin/login.php
// =====================================================

// Config dosyasını dahil et
require_once '../includes/config.php';

// Eğer zaten giriş yapılmışsa dashboard'a yönlendir
if (isLoggedIn() && isAdmin()) {
    header("Location: " . SITE_URL . "admin/index.php");
    exit();
}

$error = '';

// =====================================================
// GİRİŞ İŞLEMİ
// =====================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = clean($_POST['email']);
    $sifre = $_POST['sifre'];
    
    // CSRF kontrolü
    if (!csrf_verify($_POST['csrf_token'])) {
        $error = 'Güvenlik hatası! Lütfen sayfayı yenileyin.';
    } else {
        // Email kontrolü
        try {
            $stmt = $db->prepare("SELECT * FROM kullanicilar WHERE email = ? AND yetki = 'admin'");
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
                        
                        // Son giriş tarihini güncelle
                        $updateStmt = $db->prepare("UPDATE kullanicilar SET son_giris = NOW() WHERE id = ?");
                        $updateStmt->execute([$user['id']]);
                        
                        // Admin paneline yönlendir
                        header("Location: " . SITE_URL . "admin/index.php");
                        exit();
                    } else {
                        $error = 'Hesabınız pasif durumda. Lütfen yönetici ile iletişime geçin.';
                    }
                } else {
                    $error = 'Geçersiz şifre!';
                }
            } else {
                $error = 'Bu email adresine ait admin bulunamadı!';
            }
        } catch (PDOException $e) {
            $error = 'Veritabanı hatası: ' . $e->getMessage();
        }
    }
}

// CSRF Token oluştur
$csrf_token = csrf_token();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Giriş - <?php echo SITE_NAME; ?></title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --primary: #6C63FF;
            --primary-dark: #5A52D5;
            --dark: #2D2D3F;
            --darker: #1A1A2E;
            --gray: #6C6C8A;
            --light-gray: #F0F0F5;
            --white: #FFFFFF;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, var(--darker) 0%, var(--primary-dark) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            width: 100%;
            max-width: 440px;
        }
        
        .login-box {
            background: var(--white);
            border-radius: 24px;
            padding: 48px 40px;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.3);
        }
        
        .login-logo {
            text-align: center;
            margin-bottom: 32px;
        }
        
        .login-logo i {
            font-size: 48px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .login-logo h1 {
            font-size: 28px;
            font-weight: 800;
            margin-top: 8px;
            color: var(--dark);
        }
        
        .login-logo p {
            color: var(--gray);
            font-size: 14px;
            margin-top: 4px;
        }
        
        .login-title {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--dark);
        }
        
        .login-subtitle {
            color: var(--gray);
            font-size: 14px;
            margin-bottom: 28px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 6px;
            color: var(--dark);
        }
        
        .form-group .input-wrapper {
            position: relative;
        }
        
        .form-group .input-wrapper i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
            font-size: 18px;
        }
        
        .form-group input {
            width: 100%;
            padding: 14px 16px 14px 48px;
            border: 2px solid var(--light-gray);
            border-radius: 12px;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s;
            background: var(--white);
            color: var(--dark);
        }
        
        .form-group input:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 4px rgba(108, 99, 255, 0.1);
        }
        
        .form-group input::placeholder {
            color: #B0B0C8;
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
            color: var(--gray);
            cursor: pointer;
        }
        
        .form-options label input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: var(--primary);
            cursor: pointer;
        }
        
        .form-options a {
            color: var(--primary);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }
        
        .form-options a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }
        
        .btn-login {
            width: 100%;
            padding: 16px;
            background: var(--primary);
            color: var(--white);
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-login:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(108, 99, 255, 0.3);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .alert {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-weight: 500;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-error {
            background: #FEE2E2;
            color: #991B1B;
            border: 1px solid #FECACA;
        }
        
        .alert-success {
            background: #D1FAE5;
            color: #065F46;
            border: 1px solid #A7F3D0;
        }
        
        .alert i {
            font-size: 18px;
        }
        
        .login-footer {
            text-align: center;
            margin-top: 24px;
            color: var(--gray);
            font-size: 13px;
        }
        
        .login-footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }
        
        .login-footer a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }
        
        .admin-badge {
            display: inline-block;
            background: var(--primary);
            color: var(--white);
            font-size: 11px;
            font-weight: 700;
            padding: 3px 12px;
            border-radius: 50px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        
        @media (max-width: 480px) {
            .login-box {
                padding: 32px 24px;
            }
            
            .login-logo h1 {
                font-size: 24px;
            }
            
            .form-options {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
        }
    </style>
</head>
<body>
    
    <div class="login-container">
        <div class="login-box">
            
            <!-- Logo -->
            <div class="login-logo">
                <i class="fas fa-store"></i>
                <h1><?php echo SITE_NAME; ?></h1>
                <p>Yönetim Paneli</p>
            </div>
            
            <!-- Başlık -->
            <div class="login-title">
                <span class="admin-badge">Admin</span>
                Hoş Geldiniz
            </div>
            <p class="login-subtitle">Yönetim paneline erişmek için giriş yapın.</p>
            
            <!-- Hata Mesajı -->
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <!-- Login Form -->
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="form-group">
                    <label for="email">E-posta Adresi</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" placeholder="E-posta adresiniz" required value="<?php echo isset($_POST['email']) ? clean($_POST['email']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="sifre">Şifre</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="sifre" name="sifre" placeholder="Şifreniz" required>
                    </div>
                </div>
                
                <div class="form-options">
                    <label>
                        <input type="checkbox" name="remember">
                        Beni Hatırla
                    </label>
                    <a href="#">Şifremi Unuttum</a>
                </div>
                
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i>
                    Giriş Yap
                </button>
            </form>
            
            <div class="login-footer">
                <?php echo SITE_NAME; ?> &copy; <?php echo date('Y'); ?> - Tüm hakları saklıdır.
            </div>
            
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Şifre göster/gizle özelliği
            const passwordInput = document.getElementById('sifre');
            if (passwordInput) {
                const wrapper = passwordInput.closest('.input-wrapper');
                if (wrapper) {
                    const toggleIcon = document.createElement('i');
                    toggleIcon.className = 'fas fa-eye';
                    toggleIcon.style.cssText = `
                        position: absolute;
                        right: 16px;
                        top: 50%;
                        transform: translateY(-50%);
                        color: var(--gray);
                        cursor: pointer;
                        z-index: 2;
                    `;
                    
                    toggleIcon.addEventListener('click', function() {
                        if (passwordInput.type === 'password') {
                            passwordInput.type = 'text';
                            toggleIcon.className = 'fas fa-eye-slash';
                        } else {
                            passwordInput.type = 'password';
                            toggleIcon.className = 'fas fa-eye';
                        }
                    });
                    
                    wrapper.appendChild(toggleIcon);
                }
            }
        });
    </script>
    
</body>
</html>