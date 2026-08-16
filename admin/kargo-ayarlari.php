<?php
// =====================================================
// KARGO AYARLARI - admin/kargo-ayarlari.php
// =====================================================

// =====================================================
// AJAX İSTEKLERİ - HEADER'DAN ÖNCE
// =====================================================

// KARGO FİRMASI SİL (AJAX)
if (isset($_POST['ajax_sil']) && isset($_POST['firma_id'])) {
    require_once '../includes/config.php';
    header('Content-Type: application/json');
    
    $firma_id = (int)$_POST['firma_id'];
    $response = ['success' => false, 'message' => ''];
    
    try {
        $stmt = $db->prepare("DELETE FROM kargo_firmalari WHERE id = ?");
        $stmt->execute([$firma_id]);
        
        $response['success'] = true;
        $response['message'] = 'Kargo firması silindi!';
    } catch (PDOException $e) {
        $response['message'] = 'Hata: ' . $e->getMessage();
    }
    
    echo json_encode($response);
    exit();
}

// KARGO FİRMASI DETAY (AJAX - Düzenleme için)
if (isset($_POST['ajax_detay']) && isset($_POST['firma_id'])) {
    require_once '../includes/config.php';
    header('Content-Type: application/json');
    
    $firma_id = (int)$_POST['firma_id'];
    $response = ['success' => false, 'data' => null];
    
    try {
        $stmt = $db->prepare("SELECT * FROM kargo_firmalari WHERE id = ?");
        $stmt->execute([$firma_id]);
        $firma = $stmt->fetch();
        
        if ($firma) {
            $response['success'] = true;
            $response['data'] = $firma;
        } else {
            $response['message'] = 'Kargo firması bulunamadı!';
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
    
    // KARGO FİRMASI EKLE
    if (isset($_POST['firma_ekle'])) {
        $ad = clean($_POST['ad']);
        $web_sitesi = clean($_POST['web_sitesi']);
        $takip_url = clean($_POST['takip_url']);
        $ucret = (float)$_POST['ucret'];
        $ucretsiz_siparis_tutari = !empty($_POST['ucretsiz_siparis_tutari']) ? (float)$_POST['ucretsiz_siparis_tutari'] : null;
        $agirlik_baslangic = !empty($_POST['agirlik_baslangic']) ? (float)$_POST['agirlik_baslangic'] : null;
        $agirlik_bitis = !empty($_POST['agirlik_bitis']) ? (float)$_POST['agirlik_bitis'] : null;
        $aktif = isset($_POST['aktif']) ? 1 : 0;
        $sira = (int)$_POST['sira'];
        
        $errors = [];
        if (empty($ad)) $errors[] = 'Firma adı boş olamaz.';
        if ($ucret < 0) $errors[] = 'Ücret 0\'dan küçük olamaz.';
        
        // Logo yükleme
        $logo_url = '';
        if (empty($errors) && isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
            $upload_dir = '../uploads/kargo/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_name = $_FILES['logo']['name'];
            $file_size = $_FILES['logo']['size'];
            $file_tmp = $_FILES['logo']['tmp_name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
            
            if (!in_array($file_ext, $allowed_ext)) {
                $errors[] = 'Sadece JPG, PNG, GIF, WEBP, SVG dosyaları yüklenebilir.';
            } elseif ($file_size > 2 * 1024 * 1024) {
                $errors[] = 'Dosya boyutu 2MB\'dan büyük olamaz.';
            } else {
                $logo_url = 'kargo_' . time() . '_' . uniqid() . '.' . $file_ext;
                $upload_path = $upload_dir . $logo_url;
                
                if (!move_uploaded_file($file_tmp, $upload_path)) {
                    $errors[] = 'Logo yüklenirken bir hata oluştu!';
                    $logo_url = '';
                }
            }
        }
        
        if (empty($errors)) {
            try {
                $sql = "INSERT INTO kargo_firmalari (
                            ad, logo_url, web_sitesi, takip_url, 
                            ucret, ucretsiz_siparis_tutari,
                            agirlik_baslangic, agirlik_bitis,
                            aktif, sira
                        ) VALUES (
                            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                        )";
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    $ad, $logo_url, $web_sitesi, $takip_url,
                    $ucret, $ucretsiz_siparis_tutari,
                    $agirlik_baslangic, $agirlik_bitis,
                    $aktif, $sira
                ]);
                
                $_SESSION['success'] = 'Kargo firması başarıyla eklendi!';
                header("Location: " . SITE_URL . "admin/kargo-ayarlari.php");
                exit();
            } catch (PDOException $e) {
                $_SESSION['error'] = 'Veritabanı hatası: ' . $e->getMessage();
                header("Location: " . SITE_URL . "admin/kargo-ayarlari.php");
                exit();
            }
        } else {
            $_SESSION['error'] = implode('<br>', $errors);
            header("Location: " . SITE_URL . "admin/kargo-ayarlari.php");
            exit();
        }
    }
    
    // KARGO FİRMASI DÜZENLE
    if (isset($_POST['firma_duzenle'])) {
        $id = (int)$_POST['id'];
        $ad = clean($_POST['ad']);
        $web_sitesi = clean($_POST['web_sitesi']);
        $takip_url = clean($_POST['takip_url']);
        $ucret = (float)$_POST['ucret'];
        $ucretsiz_siparis_tutari = !empty($_POST['ucretsiz_siparis_tutari']) ? (float)$_POST['ucretsiz_siparis_tutari'] : null;
        $agirlik_baslangic = !empty($_POST['agirlik_baslangic']) ? (float)$_POST['agirlik_baslangic'] : null;
        $agirlik_bitis = !empty($_POST['agirlik_bitis']) ? (float)$_POST['agirlik_bitis'] : null;
        $aktif = isset($_POST['aktif']) ? 1 : 0;
        $sira = (int)$_POST['sira'];
        
        $errors = [];
        if (empty($ad)) $errors[] = 'Firma adı boş olamaz.';
        if ($ucret < 0) $errors[] = 'Ücret 0\'dan küçük olamaz.';
        
        // Logo yükleme
        $logo_url = null;
        if (empty($errors) && isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
            $upload_dir = '../uploads/kargo/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_name = $_FILES['logo']['name'];
            $file_size = $_FILES['logo']['size'];
            $file_tmp = $_FILES['logo']['tmp_name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
            
            if (!in_array($file_ext, $allowed_ext)) {
                $errors[] = 'Sadece JPG, PNG, GIF, WEBP, SVG dosyaları yüklenebilir.';
            } elseif ($file_size > 2 * 1024 * 1024) {
                $errors[] = 'Dosya boyutu 2MB\'dan büyük olamaz.';
            } else {
                // Eski logoyu sil
                $stmt = $db->prepare("SELECT logo_url FROM kargo_firmalari WHERE id = ?");
                $stmt->execute([$id]);
                $eski_firma = $stmt->fetch();
                if ($eski_firma && $eski_firma['logo_url']) {
                    $old_file = '../uploads/kargo/' . $eski_firma['logo_url'];
                    if (file_exists($old_file)) {
                        unlink($old_file);
                    }
                }
                
                $logo_url = 'kargo_' . time() . '_' . uniqid() . '.' . $file_ext;
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
                    $sql = "UPDATE kargo_firmalari SET 
                                ad = ?, logo_url = ?, web_sitesi = ?, takip_url = ?,
                                ucret = ?, ucretsiz_siparis_tutari = ?,
                                agirlik_baslangic = ?, agirlik_bitis = ?,
                                aktif = ?, sira = ?
                            WHERE id = ?";
                    $stmt = $db->prepare($sql);
                    $stmt->execute([
                        $ad, $logo_url, $web_sitesi, $takip_url,
                        $ucret, $ucretsiz_siparis_tutari,
                        $agirlik_baslangic, $agirlik_bitis,
                        $aktif, $sira, $id
                    ]);
                } else {
                    $sql = "UPDATE kargo_firmalari SET 
                                ad = ?, web_sitesi = ?, takip_url = ?,
                                ucret = ?, ucretsiz_siparis_tutari = ?,
                                agirlik_baslangic = ?, agirlik_bitis = ?,
                                aktif = ?, sira = ?
                            WHERE id = ?";
                    $stmt = $db->prepare($sql);
                    $stmt->execute([
                        $ad, $web_sitesi, $takip_url,
                        $ucret, $ucretsiz_siparis_tutari,
                        $agirlik_baslangic, $agirlik_bitis,
                        $aktif, $sira, $id
                    ]);
                }
                
                $_SESSION['success'] = 'Kargo firması güncellendi!';
                header("Location: " . SITE_URL . "admin/kargo-ayarlari.php");
                exit();
            } catch (PDOException $e) {
                $_SESSION['error'] = 'Veritabanı hatası: ' . $e->getMessage();
                header("Location: " . SITE_URL . "admin/kargo-ayarlari.php");
                exit();
            }
        } else {
            $_SESSION['error'] = implode('<br>', $errors);
            header("Location: " . SITE_URL . "admin/kargo-ayarlari.php");
            exit();
        }
    }
}

