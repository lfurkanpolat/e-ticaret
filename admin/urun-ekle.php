<?php
// =====================================================
// DEBUG MOD - TÜM HATALARI GÖSTER
// =====================================================
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// =====================================================
// AJAX - RASTGELE ÜRÜN KODU OLUŞTUR
// =====================================================
if (isset($_POST['generate_code'])) {
    require_once '../includes/config.php';
    header('Content-Type: application/json');
    
    $prefix = 'PRD';
    $code = '';
    $exists = true;
    $max_attempts = 100;
    $attempts = 0;
    
    while ($exists && $attempts < $max_attempts) {
        $code = $prefix . strtoupper(substr(uniqid(), -6)) . rand(100, 999);
        $attempts++;
        
        try {
            $stmt = $db->prepare("SELECT id FROM urunler WHERE urun_kodu = ?");
            $stmt->execute([$code]);
            $exists = $stmt->fetch() ? true : false;
        } catch (PDOException $e) {
            $exists = false;
        }
    }
    
    echo json_encode(['code' => $code]);
    exit();
}

// =====================================================
// NORMAL SAYFA
// =====================================================
$page_title = 'Ürün Ekle';
include 'header.php';

// =====================================================
// KATEGORİ VE MARKA LİSTESİNİ ÇEK
// =====================================================
try {
    $kategoriler = $db->query("SELECT * FROM kategoriler WHERE durum = 'aktif' ORDER BY ad")->fetchAll();
} catch (PDOException $e) {
    $kategoriler = [];
}

try {
    $markalar = $db->query("SELECT * FROM markalar WHERE durum = 'aktif' ORDER BY ad")->fetchAll();
} catch (PDOException $e) {
    $markalar = [];
}

