<?php
// =====================================================
// SLIDER YÖNETİMİ - admin/slider.php
// =====================================================

// =====================================================
// AJAX İSTEKLERİ - HEADER'DAN ÖNCE
// =====================================================

// SLIDER SİL (AJAX)
if (isset($_POST['ajax_sil']) && isset($_POST['slider_id'])) {
    require_once '../includes/config.php';
    header('Content-Type: application/json');
    
    $slider_id = (int)$_POST['slider_id'];
    $response = ['success' => false, 'message' => ''];
    
    try {
        // Resmi sil
        $stmt = $db->prepare("SELECT resim_url FROM slider WHERE id = ?");
        $stmt->execute([$slider_id]);
        $slider = $stmt->fetch();
        
        if ($slider && $slider['resim_url']) {
            $file_path = '../uploads/slider/' . $slider['resim_url'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
        
        $stmt = $db->prepare("DELETE FROM slider WHERE id = ?");
        $stmt->execute([$slider_id]);
        
        $response['success'] = true;
        $response['message'] = 'Slider başarıyla silindi!';
    } catch (PDOException $e) {
        $response['message'] = 'Hata: ' . $e->getMessage();
    }
    
    echo json_encode($response);
    exit();
}

// SLIDER DETAY (AJAX - Düzenleme için)
if (isset($_POST['ajax_detay']) && isset($_POST['slider_id'])) {
    require_once '../includes/config.php';
    header('Content-Type: application/json');
    
    $slider_id = (int)$_POST['slider_id'];
    $response = ['success' => false, 'data' => null];
    
    try {
        $stmt = $db->prepare("SELECT * FROM slider WHERE id = ?");
        $stmt->execute([$slider_id]);
        $slider = $stmt->fetch();
        
        if ($slider) {
            $response['success'] = true;
            $response['data'] = $slider;
        } else {
            $response['message'] = 'Slider bulunamadı!';
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
    
    // SLIDER EKLE
    if (isset($_POST['slider_ekle'])) {
        $baslik = clean($_POST['baslik']);
        $alt_baslik = clean($_POST['alt_baslik']);
        $aciklama = clean($_POST['aciklama']);
        $link_url = clean($_POST['link_url']);
        $buton_metni = clean($_POST['buton_metni']);
        $sira = (int)$_POST['sira'];
        $durum = clean($_POST['durum']);
        
        $errors = [];
        
        if (empty($baslik)) $errors[] = 'Başlık boş olamaz.';
        
        // Resim yükleme
        $resim_url = '';
        if (empty($errors) && isset($_FILES['resim']) && $_FILES['resim']['error'] == 0) {
            $upload_dir = '../uploads/slider/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_name = $_FILES['resim']['name'];
            $file_size = $_FILES['resim']['size'];
            $file_tmp = $_FILES['resim']['tmp_name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (!in_array($file_ext, $allowed_ext)) {
                $errors[] = 'Sadece JPG, PNG, GIF, WEBP dosyaları yüklenebilir.';
            } elseif ($file_size > 5 * 1024 * 1024) { // 5MB
                $errors[] = 'Dosya boyutu 5MB\'dan büyük olamaz.';
            } else {
                $resim_url = 'slider_' . time() . '_' . uniqid() . '.' . $file_ext;
                $upload_path = $upload_dir . $resim_url;
                
                if (!move_uploaded_file($file_tmp, $upload_path)) {
                    $errors[] = 'Resim yüklenirken bir hata oluştu!';
                    $resim_url = '';
                }
            }
        } else {
            $errors[] = 'Lütfen bir resim seçin!';
        }
        
        if (empty($errors)) {
            try {
                $sql = "INSERT INTO slider (
                            baslik, alt_baslik, aciklama, resim_url, 
                            link_url, buton_metni, sira, durum
                        ) VALUES (
                            ?, ?, ?, ?, ?, ?, ?, ?
                        )";
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    $baslik, $alt_baslik, $aciklama, $resim_url,
                    $link_url, $buton_metni, $sira, $durum
                ]);
                
                $_SESSION['success'] = 'Slider başarıyla eklendi!';
                header("Location: " . SITE_URL . "admin/slider.php");
                exit();
            } catch (PDOException $e) {
                $_SESSION['error'] = 'Veritabanı hatası: ' . $e->getMessage();
                header("Location: " . SITE_URL . "admin/slider.php");
                exit();
            }
        } else {
            $_SESSION['error'] = implode('<br>', $errors);
            header("Location: " . SITE_URL . "admin/slider.php");
            exit();
        }
    }
    
    // SLIDER DÜZENLE
    if (isset($_POST['slider_duzenle'])) {
        $id = (int)$_POST['id'];
        $baslik = clean($_POST['baslik']);
        $alt_baslik = clean($_POST['alt_baslik']);
        $aciklama = clean($_POST['aciklama']);
        $link_url = clean($_POST['link_url']);
        $buton_metni = clean($_POST['buton_metni']);
        $sira = (int)$_POST['sira'];
        $durum = clean($_POST['durum']);
        
        $errors = [];
        
        if (empty($baslik)) $errors[] = 'Başlık boş olamaz.';
        
        // Resim yükleme
        $resim_url = null;
        if (empty($errors) && isset($_FILES['resim']) && $_FILES['resim']['error'] == 0) {
            $upload_dir = '../uploads/slider/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_name = $_FILES['resim']['name'];
            $file_size = $_FILES['resim']['size'];
            $file_tmp = $_FILES['resim']['tmp_name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (!in_array($file_ext, $allowed_ext)) {
                $errors[] = 'Sadece JPG, PNG, GIF, WEBP dosyaları yüklenebilir.';
            } elseif ($file_size > 5 * 1024 * 1024) {
                $errors[] = 'Dosya boyutu 5MB\'dan büyük olamaz.';
            } else {
                // Eski resmi sil
                $stmt = $db->prepare("SELECT resim_url FROM slider WHERE id = ?");
                $stmt->execute([$id]);
                $eski_slider = $stmt->fetch();
                if ($eski_slider && $eski_slider['resim_url']) {
                    $old_file = '../uploads/slider/' . $eski_slider['resim_url'];
                    if (file_exists($old_file)) {
                        unlink($old_file);
                    }
                }
                
                $resim_url = 'slider_' . time() . '_' . uniqid() . '.' . $file_ext;
                $upload_path = $upload_dir . $resim_url;
                
                if (!move_uploaded_file($file_tmp, $upload_path)) {
                    $errors[] = 'Resim yüklenirken bir hata oluştu!';
                    $resim_url = null;
                }
            }
        }
        
        if (empty($errors)) {
            try {
                if ($resim_url) {
                    $sql = "UPDATE slider SET 
                                baslik = ?, alt_baslik = ?, aciklama = ?, 
                                resim_url = ?, link_url = ?, buton_metni = ?, 
                                sira = ?, durum = ?
                            WHERE id = ?";
                    $stmt = $db->prepare($sql);
                    $stmt->execute([
                        $baslik, $alt_baslik, $aciklama, $resim_url,
                        $link_url, $buton_metni, $sira, $durum, $id
                    ]);
                } else {
                    $sql = "UPDATE slider SET 
                                baslik = ?, alt_baslik = ?, aciklama = ?, 
                                link_url = ?, buton_metni = ?, 
                                sira = ?, durum = ?
                            WHERE id = ?";
                    $stmt = $db->prepare($sql);
                    $stmt->execute([
                        $baslik, $alt_baslik, $aciklama,
                        $link_url, $buton_metni, $sira, $durum, $id
                    ]);
                }
                
                $_SESSION['success'] = 'Slider güncellendi!';
                header("Location: " . SITE_URL . "admin/slider.php");
                exit();
            } catch (PDOException $e) {
                $_SESSION['error'] = 'Veritabanı hatası: ' . $e->getMessage();
                header("Location: " . SITE_URL . "admin/slider.php");
                exit();
            }
        } else {
            $_SESSION['error'] = implode('<br>', $errors);
            header("Location: " . SITE_URL . "admin/slider.php");
            exit();
        }
    }
}

// =====================================================
// NORMAL SAYFA
// =====================================================
$page_title = 'Slider Yönetimi';
include 'header.php';

// Session mesajlarını al ve temizle
$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['error']);
unset($_SESSION['success']);

