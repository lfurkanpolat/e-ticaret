<?php
// =====================================================
// MARKA YÖNETİMİ - admin/markalar.php
// =====================================================

// =====================================================
// AJAX İSTEKLERİ - HEADER'DAN ÖNCE
// =====================================================

// MARKA SİL (AJAX)
if (isset($_POST['ajax_sil']) && isset($_POST['marka_id'])) {
    require_once '../includes/config.php';
    header('Content-Type: application/json');
    
    $marka_id = (int)$_POST['marka_id'];
    $response = ['success' => false, 'message' => ''];
    
    try {
        // Bu markaya ait ürün var mı?
        $stmt = $db->prepare("SELECT COUNT(*) as toplam FROM urunler WHERE marka_id = ?");
        $stmt->execute([$marka_id]);
        $urun_sayisi = $stmt->fetch()['toplam'] ?? 0;
        
        if ($urun_sayisi > 0) {
            $response['message'] = 'Bu markada ' . $urun_sayisi . ' ürün var! Önce ürünleri taşıyın veya silin.';
            echo json_encode($response);
            exit();
        }
        
        // Marka logosunu sil
        $stmt = $db->prepare("SELECT logo_url FROM markalar WHERE id = ?");
        $stmt->execute([$marka_id]);
        $marka = $stmt->fetch();
        
        if ($marka && $marka['logo_url']) {
            $file_path = '../uploads/markalar/' . $marka['logo_url'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
        
        $stmt = $db->prepare("DELETE FROM markalar WHERE id = ?");
        $stmt->execute([$marka_id]);
        
        $response['success'] = true;
        $response['message'] = 'Marka silindi!';
    } catch (PDOException $e) {
        $response['message'] = 'Hata: ' . $e->getMessage();
    }
    
    echo json_encode($response);
    exit();
}

// MARKA DETAY (AJAX - Düzenleme için)
if (isset($_POST['ajax_detay']) && isset($_POST['marka_id'])) {
    require_once '../includes/config.php';
    header('Content-Type: application/json');
    
    $marka_id = (int)$_POST['marka_id'];
    $response = ['success' => false, 'data' => null];
    
    try {
        $stmt = $db->prepare("SELECT * FROM markalar WHERE id = ?");
        $stmt->execute([$marka_id]);
        $marka = $stmt->fetch();
        
        if ($marka) {
            $response['success'] = true;
            $response['data'] = $marka;
        } else {
            $response['message'] = 'Marka bulunamadı!';
        }
    } catch (PDOException $e) {
        $response['message'] = 'Hata: ' . $e->getMessage();
    }
    
    echo json_encode($response);
    exit();
}

// =====================================================
// NORMAL POST İŞLEMLERİ
// =====================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_once '../includes/config.php';
    
    // MARKA EKLE
    if (isset($_POST['marka_ekle'])) {
        $ad = clean($_POST['ad']);
        $slug = clean($_POST['slug']);
        $aciklama = clean($_POST['aciklama']);
        $web_sitesi = clean($_POST['web_sitesi']);
        $durum = clean($_POST['durum']);
        
        $errors = [];
        
        if (empty($ad)) $errors[] = 'Marka adı boş olamaz.';
        if (empty($slug)) $errors[] = 'Slug boş olamaz.';
        
        // Slug benzersiz mi?
        if (empty($errors)) {
            try {
                $stmt = $db->prepare("SELECT id FROM markalar WHERE slug = ?");
                $stmt->execute([$slug]);
                if ($stmt->fetch()) {
                    $errors[] = 'Bu slug zaten kullanılıyor!';
                }
            } catch (PDOException $e) {}
        }
        
        // Logo yükleme
        $logo_url = '';
        if (empty($errors) && isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
            $upload_dir = '../uploads/markalar/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_name = $_FILES['logo']['name'];
            $file_size = $_FILES['logo']['size'];
            $file_tmp = $_FILES['logo']['tmp_name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
            
            if (!in_array($file_ext, $allowed_ext)) {
                $errors[] = 'Sadece JPG, PNG, GIF, WEBP ve SVG dosyaları yüklenebilir.';
            } elseif ($file_size > 2 * 1024 * 1024) { // 2MB
                $errors[] = 'Dosya boyutu 2MB\'dan büyük olamaz.';
            } else {
                $logo_url = 'marka_' . time() . '_' . uniqid() . '.' . $file_ext;
                $upload_path = $upload_dir . $logo_url;
                
                if (!move_uploaded_file($file_tmp, $upload_path)) {
                    $errors[] = 'Logo yüklenirken bir hata oluştu!';
                    $logo_url = '';
                }
            }
        }
        
        if (empty($errors)) {
            try {
                $sql = "INSERT INTO markalar (ad, slug, aciklama, web_sitesi, logo_url, durum) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $db->prepare($sql);
                $stmt->execute([$ad, $slug, $aciklama, $web_sitesi, $logo_url, $durum]);
                
                $_SESSION['success'] = 'Marka başarıyla eklendi!';
                header("Location: " . SITE_URL . "admin/markalar.php");
                exit();
            } catch (PDOException $e) {
                $_SESSION['error'] = 'Veritabanı hatası: ' . $e->getMessage();
                header("Location: " . SITE_URL . "admin/markalar.php");
                exit();
            }
        } else {
            $_SESSION['error'] = implode('<br>', $errors);
            header("Location: " . SITE_URL . "admin/markalar.php");
            exit();
        }
    }
    
    // MARKA DÜZENLE
    if (isset($_POST['marka_duzenle'])) {
        $id = (int)$_POST['id'];
        $ad = clean($_POST['ad']);
        $slug = clean($_POST['slug']);
        $aciklama = clean($_POST['aciklama']);
        $web_sitesi = clean($_POST['web_sitesi']);
        $durum = clean($_POST['durum']);
        
        $errors = [];
        
        if (empty($ad)) $errors[] = 'Marka adı boş olamaz.';
        if (empty($slug)) $errors[] = 'Slug boş olamaz.';
        
        // Slug benzersiz mi? (kendisi hariç)
        if (empty($errors)) {
            try {
                $stmt = $db->prepare("SELECT id FROM markalar WHERE slug = ? AND id != ?");
                $stmt->execute([$slug, $id]);
                if ($stmt->fetch()) {
                    $errors[] = 'Bu slug zaten kullanılıyor!';
                }
            } catch (PDOException $e) {}
        }
        
        // Logo yükleme (varsa)
        $logo_url = null;
        if (empty($errors) && isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
            $upload_dir = '../uploads/markalar/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_name = $_FILES['logo']['name'];
            $file_size = $_FILES['logo']['size'];
            $file_tmp = $_FILES['logo']['tmp_name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
            
            if (!in_array($file_ext, $allowed_ext)) {
                $errors[] = 'Sadece JPG, PNG, GIF, WEBP ve SVG dosyaları yüklenebilir.';
            } elseif ($file_size > 2 * 1024 * 1024) {
                $errors[] = 'Dosya boyutu 2MB\'dan büyük olamaz.';
            } else {
                // Eski logoyu sil
                $stmt = $db->prepare("SELECT logo_url FROM markalar WHERE id = ?");
                $stmt->execute([$id]);
                $eski_marka = $stmt->fetch();
                if ($eski_marka && $eski_marka['logo_url']) {
                    $old_file = '../uploads/markalar/' . $eski_marka['logo_url'];
                    if (file_exists($old_file)) {
                        unlink($old_file);
                    }
                }
                
                $logo_url = 'marka_' . time() . '_' . uniqid() . '.' . $file_ext;
                $upload_path = $upload_dir . $logo_url;
                
                if (!move_uploaded_file($file_tmp, $upload_path)) {
                    $errors[] = 'Logo yüklenirken bir hata oluştu!';
                    $logo_url = null;
                }
            }
        }
        
        if (empty($errors)) {
            try {
                if ($logo_url) {
                    $sql = "UPDATE markalar SET ad = ?, slug = ?, aciklama = ?, web_sitesi = ?, logo_url = ?, durum = ? WHERE id = ?";
                    $stmt = $db->prepare($sql);
                    $stmt->execute([$ad, $slug, $aciklama, $web_sitesi, $logo_url, $durum, $id]);
                } else {
                    $sql = "UPDATE markalar SET ad = ?, slug = ?, aciklama = ?, web_sitesi = ?, durum = ? WHERE id = ?";
                    $stmt = $db->prepare($sql);
                    $stmt->execute([$ad, $slug, $aciklama, $web_sitesi, $durum, $id]);
                }
                
                $_SESSION['success'] = 'Marka güncellendi!';
                header("Location: " . SITE_URL . "admin/markalar.php");
                exit();
            } catch (PDOException $e) {
                $_SESSION['error'] = 'Veritabanı hatası: ' . $e->getMessage();
                header("Location: " . SITE_URL . "admin/markalar.php");
                exit();
            }
        } else {
            $_SESSION['error'] = implode('<br>', $errors);
            header("Location: " . SITE_URL . "admin/markalar.php");
            exit();
        }
    }
}

// =====================================================
// NORMAL SAYFA
// =====================================================
$page_title = 'Marka Yönetimi';
include 'header.php';

// Session mesajlarını al ve temizle
$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['error']);
unset($_SESSION['success']);

// =====================================================
// MARKA LİSTESİNİ ÇEK
// =====================================================
try {
    $stmt = $db->query("SELECT * FROM markalar ORDER BY ad ASC");
    $markalar = $stmt->fetchAll();
} catch (PDOException $e) {
    $markalar = [];
}
?>

<!-- ============================================ -->
<!-- SAYFA BAŞLIĞI -->
<!-- ============================================ -->
<div class="page-header">
    <div class="page-header-left">
        <h2><i class="fas fa-copyright"></i> Marka Yönetimi</h2>
        <span class="page-count"><?php echo count($markalar); ?> marka</span>
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
<!-- MARKA EKLEME FORM -->
<!-- ============================================ -->
<div class="form-card">
    <h3 class="form-section-title">
        <i class="fas fa-plus-circle"></i> Yeni Marka Ekle
    </h3>
    <form method="POST" action="" enctype="multipart/form-data" class="marka-form">
        <div class="form-row">
            <div class="form-group">
                <label>Marka Adı *</label>
                <input type="text" name="ad" id="markaAd" placeholder="Marka adı" required>
            </div>
            <div class="form-group">
                <label>Slug *</label>
                <input type="text" name="slug" id="markaSlug" placeholder="marka-adi" required>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Web Sitesi</label>
                <input type="url" name="web_sitesi" placeholder="https://www.marka.com">
            </div>
            <div class="form-group">
                <label>Durum</label>
                <select name="durum">
                    <option value="aktif">Aktif</option>
                    <option value="pasif">Pasif</option>
                </select>
            </div>
        </div>
        
        <div class="form-group">
            <label>Açıklama</label>
            <textarea name="aciklama" rows="3" placeholder="Marka açıklaması"></textarea>
        </div>
        
        <div class="form-group">
            <label>Marka Logosu</label>
            <input type="file" name="logo" accept="image/*">
            <small class="form-hint">Önerilen boyut: 200x200px. Maksimum 2MB. Desteklenen formatlar: JPG, PNG, GIF, WEBP, SVG</small>
            <div id="imagePreview" style="margin-top:8px;"></div>
        </div>
        
        <div class="form-actions">
            <button type="submit" name="marka_ekle" class="btn btn-primary">
                <i class="fas fa-save"></i> Marka Ekle
            </button>
        </div>
    </form>
</div>

<!-- ============================================ -->
<!-- MARKA LİSTESİ -->
<!-- ============================================ -->
<div class="table-card">
    <div class="table-header">
        <h3><i class="fas fa-list"></i> Marka Listesi</h3>
    </div>
    
    <?php if (!empty($markalar)): ?>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th width="60">#</th>
                    <th width="80">Logo</th>
                    <th>Marka Adı</th>
                    <th>Slug</th>
                    <th>Web Sitesi</th>
                    <th>Durum</th>
                    <th width="140">İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($markalar as $marka): ?>
                <tr id="marka-row-<?php echo $marka['id']; ?>">
                    <td><?php echo $marka['id']; ?></td>
                    <td>
                        <?php if ($marka['logo_url']): ?>
                            <img src="<?php echo SITE_URL; ?>uploads/markalar/<?php echo $marka['logo_url']; ?>" alt="<?php echo $marka['ad']; ?>" class="marka-logo">
                        <?php else: ?>
                            <div class="marka-logo no-logo">
                                <i class="fas fa-image"></i>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td><strong><?php echo $marka['ad']; ?></strong></td>
                    <td><code><?php echo $marka['slug']; ?></code></td>
                    <td>
                        <?php if ($marka['web_sitesi']): ?>
                            <a href="<?php echo $marka['web_sitesi']; ?>" target="_blank" title="<?php echo $marka['web_sitesi']; ?>">
                                <i class="fas fa-external-link-alt"></i> Ziyaret Et
                            </a>
                        <?php else: ?>
                            <span style="color: var(--gray);">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="status-badge <?php echo $marka['durum']; ?>">
                            <?php echo $marka['durum'] == 'aktif' ? 'Aktif' : 'Pasif'; ?>
                        </span>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn btn-sm btn-primary" onclick="editMarka(<?php echo $marka['id']; ?>)" title="Düzenle">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deleteMarka(<?php echo $marka['id']; ?>)" title="Sil">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <i class="fas fa-copyright"></i>
        <h3>Henüz marka eklenmemiş</h3>
        <p>İlk markayı ekleyerek başlayın.</p>
    </div>
    <?php endif; ?>
</div>

<!-- ============================================ -->
<!-- DÜZENLEME MODAL -->
<!-- ============================================ -->
<div class="modal" id="editModal">
    <div class="modal-overlay" onclick="closeModal('editModal')"></div>
    <div class="modal-content modal-small">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Marka Düzenle</h3>
            <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="editForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" id="edit_id">
                <input type="hidden" name="marka_duzenle" value="1">
                
                <div class="form-group">
                    <label>Marka Adı *</label>
                    <input type="text" name="ad" id="edit_ad" required>
                </div>
                
                <div class="form-group">
                    <label>Slug *</label>
                    <input type="text" name="slug" id="edit_slug" required>
                </div>
                
                <div class="form-group">
                    <label>Web Sitesi</label>
                    <input type="url" name="web_sitesi" id="edit_web_sitesi" placeholder="https://www.marka.com">
                </div>
                
                <div class="form-group">
                    <label>Durum</label>
                    <select name="durum" id="edit_durum">
                        <option value="aktif">Aktif</option>
                        <option value="pasif">Pasif</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Açıklama</label>
                    <textarea name="aciklama" id="edit_aciklama" rows="3" placeholder="Marka açıklaması"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Marka Logosu</label>
                    <input type="file" name="logo" id="edit_logo" accept="image/*">
                    <small class="form-hint">Yeni logo yüklemek için seçin. Mevcut logo korunur.</small>
                    <div id="edit_current_logo" style="margin-top:8px;"></div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-outline" onclick="closeModal('editModal')">İptal</button>
                    <button type="submit" class="btn btn-primary" id="editSubmitBtn">
                        <i class="fas fa-save"></i> Güncelle
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- SİLME ONAY MODAL -->
<!-- ============================================ -->
<div class="modal" id="deleteModal">
    <div class="modal-overlay" onclick="closeModal('deleteModal')"></div>
    <div class="modal-content modal-small">
        <div class="modal-header">
            <h3 style="color: #FF6B6B;"><i class="fas fa-exclamation-triangle"></i> Silme Onayı</h3>
            <button class="modal-close" onclick="closeModal('deleteModal')">&times;</button>
        </div>
        <div class="modal-body">
            <p style="text-align:center;font-size:16px;margin-bottom:8px;">
                Bu markayı silmek istediğinize emin misiniz?
            </p>
            <p style="text-align:center;color:var(--gray);font-size:14px;margin-bottom:20px;">
                <i class="fas fa-info-circle"></i> Bu markaya ait ürünler varsa silinemez.
            </p>
            <input type="hidden" id="delete_marka_id">
            <div class="form-actions" style="justify-content:center;">
                <button class="btn btn-outline" onclick="closeModal('deleteModal')">İptal</button>
                <button class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="fas fa-trash"></i> Evet, Sil
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- JAVASCRIPT -->
<!-- ============================================ -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // ============================================
    // SLUG OTOMATİK OLUŞTUR
    // ============================================
    const adInput = document.getElementById('markaAd');
    const slugInput = document.getElementById('markaSlug');
    
    if (adInput && slugInput) {
        adInput.addEventListener('keyup', function() {
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
    // RESİM ÖN İZLEME (Ekleme)
    // ============================================
    const logoInput = document.querySelector('input[name="logo"]');
    const preview = document.getElementById('imagePreview');
    
    if (logoInput && preview) {
        logoInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `
                        <img src="${e.target.result}" 
                             style="max-width:150px; max-height:150px; border-radius:8px; 
                                    border:2px solid var(--light-gray); padding:4px;">
                        <br>
                        <small style="color: var(--gray);">${file.name} (${(file.size / 1024).toFixed(0)} KB)</small>
                    `;
                };
                reader.readAsDataURL(file);
            } else {
                preview.innerHTML = '';
            }
        });
    }
    
});

