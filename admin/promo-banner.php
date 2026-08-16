<?php
// =====================================================
// PROMO BANNER YÖNETİMİ - admin/promo-banner.php
// =====================================================

$page_title = 'Promo Banner Yönetimi';
include 'header.php';

// =====================================================
// AJAX - BANNER SİL
// =====================================================
if (isset($_POST['ajax_sil']) && isset($_POST['banner_id'])) {
    require_once '../includes/config.php';
    header('Content-Type: application/json');
    
    $banner_id = (int)$_POST['banner_id'];
    $response = ['success' => false, 'message' => ''];
    
    try {
        $stmt = $db->prepare("DELETE FROM promo_banners WHERE id = ?");
        $stmt->execute([$banner_id]);
        $response['success'] = true;
        $response['message'] = 'Banner silindi!';
    } catch (PDOException $e) {
        $response['message'] = 'Hata: ' . $e->getMessage();
    }
    
    echo json_encode($response);
    exit();
}

// =====================================================
// AJAX - BANNER DETAY
// =====================================================
if (isset($_POST['ajax_detay']) && isset($_POST['banner_id'])) {
    require_once '../includes/config.php';
    header('Content-Type: application/json');
    
    $banner_id = (int)$_POST['banner_id'];
    $response = ['success' => false, 'data' => null];
    
    try {
        $stmt = $db->prepare("SELECT * FROM promo_banners WHERE id = ?");
        $stmt->execute([$banner_id]);
        $banner = $stmt->fetch();
        
        if ($banner) {
            $response['success'] = true;
            $response['data'] = $banner;
        } else {
            $response['message'] = 'Banner bulunamadı!';
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
    
    // BANNER EKLE
    if (isset($_POST['banner_ekle'])) {
        $baslik = clean($_POST['baslik']);
        $alt_baslik = clean($_POST['alt_baslik']);
        $aciklama = clean($_POST['aciklama']);
        $buton_metni = clean($_POST['buton_metni']);
        $buton_link = clean($_POST['buton_link']);
        $renk = clean($_POST['renk']);
        $sira = (int)$_POST['sira'];
        $durum = clean($_POST['durum']);
        $resim_url = clean($_POST['resim_url']);
        
        $errors = [];
        if (empty($baslik)) $errors[] = 'Başlık boş olamaz.';
        
        if (empty($errors)) {
            try {
                $sql = "INSERT INTO promo_banners (
                            baslik, alt_baslik, aciklama, buton_metni, buton_link, 
                            renk, resim_url, sira, durum
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    $baslik, $alt_baslik, $aciklama, $buton_metni, $buton_link,
                    $renk, $resim_url, $sira, $durum
                ]);
                
                $_SESSION['success'] = 'Banner başarıyla eklendi!';
                header("Location: " . SITE_URL . "admin/promo-banner.php");
                exit();
            } catch (PDOException $e) {
                $_SESSION['error'] = 'Veritabanı hatası: ' . $e->getMessage();
                header("Location: " . SITE_URL . "admin/promo-banner.php");
                exit();
            }
        } else {
            $_SESSION['error'] = implode('<br>', $errors);
            header("Location: " . SITE_URL . "admin/promo-banner.php");
            exit();
        }
    }
    
    // BANNER DÜZENLE
    if (isset($_POST['banner_duzenle'])) {
        $id = (int)$_POST['id'];
        $baslik = clean($_POST['baslik']);
        $alt_baslik = clean($_POST['alt_baslik']);
        $aciklama = clean($_POST['aciklama']);
        $buton_metni = clean($_POST['buton_metni']);
        $buton_link = clean($_POST['buton_link']);
        $renk = clean($_POST['renk']);
        $sira = (int)$_POST['sira'];
        $durum = clean($_POST['durum']);
        $resim_url = clean($_POST['resim_url']);
        
        $errors = [];
        if (empty($baslik)) $errors[] = 'Başlık boş olamaz.';
        
        if (empty($errors)) {
            try {
                $sql = "UPDATE promo_banners SET 
                            baslik = ?, alt_baslik = ?, aciklama = ?, 
                            buton_metni = ?, buton_link = ?,
                            renk = ?, resim_url = ?, sira = ?, durum = ?
                        WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    $baslik, $alt_baslik, $aciklama, $buton_metni, $buton_link,
                    $renk, $resim_url, $sira, $durum, $id
                ]);
                
                $_SESSION['success'] = 'Banner güncellendi!';
                header("Location: " . SITE_URL . "admin/promo-banner.php");
                exit();
            } catch (PDOException $e) {
                $_SESSION['error'] = 'Veritabanı hatası: ' . $e->getMessage();
                header("Location: " . SITE_URL . "admin/promo-banner.php");
                exit();
            }
        } else {
            $_SESSION['error'] = implode('<br>', $errors);
            header("Location: " . SITE_URL . "admin/promo-banner.php");
            exit();
        }
    }
}