// =====================================================
// SLIDER LİSTESİNİ ÇEK
// =====================================================
try {
    $sliderlar = $db->query("SELECT * FROM slider ORDER BY sira ASC, id ASC")->fetchAll();
} catch (PDOException $e) {
    $sliderlar = [];
}
?>

<!-- ============================================ -->
<!-- SAYFA BAŞLIĞI -->
<!-- ============================================ -->
<div class="page-header">
    <div class="page-header-left">
        <h2><i class="fas fa-images"></i> Slider Yönetimi</h2>
        <span class="page-count"><?php echo count($sliderlar); ?> slider</span>
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
<!-- SLIDER EKLEME FORM -->
<!-- ============================================ -->
<div class="form-card">
    <h3 class="form-section-title">
        <i class="fas fa-plus-circle"></i> Yeni Slider Ekle
    </h3>
    <form method="POST" action="" enctype="multipart/form-data" class="slider-form">
        <div class="form-row">
            <div class="form-group">
                <label>Başlık *</label>
                <input type="text" name="baslik" placeholder="Slider başlığı" required>
            </div>
            <div class="form-group">
                <label>Alt Başlık</label>
                <input type="text" name="alt_baslik" placeholder="Slider alt başlığı">
            </div>
        </div>
        
        <div class="form-group">
            <label>Açıklama</label>
            <textarea name="aciklama" rows="3" placeholder="Slider açıklaması"></textarea>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Link URL</label>
                <input type="text" name="link_url" placeholder="Örn: public/urunler.php">
                <small class="form-hint">Butona tıklandığında gidilecek sayfa.</small>
            </div>
            <div class="form-group">
                <label>Buton Metni</label>
                <input type="text" name="buton_metni" placeholder="Örn: Hemen Alışverişe Başla">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Sıra</label>
                <input type="number" name="sira" placeholder="0" value="0">
                <small class="form-hint">Küçük sayı önce gösterilir.</small>
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
            <label>Slider Resmi *</label>
            <input type="file" name="resim" accept="image/*" required>
            <small class="form-hint">Önerilen boyut: 1920x800px. Maksimum 5MB. Desteklenen formatlar: JPG, PNG, GIF, WEBP</small>
            <div id="imagePreview" style="margin-top:8px;"></div>
        </div>
        
        <div class="form-actions">
            <button type="submit" name="slider_ekle" class="btn btn-primary">
                <i class="fas fa-save"></i> Slider Ekle
            </button>
        </div>
    </form>