// =====================================================
// RESİM BOYUTLANDIRMA FONKSİYONU (Thumbnail)
// =====================================================
function resizeImage($source_path, $destination_path, $max_width, $max_height, $quality = 80) {
    if (!file_exists($source_path)) {
        error_log("resizeImage: Kaynak dosya bulunamadı: " . $source_path);
        return false;
    }
    
    list($width, $height, $type) = getimagesize($source_path);
    
    $ratio = min($max_width / $width, $max_height / $height);
    $new_width = round($width * $ratio);
    $new_height = round($height * $ratio);
    
    $new_image = imagecreatetruecolor($new_width, $new_height);
    
    switch ($type) {
        case IMAGETYPE_JPEG:
            $source = imagecreatefromjpeg($source_path);
            break;
        case IMAGETYPE_PNG:
            $source = imagecreatefrompng($source_path);
            imagealphablending($new_image, false);
            imagesavealpha($new_image, true);
            break;
        case IMAGETYPE_GIF:
            $source = imagecreatefromgif($source_path);
            break;
        case IMAGETYPE_WEBP:
            $source = imagecreatefromwebp($source_path);
            break;
        default:
            error_log("resizeImage: Desteklenmeyen dosya tipi: " . $type);
            return false;
    }
    
    imagecopyresampled($new_image, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
    
    switch ($type) {
        case IMAGETYPE_JPEG:
            imagejpeg($new_image, $destination_path, $quality);
            break;
        case IMAGETYPE_PNG:
            imagepng($new_image, $destination_path, 9);
            break;
        case IMAGETYPE_GIF:
            imagegif($new_image, $destination_path);
            break;
        case IMAGETYPE_WEBP:
            imagewebp($new_image, $destination_path, $quality);
            break;
    }
    
    imagedestroy($source);
    imagedestroy($new_image);
    return true;
}

// =====================================================
// FORM GÖNDERİLDİ Mİ?
// =====================================================
$error = '';
$success = '';
$debug_info = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Form verilerini al ve temizle
    $ad = clean($_POST['ad']);
    $slug = clean($_POST['slug']);
    $aciklama = clean($_POST['aciklama']);
    $urun_kodu = clean($_POST['urun_kodu']);
    $fiyat = (float)$_POST['fiyat'];
    $indirimli_fiyat = !empty($_POST['indirimli_fiyat']) ? (float)$_POST['indirimli_fiyat'] : null;
    $maliyet = !empty($_POST['maliyet']) ? (float)$_POST['maliyet'] : null;
    $stok = (int)$_POST['stok'];
    $kritik_stok = (int)$_POST['kritik_stok'];
    $kategori_id = (int)$_POST['kategori_id'];
    $durum = clean($_POST['durum']);
    
    // =====================================================
    // MARKA ID - DÜZELTİLDİ
    // =====================================================
    $marka_id = null;
    if (isset($_POST['marka_id']) && $_POST['marka_id'] !== '' && $_POST['marka_id'] !== null) {
        $marka_id = (int)$_POST['marka_id'];
    }
    
    // Validasyon
    $errors = [];
    
    if (empty($ad)) $errors[] = 'Ürün adı boş olamaz.';
    if (empty($slug)) $errors[] = 'Slug boş olamaz.';
    if (empty($urun_kodu)) $errors[] = 'Ürün kodu boş olamaz.';
    if ($fiyat <= 0) $errors[] = 'Fiyat 0\'dan büyük olmalıdır.';
    if ($kategori_id <= 0) $errors[] = 'Lütfen bir kategori seçin.';
    
    // Slug benzersiz mi?
    if (empty($errors)) {
        try {
            $stmt = $db->prepare("SELECT id FROM urunler WHERE slug = ?");
            $stmt->execute([$slug]);
            if ($stmt->fetch()) {
                $errors[] = 'Bu slug zaten kullanılıyor!';
            }
        } catch (PDOException $e) {
            $errors[] = 'Slug kontrol hatası: ' . $e->getMessage();
        }
    }
    
    // Ürün kodu benzersiz mi?
    if (empty($errors)) {
        try {
            $stmt = $db->prepare("SELECT id FROM urunler WHERE urun_kodu = ?");
            $stmt->execute([$urun_kodu]);
            if ($stmt->fetch()) {
                $errors[] = 'Bu ürün kodu zaten kullanılıyor!';
            }
        } catch (PDOException $e) {
            $errors[] = 'Ürün kodu kontrol hatası: ' . $e->getMessage();
        }
    }
    
    // =====================================================
    // RESİM YÜKLEME VE THUMBNAIL OLUŞTURMA
    // =====================================================
    $uploaded_images = [];
    $ana_resim_url = '';
    
    if (empty($errors) && isset($_FILES['resimler']) && !empty($_FILES['resimler']['name'][0])) {
        
        $upload_dir = '../uploads/urunler/';
        $thumb_dir = '../uploads/urunler/thumb/';
        
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        if (!file_exists($thumb_dir)) {
            mkdir($thumb_dir, 0777, true);
        }
        
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $upload_errors = [];
        $is_first = true;
        
        $total_files = count($_FILES['resimler']['name']);
        
        for ($i = 0; $i < $total_files; $i++) {
            if ($_FILES['resimler']['error'][$i] == 0) {
                $file_name = $_FILES['resimler']['name'][$i];
                $file_size = $_FILES['resimler']['size'][$i];
                $file_tmp = $_FILES['resimler']['tmp_name'][$i];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                
                if (!in_array($file_ext, $allowed_ext)) {
                    $upload_errors[] = "$file_name - Geçersiz dosya formatı!";
                    continue;
                }
                
                if ($file_size > 5 * 1024 * 1024) {
                    $upload_errors[] = "$file_name - Dosya boyutu 5MB'dan büyük!";
                    continue;
                }
                
                $timestamp = date('Ymd_His');
                $unique_id = uniqid();
                $new_filename = $timestamp . '_' . $unique_id . '.' . $file_ext;
                $thumb_filename = $timestamp . '_' . $unique_id . '_thumb.' . $file_ext;
                
                $upload_path = $upload_dir . $new_filename;
                $thumb_path = $thumb_dir . $thumb_filename;
                
                if (move_uploaded_file($file_tmp, $upload_path)) {
                    resizeImage($upload_path, $thumb_path, 300, 300, 80);
                    
                    $image_data = [
                        'url' => 'urunler/' . $new_filename,
                        'thumb' => 'urunler/thumb/' . $thumb_filename,
                        'name' => $file_name
                    ];
                    
                    if ($is_first) {
                        $ana_resim_url = $image_data['url'];
                        $image_data['ana_resim'] = true;
                        $is_first = false;
                    } else {
                        $image_data['ana_resim'] = false;
                    }
                    
                    $uploaded_images[] = $image_data;
                    
                } else {
                    $upload_errors[] = "$file_name - Yüklenirken hata oluştu!";
                }
            }
        }
        
        if (!empty($upload_errors)) {
            $errors = array_merge($errors, $upload_errors);
        }
    }
    
    // =====================================================
    // VERİTABANINA KAYDET
    // =====================================================
    if (empty($errors)) {
        try {
            $sql = "INSERT INTO urunler (
                        ad, slug, aciklama, urun_kodu, fiyat, 
                        indirimli_fiyat, maliyet, stok, kritik_stok, 
                        kategori_id, marka_id, durum, resim_url, olusturma_tarihi
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW()
                    )";
            
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([
                $ad, $slug, $aciklama, $urun_kodu, $fiyat,
                $indirimli_fiyat, $maliyet, $stok, $kritik_stok,
                $kategori_id, $marka_id, $durum, $ana_resim_url
            ]);
            
            if ($result) {
                $urun_id = $db->lastInsertId();
                
                if (!empty($uploaded_images) && $urun_id) {
                    $image_sql = "INSERT INTO urun_resimleri (urun_id, resim_url, resim_adi, ana_resim, sira) VALUES (?, ?, ?, ?, ?)";
                    $image_stmt = $db->prepare($image_sql);
                    
                    $sira = 0;
                    foreach ($uploaded_images as $img) {
                        $image_stmt->execute([
                            $urun_id,
                            $img['url'],
                            $img['name'],
                            $img['ana_resim'] ? 1 : 0,
                            $sira
                        ]);
                        $sira++;
                    }
                }
                
                $_SESSION['success'] = 'Ürün başarıyla eklendi! ' . count($uploaded_images) . ' resim yüklendi.';
             if (ob_get_level()) ob_end_clean();
              echo '<script>window.location.href="' . SITE_URL . 'admin/urunler.php";</script>';
                exit();                
                
            } else {
                $error = 'Veritabanına kayıt sırasında hata oluştu!';
            }
            
        } catch (PDOException $e) {
            $error = 'Veritabanı hatası: ' . $e->getMessage();
        }
    } else {
        $error = implode('<br>', $errors);
    }
}
?>