// Session mesajlarını al ve temizle
$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['error']);
unset($_SESSION['success']);

// Banner listesini çek
try {
    $bannerler = $db->query("SELECT * FROM promo_banners ORDER BY sira ASC, id ASC")->fetchAll();
} catch (PDOException $e) {
    $bannerler = [];
}

// Renk seçenekleri
$renkler = [
    'purple' => 'Mor',
    'dark' => 'Koyu',
    'orange' => 'Turuncu',
    'blue' => 'Mavi',
    'green' => 'Yeşil',
    'red' => 'Kırmızı',
    'pink' => 'Pembe'
];
?>

<!-- ============================================ -->
<!-- SAYFA BAŞLIĞI -->
<!-- ============================================ -->
<div class="page-header">
    <div class="page-header-left">
        <h2><i class="fas fa-ad"></i> Promo Banner Yönetimi</h2>
        <span class="page-count"><?php echo count($bannerler); ?> banner</span>
    </div>
</div>

<!-- ============================================ -->
<!-- HATA VE BAŞARI MESAJLARI -->
<!-- ============================================ -->
<?php if ($error): ?>
    <div class="alert alert-error"><?php echo $error; ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<!-- ============================================ -->
<!-- BANNER EKLEME FORM -->
<!-- ============================================ -->
<div class="form-card">
    <h3 class="form-section-title">
        <i class="fas fa-plus-circle"></i> Yeni Banner Ekle
    </h3>
    <form method="POST" action="" class="banner-form">
        <div class="form-row">
            <div class="form-group">
                <label>Başlık *</label>
                <input type="text" name="baslik" placeholder="Banner başlığı" required>
            </div>
            <div class="form-group">
                <label>Alt Başlık</label>
                <input type="text" name="alt_baslik" placeholder="Alt başlık">
            </div>
        </div>
        
        <div class="form-group">
            <label>Açıklama</label>
            <textarea name="aciklama" rows="2" placeholder="Banner açıklaması"></textarea>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Buton Metni</label>
                <input type="text" name="buton_metni" placeholder="Örn: Ürünü İncele">
            </div>
            <div class="form-group">
                <label>Buton Linki</label>
                <input type="text" name="buton_link" placeholder="Örn: public/urunler.php">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Renk</label>
                <select name="renk">
                    <?php foreach ($renkler as $key => $value): ?>
                        <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Resim URL</label>
                <input type="text" name="resim_url" placeholder="https://...">
                <small class="form-hint">Opsiyonel. Banner'da görsel göstermek isterseniz.</small>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Sıra</label>
                <input type="number" name="sira" placeholder="0" value="0">
            </div>
            <div class="form-group">
                <label>Durum</label>
                <select name="durum">
                    <option value="aktif">Aktif</option>
                    <option value="pasif">Pasif</option>
                </select>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" name="banner_ekle" class="btn btn-primary">
                <i class="fas fa-save"></i> Banner Ekle
            </button>
        </div>
    </form>
</div>

