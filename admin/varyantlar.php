<?php
// =====================================================
// ÜRÜN VARYANT YÖNETİMİ - admin/varyantlar.php
// =====================================================

// =====================================================
// AJAX İSTEKLERİ - HEADER'DAN ÖNCE
// =====================================================

// VARYANT SİL (AJAX)
if (isset($_POST['ajax_sil']) && isset($_POST['varyant_id'])) {
    require_once '../includes/config.php';
    header('Content-Type: application/json');
    
    $varyant_id = (int)$_POST['varyant_id'];
    $response = ['success' => false, 'message' => ''];
    
    try {
        // Varyant resmini sil
        $stmt = $db->prepare("SELECT resim_url FROM urun_varyantlari WHERE id = ?");
        $stmt->execute([$varyant_id]);
        $varyant = $stmt->fetch();
        
        if ($varyant && $varyant['resim_url']) {
            $file_path = '../uploads/' . $varyant['resim_url'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
        
        $stmt = $db->prepare("DELETE FROM urun_varyantlari WHERE id = ?");
        $stmt->execute([$varyant_id]);
        
        $response['success'] = true;
        $response['message'] = 'Varyant silindi!';
    } catch (PDOException $e) {
        $response['message'] = 'Hata: ' . $e->getMessage();
    }
    
    echo json_encode($response);
    exit();
}

// VARYANT DETAY (AJAX - Düzenleme için)
if (isset($_POST['ajax_detay']) && isset($_POST['varyant_id'])) {
    require_once '../includes/config.php';
    header('Content-Type: application/json');
    
    $varyant_id = (int)$_POST['varyant_id'];
    $response = ['success' => false, 'data' => null];
    
    try {
        $stmt = $db->prepare("
            SELECT v.*, u.ad as urun_adi 
            FROM urun_varyantlari v
            LEFT JOIN urunler u ON v.urun_id = u.id
            WHERE v.id = ?
        ");
        $stmt->execute([$varyant_id]);
        $varyant = $stmt->fetch();
        
        if ($varyant) {
            $response['success'] = true;
            $response['data'] = $varyant;
        } else {
            $response['message'] = 'Varyant bulunamadı!';
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
    
    // VARYANT EKLE
    if (isset($_POST['varyant_ekle'])) {
        $urun_id = (int)$_POST['urun_id'];
        $varyant_kodu = clean($_POST['varyant_kodu']);
        $renk = clean($_POST['renk']);
        $beden = clean($_POST['beden']);
        $fiyat = !empty($_POST['fiyat']) ? (float)$_POST['fiyat'] : null;
        $indirimli_fiyat = !empty($_POST['indirimli_fiyat']) ? (float)$_POST['indirimli_fiyat'] : null;
        $maliyet = !empty($_POST['maliyet']) ? (float)$_POST['maliyet'] : null;
        $stok = (int)$_POST['stok'];
        
        $errors = [];
        
        if ($urun_id <= 0) $errors[] = 'Lütfen bir ürün seçin.';
        if (empty($varyant_kodu)) $errors[] = 'Varyant kodu boş olamaz.';
        if (empty($renk) && empty($beden)) $errors[] = 'En az bir özellik (renk veya beden) girin.';
        
        // Varyant kodu benzersiz mi?
        if (empty($errors)) {
            try {
                $stmt = $db->prepare("SELECT id FROM urun_varyantlari WHERE varyant_kodu = ?");
                $stmt->execute([$varyant_kodu]);
                if ($stmt->fetch()) {
                    $errors[] = 'Bu varyant kodu zaten kullanılıyor!';
                }
            } catch (PDOException $e) {}
        }
        
        // Resim yükleme
        $resim_url = '';
        if (empty($errors) && isset($_FILES['resim']) && $_FILES['resim']['error'] == 0) {
            $upload_dir = '../uploads/urunler/';
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
                $resim_url = 'varyant_' . time() . '_' . uniqid() . '.' . $file_ext;
                $upload_path = $upload_dir . $resim_url;
                
                if (!move_uploaded_file($file_tmp, $upload_path)) {
                    $errors[] = 'Resim yüklenirken bir hata oluştu!';
                    $resim_url = '';
                }
            }
        }
        
        if (empty($errors)) {
            try {
                $sql = "INSERT INTO urun_varyantlari (
                            urun_id, varyant_kodu, renk, beden, 
                            fiyat, indirimli_fiyat, maliyet, stok, resim_url
                        ) VALUES (
                            ?, ?, ?, ?, ?, ?, ?, ?, ?
                        )";
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    $urun_id, $varyant_kodu, $renk, $beden,
                    $fiyat, $indirimli_fiyat, $maliyet, $stok, $resim_url
                ]);
                
                $_SESSION['success'] = 'Varyant başarıyla eklendi!';
                header("Location: " . SITE_URL . "admin/varyantlar.php");
                exit();
            } catch (PDOException $e) {
                $_SESSION['error'] = 'Veritabanı hatası: ' . $e->getMessage();
                header("Location: " . SITE_URL . "admin/varyantlar.php");
                exit();
            }
        } else {
            $_SESSION['error'] = implode('<br>', $errors);
            header("Location: " . SITE_URL . "admin/varyantlar.php");
            exit();
        }
    }
    
    // VARYANT DÜZENLE
    if (isset($_POST['varyant_duzenle'])) {
        $id = (int)$_POST['id'];
        $urun_id = (int)$_POST['urun_id'];
        $varyant_kodu = clean($_POST['varyant_kodu']);
        $renk = clean($_POST['renk']);
        $beden = clean($_POST['beden']);
        $fiyat = !empty($_POST['fiyat']) ? (float)$_POST['fiyat'] : null;
        $indirimli_fiyat = !empty($_POST['indirimli_fiyat']) ? (float)$_POST['indirimli_fiyat'] : null;
        $maliyet = !empty($_POST['maliyet']) ? (float)$_POST['maliyet'] : null;
        $stok = (int)$_POST['stok'];
        
        $errors = [];
        
        if ($urun_id <= 0) $errors[] = 'Lütfen bir ürün seçin.';
        if (empty($varyant_kodu)) $errors[] = 'Varyant kodu boş olamaz.';
        
        // Varyant kodu benzersiz mi? (kendisi hariç)
        if (empty($errors)) {
            try {
                $stmt = $db->prepare("SELECT id FROM urun_varyantlari WHERE varyant_kodu = ? AND id != ?");
                $stmt->execute([$varyant_kodu, $id]);
                if ($stmt->fetch()) {
                    $errors[] = 'Bu varyant kodu zaten kullanılıyor!';
                }
            } catch (PDOException $e) {}
        }
        
        // Resim yükleme
        $resim_url = null;
        if (empty($errors) && isset($_FILES['resim']) && $_FILES['resim']['error'] == 0) {
            $upload_dir = '../uploads/urunler/';
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
                $stmt = $db->prepare("SELECT resim_url FROM urun_varyantlari WHERE id = ?");
                $stmt->execute([$id]);
                $eski_varyant = $stmt->fetch();
                if ($eski_varyant && $eski_varyant['resim_url']) {
                    $old_file = '../uploads/' . $eski_varyant['resim_url'];
                    if (file_exists($old_file)) {
                        unlink($old_file);
                    }
                }
                
                $resim_url = 'varyant_' . time() . '_' . uniqid() . '.' . $file_ext;
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
                    $sql = "UPDATE urun_varyantlari SET 
                                urun_id = ?, varyant_kodu = ?, renk = ?, beden = ?,
                                fiyat = ?, indirimli_fiyat = ?, maliyet = ?, stok = ?, resim_url = ?
                            WHERE id = ?";
                    $stmt = $db->prepare($sql);
                    $stmt->execute([
                        $urun_id, $varyant_kodu, $renk, $beden,
                        $fiyat, $indirimli_fiyat, $maliyet, $stok, $resim_url, $id
                    ]);
                } else {
                    $sql = "UPDATE urun_varyantlari SET 
                                urun_id = ?, varyant_kodu = ?, renk = ?, beden = ?,
                                fiyat = ?, indirimli_fiyat = ?, maliyet = ?, stok = ?
                            WHERE id = ?";
                    $stmt = $db->prepare($sql);
                    $stmt->execute([
                        $urun_id, $varyant_kodu, $renk, $beden,
                        $fiyat, $indirimli_fiyat, $maliyet, $stok, $id
                    ]);
                }
                
                $_SESSION['success'] = 'Varyant güncellendi!';
                header("Location: " . SITE_URL . "admin/varyantlar.php");
                exit();
            } catch (PDOException $e) {
                $_SESSION['error'] = 'Veritabanı hatası: ' . $e->getMessage();
                header("Location: " . SITE_URL . "admin/varyantlar.php");
                exit();
            }
        } else {
            $_SESSION['error'] = implode('<br>', $errors);
            header("Location: " . SITE_URL . "admin/varyantlar.php");
            exit();
        }
    }
}

