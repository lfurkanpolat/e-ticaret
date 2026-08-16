<?php
// =====================================================
// API AYARLARI - admin/api-ayarlari.php
// =====================================================

// =====================================================
// NORMAL POST İŞLEMLERİ
// =====================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['api_kaydet'])) {
    require_once '../includes/config.php';
    
    // API ayarlarını al
    $api_ayarlar = [
        // Ödeme API'leri
        'iyzico_api_key' => clean($_POST['iyzico_api_key']),
        'iyzico_secret_key' => clean($_POST['iyzico_secret_key']),
        'iyzico_mode' => clean($_POST['iyzico_mode']),
        
        // Kargo API'leri
        'kargo_api_key' => clean($_POST['kargo_api_key']),
        'kargo_api_secret' => clean($_POST['kargo_api_secret']),
        'kargo_api_url' => clean($_POST['kargo_api_url']),
        
        // SMS API'leri
        'sms_api_key' => clean($_POST['sms_api_key']),
        'sms_api_secret' => clean($_POST['sms_api_secret']),
        'sms_api_sender' => clean($_POST['sms_api_sender']),
        
        // Mail API'leri
        'mail_api_key' => clean($_POST['mail_api_key']),
        'mail_api_secret' => clean($_POST['mail_api_secret']),
        'mail_api_url' => clean($_POST['mail_api_url']),
        
        // Google API
        'google_analytics_id' => clean($_POST['google_analytics_id']),
        'google_recaptcha_site_key' => clean($_POST['google_recaptcha_site_key']),
        'google_recaptcha_secret_key' => clean($_POST['google_recaptcha_secret_key']),
        
        // Genel API Ayarları
        'api_timeout' => (int)$_POST['api_timeout'],
        'api_cache_time' => (int)$_POST['api_cache_time']
    ];
    
    try {
        // API ayarlarını güncelle
        $sql = "UPDATE site_ayarlari SET 
                    iyzico_api_key = ?, iyzico_secret_key = ?, iyzico_mode = ?,
                    kargo_api_key = ?, kargo_api_secret = ?, kargo_api_url = ?,
                    sms_api_key = ?, sms_api_secret = ?, sms_api_sender = ?,
                    mail_api_key = ?, mail_api_secret = ?, mail_api_url = ?,
                    google_analytics_id = ?, google_recaptcha_site_key = ?, google_recaptcha_secret_key = ?,
                    api_timeout = ?, api_cache_time = ?,
                    guncelleme_tarihi = NOW()
                WHERE id = 1";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $api_ayarlar['iyzico_api_key'],
            $api_ayarlar['iyzico_secret_key'],
            $api_ayarlar['iyzico_mode'],
            $api_ayarlar['kargo_api_key'],
            $api_ayarlar['kargo_api_secret'],
            $api_ayarlar['kargo_api_url'],
            $api_ayarlar['sms_api_key'],
            $api_ayarlar['sms_api_secret'],
            $api_ayarlar['sms_api_sender'],
            $api_ayarlar['mail_api_key'],
            $api_ayarlar['mail_api_secret'],
            $api_ayarlar['mail_api_url'],
            $api_ayarlar['google_analytics_id'],
            $api_ayarlar['google_recaptcha_site_key'],
            $api_ayarlar['google_recaptcha_secret_key'],
            $api_ayarlar['api_timeout'],
            $api_ayarlar['api_cache_time']
        ]);
        
        $_SESSION['success'] = 'API ayarları başarıyla güncellendi!';
        header("Location: " . SITE_URL . "admin/api-ayarlari.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Veritabanı hatası: ' . $e->getMessage();
        header("Location: " . SITE_URL . "admin/api-ayarlari.php");
        exit();
    }
}

// =====================================================
// NORMAL SAYFA
// =====================================================
$page_title = 'API Ayarları';
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
    
    if (!$ayar) {
        $ayar = [];
    }
} catch (PDOException $e) {
    $ayar = [];
    $error = 'Veritabanı hatası: ' . $e->getMessage();
}

// Ödeme modları
$odeme_modlari = [
    'test' => 'Test Modu (Sandbox)',
    'live' => 'Canlı Mod (Live)'
];
?>