<!-- ============================================ -->
<!-- BANNER LİSTESİ -->
<!-- ============================================ -->
<div class="table-card">
    <div class="table-header">
        <h3><i class="fas fa-list"></i> Banner Listesi</h3>
    </div>
    
    <?php if (!empty($bannerler)): ?>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th width="60">#</th>
                    <th>Başlık</th>
                    <th>Buton</th>
                    <th>Renk</th>
                    <th>Resim</th>
                    <th>Sıra</th>
                    <th>Durum</th>
                    <th width="140">İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bannerler as $banner): ?>
                <tr id="banner-row-<?php echo $banner['id']; ?>">
                    <td><?php echo $banner['id']; ?></td>
                    <td>
                        <strong><?php echo $banner['baslik']; ?></strong>
                        <?php if ($banner['alt_baslik']): ?>
                            <br><small style="color: var(--gray);"><?php echo $banner['alt_baslik']; ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($banner['buton_metni']): ?>
                            <?php echo $banner['buton_metni']; ?>
                            <br><small style="color: var(--gray);"><?php echo $banner['buton_link']; ?></small>
                        <?php else: ?>
                            <span style="color: var(--gray);">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="color-badge" style="background: <?php 
                            $color_map = [
                                'purple' => '#6937ff',
                                'dark' => '#111',
                                'orange' => '#ff9800',
                                'blue' => '#2196F3',
                                'green' => '#4CAF50',
                                'red' => '#f44336',
                                'pink' => '#E91E63'
                            ];
                            echo $color_map[$banner['renk']] ?? '#6937ff';
                        ?>; color: #fff; padding: 4px 12px; border-radius: 50px; font-size: 12px; font-weight: 600;">
                            <?php echo $renkler[$banner['renk']] ?? $banner['renk']; ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($banner['resim_url']): ?>
                            <img src="<?php echo $banner['resim_url']; ?>" style="width: 60px; height: 40px; object-fit: cover; border-radius: 6px;">
                        <?php else: ?>
                            <span style="color: var(--gray);">Yok</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $banner['sira']; ?></td>
                    <td>
                        <span class="status-badge <?php echo $banner['durum']; ?>">
                            <?php echo $banner['durum'] == 'aktif' ? 'Aktif' : 'Pasif'; ?>
                        </span>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn btn-sm btn-primary" onclick="editBanner(<?php echo $banner['id']; ?>)" title="Düzenle">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deleteBanner(<?php echo $banner['id']; ?>)" title="Sil">
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
        <i class="fas fa-ad"></i>
        <h3>Henüz banner eklenmemiş</h3>
        <p>İlk banner'ı ekleyerek başlayın.</p>
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
            <h3><i class="fas fa-edit"></i> Banner Düzenle</h3>
            <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="editForm" method="POST">
                <input type="hidden" name="id" id="edit_id">
                <input type="hidden" name="banner_duzenle" value="1">
                
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
                    <textarea name="aciklama" id="edit_aciklama" rows="2"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Buton Metni</label>
                        <input type="text" name="buton_metni" id="edit_buton_metni">
                    </div>
                    <div class="form-group">
                        <label>Buton Linki</label>
                        <input type="text" name="buton_link" id="edit_buton_link">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Renk</label>
                        <select name="renk" id="edit_renk">
                            <?php foreach ($renkler as $key => $value): ?>
                                <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Resim URL</label>
                        <input type="text" name="resim_url" id="edit_resim_url" placeholder="https://...">
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
            <p style="text-align:center;font-size:16px;margin-bottom:20px;">Bu banner'ı silmek istediğinize emin misiniz?</p>
            <input type="hidden" id="delete_banner_id">
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
// BANNER DÜZENLE
// ============================================
function editBanner(id) {
    const submitBtn = document.getElementById('editSubmitBtn');
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Yükleniyor...';
    submitBtn.disabled = true;
    
    fetch('<?php echo SITE_URL; ?>admin/promo-banner.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'ajax_detay=1&banner_id=' + id
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const b = data.data;
            document.getElementById('edit_id').value = b.id;
            document.getElementById('edit_baslik').value = b.baslik;
            document.getElementById('edit_alt_baslik').value = b.alt_baslik || '';
            document.getElementById('edit_aciklama').value = b.aciklama || '';
            document.getElementById('edit_buton_metni').value = b.buton_metni || '';
            document.getElementById('edit_buton_link').value = b.buton_link || '';
            document.getElementById('edit_renk').value = b.renk;
            document.getElementById('edit_resim_url').value = b.resim_url || '';
            document.getElementById('edit_sira').value = b.sira;
            document.getElementById('edit_durum').value = b.durum;
            
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
// BANNER SİL
// ============================================
function deleteBanner(id) {
    document.getElementById('delete_banner_id').value = id;
    openModal('deleteModal');
}

document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    const id = document.getElementById('delete_banner_id').value;
    const btn = this;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Siliniyor...';
    btn.disabled = true;
    
    fetch('<?php echo SITE_URL; ?>admin/promo-banner.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'ajax_sil=1&banner_id=' + id
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            closeModal('deleteModal');
            const row = document.getElementById('banner-row-' + id);
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

<!-- ============================================ -->
<!-- CSS -->
<!-- ============================================ -->
<style>
.color-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 50px;
    font-size: 12px;
    font-weight: 600;
}
</style>

<?php
include 'footer.php';
?>