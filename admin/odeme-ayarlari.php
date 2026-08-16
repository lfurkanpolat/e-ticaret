<?php
// =====================================================
// ÖDEME AYARLARI - admin/odeme-ayarlari.php
// =====================================================

// =====================================================
// NORMAL POST İŞLEMLERİ
// =====================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['odeme_kaydet'])) {
    require_once '../includes/config.php';
    
    // Ödeme ayarlarını al
    $odeme_ayarlar = [
        // Kredi Kartı
        'kredi_karti_aktif' => isset($_POST['kredi_karti_aktif']) ? 1 : 0,
        'kredi_karti_baslik' => clean($_POST['kredi_karti_baslik']),
        'kredi_karti_aciklama' => clean($_POST['kredi_karti_aciklama']),
        'kredi_karti_komisyon' => (float)$_POST['kredi_karti_komisyon'],
        
        // Havale/EFT
        'havale_aktif' => isset($_POST['havale_aktif']) ? 1 : 0,
        'havale_baslik' => clean($_POST['havale_baslik']),
        'havale_aciklama' => clean($_POST['havale_aciklama']),
        'havale_banka_adi' => clean($_POST['havale_banka_adi']),
        'havale_hesap_adi' => clean($_POST['havale_hesap_adi']),
        'havale_iban' => clean($_POST['havale_iban']),
        'havale_hesap_no' => clean($_POST['havale_hesap_no']),
        
        // Kapıda Ödeme
        'kapida_aktif' => isset($_POST['kapida_aktif']) ? 1 : 0,
        'kapida_baslik' => clean($_POST['kapida_baslik']),
        'kapida_aciklama' => clean($_POST['kapida_aciklama']),
        'kapida_ek_ucret' => (float)$_POST['kapida_ek_ucret'],
        
        // Genel Ödeme Ayarları
        'varsayilan_odeme' => clean($_POST['varsayilan_odeme']),
        'odeme_bekleme_suresi' => (int)$_POST['odeme_bekleme_suresi'],
        'odeme_bildirim' => isset($_POST['odeme_bildirim']) ? 1 : 0
    ];
    
    try {
        // Ödeme ayarlarını güncelle
        $sql = "UPDATE site_ayarlari SET 
                    kredi_karti_aktif = ?, kredi_karti_baslik = ?, kredi_karti_aciklama = ?, kredi_karti_komisyon = ?,
                    havale_aktif = ?, havale_baslik = ?, havale_aciklama = ?, 
                    havale_banka_adi = ?, havale_hesap_adi = ?, havale_iban = ?, havale_hesap_no = ?,
                    kapida_aktif = ?, kapida_baslik = ?, kapida_aciklama = ?, kapida_ek_ucret = ?,
                    varsayilan_odeme = ?, odeme_bekleme_suresi = ?, odeme_bildirim = ?,
                    guncelleme_tarihi = NOW()
                WHERE id = 1";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $odeme_ayarlar['kredi_karti_aktif'],
            $odeme_ayarlar['kredi_karti_baslik'],
            $odeme_ayarlar['kredi_karti_aciklama'],
            $odeme_ayarlar['kredi_karti_komisyon'],
            $odeme_ayarlar['havale_aktif'],
            $odeme_ayarlar['havale_baslik'],
            $odeme_ayarlar['havale_aciklama'],
            $odeme_ayarlar['havale_banka_adi'],
            $odeme_ayarlar['havale_hesap_adi'],
            $odeme_ayarlar['havale_iban'],
            $odeme_ayarlar['havale_hesap_no'],
            $odeme_ayarlar['kapida_aktif'],
            $odeme_ayarlar['kapida_baslik'],
            $odeme_ayarlar['kapida_aciklama'],
            $odeme_ayarlar['kapida_ek_ucret'],
            $odeme_ayarlar['varsayilan_odeme'],
            $odeme_ayarlar['odeme_bekleme_suresi'],
            $odeme_ayarlar['odeme_bildirim']
        ]);
        
        $_SESSION['success'] = 'Ödeme ayarları başarıyla güncellendi!';
        header("Location: " . SITE_URL . "admin/odeme-ayarlari.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Veritabanı hatası: ' . $e->getMessage();
        header("Location: " . SITE_URL . "admin/odeme-ayarlari.php");
        exit();
    }
}

// =====================================================
// NORMAL SAYFA
// =====================================================
$page_title = 'Ödeme Ayarları';
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

// Ödeme yöntemleri
$odeme_yontemleri = [
    'kredi_karti' => 'Kredi Kartı',
    'havale' => 'Havale / EFT',
    'kapida' => 'Kapıda Ödeme'
];
?>