// =====================================================
// NORMAL SAYFA
// =====================================================
$page_title = 'Kargo Ayarları';
include 'header.php';

// Session mesajlarını al ve temizle
$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['error']);
unset($_SESSION['success']);

// =====================================================
// KARGO FİRMALARINI ÇEK
// =====================================================
try {
    $firmalar = $db->query("SELECT * FROM kargo_firmalari ORDER BY sira ASC, id ASC")->fetchAll();
} catch (PDOException $e) {
    $firmalar = [];
    $error = 'Veritabanı hatası: ' . $e->getMessage();
}
?>

<!-- ============================================ -->
<!-- SAYFA BAŞLIĞI -->
<!-- ============================================ -->
<div class="page-header">
    <div class="page-header-left">
        <h2><i class="fas fa-shipping-fast"></i> Kargo Ayarları</h2>
        <span class="page-count"><?php echo count($firmalar); ?> firma</span>
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
<!-- KARGO FİRMASI EKLEME FORM -->
<!-- ============================================ -->
<div class="form-card">
    <h3 class="form-section-title">
        <i class="fas fa-plus-circle"></i> Yeni Kargo Firması Ekle
    </h3>
    <form method="POST" action="" enctype="multipart/form-data" class="kargo-form">
        <div class="form-row">
            <div class="form-group">
                <label>Firma Adı *</label>
                <input type="text" name="ad" placeholder="Kargo firması adı" required>
            </div>
            <div class="form-group">
                <label>Web Sitesi</label>
                <input type="url" name="web_sitesi" placeholder="https://www.kargo.com">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Takip URL Şablonu</label>
                <input type="text" name="takip_url" placeholder="https://www.kargo.com/takip/{takip_no}">
                <small class="form-hint">{takip_no} yerine takip numarası eklenecek.</small>
            </div>
            <div class="form-group">
                <label>Ücret (₺)</label>
                <input type="number" name="ucret" step="0.01" placeholder="0.00" required>
                <small class="form-hint">Standart kargo ücreti</small>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Ücretsiz Sipariş Tutarı (₺)</label>
                <input type="number" name="ucretsiz_siparis_tutari" step="0.01" placeholder="0.00">
                <small class="form-hint">Bu tutarın üzerindeki siparişlerde kargo ücretsiz.</small>
            </div>
            <div class="form-group">
                <label>Sıra</label>
                <input type="number" name="sira" placeholder="0" value="0">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Ağırlık Aralığı (Başlangıç - kg)</label>
                <input type="number" name="agirlik_baslangic" step="0.01" placeholder="0.00">
                <small class="form-hint">Bu ağırlıktan itibaren geçerli</small>
            </div>
            <div class="form-group">
                <label>Ağırlık Aralığı (Bitiş - kg)</label>
                <input type="number" name="agirlik_bitis" step="0.01" placeholder="0.00">
                <small class="form-hint">Bu ağırlığa kadar geçerli</small>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Logo</label>
                <input type="file" name="logo" accept="image/*">
                <small class="form-hint">Önerilen boyut: 200x80px. Maksimum 2MB.</small>
                <div id="imagePreview" style="margin-top:8px;"></div>
            </div>
            <div class="form-group">
                <label style="display:flex;align-items:center;gap:10px;cursor:pointer;margin-top:28px;">
                    <input type="checkbox" name="aktif" value="1" checked>
                    <span>Aktif</span>
                </label>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" name="firma_ekle" class="btn btn-primary">
                <i class="fas fa-save"></i> Firma Ekle
            </button>
        </div>
    </form>