<!-- ============================================ -->
<!-- SAYFA BAŞLIĞI -->
<!-- ============================================ -->
<div class="page-header">
    <div class="page-header-left">
        <h2><i class="fas fa-plus-circle"></i> Yeni Ürün Ekle</h2>
    </div>
    <div class="page-header-right">
        <a href="<?php echo SITE_URL; ?>admin/urunler.php" class="btn btn-outline">
            <i class="fas fa-arrow-left"></i> Ürün Listesine Dön
        </a>
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
        <br>
        <a href="<?php echo SITE_URL; ?>admin/urun-ekle.php" class="btn btn-sm btn-primary" style="margin-top:8px;">
            <i class="fas fa-plus"></i> Yeni Ürün Ekle
        </a>
        <a href="<?php echo SITE_URL; ?>admin/urunler.php" class="btn btn-sm btn-outline" style="margin-top:8px;">
            <i class="fas fa-list"></i> Ürün Listesine Git
        </a>
    </div>
<?php endif; ?>

<!-- ============================================ -->
<!-- ÜRÜN EKLEME FORM -->
<!-- ============================================ -->
<div class="form-card">
    <form method="POST" action="" enctype="multipart/form-data" class="product-form">
        
        <!-- TEMEL BİLGİLER -->
        <h3 class="form-section-title">
            <i class="fas fa-info-circle"></i> Temel Bilgiler
        </h3>
        
        <div class="form-row">
            <div class="form-group">
                <label>Ürün Adı *</label>
                <input type="text" name="ad" id="productName" placeholder="Ürün adını girin" required 
                       value="<?php echo isset($_POST['ad']) ? clean($_POST['ad']) : ''; ?>">
            </div>
            <div class="form-group">
                <label>Slug * (SEO URL)</label>
                <input type="text" name="slug" id="productSlug" placeholder="ornek-urun-adi" required
                       value="<?php echo isset($_POST['slug']) ? clean($_POST['slug']) : ''; ?>">
                <small class="form-hint">Sadece harf, rakam ve tire kullanın.</small>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Ürün Kodu * (SKU)</label>
                <div class="input-group">
                    <input type="text" name="urun_kodu" id="productCode" placeholder="Benzersiz ürün kodu" required
                           value="<?php echo isset($_POST['urun_kodu']) ? clean($_POST['urun_kodu']) : ''; ?>">
                    <button type="button" class="btn btn-secondary" id="generateCodeBtn">
                        <i class="fas fa-random"></i> Kod Oluştur
                    </button>
                </div>
                <small class="form-hint">Her ürün için benzersiz olmalıdır.</small>
            </div>
            <div class="form-group">
                <label>Kategori *</label>
                <select name="kategori_id" required>
                    <option value="">Kategori Seçin</option>
                    <?php foreach ($kategoriler as $kategori): ?>
                        <option value="<?php echo $kategori['id']; ?>" 
                            <?php echo (isset($_POST['kategori_id']) && $_POST['kategori_id'] == $kategori['id']) ? 'selected' : ''; ?>>
                            <?php echo $kategori['ad']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (empty($kategoriler)): ?>
                    <small class="form-hint" style="color: #FF6B6B;">
                        <i class="fas fa-warning"></i> Henüz kategori eklenmemiş. 
                        <a href="<?php echo SITE_URL; ?>admin/kategoriler.php">Kategori Ekle</a>
                    </small>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- MARKA SEÇİMİ -->
        <div class="form-row">
            <div class="form-group">
                <label>Marka</label>
                <select name="marka_id">
                    <option value="">Marka Seçin</option>
                    <?php foreach ($markalar as $marka): ?>
                        <option value="<?php echo $marka['id']; ?>" 
                            <?php echo (isset($_POST['marka_id']) && $_POST['marka_id'] == $marka['id']) ? 'selected' : ''; ?>>
                            <?php echo $marka['ad']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (empty($markalar)): ?>
                    <small class="form-hint" style="color: #FF6B6B;">
                        <i class="fas fa-warning"></i> Henüz marka eklenmemiş. 
                        <a href="<?php echo SITE_URL; ?>admin/markalar.php">Marka Ekle</a>
                    </small>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label>Durum</label>
                <select name="durum">
                    <option value="aktif" <?php echo (isset($_POST['durum']) && $_POST['durum'] == 'aktif') ? 'selected' : ''; ?>>Aktif</option>
                    <option value="pasif" <?php echo (isset($_POST['durum']) && $_POST['durum'] == 'pasif') ? 'selected' : ''; ?>>Pasif</option>
                    <option value="stok_yok" <?php echo (isset($_POST['durum']) && $_POST['durum'] == 'stok_yok') ? 'selected' : ''; ?>>Stok Yok</option>
                </select>
            </div>
        </div>
        
        <div class="form-group">
            <label>Açıklama</label>
            <textarea name="aciklama" rows="4" placeholder="Ürün açıklaması"><?php echo isset($_POST['aciklama']) ? clean($_POST['aciklama']) : ''; ?></textarea>
        </div>
        
        <!-- FİYAT BİLGİLERİ -->
        <h3 class="form-section-title" style="margin-top:30px;">
            <i class="fas fa-tag"></i> Fiyat Bilgileri
        </h3>
        
        <div class="form-row">
            <div class="form-group">
                <label>Fiyat * (₺)</label>
                <input type="number" name="fiyat" step="0.01" placeholder="0.00" required
                       value="<?php echo isset($_POST['fiyat']) ? $_POST['fiyat'] : ''; ?>">
            </div>
            <div class="form-group">
                <label>İndirimli Fiyat (₺)</label>
                <input type="number" name="indirimli_fiyat" step="0.01" placeholder="0.00"
                       value="<?php echo isset($_POST['indirimli_fiyat']) ? $_POST['indirimli_fiyat'] : ''; ?>">
                <small class="form-hint">Boş bırakırsanız indirim uygulanmaz.</small>
            </div>
        </div>
        
        <div class="form-group">
            <label>Maliyet (₺)</label>
            <input type="number" name="maliyet" step="0.01" placeholder="0.00"
                   value="<?php echo isset($_POST['maliyet']) ? $_POST['maliyet'] : ''; ?>">
            <small class="form-hint">Ürünün size maliyeti (opsiyonel).</small>
        </div>
        
        <!-- STOK BİLGİLERİ -->
        <h3 class="form-section-title" style="margin-top:30px;">
            <i class="fas fa-warehouse"></i> Stok Bilgileri
        </h3>
        
        <div class="form-row">
            <div class="form-group">
                <label>Stok Miktarı *</label>
                <input type="number" name="stok" placeholder="0" required
                       value="<?php echo isset($_POST['stok']) ? $_POST['stok'] : 0; ?>">
            </div>
            <div class="form-group">
                <label>Kritik Stok Seviyesi</label>
                <input type="number" name="kritik_stok" placeholder="5"
                       value="<?php echo isset($_POST['kritik_stok']) ? $_POST['kritik_stok'] : 5; ?>">
                <small class="form-hint">Bu seviyenin altına düşünce uyarı verilir.</small>
            </div>
        </div>
        
        <!-- RESİM -->
        <h3 class="form-section-title" style="margin-top:30px;">
            <i class="fas fa-images"></i> Ürün Resimleri
        </h3>
        
        <div class="form-group">
            <label>Ürün Resimleri</label>
            <input type="file" name="resimler[]" id="imageInput" accept="image/*" multiple>
            <small class="form-hint">
                Birden fazla resim seçmek için Ctrl tuşuna basılı tutun. 
                <br>İlk seçilen resim ana resim olur. 
                <br>Önerilen boyut: 800x800px. Maksimum 5MB.
                <br>Desteklenen formatlar: JPG, PNG, GIF, WEBP
            </small>
            <div id="imagePreviewContainer" style="margin-top:12px; display:flex; flex-wrap:wrap; gap:10px;"></div>
        </div>
        
        <!-- FORM BUTTONLARI -->
        <div class="form-actions">
            <a href="<?php echo SITE_URL; ?>admin/urunler.php" class="btn btn-outline">
                <i class="fas fa-times"></i> İptal
            </a>
            <button type="submit" name="urun_ekle" class="btn btn-primary" id="submitBtn">
                <i class="fas fa-save"></i> Ürünü Kaydet
            </button>
        </div>
        
    </form>