<!-- ============================================ -->
<!-- SAYFA BAŞLIĞI -->
<!-- ============================================ -->
<div class="page-header">
    <div class="page-header-left">
        <h2><i class="fas fa-credit-card"></i> Ödeme Ayarları</h2>
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
<!-- ÖDEME AYARLARI FORM -->
<!-- ============================================ -->
<div class="form-card">
    <form method="POST" action="" class="odeme-form">
        
        <!-- KREDİ KARTI AYARLARI -->
        <h3 class="form-section-title">
            <i class="fas fa-credit-card"></i> Kredi Kartı Ayarları
        </h3>
        
        <div class="form-group">
            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                <input type="checkbox" name="kredi_karti_aktif" value="1" 
                    <?php echo ($ayar['kredi_karti_aktif'] ?? 1) ? 'checked' : ''; ?>>
                <span>Aktif</span>
            </label>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Başlık</label>
                <input type="text" name="kredi_karti_baslik" value="<?php echo $ayar['kredi_karti_baslik'] ?? 'Kredi Kartı'; ?>" placeholder="Kredi Kartı">
            </div>
            <div class="form-group">
                <label>Komisyon Oranı (%)</label>
                <input type="number" name="kredi_karti_komisyon" step="0.01" 
                       value="<?php echo $ayar['kredi_karti_komisyon'] ?? 0; ?>" placeholder="0.00">
                <small class="form-hint">Bankanın aldığı komisyon oranı</small>
            </div>
        </div>
        
        <div class="form-group">
            <label>Açıklama</label>
            <textarea name="kredi_karti_aciklama" rows="2" placeholder="Kredi kartı ile ödeme açıklaması"><?php echo $ayar['kredi_karti_aciklama'] ?? 'Kredi kartınızla güvenli ödeme yapın.'; ?></textarea>
        </div>
        
        <!-- HAVALE/EFT AYARLARI -->
        <h3 class="form-section-title" style="margin-top:30px;">
            <i class="fas fa-university"></i> Havale / EFT Ayarları
        </h3>
        
        <div class="form-group">
            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                <input type="checkbox" name="havale_aktif" value="1" 
                    <?php echo ($ayar['havale_aktif'] ?? 1) ? 'checked' : ''; ?>>
                <span>Aktif</span>
            </label>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Başlık</label>
                <input type="text" name="havale_baslik" value="<?php echo $ayar['havale_baslik'] ?? 'Havale / EFT'; ?>" placeholder="Havale / EFT">
            </div>
            <div class="form-group">
                <label>Banka Adı</label>
                <input type="text" name="havale_banka_adi" value="<?php echo $ayar['havale_banka_adi'] ?? ''; ?>" placeholder="Örn: Ziraat Bankası">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Hesap Adı</label>
                <input type="text" name="havale_hesap_adi" value="<?php echo $ayar['havale_hesap_adi'] ?? ''; ?>" placeholder="Hesap sahibi adı">
            </div>
            <div class="form-group">
                <label>IBAN</label>
                <input type="text" name="havale_iban" value="<?php echo $ayar['havale_iban'] ?? ''; ?>" placeholder="TR00 0000 0000 0000 0000 0000 00">
            </div>
        </div>
        
        <div class="form-group">
            <label>Hesap Numarası</label>
            <input type="text" name="havale_hesap_no" value="<?php echo $ayar['havale_hesap_no'] ?? ''; ?>" placeholder="Hesap numarası">
        </div>
        
        <div class="form-group">
            <label>Açıklama</label>
            <textarea name="havale_aciklama" rows="2" placeholder="Havale/EFT ile ödeme açıklaması"><?php echo $ayar['havale_aciklama'] ?? 'Havale veya EFT ile ödeme yapabilirsiniz.'; ?></textarea>
        </div>
        
        <!-- KAPIDA ÖDEME AYARLARI -->
        <h3 class="form-section-title" style="margin-top:30px;">
            <i class="fas fa-hand-holding-usd"></i> Kapıda Ödeme Ayarları
        </h3>
        
        <div class="form-group">
            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                <input type="checkbox" name="kapida_aktif" value="1" 
                    <?php echo ($ayar['kapida_aktif'] ?? 1) ? 'checked' : ''; ?>>
                <span>Aktif</span>
            </label>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Başlık</label>
                <input type="text" name="kapida_baslik" value="<?php echo $ayar['kapida_baslik'] ?? 'Kapıda Ödeme'; ?>" placeholder="Kapıda Ödeme">
            </div>
            <div class="form-group">
                <label>Ek Ücret (₺)</label>
                <input type="number" name="kapida_ek_ucret" step="0.01" 
                       value="<?php echo $ayar['kapida_ek_ucret'] ?? 0; ?>" placeholder="0.00">
                <small class="form-hint">Kapıda ödemede ekstra ücret</small>
            </div>
        </div>
        
        <div class="form-group">
            <label>Açıklama</label>
            <textarea name="kapida_aciklama" rows="2" placeholder="Kapıda ödeme açıklaması"><?php echo $ayar['kapida_aciklama'] ?? 'Kapıda ödeme ile siparişinizi teslim alın.'; ?></textarea>
        </div>
        
        <!-- GENEL ÖDEME AYARLARI -->
        <h3 class="form-section-title" style="margin-top:30px;">
            <i class="fas fa-sliders-h"></i> Genel Ödeme Ayarları
        </h3>
        
        <div class="form-row">
            <div class="form-group">
                <label>Varsayılan Ödeme Yöntemi</label>
                <select name="varsayilan_odeme">
                    <?php foreach ($odeme_yontemleri as $key => $value): ?>
                        <option value="<?php echo $key; ?>" 
                            <?php echo ($ayar['varsayilan_odeme'] ?? 'kredi_karti') == $key ? 'selected' : ''; ?>>
                            <?php echo $value; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Ödeme Bekleme Süresi (gün)</label>
                <input type="number" name="odeme_bekleme_suresi" 
                       value="<?php echo $ayar['odeme_bekleme_suresi'] ?? 3; ?>" min="1" max="30">
                <small class="form-hint">Havale/EFT ile ödemelerde bekleme süresi</small>
            </div>
        </div>
        
        <div class="form-group">
            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                <input type="checkbox" name="odeme_bildirim" value="1" 
                    <?php echo ($ayar['odeme_bildirim'] ?? 1) ? 'checked' : ''; ?>>
                <span>Ödeme Bildirimi Gönder</span>
            </label>
            <small class="form-hint">Ödeme tamamlandığında müşteriye e-posta gönder.</small>
        </div>
        
        <!-- FORM BUTTONLARI -->
        <div class="form-actions">
            <button type="submit" name="odeme_kaydet" class="btn btn-primary">
                <i class="fas fa-save"></i> Ödeme Ayarlarını Kaydet
            </button>
        </div>
        
    </form>
</div>

<?php
include 'footer.php';
?>