</div>

<!-- ============================================ -->
<!-- KARGO FİRMALARI LİSTESİ -->
<!-- ============================================ -->
<div class="table-card">
    <div class="table-header">
        <h3><i class="fas fa-list"></i> Kargo Firmaları</h3>
    </div>
    
    <?php if (!empty($firmalar)): ?>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th width="60">#</th>
                    <th width="80">Logo</th>
                    <th>Firma Adı</th>
                    <th>Ücret</th>
                    <th>Ücretsiz Limit</th>
                    <th>Ağırlık Aralığı</th>
                    <th>Durum</th>
                    <th>Sıra</th>
                    <th width="140">İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($firmalar as $firma): ?>
                <tr id="firma-row-<?php echo $firma['id']; ?>">
                    <td><?php echo $firma['id']; ?></td>
                    <td>
                        <?php if ($firma['logo_url']): ?>
                            <img src="<?php echo SITE_URL; ?>uploads/kargo/<?php echo $firma['logo_url']; ?>" 
                                 alt="<?php echo $firma['ad']; ?>" class="firma-logo">
                        <?php else: ?>
                            <div class="firma-logo no-logo">
                                <i class="fas fa-truck"></i>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong><?php echo $firma['ad']; ?></strong>
                        <?php if ($firma['web_sitesi']): ?>
                            <br>
                            <a href="<?php echo $firma['web_sitesi']; ?>" target="_blank" style="font-size:12px;color:var(--gray);">
                                <i class="fas fa-external-link-alt"></i> <?php echo parse_url($firma['web_sitesi'], PHP_URL_HOST); ?>
                            </a>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php echo number_format($firma['ucret'], 2, ',', '.'); ?> ₺
                    </td>
                    <td>
                        <?php if ($firma['ucretsiz_siparis_tutari']): ?>
                            <?php echo number_format($firma['ucretsiz_siparis_tutari'], 2, ',', '.'); ?> ₺
                        <?php else: ?>
                            <span style="color: var(--gray);">Yok</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($firma['agirlik_baslangic'] || $firma['agirlik_bitis']): ?>
                            <?php echo ($firma['agirlik_baslangic'] ?? 0); ?> - 
                            <?php echo ($firma['agirlik_bitis'] ?? '∞'); ?> kg
                        <?php else: ?>
                            <span style="color: var(--gray);">Tümü</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="status-badge <?php echo $firma['aktif'] ? 'aktif' : 'pasif'; ?>">
                            <?php echo $firma['aktif'] ? 'Aktif' : 'Pasif'; ?>
                        </span>
                    </td>
                    <td><?php echo $firma['sira']; ?></td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn btn-sm btn-primary" onclick="editFirma(<?php echo $firma['id']; ?>)" title="Düzenle">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deleteFirma(<?php echo $firma['id']; ?>)" title="Sil">
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
        <i class="fas fa-truck"></i>
        <h3>Henüz kargo firması eklenmemiş</h3>
        <p>İlk firmayı ekleyerek başlayın.</p>
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
            <h3><i class="fas fa-edit"></i> Kargo Firması Düzenle</h3>
            <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="editForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" id="edit_id">
                <input type="hidden" name="firma_duzenle" value="1">
                
                <div class="form-group">
                    <label>Firma Adı *</label>
                    <input type="text" name="ad" id="edit_ad" required>
                </div>
                
                <div class="form-group">
                    <label>Web Sitesi</label>
                    <input type="url" name="web_sitesi" id="edit_web_sitesi" placeholder="https://www.kargo.com">
                </div>
                
                <div class="form-group">
                    <label>Takip URL Şablonu</label>
                    <input type="text" name="takip_url" id="edit_takip_url" placeholder="https://www.kargo.com/takip/{takip_no}">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Ücret (₺)</label>
                        <input type="number" name="ucret" id="edit_ucret" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Ücretsiz Sipariş Tutarı (₺)</label>
                        <input type="number" name="ucretsiz_siparis_tutari" id="edit_ucretsiz_siparis_tutari" step="0.01">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Ağırlık Başlangıç (kg)</label>
                        <input type="number" name="agirlik_baslangic" id="edit_agirlik_baslangic" step="0.01">
                    </div>
                    <div class="form-group">
                        <label>Ağırlık Bitiş (kg)</label>
                        <input type="number" name="agirlik_bitis" id="edit_agirlik_bitis" step="0.01">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Sıra</label>
                        <input type="number" name="sira" id="edit_sira">
                    </div>
                    <div class="form-group">
                        <label style="display:flex;align-items:center;gap:10px;cursor:pointer;margin-top:28px;">
                            <input type="checkbox" name="aktif" id="edit_aktif" value="1">
                            <span>Aktif</span>
                        </label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Logo</label>
                    <input type="file" name="logo" accept="image/*">
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
            <p style="text-align:center;font-size:16px;margin-bottom:20px;">
                Bu kargo firmasını silmek istediğinize emin misiniz?
            </p>
            <input type="hidden" id="delete_firma_id">
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
    const imageInput = document.querySelector('input[name="logo"]');
    const preview = document.getElementById('imagePreview');
    
    if (imageInput && preview) {
        imageInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `
                        <img src="${e.target.result}" 
                             style="max-width:150px; max-height:80px; border-radius:8px; 
                                    border:2px solid var(--light-gray); padding:4px; object-fit:contain;">
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
// KARGO FİRMASI DÜZENLE
// ============================================
function editFirma(id) {
    const submitBtn = document.getElementById('editSubmitBtn');
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Yükleniyor...';
    submitBtn.disabled = true;
    
    fetch('<?php echo SITE_URL; ?>admin/kargo-ayarlari.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'ajax_detay=1&firma_id=' + id
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const f = data.data;
            document.getElementById('edit_id').value = f.id;
            document.getElementById('edit_ad').value = f.ad;
            document.getElementById('edit_web_sitesi').value = f.web_sitesi || '';
            document.getElementById('edit_takip_url').value = f.takip_url || '';
            document.getElementById('edit_ucret').value = f.ucret;
            document.getElementById('edit_ucretsiz_siparis_tutari').value = f.ucretsiz_siparis_tutari || '';
            document.getElementById('edit_agirlik_baslangic').value = f.agirlik_baslangic || '';
            document.getElementById('edit_agirlik_bitis').value = f.agirlik_bitis || '';
            document.getElementById('edit_sira').value = f.sira;
            document.getElementById('edit_aktif').checked = f.aktif == 1;
            
            // Mevcut logoyu göster
            const imgDiv = document.getElementById('edit_current_logo');
            if (f.logo_url) {
                imgDiv.innerHTML = `
                    <img src="<?php echo SITE_URL; ?>uploads/kargo/${f.logo_url}" 
                         style="max-width:150px; max-height:80px; border-radius:8px; 
                                border:2px solid var(--light-gray); padding:4px; object-fit:contain;">
                    <br>
                    <small style="color: var(--gray);">Mevcut logo</small>
                `;
            } else {
                imgDiv.innerHTML = '<small style="color: var(--gray);">Logo yok</small>';
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
// KARGO FİRMASI SİL
// ============================================
function deleteFirma(id) {
    document.getElementById('delete_firma_id').value = id;
    openModal('deleteModal');
}

document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    const id = document.getElementById('delete_firma_id').value;
    const btn = this;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Siliniyor...';
    btn.disabled = true;
    
    fetch('<?php echo SITE_URL; ?>admin/kargo-ayarlari.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'ajax_sil=1&firma_id=' + id
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            closeModal('deleteModal');
            const row = document.getElementById('firma-row-' + id);
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