// =====================================================
// NORMAL SAYFA
// =====================================================
$page_title = 'Varyant Yönetimi';
include 'header.php';

// Session mesajlarını al ve temizle
$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['error']);
unset($_SESSION['success']);

// =====================================================
// ÜRÜN LİSTESİNİ ÇEK (Select için)
// =====================================================
try {
    $urunler = $db->query("SELECT id, ad, urun_kodu FROM urunler WHERE durum = 'aktif' ORDER BY ad")->fetchAll();
} catch (PDOException $e) {
    $urunler = [];
}

// =====================================================
// VARYANT LİSTESİNİ ÇEK
// =====================================================
try {
    $stmt = $db->query("
        SELECT v.*, u.ad as urun_adi, u.urun_kodu 
        FROM urun_varyantlari v
        LEFT JOIN urunler u ON v.urun_id = u.id
        ORDER BY v.id DESC
    ");
    $varyantlar = $stmt->fetchAll();
} catch (PDOException $e) {
    $varyantlar = [];
}
?>

<!-- ============================================ -->
<!-- SAYFA BAŞLIĞI -->
<!-- ============================================ -->
<div class="page-header">
    <div class="page-header-left">
        <h2><i class="fas fa-layer-group"></i> Varyant Yönetimi</h2>
        <span class="page-count"><?php echo count($varyantlar); ?> varyant</span>
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
<!-- VARYANT EKLEME FORM -->
<!-- ============================================ -->
<div class="form-card">
    <h3 class="form-section-title">
        <i class="fas fa-plus-circle"></i> Yeni Varyant Ekle
    </h3>
    <form method="POST" action="" enctype="multipart/form-data" class="varyant-form">
        <div class="form-row">
            <div class="form-group">
                <label>Ürün *</label>
                <select name="urun_id" required>
                    <option value="">Ürün Seçin</option>
                    <?php foreach ($urunler as $urun): ?>
                        <option value="<?php echo $urun['id']; ?>">
                            <?php echo $urun['ad']; ?> (<?php echo $urun['urun_kodu']; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (empty($urunler)): ?>
                    <small class="form-hint" style="color: #FF6B6B;">
                        <i class="fas fa-warning"></i> Henüz ürün eklenmemiş. 
                        <a href="<?php echo SITE_URL; ?>admin/urun-ekle.php">Ürün Ekle</a>
                    </small>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label>Varyant Kodu *</label>
                <input type="text" name="varyant_kodu" placeholder="Benzersiz varyant kodu" required>
                <small class="form-hint">Örn: IPHONE-001-S, GOMLEK-001-L</small>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Renk</label>
                <input type="text" name="renk" placeholder="Örn: Siyah, Beyaz, Kırmızı">
            </div>
            <div class="form-group">
                <label>Beden</label>
                <input type="text" name="beden" placeholder="Örn: S, M, L, XL, 38, 40">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Fiyat</label>
                <input type="number" name="fiyat" step="0.01" placeholder="0.00">
                <small class="form-hint">Boş bırakırsanız ürün fiyatı kullanılır.</small>
            </div>
            <div class="form-group">
                <label>İndirimli Fiyat</label>
                <input type="number" name="indirimli_fiyat" step="0.01" placeholder="0.00">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Maliyet</label>
                <input type="number" name="maliyet" step="0.01" placeholder="0.00">
            </div>
            <div class="form-group">
                <label>Stok *</label>
                <input type="number" name="stok" placeholder="0" required>
            </div>
        </div>
        
        <div class="form-group">
            <label>Varyant Resmi</label>
            <input type="file" name="resim" accept="image/*">
            <small class="form-hint">Önerilen boyut: 800x800px. Maksimum 5MB.</small>
            <div id="imagePreview" style="margin-top:8px;"></div>
        </div>
        
        <div class="form-actions">
            <button type="submit" name="varyant_ekle" class="btn btn-primary">
                <i class="fas fa-save"></i> Varyant Ekle
            </button>
        </div>
    </form>
</div>

<!-- ============================================ -->
<!-- VARYANT LİSTESİ -->
<!-- ============================================ -->
<div class="table-card">
    <div class="table-header">
        <h3><i class="fas fa-list"></i> Varyant Listesi</h3>
    </div>
    
    <?php if (!empty($varyantlar)): ?>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th width="60">#</th>
                    <th>Ürün</th>
                    <th>Varyant Kodu</th>
                    <th>Renk</th>
                    <th>Beden</th>
                    <th>Fiyat</th>
                    <th>Stok</th>
                    <th width="140">İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($varyantlar as $varyant): ?>
                <tr id="varyant-row-<?php echo $varyant['id']; ?>">
                    <td><?php echo $varyant['id']; ?></td>
                    <td>
                        <strong><?php echo $varyant['urun_adi']; ?></strong>
                        <br><small style="color: var(--gray);"><?php echo $varyant['urun_kodu']; ?></small>
                    </td>
                    <td><code><?php echo $varyant['varyant_kodu']; ?></code></td>
                    <td><?php echo $varyant['renk'] ?? '-'; ?></td>
                    <td><?php echo $varyant['beden'] ?? '-'; ?></td>
                    <td>
                        <?php if ($varyant['fiyat']): ?>
                            <?php if ($varyant['indirimli_fiyat'] && $varyant['indirimli_fiyat'] < $varyant['fiyat']): ?>
                                <span class="old-price"><?php echo number_format($varyant['fiyat'], 2, ',', '.'); ?> ₺</span>
                                <br><span class="current-price"><?php echo number_format($varyant['indirimli_fiyat'], 2, ',', '.'); ?> ₺</span>
                            <?php else: ?>
                                <span class="current-price"><?php echo number_format($varyant['fiyat'], 2, ',', '.'); ?> ₺</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span style="color: var(--gray);">Ürün fiyatı</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="stock-badge <?php echo $varyant['stok'] <= 0 ? 'out-of-stock' : 'in-stock'; ?>">
                            <?php echo $varyant['stok']; ?> adet
                        </span>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn btn-sm btn-primary" onclick="editVaryant(<?php echo $varyant['id']; ?>)" title="Düzenle">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deleteVaryant(<?php echo $varyant['id']; ?>)" title="Sil">
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
        <i class="fas fa-layer-group"></i>
        <h3>Henüz varyant eklenmemiş</h3>
        <p>İlk varyantı ekleyerek başlayın.</p>
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
            <h3><i class="fas fa-edit"></i> Varyant Düzenle</h3>
            <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="editForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" id="edit_id">
                <input type="hidden" name="varyant_duzenle" value="1">
                
                <div class="form-group">
                    <label>Ürün *</label>
                    <select name="urun_id" id="edit_urun_id" required>
                        <option value="">Ürün Seçin</option>
                        <?php foreach ($urunler as $urun): ?>
                            <option value="<?php echo $urun['id']; ?>">
                                <?php echo $urun['ad']; ?> (<?php echo $urun['urun_kodu']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Varyant Kodu *</label>
                    <input type="text" name="varyant_kodu" id="edit_varyant_kodu" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Renk</label>
                        <input type="text" name="renk" id="edit_renk" placeholder="Örn: Siyah">
                    </div>
                    <div class="form-group">
                        <label>Beden</label>
                        <input type="text" name="beden" id="edit_beden" placeholder="Örn: M">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Fiyat</label>
                        <input type="number" name="fiyat" id="edit_fiyat" step="0.01" placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label>İndirimli Fiyat</label>
                        <input type="number" name="indirimli_fiyat" id="edit_indirimli_fiyat" step="0.01" placeholder="0.00">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Maliyet</label>
                        <input type="number" name="maliyet" id="edit_maliyet" step="0.01" placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label>Stok *</label>
                        <input type="number" name="stok" id="edit_stok" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Varyant Resmi</label>
                    <input type="file" name="resim" accept="image/*">
                    <small class="form-hint">Yeni resim yüklemek için seçin.</small>
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
            <p style="text-align:center;font-size:16px;margin-bottom:8px;">
                Bu varyantı silmek istediğinize emin misiniz?
            </p>
            <input type="hidden" id="delete_varyant_id">
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
// VARYANT DÜZENLE
// ============================================
function editVaryant(id) {
    const submitBtn = document.getElementById('editSubmitBtn');
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Yükleniyor...';
    submitBtn.disabled = true;
    
    fetch('<?php echo SITE_URL; ?>admin/varyantlar.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'ajax_detay=1&varyant_id=' + id
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const v = data.data;
            document.getElementById('edit_id').value = v.id;
            document.getElementById('edit_urun_id').value = v.urun_id;
            document.getElementById('edit_varyant_kodu').value = v.varyant_kodu;
            document.getElementById('edit_renk').value = v.renk || '';
            document.getElementById('edit_beden').value = v.beden || '';
            document.getElementById('edit_fiyat').value = v.fiyat || '';
            document.getElementById('edit_indirimli_fiyat').value = v.indirimli_fiyat || '';
            document.getElementById('edit_maliyet').value = v.maliyet || '';
            document.getElementById('edit_stok').value = v.stok;
            
            // Mevcut resmi göster
            const imgDiv = document.getElementById('edit_current_image');
            if (v.resim_url) {
                imgDiv.innerHTML = `
                    <img src="<?php echo SITE_URL; ?>uploads/${v.resim_url}" 
                         style="max-width:150px; max-height:150px; border-radius:8px; 
                                border:2px solid var(--light-gray); padding:4px;">
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
// VARYANT SİL
// ============================================
function deleteVaryant(id) {
    document.getElementById('delete_varyant_id').value = id;
    openModal('deleteModal');
}

document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    const id = document.getElementById('delete_varyant_id').value;
    const btn = this;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Siliniyor...';
    btn.disabled = true;
    
    fetch('<?php echo SITE_URL; ?>admin/varyantlar.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'ajax_sil=1&varyant_id=' + id
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            closeModal('deleteModal');
            const row = document.getElementById('varyant-row-' + id);
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