</div>

<!-- ============================================ -->
<!-- JAVASCRIPT -->
<!-- ============================================ -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // ============================================
    // 1. SLUG OTOMATİK OLUŞTUR
    // ============================================
    const nameInput = document.getElementById('productName');
    const slugInput = document.getElementById('productSlug');
    
    if (nameInput && slugInput) {
        nameInput.addEventListener('keyup', function() {
            if (!slugInput.dataset.edited) {
                const slug = this.value
                    .toLowerCase()
                    .replace(/ğ/g, 'g')
                    .replace(/ü/g, 'u')
                    .replace(/ş/g, 's')
                    .replace(/ı/g, 'i')
                    .replace(/ö/g, 'o')
                    .replace(/ç/g, 'c')
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-+|-+$/g, '');
                slugInput.value = slug;
            }
        });
        
        slugInput.addEventListener('focus', function() {
            this.dataset.edited = 'true';
        });
    }
    
    // ============================================
    // 2. ÜRÜN KODU OLUŞTUR (AJAX)
    // ============================================
    const generateBtn = document.getElementById('generateCodeBtn');
    const codeInput = document.getElementById('productCode');
    
    if (generateBtn && codeInput) {
        generateBtn.addEventListener('click', function() {
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Oluşturuluyor...';
            this.disabled = true;
            
            fetch('<?php echo SITE_URL; ?>admin/urun-ekle.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'generate_code=1'
            })
            .then(response => response.json())
            .then(data => {
                if (data.code) {
                    codeInput.value = data.code;
                    codeInput.style.borderColor = '#2ECC71';
                    codeInput.style.backgroundColor = '#D1FAE5';
                    setTimeout(() => {
                        codeInput.style.borderColor = '';
                        codeInput.style.backgroundColor = '';
                    }, 2000);
                }
            })
            .catch(error => {
                alert('Kod oluşturulurken bir hata oluştu!');
            })
            .finally(() => {
                this.innerHTML = originalText;
                this.disabled = false;
            });
        });
    }
    
    // ============================================
    // 3. ÇOKLU RESİM ÖN İZLEME
    // ============================================
    const imageInput = document.getElementById('imageInput');
    const previewContainer = document.getElementById('imagePreviewContainer');
    
    if (imageInput && previewContainer) {
        imageInput.addEventListener('change', function() {
            previewContainer.innerHTML = '';
            const files = this.files;
            
            if (files.length === 0) return;
            
            Array.from(files).forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const isFirst = index === 0;
                    const previewItem = document.createElement('div');
                    previewItem.className = 'image-preview-item';
                    previewItem.innerHTML = `
                        <img src="${e.target.result}" alt="Resim ${index + 1}">
                        <div class="image-preview-info">
                            <span class="image-name">${file.name}</span>
                            <span class="image-size">${(file.size / 1024).toFixed(0)} KB</span>
                            ${isFirst ? '<span class="image-main-badge">Ana Resim</span>' : ''}
                            <button type="button" class="remove-image" data-index="${index}">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    `;
                    previewContainer.appendChild(previewItem);
                    
                    const removeBtn = previewItem.querySelector('.remove-image');
                    removeBtn.addEventListener('click', function() {
                        previewItem.style.transform = 'scale(0.8)';
                        previewItem.style.opacity = '0';
                        setTimeout(() => {
                            previewItem.remove();
                            if (previewContainer.children.length === 0) {
                                imageInput.value = '';
                            }
                        }, 300);
                    });
                };
                reader.readAsDataURL(file);
            });
        });
    }
    
    // ============================================
    // 4. FORM DOĞRULAMA
    // ============================================
    document.querySelector('.product-form').addEventListener('submit', function(e) {
        const fiyat = parseFloat(document.querySelector('input[name="fiyat"]').value);
        const indirimli = document.querySelector('input[name="indirimli_fiyat"]').value;
        
        if (indirimli && parseFloat(indirimli) >= fiyat) {
            e.preventDefault();
            alert('İndirimli fiyat, normal fiyattan düşük olmalıdır!');
            return;
        }
        
        document.getElementById('submitBtn').disabled = true;
        document.getElementById('submitBtn').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Kaydediliyor...';
    });
    
});
</script>

<!-- ============================================ -->
<!-- CSS -->
<!-- ============================================ -->


<?php
include 'footer.php';
?>