// ============================================
// MODAL İŞLEMLERİ
// ============================================
function openModal(id) {
    document.getElementById(id).classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeModal(id) {
    document.getElementById(id).classList.remove('active');
    document.body.style.overflow = '';
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal.active').forEach(function(m) {
            m.classList.remove('active');
            document.body.style.overflow = '';
        });
    }
});

// ============================================
// MARKA DÜZENLE
// ============================================
function editMarka(id) {
    const submitBtn = document.getElementById('editSubmitBtn');
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Yükleniyor...';
    submitBtn.disabled = true;
    
    fetch('<?php echo SITE_URL; ?>admin/markalar.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'ajax_detay=1&marka_id=' + id
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const marka = data.data;
            document.getElementById('edit_id').value = marka.id;
            document.getElementById('edit_ad').value = marka.ad;
            document.getElementById('edit_slug').value = marka.slug;
            document.getElementById('edit_web_sitesi').value = marka.web_sitesi || '';
            document.getElementById('edit_aciklama').value = marka.aciklama || '';
            document.getElementById('edit_durum').value = marka.durum;
            
            // Mevcut logoyu göster
            const logoDiv = document.getElementById('edit_current_logo');
            if (marka.logo_url) {
                logoDiv.innerHTML = `
                    <img src="<?php echo SITE_URL; ?>uploads/markalar/${marka.logo_url}" 
                         style="max-width:150px; max-height:150px; border-radius:8px; 
                                border:2px solid var(--light-gray); padding:4px;">
                    <br>
                    <small style="color: var(--gray);">Mevcut logo</small>
                `;
            } else {
                logoDiv.innerHTML = '<small style="color: var(--gray);">Logo yok</small>';
            }
            
            openModal('editModal');
        } else {
            alert('Hata: ' + data.message);
        }
    })
    .catch(error => {
        alert('Bir hata oluştu: ' + error);
    })
    .finally(() => {
        submitBtn.innerHTML = '<i class="fas fa-save"></i> Güncelle';
        submitBtn.disabled = false;
    });
}

// ============================================
// MARKA SİL
// ============================================
function deleteMarka(id) {
    document.getElementById('delete_marka_id').value = id;
    openModal('deleteModal');
}

document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    const id = document.getElementById('delete_marka_id').value;
    const btn = this;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Siliniyor...';
    btn.disabled = true;
    
    fetch('<?php echo SITE_URL; ?>admin/markalar.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'ajax_sil=1&marka_id=' + id
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            closeModal('deleteModal');
            const row = document.getElementById('marka-row-' + id);
            if (row) {
                row.style.transition = 'all 0.3s';
                row.style.opacity = '0';
                row.style.transform = 'translateX(-20px)';
                setTimeout(() => {
                    row.remove();
                    location.reload();
                }, 300);
            }
        } else {
            alert('Hata: ' + data.message);
        }
    })
    .catch(error => {
        alert('Bir hata oluştu: ' + error);
    })
    .finally(() => {
        btn.innerHTML = '<i class="fas fa-trash"></i> Evet, Sil';
        btn.disabled = false;
    });
});
</script>

<?php
include 'footer.php';
?>