<!-- ============================================ -->
<!-- SAYFA BAŞLIĞI -->
<!-- ============================================ -->
<div class="page-header">
    <div class="page-header-left">
        <h2><i class="fas fa-plug"></i> API Ayarları</h2>
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
<!-- API AYARLARI FORM -->
<!-- ============================================ -->
<div class="form-card">
    <form method="POST" action="" class="api-form">
        
        <!-- ÖDEME API AYARLARI -->
        <h3 class="form-section-title">
            <i class="fas fa-credit-card"></i> Ödeme API Ayarları (iyzico)
        </h3>
        
        <div class="form-row">
            <div class="form-group">
                <label>API Key</label>
                <input type="text" name="iyzico_api_key" value="<?php echo $ayar['iyzico_api_key'] ?? ''; ?>" placeholder="iyzico API Key">
                <small class="form-hint">iyzico panelinden alınan API Key</small>
            </div>
            <div class="form-group">
                <label>Secret Key</label>
                <input type="password" name="iyzico_secret_key" value="<?php echo $ayar['iyzico_secret_key'] ?? ''; ?>" placeholder="iyzico Secret Key">
                <small class="form-hint">iyzico panelinden alınan Secret Key</small>
            </div>
        </div>
        
        <div class="form-group">
            <label>Ödeme Modu</label>
            <select name="iyzico_mode">
                <?php foreach ($odeme_modlari as $key => $value): ?>
                    <option value="<?php echo $key; ?>" <?php echo ($ayar['iyzico_mode'] ?? 'test') == $key ? 'selected' : ''; ?>>
                        <?php echo $value; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <small class="form-hint">Test modunda sanal ödeme yapılır, canlı modda gerçek ödeme alınır.</small>
        </div>
        
        <!-- KARGO API AYARLARI -->
        <h3 class="form-section-title" style="margin-top:30px;">
            <i class="fas fa-truck"></i> Kargo API Ayarları
        </h3>
        
        <div class="form-row">
            <div class="form-group">
                <label>Kargo API Key</label>
                <input type="text" name="kargo_api_key" value="<?php echo $ayar['kargo_api_key'] ?? ''; ?>" placeholder="Kargo API Key">
            </div>
            <div class="form-group">
                <label>Kargo API Secret</label>
                <input type="password" name="kargo_api_secret" value="<?php echo $ayar['kargo_api_secret'] ?? ''; ?>" placeholder="Kargo API Secret">
            </div>
        </div>
        
        <div class="form-group">
            <label>Kargo API URL</label>
            <input type="url" name="kargo_api_url" value="<?php echo $ayar['kargo_api_url'] ?? ''; ?>" placeholder="https://api.kargo.com/v1">
            <small class="form-hint">Kargo firmasının API endpoint'i</small>
        </div>
        
        <!-- SMS API AYARLARI -->
        <h3 class="form-section-title" style="margin-top:30px;">
            <i class="fas fa-sms"></i> SMS API Ayarları
        </h3>
        
        <div class="form-row">
            <div class="form-group">
                <label>SMS API Key</label>
                <input type="text" name="sms_api_key" value="<?php echo $ayar['sms_api_key'] ?? ''; ?>" placeholder="SMS API Key">
            </div>
            <div class="form-group">
                <label>SMS API Secret</label>
                <input type="password" name="sms_api_secret" value="<?php echo $ayar['sms_api_secret'] ?? ''; ?>" placeholder="SMS API Secret">
            </div>
        </div>
        
        <div class="form-group">
            <label>SMS Gönderici Adı</label>
            <input type="text" name="sms_api_sender" value="<?php echo $ayar['sms_api_sender'] ?? ''; ?>" placeholder="Örn: MAGAZA">
            <small class="form-hint">SMS gönderirken görünecek isim (maksimum 11 karakter)</small>
        </div>
        
        <!-- MAİL API AYARLARI -->
        <h3 class="form-section-title" style="margin-top:30px;">
            <i class="fas fa-envelope"></i> Mail API Ayarları
        </h3>
        
        <div class="form-row">
            <div class="form-group">
                <label>Mail API Key</label>
                <input type="text" name="mail_api_key" value="<?php echo $ayar['mail_api_key'] ?? ''; ?>" placeholder="Mail API Key">
            </div>
            <div class="form-group">
                <label>Mail API Secret</label>
                <input type="password" name="mail_api_secret" value="<?php echo $ayar['mail_api_secret'] ?? ''; ?>" placeholder="Mail API Secret">
            </div>
        </div>
        
        <div class="form-group">
            <label>Mail API URL</label>
            <input type="url" name="mail_api_url" value="<?php echo $ayar['mail_api_url'] ?? ''; ?>" placeholder="https://api.mailgun.net/v3/...">
            <small class="form-hint">Mail servis sağlayıcısının API endpoint'i</small>
        </div>
        
        <!-- GOOGLE API AYARLARI -->
        <h3 class="form-section-title" style="margin-top:30px;">
            <i class="fab fa-google"></i> Google API Ayarları
        </h3>
        
        <div class="form-group">
            <label>Google Analytics ID</label>
            <input type="text" name="google_analytics_id" value="<?php echo $ayar['google_analytics_id'] ?? ''; ?>" placeholder="UA-XXXXXXXXX-X">
            <small class="form-hint">Google Analytics hesabınızdan alınan ID</small>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>reCAPTCHA Site Key</label>
                <input type="text" name="google_recaptcha_site_key" value="<?php echo $ayar['google_recaptcha_site_key'] ?? ''; ?>" placeholder="reCAPTCHA Site Key">
            </div>
            <div class="form-group">
                <label>reCAPTCHA Secret Key</label>
                <input type="password" name="google_recaptcha_secret_key" value="<?php echo $ayar['google_recaptcha_secret_key'] ?? ''; ?>" placeholder="reCAPTCHA Secret Key">
            </div>
        </div>
        <div class="form-group">
            <small class="form-hint">
                <i class="fas fa-info-circle"></i> 
                <a href="https://www.google.com/recaptcha/admin" target="_blank" rel="noopener">
                    Google reCAPTCHA yönetim paneli
                </a>
            </small>
        </div>
        
        <!-- GENEL API AYARLARI -->
        <h3 class="form-section-title" style="margin-top:30px;">
            <i class="fas fa-sliders-h"></i> Genel API Ayarları
        </h3>
        
        <div class="form-row">
            <div class="form-group">
                <label>API Zaman Aşımı (saniye)</label>
                <input type="number" name="api_timeout" value="<?php echo $ayar['api_timeout'] ?? 30; ?>" min="5" max="120">
                <small class="form-hint">API çağrılarının zaman aşımı süresi</small>
            </div>
            <div class="form-group">
                <label>API Önbellek Süresi (dakika)</label>
                <input type="number" name="api_cache_time" value="<?php echo $ayar['api_cache_time'] ?? 5; ?>" min="0" max="60">
                <small class="form-hint">API yanıtlarının önbellekte kalma süresi (0 = önbellek yok)</small>
            </div>
        </div>
        
        <!-- FORM BUTTONLARI -->
        <div class="form-actions">
            <button type="submit" name="api_kaydet" class="btn btn-primary">
                <i class="fas fa-save"></i> API Ayarlarını Kaydet
            </button>
        </div>
        
    </form>
</div>


<?php
include 'footer.php';
?>