</div>

<!-- ============================================ -->
<!-- SLIDER LİSTESİ -->
<!-- ============================================ -->
<div class="table-card">
    <div class="table-header">
        <h3><i class="fas fa-list"></i> Slider Listesi</h3>
    </div>
    
    <?php if (!empty($sliderlar)): ?>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th width="60">#</th>
                    <th width="120">Resim</th>
                    <th>Başlık</th>
                    <th>Buton</th>
                    <th>Sıra</th>
                    <th>Durum</th>
                    <th width="140">İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sliderlar as $slider): ?>
                <tr id="slider-row-<?php echo $slider['id']; ?>">
                    <td><?php echo $slider['id']; ?></td>
                    <td>
                        <img src="<?php echo SITE_URL; ?>uploads/slider/<?php echo $slider['resim_url']; ?>" 
                             alt="<?php echo $slider['baslik']; ?>" class="slider-thumb">
                    </td>
                    <td>
                        <strong><?php echo $slider['baslik']; ?></strong>
                        <?php if ($slider['alt_baslik']): ?>
                            <br><small style="color: var(--gray);"><?php echo $slider['alt_baslik']; ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($slider['buton_metni']): ?>
                            <?php echo $slider['buton_metni']; ?>
                            <br><small style="color: var(--gray);"><?php echo $slider['link_url']; ?></small>
                        <?php else: ?>
                            <span style="color: var(--gray);">-</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $slider['sira']; ?></td>
                    <td>
                        <span class="status-badge <?php echo $slider['durum']; ?>">
                            <?php echo $slider['durum'] == 'aktif' ? 'Aktif' : 'Pasif'; ?>
                        </span>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn btn-sm btn-primary" onclick="editSlider(<?php echo $slider['id']; ?>)" title="Düzenle">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deleteSlider(<?php echo $slider['id']; ?>)" title="Sil">
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
        <i class="fas fa-images"></i>
        <h3>Henüz slider eklenmemiş</h3>
        <p>İlk slider'ı ekleyerek başlayın.</p>
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
            <h3><i class="fas fa-edit"></i> Slider Düzenle</h3>
            <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="editForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" id="edit_id">
                <input type="hidden" name="slider_duzenle" value="1">
                
                <div class="form-group">
                    <label>Başlık *</label>
                    <input type="text" name="baslik" id="edit_baslik" required>
                </div>
                
                <div class="form-group">
                    <label>Alt Başlık</label>
                    <input type="text" name="alt_baslik" id="edit_alt_baslik">
                </div>
                
                <div class="form-group">
                    <label>Açıklama</label>
                    <textarea name="aciklama" id="edit_aciklama" rows="3"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Link URL</label>
                        <input type="text" name="link_url" id="edit_link_url" placeholder="Örn: public/urunler.php">
                    </div>
                    <div class="form-group">
                        <label>Buton Metni</label>
                        <input type="text" name="buton_metni" id="edit_buton_metni">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Sıra</label>
                        <input type="number" name="sira" id="edit_sira">
                    </div>
                    <div class="form-group">
                        <label>Durum</label>
                        <select name="durum" id="edit_durum">
                            <option value="aktif">Aktif</option>
                            <option value="pasif">Pasif</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Slider Resmi</label>
                    <input type="file" name="resim" accept="image/*">
                    <small class="form-hint">Yeni resim yüklemek için seçin. Mevcut resim korunur.</small>
                    <div id="edit_current_image" style="margin-top:8px;"></div>
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
            <p style="text-align:center;font-size:16px;margin-bottom:20px;">
                Bu slider'ı silmek istediğinize emin misiniz?
            </p>
            <input type="hidden" id="delete_slider_id">
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
    // RESİM ÖN İZLEME (Ekleme)
    // ============================================
    const imageInput = document.querySelector('input[name="resim"]');
    const preview = document.getElementById('imagePreview');
    
    if (imageInput && preview) {
        imageInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `
                        <img src="${e.target.result}" 
                             style="max-width:200px; max-height:150px; border-radius:8px; 
                                    border:2px solid var(--light-gray); padding:4px; object-fit:cover;">
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
// SLIDER DÜZENLE
// ============================================
function editSlider(id) {
    const submitBtn = document.getElementById('editSubmitBtn');
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Yükleniyor...';
    submitBtn.disabled = true;
    
    fetch('<?php echo SITE_URL; ?>admin/slider.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'ajax_detay=1&slider_id=' + id
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const s = data.data;
            document.getElementById('edit_id').value = s.id;
            document.getElementById('edit_baslik').value = s.baslik;
            document.getElementById('edit_alt_baslik').value = s.alt_baslik || '';
            document.getElementById('edit_aciklama').value = s.aciklama || '';
            document.getElementById('edit_link_url').value = s.link_url || '';
            document.getElementById('edit_buton_metni').value = s.buton_metni || '';
            document.getElementById('edit_sira').value = s.sira;
            document.getElementById('edit_durum').value = s.durum;
            
            // Mevcut resmi göster
            const imgDiv = document.getElementById('edit_current_image');
            if (s.resim_url) {
                imgDiv.innerHTML = `
                    <img src="<?php echo SITE_URL; ?>uploads/slider/${s.resim_url}" 
                         style="max-width:200px; max-height:150px; border-radius:8px; 
                                border:2px solid var(--light-gray); padding:4px; object-fit:cover;">
                    <br>
                    <small style="color: var(--gray);">Mevcut resim</small>
                `;
            } else {
                imgDiv.innerHTML = '<small style="color: var(--gray);">Resim yok</small>';
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
// SLIDER SİL
// ============================================
function deleteSlider(id) {
    document.getElementById('delete_slider_id').value = id;
    openModal('deleteModal');
}

document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    const id = document.getElementById('delete_slider_id').value;
    const btn = this;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Siliniyor...';
    btn.disabled = true;
    
    fetch('<?php echo SITE_URL; ?>admin/slider.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'ajax_sil=1&slider_id=' + id
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            closeModal('deleteModal');
            const row = document.getElementById('slider-row-' + id);
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